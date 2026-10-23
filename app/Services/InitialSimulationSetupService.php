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
use App\Models\QuarterActualDemand;
use App\Models\QuarterForecastDemand;
use App\Models\QuarterInitialDemand;
use App\Models\QuarterProbabilityDemand;
use App\Models\Region;
use App\Models\Simulation;
use App\Models\SimulationFirm;
use App\Models\SimulationTeamMember;
use App\Models\Supplier;
use App\Models\SupplyChannel;
use App\Services\Reports\BalanceSheetService;
use App\Services\Reports\FlexScorecardService;
use App\Services\Reports\Supplementary\FinishedGoodsInventoryService;
use App\Services\Reports\Supplementary\ProcurementService;
use App\Support\SimRandom;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class InitialSimulationSetupService
{
    /**
     * Active User Service instance
     */
    protected $activeUserService;

    protected $qualityAnalysisService;

    protected $balanceSheetService;

    protected $procurementService;

    protected $generateDemandService;

    protected $finishedGoodsInventoryService;

    protected $flexScorecardService;

    /**
     * Constructor to inject dependencies.
     */
    public function __construct(
        ActiveUserService $activeUserService,
        QualityAnalysisService $qualityAnalysisService,
        BalanceSheetService $balanceSheetService,
        ProcurementService $procurementService,
        FinishedGoodsInventoryService $finishedGoodsInventoryService,
        GenerateDemandService $generateDemandService,
        FlexScorecardService $flexScorecardService
    ) {
        $this->activeUserService = $activeUserService;
        $this->qualityAnalysisService = $qualityAnalysisService;
        $this->balanceSheetService = $balanceSheetService;
        $this->procurementService = $procurementService;
        $this->finishedGoodsInventoryService = $finishedGoodsInventoryService;
        $this->generateDemandService = $generateDemandService;
        $this->flexScorecardService = $flexScorecardService;
    }

    /**
     * Setup the initial Forecasting.
     */
    public function setupForecasting($simulation_id)
    {
        DB::beginTransaction();

        try {
            // Get the active user
            $user = $this->activeUserService->getActiveUser();

            // Retrieve associated faculty and group ID
            $teamMember = SimulationTeamMember::where([
                'simulation_id' => $simulation_id,
                'student_id' => $user->id,
            ])->first();

            if (! $teamMember) {
                throw new \Exception("Team member not found for Simulation ID: $simulation_id and Student ID: {$user->id}");
            }

            // Extract necessary details
            $faculty_id = $teamMember->faculty_id ?? null;
            $group_id = $teamMember->simulation_team_id ?? null;
            $student_id = $user->id;

            // Fetch all necessary data outside the loops to avoid repeated queries
            $regions = Region::all();
            $products = Product::all();
            $firms = SimulationFirm::where('simulation_id', $simulation_id)->get();

            // Loop through quarters and prepare bulk insert data
            for ($quarter_id = 0; $quarter_id <= 3; $quarter_id++) {
                // Fetch demand data only once per product-region-firm-quarter
                foreach ($regions as $region) {
                    foreach ($products as $product) {
                        foreach ($firms as $firm) {

                            $conditions = [
                                'simulation_id' => $simulation_id,
                                'product_id' => $product->id,
                                'region_id' => $region->id,
                                'firm_id' => $firm->firm_id,
                                'quarter' => $quarter_id,
                            ];

                            // Fetch all required demand data in a single array to reduce redundant queries
                            $demandData = [
                                'initial' => QuarterInitialDemand::where($conditions)->first(),
                                'probability' => QuarterProbabilityDemand::where($conditions)->first(),
                                'actual' => QuarterActualDemand::where($conditions)->first(),
                                'forecast' => QuarterForecastDemand::where($conditions)->first(),
                            ];

                            // Check if the record exists
                            $isForecastingExists = Forecasting::where(array_merge($conditions, [
                                'faculty_id' => $faculty_id,
                                'group_number' => $group_id,
                            ]))->exists();

                            if (! $isForecastingExists) {
                                $commonData = array_merge($conditions, [
                                    'faculty_id' => $faculty_id,
                                    'group_number' => $group_id,
                                    'change_saved_by' => $student_id,
                                    'change_saved_timestamp' => now(),
                                ]);

                                // Insert InitialDemand, ProbabilityDemand, ActualDemand, and Forecasting
                                InitialDemand::create(array_merge($commonData, [
                                    'demand_channel_01' => $demandData['initial']->demand_channel_01 ?? '0',
                                    'demand_channel_02' => $demandData['initial']->demand_channel_02 ?? '0',
                                ]));

                                ProbabilityDemand::create(array_merge($commonData, [
                                    'demand_channel_01' => $demandData['probability']->demand_channel_01 ?? '0',
                                    'demand_channel_02' => $demandData['probability']->demand_channel_02 ?? '0',
                                ]));

                                ActualDemand::create(array_merge($commonData, [
                                    'demand_channel_01' => $demandData[$quarter_id == 0 ? 'forecast' : 'actual']->demand_channel_01 ?? '0',
                                    'demand_channel_02' => $demandData[$quarter_id == 0 ? 'forecast' : 'actual']->demand_channel_02 ?? '0',
                                ]));

                                Forecasting::create(array_merge($commonData, [
                                    'demand_channel_01' => $demandData['forecast']->demand_channel_01 ?? '0',
                                    'demand_channel_02' => $demandData['forecast']->demand_channel_02 ?? '0',
                                    'accuracy_channel_01' => $demandData['forecast']->accuracy_channel_01 ?? '0',
                                    'accuracy_channel_02' => $demandData['forecast']->accuracy_channel_02 ?? '0',
                                ]));
                            }
                        }
                    }
                }
            }

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error: '.$e->getMessage());
        }
    }

    /**
     * Setup the initial manufacturing
     */
    public function setupManufacturing($simulation_id)
    {
        DB::beginTransaction();

        try {
            // Get the active user
            $user = $this->activeUserService->getActiveUser();

            // Retrieve associated faculty and group ID
            $teamMember = SimulationTeamMember::where([
                'simulation_id' => $simulation_id,
                'student_id' => $user->id,
            ])->first();

            if (! $teamMember) {
                throw new \Exception("Team member not found for Simulation ID: $simulation_id and Student ID: {$user->id}");
            }

            // Extract necessary details
            $faculty_id = $teamMember->faculty_id ?? null;
            $group_id = $teamMember->simulation_team_id ?? null;
            $student_id = $user->id;

            $products = Product::all();

            // Loop through quarters and prepare bulk insert data
            for ($quarter_id = 0; $quarter_id <= 3; $quarter_id++) {

                $allocations = [];

                if ($quarter_id > 0) {
                    $plan = $this->motherboardProductionPlan($simulation_id, $faculty_id, $group_id, $quarter_id);
                    $allocations = $plan['allocations'] ?? [];
                }

                foreach ($products as $product) {

                    $forecastDemand = Forecasting::where([
                        'simulation_id' => $simulation_id,
                        'faculty_id' => $faculty_id,
                        'group_number' => $group_id,
                        'quarter' => $quarter_id,
                        'product_id' => $product->id,
                    ])->selectRaw('SUM(demand_channel_01 + demand_channel_02) as total_demand')->first();

                    $productionQty = $quarter_id === 0 ? 0 : ($allocations[$product->id] ?? ($forecastDemand->total_demand ?? 0));

                    $exists = Manufacturing::where([
                        'simulation_id' => $simulation_id,
                        'faculty_id' => $faculty_id,
                        'group_number' => $group_id,
                        'quarter' => $quarter_id,
                        'product_id' => $product->id,
                    ])->exists();

                    if (! $exists) {
                        Manufacturing::create([
                            'simulation_id' => $simulation_id,
                            'faculty_id' => $faculty_id,
                            'group_number' => $group_id,
                            'quarter' => $quarter_id,
                            'product_id' => $product->id,
                            'change_saved_by' => $student_id,
                            'production_quantity' => $productionQty,
                            'emergency_production' => 0,
                            'adjustable_production' => 0,
                            'change_saved_timestamp' => now(),
                        ]);
                    }
                }
            }

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error: '.$e->getMessage());
        }
    }

    /**
     * Setup the initial Distribution Center
     */
    public function setupDistribution($simulation_id)
    {
        DB::beginTransaction();

        try {
            // Get the active user
            $user = $this->activeUserService->getActiveUser();

            // Retrieve associated faculty and group ID
            $teamMember = SimulationTeamMember::where([
                'simulation_id' => $simulation_id,
                'student_id' => $user->id,
            ])->first();

            if (! $teamMember) {
                throw new \Exception("Team member not found for Simulation ID: $simulation_id and Student ID: {$user->id}");
            }

            // Extract necessary details
            $faculty_id = $teamMember->faculty_id ?? null;
            $group_id = $teamMember->simulation_team_id ?? null;
            $student_id = $user->id;

            $regions = Region::all();
            // Loop through quarters and prepare bulk insert data
            for ($quarter_id = 0; $quarter_id <= 3; $quarter_id++) {
                foreach ($regions->take(1) as $region) {
                    $isHomeRegion = $region->id === 1;

                    // Check if the record already exists
                    $exists = Distribution::where([
                        'simulation_id' => $simulation_id,
                        'faculty_id' => $faculty_id,
                        'group_number' => $group_id,
                        'quarter' => $quarter_id,
                        'region_id' => $region->id,
                        'dc_number' => $region->id,
                    ])->exists();

                    if (! $exists) {
                        $dcType = $isHomeRegion ? 'insourced' : SimRandom::pick(['insourced', 'outsourced']);

                        Distribution::create([
                            'simulation_id' => $simulation_id,
                            'faculty_id' => $faculty_id,
                            'group_number' => $group_id,
                            'quarter' => $quarter_id,
                            'region_id' => $region->id,
                            'dc_number' => $region->id,
                            'dc_type' => $dcType,
                            'rfid_application' => $dcType === 'insourced' ? SimRandom::pick(['yes', 'no']) : null,
                            'is_home_region' => $isHomeRegion ? 'yes' : 'no',
                            'ffgi_shipping' => $isHomeRegion ? null : SimRandom::pick(['economy', 'standard', 'expedited']),
                            'sac_shipping' => SimRandom::pick(['economy', 'standard', 'expedited']),
                            'cross_docking_k' => $isHomeRegion ? null : SimRandom::pick(['yes', 'no']),
                            'cross_docking_l' => $isHomeRegion ? null : SimRandom::pick(['yes', 'no']),
                            'cross_docking_m' => $isHomeRegion ? null : SimRandom::pick(['yes', 'no']),
                            'cross_docking_n' => $isHomeRegion ? null : SimRandom::pick(['yes', 'no']),
                            'emergency_carrier_id' => $isHomeRegion ? 5 : Carrier::inRandomOrder()->value('id'),
                            'change_saved_by' => $student_id,
                        ]);
                    }
                }
            }

            // set DC1/Plant for All Quarters Default
            $totalQuarters = Simulation::where('id', $simulation_id)->value('quarters');
            for ($quarter_id = 4; $quarter_id <= $totalQuarters; $quarter_id++) {

                // Check if the record already exists
                $exists = Distribution::where([
                    'simulation_id' => $simulation_id,
                    'faculty_id' => $faculty_id,
                    'group_number' => $group_id,
                    'quarter' => $quarter_id,
                    'region_id' => 1,
                    'dc_number' => 1,
                ])->exists();

                if (! $exists) {

                    Distribution::create([
                        'simulation_id' => $simulation_id,
                        'faculty_id' => $faculty_id,
                        'group_number' => $group_id,
                        'quarter' => $quarter_id,
                        'region_id' => 1,
                        'dc_number' => 1,
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
                        'change_saved_by' => $student_id,
                    ]);
                }
            }

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error: '.$e->getMessage());
        }
    }

    /**
     * Setup the initial transportation / shipment
     */
    public function setupTransportation($simulation_id)
    {
        // Intentionally left blank
    }

    /**
     * Setup the initial service-management
     */
    public function setupServiceManagement($simulation_id)
    {
        DB::beginTransaction();

        try {
            // Get the active user
            $user = $this->activeUserService->getActiveUser();

            // Retrieve associated faculty and group ID
            $teamMember = SimulationTeamMember::where([
                'simulation_id' => $simulation_id,
                'student_id' => $user->id,
            ])->first();

            if (! $teamMember) {
                throw new \Exception("Team member not found for Simulation ID: $simulation_id and Student ID: {$user->id}");
            }

            // Extract necessary details
            $faculty_id = $teamMember->faculty_id ?? null;
            $group_id = $teamMember->simulation_team_id ?? null;
            $student_id = $user->id;

            // Fetch only necessary data for regions
            $regions = Region::pluck('id');

            // Loop through quarters and prepare bulk insert data
            for ($quarter_id = 0; $quarter_id <= 3; $quarter_id++) {
                foreach ($regions as $region_id) {
                    ServiceManagement::firstOrCreate([
                        'simulation_id' => $simulation_id,
                        'faculty_id' => $faculty_id,
                        'group_number' => $group_id,
                        'quarter' => $quarter_id,
                        'region_id' => $region_id,
                    ], [
                        'service_id' => 1,
                        'change_saved_by' => $student_id,
                    ]);
                }
            }

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error: '.$e->getMessage());
        }
    }

    /**
     * Setup the initial demand-planning
     */
    public function setupDemandPlanning($simulation_id)
    {
        DB::beginTransaction();

        try {
            // Get the active user
            $user = $this->activeUserService->getActiveUser();

            // Retrieve associated faculty and group ID
            $teamMember = SimulationTeamMember::where([
                'simulation_id' => $simulation_id,
                'student_id' => $user->id,
            ])->first();

            if (! $teamMember) {
                throw new \Exception("Team member not found for Simulation ID: $simulation_id and Student ID: {$user->id}");
            }

            // Extract necessary details
            $faculty_id = $teamMember->faculty_id ?? null;
            $group_id = $teamMember->simulation_team_id ?? null;
            $student_id = $user->id;

            // Fetch all necessary data
            $regions = Region::pluck('id');
            $products = Product::pluck('id');
            $demandChannels = SupplyChannel::pluck('id');

            // Loop through quarters and prepare bulk insert data
            for ($quarter_id = 0; $quarter_id <= 3; $quarter_id++) {

                foreach ($regions as $region_id) {
                    foreach ($products as $product_id) {
                        foreach ($demandChannels as $demand_channel_id) {

                            $marketSpending = $demand_channel_id == 1 ? 5000 : 10000;
                            // $price = $demand_channel_id == 1 ? 300 : 450;

                            $price = $demand_channel_id == 1
                              ? ceil(rand(300, 350) / 10) * 10
                              : ceil(rand(400, 450) / 10) * 10;

                            if ($demand_channel_id == 1) {
                                $markupPercentage = rand(20, 35);
                                $markupMultiplier = 1 + ($markupPercentage / 100);
                                $marketPrice = round(round($price * $markupMultiplier) / 10) * 10; // Double round to nearest 10
                            } else {
                                $marketPrice = $price;
                            }

                            DemandPlanning::firstOrCreate([
                                'simulation_id' => $simulation_id,
                                'faculty_id' => $faculty_id,
                                'group_number' => $group_id,
                                'quarter' => $quarter_id,
                                'region_id' => $region_id,
                                'product_id' => $product_id,
                                'demand_channel_id' => $demand_channel_id,
                            ], [
                                'status' => 'yes',
                                'price' => $price,
                                'market_price' => $marketPrice,
                                'marketSpending' => $marketSpending,
                                'change_saved_by' => $student_id,
                            ]);
                        }
                    }
                }
            }

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error: '.$e->getMessage());
        }
    }

    /**
     *  Setup the initial Information Technology
     */
    public function setupInformationTechnology($simulation_id)
    {
        DB::beginTransaction();

        try {
            // Get the active user
            $user = $this->activeUserService->getActiveUser();

            // Retrieve associated faculty and group ID
            $teamMember = SimulationTeamMember::where([
                'simulation_id' => $simulation_id,
                'student_id' => $user->id,
            ])->first();

            if (! $teamMember) {
                throw new \Exception("Team member not found for Simulation ID: $simulation_id and Student ID: {$user->id}");
            }

            // Extract necessary details
            $faculty_id = $teamMember->faculty_id ?? null;
            $group_id = $teamMember->simulation_team_id ?? null;
            $student_id = $user->id;

            // Fetch all necessary data
            $suppliers = Supplier::inRandomOrder()->limit(4)->get();
            $carriers = Carrier::inRandomOrder()->limit(4)->get();

            if (empty($suppliers) || empty($carriers)) {
                throw new \Exception('No suppliers or carriers found.');
            }

            // Loop through quarters and prepare bulk insert data
            for ($quarter_id = 0; $quarter_id <= 3; $quarter_id++) {

                $informationTechnology = InformationTechnology::firstOrCreate([
                    'simulation_id' => $simulation_id,
                    'faculty_id' => $faculty_id,
                    'group_number' => $group_id,
                    'quarter' => $quarter_id,
                ], [
                    'change_saved_by' => $student_id,
                    'ai_integration' => 1,
                ]);

                // **Insert only if a new record was created**
                if ($informationTechnology->wasRecentlyCreated) {
                    // Insert supplier records
                    foreach ($suppliers as $supplier) {
                        InformationTechnologySupplier::create([
                            'information_technology_id' => $informationTechnology->id,
                            'supplier_id' => $supplier->id,
                        ]);
                    }

                    // Insert carrier records
                    foreach ($carriers as $carrier) {
                        InformationTechnologyCarrier::create([
                            'information_technology_id' => $informationTechnology->id,
                            'carrier_id' => $carrier->id,
                        ]);
                    }
                }
            }

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error: '.$e->getMessage());
        }
    }

    /**
     * Setup the initial procurement.
     */
    public function setupProcurement($simulation_id)
    {
        DB::beginTransaction();

        try {
            $user = $this->activeUserService->getActiveUser();

            $teamMember = SimulationTeamMember::where([
                'simulation_id' => $simulation_id,
                'student_id' => $user->id,
            ])->firstOrFail();

            $faculty_id = $teamMember->faculty_id;
            $group_id = $teamMember->simulation_team_id;
            $student_id = $user->id;

            $materialsTypes = ProductMaterial::select('type')->distinct()->pluck('type')->toArray();
            $products = Product::all();

            for ($quarter_id = 0; $quarter_id <= 3; $quarter_id++) {

                // Create procurement header (only for DC1/Region1 as per your earlier logic)
                $distributionCenters = Distribution::where([
                    'simulation_id' => $simulation_id,
                    'faculty_id' => $faculty_id,
                    'group_number' => $group_id,
                    'quarter' => $quarter_id,
                    'region_id' => 1,
                    'dc_number' => 1,
                ])->get();

                foreach ($distributionCenters as $distCenter) {
                    $procurement = Procurement::firstOrCreate([
                        'simulation_id' => $simulation_id,
                        'faculty_id' => $faculty_id,
                        'group_number' => $group_id,
                        'quarter' => $quarter_id,
                        'region_id' => $distCenter->region_id,
                    ], [
                        'procurement_type' => 'raw_material, sub_assembly_component',
                        'change_saved_by' => $student_id,
                    ]);

                    if (! $procurement->wasRecentlyCreated) {
                        continue;
                    }

                    // Initialize material totals
                    $totalMaterial = [];

                    foreach ($materialsTypes as $type) {
                        $totalMaterial[$type] = [];

                        foreach ($products as $product) {
                            $totalDemand = Forecasting::where([
                                'simulation_id' => $simulation_id,
                                'faculty_id' => $faculty_id,
                                'group_number' => $group_id,
                                'quarter' => $quarter_id,
                                'product_id' => $product->id,
                            ])->selectRaw('SUM(demand_channel_01 + demand_channel_02) as total_demand')->first()->total_demand ?? 0;

                            $productMaterials = ProductMaterial::with($type === 'raw_material' ? 'rawMaterial' : 'sac')
                                ->where([
                                    'product_id' => $product->id,
                                    'type' => $type,
                                ])->get();

                            foreach ($productMaterials as $material) {
                                $key = $material->material_id;
                                $requiredQty = $material->quantity * $totalDemand;

                                if (isset($totalMaterial[$type][$key])) {
                                    $totalMaterial[$type][$key]['total_quantity'] += $requiredQty;
                                } else {
                                    $totalMaterial[$type][$key] = [
                                        'material_id' => $material->material_id,
                                        'total_quantity' => $requiredQty,
                                        'material' => $material,
                                    ];
                                }
                            }
                        }

                        // Finalize material list
                        $totalMaterial[$type] = array_values($totalMaterial[$type]);
                    }

                    // Insert raw materials
                    foreach ($totalMaterial['raw_material'] as $item) {
                        ProcurementRawMaterial::create([
                            'procurement_id' => $procurement->id,
                            'raw_material_id' => $item['material_id'],
                            'quantity' => $item['total_quantity'],
                        ]);
                    }

                    // Insert sub-assembly components
                    foreach ($totalMaterial['sub_assembly_component'] as $item) {

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
            }

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error in setupProcurement: '.$e->getMessage());
        }
    }

    /**
     * Setup the postpond production.
     */
    public function setupPostPondProduction($simulation_id)
    {
        // Intentionally left blank
    }

    /**
     * Setup the Replacement Parts.
     */
    public function setupReplacementParts($simulation_id)
    {
        // Intentionally left blank
    }

    /**
     * Setup the DemandElasticity PQ, SQ, AQ, CAI etc.
     */
    public function setupDemandElasticity($simulation_id)
    {
        DB::beginTransaction();

        try {
            // Get active user and their team context
            $user = $this->activeUserService->getActiveUser();

            $teamMember = SimulationTeamMember::where([
                'simulation_id' => $simulation_id,
                'student_id' => $user->id,
            ])->first();

            if (! $teamMember) {
                throw new \Exception("Team member not found for Simulation ID: $simulation_id and Student ID: {$user->id}");
            }

            $faculty_id = $teamMember->faculty_id;
            $group_number = $teamMember->simulation_team_id;
            $regions = Region::all();
            $products = Product::with('material.sac.supplierComponent.supplier')->get();
            $channels = DemandChannel::all();
            $defaultPackagingCost = Packaging::find(1)->cost ?? 10;
            $timestamp = now();

            for ($quarter_id = 1; $quarter_id <= 3; $quarter_id++) {
                $previousQuarter = $quarter_id - 1;

                $forecastDemand = Forecasting::where(compact('simulation_id', 'faculty_id'))
                    ->where('quarter', $previousQuarter)->get();

                $actualDemandEntries = ActualDemand::where(compact('simulation_id', 'faculty_id'))
                    ->where('quarter', $previousQuarter)->get();

                $demandPlanningEntries = DemandPlanning::where(compact('simulation_id', 'faculty_id'))
                    ->where('quarter', $quarter_id)->get();

                $pastPlans = DemandPlanning::where(compact('simulation_id', 'faculty_id'))
                    ->where('quarter', $previousQuarter)->get();

                $performanceIndicators = DemandElasticity::where(compact('simulation_id', 'faculty_id'))
                    ->where('quarter', $previousQuarter)->get();

                $services = ServiceManagement::with('serviceOutsourcing.serviceCost')
                    ->where(compact('simulation_id', 'faculty_id'))
                    ->where('quarter', $quarter_id)->get();

                $currentPlans = DemandPlanning::where(compact('simulation_id', 'faculty_id'))
                    ->where('quarter', $quarter_id)->get();

                foreach ($regions as $region) {
                    foreach ($products as $product) {
                        foreach ($channels as $channel) {

                            $pastForecast = $forecastDemand
                                ->where('region_id', $region->id)
                                ->where('product_id', $product->id)
                                ->firstWhere('group_number', $group_number);

                            $pastActual = $actualDemandEntries
                                ->where('region_id', $region->id)
                                ->where('product_id', $product->id)
                                ->firstWhere('group_number', $group_number);

                            $pastPlan = $pastPlans
                                ->where('region_id', $region->id)
                                ->where('product_id', $product->id)
                                ->where('demand_channel_id', $channel->id)
                                ->firstWhere('group_number', $group_number);

                            $currentPlan = $demandPlanningEntries
                                ->where('region_id', $region->id)
                                ->where('product_id', $product->id)
                                ->where('demand_channel_id', $channel->id)
                                ->firstWhere('group_number', $group_number);

                            $pastIndicator = $performanceIndicators
                                ->where('region_id', $region->id)
                                ->where('product_id', $product->id)
                                ->where('demand_channel_id', $channel->id)
                                ->firstWhere('group_number', $group_number);

                            if (! $pastPlan || ! $pastActual || ! $currentPlan) {
                                continue;
                            }

                            $forecastVolume = $pastForecast ? (
                                $channel->id == 1 ? ($pastForecast->demand_channel_01 ?? 0) : ($pastForecast->demand_channel_02 ?? 0)
                            ) : 0;

                            $actualSale = $channel->id == 1
                              ? ($pastActual->demand_channel_01 ?? 0)
                              : ($pastActual->demand_channel_02 ?? 0);

                            $pq = $this->qualityAnalysisService->calculatePQ($product, $simulation_id, $previousQuarter, $group_number);
                            $sq = $this->qualityAnalysisService->calculateSQ($group_number, $region->id, $services);
                            $aq = $this->qualityAnalysisService->calculateAQ($forecastVolume, $actualSale);

                            $rawValues = [
                                'Marketing' => $pastPlan->marketSpending ?? 0,
                                'Packaging' => $defaultPackagingCost,
                                'Warranty' => 8 + 3 * 9,
                            ];

                            $preferences = $this->qualityAnalysisService->getPreferences($product->sort_name, $region->id);

                            $cai = $this->qualityAnalysisService->calculateCAI($aq, $sq, $pq, $preferences, $rawValues);
                            $baseCai = $quarter_id == 1 ? 0.25 : ($pastIndicator->cai ?? 0.25);

                            $newDemand = $this->qualityAnalysisService->calculateAdjustedDemand(
                                $actualSale,
                                $cai,
                                $baseCai,
                                $currentPlan->price,
                                $pastPlan->price
                            );

                            DemandElasticity::updateOrCreate(
                                [
                                    'simulation_id' => $simulation_id,
                                    'faculty_id' => $faculty_id,
                                    'group_number' => $group_number,
                                    'quarter' => $quarter_id,
                                    'region_id' => $region->id,
                                    'product_id' => $product->id,
                                    'demand_channel_id' => $channel->id,
                                ],
                                [
                                    'pq' => $pq,
                                    'sq' => $sq,
                                    'aq' => $aq,
                                    'cai' => $cai,
                                ]
                            );

                            $field = $channel->id == 1 ? 'demand_channel_01' : 'demand_channel_02';
                            // Comment: To balance Simulation
                            ActualDemand::updateOrCreate(
                                [
                                    'simulation_id' => $simulation_id,
                                    'faculty_id' => $faculty_id,
                                    'group_number' => $group_number,
                                    'quarter' => $quarter_id,
                                    'region_id' => $region->id,
                                    'product_id' => $product->id,
                                ],
                                [
                                    'firm_id' => 1,
                                    $field => round($newDemand),
                                    'change_saved_by' => $user->id,
                                    'change_saved_timestamp' => $timestamp,
                                ]
                            );

                            // Update forecast accuracy
                            $forecastRecord = Forecasting::where([
                                'simulation_id' => $simulation_id,
                                'faculty_id' => $faculty_id,
                                'group_number' => $group_number,
                                'quarter' => $quarter_id,
                                'region_id' => $region->id,
                                'product_id' => $product->id,
                            ])->first();

                            if ($forecastRecord) {
                                if ($channel->id == 1) {
                                    $forecastRecord->update([
                                        'accuracy_channel_01' => $this->generateDemandService->forecastAccuracy(
                                            $forecastRecord->demand_channel_01,
                                            round($newDemand)
                                        ),
                                    ]);
                                } else {
                                    $forecastRecord->update([
                                        'accuracy_channel_02' => $this->generateDemandService->forecastAccuracy(
                                            $forecastRecord->demand_channel_02,
                                            round($newDemand)
                                        ),
                                    ]);
                                }
                            }

                        }

                        // Demand Channel Shift Logic:: Comment to balance Simulation
                        // if ($quarter_id > 0) {
                        //   $plan1 = $currentPlans->where('region_id', $region->id)->where('product_id', $product->id)
                        //     ->where('group_number', $group_number)->firstWhere('demand_channel_id', 1);

                        //   $plan2 = $currentPlans->where('region_id', $region->id)->where('product_id', $product->id)
                        //     ->where('group_number', $group_number)->firstWhere('demand_channel_id', 2);

                        //   if ($plan1 && $plan2) {
                        //     $price1 = $plan1->price ?? 0;
                        //     $price2 = $plan2->price ?? 0;

                        //     if ($price1 > 0 && $price2 > 0) {
                        //       $diff = abs($price1 - $price2) / min($price1, $price2) * 100;

                        //       if ($diff > 33) {
                        //         $demand = ActualDemand::where([
                        //           'simulation_id' => $simulation_id,
                        //           'faculty_id'    => $faculty_id,
                        //           'group_number'  => $group_number,
                        //           'quarter'       => $quarter_id,
                        //           'region_id'     => $region->id,
                        //           'product_id'    => $product->id,
                        //         ])->first();

                        //         if ($demand && !$demand->shift_applied) {
                        //           $d1 = $demand->demand_channel_01 ?? 0;
                        //           $d2 = $demand->demand_channel_02 ?? 0;
                        //           $original1 = $d1;
                        //           $original2 = $d2;

                        //           $shift = 0.03;
                        //           $amount = 0;

                        //           if ($price1 > $price2 && $d1 > 0) {
                        //             $amount = round($d1 * $shift);
                        //             $d1 -= $amount;
                        //             $d2 += $amount;
                        //           } elseif ($price2 > $price1 && $d2 > 0) {
                        //             $amount = round($d2 * $shift);
                        //             $d2 -= $amount;
                        //             $d1 += $amount;
                        //           }

                        //           if ($original1 !== $d1 || $original2 !== $d2) {
                        //             $demand->update([
                        //               'demand_channel_01' => max(0, $d1),
                        //               'demand_channel_02' => max(0, $d2),
                        //               'shift_applied'     => true,
                        //             ]);
                        //           }
                        //         }
                        //       }
                        //     }
                        //   }
                        // }
                    }
                }
            }

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Demand Elasticity setup failed: '.$e->getMessage());
        }
    }

    /**
     * Setup the FinancialSnapshot.
     */
    public function setupFinancialSnapshot($simulation_id)
    {
        // Get the currently active user (student)
        $user = $this->activeUserService->getActiveUser();

        // Fetch the user's simulation team details
        $teamMember = SimulationTeamMember::where([
            'simulation_id' => $simulation_id,
            'student_id' => $user->id,
        ])->first();

        // Abort if user is not part of any simulation team
        if (! $teamMember) {
            throw new \Exception("Team member not found for Simulation ID: $simulation_id and Student ID: {$user->id}");
        }

        // Extract faculty ID and team group number
        $faculty_id = $teamMember->faculty_id;
        $group = $teamMember->simulation_team_id;

        for ($quarter_id = 0; $quarter_id <= 3; $quarter_id++) {

            // Check if snapshot already exists
            $alreadyExists = FinancialQuarter::where([
                'simulation_id' => $simulation_id,
                'faculty_id' => $faculty_id,
                'group_number' => $group,
                'quarter_id' => $quarter_id,
            ])->exists();

            if ($alreadyExists) {
                continue; // Skip if already stored
            }

            try {
                $this->balanceSheetService->storeFinancialSnapshot($simulation_id, $faculty_id, $group, $quarter_id);
            } catch (\Exception $e) {
                Log::error("Error: Snapshot Firm {$group}, Quarter {$quarter_id}: {$e->getMessage()}");
            }
        }
    }

    /**
     * Store ending inventory for all quarter.
     */
    public function storeEndingInventory($simulation_id)
    {
        DB::beginTransaction();

        try {
            // Get the active user
            $user = $this->activeUserService->getActiveUser();

            // Retrieve associated faculty and group ID
            $teamMember = SimulationTeamMember::where([
                'simulation_id' => $simulation_id,
                'student_id' => $user->id,
            ])->first();

            if (! $teamMember) {
                throw new \Exception("Team member not found for Simulation ID: $simulation_id and Student ID: {$user->id}");
            }

            // Extract necessary details
            $faculty_id = $teamMember->faculty_id ?? null;
            $group_id = $teamMember->simulation_team_id ?? null;

            // Loop through quarters and prepare bulk insert data
            for ($quarter_id = 0; $quarter_id <= 3; $quarter_id++) {
                $this->procurementService->procurement(
                    $simulation_id,
                    $faculty_id,
                    $quarter_id,
                    $group_id,
                    true
                );
            }

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error StoreEndingInventory: '.$e->getMessage());
        }
    }

    /**
     * Store ending inventory Finish Good for all quarter.
     */
    public function storeEndingInventoryFinishGood($simulation_id)
    {
        DB::beginTransaction();

        try {
            // Get the active user
            $user = $this->activeUserService->getActiveUser();

            // Retrieve associated faculty and group ID
            $teamMember = SimulationTeamMember::where([
                'simulation_id' => $simulation_id,
                'student_id' => $user->id,
            ])->first();

            if (! $teamMember) {
                throw new \Exception("Team member not found for Simulation ID: $simulation_id and Student ID: {$user->id}");
            }

            // Extract necessary details
            $faculty_id = $teamMember->faculty_id ?? null;
            $group_id = $teamMember->simulation_team_id ?? null;

            // Loop through quarters and prepare bulk insert data
            for ($quarter_id = 0; $quarter_id <= 3; $quarter_id++) {
                $this->finishedGoodsInventoryService->finishedGoodsInventory(
                    $simulation_id,
                    $faculty_id,
                    $quarter_id,
                    $group_id,
                    true
                );
            }

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error StoreEndingInventoryFinishGood: '.$e->getMessage());
        }
    }

    /**
     * Store ending inventory Finish Good for all quarter.
     */
    public function storeScorecard($simulation_id)
    {
        DB::beginTransaction();

        try {
            // Get the active user
            $user = $this->activeUserService->getActiveUser();

            // Retrieve associated faculty and group ID
            $teamMember = SimulationTeamMember::where([
                'simulation_id' => $simulation_id,
                'student_id' => $user->id,
            ])->first();

            if (! $teamMember) {
                throw new \Exception("Team member not found for Simulation ID: $simulation_id and Student ID: {$user->id}");
            }

            // Extract necessary details
            $faculty_id = $teamMember->faculty_id ?? null;
            $group_id = $teamMember->simulation_team_id ?? null;

            // Loop through quarters and prepare bulk insert data
            for ($quarter_id = 0; $quarter_id <= 3; $quarter_id++) {

                GenerateScorecardJob::dispatch(
                    $simulation_id,
                    $faculty_id,
                    $group_id,
                    $quarter_id
                )->onQueue('scorecards');
            }

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error Saveing Scorecard: '.$e->getMessage());
        }
    }

    /**
     * Export Financial Reports for all quarter.
     */
    public function exportFinancialReports($simulation_id)
    {
        DB::beginTransaction();

        try {
            // Get the active user
            $user = $this->activeUserService->getActiveUser();

            // Retrieve associated faculty and group ID
            $teamMember = SimulationTeamMember::where([
                'simulation_id' => $simulation_id,
                'student_id' => $user->id,
            ])->first();

            if (! $teamMember) {
                throw new \Exception("Team member not found for Simulation ID: $simulation_id and Student ID: {$user->id}");
            }

            // Extract necessary details
            $faculty_id = $teamMember->faculty_id ?? null;
            $group_id = $teamMember->simulation_team_id ?? null;

            // Loop through quarters and prepare bulk insert data
            for ($quarter_id = 0; $quarter_id <= 3; $quarter_id++) {

                ExportSimulationReportsJob::dispatch(
                    $simulation_id,
                    $faculty_id,
                    $group_id,
                    $quarter_id
                )->onQueue('financial-reports');
            }

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error Saveing export financial reports: '.$e->getMessage());
        }
    }

    /**
     * Sending AI analysis requests.
     */
    public function analysisReport(int $simulation_id): void
    {
        DB::beginTransaction();

        try {
            // Get the active user
            $user = $this->activeUserService->getActiveUser();

            // Retrieve associated faculty and group ID
            $teamMember = SimulationTeamMember::where([
                'simulation_id' => $simulation_id,
                'student_id' => $user->id,
            ])->first();

            if (! $teamMember) {
                throw new \Exception("Team member not found for Simulation ID: $simulation_id and Student ID: {$user->id}");
            }

            // Extract IDs
            $faculty_id = $teamMember->faculty_id;
            $group_id = $teamMember->simulation_team_id;

            // Detect protocol (works for both web & CLI)
            $protocol = app()->environment('production') ? 'https' : 'http';
            $host = config('services.analysis.host');

            // Loop through only Q1–Q3 (skip Q0 since it’s startup)
            for ($quarter_id = 1; $quarter_id <= 3; $quarter_id++) {
                $url = sprintf(
                    '%s://%s/api/overall_insight_for_quarter/?simulation_id=%d&faculty_id=%d&group_id=%d&quarter=%d',
                    $protocol,
                    $host,
                    $simulation_id,
                    $faculty_id,
                    $group_id,
                    $quarter_id
                );

                SendAiAnalysisReportJob::dispatch($url)
                    ->onQueue('ai-report-analysis');
            }

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error("Error sending AI analysis requests: {$e->getMessage()}");
        }
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
