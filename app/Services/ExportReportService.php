<?php

namespace App\Services;

use App\Models\ReportExport;
use App\Models\Supplier;
use App\Services\Reports\BalanceSheetService;
use App\Services\Reports\CashFlowService;
use App\Services\Reports\CorporatePnlService;
use App\Services\Reports\ProductPnlService;
use App\Services\Reports\Supplementary\DecisionService;
use App\Services\Reports\Supplementary\FinishedGoodsInventoryService;
use App\Services\Reports\Supplementary\ForecastAccuracyService;
use App\Services\Reports\Supplementary\ProcurementService;
use App\Services\Reports\Supplementary\ServiceManagementService;
use App\Support\Spreadsheet\StyleBuilder;
use App\Support\Spreadsheet\WriterEntityFactory;
use Illuminate\Support\Facades\Log;

class ExportReportService
{
    /**
     * Call Active User Service.
     */
    protected $activeUserService;

    protected $corporatePnlService;

    protected $balanceSheetService;

    protected $productPnlService;

    protected $cashFlowService;

    protected $finishedGoodsInventoryService;

    protected $procurementService;

    protected $serviceManagementService;

    protected $forecastAccuracyService;

    protected $decisionService;

    public function __construct(
        ActiveUserService $activeUserService,
        CorporatePnlService $corporatePnlService,
        BalanceSheetService $balanceSheetService,
        ProductPnlService $productPnlService,
        CashFlowService $cashFlowService,
        FinishedGoodsInventoryService $finishedGoodsInventoryService,
        ProcurementService $procurementService,
        ServiceManagementService $serviceManagementService,
        ForecastAccuracyService $forecastAccuracyService,
        DecisionService $decisionService
    ) {
        $this->activeUserService = $activeUserService;
        $this->corporatePnlService = $corporatePnlService;
        $this->balanceSheetService = $balanceSheetService;
        $this->productPnlService = $productPnlService;
        $this->cashFlowService = $cashFlowService;
        $this->finishedGoodsInventoryService = $finishedGoodsInventoryService;
        $this->procurementService = $procurementService;
        $this->forecastAccuracyService = $forecastAccuracyService;
        $this->serviceManagementService = $serviceManagementService;
        $this->decisionService = $decisionService;
    }

    /**
     * Export the Corporate P&L Statements report.
     */
    public function corporatePnl(int $simulation_id, int $faculty_id, int $group_id, int $quarter_id)
    {
        try {

            $exportsPath = storage_path('app/public/exports');

            if (! file_exists($exportsPath)) {
                mkdir($exportsPath, 0775, true);
            }

            $productReport = $this->corporatePnlService->corporateProfitLoss($simulation_id, $faculty_id, $group_id, $quarter_id)->products;
            $productSummary = $this->corporatePnlService->corporateProfitLoss($simulation_id, $faculty_id, $group_id, $quarter_id)->summary;

            $fileName = 'corporate-pnl-'.uniqid().'-'.now()->format('YmdHis').'.xlsx';
            $path = storage_path('app/public/exports/'.$fileName);

            $writer = WriterEntityFactory::createXLSXWriter();
            $writer->openToFile($path);
            $boldStyle = (new StyleBuilder)->setFontBold()->build();

            $writer->addRow(WriterEntityFactory::createRowFromArray(['Quarter 0'.$quarter_id.': Corporate Profit And Loss Statements'], $boldStyle));
            $writer->addRow(WriterEntityFactory::createRow()); // Empty Row

            // Headers
            $headerRow = ['', 'All Products'];
            foreach ($productReport as $product) {
                $headerRow[] = 'Product: '.$product->sort_name.' - '.$product->name;
            }
            $writer->addRow(WriterEntityFactory::createRowFromArray($headerRow, $boldStyle));

            // Data Row Helper
            $addDataRow = function ($label, $summaryField, $productField = null) use ($writer, $productReport, $productSummary) {
                $row = [$label];
                $row[] = $productSummary->{$summaryField} ?? '';
                foreach ($productReport as $product) {
                    $field = $productField ?: $summaryField;
                    $row[] = $product->{$field} ?? '';
                }
                $writer->addRow(WriterEntityFactory::createRowFromArray($row));
            };

            // Grouped Sections
            $writer->addRow(WriterEntityFactory::createRowFromArray(['Sale Overview'], $boldStyle));
            $addDataRow('Sales Volume', 'productSalesVolume');
            $addDataRow('Price', 'price');
            $addDataRow('Unfilled Orders', 'unfilledOrders');
            $addDataRow('Revenues', 'revenue');

            $writer->addRow(WriterEntityFactory::createRowFromArray(['Expenses'], $boldStyle));
            $addDataRow('- Product Costs', 'productCost');
            $addDataRow('- Production Shift Cost', 'productionShiftCost');
            $addDataRow('- Fixed Cost Per Order', 'fixedCost');
            $addDataRow('- Order Processing', 'orderProcessing');
            $addDataRow('- Replacement Parts', 'replacementParts');
            $addDataRow('- RFID Costs', 'rfidCosts');
            $addDataRow('- Transportation Costs', 'transportationCost');
            $addDataRow('+ Transportation Rebates', 'transportationRebate');
            $addDataRow('+ Volume Discounts', 'volumeDiscounts');
            $addDataRow('- Duties & Tariffs', 'dutiesTariffs');
            $addDataRow('Total Expenses', 'totalExpenses');
            $addDataRow('Gross Margin', 'grossMargin');

            $writer->addRow(WriterEntityFactory::createRowFromArray(['Fixed Costs'], $boldStyle));
            $addDataRow('Administrative O/H', 'administrativeOh');
            $addDataRow('Corporate O/H', 'corporateOh');
            $addDataRow('Consulting Fees', 'consultingFees');
            $addDataRow('Cross-Docking', 'crossDocking');
            $addDataRow('Distribution FC', 'distributionFc');
            $addDataRow('Emergency Procurement', 'emergencyProcurement');
            $addDataRow('Emergency Production', 'emergencyProduction');
            $addDataRow('Forecast Inaccuracy', 'forecastInaccuracy');
            $addDataRow('Information Technology', 'informationTechnology');
            $addDataRow('Introductions', 'introductions');
            $addDataRow('Inventory Charges', 'inventoryCharges');
            $addDataRow('Marketing', 'marketing');
            $addDataRow('Plant Capacity FC', 'plantCapacityFc');
            $addDataRow('Price Changes', 'priceChanges');
            $addDataRow('Procurement FC', 'procurementFc');
            $addDataRow('Production FC', 'productionFc');
            $addDataRow('Research Studies', 'researchStudies');
            $addDataRow('Service Outsourcing', 'serviceOutsourcing');
            $addDataRow('Unfilled Handling', 'unfilledHandling');
            $addDataRow('Total Fixed Costs', 'totalFixedCosts');
            $addDataRow('Operating Income', 'operatingIncome');
            $addDataRow('Non-Operating Income Taxes', 'nonOperatingIncome');
            $addDataRow('Taxes', 'taxes');
            $addDataRow('Net Income', 'netIncome');

            $writer->close();

            return ReportExport::create([
                'simulation_id' => $simulation_id,
                'faculty_id' => $faculty_id,
                'group_id' => $group_id,
                'quarter' => $quarter_id,
                'report_type' => 'corporate-pnl',
                'file_path' => 'exports/'.$fileName,
                'file_name' => $fileName,
            ]);
        } catch (\Throwable $e) {
            Log::error('Corporate PNL Download Error: '.$e->getMessage());
        }
    }

