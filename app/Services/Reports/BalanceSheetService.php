<?php

namespace App\Services\Reports;

use App\Models\Decisions\Distribution;
use App\Models\Decisions\InventoryStock;
use App\Models\Decisions\InventoryStockFinishedGood;
use App\Models\Decisions\Procurement;
use App\Models\FinancialQuarter;
use App\Models\Packaging;
use App\Models\Product;
use App\Models\RawMaterial;
use App\Models\RawMaterialCost;
use App\Models\SubAssemblyComponent;
use App\Models\SupplierComponent;
use Illuminate\Support\Facades\DB;

class BalanceSheetService
{
    protected $corporatePnlService;

    public function __construct(CorporatePnlService $corporatePnlService)
    {
        $this->corporatePnlService = $corporatePnlService;
    }

    /* =====================================================
     | ENTRY
     ===================================================== */
    public function generateBalanceSheet(
        int $simulation_id,
        int $faculty_id,
        int $group_number,
        int $quarter_id
    ) {
        if ($quarter_id === 0) {
            return $this->generateQuarterZeroBalanceSheet(
                $simulation_id,
                $faculty_id,
                $group_number
            );
        }

        return $this->generateBalanceSheetReport(
            $simulation_id,
            $faculty_id,
            $group_number,
            $quarter_id
        );
    }

    /* =====================================================
     | QUARTER 0
     ===================================================== */
    protected function generateQuarterZeroBalanceSheet(
        $simulation_id,
        $faculty_id,
        $group_number
    ) {
        $plantInvestment = 100_000_000;
        $cash = 100_000;

        return [
            'Qtr' => 0,
            'assets' => [
                'Cash' => $cash,
                'Marketable Securities' => 0,
                'Finished Goods Inventory' => 0,
                'Procurement Inventory' => 0,
                'Plant Investment' => $plantInvestment,
                'Total Assets' => $cash + $plantInvestment,
            ],
            'assets_breakdown' => [
                'Finished Goods Inventory' => [],
                'Procurement Inventory' => [],
            ],
            'liabilities_and_equities' => [
                'Corporate Capitalization' => $plantInvestment,
                'Loans' => 0,
                'Dividends, Current Quarter' => 0,
                'Dividends, Cumulative Prior' => 0,
                'Retained Earnings, Current Quarter' => 0,
                'Retained Earnings, Cumulative Prior' => 0,
                'Total Liabilities and Equities' => $plantInvestment + $cash,
            ],
            'notes' => ['Quarter 0 represents startup phase. No operations occurred.'],
        ];
    }

