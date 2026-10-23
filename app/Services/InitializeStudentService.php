<?php

namespace App\Services;

use App\Jobs\ExportSimulationReportsJob;
use App\Jobs\GenerateScorecardJob;
use App\Jobs\SendAiAnalysisReportJob;
use App\Models\Carrier;
use App\Models\Decisions\Demand\ActualDemand;
use App\Models\Decisions\Demand\InitialDemand;
use App\Models\Decisions\Demand\ProbabilityDemand;
use App\Models\Decisions\DemandElasticity;
use App\Models\Decisions\DemandPlanning;
use App\Models\Decisions\Distribution;
use App\Models\Decisions\Forecasting;
use App\Models\Decisions\InformationTechnology;
use App\Models\Decisions\InformationTechnologyCarrier;
use App\Models\Decisions\InformationTechnologySupplier;
use App\Models\Decisions\InventoryStock;
use App\Models\Decisions\Manufacturing;
use App\Models\Decisions\Procurement;
use App\Models\Decisions\ProcurementRawMaterial;
use App\Models\Decisions\ProcurementSubAssembly;
use App\Models\Decisions\ServiceManagement;
use App\Models\DemandChannel;
use App\Models\FinancialQuarter;
use App\Models\Packaging;
use App\Models\Product;
use App\Models\ProductMaterial;
use App\Models\QuarterInitialDemand;
use App\Models\Region;
use App\Models\Simulation;
use App\Models\SimulationFirm;
use App\Models\Supplier;
use App\Models\SupplyChannel;
use App\Services\Reports\BalanceSheetService;
use App\Services\Reports\FlexScorecardService;
use App\Services\Reports\Supplementary\FinishedGoodsInventoryService;
use App\Services\Reports\Supplementary\ProcurementService;
use App\Support\SimRandom;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Psr\Log\LoggerInterface;

class InitializeStudentService
{
    protected $qualityAnalysisService;

    protected $balanceSheetService;

    protected $procurementService;

    protected $generateDemandService;

    protected $finishedGoodsInventoryService;

    protected $flexScorecardService;

    protected LoggerInterface $logger;

    public function __construct(
        QualityAnalysisService $qualityAnalysisService,
        BalanceSheetService $balanceSheetService,
        ProcurementService $procurementService,
        FinishedGoodsInventoryService $finishedGoodsInventoryService,
        GenerateDemandService $generateDemandService,
        FlexScorecardService $flexScorecardService,
        LoggerInterface $logger
    ) {
        $this->qualityAnalysisService = $qualityAnalysisService;
        $this->balanceSheetService = $balanceSheetService;
        $this->procurementService = $procurementService;
        $this->finishedGoodsInventoryService = $finishedGoodsInventoryService;
        $this->generateDemandService = $generateDemandService;
        $this->flexScorecardService = $flexScorecardService;
        $this->logger = $logger;
    }