    /**
     * Export the Product P&L Statements report.
     */
    public function productPnl(int $simulation_id, int $faculty_id, int $group_id, int $quarter_id)
    {
        try {

            $exportsPath = storage_path('app/public/exports');

            if (! file_exists($exportsPath)) {
                mkdir($exportsPath, 0775, true);
            }

            $productReport = $this->productPnlService->productPnl($simulation_id, $faculty_id, $group_id, $quarter_id);

            $fileName = 'product-pnl-'.uniqid().'-'.now()->format('YmdHis').'.xlsx';
            $path = storage_path('app/public/exports/'.$fileName);

            $writer = WriterEntityFactory::createXLSXWriter();
            $writer->openToFile($path);

            $headerStyle = (new StyleBuilder)->setFontBold()->build();
            $writer->addRow(WriterEntityFactory::createRowFromArray(['Quarter 0'.$quarter_id.': Product Profit And Loss Statements'], $headerStyle));
            $writer->addRow(WriterEntityFactory::createRow());

            foreach ($productReport as $product) {

                // 1. Product Title
                $writer->addRow(WriterEntityFactory::createRowFromArray([
                    'Product '.str_pad($product->id, 2, '0', STR_PAD_LEFT).' - '.$product->sort_name.' - '.$product->name,
                ], $headerStyle));

                // 2. Region Headers
                $regionHeader = [''];
                foreach ($product->marketRegion as $region) {
                    $regionHeader[] = 'Region '.$region->id;
                    $regionHeader[] = '';
                }
                $writer->addRow(WriterEntityFactory::createRowFromArray($regionHeader, $headerStyle));

                // 3. Sub-headers (Retail, Direct)
                $subHeader = [''];
                foreach ($product->marketRegion as $region) {
                    $subHeader[] = 'Retail';
                    $subHeader[] = 'Direct';
                }
                $writer->addRow(WriterEntityFactory::createRowFromArray($subHeader, $headerStyle));

                // 4. Metrics Row-wise
                $rows = [
                    ['Product Status'],
                    ['Sales Volume'],
                    ['Price'],
                    ['Unfilled Orders'],
                    ['Revenues'],
                    ['Expenses'],
                    ['- Product Costs'],
                    ['- Production Shift Cost'],
                    ['- Fixed Cost Per Order'],
                    ['- Order Processing'],
                    ['- Replacement Parts'],
                    ['- RFID Costs'],
                    ['- Duties & Tariffs'],
                    ['Total Expenses'],
                    ['Gross Margin'],
                    ['Fixed Costs'],
                    ['- Administrative OH'],
                    ['- Forecast Inaccuracy'],
                    ['- Marketing'],
                    ['- Price Changes'],
                    ['- Service Outsourcing'],
                    ['Total Fixed Costs'],
                    ['Operating Income'],
                ];

                foreach ($product->marketRegion as $region) {

                    $rows[0][] = $region->productActive[0]->productStatus ?? '';
                    $rows[0][] = $region->productActive[1]->productStatus ?? '';

                    $rows[1][] = number_format($region->productSalesVolume[0]->salesVolume ?? 0);
                    $rows[1][] = number_format($region->productSalesVolume[1]->salesVolume ?? 0);

                    $rows[2][] = '$'.number_format($region->price[0]->price ?? 0);
                    $rows[2][] = '$'.number_format($region->price[1]->price ?? 0);

                    $rows[3][] = number_format($region->unfilledOrders);
                    $rows[3][] = '';

                    $rows[4][] = number_format($region->revenues[0]->totalRevenues ?? 0);
                    $rows[4][] = number_format($region->revenues[1]->totalRevenues ?? 0);

                    $rows[5][] = '';
                    $rows[5][] = '';

                    $rows[6][] = number_format($region->productCost);
                    $rows[6][] = '';

                    $rows[7][] = number_format($region->productionShiftCost);
                    $rows[7][] = '';

                    $rows[8][] = number_format($region->fixedCost);
                    $rows[8][] = '';

                    $rows[9][] = number_format($region->orderProcessing);
                    $rows[9][] = '';

                    $rows[10][] = number_format($region->replacementParts);
                    $rows[10][] = '';

                    $rows[11][] = number_format($region->rfidCosts);
                    $rows[11][] = '';

                    $rows[12][] = number_format($region->dutiesTariffs);
                    $rows[12][] = '';

                    $rows[13][] = number_format($region->totalExpenses);
                    $rows[13][] = '';

                    $rows[14][] = number_format($region->grossMargin);
                    $rows[14][] = '';

                    $rows[15][] = '';
                    $rows[15][] = '';

                    $rows[16][] = number_format($region->administrativeOH);
                    $rows[16][] = '';

                    $rows[17][] = number_format($region->forecastInaccuracy);
                    $rows[17][] = '';

                    $rows[18][] = number_format($region->marketing);
                    $rows[18][] = '';

                    $rows[19][] = number_format($region->priceChanges);
                    $rows[19][] = '';

                    $rows[20][] = number_format($region->serviceOutsourcing);
                    $rows[20][] = '';

                    $rows[21][] = number_format($region->totalFixedCosts);
                    $rows[21][] = '';

                    $rows[22][] = number_format($region->operatingIncome);
                    $rows[22][] = '';
                }

                foreach ($rows as $row) {
                    if (in_array($row[0], ['Expenses', 'Fixed Costs'])) {
                        $writer->addRow(WriterEntityFactory::createRowFromArray($row, $headerStyle));
                    } else {
                        $writer->addRow(WriterEntityFactory::createRowFromArray($row));
                    }
                }

                $writer->addRow(WriterEntityFactory::createRow());
            }

            $writer->close();

            return ReportExport::create([
                'simulation_id' => $simulation_id,
                'faculty_id' => $faculty_id,
                'group_id' => $group_id,
                'quarter' => $quarter_id,
                'report_type' => 'product-pnl',
                'file_path' => 'exports/'.$fileName,
                'file_name' => $fileName,
            ]);
        } catch (\Throwable $e) {
            Log::error('Product P&L Download Error: '.$e->getMessage());
        }
    }