    /* =====================================================
     | MAIN BALANCE SHEET
     ===================================================== */
    public function generateBalanceSheetReport(
        int $simulation_id,
        int $faculty_id,
        int $group_number,
        int $quarter_id
    ): array {

        /* ---------------- ASSETS ---------------- */

        $financials = $this->getCashBalance(
            $simulation_id,
            $faculty_id,
            $group_number,
            $quarter_id
        );

        $cash = round($financials['cash'], 2);
        $securities = round($financials['marketable_securities'], 2);

        $fgInventory = $this->getFinishedGoodsInventoryBreakdown(
            $simulation_id,
            $faculty_id,
            $group_number,
            $quarter_id
        );

        $procInventory = $this->getProcurementInventoryBreakdown(
            $simulation_id,
            $faculty_id,
            $group_number,
            $quarter_id
        );

        $plantInvestment = 100_000_000;

        $totalAssets = round(
            $cash
            + $securities
            + $fgInventory['total']
            + $procInventory['total']
            + $plantInvestment,
            2
        );

        $assets = [
            'Cash' => $cash,
            'Marketable Securities' => $securities,
            'Finished Goods Inventory' => $fgInventory['total'],
            'Procurement Inventory' => $procInventory['total'],
            'Plant Investment' => $plantInvestment,
            'Total Assets' => $totalAssets,
        ];

        /* -------------- LIABILITIES & EQUITY -------------- */

        $corporateCapitalization = 100_000_000;
        $loans = round($financials['loans'], 2);

        $netIncome = round(
            $this->getNetIncome(
                $simulation_id,
                $faculty_id,
                $group_number,
                $quarter_id
            ),
            2
        );

        $dividendsCurrent = round($financials['dividends'], 2);

        $dividendsPrior = round(
            $this->getCumulativeDividendsBefore(
                $simulation_id,
                $faculty_id,
                $group_number,
                $quarter_id
            ),
            2
        );

        // ✅ CORRECT RETAINED EARNINGS
        $retainedCurrent = round($netIncome - $dividendsCurrent, 2);

        $retainedPrior = round(
            $this->getCumulativeRetainedEarningsBefore(
                $simulation_id,
                $faculty_id,
                $group_number,
                $quarter_id
            ),
            2
        );

        $totalLiabilitiesEquity = round(
            $corporateCapitalization
            + $loans
            + $fgInventory['total']
            + $procInventory['total'],
            2
        );

        $liabilitiesEquity = [
            'Corporate Capitalization' => $corporateCapitalization,
            'Loans' => $loans,
            'Dividends, Current Quarter' => -$dividendsCurrent,
            'Dividends, Cumulative Prior' => -$dividendsPrior,
            'Retained Earnings, Current Quarter' => $retainedCurrent,
            'Retained Earnings, Cumulative Prior' => $retainedPrior,
            'Total Liabilities and Equities' => $totalLiabilitiesEquity,
        ];

        if (abs($totalAssets - $totalLiabilitiesEquity) > 0.01) {
            logger()->warning(
                'BALANCE SHEET MISMATCH Q'.str_pad($quarter_id, 2, '0', STR_PAD_LEFT),
                [
                    'Assets' => $totalAssets,
                    'Liabilities + Equity' => $totalLiabilitiesEquity,
                    'Gap' => $totalAssets - $totalLiabilitiesEquity,
                ]
            );
        }

        return [
            'Qtr' => $quarter_id,
            'assets' => $assets,
            'assets_breakdown' => [
                'Finished Goods Inventory' => $fgInventory['breakdown'],
                'Procurement Inventory' => $procInventory['breakdown'],
            ],
            'liabilities_and_equities' => $liabilitiesEquity,
            'notes' => $this->generateBalanceSheetNotes(
                $simulation_id,
                $faculty_id,
                $group_number,
                $quarter_id
            ),
        ];
    }

    /* =====================================================
     | INVENTORY BREAKDOWNS (UNCHANGED)
     ===================================================== */

