<?php

namespace App\Http\Controllers;

use App\Models\Decisions\ConsultingReportPreference;
use App\Models\Decisions\Procurement;
use App\Models\Region;
use App\Models\Simulation;
use App\Models\SimulationQuarter;
use App\Models\SimulationTeamMember;
use App\Models\Supplier;
use App\Services\ActiveUserService;
use App\Services\Reports\Supplementary\DecisionService;
use App\Services\Reports\Supplementary\FinishedGoodsInventoryService;
use App\Services\Reports\Supplementary\ForecastAccuracyService;
use App\Services\Reports\Supplementary\IndustryBulletinService;
use App\Services\Reports\Supplementary\ProcurementService;
use App\Services\Reports\Supplementary\ServiceManagementService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class StudentDecisionExtraReportController extends Controller
{
    // Service to retrieve data
    protected $activeUserService;

    protected $finishedGoodsInventoryService;

    protected $procurementService;

    protected $serviceManagementService;

    protected $forecastAccuracyService;

    protected $decisionService;

    protected $industryBulletinService;

    /**
     * Constructor to inject the ActiveUserService dependency.
     */
    public function __construct(
        ActiveUserService $activeUserService,
        FinishedGoodsInventoryService $finishedGoodsInventoryService,
        ProcurementService $procurementService,
        ServiceManagementService $serviceManagementService,
        ForecastAccuracyService $forecastAccuracyService,
        DecisionService $decisionService,
        IndustryBulletinService $industryBulletinService
    ) {
        $this->activeUserService = $activeUserService;
        $this->finishedGoodsInventoryService = $finishedGoodsInventoryService;
        $this->procurementService = $procurementService;
        $this->forecastAccuracyService = $forecastAccuracyService;
        $this->serviceManagementService = $serviceManagementService;
        $this->decisionService = $decisionService;
        $this->industryBulletinService = $industryBulletinService;
    }

    private function commonSimulationHeader($simulation_id, $quarter_id)
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

        $simulation->payment_status = $teamMember->payment_status;
        $simulation->student_team_number = $teamMember->simulation_team_id;

        $decisionsJson = $quarters->where('quarter_name', $quarter_id)->pluck('decisions')->first();
        $simulation->decisionEnable = $decisionsJson ? json_decode($decisionsJson, true) : [];

        return $simulation;
    }

    public function index()
    {
        return view('student.simulation.financial-reports.index');
    }

    /**
     * Display the Finished Goods extra financial report view.
     */
    public function finishedGoods(Request $request)
    {
        try {
            // Retrieve Common Simulation Header
            $commonHeader = $this->commonSimulationHeader($request->simulation_id, $request->quarter_id);

            // Extract simulation and quarter IDs from the request
            $simulation_id = $request->simulation_id;
            $quarter_id = $request->quarter_id;

            $user = $this->activeUserService->getActiveUser();

            // Get the team member's faculty and team IDs
            $teamMember = SimulationTeamMember::where([
                'simulation_id' => $simulation_id,
                'student_id' => $user->id,
            ])->first();

            $faculty_id = $teamMember->faculty_id ?? null;
            $group_id = $teamMember->simulation_team_id ?? null;

            // Retrieve finished goods inventory data for distribution centers
            $dcCenters = $this->finishedGoodsInventoryService->finishedGoodsInventory($simulation_id, $faculty_id, $quarter_id, $group_id);

            // Report Analysis
            $reportAnalysis = DB::connection('mysql_ai')->table('myapp_chatlog')
                ->where([
                    'simulation_id' => $simulation_id,
                    'faculty_id' => $faculty_id,
                    'group_number' => $group_id,
                    'quarter' => $quarter_id,
                    'report_type' => 'finished-goods',
                ])->first();

            return view('student.simulation.financial-reports.extra.finished-goods', compact('commonHeader', 'dcCenters', 'reportAnalysis'));
        } catch (\Exception $e) {
            // Log the error and redirect back with an error message
            Log::error('Error in finishedGoods: '.$e->getMessage());

            return redirect()->back()->with('error', 'An error occurred while processing the data.');
        }
    }

    /**
     * Display the Procurement extra financial report view.
     */
    public function procurement(Request $request)
    {
        try {
            // Retrieve Common Simulation Header
            $commonHeader = $this->commonSimulationHeader($request->simulation_id, $request->quarter_id);

            // Extract simulation and quarter IDs from the request
            $simulation_id = $request->simulation_id;
            $quarter_id = $request->quarter_id;

            $user = $this->activeUserService->getActiveUser();

            // Get the team member's faculty and team IDs
            $teamMember = SimulationTeamMember::where([
                'simulation_id' => $simulation_id,
                'student_id' => $user->id,
            ])->first();

            $faculty_id = $teamMember->faculty_id ?? null;
            $group_id = $teamMember->simulation_team_id ?? null;

            // Fetch procurement data (includes DC centers and inventory)
            $procurementData = $this->procurementService->procurement($simulation_id, $faculty_id, $quarter_id, $group_id);

            // Separate values for use in the view
            $dcCenters = $procurementData->dcCenters;
            $procurementInventory = $procurementData->procurementInventory;

            // Report Analysis
            $reportAnalysis = DB::connection('mysql_ai')->table('myapp_chatlog')
                ->where([
                    'simulation_id' => $simulation_id,
                    'faculty_id' => $faculty_id,
                    'group_number' => $group_id,
                    'quarter' => $quarter_id,
                    'report_type' => 'procurement',
                ])->first();

            // Return the corresponding view with the retrieved data
            return view('student.simulation.financial-reports.extra.procurement')->with([
                'commonHeader' => $commonHeader,
                'dcCenters' => $dcCenters,
                'procurementInventory' => $procurementInventory,
                'reportAnalysis' => $reportAnalysis,
            ]);
        } catch (\Exception $e) {
            // Log the error and redirect back with an error message
            Log::error('Error in procurement: '.$e->getMessage());

            return redirect()->back()->with('error', 'An error occurred while processing the data.');
        }
    }

    /**
     * Display the Forecast Accuracy extra financial report view.
     */
    public function forecastAccuracy(Request $request)
    {
        try {
            // Retrieve Common Simulation Header
            $commonHeader = $this->commonSimulationHeader($request->simulation_id, $request->quarter_id);

            // Extract simulation and quarter IDs from the request
            $simulation_id = $request->simulation_id;
            $quarter_id = $request->quarter_id;

            $user = $this->activeUserService->getActiveUser();

            // Get the team member's faculty and team IDs
            $teamMember = SimulationTeamMember::where([
                'simulation_id' => $simulation_id,
                'student_id' => $user->id,
            ])->first();

            $faculty_id = $teamMember->faculty_id ?? null;
            $group_id = $teamMember->simulation_team_id ?? null;

            // Get forecast accuracy data for each region
            $regions = $this->forecastAccuracyService->forecastAccuracy($simulation_id, $faculty_id, $quarter_id, $group_id);

            // Report Analysis
            $reportAnalysis = DB::connection('mysql_ai')->table('myapp_chatlog')
                ->where([
                    'simulation_id' => $simulation_id,
                    'faculty_id' => $faculty_id,
                    'group_number' => $group_id,
                    'quarter' => $quarter_id,
                    'report_type' => 'forecast-accuracy',
                ])->first();

            return view('student.simulation.financial-reports.extra.forecast-accuracy', compact('commonHeader', 'regions', 'reportAnalysis'));
        } catch (\Exception $e) {
            // Log the error and redirect back with an error message
            Log::error('Error in forecastAccuracy: '.$e->getMessage());

            return redirect()->back()->with('error', 'An error occurred while processing the data.');
        }
    }

    /**
     * Display the Service Operations extra financial report view.
     */
    public function serviceOps(Request $request)
    {
        try {
            // Retrieve Common Simulation Header
            $commonHeader = $this->commonSimulationHeader($request->simulation_id, $request->quarter_id);

            // Extract simulation and quarter IDs from the request
            $simulation_id = $request->simulation_id;
            $quarter_id = $request->quarter_id;

            $user = $this->activeUserService->getActiveUser();

            // Get the team member's faculty and team IDs
            $teamMember = SimulationTeamMember::where([
                'simulation_id' => $simulation_id,
                'student_id' => $user->id,
            ])->first();

            $faculty_id = $teamMember->faculty_id ?? null;
            $group_id = $teamMember->simulation_team_id ?? null;

            // Retrieve all regions from the database
            $regions = $this->serviceManagementService->serviceManagement($simulation_id, $faculty_id, $quarter_id, $group_id);

            // Report Analysis
            $reportAnalysis = DB::connection('mysql_ai')->table('myapp_chatlog')
                ->where([
                    'simulation_id' => $simulation_id,
                    'faculty_id' => $faculty_id,
                    'group_number' => $group_id,
                    'quarter' => $quarter_id,
                    'report_type' => 'service-ops',
                ])->first();

            return view('student.simulation.financial-reports.extra.service-ops', compact('commonHeader', 'regions', 'reportAnalysis'));
        } catch (\Exception $e) {
            // Log the error and redirect back with an error message
            Log::error('Error in serviceOps: '.$e->getMessage());

            return redirect()->back()->with('error', 'An error occurred while processing the data.');
        }
    }

    /**
     * Display the Decision Variables extra financial report view.
     */
    public function decisionVars(Request $request)
    {
        try {
            // Retrieve Common Simulation Header
            $commonHeader = $this->commonSimulationHeader($request->simulation_id, $request->quarter_id);

            // Extract simulation and quarter IDs from the request
            $simulation_id = $request->simulation_id;
            $quarter_id = $request->quarter_id;

            $user = $this->activeUserService->getActiveUser();

            // Get the team member's faculty and team IDs
            $teamMember = SimulationTeamMember::where([
                'simulation_id' => $simulation_id,
                'student_id' => $user->id,
            ])->first();

            $faculty_id = $teamMember->faculty_id ?? null;
            $group_id = $teamMember->simulation_team_id ?? null;

            $decisionReportData = $this->decisionService->decisionReport($simulation_id, $faculty_id, $quarter_id, $group_id);

            // Separate values for use in the view
            $procurementRawMaterial = $decisionReportData->procurementRawMaterial;
            $procurementSubAssembly = $decisionReportData->procurementSubAssembly;
            $manufacturing = $decisionReportData->manufacturing;
            $shipments = $decisionReportData->shipments;

            // Report Analysis
            $reportAnalysis = DB::connection('mysql_ai')->table('myapp_chatlog')
                ->where([
                    'simulation_id' => $simulation_id,
                    'faculty_id' => $faculty_id,
                    'group_number' => $group_id,
                    'quarter' => $quarter_id,
                    'report_type' => 'decision-vars',
                ])->first();

            return view('student.simulation.financial-reports.extra.decision-vars', compact('commonHeader'))->with([

                'suppliers' => Supplier::all(),
                'procurementRawMaterial' => $procurementRawMaterial,
                'procurementSubAssembly' => $procurementSubAssembly,
                'manufacturing' => $manufacturing,
                'shipments' => $shipments,
                'reportAnalysis' => $reportAnalysis,
            ]);
        } catch (\Exception $e) {
            // Log the error and redirect back with an error message
            Log::error('Error in decisionVars: '.$e->getMessage());

            return redirect()->back()->with('error', 'An error occurred while processing the data.');
        }
    }

    /**
     * Display the Industry Bulletin extra financial report view.
     */
    public function industryBulletin(Request $request)
    {
        try {
            // Retrieve Common Simulation Header
            $commonHeader = $this->commonSimulationHeader($request->simulation_id, $request->quarter_id);

            // Extract simulation and quarter IDs from the request
            $simulation_id = $request->simulation_id;
            $quarter_id = $request->quarter_id;

            $user = $this->activeUserService->getActiveUser();

            // Get the team member's faculty and team IDs
            $teamMember = SimulationTeamMember::where([
                'simulation_id' => $simulation_id,
                'student_id' => $user->id,
            ])->first();

            $faculty_id = $teamMember->faculty_id ?? null;
            $group_id = $teamMember->simulation_team_id ?? null;

            $data = $this->industryBulletinService->getIndustryBulletinData($simulation_id, $faculty_id, $quarter_id, $group_id);

            // Report Analysis
            $reportAnalysis = DB::connection('mysql_ai')->table('myapp_chatlog')
                ->where([
                    'simulation_id' => $simulation_id,
                    'faculty_id' => $faculty_id,
                    'group_number' => $group_id,
                    'quarter' => $quarter_id,
                    'report_type' => 'industry-bulletin',
                ])->first();

            return view('student.simulation.financial-reports.extra.industry-bulletin', compact(
                'commonHeader',
                'data',
                'reportAnalysis'
            ));
        } catch (\Exception $e) {
            // Log the error and redirect back with an error message
            Log::error('Error in industryBulletin: '.$e->getMessage());

            return redirect()->back()->with('error', 'An error occurred while processing the data.');
        }
    }

    /**
     * Display the consulting reports (Piurchase) view.
     */
    public function consultingreports(Request $request)
    {
        try {
            // Retrieve Common Simulation Header
            $commonHeader = $this->commonSimulationHeader($request->simulation_id, $request->quarter_id);

            // Extract simulation and quarter IDs from the request
            $simulation_id = (int) $request->simulation_id;
            $quarter_id = (int) $request->quarter_id;

            $user = $this->activeUserService->getActiveUser();

            // Get the team member's faculty and team IDs
            $teamMember = SimulationTeamMember::where([
                'simulation_id' => $simulation_id,
                'student_id' => $user->id,
            ])->first();

            $faculty_id = $teamMember->faculty_id ?? null;
            $group_id = $teamMember->simulation_team_id ?? null;

            $getReports = ConsultingReportPreference::with('consultingFee')
                ->where([
                    'simulation_id' => $simulation_id,
                    'faculty_id' => $faculty_id,
                    'group_id' => $group_id,
                    'quarter' => $quarter_id,
                ])->orderBy('consulting_fee_id')->get();

            return view('student.simulation.buy-reports.index', compact(
                'commonHeader',
                'getReports'
            ));
        } catch (\Exception $e) {
            // Log the error and redirect back with an error message
            Log::error('Error in ConsultingReport: '.$e->getMessage());

            return redirect()->back()->with('error', 'An error occurred while processing the data.');
        }
    }

    /**
     * Display the Inventry reports (FGI & Procurement Stock) view.
     */
    public function inventoryStock(Request $request)
    {

        try {
            // Common Header
            $commonHeader = $this->commonSimulationHeader(
                $request->simulation_id,
                $request->quarter_id
            );

            $simulation_id = $request->simulation_id;
            $quarter_id = $request->quarter_id;

            $user = $this->activeUserService->getActiveUser();

            // Team member
            $teamMember = SimulationTeamMember::where([
                'simulation_id' => $simulation_id,
                'student_id' => $user->id,
            ])->first();

            $faculty_id = $teamMember->faculty_id ?? null;
            $group_id = $teamMember->simulation_team_id ?? null;

            /*
              |--------------------------------------------------------------------------
              | Finished Goods Data
              |--------------------------------------------------------------------------
              */
            $finishedGoodsDcCenters =
              $this->finishedGoodsInventoryService
                  ->finishedGoodsInventory(
                      $simulation_id,
                      $faculty_id,
                      $quarter_id,
                      $group_id
                  );

            /*
              |--------------------------------------------------------------------------
              | Procurement Data
              |--------------------------------------------------------------------------
              */
            $procurementData =
              $this->procurementService
                  ->procurement(
                      $simulation_id,
                      $faculty_id,
                      $quarter_id,
                      $group_id
                  );

            $procurementDcCenters = $procurementData->dcCenters;
            $procurementInventory = $procurementData->procurementInventory;

            $procurementClosure = $this->procurementClosureData($simulation_id, $faculty_id, $group_id, $quarter_id);

            /*
              |--------------------------------------------------------------------------
              | Single View
              |--------------------------------------------------------------------------
              */
            return view(
                'student.simulation.financial-reports.extra.inventory-report',
                compact(
                    'commonHeader',
                    'finishedGoodsDcCenters',
                    'procurementDcCenters',
                    'procurementInventory',
                    'procurementClosure',

                )
            );
        } catch (\Exception $e) {
            Log::error('Error in inventoryReport: '.$e->getMessage());

            return redirect()
                ->back()
                ->with('error', 'An error occurred while processing the data.');
        }
    }

    private function procurementClosureData(
        $simulation_id,
        $faculty_id,
        $group_number,
        $quarter_id
    ) {
        try {
            $procurementData = Procurement::where([
                'simulation_id' => $simulation_id,
                'faculty_id' => $faculty_id,
                'group_number' => $group_number,
                'quarter' => $quarter_id,
            ])->with(['rawMaterial', 'subAssembly'])->get();

            if ($procurementData->isEmpty()) {
                return [];
            }

            return $procurementData
                ->groupBy('region_id')
                ->map(function ($regionGroup) {
                    return [
                        'rawMaterial' => $regionGroup
                            ->flatMap->rawMaterial
                            ->mapWithKeys(function ($rawMaterial) {
                                return [
                                    $rawMaterial->raw_material_id => $rawMaterial->quantity,
                                ];
                            })
                            ->toArray(),

                        'subAssembly' => $regionGroup
                            ->flatMap->subAssembly
                            ->groupBy('sub_assembly_component_id')
                            ->map(function ($componentGroup) {
                                return $componentGroup
                                    ->groupBy('supplier_id')
                                    ->map(function ($supplierGroup) {
                                        return $supplierGroup
                                            ->mapWithKeys(function ($supply) {
                                                return [
                                                    $supply->supply_channel_id => $supply->total_shipment,
                                                ];
                                            })
                                            ->toArray();
                                    })
                                    ->toArray();
                            })
                            ->toArray(),
                    ];
                })
                ->toArray();
        } catch (\Exception $e) {
            Log::error('Procurement Closure Error: '.$e->getMessage());

            return [];
        }
    }
}