    /**
     * Export the Balance Sheet financial report.
     */
    public function balanceSheet(int $simulation_id, int $faculty_id, int $group_id, int $quarter_id)
    {
        try {

            $exportsPath = storage_path('app/public/exports');

            if (! file_exists($exportsPath)) {
                mkdir($exportsPath, 0775, true);
            }

            $data = $this->balanceSheetService->generateBalanceSheet($simulation_id, $faculty_id, $group_id, $quarter_id);

            // Step 5: Define the filename for the downloadable Excel report
            $fileName = 'balance-sheet-'.uniqid().'-'.now()->format('YmdHis').'.xlsx';
            $path = storage_path('app/public/exports/'.$fileName);

            $writer = WriterEntityFactory::createXLSXWriter();
            $writer->openToFile($path);

            $boldStyle = (new StyleBuilder)->setFontBold()->build();
            $writer->addRow(WriterEntityFactory::createRowFromArray(['Quarter 0'.$quarter_id.': Balance Sheet Report'], $boldStyle));
            $writer->addRow(WriterEntityFactory::createRow()); // Empty Row

            // Heading
            $writer->addRow(WriterEntityFactory::createRowFromArray(["Quarter {$quarter_id}: Balance Sheet Report"], $boldStyle));
            $writer->addRow(WriterEntityFactory::createRow()); // Empty

            // Assets
            $writer->addRow(WriterEntityFactory::createRowFromArray(['Assets'], $boldStyle));
            $writer->addRow(WriterEntityFactory::createRowFromArray(['Type', 'Amount (₹)'], $boldStyle));
            foreach ($data['assets'] as $key => $value) {
                $writer->addRow(WriterEntityFactory::createRowFromArray([$key, number_format($value, 2)]));
            }

            // Finished Goods Breakdown
            if (! empty($data['assets_breakdown']['Finished Goods Inventory'])) {
                $writer->addRow(WriterEntityFactory::createRow()); // Empty
                $writer->addRow(WriterEntityFactory::createRowFromArray(['Finished Goods Inventory Breakdown'], $boldStyle));
                $writer->addRow(WriterEntityFactory::createRowFromArray(['Region', 'Product Name', 'Quantity', 'Cost / Unit'], $boldStyle));
                foreach ($data['assets_breakdown']['Finished Goods Inventory'] as $fg) {
                    foreach ($fg->products as $product) {
                        $writer->addRow(WriterEntityFactory::createRowFromArray([
                            $fg->region_id,
                            "Product {$product->product_id}: {$product->product_name}",
                            $product->quantity,
                            number_format($product->product_cost, 2),
                        ]));
                    }
                }
            }

            // Procurement Breakdown
            if (! empty($data['assets_breakdown']['Procurement Inventory'])) {
                $writer->addRow(WriterEntityFactory::createRow()); // Empty
                $writer->addRow(WriterEntityFactory::createRowFromArray(['Procurement Inventory Breakdown'], $boldStyle));
                $writer->addRow(WriterEntityFactory::createRowFromArray(['Region', 'Component Name', 'Quantity', 'Cost / Unit'], $boldStyle));
                foreach ($data['assets_breakdown']['Procurement Inventory'] as $proc) {
                    foreach ($proc->procurement as $component) {
                        $writer->addRow(WriterEntityFactory::createRowFromArray([
                            $proc->region_id,
                            $component->name,
                            $component->units,
                            number_format($component->cost, 2),
                        ]));
                    }
                }
            }

            // Liabilities and Equities
            $writer->addRow(WriterEntityFactory::createRow()); // Empty
            $writer->addRow(WriterEntityFactory::createRowFromArray(['Liabilities & Equities'], $boldStyle));
            $writer->addRow(WriterEntityFactory::createRowFromArray(['Type', 'Amount (₹)'], $boldStyle));
            foreach ($data['liabilities_and_equities'] as $key => $value) {
                $writer->addRow(WriterEntityFactory::createRowFromArray([$key, number_format($value, 2)]));
            }

            // Notes
            if (! empty($data['notes'])) {
                $writer->addRow(WriterEntityFactory::createRow()); // Empty
                $writer->addRow(WriterEntityFactory::createRowFromArray(['Notes'], $boldStyle));
                foreach ($data['notes'] as $note) {
                    $writer->addRow(WriterEntityFactory::createRowFromArray([$note]));
                }
            }
            // Step 6: Finalize the writer (closes stream and sends the file to the browser)
            $writer->close();

            return ReportExport::create([
                'simulation_id' => $simulation_id,
                'faculty_id' => $faculty_id,
                'group_id' => $group_id,
                'quarter' => $quarter_id,
                'report_type' => 'balance-sheet',
                'file_path' => 'exports/'.$fileName,
                'file_name' => $fileName,
            ]);
        } catch (\Throwable $e) {
            Log::error('Download Error in Balance Sheet Report: '.$e->getMessage());
        }
    }