    public function getProcurementInventoryBreakdown(
        int $simulation_id,
        int $faculty_id,
        int $group_number,
        int $quarter_id
    ): array {
        $dcCenters = Distribution::where([
            'simulation_id' => $simulation_id,
            'faculty_id' => $faculty_id,
            'group_number' => $group_number,
            'quarter' => $quarter_id,
        ])->get(['region_id', 'dc_number']);

        $rawMaterials = RawMaterial::all();
        $subAssemblies = SubAssemblyComponent::all();

        $inventoryStock = InventoryStock::where([
            'simulation_id' => $simulation_id,
            'faculty_id' => $faculty_id,
            'group_number' => $group_number,
            'quarter' => $quarter_id,
        ])->get();

        $breakdown = $dcCenters->each(function ($center) use (
            $rawMaterials,
            $subAssemblies,
            $inventoryStock,
            $simulation_id,
            $faculty_id,
            $group_number,
            $quarter_id
        ) {
            $stock = $inventoryStock->firstWhere('region_id', $center->region_id);
            $center->procurement = collect();

            if ($center->region_id == 1) {
                foreach ($rawMaterials as $raw) {
                    $quantity = $raw->id == 1
                        ? ($stock->plastic ?? 0)
                        : ($raw->id == 2 ? ($stock->casing ?? 0) : 0);

                    $cost = RawMaterialCost::where(
                        'raw_material_id',
                        $raw->id
                    )->value('cost') ?? 0;

                    $item = clone $raw;
                    $item->type = 'Raw-Materials';
                    $item->units = $quantity;
                    $item->cost = $cost;
                    $item->totalAssets = $quantity * $cost;

                    $center->procurement->push($item);
                }
            }

            foreach ($subAssemblies as $sub) {
                $quantity =
                    $sub->id == 1 ? ($stock->control_interface ?? 0)
                    : ($sub->id == 2 ? ($stock->audio_modal ?? 0)
                    : ($sub->id == 3 ? ($stock->motherboard ?? 0) : 0));

                $procurement = Procurement::where([
                    'simulation_id' => $simulation_id,
                    'faculty_id' => $faculty_id,
                    'group_number' => $group_number,
                    'quarter' => $quarter_id,
                    'region_id' => $center->region_id,
                ])->first();

                $records = $procurement
                    ? $procurement->subAssembly()
                        ->where('sub_assembly_component_id', $sub->id)
                        ->get()
                    : collect();

                $totalCost = $records->sum(function ($record) {
                    $unitCost = SupplierComponent::where([
                        'supplier_id' => $record->supplier_id,
                        'sub_assembly_component_id' => $record->sub_assembly_component_id,
                    ])->value('cost') ?? 0;

                    return $unitCost * $record->quantity;
                });

                $totalUnits = $records->sum('quantity');
                $weightedCost = $totalUnits > 0 ? $totalCost / $totalUnits : 0;

                $item = clone $sub;
                $item->type = 'Sub-Assembly';
                $item->units = $quantity;
                $item->cost = $weightedCost;
                $item->totalAssets = $quantity * $weightedCost;

                $center->procurement->push($item);
            }
        });

        $total = $breakdown->sum(fn ($item) => $item->procurement->sum(fn ($p) => $p->totalAssets)
        );

        return [
            'total' => $total,
            'breakdown' => $breakdown,
        ];
    }

    public function getFinishedGoodsInventoryBreakdown(
        int $simulation_id,
        int $faculty_id,
        int $group_number,
        int $quarter_id
    ): array {
        $dcCenters = Distribution::where([
            'simulation_id' => $simulation_id,
            'faculty_id' => $faculty_id,
            'group_number' => $group_number,
            'quarter' => $quarter_id,
        ])->get(['region_id', 'dc_number']);

        $inventoryStocks = InventoryStockFinishedGood::where([
            'simulation_id' => $simulation_id,
            'faculty_id' => $faculty_id,
            'group_number' => $group_number,
            'quarter' => $quarter_id,
        ])->get();

        $breakdown = $dcCenters->each(function ($dc) use ($inventoryStocks) {
            $stock = $inventoryStocks->firstWhere('region_id', $dc->region_id);

            $dc->products = (object) [
                'product_0' => (object) [
                    'product_id' => '00',
                    'product_cost' => $this->calculateProduct0UnitCost(),
                    'product_name' => 'Postponed Production',
                    'quantity' => max(0, $stock->product_0 ?? 0),
                ],
                'product_1' => (object) [
                    'product_id' => '01',
                    'product_cost' => $this->calculateProductUnitCost(1),
                    'product_name' => 'Smart Home Assistant',
                    'quantity' => max(0, $stock->product_01 ?? 0),
                ],
                'product_2' => (object) [
                    'product_id' => '02',
                    'product_cost' => $this->calculateProductUnitCost(2),
                    'product_name' => 'Smart Thermostat',
                    'quantity' => max(0, $stock->product_02 ?? 0),
                ],
            ];
        });

        $total = $breakdown->sum(fn ($item) => ($item->products->product_0->quantity * $item->products->product_0->product_cost)
            + ($item->products->product_1->quantity * $item->products->product_1->product_cost)
            + ($item->products->product_2->quantity * $item->products->product_2->product_cost)
        );

        return [
            'total' => $total,
            'breakdown' => $breakdown,
        ];
    }

