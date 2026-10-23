<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreSimulationRequest;
use App\Http\Requests\UpdateSimulationRequest;
use App\Models\Faculty;
use App\Models\FacultyInvite;
use App\Models\Firm;
use App\Models\QuarterActualDemand;
use App\Models\QuarterForecastDemand;
use App\Models\QuarterInitialDemand;
use App\Models\QuarterProbabilityDemand;
use App\Models\Simulation;
use App\Models\SimulationFaculty;
use App\Models\SimulationFirm;
use App\Models\SimulationQuarter;
use App\Models\SimulationQuarterCustomize;
use App\Models\SimulationTeam;
use App\Models\SimulationTeamMember;
use App\Models\StudentEnroll;
use App\Services\ActiveUserService;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SimulationController extends Controller
{
    protected $activeUserService;

    public function __construct(ActiveUserService $activeUserService)
    {
        $this->activeUserService = $activeUserService;
    }

    /**
     * FUNCTION TO DISPLAY COURSE / SIMULATION INFORMATION
     * This function is inherited by various other functions.
     */
    public function courseDetail(int $courses_id)
    {
        $course = Simulation::where('id', $courses_id)->first();

        // STEP 01 (A): FORMAT THE DATA..
        $course->start_date = Carbon::parse($course->start_date)->format('m-d-Y');
        $course->end_date = Carbon::parse($course->end_date)->format('m-d-Y');
        // STEP 01 (B):  CALCULATE THE NUMBER OF DAYS BETWEEN START DATE AND END DATE..
        $startDate = Carbon::createFromFormat('m-d-Y', $course->start_date);
        $endDate = Carbon::createFromFormat('m-d-Y', $course->end_date);
        // STEP 01 (C):  GET THE DIFFERENCE IN DAYS..
        $course->daysDifference = (int) $startDate->diffInDays($endDate, true);

        return $course;
    }

    /**
     * Display a listing of the resource.
     *
     * @return Response
     */
    public function index()
    {

        $activeUser = $this->activeUserService->getActiveUser();

        if ($activeUser && $activeUser->role === 'super-admin') {
            $courses = Simulation::with('simulationAdmin')
                ->get();
        } else {
            $courses = Simulation::with('simulationAdmin')
                ->where('created_by', $activeUser->id)
                ->get();
        }

        // Format dates
        $courses->each(function ($item) {
            $item->start_date = Carbon::parse($item->start_date)->format('m-d-Y');
            $item->end_date = Carbon::parse($item->end_date)->format('m-d-Y');
        });

        // Calculate totals
        $totalInvoice = $courses->sum(function ($item) {
            return $item->fee * $item->licence_count;
        });

        $receiveAmount = $courses->where('payment_status', 'settled')->sum(function ($item) {
            return $item->fee * $item->licence_count;
        });

        $balanceAmount = $courses->where('payment_status', '!=', 'settled')->sum(function ($item) {
            return $item->fee * $item->licence_count;
        });

        return view('admin.simulation.index', compact('courses', 'totalInvoice', 'receiveAmount', 'balanceAmount'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return Response
     */
    public function create()
    {
        return view('admin.simulation.create');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @return Response
     */
    public function store(StoreSimulationRequest $request)
    {
        $activeUser = $this->activeUserService->getActiveUser();
        $validatedData = $request->validated();

        DB::beginTransaction();

        try {
            // Step 1: Create Simulation Record
            $simulation = Simulation::create([
                'title' => $validatedData['title'],
                'organization' => $validatedData['organization'],
                'quarters' => 3 + $validatedData['quarters'], // Adding 3 past quarters
                'firms' => 1,
                'teams' => $validatedData['firms'] + 0, // +2 for BaseLineForm and AiFirm
                'start_date' => Carbon::createFromFormat('m-d-Y', $validatedData['start_date'])->format('Y-m-d'),
                'end_date' => Carbon::createFromFormat('m-d-Y', $validatedData['end_date'])->format('Y-m-d'),
                'fee' => 100.00,
                'licence_count' => $validatedData['firms'],
                'created_by' => $activeUser->id,
                'timezone' => $validatedData['timezone'] ?? 'America/New_York',
            ]);

            $simulation_id = $simulation->id;
            $quarter_duration = $validatedData['quarter_duration'];
            $total_quarters = $simulation->quarters;
            $start_date = $simulation->start_date;

            // Step 2: Create Quarters (3 past + future)
            for ($quarter = 1; $quarter <= $total_quarters; $quarter++) {
                if ($quarter == 1) {
                    $qStartDate = Carbon::parse($start_date)->subDays($quarter_duration * 3);
                } else {
                    $prevQuarterEnd = SimulationQuarter::where([
                        'simulation_id' => $simulation_id,
                        'quarter_name' => $quarter - 1,
                    ])->value('end_date');

                    $qStartDate = Carbon::parse($prevQuarterEnd)->addDay();
                }

                $qEndDate = $qStartDate->copy()->addDays($quarter_duration - 1);

                if ($quarter < 6) {
                    $decisions = '1, 2, 3';
                } elseif ($quarter < 7) {
                    $decisions = '1, 2, 3, 4, 5';
                } else {
                    $decisions = '1, 2, 3, 4, 5, 6, 7, 8';
                }

                SimulationQuarter::create([
                    'simulation_id' => $simulation_id,
                    'quarter_name' => $quarter,
                    'start_date' => $qStartDate->format('Y-m-d'),
                    'end_date' => $qEndDate->format('Y-m-d'),
                    'start_time' => '00:01:00',
                    'end_time' => '22:00:00',
                    'decisions' => $decisions,
                ]);
            }

            // Step 3: Create Firms
            for ($firm = 1; $firm <= $simulation->firms; $firm++) {
                SimulationFirm::create([
                    'simulation_id' => $simulation_id,
                    'firm_id' => $firm,
                    'firm_name_custome' => Firm::where('id', $firm)->value('custome_name'),
                ]);
            }

            // Step 4: Create Teams
            for ($team = 1; $team <= $simulation->teams; $team++) {
                SimulationTeam::create([
                    'simulation_id' => $simulation_id,
                    'group_number' => $team,
                    'team_name' => $team,
                ]);
            }

            // Step 5: Update Simulation End Date based on last quarter
            $lastQuarterEnd = SimulationQuarter::where([
                'simulation_id' => $simulation_id,
                'quarter_name' => $total_quarters,
            ])->value('end_date');

            $simulation->end_date = $lastQuarterEnd;
            $simulation->save();

            DB::commit();

            return redirect()->route('admin.courses.manageQuarter', $simulation_id);
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Simulation creation failed: '.$e->getMessage());

            return redirect()->route('admin.courses.index')
                ->with('error', 'There was a technical issue creating the simulation. Please try again later.');
        }
    }

    /**
     * Display the specified resource.
     *
     * @return Response
     */
    public function show(Simulation $simulation)
    {
        $course = $this->courseDetail($simulation->id);

        // Check if initial-demand setup is done
        if (! QuarterForecastDemand::where('simulation_id', $course->id)->exists()) {
            return redirect()
                ->route('admin.courses.industryInitialDemand', $course->id)
                ->with('setupInitialDemand', 'Please complete the initial demand setup for this course.');
        }

        // Check if faculty selection is pending
        if (! SimulationFaculty::where('simulation_id', $course->id)->exists()) {
            return redirect()
                ->route('admin.courses.selectFaculty', $course->id)
                ->with('facultySelection', 'Please select faculty members for this course.');
        }

        try {
            // FETCH SIMULATION WITH EAGER-LOADED RELATIONSHIPS
            $simulation = Simulation::with([
                'simulationFaculties' => function ($query) {
                    $query->whereIn('role', ['faculty', 'co_faculty']);
                },
                'simulationFaculties.faculty.profile',
                'simulationQuarters',
            ])->findOrFail($simulation->id);

            $simulation->simulationQuarters->each(function ($quarter) {
                $quarter->start_date = Carbon::parse($quarter->start_date)->format('m-d-Y');
                $quarter->end_date = Carbon::parse($quarter->end_date)->format('m-d-Y');
                $quarter->start_time = Carbon::parse($quarter->start_time)->format('h:i A');
                $quarter->end_time = Carbon::parse($quarter->end_time)->format('h:i A');
            });

            $students = SimulationTeamMember::with('student')
                ->where('simulation_id', $simulation->id)
                ->get();

            // STUDENT COUNTS
            $totalStudentEnroll = StudentEnroll::where([
                'simulation_id' => $simulation->id,
            ])->count();

            $totalStudentAssigned = SimulationTeamMember::where([
                'simulation_id' => $simulation->id,
            ])->count();

            $totalPaidStudents = SimulationTeamMember::where([
                'simulation_id' => $simulation->id,
                'payment_status' => 'paid',
            ])->count();

            $totalUnpaidStudents = SimulationTeamMember::where([
                'simulation_id' => $simulation->id,
                'payment_status' => 'unpaid',
            ])->count();

        } catch (Exception $e) {
            return redirect()->back()->with('error', 'Simulation not found.');
        }

        return view('admin.simulation.show', compact('course', 'simulation', 'students',
            'totalStudentEnroll',
            'totalStudentAssigned',
            'totalPaidStudents',
            'totalUnpaidStudents'));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @return Response
     */
    public function edit(Simulation $simulation)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @return Response
     */
    public function update(UpdateSimulationRequest $request, Simulation $simulation)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @return Response
     */
    public function destroy(Request $request, Simulation $simulation)
    {
        $confirmationCode = trim($request->input('confirmation_code'));
        $validateToken = $request->input('validateToken');

        if ($confirmationCode === '') {
            return redirect()
                ->back()
                ->withErrors(['confirmation_code' => 'The confirmation code is required.'])
                ->withInput();
        }

        if ($confirmationCode === $validateToken) {
            DB::beginTransaction();

            try {
                SimulationFirm::where('simulation_id', $simulation->id)->delete();
                SimulationTeam::where('simulation_id', $simulation->id)->delete();
                SimulationTeamMember::where('simulation_id', $simulation->id)->delete();
                SimulationFaculty::where('simulation_id', $simulation->id)->delete();
                SimulationQuarter::where('simulation_id', $simulation->id)->delete();
                SimulationQuarterCustomize::where('simulation_id', $simulation->id)->delete();

                QuarterInitialDemand::where('simulation_id', $simulation->id)->delete();
                QuarterProbabilityDemand::where('simulation_id', $simulation->id)->delete();
                QuarterActualDemand::where('simulation_id', $simulation->id)->delete();
                QuarterForecastDemand::where('simulation_id', $simulation->id)->delete();

                FacultyInvite::where('simulation_id', $simulation->id)->delete();

                $simulation->delete();

                DB::commit();

                return redirect()
                    ->route('admin.simulation.index')
                    ->with('success', 'Course deleted successfully.');
            } catch (Exception $e) {
                DB::rollBack();

                return redirect()
                    ->back()
                    ->withErrors(['delete_error' => 'An error occurred while deleting the course. Please try again.']);
            }
        } else {
            return redirect()
                ->back()
                ->withErrors(['confirmation_code' => 'The confirmation code is incorrect.'])
                ->withInput();
        }
    }

    /**
     * Update the schedule for a specific simulation quarter.
     *
     * @return RedirectResponse
     */
    public function quarterReSchedule(Request $request)
    {
        // Fetch the simulation quarter record by ID and simulation ID
        $record = SimulationQuarter::where('id', $request->quarter_id)
            ->where('simulation_id', $request->simulation_id)
            ->first();

        // Check if the quarter record exists
        if (! $record) {
            return redirect()
                ->route('admin.simulation.show', $request->simulation_id)
                ->with('error', 'Quarter not found. Unable to update schedule.');
        }

        try {
            // Update schedule fields with properly formatted dates and times
            $record->start_date = Carbon::createFromFormat('m-d-Y', $request->start_date)->format('Y-m-d');
            $record->end_date = Carbon::createFromFormat('m-d-Y', $request->end_date)->format('Y-m-d');
            $record->start_time = Carbon::parse($request->start_time)->format('H:i:s');
            $record->end_time = Carbon::parse($request->end_time)->format('H:i:s');
            $record->is_schedule = 'Yes';

            $record->save();

            // Redirect with success message if update is successful
            return redirect()
                ->route('admin.simulation.show', $request->simulation_id)
                ->with('success', 'Quarter schedule updated successfully.');
        } catch (Exception $e) {
            // Redirect with error message on failure
            return redirect()
                ->route('admin.simulation.show', $request->simulation_id)
                ->with('error', 'An error occurred while updating the schedule. Please try again.');
        }
    }

    /**
     * Deletes a specific quarter associated with a simulation.
     *
     * @param  int  $simulation_id
     * @param  int  $quarter_id
     * @return RedirectResponse
     */
    public function removeQuarter($simulation_id, $quarter_id)
    {
        // Find the quarter record for the given simulation and quarter ID
        $quarter = SimulationQuarter::where('id', $quarter_id)
            ->where('simulation_id', $simulation_id)
            ->first();

        if ($quarter) {
            // Delete the record if found
            $quarter->delete();

            // Reorder remaining quarters
            $quarters = SimulationQuarter::where('simulation_id', $simulation_id)
                ->orderBy('id') // or order by start_date if more appropriate
                ->get();

            $counter = 1;
            foreach ($quarters as $qtr) {
                $qtr->quarter_name = $counter;
                $qtr->save();
                $counter++;
            }

            return redirect()
                ->route('admin.simulation.show', $simulation_id)
                ->with('success', 'Quarter deleted successfully.');
        }

        // If no record was found, return with an error
        return redirect()
            ->route('admin.simulation.show', $simulation_id)
            ->with('error', 'Quarter not found.');
    }

    /**
     * Increase Licence associated with a simulation.
     *
     * @return RedirectResponse
     */
    public function increaseLicence(Request $request)
    {
        // Validate request
        $request->validate([
            'licence_count' => 'required|integer|min:1',
            'simulation_id' => 'required|exists:simulations,id',
        ], [
            'licence_count.required' => 'Please enter the number of licences to add.',
            'licence_count.integer' => 'Licence count must be a number.',
            'licence_count.min' => 'Licence count must be at least 1.',
        ]);

        try {
            // Find simulation
            $simulation = Simulation::findOrFail($request->simulation_id);

            // Add requested licence count to existing count
            $simulation->licence_count += (int) $request->licence_count;

            // Save updated simulation
            $simulation->save();

            return redirect()
                ->route('admin.simulation.show', $simulation->id)
                ->with('success', 'Licence count updated successfully.');
        } catch (Exception $e) {
            Log::error('Error increasing licence count: '.$e->getMessage());

            return redirect()
                ->back()
                ->with('error', 'An unexpected error occurred while updating licence count.');
        }
    }

    /**
     * Toggle the AI Analysis status for a simulation.
     *
     * @param  int  $simulation_id
     * @return RedirectResponse
     */
    public function aiAnalysis(Request $request, $simulation_id)
    {
        try {

            $simulation = Simulation::findOrFail($simulation_id);

            // Toggle the value
            $simulation->is_analysis_enabled = ! $simulation->is_analysis_enabled;

            $simulation->save();

            return redirect()
                ->route('admin.simulation.show', $simulation->id)
                ->with('success', 'AI Analysis status updated successfully.');
        } catch (Exception $e) {

            Log::error('Error toggling AI analysis status: '.$e->getMessage());

            return redirect()
                ->back()
                ->with('error', 'An unexpected error occurred while updating AI Analysis status.');
        }
    }
}