    /**
     * Export the Cash Flow Analysis Report financial report.
     */
    public function cashFlow(int $simulation_id, int $faculty_id, int $group_id, int $quarter_id)
    {
        try {

            $exportsPath = storage_path('app/public/exports');

            if (! file_exists($exportsPath)) {
                mkdir($exportsPath, 0775, true);
            }

            $data = $this->cashFlowService->generateReport($simulation_id, $faculty_id, $group_id, $quarter_id);

            // Step 5: Define the filename for the downloadable Excel report
            $fileName = 'cash-flow-'.uniqid().'-'.now()->format('YmdHis').'.xlsx';
            $path = storage_path('app/public/exports/'.$fileName);

            $writer = WriterEntityFactory::createXLSXWriter();
            $writer->openToFile($path);
            $boldStyle = (new StyleBuilder)->setFontBold()->build();

            // Report Title
            $writer->addRow(WriterEntityFactory::createRowFromArray(['Quarter 0'.$quarter_id.': Cash Flow Analysis Report'], $boldStyle));
            $writer->addRow(WriterEntityFactory::createRow()); // Empty Row

            $rows = [
                ['Starting Cash Balance (Final Cash Balance, Q'.($quarter_id - 1).')', number_format((float) ($data['starting_cash'] ?? 0), 0)],
                ['+ Marketable Securities (Converted to Cash in Q'.($quarter_id - 1).')', number_format((float) ($data['marketable_securities_converted'] ?? 0), 0)],
                ['- Loans (Liquidated During Q'.($quarter_id - 1).')', number_format((float) ($data['loans_repaid'] ?? 0), 0)],
                [],
                ['+ Finished Goods Inventory Changes', ''],
                ['Product Inventory Delta', number_format((float) ($data['finished_goods_inventory_delta'] ?? 0), 0)],
                [],
                ['+ Procurement Inventory Changes', ''],
                ['Procurement Inventory Delta', number_format((float) ($data['procurement_inventory_delta'] ?? 0), 0)],
                [],
                ['+ Plant Investment Changes', number_format((float) ($data['plant_investment_delta'] ?? 0), 0)],
                ['+ Net Income', number_format((float) ($data['net_income'] ?? 0), 0)],
                ["'= Preliminary Cash Balance", number_format((float) ($data['preliminary_cash'] ?? 0), 0)],
                ["- Dividends (Paid at End of Q{$quarter_id})", number_format((float) ($data['dividends'] ?? 0), 0)],
                ["'= Actual Cash Balance (End of Q{$quarter_id})", number_format((float) ($data['actual_cash'] ?? 0), 0)],
                ['- Operating Cash Excess (To Marketable Securities)', number_format((float) ($data['cash_excess'] ?? 0), 0)],
                ['+ Operating Cash Deficit (From Loans)', number_format((float) ($data['cash_deficit'] ?? 0), 0)],
                ["'= Final Cash Balance (End of Q{$quarter_id})", number_format((float) ($data['final_cash'] ?? 0), 0)],
                [],
                [],
                ['Notes'],
                ['(1) Marketable Securities and Loans refer to values from last quarter\'s balance sheet.'],
                ['(2) Inventory and investment changes can be positive (cash outflow), negative (cash inflow), or zero.'],
                ['(3) Only one of "Operating Cash Excess" or "Deficit" will be non-zero. Cash is auto-balanced between 5% and 10% of revenue.'],
            ];

            foreach ($rows as $row) {
                $isBold = isset($row[0]) && str_starts_with($row[0], '=');
                $writer->addRow(
                    WriterEntityFactory::createRowFromArray($row, $isBold ? $boldStyle : null)
                );
            }

            // Step 6: Finalize the writer (closes stream and sends the file to the browser)
            $writer->close();

            return ReportExport::create([
                'simulation_id' => $simulation_id,
                'faculty_id' => $faculty_id,
                'group_id' => $group_id,
                'quarter' => $quarter_id,
                'report_type' => 'cash-flow',
                'file_path' => 'exports/'.$fileName,
                'file_name' => $fileName,
            ]);
        } catch (\Throwable $e) {
            Log::error('Download Error in Cash Flow Analysis Report: '.$e->getMessage());
        }
    }

