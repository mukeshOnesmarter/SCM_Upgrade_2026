<?php

namespace App\Http\Controllers;

use App\Models\Simulation;
use App\Models\SimulationQuarter;
use App\Models\SimulationTeamMember;
use App\Services\ActiveUserService;
use App\Services\Reports\BalanceSheetService;
use App\Services\Reports\CashFlowService;
use App\Services\Reports\CorporatePnlService;
use App\Services\Reports\ProductPnlService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class StudentDecisionReportController extends Controller
{
    /**
     * Call Active User Service.
     */
    protected $activeUserService;

    protected $corporatePnlService;

    protected $balanceSheetService;

    protected $productPnlService;

    protected $cashFlowService;

    public function __construct(
        ActiveUserService $activeUserService,
        CorporatePnlService $corporatePnlService,
        BalanceSheetService $balanceSheetService,
        ProductPnlService $productPnlService,
        CashFlowService $cashFlowService
    ) {
        $this->activeUserService = $activeUserService;
        $this->corporatePnlService = $corporatePnlService;
        $this->balanceSheetService = $balanceSheetService;
        $this->productPnlService = $productPnlService;
        $this->cashFlowService = $cashFlowService;
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

    public function corporatePnl(Request $request)
    {
        try {
            // Get the active user
            $user = $this->activeUserService->getActiveUser();

            // Retrieve Common Simulation Header
            $commonHeader = $this->commonSimulationHeader($request->simulation_id, $request->quarter_id);

            // Retrieve associated faculty and group ID
            $teamMember = SimulationTeamMember::where([
                'simulation_id' => $request->simulation_id,
                'student_id' => $user->id,
            ])->first();

            // Extract necessary details
            $simulation_id = $request->simulation_id;
            $quarter_id = $request->quarter_id;
            $faculty_id = $teamMember->faculty_id ?? null;
            $group_id = $teamMember->simulation_team_id ?? null;

            // Report Analysis
            $reportAnalysis = DB::connection('mysql_ai')->table('myapp_chatlog')
                ->where([
                    'simulation_id' => $simulation_id,
                    'faculty_id' => $faculty_id,
                    'group_number' => $group_id,
                    'quarter' => $quarter_id,
                    'report_type' => 'corporate-pnl',
                ])->first();

            return view('student.simulation.financial-reports.corporate-pnl', [
                'commonHeader' => $commonHeader,
                'productReport' => $this->corporatePnlService->corporateProfitLoss($simulation_id, $faculty_id, $group_id, $quarter_id)->products,
                'productSummary' => $this->corporatePnlService->corporateProfitLoss($simulation_id, $faculty_id, $group_id, $quarter_id)->summary,
                'reportAnalysis' => $reportAnalysis,
            ]);
        } catch (\Throwable $e) {
            // Log the error and redirect back with an error message
            Log::error('Error in Corporate P&L Statement Report: '.$e->getMessage());

            return redirect()->back()->with('error', 'An error occurred while processing the Corporate P&L Statement data.');
        }
    }

    public function productPnl(Request $request)
    {
        try {
            // Get the active user
            $user = $this->activeUserService->getActiveUser();

            // Retrieve Common Simulation Header
            $commonHeader = $this->commonSimulationHeader($request->simulation_id, $request->quarter_id);

            // Retrieve associated faculty and group ID
            $teamMember = SimulationTeamMember::where([
                'simulation_id' => $request->simulation_id,
                'student_id' => $user->id,
            ])->first();

            // Extract necessary details
            $simulation_id = $request->simulation_id;
            $quarter_id = $request->quarter_id;
            $faculty_id = $teamMember->faculty_id ?? null;
            $group_id = $teamMember->simulation_team_id ?? null;

            // Report Analysis
            $reportAnalysis = DB::connection('mysql_ai')->table('myapp_chatlog')
                ->where([
                    'simulation_id' => $simulation_id,
                    'faculty_id' => $faculty_id,
                    'group_number' => $group_id,
                    'quarter' => $quarter_id,
                    'report_type' => 'product-pnl',
                ])->first();

            // Get raw product report
            $productReport = $this->productPnlService->productPnl($simulation_id, $faculty_id, $group_id, $quarter_id);

            // Sum metrics per product for chart
            $productReport->each(function ($product) {
                $product->totalRevenue = $product->marketRegion->sum(fn ($r) => $r->revenues->sum('totalRevenues'));
                $product->totalExpenses = $product->marketRegion->sum(fn ($r) => $r->totalExpenses);
                $product->grossMargin = $product->marketRegion->sum(fn ($r) => $r->grossMargin);
                $product->totalFixedCosts = $product->marketRegion->sum(fn ($r) => $r->totalFixedCosts);
                $product->operatingIncome = $product->marketRegion->sum(fn ($r) => $r->operatingIncome);
                $product->totalSalesVolume = collect($product->marketRegion)->sum(function ($r) {
                    return collect($r->productSalesVolume)->sum('salesVolume');
                });
            });

            return view('student.simulation.financial-reports.product-pnl', [
                'commonHeader' => $commonHeader,
                'productReport' => $productReport,
                'reportAnalysis' => $reportAnalysis,
            ]);
        } catch (\Throwable $e) {

            // Log the error and redirect back with an error message
            Log::error('Error in Product P&L Statement Report: '.$e->getMessage());

            return redirect()->back()->with('error', 'An error occurred while processing the Product P&L Statement data.');
        }
    }

    public function balanceSheet(Request $request)
    {
        try {
            // Get the active user
            $user = $this->activeUserService->getActiveUser();

            // Retrieve Common Simulation Header
            $commonHeader = $this->commonSimulationHeader($request->simulation_id, $request->quarter_id);

            // Retrieve associated faculty and group ID
            $teamMember = SimulationTeamMember::where([
                'simulation_id' => $request->simulation_id,
                'student_id' => $user->id,
            ])->first();

            // Extract necessary details
            $simulation_id = $request->simulation_id;
            $quarter_id = $request->quarter_id;
            $faculty_id = $teamMember->faculty_id ?? null;
            $group_id = $teamMember->simulation_team_id ?? null;

            // Report Analysis
            $reportAnalysis = DB::connection('mysql_ai')->table('myapp_chatlog')
                ->where([
                    'simulation_id' => $simulation_id,
                    'faculty_id' => $faculty_id,
                    'group_number' => $group_id,
                    'quarter' => $quarter_id,
                    'report_type' => 'balance-sheet',
                ])->first();

            return view('student.simulation.financial-reports.balance-sheet', [
                'quarter_id' => $quarter_id,
                'commonHeader' => $commonHeader,
                'data' => $this->balanceSheetService->generateBalanceSheet($simulation_id, $faculty_id, $group_id, $quarter_id),
                'reportAnalysis' => $reportAnalysis,
            ]);
        } catch (\Throwable $e) {
            // Log the error and redirect back with an error message
            Log::error('Error in Balance Sheet Report: '.$e->getMessage());

            return redirect()->back()->with('error', 'An error occurred while processing the Balance Sheet Report data.');
        }
    }

    public function cashFlow(Request $request)
    {
        try {
            // Get the active user
            $user = $this->activeUserService->getActiveUser();

            // Retrieve Common Simulation Header
            $commonHeader = $this->commonSimulationHeader($request->simulation_id, $request->quarter_id);

            // Retrieve associated faculty and group ID
            $teamMember = SimulationTeamMember::where([
                'simulation_id' => $request->simulation_id,
                'student_id' => $user->id,
            ])->first();

            // Extract necessary details
            $simulation_id = $request->simulation_id;
            $quarter_id = $request->quarter_id;
            $faculty_id = $teamMember->faculty_id ?? null;
            $group_id = $teamMember->simulation_team_id ?? null;

            // Report Analysis
            $reportAnalysis = DB::connection('mysql_ai')->table('myapp_chatlog')
                ->where([
                    'simulation_id' => $simulation_id,
                    'faculty_id' => $faculty_id,
                    'group_number' => $group_id,
                    'quarter' => $quarter_id,
                    'report_type' => 'cash-flow',
                ])->first();

            return view('student.simulation.financial-reports.cash-flow', [
                'quarter_id' => $quarter_id,
                'commonHeader' => $commonHeader,
                'data' => $this->cashFlowService->generateReport($simulation_id, $faculty_id, $group_id, $quarter_id),
                'reportAnalysis' => $reportAnalysis,
            ]);
        } catch (\Throwable $e) {
            // Log the error and redirect back with an error message
            Log::error('Error in Cash Flow Analysis Report: '.$e->getMessage());

            return redirect()->back()->with('error', 'An error occurred while processing the Cash Flow Analysis Report data.');
        }
    }
}
