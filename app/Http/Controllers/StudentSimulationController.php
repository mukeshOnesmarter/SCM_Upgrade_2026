<?php

namespace App\Http\Controllers;

use App\Jobs\RunInitializeStudentJob;
use App\Models\Decisions\Forecasting;
use App\Models\Faculty;
use App\Models\FacultyInvite;
use App\Models\Simulation;
use App\Models\SimulationFaculty;
use App\Models\SimulationQuarter;
use App\Models\SimulationQuarterCustomize;
use App\Models\SimulationTeamMember;
use App\Services\ActiveUserService;
use App\Services\InitialSimulationSetupService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class StudentSimulationController extends Controller
{
    /**
     * Call Active User Service.
     */
    protected $activeUserService;

    protected $initialSimulationSetupService;

    public function __construct(
        ActiveUserService $activeUserService,
        InitialSimulationSetupService $initialSimulationSetupService
    ) {
        $this->activeUserService = $activeUserService;
        $this->initialSimulationSetupService = $initialSimulationSetupService;
    }

    // CUSTOMIZE METHOD 01: INVITATIONS
    public function invitations()
    {
        $activeUser = $this->activeUserService->getActiveUser();

        // GET ALL INVITATIONS ASSOCIATED WITH THE ACTIVE USER'S EMAIL.
        $allInvitations = FacultyInvite::where('email', $activeUser->email)
            ->get()
            ->map(function ($invitation) {
                // CHECK STATUS IS FACULTY PUBLISHED THIS SIMULATION AND ALLOW STUDENT TO JOIN.
                $simulationFaculty = SimulationFaculty::where('simulation_id', $invitation->simulation_id)
                    ->where('faculty_id', $invitation->faculty_id)
                    ->first();

                $invitation->is_faculty_published = optional($simulationFaculty)->is_published;

                return $invitation;
            });

        foreach ($allInvitations as $key => $invitation) {
            $invitation->faculty = Faculty::find($invitation->faculty_id);
            $invitation->simulation = Simulation::find($invitation->simulation_id);
            $invitation->invited_at = Carbon::parse($invitation->invited_at)->format('M d, Y');
            // CHECK STUDENT JOINED OR NOT.
            $studentJoined = SimulationTeamMember::where('simulation_id', $invitation->simulation_id)
                ->where('faculty_id', $invitation->faculty_id)
                ->where('student_id', $activeUser->id)
                ->whereNotNull('student_joined')
                ->first();

            $invitation->is_student_join = $studentJoined ? Carbon::parse($studentJoined->student_joined)->format('M d, Y') : null;
        }

        return view('student.invitation', compact('allInvitations'));
    }

    // CUSTOMIZE METHOD 02: JOINSIMULATION
    public function joinSimulation()
    {
        return view('student.simulation.join-simulation');
    }

    // CUSTOMIZE METHOD 03: REVIEWSIMULATION
    public function reviewSimulation(Request $request)
    {
        if ($request->isMethod('get')) {
            abort(403, 'Direct access is not allowed.');
        }

        try {
            $activeUser = $this->activeUserService->getActiveUser();

            $validatedData = $request->validate([
                'simulation_code' => 'required|string|max:10',
            ]);

            $simulationCode = trim($validatedData['simulation_code']);

            $foundSimulation = Simulation::where('share_code', $simulationCode)
                ->whereDate('start_date', '<=', now()->format('Y-m-d'))
                ->whereDate('end_date', '>=', now()->format('Y-m-d'))
                ->where('is_published', '!=', 'No')
                ->first();

            if (! $foundSimulation) {
                return redirect()->route('student.invitations')
                    ->with('error', 'The simulation code does not exist. Please try another one.');
            }

            $studentRecord = SimulationTeamMember::where('student_id', $activeUser->id)
                ->where('simulation_id', $foundSimulation->id)
                ->first();

            if ($studentRecord && ! is_null($studentRecord->student_joined)) {
                return redirect()->back()
                    ->with('info', 'You have already joined this simulation.');
            }

            // ✅ FIXED PART
            if (! $studentRecord) {
                return redirect()->back()
                    ->with('error', 'You are not assigned to this simulation.');
            }

            $studentRecord->update([
                'student_joined' => now(),
            ]);

            // LATE JOIN INITIALIZATION
            $teamId = $studentRecord->simulation_team_id;
            $simulationId = $foundSimulation->id;
            $facultyId = $studentRecord->faculty_id;

            // Only if simulation is already deployed and not currently deploying
            if ($foundSimulation->is_published != 'No' && ! $foundSimulation->is_deploying) {

                // 🔐 TEAM LEVEL CHECK using Forecasting Q0
                $alreadyInitialized = Forecasting::where([
                    'simulation_id' => $simulationId,
                    'faculty_id' => $facultyId,
                    'group_number' => $teamId,
                    'quarter' => 0,
                ])->exists();

                if (! $alreadyInitialized) {

                    dispatch((new RunInitializeStudentJob($simulationId, $studentRecord->id))
                        ->onQueue('simulation-setup'));
                }
            }

            if ($foundSimulation->start_date <= now()->format('Y-m-d')) {
                return redirect()
                    ->route('student.simulation.decisions.forecasting', [
                        'simulation_id' => $foundSimulation->id,
                        'quarter_id' => 4,
                    ])
                    ->with('success', 'Simulation has been joined successfully.');
            } else {
                return view('student.simulation.review-simulation', compact('foundSimulation'));
            }
        } catch (ValidationException $e) {
            return redirect()->back()->withErrors($e->validator)->withInput();
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'An unexpected error occurred. Please try again later.');
        }
    }

    // CUSTOMIZE METHOD 04: SIMULATION OVERVIEW
    public function index()
    {
        // CALL ACTIVE USER SERVICE.
        $activeUser = $this->activeUserService->getActiveUser();

        // LIST ALL SIMULATIONS ASSIGNED TO THE ACTIVE STUDENT AND JOINED, WITH EAGER LOADING
        $simulations = SimulationTeamMember::with(['simulation', 'faculty'])
            ->where('student_id', $activeUser->id)
            ->where('student_allowed', 'Yes')
            ->whereNotNull('student_joined')
            ->get();

        foreach ($simulations as $item) {
            // Get faculty_id for this team
            $faculty_id = $item->faculty_id;

            // Fetch customized quarters for this simulation + faculty
            $customQuarters = SimulationQuarterCustomize::where('simulation_id', $item->simulation_id)
                ->where('faculty_id', $faculty_id)
                ->get();

            // Determine which set of quarters to use: customized or default
            $quarters = $customQuarters->isNotEmpty()
                ? $customQuarters
                : SimulationQuarter::where('simulation_id', $item->simulation_id)->get();

            // Find current active quarter from selected set
            $currentQuarter = $quarters->first(function ($quarter) {
                return $quarter->is_schedule == 'Yes'
                    && empty($quarter->close_manualy)
                    && Carbon::parse($quarter->start_date)->startOfDay()->lte(now())
                    && Carbon::parse($quarter->end_date)->endOfDay()->gte(now());
            });

            // Attach current quarter to the simulation item
            $item->setAttribute('curent_quarter', $currentQuarter);

            // Format simulation dates
            if ($item->simulation) {
                $item->simulation->duration = (int) Carbon::parse($item->simulation->start_date)
                    ->diffInDays(Carbon::parse($item->simulation->end_date), true) + 1;

                $item->simulation->start_date = Carbon::parse($item->simulation->start_date)->format('M d, Y');
                $item->simulation->end_date = Carbon::parse($item->simulation->end_date)->format('M d, Y');
            }

            // Format current quarter dates
            if ($currentQuarter) {
                $currentQuarter->days_remaining = (int) Carbon::parse($currentQuarter->end_date)
                    ->diffInDays(now(), true);

                $currentQuarter->start_date = Carbon::parse($currentQuarter->start_date)->format('M d, Y');
                $currentQuarter->end_date = Carbon::parse($currentQuarter->end_date)->format('M d, Y');
            }
        }

        return view('student.simulation.index', compact('simulations'));
    }

    // CUSTOMIZE METHOD 05: SIMULATION LoCKED STATE
    public function locked(Request $request)
    {
        $simulation_id = $request->simulation_id;

        // Get the currently active user (student)
        $user = $this->activeUserService->getActiveUser();

        // Retrieve the faculty ID for the active student in the given simulation
        $teamMember = SimulationTeamMember::where([
            'simulation_id' => $simulation_id,
            'student_id' => $user->id,
        ])->first(['faculty_id']);

        if (! $teamMember) {
            return view('student.simulation.index');
        }

        $faculty_id = $teamMember->faculty_id ?? null;

        $simulation = Simulation::find($simulation_id);
        $tz = $simulation->timezone ?? 'America/New_York';

        $simulationFaculty = SimulationFaculty::where([
            'simulation_id' => $simulation_id,
            'faculty_id' => $faculty_id,
        ])->first();

        $now = now($tz);

        $isFrozen = $simulationFaculty
            && $simulationFaculty->frozen_until
            && $now->lt($simulationFaculty->frozen_until);

        $isSyncing = $simulation && $simulation->is_syncing;

        $simulationInitialize = $simulation && $simulation->is_deploying;

        if ($isFrozen || $isSyncing || $simulationInitialize) {

            $remainingTime = null;

            if ($isFrozen) {
                $remainingTime = $now
                    ->diff($simulationFaculty->frozen_until)
                    ->format('%h hours %i minutes %s seconds');
            }

            return view('student.simulation.quarter-closed', [
                'remainingTime' => $remainingTime,
                'isSyncing' => $isSyncing,
                'simulationInitialize' => $simulationInitialize,
            ]);
        }

        return redirect()->route('student.dashboard');
    }

    // CUSTOMIZE METHOD 06: SIMULATION STUDENT BLOCKED
    public function studentBlocked(Request $request)
    {
        return view('student.simulation.access-denied.blocked');
    }

    // CUSTOMIZE METHOD 07: SIMULATION STUDENT REMOVED
    public function studenRemoved(Request $request)
    {
        return view('student.simulation.access-denied.removed');
    }

    // CUSTOMIZE METHOD 08: SIMULATION STUDENT UNPAID
    public function studenUnpaid(Request $request)
    {
        return view('student.simulation.access-denied.unpaid');
    }

    // CUSTOMIZE METHOD 09: SIMULATION PAYMENT
    public function payment(Request $request)
    {
        return view('student.simulation.payment.index');
    }

    // CUSTOMIZE METHOD 10: SIMULATION PROCESS PAYMENT
    public function processPayment(Request $request)
    {
        return view('student.simulation.payment.process-payment');
    }
}