    // ---------- Forecasting ----------
    public function setupForecasting(int $simulationId, int $facultyId, int $groupId, int $studentId): bool
    {
        $regions = Region::all();
        $products = Product::all();

        // unique firm ids for the simulation to avoid duplicate SimulationFirm rows causing repeated writes
        $firmIds = SimulationFirm::where('simulation_id', $simulationId)
            ->pluck('firm_id')
            ->unique()
            ->values()
            ->all();

        // Preload only QuarterInitialDemand (the single source of truth for calculations)
        $keyFn = function ($r) {
            return "{$r->simulation_id}|{$r->product_id}|{$r->region_id}|{$r->firm_id}|{$r->quarter}";
        };
        $qInitials = QuarterInitialDemand::where('simulation_id', $simulationId)->get()->keyBy($keyFn);

        // In-memory store for forecasts produced in this run; used if an initial is missing next quarter
        $inMemoryForecasts = [];

        for ($quarterId = 0; $quarterId <= 3; $quarterId++) {
            DB::transaction(function () use (
                $simulationId,
                $facultyId,
                $groupId,
                $studentId,
                $regions,
                $products,
                $firmIds,
                $quarterId,
                $qInitials,
                &$inMemoryForecasts
            ) {
                $now = Carbon::now();

                foreach ($regions as $region) {
                    foreach ($products as $product) {
                        foreach ($firmIds as $firm_id) {
                            $readKey = "{$simulationId}|{$product->id}|{$region->id}|{$firm_id}|{$quarterId}";

                            // Determine initial demand to use
                            $initialRow = $qInitials->get($readKey);
                            if ($initialRow) {
                                $initial_d1 = (float) $initialRow->demand_channel_01;
                                $initial_d2 = (float) $initialRow->demand_channel_02;
                            } else {
                                // fallback to last-run forecast for previous quarter if available
                                if ($quarterId > 0) {
                                    $prevKey = "{$simulationId}|{$product->id}|{$region->id}|{$firm_id}|".($quarterId - 1);
                                    $prevForecast = $inMemoryForecasts[$prevKey] ?? null;
                                    if ($prevForecast) {
                                        $initial_d1 = (float) $prevForecast['d1'];
                                        $initial_d2 = (float) $prevForecast['d2'];
                                    } else {
                                        $initial_d1 = 0.0;
                                        $initial_d2 = 0.0;
                                    }
                                } else {
                                    $initial_d1 = 0.0;
                                    $initial_d2 = 0.0;
                                }

                                // optional log to diagnose missing initial rows
                                if (! isset($prevForecast)) {
                                    $this->logger->debug('setupForecasting: missing initial and prev forecast', [
                                        'key' => $readKey,
                                        'quarter' => $quarterId,
                                    ]);
                                }
                            }

                            // Compute probability demand
                            $prob_d1 = $this->generateDemandService->probabilityDemand($initial_d1, $this->generateDemandService->randomNumber());
                            $prob_d2 = $this->generateDemandService->probabilityDemand($initial_d2, $this->generateDemandService->randomNumber());

                            // Compute actual demand from probability
                            $actual_d1 = $this->generateDemandService->actualDemand($prob_d1);
                            $actual_d2 = $this->generateDemandService->actualDemand($prob_d2);

                            // Compute forecast demand from actual and initial
                            $forecast_d1 = $this->generateDemandService->forecastDemand($actual_d1, $initial_d1);
                            $forecast_d2 = $this->generateDemandService->forecastDemand($actual_d2, $initial_d2);

                            // Compute accuracy
                            $accuracy_d1 = $this->generateDemandService->forecastAccuracy($forecast_d1, $actual_d1);
                            $accuracy_d2 = $this->generateDemandService->forecastAccuracy($forecast_d2, $actual_d2);

                            // Save forecasts to in-memory map for next-quarter fallback
                            $inMemoryForecasts[$readKey] = [
                                'd1' => $forecast_d1,
                                'd2' => $forecast_d2,
                            ];

                            // Build the target conditions for this group
                            $targetConditions = [
                                'simulation_id' => $simulationId,
                                'product_id' => $product->id,
                                'region_id' => $region->id,
                                'firm_id' => $firm_id,
                                'quarter' => $quarterId,
                                'faculty_id' => $facultyId,
                                'group_number' => $groupId,
                            ];

                            // Persist InitialDemand
                            InitialDemand::updateOrCreate($targetConditions, [
                                'demand_channel_01' => (int) round($initial_d1),
                                'demand_channel_02' => (int) round($initial_d2),
                                'change_saved_by' => $studentId,
                                'change_saved_timestamp' => $now,
                            ]);

                            // Persist ProbabilityDemand
                            ProbabilityDemand::updateOrCreate($targetConditions, [
                                'demand_channel_01' => (int) round($prob_d1),
                                'demand_channel_02' => (int) round($prob_d2),
                                'gdp_pct' => 0,
                                'change_saved_by' => $studentId,
                                'change_saved_timestamp' => $now,
                            ]);

                            // Persist ActualDemand
                            ActualDemand::updateOrCreate($targetConditions, [
                                'demand_channel_01' => (int) round($actual_d1),
                                'demand_channel_02' => (int) round($actual_d2),
                                'change_saved_by' => $studentId,
                                'change_saved_timestamp' => $now,
                            ]);

                            // Persist Forecasting
                            Forecasting::updateOrCreate($targetConditions, [
                                'demand_channel_01' => (int) round($forecast_d1),
                                'demand_channel_02' => (int) round($forecast_d2),
                                'accuracy_channel_01' => round($accuracy_d1, 2),
                                'accuracy_channel_02' => round($accuracy_d2, 2),
                                'change_saved_by' => $studentId,
                                'change_saved_timestamp' => $now,
                            ]);
                        } // end firm loop
                    } // end product loop
                } // end region loop
            }); // end quarter transaction
        } // end quarter loop

        return true;
    }