    /* =====================================================
     | HELPERS
     ===================================================== */

    public function getCashBalance($simulation_id, $faculty_id, $group_number, $quarter_id): array
    {
        $quarter = FinancialQuarter::where([
            'simulation_id' => $simulation_id,
            'faculty_id' => $faculty_id,
            'group_number' => $group_number,
            'quarter_id' => $quarter_id,
        ])->first();

        return [
            'cash' => round($quarter->cash ?? 0, 2),
            'marketable_securities' => round($quarter->marketable_securities ?? 0, 2),
            'loans' => round($quarter->loans ?? 0, 2),
            'dividends' => round($quarter->dividends ?? 0, 2),
        ];
    }

    public function getRevenue($simulation_id, $faculty_id, $group_number, $quarter_id)
    {
        return $this->corporatePnlService
            ->corporateProfitLoss($simulation_id, $faculty_id, $group_number, $quarter_id)
            ->summary
            ->revenue ?? 0;
    }

    public function getNetIncome($simulation_id, $faculty_id, $group_number, $quarter_id)
    {
        return $this->corporatePnlService
            ->corporateProfitLoss($simulation_id, $faculty_id, $group_number, $quarter_id)
            ->summary
            ->netIncome ?? 0;
    }

    public function calculateProductUnitCost(int $product_id): float
    {
        $product = Product::with('material')->find($product_id);
        if (! $product) {
            return 0;
        }

        $rawCost = $product->material
            ->where('type', 'raw_material')
            ->sum(fn ($m) => $m->quantity *
                (RawMaterialCost::where('raw_material_id', $m->material_id)->value('cost') ?? 0)
            );

        $subCost = $product->material
            ->where('type', 'sub_assembly_component')
            ->sum(fn ($s) => $s->quantity *
                (SupplierComponent::where([
                    'supplier_id' => 4,
                    'sub_assembly_component_id' => $s->material_id,
                ])->value('cost') ?? 0)
            );

        $labor = $product_id == 1 ? 30 : 36;
        $production = $product_id == 1 ? 20 : 16;
        $bandwidth = 10 + (pow(0.5, 3) / 2);
        $warrantyCost = 8 + 3 * pow(3, 2);
        $packagingCost = Packaging::find(1)->cost ?? 10;

        return
            $rawCost
        + $subCost
        + $labor
        + $production
        + $bandwidth
        + $warrantyCost
        + $packagingCost;
    }

    public function getCumulativeRetainedEarningsBefore(
        $simulation_id,
        $faculty_id,
        $group_number,
        $quarter_id
    ) {
        if ($quarter_id <= 1) {
            return 0;
        }

        return FinancialQuarter::where([
            'simulation_id' => $simulation_id,
            'faculty_id' => $faculty_id,
            'group_number' => $group_number,
        ])
            ->where('quarter_id', '<', $quarter_id)
            ->sum(DB::raw('net_income - dividends'));
    }

    public function getCumulativeDividendsBefore(
        $simulation_id,
        $faculty_id,
        $group_number,
        $quarter_id
    ) {
        if ($quarter_id <= 1) {
            return 0;
        }

        return FinancialQuarter::where([
            'simulation_id' => $simulation_id,
            'faculty_id' => $faculty_id,
            'group_number' => $group_number,
        ])
            ->where('quarter_id', '<', $quarter_id)
            ->sum('dividends');
    }