    /**
     * Export the Finished Goods extra financial report.
     */
    public function finishedGoods(int $simulation_id, int $faculty_id, int $group_id, int $quarter_id)
    {
        try {

            $exportsPath = storage_path('app/public/exports');

            if (! file_exists($exportsPath)) {
                mkdir($exportsPath, 0775, true);
            }

            $dcCenters = app(FinishedGoodsInventoryService::class)
                ->finishedGoodsInventory($simulation_id, $faculty_id, $quarter_id, $group_id);

            $fileName = 'finished-goods-'.uniqid().'-'.now()->format('YmdHis').'.xlsx';
            $path = storage_path('app/public/exports/'.$fileName);

            $writer = WriterEntityFactory::createXLSXWriter();
            $writer->openToFile($path);
            $headerStyle = (new StyleBuilder)->setFontBold()->build();

            $writer->addRow(
                WriterEntityFactory::createRowFromArray(
                    ['Quarter 0'.$quarter_id.': FGI - Finished Goods Inventory Report'],
                    $headerStyle
                )
            );
            $writer->addRow(WriterEntityFactory::createRow());

            foreach ($dcCenters as $item) {

                $regionTitle = 'DC-'.str_pad($item->dc_number, 2, '0', STR_PAD_LEFT)
                  .' '.($item->region->name ?? '')
                  .' - '.($item->region->custome_name ?? '');

                $writer->addRow(WriterEntityFactory::createRowFromArray([$regionTitle], $headerStyle));

                // Column headers
                $writer->addRow(WriterEntityFactory::createRowFromArray([
                    'Metric',
                    'Product 0 (SHELL)',
                    'Product 01 (SMART HOME ASSISTANT)',
                    'Product 02 (SMART THERMOSTAT)',
                ], $headerStyle));

                // Beginning
                $writer->addRow(WriterEntityFactory::createRowFromArray([
                    'Beginning Inventory',
                    $item->beginning_inventory->product_0 ?? 0,
                    $item->beginning_inventory->product_01 ?? 0,
                    $item->beginning_inventory->product_02 ?? 0,
                ]));

                // Regular Production
                $writer->addRow(WriterEntityFactory::createRowFromArray([
                    '+ Regular Production',
                    $item->regular_production->product_0 ?? 0,
                    $item->regular_production->product_01 ?? 0,
                    $item->regular_production->product_02 ?? 0,
                ]));

                // Emergency Production
                $writer->addRow(WriterEntityFactory::createRowFromArray([
                    '+ Emergency Production',
                    $item->emergency_production->product_0 ?? 0,
                    $item->emergency_production->product_01 ?? 0,
                    $item->emergency_production->product_02 ?? 0,
                ]));

                // Postponed RAW
                $writer->addRow(WriterEntityFactory::createRowFromArray([
                    '+ Postponed Production (RAW product_0 staged)',
                    $item->postponed_production->product_0 ?? 0,
                    $item->postponed_production->product_01 ?? 0,
                    $item->postponed_production->product_02 ?? 0,
                ]));

                // Available
                $writer->addRow(WriterEntityFactory::createRowFromArray([
                    'AVAILABLE INVENTORY (Before Demand)',
                    $item->available_inventory->product_0 ?? 0,
                    $item->available_inventory->product_01 ?? 0,
                    $item->available_inventory->product_02 ?? 0,
                ], $headerStyle));

                // Sales
                $writer->addRow(WriterEntityFactory::createRowFromArray([
                    '- Actual Sales (Demand)',
                    $item->sales->product_0 ?? 0,
                    $item->sales->product_01 ?? 0,
                    $item->sales->product_02 ?? 0,
                ]));

                // Used FG
                $writer->addRow(WriterEntityFactory::createRowFromArray([
                    'Used Finished Goods',
                    0,
                    $item->used_conversion->used_fg_p1 ?? 0,
                    $item->used_conversion->used_fg_p2 ?? 0,
                ]));

                // Converted allocations
                $writer->addRow(WriterEntityFactory::createRowFromArray([
                    'Converted from Product 0 to meet demand',
                    ($item->used_conversion->converted_p1 ?? 0) + ($item->used_conversion->converted_p2 ?? 0),
                    $item->used_conversion->converted_p1 ?? 0,
                    $item->used_conversion->converted_p2 ?? 0,
                ]));

                // Shortage
                $writer->addRow(WriterEntityFactory::createRowFromArray([
                    'Remaining Shortage After Conversion',
                    0,
                    $item->used_conversion->remaining_shortage_p1 ?? 0,
                    $item->used_conversion->remaining_shortage_p2 ?? 0,
                ]));

                // SHIPMENTS
                $writer->addRow(WriterEntityFactory::createRowFromArray(['- Shipments']));

                $writer->addRow(WriterEntityFactory::createRowFromArray([
                    'Surface',
                    $item->shipment->surface->product_0 ?? 0,
                    $item->shipment->surface->product_01 ?? 0,
                    $item->shipment->surface->product_02 ?? 0,
                ]));

                $writer->addRow(WriterEntityFactory::createRowFromArray([
                    'Air',
                    $item->shipment->air->product_0 ?? 0,
                    $item->shipment->air->product_01 ?? 0,
                    $item->shipment->air->product_02 ?? 0,
                ]));

                $writer->addRow(WriterEntityFactory::createRowFromArray([
                    'Emergency',
                    $item->shipment->emergency->product_0 ?? 0,
                    $item->shipment->emergency->product_01 ?? 0,
                    $item->shipment->emergency->product_02 ?? 0,
                ]));

                // ENDING INVENTORY
                $writer->addRow(WriterEntityFactory::createRowFromArray([
                    'ENDING INVENTORY (Final)',
                    $item->ending_inventory->product_0 ?? 0,
                    $item->ending_inventory->product_01 ?? 0,
                    $item->ending_inventory->product_02 ?? 0,
                ], $headerStyle));

                $writer->addRow(WriterEntityFactory::createRowFromArray([]));
            }

            $writer->close();

            return ReportExport::create([
                'simulation_id' => $simulation_id,
                'faculty_id' => $faculty_id,
                'group_id' => $group_id,
                'quarter' => $quarter_id,
                'report_type' => 'finished-goods',
                'file_path' => 'exports/'.$fileName,
                'file_name' => $fileName,
            ]);
        } catch (\Throwable $e) {
            Log::error('Download Error in Finished Goods Inventory Report: '.$e->getMessage());
        }
    }