    // ---------- Manufacturing ----------
    public function setupManufacturing(int $simulationId, int $facultyId, int $groupId, int $studentId): bool
    {
        $products = Product::all();

        for ($quarterId = 0; $quarterId <= 3; $quarterId++) {
            DB::transaction(function () use ($simulationId, $facultyId, $groupId, $studentId, $products, $quarterId) {
                foreach ($products as $product) {
                    $forecastDemand = Forecasting::where([
                        'simulation_id' => $simulationId,
                        'faculty_id' => $facultyId,
                        'group_number' => $groupId,
                        'quarter' => $quarterId,
                        'product_id' => $product->id,
                    ])->selectRaw('SUM(demand_channel_01 + demand_channel_02) as total_demand')->first();

                    Manufacturing::updateOrCreate([
                        'simulation_id' => $simulationId,
                        'faculty_id' => $facultyId,
                        'group_number' => $groupId,
                        'quarter' => $quarterId,
                        'product_id' => $product->id,
                    ], [
                        'change_saved_by' => $studentId,
                        'production_quantity' => $forecastDemand->total_demand ?? 0,
                        'emergency_production' => 0,
                        'adjustable_production' => 0,
                        'change_saved_timestamp' => now(),
                    ]);
                }
            });
        }

        return true;
    }

    // ---------- Distribution (short name) ----------
    public function setupDistribution(int $simulationId, int $facultyId, int $groupId, int $studentId): bool
    {
        $regions = Region::all();

        for ($quarterId = 0; $quarterId <= 3; $quarterId++) {
            DB::transaction(function () use ($simulationId, $facultyId, $groupId, $studentId, $regions, $quarterId) {
                foreach ($regions->take(1) as $region) {
                    $isHomeRegion = $region->id === 1;

                    Distribution::firstOrCreate([
                        'simulation_id' => $simulationId,
                        'faculty_id' => $facultyId,
                        'group_number' => $groupId,
                        'quarter' => $quarterId,
                        'region_id' => $region->id,
                        'dc_number' => $region->id,
                    ], [
                        'dc_type' => $isHomeRegion ? 'insourced' : SimRandom::pick(['insourced', 'outsourced']),
                        'rfid_application' => 'yes',
                        'is_home_region' => $isHomeRegion ? 'yes' : 'no',
                        'ffgi_shipping' => $isHomeRegion ? null : SimRandom::pick(['economy', 'standard', 'expedited']),
                        'sac_shipping' => SimRandom::pick(['economy', 'standard', 'expedited']),
                        'cross_docking_k' => $isHomeRegion ? null : SimRandom::pick(['yes', 'no']),
                        'cross_docking_l' => $isHomeRegion ? null : SimRandom::pick(['yes', 'no']),
                        'cross_docking_m' => $isHomeRegion ? null : SimRandom::pick(['yes', 'no']),
                        'cross_docking_n' => $isHomeRegion ? null : SimRandom::pick(['yes', 'no']),
                        'emergency_carrier_id' => $isHomeRegion ? 5 : Carrier::inRandomOrder()->value('id'),
                        'change_saved_by' => $studentId,
                    ]);
                }
            });
        }

        $totalQuarters = Simulation::where('id', $simulationId)->value('quarters') ?? 4;
        for ($quarterId = 4; $quarterId <= $totalQuarters; $quarterId++) {
            DB::transaction(function () use ($simulationId, $facultyId, $groupId, $studentId, $quarterId) {
                Distribution::firstOrCreate([
                    'simulation_id' => $simulationId,
                    'faculty_id' => $facultyId,
                    'group_number' => $groupId,
                    'quarter' => $quarterId,
                    'region_id' => 1,
                    'dc_number' => 1,
                ], [
                    'dc_type' => 'insourced',
                    'rfid_application' => 'yes',
                    'is_home_region' => 'yes',
                    'ffgi_shipping' => null,
                    'sac_shipping' => 'economy',
                    'cross_docking_k' => null,
                    'cross_docking_l' => null,
                    'cross_docking_m' => null,
                    'cross_docking_n' => null,
                    'emergency_carrier_id' => 5,
                    'change_saved_by' => $studentId,
                ]);
            });
        }

        return true;
    }