    public function procurementMotherboard(
        $simulation_id,
        $faculty_id,
        $group_number,
        $quarter_id
    ) {
        $centers = Distribution::where([
            'simulation_id' => $simulation_id,
            'faculty_id' => $faculty_id,
            'group_number' => $group_number,
            'quarter' => $quarter_id,
        ])->get(['region_id', 'dc_number']);

        $centers->each(function ($item) use (
            $simulation_id,
            $faculty_id,
            $group_number,
            $quarter_id
        ) {
            $procurement = Procurement::where([
                'simulation_id' => $simulation_id,
                'faculty_id' => $faculty_id,
                'group_number' => $group_number,
                'quarter' => $quarter_id,
                'region_id' => $item->region_id,
            ])->first();

            $item->motherboard_quantity = $procurement
                ? $procurement->subAssembly()
                    ->where('sub_assembly_component_id', 3)
                    ->sum('quantity')
                : 0;
        });

        return $centers;
    }

    public function generateBalanceSheetNotes(
        $simulation_id,
        $faculty_id,
        $group_number,
        $quarter_id
    ): array {
        return $this->procurementMotherboard(
            $simulation_id,
            $faculty_id,
            $group_number,
            $quarter_id
        )
            ->map(fn ($r) => "Region {$r->region_id}: {$r->motherboard_quantity}Ds")
            ->toArray();
    }

    public function storeFinancialSnapshot(
        $simulation_id,
        $faculty_id,
        $group_number,
        $quarter_id
    ) {
        $netIncome = $this->getNetIncome(
            $simulation_id,
            $faculty_id,
            $group_number,
            $quarter_id
        );

        $revenue = $this->getRevenue(
            $simulation_id,
            $faculty_id,
            $group_number,
            $quarter_id
        );

        $quarter = FinancialQuarter::firstOrNew([
            'simulation_id' => $simulation_id,
            'faculty_id' => $faculty_id,
            'group_number' => $group_number,
            'quarter_id' => $quarter_id,
        ]);

        $cash = $quarter->cash ?? 0;
        $loans = $quarter->loans ?? 0;
        $dividends = $quarter->dividends ?? 0;

        $prev = FinancialQuarter::where([
            'simulation_id' => $simulation_id,
            'faculty_id' => $faculty_id,
            'group_number' => $group_number,
            'quarter_id' => $quarter_id - 1,
        ])->first();

        $securities = $prev ? ($prev->marketable_securities ?? 0) : 0;
        $securities += round($securities * 0.015, 2);

        $maxCash = round($revenue * 0.10, 2);
        if ($cash > $maxCash) {
            $securities += ($cash - $maxCash);
            $cash = $maxCash;
        }

        $minCash = round($revenue * 0.05, 2);
        if ($cash < $minCash) {
            $loans += ($minCash - $cash);
            $cash = $minCash;
        }

        FinancialQuarter::updateOrCreate([
            'simulation_id' => $simulation_id,
            'faculty_id' => $faculty_id,
            'group_number' => $group_number,
            'quarter_id' => $quarter_id,
        ], [
            'cash' => round($cash, 2),
            'loans' => round($loans, 2),
            'marketable_securities' => round($securities, 2),
            'dividends' => round($dividends, 2),
            'net_income' => round($netIncome, 2),
            'retained_earnings_current' => round($netIncome - $dividends, 2),
        ]);
    }

    public function getCumulativeRetainedEarnings(
        $simulation_id,
        $faculty_id,
        $group_number,
        $quarter_id
    ) {
        return FinancialQuarter::where([
            'simulation_id' => $simulation_id,
            'faculty_id' => $faculty_id,
            'group_number' => $group_number,
        ])
            ->where('quarter_id', '<', $quarter_id)
            ->sum(DB::raw('net_income - dividends'));
    }

    public function calculateProduct0UnitCost(): float
    {

        $plasticCost = RawMaterialCost::where('raw_material_id', 1)->value('cost') ?? 0;

        $casingCost = RawMaterialCost::where('raw_material_id', 2)->value('cost') ?? 0;

        $motherboardCost = SupplierComponent::where([

            'supplier_id' => 4,

            'sub_assembly_component_id' => 3,

        ])->value('cost') ?? 0;

        return

            (9 * $plasticCost)

            + (9 * $casingCost)

            + $motherboardCost

            + 22 // labor

            + 11; // production

    }
}