    /**
     * Export the Procurement extra financial report.
     */
    public function procurement(int $simulation_id, int $faculty_id, int $group_id, int $quarter_id)
    {
        try {

            $exportsPath = storage_path('app/public/exports');

            if (! file_exists($exportsPath)) {
                mkdir($exportsPath, 0775, true);
            }

            // Fetch procurement data
            $procurementData = app(ProcurementService::class)->procurement($simulation_id, $faculty_id, $quarter_id, $group_id);
            $dcCenters = $procurementData->dcCenters;
            $procurementInventory = $procurementData->procurementInventory;
            $inventoryTypes = ['plastic', 'casing', 'control_interface', 'audio_modal', 'motherboard'];

            // Step 4: Define the filename for the downloadable Excel report
            $fileName = 'procurement-'.uniqid().'-'.now()->format('YmdHis').'.xlsx';
            $path = storage_path('app/public/exports/'.$fileName);

            // Step 5: Initialize Spout XLSX Writer to stream file directly to browser
            $writer = WriterEntityFactory::createXLSXWriter();
            $writer->openToFile($path);
            $headerStyle = (new StyleBuilder)->setFontBold()->build();

            // Report Title
            $writer->addRow(WriterEntityFactory::createRowFromArray(['Quarter 0'.$quarter_id.': Procurement Inventory Report'], $headerStyle));
            $writer->addRow(WriterEntityFactory::createRow()); // Empty Row

            $headerRow = ['Distribution Center(s)'];
            foreach ($procurementInventory as $type) {
                $headerRow[] = $type->name.($type->name === 'Plastics' || $type->name === 'Casings' ? ' (KG)' : ' (Unit)');
            }

            $writer->addRow(WriterEntityFactory::createRowFromArray($headerRow, $headerStyle));

            foreach ($dcCenters as $item) {

                $regionLabel = 'DC-'.str_pad($item->dc_number, 2, '0', STR_PAD_LEFT).': Region ('.$item->region->name.') - '.$item->region->custome_name;

                // Section 1: Beginning Inventory
                $row = [$regionLabel.' - Beginning Inventory'];
                foreach ($inventoryTypes as $type) {
                    $row[] = number_format(optional($item->beginning_inventory)->$type ?? 0, 0);
                }
                $writer->addRow(WriterEntityFactory::createRowFromArray($row));

                // Section 2: Purchases Surface
                $row = ['Purchases Surface'];
                foreach ($inventoryTypes as $type) {
                    $row[] = number_format(optional($item->purchases_surface)->$type ?? 0, 0);
                }
                $writer->addRow(WriterEntityFactory::createRowFromArray($row));

                // Section 3: Purchases Air
                $row = ['Purchases Air'];
                foreach ($inventoryTypes as $type) {
                    $row[] = number_format(optional($item->purchases_air)->$type ?? 0, 0);
                }
                $writer->addRow(WriterEntityFactory::createRowFromArray($row));

                // Section 4: Purchases Emergency
                $row = ['Purchases Emergency'];
                foreach ($inventoryTypes as $type) {
                    $row[] = number_format(optional($item->purchases_emergency)->$type ?? 0, 0);
                }
                $writer->addRow(WriterEntityFactory::createRowFromArray($row));

                // Section 5: Available Inventory
                $row = ['AVAILABLE INVENTORY'];
                foreach ($inventoryTypes as $type) {
                    $row[] = number_format(optional($item->available_inventory)->$type ?? 0, 0);
                }
                $writer->addRow(WriterEntityFactory::createRowFromArray($row));

                // Section 6: Production by Product
                if ($item->dc_number === 1 && $item->region_id === 1) {
                    $writer->addRow(WriterEntityFactory::createRowFromArray(['Production:']));
                    foreach ($item->production as $product) {
                        $row = ['Product '.$product->product_id.' - '.$product->product_name];
                        foreach ($inventoryTypes as $type) {
                            $row[] = '-'.number_format(optional($product)->$type ?? 0, 0);
                        }
                        $writer->addRow(WriterEntityFactory::createRowFromArray($row));
                    }

                    $writer->addRow(WriterEntityFactory::createRowFromArray(['Emergency Production:']));
                    foreach ($item->emergency_production as $product) {
                        $row = ['Product '.$product->product_id.' - '.$product->product_name];
                        foreach ($inventoryTypes as $type) {
                            $row[] = '-'.number_format(optional($product)->$type ?? 0, 0);
                        }
                        $writer->addRow(WriterEntityFactory::createRowFromArray($row));
                    }
                }

                // Section 7: Postponed Production
                $row = ['Postponed Production'];
                foreach ($inventoryTypes as $type) {
                    $row[] = '-'.number_format(optional($item->postponed_production)->$type ?? 0, 0);
                }
                $writer->addRow(WriterEntityFactory::createRowFromArray($row));

                // Section 8: Replacement Parts
                $row = ['Replacement Parts'];
                foreach ($inventoryTypes as $type) {
                    $row[] = '-'.number_format(optional($item->replacement_parts)->$type ?? 0, 0);
                }
                $writer->addRow(WriterEntityFactory::createRowFromArray($row));

                // Section 9: Purchases Delayed
                $row = ['Purchases Delayed'];
                foreach ($inventoryTypes as $type) {
                    $row[] = number_format(optional($item->purchases_delayed)->$type ?? 0, 0);
                }
                $writer->addRow(WriterEntityFactory::createRowFromArray($row));

                // Section 10: Ending Inventory
                $row = ['Ending Inventory'];
                foreach ($inventoryTypes as $type) {
                    $row[] = number_format(optional($item->ending_inventory)->$type ?? 0, 0);
                }
                $writer->addRow(WriterEntityFactory::createRowFromArray($row));

                // Add empty row for spacing
                $writer->addRow(WriterEntityFactory::createRowFromArray(['']));
            }

            // Step 6: Finalize the writer (closes stream and sends the file to the browser)
            $writer->close();

            return ReportExport::create([
                'simulation_id' => $simulation_id,
                'faculty_id' => $faculty_id,
                'group_id' => $group_id,
                'quarter' => $quarter_id,
                'report_type' => 'procurement',
                'file_path' => 'exports/'.$fileName,
                'file_name' => $fileName,
            ]);
        } catch (\Throwable $e) {
            Log::error('Download Error in Procurement Inventory Report: '.$e->getMessage());
        }
    }