    // ---------- Transportation (kept intentionally blank) ----------
    public function setupTransportation(int $simulationId, int $facultyId, int $groupId, int $studentId): bool
    {
        return true;
    }

    // ---------- Service Management ----------
    public function setupServiceManagement(int $simulationId, int $facultyId, int $groupId, int $studentId): bool
    {
        $regionIds = Region::pluck('id');

        for ($quarterId = 0; $quarterId <= 3; $quarterId++) {
            DB::transaction(function () use ($simulationId, $facultyId, $groupId, $studentId, $regionIds, $quarterId) {
                foreach ($regionIds as $regionId) {
                    ServiceManagement::firstOrCreate([
                        'simulation_id' => $simulationId,
                        'faculty_id' => $facultyId,
                        'group_number' => $groupId,
                        'quarter' => $quarterId,
                        'region_id' => $regionId,
                    ], [
                        'service_id' => 1,
                        'change_saved_by' => $studentId,
                    ]);
                }
            });
        }

        return true;
    }

    // ---------- Demand Planning ----------
    public function setupDemandPlanning(int $simulationId, int $facultyId, int $groupId, int $studentId): bool
    {
        $regionIds = Region::pluck('id');
        $productIds = Product::pluck('id');
        $demandChannels = SupplyChannel::pluck('id');

        for ($quarterId = 0; $quarterId <= 3; $quarterId++) {
            DB::transaction(function () use ($simulationId, $facultyId, $groupId, $studentId, $regionIds, $productIds, $demandChannels, $quarterId) {
                foreach ($regionIds as $regionId) {
                    foreach ($productIds as $productId) {
                        foreach ($demandChannels as $demandChannelId) {
                            $marketSpending = $demandChannelId == 1 ? 5000 : 10000;

                            $price = $demandChannelId == 1
                              ? ceil(rand(300, 350) / 10) * 10
                              : ceil(rand(400, 450) / 10) * 10;

                            if ($demandChannelId == 1) {
                                $markupPercentage = rand(20, 35);
                                $markupMultiplier = 1 + ($markupPercentage / 100);
                                $marketPrice = round(round($price * $markupMultiplier) / 10) * 10;
                            } else {
                                $marketPrice = $price;
                            }

                            DemandPlanning::firstOrCreate([
                                'simulation_id' => $simulationId,
                                'faculty_id' => $facultyId,
                                'group_number' => $groupId,
                                'quarter' => $quarterId,
                                'region_id' => $regionId,
                                'product_id' => $productId,
                                'demand_channel_id' => $demandChannelId,
                            ], [
                                'status' => 'yes',
                                'price' => $price,
                                'market_price' => $marketPrice,
                                'marketSpending' => $marketSpending,
                                'change_saved_by' => $studentId,
                            ]);
                        }
                    }
                }
            });
        }

        return true;
    }

