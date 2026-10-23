<?php

namespace App\Services\Exports;

use App\Models\Decisions\Manufacturing;
use App\Models\Decisions\ProcurementSubAssembly;
// optional - adapt if your model is different
use App\Models\SimulationTeam;
use App\Models\SupplierComponent;
use App\Services\Reports\BalanceSheetService;
use App\Services\Reports\CorporatePnlService;
use App\Services\Reports\Supplementary\ForecastAccuracyService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

/**
 * Standalone exporter which does NOT rely on FlexScorecardService private methods.
 * It instantiates needed services internally via app(...) and computes values directly.
 *
 * Note: adapt model/field names in fallbacks (corporate summary fields, inventory model) to match your app.
 */
class ScorecardRawExportService
{
    protected $forecastSvc;

    protected $corporateSvc;

    public function __construct()
    {
        // lazy instantiate these if available
        $this->forecastSvc = app()->make(ForecastAccuracyService::class);
        $this->corporateSvc = app()->make(CorporatePnlService::class);
    }

    /**
     * Main entry: returns array(rows).
     */
    public function getRawMetrics(int $simulationId, ?int $facultyId, ?int $groupId, int $quarter): array
    {
        $rows = [];

        // fetch corporate summary once (best-effort)
        $corpSummary = $this->getCorporateSummary($simulationId, $facultyId, $groupId, $quarter);

        // helper local normalize implementation (mirrors typical normalizeMetric)
        $normalizeLocal = function ($raw, $worst, $best, $lowerIsBetter = false) {
            if (! is_numeric($raw)) {
                return null;
            }
            // If benchmarks are missing, try clamp (if raw is ratio 0..1 or >1) - we leave null if can't normalize reliably.
            if ($worst === null || $best === null) {
                // best-effort: if raw looks like percent >1 treat as percent 0..100 (e.g. forecast accuracy)
                return is_numeric($raw) ? round($raw, 6) : null;
            }
            if ($best == $worst) {
                // if identical benchmark: match behavior - return 100 if raw == best else 50
                return ($raw == $best) ? 100 : 50;
            }
            // map raw to 0..100
            if ($lowerIsBetter) {
                // lower is better: smaller raw -> closer to 100
                $pct = ($worst - $raw) / ($worst - $best);
            } else {
                $pct = ($raw - $worst) / ($best - $worst);
            }
            $pct = max(0, min(1, $pct));

            return round($pct * 100, 6);
        };

        // helper to format numbers for CSV
        $format = function ($v) {
            if ($v === null) {
                return null;
            }
            if (is_float($v) || is_float($v)) {
                return round($v, 6);
            }

            return $v;
        };

        // helper to fetch benchmarks - try to mimic your existing system but fallback to nulls
        $fetchBenchmark = function ($metricKey) {
            // If you have a Benchmark model or method, call it here. For now return nulls.
            return (object) ['worst' => null, 'avg' => null, 'best' => null];
        };

        // helper to fetch weight - if you keep weights in config or service, adapt this to read them
        $fetchWeight = function ($metricKey) {
            // if you have a weights config or DB, load here. fallback: null
            $weights = config('scorecard.weights', []); // optional config

            return $weights[$metricKey] ?? null;
        };

        //
        // Compute forecast totals and accuracy (via ForecastAccuracyService)
        //
        $forecastTotals = $this->computeForecastTotals($simulationId, $facultyId, $groupId, $quarter);
        // forecastTotals: ['forecast_units'=>int,'actual_units'=>int,'accuracy'=>float]

        //
        // Define metrics and for each try to compute raw_value & inputs (best-effort).
        // Where possible use corporate summary or forecast service.
        //
        $metrics = [
            // Financial
            'net_profit_margin' => [
                'label' => 'Net Profit Margin',
                'compute' => function () use ($corpSummary) {
                    if (! $corpSummary) {
                        return null;
                    }
                    $net = $corpSummary->netIncome ?? $corpSummary->net_income ?? null;
                    $rev = $corpSummary->revenue ?? null;
                    if (is_numeric($net) && is_numeric($rev) && $rev != 0) {
                        return ['raw' => $net / $rev, 'inputs' => ['net_income' => $net, 'revenue' => $rev], 'formula' => 'net_income / revenue'];
                    }

                    return null;
                },
            ],
            'operating_margin' => [
                'label' => 'Operating Margin',
                'compute' => function () use ($corpSummary) {
                    if (! $corpSummary) {
                        return null;
                    }
                    $op = $corpSummary->operatingIncome ?? $corpSummary->operating_income ?? null;
                    $rev = $corpSummary->revenue ?? null;
                    if (is_numeric($op) && is_numeric($rev) && $rev != 0) {
                        return ['raw' => $op / $rev, 'inputs' => ['operating_income' => $op, 'revenue' => $rev], 'formula' => 'operating_income / revenue'];
                    }

                    return null;
                },
            ],
            'return_on_assets' => [
                'label' => 'Return on Assets',
                'compute' => function () use ($simulationId, $facultyId, $groupId, $quarter, $corpSummary) {
                    try {
                        // 1) net income from corp summary (fallback to corporate service)
                        $netIncome = $corpSummary->netIncome ?? $corpSummary->net_income ?? null;
                        if ($netIncome === null && class_exists(CorporatePnlService::class)) {
                            try {
                                $corpSvc = app()->make(CorporatePnlService::class);
                                $s = $corpSvc->corporateProfitLoss($simulationId, $facultyId, $groupId, $quarter)->summary ?? null;
                                $netIncome = $s->netIncome ?? $s->net_income ?? $netIncome;
                            } catch (Throwable $e) {
                                Log::warning('return_on_assets: corporate fetch failed: '.$e->getMessage());
                            }
                        }
                        $netIncome = (float) ($netIncome ?? 0);

                        // 2) total assets via BalanceSheetService (uses the confirmed class)
                        $totalAssets = 0.0;
                        try {
                            if (class_exists(BalanceSheetService::class)) {
                                $bsSvc = app()->make(BalanceSheetService::class);
                                $bs = $bsSvc->generateBalanceSheet($simulationId, $facultyId, $groupId, $quarter);
                                $totalAssets = (float) ($bs['assets']['Total Assets'] ?? 0);
                            }
                        } catch (Throwable $e) {
                            Log::warning('return_on_assets: balance sheet fetch failed: '.$e->getMessage());
                        }

                        if ($totalAssets == 0.0) {
                            return [
                                'raw' => 0.00,
                                'inputs' => ['net_income' => $netIncome, 'total_assets' => $totalAssets],
                                'formula' => '(NetIncome / TotalAssets) * 4 — returns 0 when TotalAssets = 0',
                            ];
                        }

                        $roa = round(($netIncome / $totalAssets) * 4, 2);

                        return [
                            'raw' => $roa,
                            'inputs' => ['net_income' => $netIncome, 'total_assets' => $totalAssets],
                            'formula' => '(NetIncome / TotalAssets) * 4',
                        ];
                    } catch (Throwable $e) {
                        Log::warning('return_on_assets compute error: '.$e->getMessage());

                        return null;
                    }
                },
            ],

            'sales_growth' => [
                'label' => 'Sales Growth',
                'compute' => function () use ($simulationId, $facultyId, $groupId, $quarter) {
                    try {
                        $corp = app()->make(CorporatePnlService::class);
                        $cur = $corp->corporateProfitLoss($simulationId, $facultyId, $groupId, $quarter)->summary ?? null;
                        $prevQ = max(1, $quarter - 1);
                        $prev = $corp->corporateProfitLoss($simulationId, $facultyId, $groupId, $prevQ)->summary ?? null;
                        if ($cur && $prev) {
                            $curRev = $cur->revenue ?? null;
                            $prevRev = $prev->revenue ?? null;
                            if (is_numeric($curRev) && is_numeric($prevRev) && $prevRev != 0) {
                                return ['raw' => ($curRev - $prevRev) / $prevRev, 'inputs' => ['current_revenue' => $curRev, 'previous_revenue' => $prevRev], 'formula' => '(current - previous)/previous'];
                            }
                        }
                    } catch (Throwable $e) {
                    }

                    return null;
                },
            ],

            // Operational
            'inventory_turnover' => [
                'label' => 'Inventory Turnover',
                'compute' => function () use ($simulationId, $facultyId, $groupId, $quarter, $corpSummary) {

                    try {
                        // 1) COGS (productCost)
                        $cogs = $corpSummary->productCost
                          ?? $corpSummary->product_cost
                          ?? $corpSummary->cogs
                          ?? null;

                        // fallback: corporate service if needed
                        if ($cogs === null && class_exists(CorporatePnlService::class)) {
                            try {
                                $corpSvc = app()->make(CorporatePnlService::class);
                                $cp = $corpSvc->corporateProfitLoss($simulationId, $facultyId, $groupId, $quarter)->summary ?? null;
                                $cogs = $cp->productCost ?? $cp->product_cost ?? $cp->cogs ?? $cogs;
                            } catch (Throwable $e) {
                                Log::warning('inventory_turnover: COGS fetch failed: '.$e->getMessage());
                            }
                        }

                        // 2) Use BalanceSheetService (this path is correct now)
                        $bsSvc = app()->make(BalanceSheetService::class);

                        $prevQ = max(1, $quarter - 1);

                        $bsCurr = $bsSvc->generateBalanceSheet($simulationId, $facultyId, $groupId, $quarter);
                        $bsPrev = $bsSvc->generateBalanceSheet($simulationId, $facultyId, $groupId, $prevQ);

                        $fgCurr = isset($bsCurr['assets']['Finished Goods Inventory'])
                          ? (float) $bsCurr['assets']['Finished Goods Inventory'] : 0;

                        $fgPrev = isset($bsPrev['assets']['Finished Goods Inventory'])
                          ? (float) $bsPrev['assets']['Finished Goods Inventory'] : 0;

                        // 3) average FG
                        $avgFG = ($fgCurr + $fgPrev) / 2;

                        // simulation rule: avg FG = 0 => return 100
                        if ($avgFG == 0) {
                            return [
                                'raw' => 100.00,
                                'inputs' => [
                                    'COGS' => $cogs,
                                    'FG_Current' => $fgCurr,
                                    'FG_Previous' => $fgPrev,
                                    'AvgFG' => $avgFG,
                                ],
                                'formula' => 'productCost ÷ avg(FG_current, FG_previous) — fallback rule: 100 when AvgFG=0',
                            ];
                        }

                        // 4) Normal case
                        if (is_numeric($cogs) && $avgFG > 0) {
                            $turnover = round($cogs / $avgFG, 2);

                            return [
                                'raw' => $turnover,
                                'inputs' => [
                                    'COGS' => $cogs,
                                    'FG_Current' => $fgCurr,
                                    'FG_Previous' => $fgPrev,
                                    'AvgFG' => $avgFG,
                                ],
                                'formula' => 'productCost ÷ avg(FG_current, FG_previous)',
                            ];
                        }

                        // no usable values
                        return null;
                    } catch (Throwable $e) {
                        Log::warning('inventory_turnover compute error: '.$e->getMessage());

                        return null;
                    }
                },
            ],
            'fill_rate' => [
                'label' => 'Fill Rate',
                'compute' => function () use ($corpSummary) {

                    try {
                        // 1) Get sales volume (+ fallback)
                        $sales = $corpSummary->productSalesVolume
                          ?? $corpSummary->product_sales_volume
                          ?? 0;

                        // 2) Get unfilled orders (+ fallback)
                        $unfilled = $corpSummary->unfilledOrders
                          ?? $corpSummary->unfilled_orders
                          ?? 0;

                        $sales = (float) $sales;
                        $unfilled = (float) $unfilled;

                        // 3) Zero rule
                        if ($sales == 0) {
                            return [
                                'raw' => 0.00,
                                'inputs' => [
                                    'sales_volume' => $sales,
                                    'unfilled_orders' => $unfilled,
                                ],
                                'formula' => '((SalesVolume - UnfilledOrders) / SalesVolume) × 100',
                            ];
                        }

                        // 4) Calculate
                        $fulfilled = max(0, $sales - $unfilled);
                        $rate = round(($fulfilled / $sales) * 100, 2);

                        return [
                            'raw' => $rate,
                            'inputs' => [
                                'sales_volume' => $sales,
                                'unfilled_orders' => $unfilled,
                                'fulfilled' => $fulfilled,
                            ],
                            'formula' => '((SalesVolume - UnfilledOrders) / SalesVolume) × 100',
                        ];
                    } catch (Throwable $e) {
                        Log::warning('fill_rate compute error: '.$e->getMessage());

                        return null;
                    }
                },
            ],

            'production_deviation' => [
                'label' => 'Production Deviation',
                'compute' => function () use ($simulationId, $facultyId, $groupId, $quarter) {
                    try {
                        // 1) get corporate summary emergencyProduction if available
                        $corpSummary = null;
                        try {
                            if (class_exists(CorporatePnlService::class)) {
                                $corpSvc = app()->make(CorporatePnlService::class);
                                $corpSummary = $corpSvc->corporateProfitLoss($simulationId, $facultyId, $groupId, $quarter)->summary ?? null;
                            }
                        } catch (Throwable $e) {
                            Log::warning('production_deviation: corporate summary fetch failed: '.$e->getMessage());
                        }

                        $emergencyFromSummary = 0.0;
                        if ($corpSummary) {
                            $emergencyFromSummary = (float) ($corpSummary->emergencyProduction ?? $corpSummary->emergency_production ?? 0);
                        }

                        // 2) compute total production from Manufacturing rows (sum production_quantity + emergency_production)
                        $totalProduction = 0.0;
                        if (class_exists(Manufacturing::class)) {
                            try {
                                $totalProduction = Manufacturing::where([
                                    'simulation_id' => $simulationId,
                                    'faculty_id' => $facultyId,
                                    'group_number' => $groupId,
                                    'quarter' => $quarter,
                                ])->get()->sum(function ($item) {
                                    return (float) ($item->production_quantity ?? $item->produced ?? 0) + (float) ($item->emergency_production ?? 0);
                                });
                            } catch (Throwable $e) {
                                Log::warning('production_deviation: Manufacturing query failed: '.$e->getMessage());
                            }
                        }

                        // 3) zero rule: if no production, return 0
                        if ((float) $totalProduction == 0.0) {
                            return [
                                'raw' => 0.0,
                                'inputs' => [
                                    'emergency_from_summary' => $emergencyFromSummary,
                                    'total_production' => $totalProduction,
                                ],
                                'formula' => '(Emergency Production ÷ Total Production) × 100 — returns 0 when TotalProduction = 0',
                            ];
                        }

                        // 4) compute percentage and round to 0 decimals (match your function)
                        $percent = round(($emergencyFromSummary / (float) $totalProduction) * 100, 0);

                        return [
                            'raw' => (float) $percent,
                            'inputs' => [
                                'emergency_from_summary' => $emergencyFromSummary,
                                'total_production' => (float) $totalProduction,
                            ],
                            'formula' => '(Emergency Production ÷ Total Production) × 100',
                        ];
                    } catch (Throwable $e) {
                        Log::warning('production_deviation compute error: '.$e->getMessage());

                        return null;
                    }
                },
            ],

            'defect_rate' => [
                'label' => 'Defect Rate',
                'compute' => function () use ($simulationId, $facultyId, $groupId, $quarter) {
                    try {
                        // 1) Units sold (denominator) from corporate P&L summary
                        $unitsSold = 0.0;
                        try {
                            if (class_exists(CorporatePnlService::class)) {
                                $corpSvc = app()->make(CorporatePnlService::class);
                                $s = $corpSvc->corporateProfitLoss($simulationId, $facultyId, $groupId, $quarter)->summary ?? (object) [];
                                $unitsSold = (float) ($s->productSalesVolume ?? $s->product_sales_volume ?? 0);
                            }
                        } catch (Throwable $e) {
                            Log::warning('defect_rate: corporate summary fetch failed: '.$e->getMessage());
                        }

                        if ($unitsSold <= 0.0) {
                            return [
                                'raw' => 0.0,
                                'inputs' => ['units_sold' => $unitsSold],
                                'formula' => '(ReplacementParts ÷ SalesVolume) × 100 — units_sold == 0 => 0.0',
                            ];
                        }

                        // 2) Gather procured sub-assembly rows for this firm/quarter
                        if (! class_exists(ProcurementSubAssembly::class)) {
                            return [
                                'raw' => 0.0,
                                'inputs' => ['note' => 'ProcurementSubAssembly model missing'],
                                'formula' => '(ReplacementParts ÷ SalesVolume) × 100',
                            ];
                        }

                        $procured = ProcurementSubAssembly::with('supplier')
                            ->whereHas('procurement', function ($q) use ($simulationId, $facultyId, $groupId, $quarter) {
                                $q->where([
                                    'simulation_id' => $simulationId,
                                    'faculty_id' => $facultyId,
                                    'group_number' => $groupId,
                                    'quarter' => $quarter,
                                ]);
                            })
                            ->get(['supplier_id', 'sub_assembly_component_id', 'quantity']);

                        if ($procured->isEmpty()) {
                            return [
                                'raw' => 0.0,
                                'inputs' => ['units_sold' => $unitsSold, 'procured_rows' => 0],
                                'formula' => '(ReplacementParts ÷ SalesVolume) × 100 — no procurement rows',
                            ];
                        }

                        // 3) Load supplier-component failure rates
                        $byKey = fn ($sid, $cid) => $sid.':'.$cid;
                        $supplierIds = $procured->pluck('supplier_id')->unique()->filter()->values()->all();
                        $componentIds = $procured->pluck('sub_assembly_component_id')->unique()->filter()->values()->all();

                        $supplierComponents = collect();
                        if (class_exists(SupplierComponent::class) && ! empty($supplierIds) && ! empty($componentIds)) {
                            try {
                                $supplierComponents = SupplierComponent::whereIn('supplier_id', $supplierIds)
                                    ->whereIn('sub_assembly_component_id', $componentIds)
                                    ->get(['supplier_id', 'sub_assembly_component_id', 'failure_rate'])
                                    ->keyBy(fn ($sc) => $byKey($sc->supplier_id, $sc->sub_assembly_component_id));
                            } catch (Throwable $e) {
                                Log::warning('defect_rate: SupplierComponent query failed: '.$e->getMessage());
                            }
                        }

                        // 4) Expected failures
                        $expectedFailures = 0.0;
                        foreach ($procured as $row) {
                            $key = $byKey($row->supplier_id, $row->sub_assembly_component_id);
                            $ratePct = (float) optional($supplierComponents->get($key))->failure_rate; // e.g., 1.8
                            $qty = (float) ($row->quantity ?? 0);
                            $expectedFailures += ($qty * ($ratePct / 100.0));
                        }

                        if ($expectedFailures <= 0.0) {
                            return [
                                'raw' => 0.0,
                                'inputs' => [
                                    'units_sold' => $unitsSold,
                                    'expected_failures' => $expectedFailures,
                                ],
                                'formula' => '(ReplacementParts ÷ SalesVolume) × 100 — expectedFailures <= 0',
                            ];
                        }

                        // 5) Convert to % of units sold and round to 2 decimals
                        $rate = ($expectedFailures / $unitsSold) * 100.0;
                        $rate = round(max(0.0, $rate), 2);

                        return [
                            'raw' => $rate,
                            'inputs' => [
                                'units_sold' => $unitsSold,
                                'expected_failures' => $expectedFailures,
                                'procured_rows' => $procured->count(),
                            ],
                            'formula' => '(ReplacementParts ÷ SalesVolume) × 100',
                        ];
                    } catch (Throwable $e) {
                        Log::warning('defect_rate compute error: '.$e->getMessage());

                        return null;
                    }
                },
            ],

            'controllable_costs_to_revenue' => [
                'label' => 'Controllable Costs to Revenue',
                'compute' => function () use ($simulationId, $facultyId, $groupId, $quarter) {
                    try {
                        // prefer an already-fetched corpSummary if in scope; otherwise fetch
                        $s = null;
                        if (isset($GLOBALS['corpSummary']) && $GLOBALS['corpSummary']) {
                            $s = $GLOBALS['corpSummary'];
                        }
                        if ($s === null && class_exists(CorporatePnlService::class)) {
                            try {
                                $corpSvc = app()->make(CorporatePnlService::class);
                                $s = $corpSvc->corporateProfitLoss($simulationId, $facultyId, $groupId, $quarter)->summary ?? (object) [];
                            } catch (Throwable $e) {
                                Log::warning('controllable_costs_to_revenue: corporate summary fetch failed: '.$e->getMessage());
                            }
                        }

                        // Safely read fields (use 0.0 defaults)
                        $disposalSales = (float) ($s->disposalSales ?? $s->disposal_sales ?? 0.0);
                        $emergencyProcurement = (float) ($s->emergencyProcurement ?? $s->emergency_procurement ?? 0.0);
                        $emergencyProduction = (float) ($s->emergencyProduction ?? $s->emergency_production ?? 0.0);
                        $inventoryCharges = (float) ($s->inventoryCharges ?? $s->inventory_charges ?? 0.0);
                        $procurementFc = (float) ($s->procurementFc ?? $s->procurement_fc ?? 0.0);
                        $productionFc = (float) ($s->productionFc ?? $s->production_fc ?? 0.0);
                        $revenue = (float) ($s->revenue ?? 0.0);

                        $numerator = $disposalSales
                          + $emergencyProcurement
                          + $emergencyProduction
                          + $inventoryCharges
                          + $procurementFc
                          + $productionFc;

                        if ($revenue <= 0.0) {
                            return [
                                'raw' => 0.0,
                                'inputs' => [
                                    'disposal_sales' => $disposalSales,
                                    'emergency_procurement' => $emergencyProcurement,
                                    'emergency_production' => $emergencyProduction,
                                    'inventory_charges' => $inventoryCharges,
                                    'procurement_fc' => $procurementFc,
                                    'production_fc' => $productionFc,
                                    'revenue' => $revenue,
                                ],
                                'formula' => '(sum of controllable costs ÷ revenue) × 100 — revenue <= 0 => 0.0',
                            ];
                        }

                        $pct = ($numerator / $revenue) * 100.0;
                        $pct = max(0.0, $pct);
                        $pct = round($pct, 2);

                        return [
                            'raw' => $pct,
                            'inputs' => [
                                'disposal_sales' => $disposalSales,
                                'emergency_procurement' => $emergencyProcurement,
                                'emergency_production' => $emergencyProduction,
                                'inventory_charges' => $inventoryCharges,
                                'procurement_fc' => $procurementFc,
                                'production_fc' => $productionFc,
                                'revenue' => $revenue,
                            ],
                            'formula' => '(EmergencyProcurement + EmergencyProduction + InventoryCharges + ProcurementFC + ProductionFC + DisposalSales) ÷ Revenue × 100',
                        ];
                    } catch (Throwable $e) {
                        Log::warning('controllable_costs_to_revenue compute error: '.$e->getMessage());

                        return null;
                    }
                },
            ],

            'transportation_expense_per_unit' => [
                'label' => 'Transportation Expense Per Unit',
                'compute' => function () use ($corpSummary) {
                    if (! $corpSummary) {
                        return null;
                    }
                    $transport = $corpSummary->transportationCost ?? $corpSummary->transportation_cost ?? null;
                    $rebate = $corpSummary->transportationRebate ?? $corpSummary->transportation_rebate ?? 0;
                    $volume = $corpSummary->productSalesVolume ?? $corpSummary->product_sales_volume ?? null;
                    if (is_numeric($transport) && is_numeric($volume) && $volume != 0) {
                        return ['raw' => ($transport - ($rebate ?? 0)) / $volume, 'inputs' => ['transportationCost' => $transport, 'transportationRebate' => $rebate, 'productSalesVolume' => $volume], 'formula' => '(transportationCost - transportationRebate) / productSalesVolume'];
                    }

                    return null;
                },
            ],
            'forecast_accuracy' => [
                'label' => 'Forecast Accuracy',
                'compute' => function () use ($forecastTotals) {
                    if (isset($forecastTotals['accuracy'])) {
                        return ['raw' => $forecastTotals['accuracy'], 'inputs' => ['forecast_units' => $forecastTotals['forecast_units'], 'actual_units' => $forecastTotals['actual_units']], 'formula' => 'ForecastAccuracyService aggregate'];
                    }

                    return null;
                },
            ],

            // Customer
            'market_penetration_rate' => [
                'label' => 'Market Penetration Rate',
                'compute' => function () use ($simulationId, $facultyId, $quarter, $corpSummary) {
                    try {
                        // my sales from corporate summary
                        $mySales = (float) ($corpSummary->productSalesVolume ?? $corpSummary->product_sales_volume ?? 0);

                        // sum industry sales across active teams
                        $industrySales = 0.0;
                        try {
                            if (class_exists(SimulationTeam::class)) {
                                $groups = SimulationTeam::where('simulation_id', $simulationId)
                                    ->where('status', 1)
                                    ->pluck('group_number')
                                    ->all();
                                foreach ($groups as $gid) {
                                    try {
                                        if (class_exists(CorporatePnlService::class)) {
                                            $corpSvc = app()->make(CorporatePnlService::class);
                                            $s = $corpSvc->corporateProfitLoss($simulationId, $facultyId, $gid, $quarter)->summary ?? null;
                                            $industrySales += (float) ($s->productSalesVolume ?? $s->product_sales_volume ?? 0);
                                        }
                                    } catch (Throwable $inner) {
                                        Log::warning("market_penetration_rate: corp fetch for group {$gid} failed: ".$inner->getMessage());
                                    }
                                }
                            }
                        } catch (Throwable $e) {
                            Log::warning('market_penetration_rate: industry sales gather failed: '.$e->getMessage());
                        }

                        if ($industrySales <= 0.0) {
                            return [
                                'raw' => 0.0,
                                'inputs' => ['my_sales' => $mySales, 'industry_sales' => $industrySales],
                                'formula' => '(ProductSalesVolume ÷ TotalMarketVolume) × 100 — industrySales == 0 => 0',
                            ];
                        }

                        // market size assumption from your code: industry covers 20% of market
                        $marketSize = $industrySales / 0.20;
                        $penetration = round(($mySales / $marketSize) * 100, 2);

                        return [
                            'raw' => $penetration,
                            'inputs' => ['my_sales' => $mySales, 'industry_sales' => $industrySales, 'market_size' => $marketSize],
                            'formula' => '(ProductSalesVolume ÷ TotalMarketVolume) × 100 (marketSize = industrySales / 0.20)',
                        ];
                    } catch (Throwable $e) {
                        Log::warning('market_penetration_rate compute error: '.$e->getMessage());

                        return null;
                    }
                },
            ],

            'customer_satisfaction_index' => [
                'label' => 'Customer Satisfaction Index',
                'compute' => function () use ($simulationId, $facultyId, $groupId, $quarter, $corpSummary, $forecastTotals) {
                    try {
                        // 1) Fill Rate: use corpSummary if present (reuse your fill logic)
                        $fillRate = null;
                        try {
                            $sales = (float) ($corpSummary->productSalesVolume ?? $corpSummary->product_sales_volume ?? 0);
                            $unfilled = (float) ($corpSummary->unfilledOrders ?? $corpSummary->unfilled_orders ?? 0);
                            if ($sales == 0) {
                                $fillRate = 0.0;
                            } else {
                                $filled = max(0, $sales - $unfilled);
                                $fillRate = round(($filled / $sales) * 100, 2);
                            }
                        } catch (Throwable $e) {
                            Log::warning('customer_satisfaction_index: fillRate calc failed: '.$e->getMessage());
                            $fillRate = 0.0;
                        }

                        // 2) Defect Rate: try procurement-based approach similar to your calculateDefectRate
                        $defectRate = 0.0;
                        try {
                            if (class_exists(ProcurementSubAssembly::class)) {
                                $procured = ProcurementSubAssembly::with('supplier')
                                    ->whereHas('procurement', function ($q) use ($simulationId, $facultyId, $groupId, $quarter) {
                                        $q->where([
                                            'simulation_id' => $simulationId,
                                            'faculty_id' => $facultyId,
                                            'group_number' => $groupId,
                                            'quarter' => $quarter,
                                        ]);
                                    })
                                    ->get(['supplier_id', 'sub_assembly_component_id', 'quantity']);

                                $unitsSold = (float) ($corpSummary->productSalesVolume ?? $corpSummary->product_sales_volume ?? 0);

                                if ($unitsSold > 0 && $procured->isNotEmpty()) {
                                    $byKey = fn ($sid, $cid) => $sid.':'.$cid;
                                    $supplierIds = $procured->pluck('supplier_id')->unique()->filter()->values()->all();
                                    $componentIds = $procured->pluck('sub_assembly_component_id')->unique()->filter()->values()->all();

                                    $supplierComponents = collect();
                                    if (class_exists(SupplierComponent::class) && ! empty($supplierIds) && ! empty($componentIds)) {
                                        $supplierComponents = SupplierComponent::whereIn('supplier_id', $supplierIds)
                                            ->whereIn('sub_assembly_component_id', $componentIds)
                                            ->get(['supplier_id', 'sub_assembly_component_id', 'failure_rate'])
                                            ->keyBy(fn ($sc) => $byKey($sc->supplier_id, $sc->sub_assembly_component_id));
                                    }

                                    $expectedFailures = 0.0;
                                    foreach ($procured as $row) {
                                        $key = $byKey($row->supplier_id, $row->sub_assembly_component_id);
                                        $ratePct = (float) optional($supplierComponents->get($key))->failure_rate;
                                        $expectedFailures += ((float) $row->quantity) * ($ratePct / 100.0);
                                    }

                                    if ($expectedFailures > 0) {
                                        $defectRate = round(($expectedFailures / $unitsSold) * 100.0, 2);
                                    } else {
                                        $defectRate = 0.0;
                                    }
                                } else {
                                    $defectRate = 0.0;
                                }
                            }
                        } catch (Throwable $e) {
                            Log::warning('customer_satisfaction_index: defectRate calc failed: '.$e->getMessage());
                            $defectRate = 0.0;
                        }

                        // 3) Forecast Accuracy: try forecastTotals if available (0-100 scale expected)
                        $forecastAcc = null;
                        if (isset($forecastTotals['accuracy'])) {
                            $forecastAcc = (float) $forecastTotals['accuracy'];
                        } else {
                            // fallback to 0– if not available
                            $forecastAcc = 0.0;
                        }

                        // 4) Price / ASP score
                        $myRevenue = (float) ($corpSummary->revenue ?? 0);
                        $myUnits = (float) ($corpSummary->productSalesVolume ?? 0);
                        $myAsp = ($myUnits > 0) ? ($myRevenue / $myUnits) : 0.0;

                        $marketAspTotal = 0.0;
                        $marketUnits = 0.0;
                        if (class_exists(SimulationTeam::class)) {
                            $allGroups = SimulationTeam::where('simulation_id', $simulationId)
                                ->where('status', 1)
                                ->pluck('group_number');
                            foreach ($allGroups as $gid) {
                                try {
                                    if (class_exists(CorporatePnlService::class)) {
                                        $corpSvc = app()->make(CorporatePnlService::class);
                                        $summary = $corpSvc->corporateProfitLoss($simulationId, $facultyId, $gid, $quarter)->summary ?? (object) [];
                                        $rev = (float) ($summary->revenue ?? 0);
                                        $units = (float) ($summary->productSalesVolume ?? 0);
                                        if ($units > 0) {
                                            $marketAspTotal += $rev;
                                            $marketUnits += $units;
                                        }
                                    }
                                } catch (Throwable $e) {
                                    Log::warning("customer_satisfaction_index: market ASP fetch failed for group {$gid}: ".$e->getMessage());
                                }
                            }
                        }

                        $marketAsp = ($marketUnits > 0) ? ($marketAspTotal / $marketUnits) : 0.0;

                        if ($marketAsp > 0 && $myAsp > 0) {
                            $gapPct = (($myAsp - $marketAsp) / $marketAsp) * 100;
                            $priceScore = max(0, 100 - $gapPct);
                        } else {
                            $priceScore = 80; // fallback
                        }

                        // 5) Quality score (higher is better)
                        $qualityScore = max(0, 100 - $defectRate);

                        // 6) weights and aggregation
                        $w1 = 0.4; // Fill rate
                        $w2 = 0.3; // Quality
                        $w3 = 0.2; // Forecast accuracy
                        $w4 = 0.1; // Price

                        $csi = ($w1 * $fillRate) + ($w2 * $qualityScore) + ($w3 * $forecastAcc) + ($w4 * $priceScore);
                        $csi = round($csi, 0);

                        return [
                            'raw' => $csi,
                            'inputs' => [
                                'fill_rate' => $fillRate,
                                'defect_rate' => $defectRate,
                                'forecast_accuracy' => $forecastAcc,
                                'price_score' => $priceScore,
                                'my_asp' => $myAsp,
                                'market_asp' => $marketAsp,
                            ],
                            'formula' => 'weighted combination of fillRate(40%), quality(30%), forecastAcc(20%), price(10%)',
                        ];
                    } catch (Throwable $e) {
                        Log::warning('customer_satisfaction_index compute error: '.$e->getMessage());

                        return null;
                    }
                },
            ],
            // External
            'revenue_growth' => [
                'label' => 'Revenue Growth',
                'compute' => function () use ($simulationId, $facultyId, $groupId, $quarter) {
                    // fallback to sales_growth computation
                    try {
                        $corp = app()->make(CorporatePnlService::class);
                        $cur = $corp->corporateProfitLoss($simulationId, $facultyId, $groupId, $quarter)->summary ?? null;
                        $prev = $corp->corporateProfitLoss($simulationId, $facultyId, $groupId, max(1, $quarter - 1))->summary ?? null;
                        if ($cur && $prev) {
                            $curRev = $cur->revenue ?? null;
                            $prevRev = $prev->revenue ?? null;
                            if (is_numeric($curRev) && is_numeric($prevRev) && $prevRev != 0) {
                                return ['raw' => ($curRev - $prevRev) / $prevRev, 'inputs' => ['current_revenue' => $curRev, 'previous_revenue' => $prevRev], 'formula' => '(curr - prev)/prev'];
                            }
                        }
                    } catch (Throwable $e) {
                    }

                    return null;
                },
            ],
            'earnings_growth' => [
                'label' => 'Earnings Growth',
                'compute' => function () use ($simulationId, $facultyId, $groupId, $quarter) {
                    try {
                        $corp = app()->make(CorporatePnlService::class);
                        $cur = $corp->corporateProfitLoss($simulationId, $facultyId, $groupId, $quarter)->summary ?? null;
                        $prev = $corp->corporateProfitLoss($simulationId, $facultyId, $groupId, max(1, $quarter - 1))->summary ?? null;
                        if ($cur && $prev) {
                            $curE = $cur->netIncome ?? $cur->net_income ?? null;
                            $prevE = $prev->netIncome ?? $prev->net_income ?? null;
                            if (is_numeric($curE) && is_numeric($prevE) && $prevE != 0) {
                                return ['raw' => ($curE - $prevE) / $prevE, 'inputs' => ['curr' => $curE, 'prev' => $prevE], 'formula' => '(curr - prev)/prev'];
                            }
                        }
                    } catch (Throwable $e) {
                    }

                    return null;
                },
            ],
            'consistency_score' => [
                'label' => 'Consistency Score',
                'compute' => function () use ($simulationId, $facultyId, $groupId, $quarter) {
                    try {
                        // Need at least 3 quarters to compute
                        if ($quarter < 3) {
                            return null;
                        }

                        $startQ = $quarter - 2;
                        $net = [];

                        // Fetch net income for the 3-quarter rolling window
                        for ($q = $startQ; $q <= $quarter; $q++) {
                            try {
                                if (class_exists(CorporatePnlService::class)) {
                                    $corpSvc = app()->make(CorporatePnlService::class);
                                    $summary = $corpSvc->corporateProfitLoss($simulationId, $facultyId, $groupId, $q)->summary ?? (object) [];
                                    $net[] = (float) ($summary->netIncome ?? $summary->net_income ?? 0.0);
                                } else {
                                    // If service missing, push 0 and continue
                                    $net[] = 0.0;
                                }
                            } catch (Throwable $e) {
                                Log::warning("consistency_score: failed to fetch corp summary for quarter {$q}: ".$e->getMessage());
                                $net[] = 0.0;
                            }
                        }

                        // Ensure we have 3 values
                        if (count($net) !== 3) {
                            return null;
                        }

                        // Strictly increasing check
                        $isGrowing = ($net[1] > $net[0]) && ($net[2] > $net[1]);

                        return [
                            'raw' => $isGrowing ? 1 : 0,
                            'inputs' => [
                                'quarters' => [$startQ, $startQ + 1, $quarter],
                                'net_incomes' => $net,
                            ],
                            'formula' => '1 if netIncome(q-2) < netIncome(q-1) < netIncome(q) else 0 (needs 3 quarters)',
                        ];
                    } catch (Throwable $e) {
                        Log::warning('consistency_score compute error: '.$e->getMessage());

                        return null;
                    }
                },
            ],
            // FI Index (we'll compute as simple aggregation of a few components if available)
            'fi_index' => [
                'label' => 'FI Index',
                'compute' => function () use ($simulationId, $facultyId, $groupId, $quarter, $corpSummary) {
                    try {
                        // Helper clamp (0..100)
                        $clamp = function ($v, $min = 0.0, $max = 100.0) {
                            if (! is_numeric($v)) {
                                return 0.0;
                            }
                            $v = (float) $v;
                            if ($v < $min) {
                                return $min;
                            }
                            if ($v > $max) {
                                return $max;
                            }

                            return $v;
                        };

                        // --- Profitability: avg(Net Profit Margin %, Operating Margin %) scaled to 0..100
                        $netIncome = null;
                        $opIncome = null;
                        $revenue = null;
                        if ($corpSummary) {
                            $netIncome = $corpSummary->netIncome ?? $corpSummary->net_income ?? $corpSummary->net ?? $netIncome;
                            $opIncome = $corpSummary->operatingIncome ?? $corpSummary->operating_income ?? $opIncome;
                            $revenue = $corpSummary->revenue ?? $corpSummary->rev ?? $revenue;
                        }
                        // fallback fetch
                        if (($netIncome === null || $opIncome === null || $revenue === null) && class_exists(CorporatePnlService::class)) {
                            try {
                                $corpSvc = app()->make(CorporatePnlService::class);
                                $cur = $corpSvc->corporateProfitLoss($simulationId, $facultyId, $groupId, $quarter)->summary ?? null;
                                $netIncome = $netIncome ?? ($cur->netIncome ?? $cur->net_income ?? null);
                                $opIncome = $opIncome ?? ($cur->operatingIncome ?? $cur->operating_income ?? null);
                                $revenue = $revenue ?? ($cur->revenue ?? null);
                            } catch (Throwable $e) {
                                Log::warning('fi_index: corporate summary fetch failed: '.$e->getMessage());
                            }
                        }

                        $npmPct = (is_numeric($netIncome) && is_numeric($revenue) && $revenue != 0) ? (($netIncome / $revenue) * 100.0) : 0.0;
                        $opmPct = (is_numeric($opIncome) && is_numeric($revenue) && $revenue != 0) ? (($opIncome / $revenue) * 100.0) : 0.0;
                        $profitability = $clamp((($npmPct + $opmPct) / 2.0));

                        // --- Growth: avg(Revenue Growth %, Earnings Growth %) clamp negatives to 0..100
                        $revGrowth = 0.0;
                        $earningsGrowth = 0.0;
                        try {
                            if (class_exists(CorporatePnlService::class)) {
                                $corpSvc = app()->make(CorporatePnlService::class);
                                $cur = $corpSvc->corporateProfitLoss($simulationId, $facultyId, $groupId, $quarter)->summary ?? null;
                                $prev = $corpSvc->corporateProfitLoss($simulationId, $facultyId, $groupId, max(1, $quarter - 1))->summary ?? null;
                                if ($cur && $prev) {
                                    $curRev = $cur->revenue ?? null;
                                    $prevRev = $prev->revenue ?? null;
                                    if (is_numeric($curRev) && is_numeric($prevRev) && $prevRev != 0) {
                                        $revGrowth = (($curRev - $prevRev) / $prevRev) * 100.0;
                                    }
                                    $curE = $cur->netIncome ?? $cur->net_income ?? null;
                                    $prevE = $prev->netIncome ?? $prev->net_income ?? null;
                                    if (is_numeric($curE) && is_numeric($prevE) && $prevE != 0) {
                                        $earningsGrowth = (($curE - $prevE) / $prevE) * 100.0;
                                    }
                                }
                            }
                        } catch (Throwable $e) {
                            Log::warning('fi_index: growth fetch failed: '.$e->getMessage());
                        }
                        // clamp negatives to 0 per spec
                        $growth = $clamp((max(0, $revGrowth) + max(0, $earningsGrowth)) / 2.0);

                        // --- Efficiency: Return on Assets (financial) × 10 (ROA in earlier block = (netIncome/totalAssets)*4)
                        $roa = 0.0;
                        try {
                            if (class_exists(BalanceSheetService::class) && class_exists(CorporatePnlService::class)) {
                                $bsSvc = app()->make(BalanceSheetService::class);
                                $bs = $bsSvc->generateBalanceSheet($simulationId, $facultyId, $groupId, $quarter);
                                $totalAssets = (float) ($bs['assets']['Total Assets'] ?? 0);
                                $net = $netIncome ?? 0.0;
                                if ($totalAssets != 0) {
                                    $roa = (($net / $totalAssets) * 4.0); // matches your formula
                                } else {
                                    $roa = 0.0;
                                }
                            } else {
                                // fallback try compute from corpSummary & BS in other ways
                                $bs = null;
                                if (class_exists(BalanceSheetService::class)) {
                                    try {
                                        $bsSvc = app()->make(BalanceSheetService::class);
                                        $bs = $bsSvc->generateBalanceSheet($simulationId, $facultyId, $groupId, $quarter);
                                    } catch (Throwable $e) {
                                    }
                                }
                                $totalAssets = (float) ($bs['assets']['Total Assets'] ?? 0);
                                $net = $netIncome ?? 0.0;
                                if ($totalAssets != 0) {
                                    $roa = (($net / $totalAssets) * 4.0);
                                }
                            }
                        } catch (Throwable $e) {
                            Log::warning('fi_index: efficiency (ROA) fetch failed: '.$e->getMessage());
                            $roa = 0.0;
                        }
                        $efficiency = $clamp($roa * 10.0);

                        // --- Stability: consistency score (0/1) × 10 → 0..10 scaled to 0..100 by *10
                        $stabilityRaw = null;
                        try {
                            // compute consistency as in your logic: need 3 quarters
                            if ($quarter >= 3 && class_exists(CorporatePnlService::class)) {
                                $startQ = $quarter - 2;
                                $netArr = [];
                                $corpSvc = app()->make(CorporatePnlService::class);
                                for ($q = $startQ; $q <= $quarter; $q++) {
                                    $sum = $corpSvc->corporateProfitLoss($simulationId, $facultyId, $groupId, $q)->summary ?? null;
                                    $netArr[] = (float) ($sum->netIncome ?? $sum->net_income ?? 0);
                                }
                                if (count($netArr) === 3) {
                                    $isGrowing = ($netArr[1] > $netArr[0]) && ($netArr[2] > $netArr[1]);
                                    $stabilityRaw = $isGrowing ? 1 : 0;
                                }
                            }
                        } catch (Throwable $e) {
                            Log::warning('fi_index: stability fetch failed: '.$e->getMessage());
                        }

                        // --- Compose FI index per weights
                        $profitability_w = 0.30;
                        $growth_w = 0.30;
                        $stability_w = 0.20;
                        $efficiency_w = 0.20;

                        // start with required components
                        $fi = 0.0;
                        $fi += $profitability_w * $profitability;
                        $fi += $growth_w * $growth;
                        $fi += $efficiency_w * $efficiency;

                        // optionally add stability if available (scale to 0..100)
                        if ($stabilityRaw !== null) {
                            $stability = $clamp($stabilityRaw * 10.0); // 1 -> 10 -> clamp -> then * weight
                            $fi += $stability_w * $stability;
                        } else {
                            // if consistency not computable, rescale weights proportionally among available ones
                            $available = $profitability_w + $growth_w + $efficiency_w; // 0.8
                            if ($available > 0) {
                                $scale = 1.0 / $available;
                                $fi = ($fi / 1.0) * $scale; // normalize to 0..100 scale roughly
                            }
                        }

                        // final clamp & round
                        $fi = max(0.0, min(100.0, (float) $fi));
                        $fi = round($fi, 2);

                        return [
                            'raw' => $fi,
                            'inputs' => [
                                'netIncome' => (float) ($netIncome ?? 0),
                                'operatingIncome' => (float) ($opIncome ?? 0),
                                'revenue' => (float) ($revenue ?? 0),
                                'revenue_growth_pct' => (float) $revGrowth,
                                'earnings_growth_pct' => (float) $earningsGrowth,
                                'roa' => (float) $roa,
                                'stability_raw' => $stabilityRaw,
                            ],
                            'formula' => '0.30*Profitability + 0.30*Growth + 0.20*Efficiency (+0.20*Stability if available); components scaled to 0..100',
                        ];
                    } catch (Throwable $e) {
                        Log::warning('fi_index compute error: '.$e->getMessage());

                        return null;
                    }
                },
            ],

        ];

        // iterate metrics and build rows
        foreach ($metrics as $key => $meta) {
            $label = $meta['label'] ?? $key;
            $computed = null;
            try {
                $computed = is_callable($meta['compute']) ? $meta['compute']() : null;
            } catch (Throwable $e) {
                Log::warning("Metric {$key} compute error: ".$e->getMessage());
            }

            $raw = $computed['raw'] ?? null;
            $inputs = $computed['inputs'] ?? null;
            $formulaText = $computed['formula'] ?? ($meta['formula'] ?? null);
            $bench = $fetchBenchmark($key);
            $normalized = null;
            if (is_numeric($raw)) {
                $normalized = $normalizeLocal($raw, $bench->worst ?? null, $bench->best ?? null, $meta['lowerIsBetter'] ?? false);
            }
            $weight = $fetchWeight($key);
            $rows[] = [
                'metric_key' => $key,
                'label' => $label,
                'raw_value' => $format($raw),
                'inputs' => $inputs,
                'formula' => $formulaText,
                'benchmarks' => ['worst' => $bench->worst ?? null, 'avg' => $bench->avg ?? null, 'best' => $bench->best ?? null],
                'normalized' => $format($normalized),
                'weight' => is_numeric($weight) ? $weight : $weight,
                'notes' => $computed ? 'computed via local fallback' : 'no data - check source models/services',
            ];
        }

        // ensure the forecast_units and actual_units rows appear at top (like your requested sample)
        if ($forecastTotals) {
            array_unshift($rows, [
                'metric_key' => 'actual_units',
                'label' => 'Actual Units (sum)',
                'raw_value' => $format($forecastTotals['actual_units']),
                'inputs' => ['method' => 'ForecastAccuracyService::buildForecastActualRows'],
                'formula' => 'SUM(actual rows)',
                'benchmarks' => ['worst' => null, 'avg' => null, 'best' => null],
                'normalized' => null,
                'weight' => null,
                'notes' => 'computed from ForecastAccuracyService',
            ]);
            array_unshift($rows, [
                'metric_key' => 'forecast_units',
                'label' => 'Forecast Units (sum)',
                'raw_value' => $format($forecastTotals['forecast_units']),
                'inputs' => ['method' => 'ForecastAccuracyService::buildForecastActualRows'],
                'formula' => 'SUM(forecast rows)',
                'benchmarks' => ['worst' => null, 'avg' => null, 'best' => null],
                'normalized' => null,
                'weight' => null,
                'notes' => 'computed from ForecastAccuracyService',
            ]);
        }

        // optionally add controllable cost lines (from corporate summary)
        if ($corpSummary) {
            $contItems = [
                ['key' => 'revenue', 'label' => 'Revenue ($)', 'field' => 'revenue'],
                ['key' => 'emergency_procurement', 'label' => 'Emergency Procurement ($)', 'field' => 'emergencyProcurement'],
                ['key' => 'emergency_production', 'label' => 'Emergency Production ($)', 'field' => 'emergencyProduction'],
                ['key' => 'inventory_charges', 'label' => 'Inventory Charges ($)', 'field' => 'inventoryCharges'],
                ['key' => 'procurement_fc', 'label' => 'Procurement FC ($)', 'field' => 'procurementFc'],
                ['key' => 'production_fc', 'label' => 'Production FC ($)', 'field' => 'productionFc'],
            ];
            foreach ($contItems as $ci) {
                $val = $corpSummary->{$ci['field']} ?? $corpSummary->{Str::snake($ci['field'])} ?? null;
                $rows[] = [
                    'metric_key' => $ci['key'],
                    'label' => $ci['label'],
                    'raw_value' => $format($val),
                    'inputs' => ['source' => 'corporateProfitLoss.summary.'.$ci['field']],
                    'formula' => $ci['label'],
                    'benchmarks' => ['worst' => null, 'avg' => null, 'best' => null],
                    'normalized' => null,
                    'weight' => null,
                    'notes' => 'pulled from corporate P&L summary if available',
                ];
            }
        }

        return $rows;
    }

