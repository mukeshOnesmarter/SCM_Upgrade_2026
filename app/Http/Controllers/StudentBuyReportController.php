<?php

namespace App\Http\Controllers;

use App\Models\ConsultingFee;
use App\Models\Decisions\ConsultingReportPreference;
use App\Models\Simulation;
use App\Models\SimulationQuarter;
use App\Models\SimulationTeamMember;
use App\Services\ActiveUserService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class StudentBuyReportController extends Controller
{
    protected $activeUserService;

    public function __construct(ActiveUserService $activeUserService)
    {
        $this->activeUserService = $activeUserService;
    }

    private function commonSimulationHeader($simulation_id, $quarter_id, $decisions_title = 'Buy Reports')
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

        $simulation->decisions_title = $decisions_title;
        $simulation->payment_status = $teamMember->payment_status;
        $simulation->student_team_number = $teamMember->simulation_team_id;

        $decisionsJson = $quarters->where('quarter_name', $quarter_id)->pluck('decisions')->first();
        $simulation->decisionEnable = $decisionsJson ? json_decode($decisionsJson, true) : [];

        return $simulation;
    }

    /**
     * Display main research study listing page
     */
    public function index()
    {
        $getReports = ConsultingFee::orderBy('id')->get();
        $routeParams = ['simulation_id' => request('simulation_id'), 'quarter_id' => request('quarter_id', 3)];

        return view('student.simulation.buy-reports.index', compact('getReports', 'routeParams'));
    }

    /**
     * Generic reusable method for all report pages
     */
    private function loadConsultingReport($slug, $view, $simulation_id, $quarter_id)
    {
        try {
            // Step 1: Get the active user
            $user = $this->activeUserService->getActiveUser();

            // tep 2: Fetch team information for this simulation
            $teamMember = SimulationTeamMember::where([
                'simulation_id' => $simulation_id,
                'student_id' => $user->id,
            ])->first();

            if (! $teamMember) {
                return redirect()->back()->with('error', 'Team member not found for this simulation.');
            }

            // Step 3: Find the consulting fee record by slug
            $consultingFee = ConsultingFee::where('slug', $slug)->first();

            if (! $consultingFee) {
                return redirect()->back()->with('error', 'Consulting report not found.');
            }

            // Step 4: Check if this team already bought this report for the quarter
            $preference = ConsultingReportPreference::where([
                'simulation_id' => $simulation_id,
                'faculty_id' => $teamMember->faculty_id ?? null,
                'group_id' => $teamMember->simulation_team_id ?? null,
                'quarter' => $quarter_id,
                'consulting_fee_id' => $consultingFee->id,
            ])->first();

            $hasPurchased = (bool) $preference;

            // Step 5: Load the appropriate report view
            return view("student.simulation.buy-reports.{$view}", [
                'commonHeader' => $this->commonSimulationHeader($simulation_id, $quarter_id),
                'consulting_fee' => $consultingFee,
                'consulting_fee_id' => $consultingFee->id,
                'hasPurchased' => $hasPurchased,
                'simulation_id' => $simulation_id,
                'quarter' => $quarter_id,
                'group_number' => $teamMember->simulation_team_id,
                'preference' => $preference,
            ]);
        } catch (\Exception $e) {
            Log::error("Error loading report '{$slug}': ".$e->getMessage());

            return redirect()->back()->with('error', 'An error occurred while loading the consulting report.');
        }
    }

    /* --------------------------------------------------------------------------
     * 🔹 Benchmarking Reports
     * -------------------------------------------------------------------------- */

    /** Benchmark – Earnings */
    public function benchmarkEarnings($simulation_id, $quarter_id)
    {
        return $this->loadConsultingReport('earnings', 'benchmark-earnings', $simulation_id, $quarter_id);
    }

    /** Benchmark – Balance Sheets */
    public function benchmarkBalanceSheet($simulation_id, $quarter_id)
    {
        return $this->loadConsultingReport('balancesheet', 'benchmark-balance-sheet', $simulation_id, $quarter_id);
    }

    /** Benchmark – Procurement */
    public function benchmarkProcurement($simulation_id, $quarter_id)
    {
        return $this->loadConsultingReport('procurement', 'benchmark-procurement', $simulation_id, $quarter_id);
    }

    /** Benchmark – Manufacturing */
    public function benchmarkManufacturing($simulation_id, $quarter_id)
    {
        return $this->loadConsultingReport('manufacturing', 'benchmark-manufacturing', $simulation_id, $quarter_id);
    }

    /** Benchmark – Distribution */
    public function benchmarkDistribution($simulation_id, $quarter_id)
    {
        return $this->loadConsultingReport('distribution', 'benchmark-distribution', $simulation_id, $quarter_id);
    }

    /** Benchmark – Transportation */
    public function benchmarkTransportation($simulation_id, $quarter_id)
    {
        return $this->loadConsultingReport('transportation', 'benchmark-transportation', $simulation_id, $quarter_id);
    }

    /** Benchmark – Generate Demand */
    public function benchmarkGenerateDemand($simulation_id, $quarter_id)
    {
        return $this->loadConsultingReport('generatedemand', 'benchmark-generate-demand', $simulation_id, $quarter_id);
    }

    /** Benchmark – Information Technology & Research Studies */
    public function benchmarkIT($simulation_id, $quarter_id)
    {
        return $this->loadConsultingReport('it', 'benchmark-it', $simulation_id, $quarter_id);
    }

    /** Benchmark – Operating Statistics */
    public function benchmarkOperatingStatistics($simulation_id, $quarter_id)
    {
        return $this->loadConsultingReport('operating', 'benchmark-operating-statistics', $simulation_id, $quarter_id);
    }

    /* --------------------------------------------------------------------------
     * 🔹 Market / Regional Reports
     * -------------------------------------------------------------------------- */

    /** Market Statistics */
    public function marketStatistics($simulation_id, $quarter_id)
    {
        return $this->loadConsultingReport('market', 'market-statistics', $simulation_id, $quarter_id);
    }

    /** Regional Summary Analysis */
    public function regionalSummary($simulation_id, $quarter_id)
    {
        return $this->loadConsultingReport('region', 'regional-summary', $simulation_id, $quarter_id);
    }

    /* --------------------------------------------------------------------------
     * 🔹 Customer Reports
     * -------------------------------------------------------------------------- */

    /** Customer Satisfaction */
    public function customerSatisfaction($simulation_id, $quarter_id)
    {
        return $this->loadConsultingReport('customersatisfaction', 'customer-satisfaction', $simulation_id, $quarter_id);
    }

    /** Price Sensitivity Analysis */
    public function priceSensitivity($simulation_id, $quarter_id)
    {
        return $this->loadConsultingReport('pricesensitivity', 'price-sensitivity', $simulation_id, $quarter_id);
    }

    /** Upcoming Quarter Selection */
    public function upcomingQuarterSelection($simulation_id, $quarter_id)
    {
        return view('student.simulation.decisions.upcoming-quarter.index', [
            'commonHeader' => $this->commonSimulationHeader($simulation_id, $quarter_id),
        ]);
    }

    /** Buy Consulting Report */
    public function buyConsultingReport(Request $request)
    {
        $request->validate([
            'simulation_id' => 'required|integer|exists:simulations,id',
            'consulting_fee_id' => 'required|integer|exists:consulting_fees,id',
            'quarter' => 'required|integer|min:1',
        ]);

        DB::beginTransaction();

        try {
            // Get the active user
            $user = $this->activeUserService->getActiveUser();

            // Fetch the team member record
            $teamMember = SimulationTeamMember::where([
                'simulation_id' => $request->simulation_id,
                'student_id' => $user->id,
            ])->first();

            if (! $teamMember) {
                return redirect()->back()->with('error', 'Team member not found for this simulation.');
            }

            // Extract required info
            $simulation_id = $request->simulation_id;
            $faculty_id = $teamMember->faculty_id ?? null;
            $group_id = $teamMember->simulation_team_id ?? null;
            $quarter = $request->quarter;
            $consulting_id = $request->consulting_fee_id;

            // Create or update preference (unique by simulation, group, quarter, and report)
            ConsultingReportPreference::updateOrCreate(
                [
                    'simulation_id' => $simulation_id,
                    'faculty_id' => $faculty_id,
                    'group_id' => $group_id,
                    'quarter' => $quarter,
                    'consulting_fee_id' => $consulting_id,
                ],
                [] // no additional fields to update
            );

            DB::commit();

            return redirect()->back()->with('success', 'Consulting report purchased successfully.');
        } catch (ValidationException $e) {
            DB::rollBack();

            return redirect()->back()->withErrors($e->validator)->withInput();
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Consulting Report Purchase Error: '.$e->getMessage());

            return redirect()->back()->with('error', 'An unexpected error occurred. Please try again later.');
        }
    }

    // Delete Report
    public function removeConsultingReport(Request $request)
    {
        $request->validate([
            'simulation_id' => 'required|integer|exists:simulations,id',
            'consulting_fee_id' => 'required|integer|exists:consulting_fees,id',
            'quarter' => 'required|integer|min:1',
        ]);

        DB::beginTransaction();

        try {
            $user = $this->activeUserService->getActiveUser();

            $teamMember = SimulationTeamMember::where([
                'simulation_id' => $request->simulation_id,
                'student_id' => $user->id,
            ])->first();

            if (! $teamMember) {
                return redirect()->back()->with('error', 'Team member not found for this simulation.');
            }

            $deleted = ConsultingReportPreference::where([
                'simulation_id' => $request->simulation_id,
                'faculty_id' => $teamMember->faculty_id ?? null,
                'group_number' => $teamMember->simulation_team_id ?? null,
                'quarter' => $request->quarter,
                'consulting_fee_id' => $request->consulting_fee_id,
            ])->delete();

            DB::commit();

            if ($deleted) {
                return redirect()->back()->with('success', 'Consulting report removed successfully.');
            } else {
                return redirect()->back()->with('warning', 'No matching consulting report found to remove.');
            }
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Consulting Report Remove Error: '.$e->getMessage());

            return redirect()->back()->with('error', 'An unexpected error occurred. Please try again later.');
        }
    }
}