    // ---------- Information Technology ----------
    public function setupInformationTechnology(int $simulationId, int $facultyId, int $groupId, int $studentId): bool
    {
        $suppliers = Supplier::inRandomOrder()->limit(4)->get();
        $carriers = Carrier::inRandomOrder()->limit(4)->get();

        if ($suppliers->isEmpty() || $carriers->isEmpty()) {
            throw new \RuntimeException('Suppliers or carriers not available for Information Technology setup.');
        }

        for ($quarterId = 0; $quarterId <= 3; $quarterId++) {
            DB::transaction(function () use ($simulationId, $facultyId, $groupId, $studentId, $suppliers, $carriers, $quarterId) {
                $it = InformationTechnology::firstOrCreate([
                    'simulation_id' => $simulationId,
                    'faculty_id' => $facultyId,
                    'group_number' => $groupId,
                    'quarter' => $quarterId,
                ], [
                    'change_saved_by' => $studentId,
                    'ai_integration' => 1,
                ]);

                if ($it->wasRecentlyCreated) {
                    foreach ($suppliers as $supplier) {
                        InformationTechnologySupplier::create([
                            'information_technology_id' => $it->id,
                            'supplier_id' => $supplier->id,
                        ]);
                    }

                    foreach ($carriers as $carrier) {
                        InformationTechnologyCarrier::create([
                            'information_technology_id' => $it->id,
                            'carrier_id' => $carrier->id,
                        ]);
                    }
                }
            });
        }

        return true;
    }

    // ---------- Procurement ----------
    public function setupProcurement(
        int $simulationId,
        int $facultyId,
        int $groupId,
        int $studentId
    ): bool {

        $materialsTypes = ProductMaterial::select('type')
            ->distinct()
            ->pluck('type')
            ->toArray();

        $products = Product::all();

        for ($quarterId = 0; $quarterId <= 3; $quarterId++) {

            DB::transaction(function () use (
                $simulationId,
                $facultyId,
                $groupId,
                $studentId,
                $materialsTypes,
                $products,
                $quarterId
            ) {

                // Only Home DC (Region 1)
                $distributionCenters = Distribution::where([
                    'simulation_id' => $simulationId,
                    'faculty_id' => $facultyId,
                    'group_number' => $groupId,
                    'quarter' => $quarterId,
                    'region_id' => 1,
                    'dc_number' => 1,
                ])->get();

                foreach ($distributionCenters as $distCenter) {

                    // Create procurement header
                    $procurement = Procurement::firstOrCreate([
                        'simulation_id' => $simulationId,
                        'faculty_id' => $facultyId,
                        'group_number' => $groupId,
                        'quarter' => $quarterId,
                        'region_id' => $distCenter->region_id,
                    ], [
                        'procurement_type' => 'raw_material, sub_assembly_component',
                        'change_saved_by' => $studentId,
                    ]);

                    // If already created, do not recalc
                    if (! $procurement->wasRecentlyCreated) {
                        return;
                    }

                    /**
                     * ---------------------------------------------------------
                     * PROCUREMENT = MANUFACTURING (Qtr 1–3 RULE)
                     * ---------------------------------------------------------
                     */
                    $totalMaterial = [];

                    foreach ($materialsTypes as $type) {

                        $totalMaterial[$type] = [];

                        foreach ($products as $product) {

                            // Manufacturing quantity (already equals forecast)
                            $manufacturingQty = Manufacturing::where([
                                'simulation_id' => $simulationId,
                                'faculty_id' => $facultyId,
                                'group_number' => $groupId,
                                'quarter' => $quarterId,
                                'product_id' => $product->id,
                            ])->value('production_quantity') ?? 0;

                            if ($manufacturingQty <= 0) {
                                continue;
                            }

                            // BOM items for product
                            $productMaterials = ProductMaterial::with(
                                $type === 'raw_material' ? 'rawMaterial' : 'sac'
                            )->where([
                                'product_id' => $product->id,
                                'type' => $type,
                            ])->get();

                            foreach ($productMaterials as $material) {

                                $key = $material->material_id;
                                $requiredQty = $material->quantity * $manufacturingQty;

                                if (! isset($totalMaterial[$type][$key])) {
                                    $totalMaterial[$type][$key] = [
                                        'material_id' => $material->material_id,
                                        'total_quantity' => 0,
                                    ];
                                }

                                $totalMaterial[$type][$key]['total_quantity'] += $requiredQty;
                            }
                        }

                        $totalMaterial[$type] = array_values($totalMaterial[$type]);
                    }

                    // Save RAW MATERIAL procurement
                    foreach ($totalMaterial['raw_material'] ?? [] as $item) {
                        ProcurementRawMaterial::create([
                            'procurement_id' => $procurement->id,
                            'raw_material_id' => $item['material_id'],
                            'quantity' => $item['total_quantity'],
                        ]);
                    }

                    // Save SUB-ASSEMBLY (Motherboard etc.)
                    foreach ($totalMaterial['sub_assembly_component'] ?? [] as $item) {
                        ProcurementSubAssembly::create([
                            'procurement_id' => $procurement->id,
                            'sub_assembly_component_id' => $item['material_id'],
                            'supplier_id' => 4,
                            'supply_channel_id' => 1,
                            'quantity' => $item['total_quantity'],
                            'total_shipment' => $item['total_quantity'],
                            'quantity_delay' => 0,
                        ]);
                    }
                }
            });
        }

        return true;
    }