    /**
     * Export the Forecast Accuracy extra financial report.
     */
    public function forecastAccuracy(int $simulation_id, int $faculty_id, int $group_id, int $quarter_id)
    {
        try {

            $exportsPath = storage_path('app/public/exports');

            if (! file_exists($exportsPath)) {
                mkdir($exportsPath, 0775, true);
            }

            $regions = app(ForecastAccuracyService::class)->forecastAccuracy($simulation_id, $faculty_id, $quarter_id, $group_id);

            // Step 5: Initialize Spout XLSX Writer to stream file directly to browser
            $fileName = 'forecast-accuracy-'.uniqid().'-'.now()->format('YmdHis').'.xlsx';
            $path = storage_path('app/public/exports/'.$fileName);

            $writer = WriterEntityFactory::createXLSXWriter();
            $writer->openToFile($path);
            $headerStyle = (new StyleBuilder)->setFontBold()->build();

            // Report Title
            $writer->addRow(WriterEntityFactory::createRowFromArray(['Quarter 0'.$quarter_id.': Forecasting Accuracy Report'], $headerStyle));
            $writer->addRow(WriterEntityFactory::createRow()); // Empty Row

            foreach ($regions as $region) {

                // Section Header: Region Name
                $writer->addRow(WriterEntityFactory::createRowFromArray(['Region: '.$region->name], $headerStyle));

                // Sub-Headers Row
                $writer->addRow(WriterEntityFactory::createRowFromArray([
                    '',
                    'Forecast - Demand Retail',
                    'Forecast - Demand Direct',
                    'Actual - Demand Retail',
                    'Actual - Demand Direct',
                    'Accuracy - Demand Retail',
                    'Accuracy - Demand Direct',
                ], $headerStyle));

                // Product Data Rows
                foreach ($region->products as $product) {
                    $data = $product->demandData;

                    $writer->addRow(WriterEntityFactory::createRowFromArray([
                        'Product '.str_pad($product->id, 2, '0', STR_PAD_LEFT),
                        $data->forecastDemand->demandChannel_01 ?? 0,
                        $data->forecastDemand->demandChannel_02 ?? 0,
                        $data->actualDemand->demandChannel_01 ?? 0,
                        $data->actualDemand->demandChannel_02 ?? 0,
                        $data->demandAccuracy->demandChannel_01 ?? 0 .'%',
                        $data->demandAccuracy->demandChannel_02 ?? 0 .'%',

                    ]));
                }

                // Add empty row for spacing between regions
                $writer->addRow(WriterEntityFactory::createRowFromArray(['']));
            }

            // Step 6: Finalize the writer (closes stream and sends the file to the browser)
            $writer->close();

            return ReportExport::create([
                'simulation_id' => $simulation_id,
                'faculty_id' => $faculty_id,
                'group_id' => $group_id,
                'quarter' => $quarter_id,
                'report_type' => 'forecast-accuracy',
                'file_path' => 'exports/'.$fileName,
                'file_name' => $fileName,
            ]);
        } catch (\Throwable $e) {
            Log::error('Download Error in Forecasting Accuracy Report: '.$e->getMessage());
        }
    }

    /**
     * Export the Service Operations extra financial report.
     */
    public function serviceOps(int $simulation_id, int $faculty_id, int $group_id, int $quarter_id)
    {
        try {

            $exportsPath = storage_path('app/public/exports');

            if (! file_exists($exportsPath)) {
                mkdir($exportsPath, 0775, true);
            }

            // Step 4: Define the filename for the downloadable Excel report
            $regions = app(ServiceManagementService::class)->serviceManagement($simulation_id, $faculty_id, $quarter_id, $group_id);

            // Step 5: Initialize Spout XLSX Writer to stream file directly to browser
            $fileName = 'service-ops-'.uniqid().'-'.now()->format('YmdHis').'.xlsx';
            $path = storage_path('app/public/exports/'.$fileName);

            $writer = WriterEntityFactory::createXLSXWriter();
            $writer->openToFile($path);
            $headerStyle = (new StyleBuilder)->setFontBold()->build();
            $currency = config('currency.symbol');

            // Report Title
            $writer->addRow(WriterEntityFactory::createRowFromArray(['Quarter 0'.$quarter_id.': Service Center Operations Reports'], $headerStyle));
            $writer->addRow(WriterEntityFactory::createRow()); // Empty Row

            foreach ($regions as $region) {

                $regionTitle = "Region ({$region->name}): {$region->custome_name}";
                $writer->addRow(WriterEntityFactory::createRowFromArray([$regionTitle], $headerStyle));

                // Product Header
                $writer->addRow(WriterEntityFactory::createRowFromArray([
                    'Product Name',
                    'Total Calls',
                    'CSR Cost per Call',
                    'Service Outsourcing Cost',
                ], $headerStyle));

                foreach ($region->products as $product) {

                    $serviceCost = $product->calls * $product->csr_cost;

                    $writer->addRow(WriterEntityFactory::createRowFromArray([
                        $product->name,
                        number_format($product->calls, 0),
                        $currency.number_format($product->csr_cost, 2),
                        $currency.number_format($serviceCost, 2),
                    ]));
                }

                // Total Calls and Overall Cost
                $writer->addRow(WriterEntityFactory::createRow()); // Empty Row

                $writer->addRow(WriterEntityFactory::createRowFromArray([
                    'Overall Calls',
                    number_format($region->totalCall, 0),
                ], $headerStyle));

                $writer->addRow(WriterEntityFactory::createRowFromArray([
                    'Overall Calls Cost',
                    $currency.number_format($region->serviceOutsourcingTotal, 2),
                ], $headerStyle));

                $writer->addRow(WriterEntityFactory::createRow()); // Separator
                $writer->addRow(WriterEntityFactory::createRow()); // Separator
            }
            // Step 6: Finalize the writer (closes stream and sends the file to the browser)
            $writer->close();

            return ReportExport::create([
                'simulation_id' => $simulation_id,
                'faculty_id' => $faculty_id,
                'group_id' => $group_id,
                'quarter' => $quarter_id,
                'report_type' => 'service-ops',
                'file_path' => 'exports/'.$fileName,
                'file_name' => $fileName,
            ]);
        } catch (\Throwable $e) {
            Log::error('Download Error in Service Center Operations Reports: '.$e->getMessage());
        }
    }

