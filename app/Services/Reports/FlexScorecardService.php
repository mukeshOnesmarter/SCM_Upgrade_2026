<?php

namespace App\Services\Reports;

use App\Models\Decisions\Manufacturing;
use App\Models\Decisions\ProcurementSubAssembly;
use App\Models\Decisions\Scorecard;
use App\Models\SimulationTeam;
use App\Models\SupplierComponent;
use App\Services\Reports\Supplementary\ForecastAccuracyService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class FlexScorecardService
{
    protected CorporatePnlService $dataCollection;

    protected BalanceSheetService $balanceSheet;

    protected ForecastAccuracyService $forecastAccuracyService;

    public function __construct(
        CorporatePnlService $corporatePnlService,
        BalanceSheetService $balanceSheetService,
        ForecastAccuracyService $forecastAccuracyService
    ) {
        $this->dataCollection = $corporatePnlService;
        $this->balanceSheet = $balanceSheetService;
        $this->forecastAccuracyService = $forecastAccuracyService;
    }

    /* ===============================================================
     |  ENTRY POINT — GROUP-SAFE, BENCHMARK-CACHED
     =============================================================== */
    public function storeScorecardAtomic(
        int $simulation_id,
        int $faculty_id,
        int $group_id,
        int $quarter
    ): void {

        /* ------------------------------------------------------------
         |  HARD DB IDEMPOTENCY
         ------------------------------------------------------------ */
        if (Scorecard::where([
            'simulation_id' => $simulation_id,
            'faculty_id' => $faculty_id,
            'group_id' => $group_id,
            'quarter' => $quarter,
        ])->exists()) {
            return;
        }

        /* ------------------------------------------------------------
         |  DELETE OLD ROWS (SAFE RE-RUN)
         ------------------------------------------------------------ */
        DB::table('scorecards')
            ->where(compact('simulation_id', 'faculty_id', 'group_id', 'quarter'))
            ->delete();

        /* ------------------------------------------------------------
         |  LOAD / BUILD BENCHMARKS (ONCE PER QUARTER)
         ------------------------------------------------------------ */
        $benchmarkKey = "scorecard-benchmarks:sim:$simulation_id:q:$quarter";

        $benchmarks = Cache::remember(
            $benchmarkKey,
            now()->addHours(2),
            function () use ($simulation_id, $faculty_id, $quarter) {

                $groups = SimulationTeam::where('simulation_id', $simulation_id)
                    ->where('status', 1)
                    ->pluck('group_number');

                $allScorecards = [];

                foreach ($groups as $gid) {
                    $allScorecards[$gid] = $this->generateScorecard(
                        $simulation_id,
                        $faculty_id,
                        $gid,
                        $quarter
                    );
                }

                return $this->buildBenchmarksFromScorecards($allScorecards);
            }
        );

        /* ------------------------------------------------------------
         |  GENERATE SCORECARD FOR THIS GROUP ONLY
         ------------------------------------------------------------ */
        $scorecard = $this->generateScorecard(
            $simulation_id,
            $faculty_id,
            $group_id,
            $quarter
        );

        $weights = $this->defaultWeights();
        $units = $this->unitsMap();

        $rows = [];
        $categoryScores = [];

        $categories = ['financial', 'operational', 'customer', 'external'];

        foreach ($categories as $category) {
            $categoryScores[$category] =
                $this->calculateCategoryPerformanceFromData(
                    $scorecard,
                    $category,
                    $weights
                );
        }

        foreach ($scorecard as $category => $metrics) {
            foreach ($metrics as $metric => $value) {
                $bm = $benchmarks[$category][$metric] ?? [];

                [$worst, $best] = $this->adjustBenchmarkForDisplay(
                    $metric,
                    $bm['worst'] ?? null,
                    $bm['industry_best'] ?? null
                );

                $rows[] = [
                    'simulation_id' => $simulation_id,
                    'faculty_id' => $faculty_id,
                    'group_id' => $group_id,
                    'quarter' => $quarter,
                    'category' => $category,
                    'sub_measure' => $metric,
                    'your_firm' => round((float) $value, 2),
                    'industry_worst' => $worst,
                    'industry_avg' => $bm['average'] ?? null,
                    'industry_best' => $best,
                    'unit' => $units[$metric] ?? null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
        }

        /* ------------------------------------------------------------
         |  CATEGORY TOTALS
         ------------------------------------------------------------ */
        foreach ($categoryScores as $category => $score) {
            $rows[] = [
                'simulation_id' => $simulation_id,
                'faculty_id' => $faculty_id,
                'group_id' => $group_id,
                'quarter' => $quarter,
                'category' => $category,
                'sub_measure' => 'Overall Performance',
                'your_firm' => round($score, 2),
                'industry_worst' => null,
                'industry_avg' => null,
                'industry_best' => null,
                'unit' => 'pts',
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        /* ------------------------------------------------------------
         |  OVERALL PERFORMANCE
         ------------------------------------------------------------ */
        $rows[] = [
            'simulation_id' => $simulation_id,
            'faculty_id' => $faculty_id,
            'group_id' => $group_id,
            'quarter' => $quarter,
            'category' => 'summary',
            'sub_measure' => 'Overall Performance',
            'your_firm' => round(array_sum($categoryScores), 2),
            'industry_worst' => null,
            'industry_avg' => null,
            'industry_best' => null,
            'unit' => 'index',
            'created_at' => now(),
            'updated_at' => now(),
        ];

        DB::table('scorecards')->insert($rows);
    }

    /* ===============================================================
     |  PURE CATEGORY CALCULATION
     =============================================================== */
    private function calculateCategoryPerformanceFromData(
        array $scorecard,
        string $category,
        array $weights
    ): float {
        $total = 0.0;

        foreach ($scorecard[$category] as $metric => $raw) {
            $weight = $weights[$category][$metric] ?? 0;
            $total += ((float) $raw) * $weight;
        }

        return round($total, 2);
    }

    /* ===============================================================
     |  SCORECARD GENERATION (SINGLE GROUP)
     =============================================================== */
    /**
     * Worst, average and best of each measure across the course's active
     * teams for one quarter. Called by the student KPI page; the old code
     * called it but never defined it, so that page failed. Uses the same
     * cache and the same calculation as storeScorecardAtomic.
     */
    public function generateBenchmarks($simulation_id, $faculty_id, $quarter)
    {
        return Cache::remember(
            "scorecard-benchmarks:sim:$simulation_id:q:$quarter",
            now()->addHours(2),
            function () use ($simulation_id, $faculty_id, $quarter) {
                $allScorecards = [];

                foreach (SimulationTeam::where('simulation_id', $simulation_id)->where('status', 1)->pluck('group_number') as $gid) {
                    $allScorecards[$gid] = $this->generateScorecard($simulation_id, $faculty_id, $gid, $quarter);
                }

                return $this->buildBenchmarksFromScorecards($allScorecards);
            }
        );
    }

    public function generateScorecard(
        int $simulation_id,
        int $faculty_id,
        int $group_id,
        int $quarter
    ): array {
        return [
            'financial' => [
                'Net Profit Margin' => $this->calculateNetProfitMargin($simulation_id, $faculty_id, $group_id, $quarter),
                'Operating Margin' => $this->calculateOperatingMargin($simulation_id, $faculty_id, $group_id, $quarter),
                'Return on Assets' => $this->calculateInventoryTurns($simulation_id, $faculty_id, $group_id, $quarter),
                'Sales Growth' => $this->calculateSalesGrowth($simulation_id, $faculty_id, $group_id, $quarter),
            ],
            'operational' => [
                'Inventory Turnover' => $this->calculateInventoryTurnover($simulation_id, $faculty_id, $group_id, $quarter, $quarter - 1),
                'Fill Rate' => $this->calculateFillRate($simulation_id, $faculty_id, $group_id, $quarter),
                'Forecast Accuracy' => $this->calculateForecastAccuracy($simulation_id, $faculty_id, $group_id, $quarter),
                'Production Deviation' => $this->calculateProductionDeviation($simulation_id, $faculty_id, $group_id, $quarter),
                'Defect Rate' => $this->calculateDefectRate($simulation_id, $faculty_id, $group_id, $quarter),
                'Controllable Costs to Revenue' => $this->calculateControllableCostsToRevenue($simulation_id, $faculty_id, $group_id, $quarter),
                'Transportation Expense Per Unit' => $this->calculateTransportationExpensesPerUnit($simulation_id, $faculty_id, $group_id, $quarter),
            ],
            'customer' => [
                'Market Penetration Rate' => $this->calculateMarketPenetrationRate($simulation_id, $faculty_id, $group_id, $quarter),
                'Customer Satisfaction Index' => $this->calculateCustomerSatisfactionIndex($simulation_id, $faculty_id, $group_id, $quarter),
            ],
            'external' => [
                'Revenue Growth' => $this->calculateRevenueGrowth($simulation_id, $faculty_id, $group_id, $quarter),
                'Earnings Growth' => $this->calculateEarningsGrowth($simulation_id, $faculty_id, $group_id, $quarter),
                'Consistency Score' => $this->calculateConsistencyScore($simulation_id, $faculty_id, $group_id, $quarter),
            ],
            'fi_index' => [
                'FI Index' => $this->calculateFIIndex($simulation_id, $faculty_id, $group_id, $quarter),
            ],
        ];
    }

    /* ===============================================================
     |  BENCHMARK BUILDER (IN-MEMORY)
     =============================================================== */
    private function buildBenchmarksFromScorecards(array $allScorecards): array
    {
        $data = [];

        foreach ($allScorecards as $scorecard) {
            foreach ($scorecard as $cat => $metrics) {
                foreach ($metrics as $metric => $value) {
                    $data[$cat][$metric][] = (float) $value;
                }
            }
        }

        foreach ($data as $cat => $metrics) {
            foreach ($metrics as $metric => $values) {
                sort($values);
                $data[$cat][$metric] = [
                    'worst' => min($values),
                    'average' => array_sum($values) / count($values),
                    'industry_best' => max($values),
                ];
            }
        }

        return $data;
    }

    /* ===============================================================
     |  HELPERS
     =============================================================== */
    private function adjustBenchmarkForDisplay(string $metric, ?float $worst, ?float $best): array
    {
        $reverse = [
            'Defect Rate',
            'Transportation Expense Per Unit',
            'Controllable Costs to Revenue',
            'Production Deviation',
        ];

        return in_array($metric, $reverse, true)
            ? [$best, $worst]
            : [$worst, $best];
    }

    private function unitsMap(): array
    {
        return [
            'Net Profit Margin' => '%',
            'Operating Margin' => '%',
            'Return on Assets' => '%',
            'Sales Growth' => '%',
            'Inventory Turnover' => 'x',
            'Fill Rate' => '%',
            'Forecast Accuracy' => '%',
            'Production Deviation' => '%',
            'Defect Rate' => '%',
            'Controllable Costs to Revenue' => '%',
            'Transportation Expense Per Unit' => '$',
            'Market Penetration Rate' => '%',
            'Customer Satisfaction Index' => '%',
            'Revenue Growth' => '%',
            'Earnings Growth' => '%',
            'Consistency Score' => 'pts',
            'Overall Performance' => 'pts',
        ];
    }

    private function defaultWeights(): array
    {
        return [
            'financial' => [
                'Net Profit Margin' => 0.30 * (3 / 7),
                'Operating Margin' => 0.30 * (1 / 7),
                'Return on Assets' => 0.30 * (2 / 7),
                'Sales Growth' => 0.30 * (1 / 7),
            ],
            'operational' => [
                'Inventory Turnover' => 0.30 * (2 / 9),
                'Fill Rate' => 0.30 * (1 / 9),
                'Forecast Accuracy' => 0.30 * (2 / 9),
                'Production Deviation' => 0.30 * (-1 / 9),
                'Defect Rate' => 0.30 * (-1 / 9),
                'Controllable Costs to Revenue' => 0.30 * (-1 / 9),
                'Transportation Expense Per Unit' => 0.30 * (-1 / 9),
            ],
            'customer' => [
                'Market Penetration Rate' => 0.20 * (1 / 3),
                'Customer Satisfaction Index' => 0.20 * (2 / 3),
            ],
            'external' => [
                'Revenue Growth' => 0.20 * (1 / 3),
                'Earnings Growth' => 0.20 * (1 / 3),
                'Consistency Score' => 0.20 * (1 / 3),
            ],
        ];
    }

    /* ===============================================================
     |  KPI CALCULATORS
     =============================================================== */

    // calculateNetProfitMargin()
    // calculateOperatingMargin()
    // calculateInventoryTurns()
    // calculateSalesGrowth()
    // calculateInventoryTurnover()
    // calculateFillRate()
    // calculateProductionDeviation()
    // calculateDefectRate()
    // calculateControllableCostsToRevenue()
    // calculateTransportationExpensesPerUnit()
    // calculateForecastAccuracy()
    // calculateMarketPenetrationRate()
    // calculateCustomerSatisfactionIndex()
    // calculateRevenueGrowth()
    // calculateEarningsGrowth()
    // calculateConsistencyScore()

    // /// FINANCIAL METRIC ////

    /**
     * Calculate Net Profit Margin.
     *
     * Measures profitability after all expenses.
     * Formula: (Net Income / Revenue) * 100
     * Returns 0 if revenue is zero.
     */
    public function calculateNetProfitMargin($simulation_id, $faculty_id, $group_id, $quarter_id)
    {
        $s = $this->dataCollection->corporateProfitLoss($simulation_id, $faculty_id, $group_id, $quarter_id)->summary ?? (object) [];
        $rev = (float) ($s->revenue ?? 0);
        if ($rev == 0) {
            return 0.00;
        }

        return round(((float) ($s->netIncome ?? 0) / $rev) * 100, 2);
    }

    /**
     * Calculate Operating Margin.
     *
     * Measures operating efficiency before interest/taxes.
     * Formula: (Operating Income / Revenue) * 100
     * Returns 0 if revenue is zero.
     */
    public function calculateOperatingMargin($simulation_id, $faculty_id, $group_id, $quarter_id)
    {
        $s = $this->dataCollection
            ->corporateProfitLoss($simulation_id, $faculty_id, $group_id, $quarter_id)
            ->summary ?? (object) [];

        $rev = (float) ($s->revenue ?? 0);
        if ($rev == 0) {
            return 0.00;
        }

        $opIncome = (float) ($s->operatingIncome ?? 0);

        return round(($opIncome / $rev) * 100, 2);
    }

    /**
     * Calculate Return on Assets (Financial).
     *
     * Proxy for asset efficiency using profitability.
     * Formula: (Net Income / Total Assets) * 4
     * Returns 0 if total assets is zero.
     */
    public function calculateInventoryTurns($simulation_id, $faculty_id, $group_id, $quarter_id)
    {
        $s = $this->dataCollection
            ->corporateProfitLoss($simulation_id, $faculty_id, $group_id, $quarter_id)
            ->summary ?? (object) [];

        $bs = $this->balanceSheet
            ->generateBalanceSheet($simulation_id, $faculty_id, $group_id, $quarter_id);

        $netIncome = (float) ($s->netIncome ?? 0);
        $totalAssets = (float) ($bs['assets']['Total Assets'] ?? 0);

        if ($totalAssets == 0) {
            return 0.00;
        }

        return round(($netIncome / $totalAssets) * 4, 2);
    }

    /**
     * Calculate Sales Growth.
     *
     * Measures % change in revenue from previous quarter.
     * Formula: ((Current Revenue - Previous Revenue) ÷ Previous Revenue) × 100
     * Returns 0 if previous revenue is zero.
     */
    public function calculateSalesGrowth($simulation_id, $faculty_id, $group_id, $quarter_id)
    {
        $cur = $this->dataCollection
            ->corporateProfitLoss($simulation_id, $faculty_id, $group_id, $quarter_id)
            ->summary ?? (object) [];

        $prev = $this->dataCollection
            ->corporateProfitLoss($simulation_id, $faculty_id, $group_id, $quarter_id - 1)
            ->summary ?? (object) [];

        $curRev = (float) ($cur->revenue ?? 0);
        $prevRev = (float) ($prev->revenue ?? 0);

        if ($prevRev == 0) {
            return 0.00; // avoid division by zero
        }

        return round((($curRev - $prevRev) / $prevRev) * 100, 2);
    }

    // /// OPERATIONAL METRIC ////

    /**
     * Calculate Inventory Turnover (Operational).
     *
     * Measures how often finished goods inventory is sold/replaced.
     * Formula: Product Cost ÷ Avg(FG Inventory of current & previous quarter)
     * Returns 100 if average FG inventory is zero (simulation fallback).
     */
    public function calculateInventoryTurnover($simulation_id, $faculty_id, $group_id, $quarter_id, $previous_quarter_id)
    {
        $s = $this->dataCollection
            ->corporateProfitLoss($simulation_id, $faculty_id, $group_id, $quarter_id)
            ->summary ?? (object) [];

        $bsCurr = $this->balanceSheet
            ->generateBalanceSheet($simulation_id, $faculty_id, $group_id, $quarter_id);

        $bsPrev = $this->balanceSheet
            ->generateBalanceSheet($simulation_id, $faculty_id, $group_id, $previous_quarter_id);

        $fgCurr = (float) ($bsCurr['assets']['Finished Goods Inventory'] ?? 0);
        $fgPrev = (float) ($bsPrev['assets']['Finished Goods Inventory'] ?? 0);

        $avgFG = ($fgCurr + $fgPrev) / 2;
        if ($avgFG == 0) {
            return 100.00; // simulation rule fallback
        }

        $cogs = (float) ($s->productCost ?? 0);

        return round($cogs / $avgFG, 2);
        // return round(($cogs / $avgFG) * 4, 2);
    }

    /**
     * Calculate Fill Rate.
     *
     * Measures the % of customer demand fulfilled.
     * Formula: ((Sales Volume - Unfilled Orders) ÷ Sales Volume) × 100
     * Returns 0 if sales volume is zero.
     */
    public function calculateFillRate($simulation_id, $faculty_id, $group_id, $quarter_id)
    {
        $s = $this->dataCollection->corporateProfitLoss($simulation_id, $faculty_id, $group_id, $quarter_id)->summary ?? (object) [];
        $totalOrders = (float) ($s->productSalesVolume ?? 0);
        $unfilled = (float) ($s->unfilledOrders ?? 0);
        $fulfilledOrders = max(0.0, $totalOrders - $unfilled);
        if ($totalOrders == 0) {
            return 0.00;
        }

        return round(($fulfilledOrders / $totalOrders) * 100, 2);
    }

    /**
     * Calculate Production Deviation.
     *
     * Measures reliance on emergency production.
     * Formula: (Emergency Production ÷ Total Production) × 100
     * Returns 0 if no production occurred.
     * High values indicate poor planning/inefficiency.
     */
    public function calculateProductionDeviation($simulation_id, $faculty_id, $group_id, $quarter_id)
    {
        $s = $this->dataCollection
            ->corporateProfitLoss($simulation_id, $faculty_id, $group_id, $quarter_id)
            ->summary ?? collect();

        $totalProduction = Manufacturing::where([
            'simulation_id' => $simulation_id,
            'faculty_id' => $faculty_id,
            'group_number' => $group_id,
            'quarter' => $quarter_id,
        ])->get()->sum(function ($item) {
            return $item->production_quantity + $item->emergency_production;
        });

        if ($totalProduction == 0) {
            return 0;
        }

        return round(($s->emergencyProduction / $totalProduction) * 100, 0);
    }

    /**
     * Calculate Defect Rate.
     *
     * Measures product quality (% of units replaced).
     * Formula: (Replacement Parts ÷ Sales Volume) × 100
     * Returns 0 if sales volume is zero.
     */
    public function calculateDefectRate($simulation_id, $faculty_id, $group_id, $quarter_id)
    {
        // 1) Units sold (denominator) – reuse your P&L summary
        $s = $this->dataCollection
            ->corporateProfitLoss($simulation_id, $faculty_id, $group_id, $quarter_id)
            ->summary ?? (object) [];

        $unitsSold = (float) ($s->productSalesVolume ?? 0);
        if ($unitsSold <= 0) {
            return 0.0; // or return null if you prefer to show "—"
        }

        // 2) Gather all procured sub-assembly quantities for this firm/quarter
        $procured = ProcurementSubAssembly::with('supplier') // supplier_id on each row
            ->whereHas('procurement', function ($q) use ($simulation_id, $faculty_id, $group_id, $quarter_id) {
                $q->where([
                    'simulation_id' => $simulation_id,
                    'faculty_id' => $faculty_id,
                    'group_number' => $group_id,
                    'quarter' => $quarter_id,
                ]);
            })
            ->get(['supplier_id', 'sub_assembly_component_id', 'quantity']);

        if ($procured->isEmpty()) {
            // No procurement rows recorded → no defects computable
            return 0.0; // or null
        }

        // 3) Load failure rates (%) for the involved supplier+component combos
        $byKey = fn ($sid, $cid) => $sid.':'.$cid;
        $needKeys = $procured->map(fn ($p) => $byKey($p->supplier_id, $p->sub_assembly_component_id))->unique();

        $supplierComponents = SupplierComponent::whereIn('supplier_id', $procured->pluck('supplier_id')->unique())
            ->whereIn('sub_assembly_component_id', $procured->pluck('sub_assembly_component_id')->unique())
            ->get(['supplier_id', 'sub_assembly_component_id', 'failure_rate'])
            ->keyBy(fn ($sc) => $byKey($sc->supplier_id, $sc->sub_assembly_component_id));

        // 4) Expected failures across all components
        $expectedFailures = 0.0;
        foreach ($procured as $row) {
            $key = $byKey($row->supplier_id, $row->sub_assembly_component_id);
            $ratePct = (float) optional($supplierComponents->get($key))->failure_rate; // e.g., 1.8
            $expectedFailures += ((float) $row->quantity) * ($ratePct / 100.0);
        }

        if ($expectedFailures <= 0) {
            return 0.0; // or null
        }

        // 5) Convert to % of units sold
        $rate = ($expectedFailures / $unitsSold) * 100.0;

        // OPTIONAL tiny noise (±0.2%). Comment out if not desired:
        // $rate += mt_rand(-200, 200) / 100.0 / 100.0; // -0.2% .. +0.2%

        return round(max(0.0, $rate), 2);
    }

    /**
     * Calculate Controllable Costs to Revenue.
     *
     * Measures controllable costs as a % of revenue.
     * Formula: (Emergency Procurement + Emergency Production
     *          + Inventory Charges + Procurement FC + Production FC) ÷ Revenue × 100
     * Returns 0 if revenue is zero.
     */
    public function calculateControllableCostsToRevenue($simulation_id, $faculty_id, $group_id, $quarter_id)
    {
        $s = $this->dataCollection
            ->corporateProfitLoss($simulation_id, $faculty_id, $group_id, $quarter_id)
            ->summary ?? (object) [];

        $disposalSales = (float) ($s->disposalSaleIncome ?? 0);
        $emergencyProcurement = (float) ($s->emergencyProcurement ?? 0);
        $unfilledHandling = (float) ($s->unfilledHandling ?? 0);
        $emergencyProduction = (float) ($s->emergencyProduction ?? 0);
        $inventoryCharges = (float) ($s->inventoryCharges ?? 0);
        $procurementFc = (float) ($s->procurementFc ?? 0);
        $productionFc = (float) ($s->productionFc ?? 0);
        $revenue = (float) ($s->revenue ?? 0);

        $numerator =
            $emergencyProcurement
            + $emergencyProduction
            + $inventoryCharges
            + $procurementFc
            + $productionFc;

        if ($revenue <= 0) {
            return 0.0; // or return null if you prefer to show “—”
        }

        // Percentage, rounded to 2 decimals (your view can format to 0 or 2 as needed)
        $pct = ($numerator / $revenue) * 100;

        // Keep it sane
        $pct = max(0.0, $pct);

        return round($pct, 2);
    }

    /**
     * Calculate Transportation Expenses Per Unit.
     *
     * Measures average logistics cost per unit sold.
     * Formula: (Transportation Cost - Rebate) ÷ Sales Volume
     * Returns 0 if sales volume is zero.
     */
    public function calculateTransportationExpensesPerUnit($simulation_id, $faculty_id, $group_id, $quarter_id)
    {
        $s = $this->dataCollection->corporateProfitLoss($simulation_id, $faculty_id, $group_id, $quarter_id)->summary ?? (object) [];
        $units = (float) ($s->productSalesVolume ?? 0);
        if ($units == 0) {
            return 0.00;
        }
        $cost = (float) ($s->transportationCost ?? 0);
        $rebate = (float) ($s->transportationRebate ?? 0); // matches CorporatePnlService

        return round(($cost - $rebate) / $units, 2);
    }

    /**
     * Calculate Forecast Accuracy.
     *
     * Measures how accurate demand forecast was vs actual revenue.
     * Formula: 100 - ((Forecast Inaccuracy ÷ Revenue) × 100)
     * Returns 0 if revenue is zero. Values clamped to [0, 100].
     */
    public function calculateForecastAccuracy($simulation_id, $faculty_id, $group_id, $quarter_id)
    {
        return $this->forecastAccuracyService->getOverallForecastAccuracy(
            $simulation_id,
            $faculty_id,
            $group_id,
            $quarter_id
        );
    }

    // // CUSTOMER METRIC ////

    /**
     * Calculate Market Penetration Rate.
     *
     * Measures the firm's share of total market demand.
     * Formula: (Product Sales Volume ÷ Total Market Volume) × 100
     * Returns 0 if total market volume is zero.
     */
    public function calculateMarketPenetrationRate($simulation_id, $faculty_id, $group_id, $quarter_id)
    {
        // Your firm’s sales volume (all products, both channels)
        $me = $this->dataCollection
            ->corporateProfitLoss($simulation_id, $faculty_id, $group_id, $quarter_id)
            ->summary ?? (object) [];

        $mySales = (float) ($me->productSalesVolume ?? 0);

        // Sum industry sales across all *active* teams in this simulation
        $industrySales = SimulationTeam::where('simulation_id', $simulation_id)
            ->where('status', 1)
            ->pluck('group_number')
            ->sum(function ($gid) use ($simulation_id, $faculty_id, $quarter_id) {
                $s = $this->dataCollection
                    ->corporateProfitLoss($simulation_id, $faculty_id, $gid, $quarter_id)
                    ->summary ?? (object) [];

                return (float) ($s->productSalesVolume ?? 0);
            });

        if ($industrySales <= 0) {
            return 0.0; // no market activity → 0% penetration
        }

        // FLEXEE covers 20% of the total market → market size = industry / 0.20
        $marketSize = $industrySales / 0.20;

        return round(($mySales / $marketSize) * 100, 2); // e.g., 1.37%
    }

    /**
     * Calculate Customer Satisfaction Index (CSI).
     *
     * Proxy for customer satisfaction (survey/feedback based).
     * Derived from service, quality, predictability, and price stability.
     */
    public function calculateCustomerSatisfactionIndex($simulation_id, $faculty_id, $group_id, $quarter_id)
    {
        // -----------------------------
        // Base KPIs (0–100)
        // -----------------------------
        $fillRate = $this->calculateFillRate($simulation_id, $faculty_id, $group_id, $quarter_id);
        $defectRate = $this->calculateDefectRate($simulation_id, $faculty_id, $group_id, $quarter_id);
        $forecastAcc = $this->calculateForecastAccuracy($simulation_id, $faculty_id, $group_id, $quarter_id);

        // Convert defect rate → quality satisfaction
        $qualityScore = max(0, 100 - $defectRate);

        // -----------------------------
        // Current Quarter ASP
        // -----------------------------
        $currentSummary = $this->dataCollection
            ->corporateProfitLoss($simulation_id, $faculty_id, $group_id, $quarter_id)
            ->summary ?? (object) [];

        $currentRevenue = (float) ($currentSummary->revenue ?? 0);
        $currentUnits = (float) ($currentSummary->productSalesVolume ?? 0);
        $currentAsp = ($currentUnits > 0) ? ($currentRevenue / $currentUnits) : 0;

        // -----------------------------
        // Prior Quarter ASP
        // -----------------------------
        $priorSummary = null;

        if ($quarter_id > 1) {
            $priorSummary = $this->dataCollection
                ->corporateProfitLoss($simulation_id, $faculty_id, $group_id, $quarter_id - 1)
                ->summary ?? null;
        }

        // -----------------------------
        // Price Stability Score (QoQ)
        // -----------------------------
        if (
            $quarter_id == 1 ||
            ! $priorSummary ||
            ($priorSummary->productSalesVolume ?? 0) == 0
        ) {
            // Grace period for Q1 or missing data
            $priceScore = 100;
        } else {
            $priorRevenue = (float) ($priorSummary->revenue ?? 0);
            $priorUnits = (float) ($priorSummary->productSalesVolume ?? 0);
            $priorAsp = ($priorUnits > 0) ? ($priorRevenue / $priorUnits) : 0;

            if ($priorAsp < 0.01 || $currentAsp < 0.01) {
                $priceScore = 100;
            } else {
                // Linear penalty: 1% price change = 1 point loss
                $changePercent = abs(($currentAsp - $priorAsp) / $priorAsp) * 100;

                // Floor prevents price volatility from overpowering CSI
                $priceScore = max(40, 100 - $changePercent);
            }
        }

        // -----------------------------
        // CSI Weighted Score
        // -----------------------------
        $csi =
            (0.4 * $fillRate) +
            (0.3 * $qualityScore) +
            (0.2 * $forecastAcc) +
            (0.1 * $priceScore);

        return round($csi, 0); // Final CSI (0–100)
    }

    // External Metrics //

    /**
     * Calculate Revenue Growth.
     *
     * Measures % change in revenue from previous quarter.
     * Formula: ((Current Revenue − Previous Revenue) ÷ Previous Revenue) × 100
     * Returns 0 if previous revenue is zero.
     */
    public function calculateRevenueGrowth($simulation_id, $faculty_id, $group_id, $quarter_id)
    {
        $cur = $this->dataCollection->corporateProfitLoss($simulation_id, $faculty_id, $group_id, $quarter_id)->summary ?? (object) [];
        $prev = $this->dataCollection->corporateProfitLoss($simulation_id, $faculty_id, $group_id, $quarter_id - 1)->summary ?? (object) [];
        $prevRev = (float) ($prev->revenue ?? 0);
        if ($prevRev == 0) {
            return 0.00;
        }

        return round((((float) ($cur->revenue ?? 0) - $prevRev) / $prevRev) * 100, 2);
    }

    /**
     * Calculate Earnings Growth.
     *
     * Measures % change in net income from previous quarter.
     * Formula: ((Current Net Income − Previous Net Income) ÷ Previous Net Income) × 100
     * Returns 0 if previous net income is zero.
     */
    public function calculateEarningsGrowth($simulation_id, $faculty_id, $group_id, $quarter_id)
    {
        $cur = $this->dataCollection->corporateProfitLoss($simulation_id, $faculty_id, $group_id, $quarter_id)->summary ?? (object) [];
        $prev = $this->dataCollection->corporateProfitLoss($simulation_id, $faculty_id, $group_id, $quarter_id - 1)->summary ?? (object) [];
        $prevNi = (float) ($prev->netIncome ?? 0);
        if ($prevNi == 0) {
            return 0.00;
        }

        return round((((float) ($cur->netIncome ?? 0) - $prevNi) / $prevNi) * 100, 2);
    }

    /**
     * Calculate Consistency Score.
     *
     * Gauges stability of revenue over recent quarters.
     * Based on standard deviation of revenue (lower = more consistent).
     * Scaled to ~0–10, where higher = more consistent performance.
     */
    public function calculateConsistencyScore($simulation_id, $faculty_id, $group_id, $quarter_id)
    {
        // Need at least 3 quarters to evaluate (Q1, Q2 => null)
        if ($quarter_id < 3) {
            return null;
        }

        // 3-quarter rolling window ending at current quarter
        $startQ = $quarter_id - 2;
        $net = [];

        for ($q = $startQ; $q <= $quarter_id; $q++) {
            $summary = $this->dataCollection
                ->corporateProfitLoss($simulation_id, $faculty_id, $group_id, $q)
                ->summary ?? (object) [];
            $net[] = (float) ($summary->netIncome ?? 0);
        }

        // Strictly increasing earnings across the window?
        $isGrowing = ($net[1] > $net[0]) && ($net[2] > $net[1]);

        // Return 1/0 per your rule (use 10/0 if you want a 0–10 scale in UI)
        return $isGrowing ? 1 : 0;
    }

    /**
     * Clamp helper: keep a number within [min, max]
     */
    private function clamp(float $v, float $min = 0.0, float $max = 100.0): float
    {
        return max($min, min($max, $v));
    }

    /**
     * Financial Investor Index (Chapter 12 compliant)
     *
     * FI Index = 0.30 × Net Profit Margin
     *          + 0.30 × Sales Growth
     *          + 0.20 × Consistency Score
     *          + 0.20 × Inventory Turnover
     *
     * Notes:
     * - All inputs are RAW values (no normalization, no clamping)
     * - Consistency Score is binary (1 or 0)
     * - Inventory Turnover is annualized (×4 already applied)
     */
    public function calculateFIIndex($simulation_id, $faculty_id, $group_id, $quarter_id)
    {
        $netProfitMargin = (float) $this->calculateNetProfitMargin(
            $simulation_id,
            $faculty_id,
            $group_id,
            $quarter_id
        );

        $salesGrowth = (float) $this->calculateSalesGrowth(
            $simulation_id,
            $faculty_id,
            $group_id,
            $quarter_id
        );

        $consistency = $this->calculateConsistencyScore(
            $simulation_id,
            $faculty_id,
            $group_id,
            $quarter_id
        ) ?? 0.0; // below Q3 → treated as 0

        $inventoryTurnover = (float) $this->calculateInventoryTurnover(
            $simulation_id,
            $faculty_id,
            $group_id,
            $quarter_id,
            $quarter_id - 1
        );

        $fiIndex =
            (0.30 * $netProfitMargin) +
            (0.30 * $salesGrowth) +
            (0.20 * $consistency) +
            (0.20 * $inventoryTurnover);

        return round($fiIndex, 2);
    }
}