    // ---------- Demand Elasticity ----------
    public function setupDemandElasticity(int $simulationId, int $facultyId, int $groupId, int $studentId): bool
    {
        $regions = Region::all();
        $products = Product::with('material.sac.supplierComponent.supplier')->get();
        $channels = DemandChannel::all();
        $defaultPackagingCost = Packaging::find(1)->cost ?? 10;

        for ($quarterId = 1; $quarterId <= 3; $quarterId++) {
            $previousQuarter = $quarterId - 1;

            $forecastDemand = Forecasting::where([
                'simulation_id' => $simulationId,
                'faculty_id' => $facultyId,
            ])->where('quarter', $previousQuarter)->get();

            $actualDemandEntries = ActualDemand::where([
                'simulation_id' => $simulationId,
                'faculty_id' => $facultyId,
            ])->where('quarter', $previousQuarter)->get();

            $demandPlanningEntries = DemandPlanning::where([
                'simulation_id' => $simulationId,
                'faculty_id' => $facultyId,
            ])->where('quarter', $quarterId)->get();

            $pastPlans = DemandPlanning::where([
                'simulation_id' => $simulationId,
                'faculty_id' => $facultyId,
            ])->where('quarter', $previousQuarter)->get();

            $performanceIndicators = DemandElasticity::where([
                'simulation_id' => $simulationId,
                'faculty_id' => $facultyId,
            ])->where('quarter', $previousQuarter)->get();

            $services = ServiceManagement::with('serviceOutsourcing.serviceCost')
                ->where([
                    'simulation_id' => $simulationId,
                    'faculty_id' => $facultyId,
                ])->where('quarter', $quarterId)->get();

            $currentPlans = DemandPlanning::where([
                'simulation_id' => $simulationId,
                'faculty_id' => $facultyId,
            ])->where('quarter', $quarterId)->get();

            foreach ($regions as $region) {
                foreach ($products as $product) {
                    foreach ($channels as $channel) {
                        $pastForecast = $forecastDemand
                            ->where('region_id', $region->id)
                            ->where('product_id', $product->id)
                            ->firstWhere('group_number', $groupId);

                        $pastActual = $actualDemandEntries
                            ->where('region_id', $region->id)
                            ->where('product_id', $product->id)
                            ->firstWhere('group_number', $groupId);

                        $pastPlan = $pastPlans
                            ->where('region_id', $region->id)
                            ->where('product_id', $product->id)
                            ->where('demand_channel_id', $channel->id)
                            ->firstWhere('group_number', $groupId);

                        $currentPlan = $demandPlanningEntries
                            ->where('region_id', $region->id)
                            ->where('product_id', $product->id)
                            ->where('demand_channel_id', $channel->id)
                            ->firstWhere('group_number', $groupId);

                        $pastIndicator = $performanceIndicators
                            ->where('region_id', $region->id)
                            ->where('product_id', $product->id)
                            ->where('demand_channel_id', $channel->id)
                            ->firstWhere('group_number', $groupId);

                        if (! $pastPlan || ! $pastActual || ! $currentPlan) {
                            continue;
                        }

                        $forecastVolume = $pastForecast ? (
                            $channel->id == 1 ? ($pastForecast->demand_channel_01 ?? 0) : ($pastForecast->demand_channel_02 ?? 0)
                        ) : 0;

                        $actualSale = $channel->id == 1
                          ? ($pastActual->demand_channel_01 ?? 0)
                          : ($pastActual->demand_channel_02 ?? 0);

                        $pq = $this->qualityAnalysisService->calculatePQ($product, $simulationId, $previousQuarter, $groupId);
                        $sq = $this->qualityAnalysisService->calculateSQ($groupId, $region->id, $services);
                        $aq = $this->qualityAnalysisService->calculateAQ($forecastVolume, $actualSale);

                        $rawValues = [
                            'Marketing' => $pastPlan->marketSpending ?? 0,
                            'Packaging' => $defaultPackagingCost,
                            'Warranty' => 8 + 3 * 9,
                        ];

                        $preferences = $this->qualityAnalysisService->getPreferences($product->sort_name, $region->id);

                        $cai = $this->qualityAnalysisService->calculateCAI($aq, $sq, $pq, $preferences, $rawValues);
                        $baseCai = $quarterId == 1 ? 0.25 : ($pastIndicator->cai ?? 0.25);

                        $newDemand = $this->qualityAnalysisService->calculateAdjustedDemand(
                            $actualSale,
                            $cai,
                            $baseCai,
                            $currentPlan->price,
                            $pastPlan->price
                        );

                        DemandElasticity::updateOrCreate([
                            'simulation_id' => $simulationId,
                            'faculty_id' => $facultyId,
                            'group_number' => $groupId,
                            'quarter' => $quarterId,
                            'region_id' => $region->id,
                            'product_id' => $product->id,
                            'demand_channel_id' => $channel->id,
                        ], [
                            'pq' => $pq,
                            'sq' => $sq,
                            'aq' => $aq,
                            'cai' => $cai,
                        ]);
                    }
                }
            }
        }

        return true;
    }