    /**
     * Export the Decision Variables extra financial report.
     */
    public function decisionVars(int $simulation_id, int $faculty_id, int $group_id, int $quarter_id)
    {
        try {

            $exportsPath = storage_path('app/public/exports');

            if (! file_exists($exportsPath)) {
                mkdir($exportsPath, 0775, true);
            }

            // Step 4: Define the filename for the downloadable Excel report
            $decisionReportData = app(DecisionService::class)->decisionReport($simulation_id, $faculty_id, $quarter_id, $group_id);
            $procurementRawMaterial = $decisionReportData->procurementRawMaterial;
            $procurementSubAssembly = $decisionReportData->procurementSubAssembly;
            $manufacturing = $decisionReportData->manufacturing;
            $shipments = $decisionReportData->shipments;
            $suppliers = Supplier::all();

            // Step 5: Initialize Spout XLSX Writer to stream file directly to browser
            $fileName = 'decision-vars-'.uniqid().'-'.now()->format('YmdHis').'.xlsx';
            $path = storage_path('app/public/exports/'.$fileName);

            $writer = WriterEntityFactory::createXLSXWriter();
            $writer->openToFile($path);
            $headerStyle = (new StyleBuilder)->setFontBold()->build();

            // Report Title
            $writer->addRow(WriterEntityFactory::createRowFromArray(['Quarter 0'.$quarter_id.': Other Decision Variables Report'], $headerStyle));
            $writer->addRow(WriterEntityFactory::createRow()); // Empty Row

            $writer->addRow(WriterEntityFactory::createRowFromArray(['Procurement - Raw Materials'], $headerStyle));

            $rawHeaders = collect($procurementRawMaterial)->pluck('name')->toArray();
            $writer->addRow(WriterEntityFactory::createRowFromArray($rawHeaders));

            $rawData = collect($procurementRawMaterial)->pluck('quantity')->map(fn ($q) => number_format($q, 0))->toArray();
            $writer->addRow(WriterEntityFactory::createRowFromArray($rawData));

            $writer->addRow(WriterEntityFactory::createRowFromArray([''])); // Blank row

            // ------------------ Sub-Assembly Components ------------------//
            $writer->addRow(WriterEntityFactory::createRowFromArray(['Procurement - Sub-Assembly Components'], $headerStyle));

            $supplierNames = collect($suppliers)->pluck('name')->map(fn ($s) => "Supplier ($s)")->toArray();
            $headers = array_merge(['DC', 'Component'], $supplierNames);
            $writer->addRow(WriterEntityFactory::createRowFromArray($headers));

            foreach ($procurementSubAssembly as $item) {
                foreach ($item->subAssembly as $component) {
                    $supplierData = collect($component->suppliers)
                        ->pluck('procurement')
                        ->map(fn ($p) => number_format($p, 0))
                        ->values()
                        ->toArray();
                    $writer->addRow(WriterEntityFactory::createRowFromArray([
                        'DC'.$item->region_id,
                        $component->name,
                        ...$supplierData,
                    ]));
                }
            }

            $writer->addRow(WriterEntityFactory::createRowFromArray([''])); // Blank row

            // ------------------ Manufacturing ------------------//
            $writer->addRow(WriterEntityFactory::createRowFromArray(['Manufacturing'], $headerStyle));
            $writer->addRow(WriterEntityFactory::createRowFromArray(['Process', 'Product 0', 'Product 1', 'Product 2']));

            foreach ($manufacturing as $process => $data) {
                $writer->addRow(WriterEntityFactory::createRowFromArray([
                    ucwords(str_replace('_', ' ', $process)),
                    number_format($data->product_0, 0),
                    number_format($data->product_1, 0),
                    number_format($data->product_2, 0),
                ]));
            }

            $writer->addRow(WriterEntityFactory::createRowFromArray([''])); // Blank row

            // ------------------ Transportation ------------------//
            $writer->addRow(WriterEntityFactory::createRowFromArray(['Transportation - Plant/DC1 Shipments To Other DCs'], $headerStyle));
            $writer->addRow(WriterEntityFactory::createRowFromArray(['DC', 'Carrier', 'Product 0', 'Product 1', 'Product 2']));

            foreach ($shipments as $shipment) {
                foreach ($shipment->carriers as $carrier) {
                    $writer->addRow(WriterEntityFactory::createRowFromArray([
                        'DC'.$shipment->distribution_center_id,
                        'Carrier '.$carrier->name,
                        ...collect($carrier->products)
                            ->map(fn ($q) => number_format($q, 0))
                            ->values() // 🔥 FIX
                            ->toArray(),
                    ]));
                }
            }

            $writer->addRow(WriterEntityFactory::createRowFromArray([''])); // Blank row

            // ------------------ Information Technology ------------------//
            $writer->addRow(WriterEntityFactory::createRowFromArray(['Information Technology'], $headerStyle));

            $itItems = [
                'IT Synchronization With Carriers?',
                'IT Synchronization With Suppliers?',
                'Procurement Transactions Report?',
                'Product Cost Report?',
                'Replacement Parts Demand Report?',
                'Retail Pipeline Report?',
                'Transportation Cost Report?',
                'Transportation Report?',
            ];

            foreach ($itItems as $item) {
                $writer->addRow(WriterEntityFactory::createRowFromArray([$item, '0']));
            }

            // Step 6: Finalize the writer (closes stream and sends the file to the browser)
            $writer->close();

            return ReportExport::create([
                'simulation_id' => $simulation_id,
                'faculty_id' => $faculty_id,
                'group_id' => $group_id,
                'quarter' => $quarter_id,
                'report_type' => 'decision-vars',
                'file_path' => 'exports/'.$fileName,
                'file_name' => $fileName,
            ]);
        } catch (\Throwable $e) {
            Log::error('Download Error in Other Decision Variables Report: '.$e->getMessage());
        }
    }
}
