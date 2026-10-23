<?php

namespace App\Http\Controllers;

use App\Models\Carrier;
use App\Models\CarrierCrossDocking;
use App\Models\Decisions\DemandPlanning;
use App\Models\Decisions\Distribution;
use App\Models\Decisions\Forecasting;
use App\Models\Decisions\InformationTechnology;
use App\Models\Decisions\Manufacturing;
use App\Models\Decisions\PostpondProduction;
use App\Models\Decisions\Procurement;
use App\Models\Decisions\Scorecard;
use App\Models\Decisions\ServiceManagement;
use App\Models\Decisions\Transportation;
use App\Models\Decisions\TransportationInfo;
use App\Models\DemandChannel;
use App\Models\Product;
use App\Models\ProductMaterial;
use App\Models\RawMaterial;
use App\Models\Region;
use App\Models\ServiceOutsourcing;
use App\Models\Simulation;
use App\Models\SimulationQuarter;
use App\Models\SimulationQuarterCustomize;
use App\Models\SimulationTeamMember;
use App\Models\SubAssemblyComponent;
use App\Models\Supplier;
use App\Models\SupplyChannel;
use App\Services\ActiveUserService;
use App\Services\QualityAnalysis\RegionalSummaryService;
use App\Services\Reports\FlexScorecardService;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class StudentDecisionController extends Controller
{
    /**
     * Call Active User Service.
     */
    protected $activeUserService;

    protected $decisionCompletedController;

    protected $flexScorecardService;

    public function __construct(
        ActiveUserService $activeUserService,
        StudentDecisionCompletedController $decisionCompletedController,
        FlexScorecardService $flexScorecardService
    ) {
        $this->activeUserService = $activeUserService;
        $this->decisionCompletedController = $decisionCompletedController;
        $this->flexScorecardService = $flexScorecardService;
        // $this->middleware('syncDecision');
    }

    /**
     * DEFINE COMMON HEADER FOR ALL DECISIONS SCREENS LIKE: FORECASTING, PROCUREMENT
     */
    private function commonSimulationHeader($simulation_id, $quarter_id, $decisions_title)
    {
        // CHECK IF SIMULATION EXISTS.
        $simulation = Simulation::findOrFail($simulation_id);

        // Get the currently active user (student)
        $user = $this->activeUserService->getActiveUser();

        // Retrieve the faculty ID and group number for the active student in the given simulation
        $teamMember = SimulationTeamMember::where([
            'simulation_id' => $simulation_id,
            'student_id' => $user->id,
        ])->first(['faculty_id', 'simulation_team_id', 'payment_status']);

        // Extract faculty ID and group number, handling potential null values
        $faculty_id = $teamMember->faculty_id ?? null;

        // CHECK IF QUARTER EXISTS UNDER THIS SIMULATION.
        SimulationQuarter::where('simulation_id', $simulation_id)
            ->where('quarter_name', $quarter_id)
            ->firstOrFail();

        // 5. Determine which quarters to use: customized or default
        $customQuarters = $simulation->simulationQuarterCustomize->where('faculty_id', $faculty_id);
        $quarters = $customQuarters->isNotEmpty() ? $customQuarters : $simulation->simulationQuarters;

        $simulation->simulationQuarters = $quarters;
        $simulation->quarter_selected = $quarter_id;

        $quarterInProcess = $quarters->first(function ($quarter) {

            return $quarter->is_schedule === 'Yes'
                && is_null($quarter->close_manualy)
                && Carbon::parse($quarter->start_date)->startOfDay()->lte(now())
                && Carbon::parse($quarter->end_date)->endOfDay()->gte(now());
        });

        if ($quarterInProcess) {
            $simulation->simulationStatus = 'active';
        } else {
            $simulation->simulationStatus = 'completed';
            // fallback to the last quarter in the list (assuming it's the most recent)
            $quarterInProcess = $quarters->last();
        }

        // Set quarter_inprocess and calculate days remaining
        $simulation->quarter_inprocess = $quarterInProcess;
        $simulation->quarter_inprocess->days_remaining = (int) Carbon::parse($quarterInProcess->end_date)
            ->diffInDays(now()->format('m/d/Y'), true);

        // 7. Find selected quarter info (from original SimulationQuarter model for safety)
        $simulation->quarter_selected_info = SimulationQuarter::where('simulation_id', $simulation_id)
            ->where('quarter_name', $quarter_id)
            ->where('is_schedule', 'Yes')
            ->first();

        if ($simulation->quarter_selected_info) {
            $simulation->quarter_selected_info->days_remaining = (int) Carbon::parse($simulation->quarter_selected_info->end_date)
                ->diffInDays(now()->format('m/d/Y'), true);
        }

        // 8. Set decision title
        $simulation->decisions_title = $decisions_title;
        $simulation->payment_status = $teamMember->payment_status;
        $simulation->student_team_number = $teamMember->simulation_team_id;

        $decisionsJson = $quarters->where('quarter_name', $quarter_id)->pluck('decisions')->first();
        $simulation->decisionEnable = $decisionsJson ? json_decode($decisionsJson, true) : [];

        return $simulation;
    }

    /**
     * Load Template
     */
    private function loadTemplate($commonHeader, $quarterCompleted, $quarterUpcoming, $decisionTemplate)
    {
        $data = [];

        if ($commonHeader->quarter_selected > $commonHeader->quarter_inprocess->quarter_name) {
            $data['quarterUpcoming'] = $quarterUpcoming;
            $view = 'upcoming-quarter.index';
        } else {
            $view = $decisionTemplate;
        }

        return ['view' => $view, 'data' => $data];
    }

    /**
     * SIMULATION DASHBOARD
     */
    public function simulationDashboard(Request $request)
    {
        try {
            // Retrieve Common Simulation Header
            $commonHeader = $this->commonSimulationHeader(
                $request->simulation_id,
                $request->quarter_id,
                'Dashboard'
            );

            // Additional data for upcoming or completed quarter
            $quarterCompleted = 'Completed Quarter Data Here';
            $quarterUpcoming = 'Completed Upcoming Data Here';

            // Load respective template based on the quarter status
            $loadTemplate = $this->loadTemplate(
                $commonHeader,
                $quarterCompleted,
                $quarterUpcoming,
                'index',
            );

            // Get the currently active user (student)
            $user = $this->activeUserService->getActiveUser();

            // Retrieve the faculty ID and group number for the active student in the given simulation
            $teamMember = SimulationTeamMember::where([
                'simulation_id' => $request->simulation_id,
                'student_id' => $user->id,
            ])->first(['faculty_id', 'simulation_team_id']);

            // Extract faculty ID and group number, handling potential null values
            $faculty_id = $teamMember->faculty_id ?? null;
            $group_id = $teamMember->simulation_team_id ?? null;

            // Get the quarter ID from the request
            $quarter_id = $request->quarter_id;
            $simulation_id = $request->simulation_id;

            // Full Scorecard for all groups
            $rows = Scorecard::select(
                'category',
                'sub_measure',
                'your_firm',
                'industry_worst',
                'industry_avg',
                'industry_best',
                'unit'
            )
                ->where([
                    'simulation_id' => $simulation_id,
                    'faculty_id' => $faculty_id,
                    'group_id' => $group_id,
                    'quarter' => $quarter_id,
                ])->get();

            $quarter_id = max(1, (int) $request->quarter_id);

            $overallProgress = Scorecard::where([
                'simulation_id' => $simulation_id,
                'faculty_id' => $faculty_id,
                'group_id' => $group_id,
                'category' => 'summary',
            ])
                ->whereBetween('quarter', [0, $quarter_id])
                ->orderBy('quarter', 'asc')
                ->get();

            // Map for "your firm" (value + unit)
            $scorecard = $rows->groupBy('category')->map(function ($items) {
                return $items->mapWithKeys(function ($r) {
                    return [$r->sub_measure => [
                        'value' => $r->your_firm,
                        'unit' => $r->unit,
                    ]];
                })->toArray();
            })->toArray();

            if (empty($scorecard)) {
                return redirect()->back()->with('error', 'Your scorecard data could not be found.');
            }

            // Dynamic Benchmarks
            $benchmarks = $rows->groupBy('category')->map(function ($items) {
                return $items->mapWithKeys(function ($r) {
                    return [$r->sub_measure => [
                        'worst' => $r->industry_worst,
                        'average' => $r->industry_avg,
                        'industry_best' => $r->industry_best,
                        'unit' => $r->unit,
                    ]];
                })->toArray();
            })->toArray();

            // Merge Data for View
            $data = array_merge([
                'commonHeader' => $commonHeader,
                'scorecard' => $scorecard,
                'benchmarks' => $benchmarks,
                'overallProgress' => $overallProgress,
            ], $loadTemplate['data']);

            $quarterSelected = $commonHeader->quarter_selected;
            $quarterInProcess = $commonHeader->quarter_inprocess->quarter_name;

            $viewPath = $quarterSelected <= $quarterInProcess
                ? 'student.simulation.decisions.dashboard.index'
                : 'student.simulation.decisions.dashboard.upcoming';

            return view($viewPath, $data);
        } catch (ModelNotFoundException $e) {
            return redirect()->back()->with('error', 'Simulation or team member record not found.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'An unexpected error occurred. Please try again later.');
        }
    }

    /**
     * SIMULATION KEY PERFORMANCE INDICATORS
     */
    public function dashboardKPIs(Request $request)
    {
        try {
            // Retrieve Common Simulation Header
            $commonHeader = $this->commonSimulationHeader(
                $request->simulation_id,
                $request->quarter_id,
                'Key Performance Indicators'
            );

            // Additional data for upcoming or completed quarter
            $quarterCompleted = 'Completed Quarter Data Here';
            $quarterUpcoming = 'Completed Upcoming Data Here';

            // Load respective template based on the quarter status
            $loadTemplate = $this->loadTemplate(
                $commonHeader,
                $quarterCompleted,
                $quarterUpcoming,
                'index',
            );

            // Get the currently active user (student)
            $user = $this->activeUserService->getActiveUser();

            // Retrieve the faculty ID and group number for the active student in the given simulation
            $teamMember = SimulationTeamMember::where([
                'simulation_id' => $request->simulation_id,
                'student_id' => $user->id,
            ])->first(['faculty_id', 'simulation_team_id']);

            // Extract faculty ID and group number, handling potential null values
            $faculty_id = $teamMember->faculty_id ?? null;
            $group_id = $teamMember->simulation_team_id ?? null;

            // Get the quarter ID from the request
            $quarter_id = $request->quarter_id;
            $simulation_id = $request->simulation_id;

            // Full Scorecard for all groups
            $scSvc = app(FlexScorecardService::class);
            $scorecard = $scSvc->generateScorecard($simulation_id, $faculty_id, $group_id, $quarter_id);
            $benchmarks = $scSvc->generateBenchmarks($simulation_id, $faculty_id, $quarter_id);

            // Merge Data for View
            $data = array_merge([
                'commonHeader' => $commonHeader,
                'scorecard' => $scorecard,
                'benchmarks' => $benchmarks,
            ], $loadTemplate['data']);

            $quarterSelected = $commonHeader->quarter_selected;
            $quarterInProcess = $commonHeader->quarter_inprocess->quarter_name;

            $viewPath = $quarterSelected <= $quarterInProcess
                ? 'student.simulation.decisions.dashboard.kpis'
                : 'student.simulation.decisions.dashboard.upcoming';

            return view($viewPath, $data);
        } catch (ModelNotFoundException $e) {
            return redirect()->back()->with('error', 'Simulation or team member record not found.');
        } catch (\Exception $e) {
            report($e);

            return redirect()->back()->with('error', 'An unexpected error occurred. Please try again later.');
        }
    }

    /**
     * PERFORMANCE TRENDS
     */
    public function performanceTrends(Request $request)
    {
        try {
            // Retrieve Common Simulation Header
            $commonHeader = $this->commonSimulationHeader(
                $request->simulation_id,
                $request->quarter_id,
                'Performance Trends'
            );

            // Additional data for upcoming or completed quarter
            $quarterCompleted = 'Completed Quarter Data Here';
            $quarterUpcoming = 'Completed Upcoming Data Here';

            // Load respective template based on the quarter status
            $loadTemplate = $this->loadTemplate(
                $commonHeader,
                $quarterCompleted,
                $quarterUpcoming,
                'performance-trends',
            );

            // Get the currently active user (student)
            $user = $this->activeUserService->getActiveUser();

            // Retrieve the faculty ID and group number for the active student in the given simulation
            $teamMember = SimulationTeamMember::where([
                'simulation_id' => $request->simulation_id,
                'student_id' => $user->id,
            ])->first(['faculty_id', 'simulation_team_id']);

            // Extract faculty ID and group number, handling potential null values
            $group_id = $teamMember->simulation_team_id ?? null;
            $quarter_id = $request->quarter_id;
            $simulation_id = $request->simulation_id;

            // Detect protocol (http or https) based on request
            $protocol = app()->environment('production') ? 'https' : 'http';

            // Build Performance Trend image URL
            $performanceTrend = sprintf(
                '%s://%s/api/multi-panel-dashboard?simulation_id=%d&group_id=%d&end_quarter=%d',
                $protocol,
                config('services.analysis.host'),
                $simulation_id,
                $group_id,
                $quarter_id
            );

            // Merge Data for View
            $data = array_merge([
                'commonHeader' => $commonHeader,
                'performanceTrend' => $performanceTrend,
            ], $loadTemplate['data']);

            $quarterSelected = $commonHeader->quarter_selected;
            $quarterInProcess = $commonHeader->quarter_inprocess->quarter_name;

            $viewPath = $quarterSelected <= $quarterInProcess
                ? 'student.simulation.decisions.dashboard.performance-trends'
                : 'student.simulation.decisions.dashboard.upcoming';

            return view($viewPath, $data);
        } catch (ModelNotFoundException $e) {
            return redirect()->back()->with('error', 'Simulation or team member record not found.');
        } catch (\Exception $e) {
            Log::error('Performance Trends Error: '.$e->getMessage(), ['trace' => $e->getTraceAsString()]);

            return redirect()->back()->with('error', 'An unexpected error occurred. Please try again later.');
        }
    }

    /**
     * FORECASTING
     */
    public function forecasting(Request $request)
    {
        try {
            // Retrieve Common Simulation Header
            $commonHeader = $this->commonSimulationHeader(
                $request->simulation_id,
                $request->quarter_id,
                'Forecasting'
            );

            // Additional data for upcoming or completed quarter
            $quarterCompleted = 'Completed Quarter Data Here';
            $quarterUpcoming = 'Completed Upcoming Data Here';

            // Load respective template based on the quarter status
            $loadTemplate = $this->loadTemplate(
                $commonHeader,
                $quarterCompleted,
                $quarterUpcoming,
                'forecasting',
            );

            // Get the currently active user (student)
            $user = $this->activeUserService->getActiveUser();

            // Retrieve the faculty ID and group number for the active student in the given simulation
            $teamMember = SimulationTeamMember::where([
                'simulation_id' => $request->simulation_id,
                'student_id' => $user->id,
            ])->first(['faculty_id', 'simulation_team_id']);

            // Extract faculty ID and group number, handling potential null values
            $faculty_id = $teamMember->faculty_id ?? null;
            $group_number = $teamMember->simulation_team_id ?? null;

            // Get the quarter ID from the request
            $quarter_id = $request->quarter_id;

            // STEP 02: CHECK IF THE FORECAST DEMAND ARE CREATED FOR THIS QUARTER BY ANY STUDENT OR NOT.
            $forecastingRecord = Forecasting::where([
                'simulation_id' => $request->simulation_id,
                'faculty_id' => $faculty_id,
                'quarter' => $quarter_id,
                'group_number' => $group_number,
            ])->exists();

            // STEP 03: REPRESENTATION OF THE STRUCTURE.
            // >> Regions: A → B → C
            // >> >> >>Each Region Has Products:
            $regions = Region::get()->map(function ($region) use ($request, $faculty_id, $group_number, $quarter_id) {
                $region->products = Product::get()->map(function ($product) use ($region, $request, $faculty_id, $group_number, $quarter_id) {
                    $product->demands = DemandChannel::get()->map(function ($demand) use ($region, $product, $request, $faculty_id, $group_number, $quarter_id) {

                        $column = ($demand->id == 1) ? 'demand_channel_01' : 'demand_channel_02';

                        $demand->last_quarter_demand = Forecasting::where([
                            'simulation_id' => $request->simulation_id,
                            'faculty_id' => $faculty_id,
                            'quarter' => $quarter_id - 1,
                            'group_number' => $group_number,
                            'region_id' => $region->id,
                            'product_id' => $product->id,
                        ])->pluck($column)->first();

                        $demand->demand_saved = Forecasting::where([
                            'simulation_id' => $request->simulation_id,
                            'faculty_id' => $faculty_id,
                            'quarter' => $quarter_id,
                            'group_number' => $group_number,
                            'region_id' => $region->id,
                            'product_id' => $product->id,
                        ])->pluck($column)->first();

                        return $demand;
                    });

                    return $product;
                });

                return $region;
            });

            // << ADDITIONAL DATA TO BE SHOW << //

            // Fetch the latest updated record
            $lastSavedBy = Forecasting::with('savedByStudent.profile')->where([
                'simulation_id' => $request->simulation_id,
                'faculty_id' => $faculty_id,
                'quarter' => $quarter_id,
                'group_number' => $group_number,
            ])->latest('updated_at')->first();

            // Quarter Closure Info
            $quarterClosureInfo = $this->decisionCompletedController->forcastingClosure(
                $request->simulation_id,
                $faculty_id,
                $group_number,
                $quarter_id
            );

            // Merge Data for View
            $data = array_merge([
                'commonHeader' => $commonHeader,
                'regions' => $regions,
                'lastSavedBy' => $lastSavedBy,
                'quarterClosureInfo' => $quarterClosureInfo,
            ], $loadTemplate['data']);

            $quarterSelected = $commonHeader->quarter_selected;
            $quarterInProcess = $commonHeader->quarter_inprocess->quarter_name;
            $basePath = 'student.simulation.';
            if ($commonHeader->simulationStatus === 'completed' || $quarterSelected < $quarterInProcess) {
                $viewPath = $basePath."decisions-completed.{$loadTemplate['view']}";
            } else {
                $viewPath = $basePath."decisions.{$loadTemplate['view']}";
            }

            return view($viewPath, $data);
        } catch (\Exception $e) {
            Log::error('Error: '.$e->getMessage());

            return redirect()->back()->with('error', 'An error occurred while processing the data.');
        }
    }

    /**
     * PROCUREMENT
     */
    public function procurement(Request $request)
    {
        try {
            // Retrieve Common Simulation Header
            $commonHeader = $this->commonSimulationHeader(
                $request->simulation_id,
                $request->quarter_id,
                'Procurement'
            );

            // Additional data for upcoming or completed quarter
            $quarterCompleted = 'Completed Quarter Data Here';
            $quarterUpcoming = 'Completed Upcoming Data Here';

            // Load respective template based on the quarter status
            $loadTemplate = $this->loadTemplate(
                $commonHeader,
                $quarterCompleted,
                $quarterUpcoming,
                'procurement',
            );

            // Get the currently active user (student)
            $user = $this->activeUserService->getActiveUser();

            // Retrieve the faculty ID and group number for the active student in the given simulation
            $teamMember = SimulationTeamMember::where([
                'simulation_id' => $request->simulation_id,
                'student_id' => $user->id,
            ])->first(['faculty_id', 'simulation_team_id']);

            // Extract faculty ID and group number, handling potential null values
            $faculty_id = $teamMember->faculty_id ?? null;
            $group_number = $teamMember->simulation_team_id ?? null;

            // Get the quarter ID from the request
            $quarter_id = $request->quarter_id;

            // Get the region ID from the request
            $region_id = $request->region_id ?? 1;

            // Get Total Forecast Demand (aggregate across products & channels)
            $totalForcastDemand = Forecasting::where([
                'simulation_id' => $request->simulation_id,
                'faculty_id' => $faculty_id,
                'group_number' => $group_number,
                'quarter' => $quarter_id,
            ])->selectRaw('SUM(demand_channel_01 + demand_channel_02) as total_demand')->first()->total_demand ?? 0;

            // Fetch Products with Industry and their Material Types
            $products = Product::with('industry')->get();

            $products->map(function ($product) use ($request, $faculty_id, $group_number, $quarter_id) {
                // Fetch Material Types
                $product->materialtypes = ProductMaterial::select('type')
                    ->where('product_id', $product->id)
                    ->distinct()
                    ->get();

                // Fetch Materials for each Type
                $product->materialtypes->map(function ($materialtype) use ($product) {
                    $materialtype->collections = ProductMaterial::where([
                        'product_id' => $product->id,
                        'type' => $materialtype->type,
                    ])->get();

                    $materialtype->collections->map(function ($collection) use ($materialtype) {
                        $collection->component = $materialtype->type === 'raw_material'
                            ? RawMaterial::find($collection->material_id)
                            : SubAssemblyComponent::find($collection->material_id);

                        return $collection;
                    });

                    return $materialtype;
                });

                // Get Forecast Demand for the Product (single product-level sum)
                $product->forcastDemand = Forecasting::where([
                    'simulation_id' => $request->simulation_id,
                    'faculty_id' => $faculty_id,
                    'group_number' => $group_number,
                    'quarter' => $quarter_id,
                    'product_id' => $product->id,
                ])->selectRaw('SUM(demand_channel_01 + demand_channel_02) as total_demand')->first()->total_demand ?? 0;

                return $product;
            });

            //
            // ----------------------------
            // Corrected totalMaterial logic
            // ----------------------------
            //

            // 1) Precompute product-level forecasts (single grouped query)
            $productForecasts = Forecasting::where([
                'simulation_id' => $request->simulation_id,
                'faculty_id' => $faculty_id,
                'group_number' => $group_number,
                'quarter' => $quarter_id,
            ])
                ->select('product_id', DB::raw('SUM(demand_channel_01 + demand_channel_02) as total_demand'))
                ->groupBy('product_id')
                ->pluck('total_demand', 'product_id'); // [ product_id => total_demand ]

            // 2) Build $totalMaterial using per-product forecasts (correct approach)
            $totalMaterial = [];
            $materials = ProductMaterial::select('type')->distinct()->pluck('type')->toArray();
            $productAssemble = Product::all();

            foreach ($materials as $type) {
                $totalMaterial[$type] = [];

                foreach ($productAssemble as $product) {
                    $totalDemand = $productForecasts[$product->id] ?? 0;

                    $productMaterials = ProductMaterial::with($type === 'raw_material' ? 'rawMaterial' : 'sac')
                        ->where([
                            'product_id' => $product->id,
                            'type' => $type,
                        ])
                        ->get();

                    foreach ($productMaterials as $material) {
                        $key = $material->material_id;

                        $requiredQty = $material->quantity * $totalDemand;

                        if (isset($totalMaterial[$type][$key])) {
                            $totalMaterial[$type][$key]['total_quantity'] += $requiredQty;
                        } else {
                            $totalMaterial[$type][$key] = [
                                'material_id' => $material->material_id,
                                'total_quantity' => $requiredQty,
                                'material' => $material, // keep relations for name, etc.
                            ];
                        }
                    }
                }

                // convert associative by id -> numeric array
                $totalMaterial[$type] = array_values($totalMaterial[$type]);
            }

            //
            // ----------------------------
            // Owned Distribution Centers + manageProcurement
            // ----------------------------
            //
            $ownedDcCenters = Distribution::where([
                'simulation_id' => $request->simulation_id,
                'faculty_id' => $faculty_id,
                'group_number' => $group_number,
                'quarter' => $quarter_id,
            ])->get();

            $data = ([
                'simulation_id' => $request->simulation_id,
                'faculty_id' => $faculty_id,
                'group_number' => $group_number,
                'quarter_id' => $quarter_id,
            ]);

            // Map regionProcurement using precomputed $totalMaterial
            $ownedDcCenters->map(function ($dcCenters) use ($data, $totalMaterial) {
                $region_id = $dcCenters->region_id;

                $manageProcurement = ProductMaterial::select('type')->distinct()->get();
                $manageProcurement->map(function ($procurement) use ($data, $region_id, $totalMaterial) {
                    $procurement->material = ProductMaterial::with($procurement->type === 'raw_material' ? 'rawMaterial' : 'sac.supplierComponent.supplier')
                        ->where('type', $procurement->type)
                        ->select('material_id', 'quantity')
                        ->distinct()
                        ->orderBy('material_id')
                        ->get();

                    $procurement->material->map(function ($item) use ($procurement, $data, $region_id, $totalMaterial) {
                        // Instead of using countProducts * totalForcastDemand (which overcounts),
                        // lookup aggregated total from $totalMaterial we computed above.
                        $typeKey = $procurement->type;
                        $found = null;

                        if (isset($totalMaterial[$typeKey]) && is_array($totalMaterial[$typeKey])) {
                            // find the entry with matching material_id
                            foreach ($totalMaterial[$typeKey] as $entry) {
                                if ($entry['material_id'] == $item->material_id) {
                                    $found = $entry;
                                    break;
                                }
                            }
                        }

                        // total units required across all products that use this material (correct calculation)
                        $item->demand = $found['total_quantity'] ?? 0;

                        if ($procurement->type === 'raw_material') {

                            $procurementSaved = Procurement::with('rawMaterial')
                                ->where([
                                    'simulation_id' => $data['simulation_id'],
                                    'faculty_id' => $data['faculty_id'],
                                    'group_number' => $data['group_number'],
                                    'quarter' => $data['quarter_id'],
                                    'region_id' => $region_id,
                                ])->first();

                            $savedRawMaterial = optional(optional($procurementSaved)->rawMaterial)->firstWhere('raw_material_id', $item->material_id);

                            $item->rawMaterial->quantity = $savedRawMaterial->quantity ?? null;
                        } else {
                            // sub-assembly: maintain your per-supplier / per-channel history as before
                            $item->sac->supplierComponent->map(function ($procurement) use ($data, $region_id) {
                                // fetch supply Channel
                                $procurement->supply_channels = SupplyChannel::all();
                                $procurement->supply_channels->map(function ($channel) use ($procurement, $data, $region_id) {
                                    // Fetch procurement records for current and previous quarter
                                    $currentProcurement = Procurement::where([
                                        'simulation_id' => $data['simulation_id'],
                                        'faculty_id' => $data['faculty_id'],
                                        'group_number' => $data['group_number'],
                                        'quarter' => $data['quarter_id'],
                                        'region_id' => $region_id,
                                    ])->first();

                                    $previousProcurement = Procurement::where([
                                        'simulation_id' => $data['simulation_id'],
                                        'faculty_id' => $data['faculty_id'],
                                        'group_number' => $data['group_number'],
                                        'quarter' => $data['quarter_id'] - 1,
                                        'region_id' => $region_id,
                                    ])->first();

                                    // Current Quarter
                                    $currentSubAssembly = $currentProcurement
                                        ? $currentProcurement->subAssembly()->where([
                                            'sub_assembly_component_id' => $procurement->sub_assembly_component_id,
                                            'supplier_id' => $procurement->supplier_id,
                                            'supply_channel_id' => $channel->id,
                                        ])->first(['total_shipment'])
                                        : null;

                                    // Previous Quarter
                                    $previousSubAssembly = $previousProcurement
                                        ? $previousProcurement->subAssembly()->where([
                                            'sub_assembly_component_id' => $procurement->sub_assembly_component_id,
                                            'supplier_id' => $procurement->supplier_id,
                                            'supply_channel_id' => $channel->id,
                                        ])->first(['total_shipment'])
                                        : null;

                                    $channel->procurement = $currentSubAssembly ?? null;
                                    $channel->previousProcurement = $previousSubAssembly ?? null;

                                    return $channel;
                                });

                                return $procurement;
                            });
                        }

                        return $item;
                    });

                    return $procurement;
                });

                $dcCenters->regionProcurement = $manageProcurement;

                return $dcCenters;
            });

            // Check Distribution Center if exists
            $distributionCenters = Distribution::with('region')
                ->where([
                    'simulation_id' => $request->simulation_id,
                    'faculty_id' => $faculty_id,
                    'quarter' => $quarter_id,
                    'group_number' => $group_number,
                ])->where('is_home_region', '=', 'no')->get();

            // Fetch the latest updated record
            $lastSavedBy = Procurement::with('savedByStudent.profile')->where([
                'simulation_id' => $request->simulation_id,
                'faculty_id' => $faculty_id,
                'quarter' => $quarter_id,
                'group_number' => $group_number,
            ])->latest('updated_at')->first();

            // Quarter Closure Info
            $quarterClosureInfo = $this->decisionCompletedController->procurementClosure(
                $request->simulation_id,
                $faculty_id,
                $group_number,
                $quarter_id
            );

            // Merge Data for View
            $data = array_merge([
                'commonHeader' => $commonHeader,
                'products' => $products,
                'ownedDcCenters' => $ownedDcCenters,
                'distributionCenters' => $distributionCenters,
                'totalMaterial' => $totalMaterial,
                'totalForcastDemand' => $totalForcastDemand,
                'lastSavedBy' => $lastSavedBy,
                'quarterClosureInfo' => $quarterClosureInfo,
            ], $loadTemplate['data']);

            $quarterSelected = $commonHeader->quarter_selected;
            $quarterInProcess = $commonHeader->quarter_inprocess->quarter_name;
            $basePath = 'student.simulation.';
            if ($commonHeader->simulationStatus === 'completed' || $quarterSelected < $quarterInProcess) {
                $viewPath = $basePath."decisions-completed.{$loadTemplate['view']}";
            } else {
                $viewPath = $basePath."decisions.{$loadTemplate['view']}";
            }

            return view($viewPath, $data);
        } catch (\Exception $e) {
            Log::error('Error: '.$e->getMessage());

            return redirect()->back()->with('error', 'An error occurred while processing the data.');
        }
    }

    /**
     * MANUFACTURING
     */
    public function manufacturing(Request $request)
    {
        try {
            // Retrieve Common Simulation Header
            $commonHeader = $this->commonSimulationHeader(
                $request->simulation_id,
                $request->quarter_id,
                'Manufacturing'
            );

            // Additional data for upcoming or completed quarter
            $quarterCompleted = 'Completed Quarter Data Here';
            $quarterUpcoming = 'Completed Upcoming Data Here';

            // Load respective template based on the quarter status
            $loadTemplate = $this->loadTemplate(
                $commonHeader,
                $quarterCompleted,
                $quarterUpcoming,
                'manufacturing'
            );

            // Get the currently active user (student)
            $user = $this->activeUserService->getActiveUser();

            // Retrieve the faculty ID and group number for the active student in the given simulation
            $teamMember = SimulationTeamMember::where([
                'simulation_id' => $request->simulation_id,
                'student_id' => $user->id,
            ])->first(['faculty_id', 'simulation_team_id']);

            // Extract faculty ID and group number, handling potential null values
            $faculty_id = $teamMember->faculty_id ?? null;
            $group_number = $teamMember->simulation_team_id ?? null;

            // Get the quarter ID from the request
            $quarter_id = $request->quarter_id;

            // Fetch Products with Industry and their Material Types
            $products = Product::with('industry')->get();

            $products->map(function ($product) use ($request, $faculty_id, $group_number, $quarter_id) {
                // Get Forecast Demand for the Product
                $product->forcastDemand = Forecasting::where([
                    'simulation_id' => $request->simulation_id,
                    'faculty_id' => $faculty_id,
                    'group_number' => $group_number,
                    'quarter' => $quarter_id,
                    'product_id' => $product->id,
                ])->selectRaw('SUM(demand_channel_01 + demand_channel_02) as total_demand')->first()->total_demand ?? 0;
                // Get Submitted Record If any:

                $product->lastQuartersaved = Manufacturing::where([
                    'simulation_id' => $request->simulation_id,
                    'faculty_id' => $faculty_id,
                    'group_number' => $group_number,
                    'quarter' => $quarter_id - 1,
                    'product_id' => $product->id,
                ])->first(['production_quantity', 'emergency_production', 'adjustable_production']);

                $product->manufacturingRecord = Manufacturing::where([
                    'simulation_id' => $request->simulation_id,
                    'faculty_id' => $faculty_id,
                    'group_number' => $group_number,
                    'quarter' => $quarter_id,
                    'product_id' => $product->id,
                ])->first(['production_quantity', 'emergency_production', 'adjustable_production']);

                return $product;
            });

            // Get submitted record for porduct zero.
            $product_zero_manufacturing = PostpondProduction::where([
                'simulation_id' => $request->simulation_id,
                'faculty_id' => $faculty_id,
                'group_number' => $group_number,
                'quarter' => $quarter_id,
                'product_id' => 0, // Product Zero
            ])->first(['quantity']);

            $pzm_last_qtr = PostpondProduction::where([
                'simulation_id' => $request->simulation_id,
                'faculty_id' => $faculty_id,
                'group_number' => $group_number,
                'quarter' => $quarter_id - 1,
                'product_id' => 0, // Product Zero
            ])->first(['quantity']);

            // Fetch the latest updated record
            $lastSavedBy = Manufacturing::with('savedByStudent.profile')->where([
                'simulation_id' => $request->simulation_id,
                'faculty_id' => $faculty_id,
                'quarter' => $quarter_id,
                'group_number' => $group_number,
            ])->latest('updated_at')->first();

            // Quarter Closure Info
            $quarterClosureInfo = $this->decisionCompletedController->manufacturingClosure(
                $request->simulation_id,
                $faculty_id,
                $group_number,
                $quarter_id
            );

            // Merge Data for View
            $data = array_merge([
                'commonHeader' => $commonHeader,
                'products' => $products,
                'lastSavedBy' => $lastSavedBy,
                'product_zero_manufacturing' => $product_zero_manufacturing,
                'pzm_last_qtr' => $pzm_last_qtr,
                'quarterClosureInfo' => $quarterClosureInfo,
            ], $loadTemplate['data']);

            $quarterSelected = $commonHeader->quarter_selected;
            $quarterInProcess = $commonHeader->quarter_inprocess->quarter_name;
            $basePath = 'student.simulation.';
            if ($commonHeader->simulationStatus === 'completed' || $quarterSelected < $quarterInProcess) {
                $viewPath = $basePath."decisions-completed.{$loadTemplate['view']}";
            } else {
                $viewPath = $basePath."decisions.{$loadTemplate['view']}";
            }

            return view($viewPath, $data);
        } catch (\Exception $e) {
            Log::error('Error: '.$e->getMessage());

            return redirect()->back()->with('error', 'An error occurred while processing the data.');
        }
    }

    /**
     * DISTRIBUTION
     */
    public function distribution(Request $request)
    {
        // if ($block = $this->restrictPageByQuarter($request, 6, 'Distribution')) {
        //     return $block;
        // }

        try {
            // Retrieve common simulation header
            $commonHeader = $this->commonSimulationHeader(
                $request->simulation_id,
                $request->quarter_id,
                'Distribution'
            );

            // Additional data for upcoming or completed quarter
            $quarterCompleted = 'Completed Quarter Data Here';
            $quarterUpcoming = 'Completed Upcoming Data Here';

            // Load respective template based on the quarter status
            $loadTemplate = $this->loadTemplate(
                $commonHeader,
                $quarterCompleted,
                $quarterUpcoming,
                'distribution',
            );

            // Get the currently active user (student)
            $user = $this->activeUserService->getActiveUser();

            // Retrieve the faculty ID and group number for the active student in the given simulation
            $teamMember = SimulationTeamMember::where([
                'simulation_id' => $request->simulation_id,
                'student_id' => $user->id,
            ])->first(['faculty_id', 'simulation_team_id']);

            // Extract faculty ID and group number, handling potential null values
            $faculty_id = $teamMember->faculty_id ?? null;
            $group_number = $teamMember->simulation_team_id ?? null;

            // Get the quarter ID from the request
            $quarter_id = $request->quarter_id;

            // Fetch the latest updated record
            $lastSavedBy = Distribution::with('savedByStudent.profile')->where([
                'simulation_id' => $request->simulation_id,
                'faculty_id' => $faculty_id,
                'quarter' => $quarter_id,
                'group_number' => $group_number,
            ])->latest('updated_at')->first();

            // Quarter Closure Info
            $quarterClosureInfo = $this->decisionCompletedController->distributionClosure(
                $request->simulation_id,
                $faculty_id,
                $group_number,
                $quarter_id
            );

            // Fetch current quarter Distribution Center data
            $currentDistributionCenters = Distribution::with('emergencyCarrier')
                ->where([
                    'simulation_id' => $request->simulation_id,
                    'faculty_id' => $faculty_id,
                    'quarter' => $quarter_id,
                    'group_number' => $group_number,
                ])->get();

            // Fetch previous quarter Distribution Center data
            $previousDistributionCenters = Distribution::with('emergencyCarrier')
                ->where([
                    'simulation_id' => $request->simulation_id,
                    'faculty_id' => $faculty_id,
                    'quarter' => $quarter_id - 1,
                    'group_number' => $group_number,
                ])->get();

            // Fetch all region with Distribution Center info if saved
            $regions = Region::all()->map(function ($region) use ($currentDistributionCenters, $previousDistributionCenters) {
                $region_id = $region->id;
                $dc_number = $region->id;

                // Current Quarter DC record
                $region->dataSaved = ($region_id == 1 && $dc_number == 1)
                    ? collect()
                    : $currentDistributionCenters->where('region_id', $region_id)
                        ->where('dc_number', $region_id)
                        ->first();

                // Previous Quarter DC record
                $region->prevDataSaved = ($region_id == 1 && $dc_number == 1)
                    ? collect()
                    : $previousDistributionCenters->where('region_id', $region_id)
                        ->where('dc_number', $region_id)
                        ->first();

                return $region;
            });

            // NonOperationalDcs
            $showDcAlert = Distribution::where('simulation_id', $request->simulation_id)
                ->where('group_number', $group_number)
                ->where('quarter', $request->quarter_id)
                ->where('region_id', '>', 1)
                ->where('dc_number', '>', 1)
                ->where('is_operational', 'no') // change to 0 if boolean
                ->exists();

            // operationalDcs
            $operationalDcs = Distribution::where('simulation_id', $request->simulation_id)
                ->where('group_number', $group_number)
                ->where('quarter', $request->quarter_id)
                ->where('region_id', '>', 1)
                ->where('dc_number', '>', 1)
                ->where('is_operational', 'yes')
                ->pluck('region_id');

            // missingTransport
            $missingTransport = $operationalDcs->contains(function ($dcId) use ($request, $group_number) {
                return ! Transportation::where([
                    'simulation_id' => $request->simulation_id,
                    'group_number' => $group_number,
                    'quarter' => $request->quarter_id,
                    'distribution_center_id' => $dcId,
                ])->exists();
            });

            // Allowed carriers (Cross Docking)
            $allowedCarrierIdsByRegion = TransportationInfo::whereHas('transportation', function ($q) use ($request, $group_number, $quarter_id) {
                $q->where('simulation_id', $request->simulation_id)
                    ->where('group_number', $group_number)
                    ->where('quarter', $quarter_id);
            })
                ->get()
                ->groupBy(function ($item) {
                    return $item->transportation->distribution_center_id; // region/DC id
                })
                ->map(function ($items) {
                    return $items->pluck('carrier_id')->unique()->toArray();
                })
                ->toArray();

            // Merge Data for View
            $data = array_merge([
                'commonHeader' => $commonHeader,
                'homeRegions' => $currentDistributionCenters->where('dc_number', 1)->first(),
                'regions' => $regions,
                'crossDocking' => CarrierCrossDocking::with('carrier')->get(),
                'carriers' => Carrier::all(),
                'allowedCarrierIdsByRegion' => $allowedCarrierIdsByRegion,
                'lastSavedBy' => $lastSavedBy,
                'showDcAlert' => $showDcAlert,
                'missingTransport' => $missingTransport,
                'quarterClosureInfo' => $quarterClosureInfo,
            ], $loadTemplate['data']);

            $quarterSelected = $commonHeader->quarter_selected;
            $quarterInProcess = $commonHeader->quarter_inprocess->quarter_name;
            $basePath = 'student.simulation.';
            if ($commonHeader->simulationStatus === 'completed' || $quarterSelected < $quarterInProcess) {
                $viewPath = $basePath."decisions-completed.{$loadTemplate['view']}";
            } else {
                $viewPath = $basePath."decisions.{$loadTemplate['view']}";
            }

            return view($viewPath, $data);
        } catch (\Exception $e) {
            report($e);
            Log::error('Error: '.$e->getMessage());

            return redirect()->back()->with('error', 'An error occurred while processing the data.');
        }
    }

    /**
     * TRANSPORTATION METHOD
     */
    public function transportation(Request $request)
    {
        try {

            /**
             * -------------------------------------------------
             * COMMON HEADER & TEMPLATE
             * -------------------------------------------------
             */
            $commonHeader = $this->commonSimulationHeader(
                $request->simulation_id,
                $request->quarter_id,
                'Transportation'
            );

            $loadTemplate = $this->loadTemplate(
                $commonHeader,
                'Completed Quarter Data Here',
                'Upcoming Quarter Data Here',
                'transportation'
            );

            /**
             * -------------------------------------------------
             * ACTIVE USER + TEAM
             * -------------------------------------------------
             */
            $user = $this->activeUserService->getActiveUser();

            $teamMember = SimulationTeamMember::where([
                'simulation_id' => $request->simulation_id,
                'student_id' => $user->id,
            ])->firstOrFail(['faculty_id', 'simulation_team_id']);

            $simulation_id = (int) $request->simulation_id;
            $faculty_id = (int) $teamMember->faculty_id;
            $group_number = (int) $teamMember->simulation_team_id;
            $quarter_id = (int) $request->quarter_id;

            /**
             * -------------------------------------------------
             * DISTRIBUTION CENTERS
             * -------------------------------------------------
             */
            $distributioncenters = Distribution::where([
                'simulation_id' => $simulation_id,
                'faculty_id' => $faculty_id,
                'group_number' => $group_number,
                'quarter' => $quarter_id,
            ])
                ->where('is_home_region', 'no')
                ->where('is_operational', 'yes')
                ->orderBy('region_id')
                ->get();

            /**
             * -------------------------------------------------
             * LOAD CARRIERS ONCE
             * -------------------------------------------------
             */
            $carriers = Carrier::with('channels.supplyChannel')->get();

            /**
             * -------------------------------------------------
             * LOOP DISTRIBUTION CENTERS
             * -------------------------------------------------
             */
            $distributioncenters->each(function ($dc) use (
                $simulation_id,
                $faculty_id,
                $group_number,
                $quarter_id,
                $carriers
            ) {

                /**
                 * ---------------------------------------------
                 * LOAD TRANSPORTATION (CURRENT + PREVIOUS)
                 * ---------------------------------------------
                 */
                $currentTransportation = Transportation::with('info')->where([
                    'simulation_id' => $simulation_id,
                    'faculty_id' => $faculty_id,
                    'group_number' => $group_number,
                    'quarter' => $quarter_id,
                    'distribution_center_id' => $dc->region_id,
                ])->first();

                $previousTransportation = Transportation::with('info')->where([
                    'simulation_id' => $simulation_id,
                    'faculty_id' => $faculty_id,
                    'group_number' => $group_number,
                    'quarter' => $quarter_id - 1,
                    'distribution_center_id' => $dc->region_id,
                ])->first();

                /**
                 * ---------------------------------------------
                 * PREVIOUS DISTRIBUTION (FOR CROSS DOCKING)
                 * ---------------------------------------------
                 */
                $previousDistribution = Distribution::where([
                    'simulation_id' => $simulation_id,
                    'faculty_id' => $faculty_id,
                    'group_number' => $group_number,
                    'quarter' => $quarter_id - 1,
                    'region_id' => $dc->region_id,
                ])->first();

                /**
                 * ---------------------------------------------
                 * CURRENT DISTRIBUTION (FOR PREFILL)
                 * ---------------------------------------------
                 */
                $currentDistribution = Distribution::where([
                    'simulation_id' => $simulation_id,
                    'faculty_id' => $faculty_id,
                    'group_number' => $group_number,
                    'quarter' => $quarter_id,
                    'region_id' => $dc->region_id,
                ])->first();

                /**
                 * ---------------------------------------------
                 * CROSS DOCKING MAPPING
                 * ---------------------------------------------
                 */
                $dc->crossDocking = collect(['K', 'L', 'M', 'N'])->map(function ($carrier) use ($previousDistribution, $currentDistribution) {

                    $prevValue = $previousDistribution
                        ? $previousDistribution->{'cross_docking_'.strtolower($carrier)}
                        : 'no';

                    $currValue = $currentDistribution
                        ? $currentDistribution->{'cross_docking_'.strtolower($carrier)}
                        : 'no';

                    return (object) [
                        'carrier' => $carrier,
                        'previous' => $prevValue === 'yes' ? 'Yes' : 'No',
                        'current' => $currValue === 'yes',
                    ];
                });

                /**
                 * ---------------------------------------------
                 * PRODUCTS + SHELL PRODUCT
                 * ---------------------------------------------
                 */
                $products = Product::with('industry')->orderBy('id')->get();

                if (! $products->contains('id', 0)) {
                    $dummy = new Product;
                    $dummy->id = 0;
                    $dummy->industry_id = 1;
                    $dummy->sort_name = '(SHELL)';
                    $dummy->name = 'PRODUCT';
                    $dummy->distribution = 60;
                    $dummy->description = null;

                    $products->push($dummy);
                }

                /**
                 * ---------------------------------------------
                 * ATTACH PRODUCTS → CARRIERS → CHANNELS
                 * ---------------------------------------------
                 */
                $dc->products = $products->map(function ($product) use (
                    $carriers,
                    $currentTransportation,
                    $previousTransportation
                ) {

                    $product->carriers = $carriers->map(function ($carrier) use ($product, $currentTransportation, $previousTransportation) {

                        // CLONE carrier so each product has its own instance
                        $carrierClone = clone $carrier;

                        $carrierClone->channels = $carrier->channels->map(function ($channel) use (
                            $product,
                            $carrier,
                            $currentTransportation,
                            $previousTransportation
                        ) {

                            $channelClone = clone $channel;

                            $current = $currentTransportation
                                ? $currentTransportation->info
                                    ->where('product_id', $product->id)
                                    ->where('carrier_id', $carrier->id)
                                    ->where('supply_channel_id', $channel->supply_channel_id)
                                    ->first()
                                : null;

                            $previous = $previousTransportation
                                ? $previousTransportation->info
                                    ->where('product_id', $product->id)
                                    ->where('carrier_id', $carrier->id)
                                    ->where('supply_channel_id', $channel->supply_channel_id)
                                    ->first()
                                : null;

                            $channelClone->quantity = $current->total_shipment ?? 0;
                            $channelClone->previous_quantity = $previous->total_shipment ?? 0;

                            return $channelClone;
                        });

                        return $carrierClone;
                    });

                    return $product;
                });
            });

            /**
             * -------------------------------------------------
             * LAST SAVED INFO
             * -------------------------------------------------
             */
            $lastSavedBy = Transportation::with('savedByStudent.profile')
                ->where([
                    'simulation_id' => $simulation_id,
                    'faculty_id' => $faculty_id,
                    'group_number' => $group_number,
                    'quarter' => $quarter_id,
                ])
                ->latest('updated_at')
                ->first();

            /**
             * -------------------------------------------------
             * QUARTER CLOSURE INFO
             * -------------------------------------------------
             */
            $quarterClosureInfo = $this->decisionCompletedController
                ->transportationClosure(
                    $simulation_id,
                    $faculty_id,
                    $group_number,
                    $quarter_id
                );

            /**
             * -------------------------------------------------
             * VIEW RESOLUTION
             * -------------------------------------------------
             */
            $data = array_merge([
                'commonHeader' => $commonHeader,
                'distributioncenters' => $distributioncenters,
                'lastSavedBy' => $lastSavedBy,
                'quarterClosureInfo' => $quarterClosureInfo,
            ], $loadTemplate['data']);

            $quarterSelected = $commonHeader->quarter_selected;
            $quarterInProcess = $commonHeader->quarter_inprocess->quarter_name;

            $basePath = 'student.simulation.';
            $viewPath = (
                $commonHeader->simulationStatus === 'completed'
                || $quarterSelected < $quarterInProcess
            )
                ? $basePath."decisions-completed.{$loadTemplate['view']}"
                : $basePath."decisions.{$loadTemplate['view']}";

            return view($viewPath, $data);
        } catch (\Throwable $e) {

            Log::error('[Transportation Load Failed]', [
                'simulation_id' => $request->simulation_id ?? null,
                'quarter_id' => $request->quarter_id ?? null,
                'error' => $e->getMessage(),
            ]);

            return redirect()->back()
                ->with('error', 'An error occurred while loading transportation data.');
        }
    }

    /**
     * SERVICE MANAGEMENT
     */
    public function serviceManagement(Request $request)
    {
        // if ($block = $this->restrictPageByQuarter($request, 7, 'Service Management')) {
        //     return $block;
        // }

        try {
            // Retrieve common simulation header
            $commonHeader = $this->commonSimulationHeader(
                $request->simulation_id,
                $request->quarter_id,
                'Service Management'
            );

            // Additional data for upcoming or completed quarter
            $quarterCompleted = 'Completed Quarter Data Here';
            $quarterUpcoming = 'Completed Upcoming Data Here';

            // Load respective template based on the quarter status
            $loadTemplate = $this->loadTemplate(
                $commonHeader,
                $quarterCompleted,
                $quarterUpcoming,
                'service-management',
            );

            // Get the currently active user (student)
            $user = $this->activeUserService->getActiveUser();

            // Retrieve the faculty ID and group number for the active student in the given simulation
            $teamMember = SimulationTeamMember::where([
                'simulation_id' => $request->simulation_id,
                'student_id' => $user->id,
            ])->first(['faculty_id', 'simulation_team_id']);

            // Extract faculty ID and group number, handling potential null values
            $faculty_id = $teamMember->faculty_id ?? null;
            $group_number = $teamMember->simulation_team_id ?? null;

            // Get the quarter ID from the request
            $quarter_id = $request->quarter_id;

            // Retrieve All regions and Service Outsourcing options
            $regions = Region::all();
            $regions->map(function ($region) use ($request, $faculty_id, $group_number, $quarter_id) {
                $region->service_outsourcing = ServiceOutsourcing::all();

                // Current Quarter
                $currentServiceRecord = ServiceManagement::where([
                    'simulation_id' => $request->simulation_id,
                    'faculty_id' => $faculty_id,
                    'group_number' => $group_number,
                    'quarter' => $quarter_id,
                    'region_id' => $region->id,
                ])->first('service_id');

                // Previous Quarter
                $previousServiceRecord = ServiceManagement::with('serviceOutsourcing')
                    ->where([
                        'simulation_id' => $request->simulation_id,
                        'faculty_id' => $faculty_id,
                        'group_number' => $group_number,
                        'quarter' => $quarter_id - 1,
                        'region_id' => $region->id,
                    ])->first();

                $region->service_assign_id =
                    $currentServiceRecord ? $currentServiceRecord->service_id : null;

                $region->previous_service =
                    $previousServiceRecord ? $previousServiceRecord->serviceOutsourcing->level_title : null;

                return $region; // Return the modified region
            });

            // Fetch the latest updated record
            $lastSavedBy = ServiceManagement::with('savedByStudent.profile')->where([
                'simulation_id' => $request->simulation_id,
                'faculty_id' => $faculty_id,
                'quarter' => $quarter_id,
                'group_number' => $group_number,
            ])->latest('updated_at')->first();

            // Quarter Closure Info
            $quarterClosureInfo = $this->decisionCompletedController->serviceManagementClosure(
                $request->simulation_id,
                $faculty_id,
                $group_number,
                $quarter_id
            );

            // Merge data for view
            $data = array_merge([
                'commonHeader' => $commonHeader,
                'regions' => $regions,
                'lastSavedBy' => $lastSavedBy,
                'quarterClosureInfo' => $quarterClosureInfo,
            ], $loadTemplate['data']);

            $quarterSelected = $commonHeader->quarter_selected;
            $quarterInProcess = $commonHeader->quarter_inprocess->quarter_name;
            $basePath = 'student.simulation.';
            if ($commonHeader->simulationStatus === 'completed' || $quarterSelected < $quarterInProcess) {
                $viewPath = $basePath."decisions-completed.{$loadTemplate['view']}";
            } else {
                $viewPath = $basePath."decisions.{$loadTemplate['view']}";
            }

            return view($viewPath, $data);
        } catch (\Exception $e) {
            Log::error('Error: '.$e->getMessage());

            return redirect()->back()->with('error', 'An error occurred while processing the data.');
        }
    }

    /**
     * DEMAND PLANNING
     */
    public function demandPlanning(Request $request)
    {
        // if ($block = $this->restrictPageByQuarter($request, 7, 'Demand Planning')) {
        //     return $block;
        // }

        try {
            // Retrieve common simulation header
            $commonHeader = $this->commonSimulationHeader(
                $request->simulation_id,
                $request->quarter_id,
                'Demand Planning'
            );

            // Additional data for upcoming or completed quarter
            $quarterCompleted = 'Completed Quarter Data Here';
            $quarterUpcoming = 'Completed Upcoming Data Here';

            // Load respective template based on the quarter status
            $loadTemplate = $this->loadTemplate(
                $commonHeader,
                $quarterCompleted,
                $quarterUpcoming,
                'demand-planning',
            );

            // Get the currently active user (student)
            $user = $this->activeUserService->getActiveUser();

            // Retrieve the faculty ID and group number for the active student in the given simulation
            $teamMember = SimulationTeamMember::where([
                'simulation_id' => $request->simulation_id,
                'student_id' => $user->id,
            ])->first(['faculty_id', 'simulation_team_id']);

            // Extract faculty ID and group number, handling potential null values
            $faculty_id = $teamMember->faculty_id ?? null;
            $group_number = $teamMember->simulation_team_id ?? null;

            // Get the quarter ID from the request
            $quarter_id = $request->quarter_id;

            // collect data to use with regions:
            $data = ([
                'simulation_id' => $request->simulation_id,
                'faculty_id' => $faculty_id,
                'group_number' => $group_number,
                'quarter' => $quarter_id,
            ]);

            // Retrieve all regions
            $regions = Region::all();
            $regions->map(function ($region) use ($data) {
                $region->products = Product::all();
                $region->products->map(function ($product) use ($region, $data) {
                    $product->demands = DemandChannel::all();
                    $product->demands->map(function ($demand) use ($product, $region, $data) {
                        // Current Quarter Demand Record
                        $currentDemandRecord = DemandPlanning::where([
                            'simulation_id' => $data['simulation_id'],
                            'faculty_id' => $data['faculty_id'],
                            'group_number' => $data['group_number'],
                            'quarter' => $data['quarter'],
                            'region_id' => $region->id,
                            'product_id' => $product->id,
                            'demand_channel_id' => $demand->id,
                        ])->first(['status', 'price', 'marketSpending']);

                        // Previous Quarter Demand Record
                        $previousDemandRecord = DemandPlanning::where([
                            'simulation_id' => $data['simulation_id'],
                            'faculty_id' => $data['faculty_id'],
                            'group_number' => $data['group_number'],
                            'quarter' => $data['quarter'] - 1,
                            'region_id' => $region->id,
                            'product_id' => $product->id,
                            'demand_channel_id' => $demand->id,
                        ])->first(['status', 'price', 'marketSpending']);

                        // Assign Current Quarter Values
                        $demand->current_status = $currentDemandRecord->status ?? null;
                        $demand->current_price = $currentDemandRecord->price ?? null;
                        $demand->current_marketSpending = $currentDemandRecord->marketSpending ?? null;

                        // Assign Previous Quarter Values
                        $demand->previous_status = $previousDemandRecord->status ?? null;
                        $demand->previous_price = $previousDemandRecord->price ?? null;
                        $demand->previous_marketSpending = $previousDemandRecord->marketSpending ?? null;

                        return $demand; // Return the modified demand
                    });

                    return $product; // Return the modified product
                });

                return $region; // Return the modified region
            });

            // Fetch the latest updated record
            $lastSavedBy = DemandPlanning::with('savedByStudent.profile')->where([
                'simulation_id' => $request->simulation_id,
                'faculty_id' => $faculty_id,
                'quarter' => $quarter_id,
                'group_number' => $group_number,
            ])->latest('updated_at')->first();

            // Quarter Closure Info
            $quarterClosureInfo = $this->decisionCompletedController->demandPlanningClosure(
                $request->simulation_id,
                $faculty_id,
                $group_number,
                $quarter_id
            );

            // Merge data for view
            $data = array_merge([
                'commonHeader' => $commonHeader,
                'regions' => $regions,
                'lastSavedBy' => $lastSavedBy,
                'quarterClosureInfo' => $quarterClosureInfo,
            ], $loadTemplate['data']);

            $quarterSelected = $commonHeader->quarter_selected;
            $quarterInProcess = $commonHeader->quarter_inprocess->quarter_name;
            $basePath = 'student.simulation.';
            if ($commonHeader->simulationStatus === 'completed' || $quarterSelected < $quarterInProcess) {
                $viewPath = $basePath."decisions-completed.{$loadTemplate['view']}";
            } else {
                $viewPath = $basePath."decisions.{$loadTemplate['view']}";
            }

            return view($viewPath, $data);
        } catch (\Exception $e) {
            Log::error('Error: '.$e->getMessage());

            return redirect()->back()->with('error', 'An error occurred while processing the data.');
        }
    }

    /**
     * INFORMATION TECHNOLOGY
     */
    public function informationTechnology(Request $request)
    {
        // if ($block = $this->restrictPageByQuarter($request, 7, 'Information & Technology')) {
        //     return $block;
        // }

        try {
            // Retrieve common simulation header
            $commonHeader = $this->commonSimulationHeader(
                $request->simulation_id,
                $request->quarter_id,
                'Information Technology'
            );

            // Additional data for upcoming or completed quarter
            $quarterCompleted = 'Completed Quarter Data Here';
            $quarterUpcoming = 'Completed Upcoming Data Here';

            // Load respective template based on the quarter status
            $loadTemplate = $this->loadTemplate(
                $commonHeader,
                $quarterCompleted,
                $quarterUpcoming,
                'information-technology'
            );

            // Get the currently active user (student)
            $user = $this->activeUserService->getActiveUser();

            // Retrieve the faculty ID and group number for the active student in the given simulation
            $teamMember = SimulationTeamMember::where([
                'simulation_id' => $request->simulation_id,
                'student_id' => $user->id,
            ])->first(['faculty_id', 'simulation_team_id']);

            // Extract faculty ID and group number, handling potential null values
            $faculty_id = $teamMember->faculty_id ?? null;
            $group_number = $teamMember->simulation_team_id ?? null;

            // Get the quarter ID from the request
            $quarter_id = $request->quarter_id;

            $distributioncenters = Distribution::where([
                'simulation_id' => $request->simulation_id,
                'faculty_id' => $faculty_id,
                'group_number' => $group_number,
                'quarter' => $quarter_id,
            ])
                ->where('is_home_region', '=', 'no')
                ->where('is_operational', '=', 'yes')
                ->orderBy('region_id')
                ->get();

            // Fetch the latest updated record
            $lastSavedBy = InformationTechnology::with('savedByStudent.profile')->where([
                'simulation_id' => $request->simulation_id,
                'faculty_id' => $faculty_id,
                'quarter' => $quarter_id,
                'group_number' => $group_number,
            ])->latest('updated_at')->first();

            // Quarter Closure Info
            $quarterClosureInfo = $this->decisionCompletedController->informationTechnologyClosure(
                $request->simulation_id,
                $faculty_id,
                $group_number,
                $quarter_id
            );

            $synchronization = InformationTechnology::with('carriers', 'suppliers')
                ->where([
                    'simulation_id' => $request->simulation_id,
                    'faculty_id' => $faculty_id,
                    'quarter' => $quarter_id,
                    'group_number' => $group_number,
                ])->first();

            $synchronizationPrevious = InformationTechnology::with('carriers', 'suppliers')
                ->where([
                    'simulation_id' => $request->simulation_id,
                    'faculty_id' => $faculty_id,
                    'quarter' => $quarter_id - 1,
                    'group_number' => $group_number,
                ])->first();

            // Merge data for view
            $data = array_merge([
                'commonHeader' => $commonHeader,
                'distributioncenters' => $distributioncenters,
                'synchronization' => $synchronization,
                'synchronizationPrevious' => $synchronizationPrevious,
                'suppliers' => Supplier::with('supplierComponent')->get(),
                'carriers' => Carrier::all(),
                'lastSavedBy' => $lastSavedBy,
                'quarterClosureInfo' => $quarterClosureInfo,
            ], $loadTemplate['data']);

            $quarterSelected = $commonHeader->quarter_selected;
            $quarterInProcess = $commonHeader->quarter_inprocess->quarter_name;
            $basePath = 'student.simulation.';
            if ($commonHeader->simulationStatus === 'completed' || $quarterSelected < $quarterInProcess) {
                $viewPath = $basePath."decisions-completed.{$loadTemplate['view']}";
            } else {
                $viewPath = $basePath."decisions.{$loadTemplate['view']}";
            }

            return view($viewPath, $data);
        } catch (\Exception $e) {
            Log::error('Error: '.$e->getMessage());

            return redirect()->back()->with('error', 'An error occurred while processing the data.');
        }
    }

    /**
     * SIMULATION PRODUCTS
     */
    public function simulationProducts(Request $request)
    {
        try {
            $user = $this->activeUserService->getActiveUser();

            // STEP 02: COLLECT PRODUCTS:
            $products = Product::with('industry')->get();
            $products->map(function ($product) {
                $product->materialtypes = ProductMaterial::select('type')->where('product_id', $product->id)->distinct()->get();
                $product->materialtypes->map(function ($materialtype) use ($product) {
                    $materialtype->collections = ProductMaterial::where(['product_id' => $product->id, 'type' => $materialtype->type])->get();
                    $materialtype->collections->map(function ($collection) use ($materialtype) {
                        if ($materialtype->type == 'raw_material') {
                            $collection->component = RawMaterial::where(['id' => $collection->material_id])->first();
                        } else {
                            $collection->component = SubAssemblyComponent::where(['id' => $collection->material_id])->first();
                        }

                        return $collection;
                    });

                    return $materialtype;
                });

                return $product;
            });

            return view('student.simulation.products', compact('products'));
        } catch (ModelNotFoundException $e) {
            return redirect()->back()->with('error', 'Simulation or team member record not found.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'An unexpected error occurred. Please try again later.');
        }
    }

    /**
     * SIMULATION FIRMS
     */
    public function simulationFirms(Request $request)
    {
        try {
            $simulation_id = $request->simulation_id;
            $activeUser = $this->activeUserService->getActiveUser();

            // ENSURE THE SIMULATION EXISTS WITH RELATED FIRMS.
            $simulation = Simulation::with('simulationFirms')->find($simulation_id);

            if (! $simulation) {
                return redirect()->back()->with('error', 'Simulation not found.');
            }

            // CHECK IF THE ACTIVE USER IS PART OF A SIMULATION TEAM.
            $teamMemberExists = SimulationTeamMember::where('student_id', $activeUser->id)
                ->where('simulation_id', $simulation_id)
                ->where('student_allowed', 'Yes')
                ->whereNotNull('student_joined')
                ->exists();

            if (! $teamMemberExists) {
                return redirect()->back()->with('error', 'You are not assigned to this simulation.');
            }

            return view('student.simulation.firms', compact('simulation'));
        } catch (ModelNotFoundException $e) {
            return redirect()->back()->with('error', 'Simulation or team member record not found.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'An unexpected error occurred. Please try again later.');
        }
    }

    /**
     * SIMULATION TEAM MEMBER
     */
    public function simulationTeamMember(Request $request)
    {
        try {
            $simulation_id = $request->simulation_id;
            $activeUser = $this->activeUserService->getActiveUser();

            // Retrieve common simulation header
            $commonHeader = $this->commonSimulationHeader(
                $request->simulation_id,
                $request->quarter_id,
                'Information Technology'
            );

            // ENSURE THE SIMULATION EXISTS
            Simulation::findOrFail($simulation_id);

            // FIND THE ACTIVE USER'S TEAM MEMBERSHIP IN THE SIMULATION
            $teamMember = SimulationTeamMember::with('student.profile')
                ->where('student_id', $activeUser->id)
                ->where('simulation_id', $simulation_id)
                ->where('student_allowed', 'Yes')
                ->whereNotNull('student_joined')
                ->firstOrFail();

            $teamMembers = SimulationTeamMember::with('student.profile')
                ->where(function ($query) use ($simulation_id, $teamMember) {
                    $query->where('simulation_id', $simulation_id)
                        ->where('simulation_team_id', $teamMember->simulation_team_id); // Fixed missing parenthesis
                })
                ->distinct() // This only works if selecting specific columns
                ->get();

            return view('student.simulation.team-member', compact('teamMembers', 'commonHeader'));
        } catch (ModelNotFoundException $e) {
            return redirect()->back()->with('error', 'Simulation or team member record not found.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'An unexpected error occurred. Please try again later.');
        }
    }

    /**
     * Analysis Summary
     */
    public function analysisSummary(Request $request)
    {
        // Retrieve common simulation header
        $commonHeader = $this->commonSimulationHeader(
            $request->simulation_id,
            $request->quarter_id,
            'Demand Analysis'
        );

        // Validate quarter
        if (
            $commonHeader->quarter_inprocess === null ||
            $commonHeader->quarter_selected > $commonHeader->quarter_inprocess->quarter_name
        ) {
            return redirect()
                ->route('student.simulation.decisions.forecasting', ['simulation_id' => $request->simulation_id, 'quarter_id' => $request->quarter_id]);
        }

        try {
            // Get the currently active user (student)
            $user = $this->activeUserService->getActiveUser();

            // Retrieve the faculty ID and group number for the active student in the given simulation
            $teamMember = SimulationTeamMember::where([
                'simulation_id' => $request->simulation_id,
                'student_id' => $user->id,
            ])->first(['faculty_id', 'simulation_team_id']);

            // Extract faculty ID and group number, handling potential null values
            $faculty_id = $teamMember->faculty_id ?? null;
            $group_number = $teamMember->simulation_team_id ?? null;

            // Get the quarter ID from the request
            $quarter_id = $request->quarter_id;

            $summary = app(RegionalSummaryService::class)->regionalSummary($request->simulation_id, $faculty_id, $quarter_id);

            return view('student.simulation.analysis-summary', compact('commonHeader', 'summary', 'group_number'));
        } catch (\Exception $e) {
            Log::error('Error: '.$e->getMessage());

            return redirect()->back()->with('error', 'An error occurred while processing the data.');
        }
    }

    /**
     * Restricts page access based on current active quarter and minimum allowed quarter.
     */
    private function restrictPageByQuarter(Request $request, int $minQuarterAllowed, string $pageName = 'this page')
    {
        $requestedQuarter = (int) $request->quarter_id;

        $currentQuarter = (int) SimulationQuarterCustomize::where('simulation_id', $request->simulation_id)
            ->where('is_schedule', 'Yes')
            ->whereNull('close_manualy')
            ->whereDate('start_date', '<=', now())
            ->whereDate('end_date', '>=', now())
            ->value('quarter_name');

        if ($requestedQuarter < $minQuarterAllowed && $requestedQuarter === $currentQuarter) {
            abort(403, 'Access denied for this Quarter.');
        }

        return null; // Access allowed
    }
}