    // ---------- Financial snapshot ----------
    public function setupFinancialSnapshot(int $simulationId, int $facultyId, int $groupId, int $studentId): bool
    {
        for ($quarterId = 0; $quarterId <= 3; $quarterId++) {
            $alreadyExists = FinancialQuarter::where([
                'simulation_id' => $simulationId,
                'faculty_id' => $facultyId,
                'group_number' => $groupId,
                'quarter_id' => $quarterId,
            ])->exists();

            if ($alreadyExists) {
                continue;
            }

            $this->balanceSheetService->storeFinancialSnapshot($simulationId, $facultyId, $groupId, $quarterId);
        }

        return true;
    }

    // ---------- Ending inventory ----------
    public function storeEndingInventory(int $simulationId, int $facultyId, int $groupId, int $studentId): bool
    {
        for ($quarterId = 0; $quarterId <= 3; $quarterId++) {
            $this->procurementService->procurement($simulationId, $facultyId, $quarterId, $groupId, true);
        }

        return true;
    }

    public function storeEndingInventoryFinishGood(int $simulationId, int $facultyId, int $groupId, int $studentId): bool
    {
        for ($quarterId = 0; $quarterId <= 3; $quarterId++) {
            $this->finishedGoodsInventoryService->finishedGoodsInventory($simulationId, $facultyId, $quarterId, $groupId, true);
        }

        return true;
    }