    /**
     * Build forecast totals and accuracy using ForecastAccuracyService if available
     */
    protected function computeForecastTotals(int $simulationId, ?int $facultyId, ?int $groupId, int $quarter): ?array
    {
        try {
            if (! $this->forecastSvc) {
                return null;
            }
            if (method_exists($this->forecastSvc, 'buildForecastActualRows')) {
                $rows = $this->forecastSvc->buildForecastActualRows($simulationId, $facultyId, $groupId, $quarter);
                $totalForecast = 0;
                $totalActual = 0;
                foreach ($rows as $r) {
                    $totalForecast += (float) ($r['forecast'] ?? 0);
                    $totalActual += (float) ($r['actual'] ?? 0);
                }
                $accuracy = null;
                if (method_exists($this->forecastSvc, 'computeAggregateAccuracy')) {
                    $accuracy = $this->forecastSvc->computeAggregateAccuracy($rows, 100);
                } elseif (method_exists($this->forecastSvc, 'getOverallForecastAccuracy')) {
                    $accuracy = $this->forecastSvc->getOverallForecastAccuracy($simulationId, $facultyId, $groupId, $quarter);
                }

                return ['forecast_units' => $totalForecast, 'actual_units' => $totalActual, 'accuracy' => $accuracy];
            }
        } catch (Throwable $e) {
            Log::warning('computeForecastTotals error: '.$e->getMessage());
        }

        return null;
    }

    protected function getCorporateSummary($simulationId, $facultyId, $groupId, $quarter)
    {
        try {
            if ($this->corporateSvc && method_exists($this->corporateSvc, 'corporateProfitLoss')) {
                $res = $this->corporateSvc->corporateProfitLoss($simulationId, $facultyId, $groupId, $quarter);

                return $res->summary ?? null;
            }
        } catch (Throwable $e) {
            Log::warning('getCorporateSummary error: '.$e->getMessage());
        }

        return null;
    }
}