    // ---------- Scorecard & reports ----------
    public function storeScorecard(int $simulationId, int $facultyId, int $groupId, int $studentId): bool
    {
        for ($quarterId = 0; $quarterId <= 3; $quarterId++) {
            GenerateScorecardJob::dispatch($simulationId, $facultyId, $groupId, $quarterId)->onQueue('scorecards');
        }

        return true;
    }

    public function exportFinancialReports(int $simulationId, int $facultyId, int $groupId, int $studentId): bool
    {
        for ($quarterId = 0; $quarterId <= 3; $quarterId++) {
            ExportSimulationReportsJob::dispatch($simulationId, $facultyId, $groupId, $quarterId)->onQueue('financial-reports');
        }

        return true;
    }

    // ---------- AI analysis ----------
    public function analysisReport(int $simulationId, int $facultyId, int $groupId, int $studentId): bool
    {

        // Detect protocol based on environment
        $protocol = app()->environment('production') ? 'https' : 'http';

        // Get base host from .env (configured in config/services.php)
        $host = config('services.analysis.host');

        for ($quarterId = 1; $quarterId <= 3; $quarterId++) {

            // Build dynamic analysis URL
            $url = sprintf(
                '%s://%s/api/overall_insight_for_quarter/?simulation_id=%d&faculty_id=%d&group_id=%d&quarter=%d',
                $protocol,
                $host,
                $simulationId,
                $facultyId,
                $groupId,
                $quarterId
            );

            SendAiAnalysisReportJob::dispatch($url)->onQueue('ai-report-analysis');
        }

        return true;
    }

    /**
     * Generates the motherboard-based manufacturing plan (90% utilization, evenly split across products 1 and 2).
     *
     * @return int
     */
    private function motherboardProductionPlan(int $simulation_id, ?int $faculty_id, ?int $group_id, int $quarter_id): array
    {
        // 1) beginning inventory = InventoryStock where quarter = quarter_id - 1
        $beginningInventory = InventoryStock::where([
            'simulation_id' => $simulation_id,
            'faculty_id' => $faculty_id,
            'group_number' => $group_id,
            'quarter' => $quarter_id - 1,
        ])->first();

        $beginMotherboards = max(0, $beginningInventory->motherboard ?? 0);

        // 2) arrivals = previous-quarter procurements (normal + emergency)
        $prevMotherboardArrival = 0;

        if ($quarter_id > 0) {

            $prevProcurements = Procurement::with('subAssembly', 'procurementEmergencySub')
                ->where([
                    'simulation_id' => $simulation_id,
                    'faculty_id' => $faculty_id,
                    'group_number' => $group_id,
                    'quarter' => $quarter_id - 1,
                ])->first();

            if ($prevProcurements) {
                $prevNormal = optional($prevProcurements->subAssembly)
                    ->where('sub_assembly_component_id', 3)
                    ->sum('quantity') ?? 0;

                $prevEmergency = optional($prevProcurements->procurementEmergencySub)
                    ->where('sub_assembly_component_id', 3)
                    ->sum('quantity') ?? 0;

                $prevMotherboardArrival = $prevNormal + $prevEmergency;
            }
        }

        // ✅ total available motherboards
        $totalAvailable = $beginMotherboards + $prevMotherboardArrival;

        // ✅ calculate 90% of available motherboards for manufacturing
        $toManufactureTotal = (int) floor($totalAvailable * 0.9);

        // ✅ Split 50/50 between Product 1 and Product 2 (handle odd numbers)
        $product1Qty = (int) ceil($toManufactureTotal / 2);
        $product2Qty = (int) floor($toManufactureTotal / 2);

        // ✅ return both total and per-product allocations
        return [
            'total_available' => floor($totalAvailable / 1000) * 1000,
            'to_manufacture' => floor($toManufactureTotal / 1000) * 1000,
            'allocations' => [
                1 => floor($product1Qty / 1000) * 1000,
                2 => floor($product2Qty / 1000) * 1000,
            ],
        ];
    }
}
