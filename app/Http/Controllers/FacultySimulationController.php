<?php

namespace App\Http\Controllers;

use App\Jobs\QuarterClosing\DispatchGroupChainsJob;
use App\Jobs\QuarterClosureJob;
use App\Jobs\RunInitializeStudentJob;
use App\Models\Decision;
use App\Models\Decisions\Forecasting;
use App\Models\Decisions\Scorecard;
use App\Models\DemandChannel;
use App\Models\Faculty;
use App\Models\FacultyInvite;
use App\Models\Firm;
use App\Models\Product;
use App\Models\QuarterForecastDemand;
use App\Models\QuarterInitialDemand;
use App\Models\Region;
use App\Models\Simulation;
use App\Models\SimulationFaculty;
use App\Models\SimulationJoinToken;
use App\Models\SimulationQuarter;
use App\Models\SimulationQuarterCustomize;
use App\Models\SimulationTeam;
use App\Models\SimulationTeamMember;
use App\Models\Student;
use App\Models\StudentEnroll;
use App\Models\StudentProfile;
use App\Services\ActiveUserService;
use App\Services\EmergencyProcurementService;
use App\Services\InventoryStockService;
use App\Services\MailjetService;
use App\Services\QualityAnalysis\RegionalSummaryService;
use App\Services\QuarterClosingService;
use App\Support\NewAccountPassword;
use App\Support\Spreadsheet\ReaderEntityFactory;
use Carbon\Carbon;
use Exception;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class FacultySimulationController extends Controller
{
    /**
     * Call Active User Service.
     */
    protected $activeUserService;

    protected $quarterClosingService;

    protected $emergencyProcurementService;

    protected $inventoryStockService;

    protected $mailjetService;

    public function __construct(
        ActiveUserService $activeUserService,
        QuarterClosingService $quarterClosingService,
        EmergencyProcurementService $emergencyProcurementService,
        InventoryStockService $inventoryStockService,
        MailjetService $mailjetService
    ) {
        $this->activeUserService = $activeUserService;
        $this->quarterClosingService = $quarterClosingService;
        $this->emergencyProcurementService = $emergencyProcurementService;
        $this->inventoryStockService = $inventoryStockService;
        $this->mailjetService = $mailjetService;
    }

    /**
     * COMMON HEADER FOR THE SIMULATION, INHERITED BY ALL SIMULATION-RELATED PAGES.
     */
    public function simulationOverview($simulation_id)
    {
        // STEP 1: GET THE DETAILS OF REQUEST SIMULATION / COURSE
        $simulationOverview = Simulation::findOrFail($simulation_id);

        return $simulationOverview;
    }

    /**
     * (1) LISTING OF COURSES AND SIMULATIONS ASSIGNED TO ACTIVE FACULTY.
     */
    public function index()
    {
        $activeUser = $this->activeUserService->getActiveUser();
        $faculty_id = $activeUser->id;
        $simulations = collect();

        // STEP 01: GET ALL SIMULATIONS ASSIGNED TO THIS FACULTY.
        $simulationAssigned = SimulationFaculty::where('faculty_id', $faculty_id)->get();
        // STEP 02: GET ALL SIMULATIONS ASSIGNED AND DEPLOYED BY ADMIN.

        foreach ($simulationAssigned as $item) {

            $check_simulation_published = Simulation::with('simulationTeams')
                ->where('id', $item->simulation_id)
                ->where('is_published', '!=', 'No')
                ->first();

            if ($check_simulation_published) {

                $check_simulation_published->simulationTeams->each(function ($team) use ($faculty_id) {
                    $team->team_members = SimulationTeamMember::where([
                        'simulation_id' => $team->simulation_id,
                        'simulation_team_id' => $team->id,
                        'faculty_id' => $faculty_id,
                    ])->count();
                });

                $simulations[] = $check_simulation_published;
            }
        }

        return view('faculty.simulation.index', compact('simulations'));
    }

    /**
     * (2) DETAILED INFORMATION OF THE COURSE,
     *     INCLUDING GROUPS, STUDENTS, QUARTER FORECAST DEMAND, ETC.
     */
    public function show(Request $request)
    {
        $activeUser = $this->activeUserService->getActiveUser();
        $simulation_id = $request->course_id;

        // STEP 1: CALL COURSE OVERVIEW FUNCTIOM.
        $simulationOverview = $this->simulationOverview($simulation_id);

        try {
            // FETCH SIMULATION WITH EAGER-LOADED RELATIONSHIPS
            $simulation = Simulation::with([
                'simulationFaculties.faculty.profile',
                'simulationTeams',
                'simulationFirms',
                'simulationQuarters',
            ])->with([
                'simulationQuarterCustomize' => function ($query) use ($simulation_id) {
                    $query->where('faculty_id', $this->getOwnerFacultyId($simulation_id));
                },
            ])->findOrFail($simulation_id);

            // FETCH FACULTY-SPECIFIC PUBLISHING STATUS.
            $simulationFaculty = SimulationFaculty::where('simulation_id', $simulation_id)
                ->where('faculty_id', $this->getOwnerFacultyId($simulation_id))
                ->first();

            $simulation->is_published_by_current_faculty = optional($simulationFaculty)->is_published;

            // TEAMMEMBERS
            $simulation->simulationTeams->each(function ($team) use ($simulation_id) {
                $team->teamMembers = SimulationTeamMember::with('student.profile')
                    ->where('simulation_id', $team->simulation_id)
                    ->where('faculty_id', $this->getOwnerFacultyId($simulation_id))
                    ->where('simulation_team_id', $team->group_number)
                    ->get();
            });

            // QUARTERS - SUM FORECAST DEMAND CHANNELS.
            $quarters = $simulation->simulationQuarterCustomize->isNotEmpty()
              ? $simulation->simulationQuarterCustomize
              : $simulation->simulationQuarters;

            $inprocessSet = false;

            foreach ($quarters as $quarter) {
                if (! $inprocessSet && $quarter->end_date >= now()->format('Y-m-d') && is_null($quarter->close_manualy)) {
                    $quarter->inprocess_currently = 'Yes';
                    $inprocessSet = true;
                } else {
                    $quarter->inprocess_currently = 'No';
                }
            }
        } catch (Exception $e) {
            return redirect()->back()->with('error', 'Simulation not found.');
        }

        // STEP 3: LIST OF INVITED STUDENTS FOR SIMULATION.
        $students = StudentEnroll::with('student.profile')
            ->where(['simulation_id' => $simulation_id, 'faculty_id' => $this->getOwnerFacultyId($simulation_id)])
            ->whereNotIn(
                'student_id',
                SimulationTeamMember::where([
                    'simulation_id' => $simulation_id,
                    'faculty_id' => $this->getOwnerFacultyId($simulation_id),
                ])->pluck('student_id')->toArray()
            )->get()
            ->sortBy('student.first_name');

        $tokenData = SimulationJoinToken::firstOrCreate(
            [
                'simulation_id' => $simulation_id,
                'faculty_id' => $this->getOwnerFacultyId($simulation_id),
            ],
            [
                'token' => Str::random(12),
                'share_code' => $simulation->share_code,
                'expires_at' => now()->addMonths(6),
            ]
        );

        $registrationUrl = route('self-student-registration', $tokenData->token);

        // STUDENT COUNTS
        $totalStudentEnroll = StudentEnroll::where([
            'simulation_id' => $simulation_id,
            'faculty_id' => $this->getOwnerFacultyId($simulation_id),
        ])->count();

        $totalStudentAssigned = SimulationTeamMember::where([
            'simulation_id' => $simulation_id,
            'faculty_id' => $this->getOwnerFacultyId($simulation_id),
        ])->count();

        $totalPaidStudents = SimulationTeamMember::where([
            'simulation_id' => $simulation_id,
            'faculty_id' => $this->getOwnerFacultyId($simulation_id),
            'payment_status' => 'paid',
        ])->count();

        $totalUnpaidStudents = SimulationTeamMember::where([
            'simulation_id' => $simulation_id,
            'faculty_id' => $this->getOwnerFacultyId($simulation_id),
            'payment_status' => 'unpaid',
        ])->count();

        return view('faculty.simulation.show', compact(
            'simulationOverview',
            'simulation',
            'simulationFaculty',
            'students',
            'quarters',
            'registrationUrl',
            'totalStudentEnroll',
            'totalStudentAssigned',
            'totalPaidStudents',
            'totalUnpaidStudents'
        ));
    }

    /**
     * (3) INVITE THE STUDENTS TO JOIN THE SIMULATION.
     */
    public function invite(Request $request)
    {
        // STEP 1: VALIDATE THE EMAIL ADDRESS.
        $validatedData = $request->validate([
            'email' => 'required|email',
            'faculty_id' => 'required|exists:faculties,id',
            'simulation_id' => 'required|exists:simulations,id',
        ]);

        // STEP 2: CREATE A NEW FACULTY INVITE RECORD
        $invite = FacultyInvite::create([
            'faculty_id' => $this->getOwnerFacultyId($request->simulation_id),
            'simulation_id' => $request->simulation_id,
            'email' => $validatedData['email'],
        ]);

        // STEP 3: CHECK IF THE RECORD CREATE
        if ($invite) {
            // STEP 3:(A) IF RECORD CREATE.
            $simulation = Simulation::findOrFail($invite->simulation_id);
            // STEP 3:(B) FORMAT DATA TO SENT EMAIL.
            $simulationTitle = $invite->simulation_title = $simulation->title;
            $simulationStartDate = $invite->start_date = Carbon::parse($simulation->start_date)->format('M d, Y');
            $userEmail = $invite->email;
            $password = $invite->password_generated;
            $token = $invite->invite_token;
            // STEP 3:(C) SENT INTRODUCTION E-EMAIL
            try {
                DB::beginTransaction();

                $simulation = Simulation::with('simulationQuarters')->find($request->simulation_id);
                $simulation->course_code = $simulation->share_code;
                $simulation->share_code = Crypt::encryptString($simulation->share_code);

                $faculty = Faculty::find($this->getOwnerFacultyId($request->simulation_id));
                $faculty->faculty_code = Crypt::encryptString($faculty->faculty_code);

                $student_email = $validatedData['email'];

                $emailTemplate = view('emails.simulation-invite-student', compact('simulation', 'faculty', 'student_email'))->render();

                $this->mailjetService->send($validatedData['email'], $validatedData['email'], 'Welcome to FLEXEE - Student Registration', $emailTemplate);

                FacultyInvite::findOrFail($invite->id)->update([
                    'sent_intro_email' => 'Yes',
                ]);

                DB::commit();

                return redirect()->route('faculty.courses.show', $request->simulation_id)
                    ->with('success', 'Invitation Send successfully!');
            } catch (Exception $e) {
                // Log the error
                Log::error('Email sending failed: '.$e->getMessage());

                // If email fails, delete the last inserted record (as email is unique)
                $invite->delete();

                return redirect()->route('faculty.courses.show', $request->simulation_id)
                    ->with('error', 'Failed to send invitation!');
            }
        } else {
            // RECORD NOT CREATE
            return redirect()->route('faculty.courses.show', $request->simulation_id)
                ->with('error', 'Failed to send invitation!');
        }
    }

    /**
     * (4) BULK INVITE.
     */
    public function bulkInvite(Request $request)
    {
        // STEP 1: Validate file upload
        $request->validate([
            'file' => 'required|mimes:xlsx,csv', // no xls support with OpenSpout
            'simulation_id' => 'required|exists:simulations,id',
            'faculty_id' => 'required|exists:faculties,id',
        ]);

        $file = $request->file('file');
        $extension = strtolower($file->getClientOriginalExtension());

        $successCount = 0;
        $errorCount = 0;
        $errors = [];

        try {
            // STEP 2: Pick reader
            if ($extension === 'xlsx') {
                $reader = ReaderEntityFactory::createXLSXReader();
            } elseif ($extension === 'csv') {
                $reader = ReaderEntityFactory::createCSVReader();
            } else {
                throw new Exception("Unsupported file type: {$extension}");
            }

            $reader->open($file->getRealPath());

            foreach ($reader->getSheetIterator() as $sheet) {
                foreach ($sheet->getRowIterator() as $rowIndex => $row) {
                    $cells = $row->toArray();

                    // Skip header row
                    if ($rowIndex === 1) {
                        continue;
                    }

                    $email = isset($cells[1]) ? trim($cells[1]) : null;

                    if (! $email) {
                        $errorCount++;

                        continue;
                    }

                    try {
                        // STEP 3: Validate email + uniqueness
                        $validator = Validator::make([
                            'email' => $email,
                            'faculty_id' => $this->getOwnerFacultyId($request->simulation_id),
                            'simulation_id' => $request->simulation_id,
                        ], [
                            'email' => 'required|email',
                            'faculty_id' => 'required|exists:faculties,id',
                            'simulation_id' => 'required|exists:simulations,id',
                        ]);

                        if ($validator->fails()) {
                            throw new Exception("Invalid data for {$email}");
                        }

                        DB::beginTransaction();

                        // STEP 4: Create FacultyInvite
                        $invite = FacultyInvite::create([
                            'faculty_id' => $this->getOwnerFacultyId($request->simulation_id),
                            'simulation_id' => $request->simulation_id,
                            'email' => $email,
                        ]);

                        if (! $invite) {
                            throw new Exception("Failed to create invite for {$email}");
                        }

                        // STEP 5: Prepare simulation & faculty data
                        $simulation = Simulation::with('simulationQuarters')->findOrFail($request->simulation_id);
                        $simulation->course_code = $simulation->share_code;
                        $simulation->share_code = Crypt::encryptString($simulation->share_code);

                        $faculty = Faculty::findOrFail($this->getOwnerFacultyId($request->simulation_id));
                        $faculty->faculty_code = Crypt::encryptString($faculty->faculty_code);

                        // STEP 6: Prepare & send email
                        $student_email = $email;
                        $emailTemplate = view('emails.simulation-invite-student', compact('simulation', 'faculty', 'student_email'))->render();

                        $this->mailjetService->send($email, $email, 'Welcome to FLEXEE – Student Registration', $emailTemplate);

                        FacultyInvite::findOrFail($invite->id)->update([
                            'sent_intro_email' => 'Yes',
                        ]);

                        DB::commit();
                        $successCount++;
                    } catch (Exception $e) {
                        DB::rollBack();
                        Log::error("Bulk invite failed for {$email}: ".$e->getMessage());
                        $errorCount++;
                        $errors[] = $email;
                    }
                }
            }

            $reader->close();

            return back()->with('success', "Invites sent: {$successCount}, Failed: {$errorCount}")
                ->with('bulkErrors', $errors);
        } catch (Exception $e) {
            Log::error('Error '.$e->getMessage());

            return back()->with('error', 'Bulk invite failed: '.$e->getMessage());
        }
    }

    /**
     * (4) LIST OF INVITEES FOR FACULTY INVITATIONS.
     */
    public function invitees(Request $request)
    {
        $activeUser = $this->activeUserService->getActiveUser();
        $simulation_id = $request->course_id;

        // STEP 1: CHECK IF THE SIMULATION EXISTS.
        Simulation::findOrFail($simulation_id);

        // STEP 2: INHERIT THE COMMON HEADER.
        $simulationOverview = $this->simulationOverview($simulation_id);

        // STEP 3: COLLECT THE LIST OF INVITED STUDENTS FOR SIMULATION.
        $invitees = FacultyInvite::where(['faculty_id' => $this->getOwnerFacultyId($simulation_id), 'simulation_id' => $simulation_id])->get();

        return view('faculty.simulation.invitees', compact('invitees', 'simulationOverview'));
    }

    /**
     * (5) LIST OF STUDENTS ENROLLED IN THE SIMULATION UPON FACULTY INVITATION.
     */
    public function students(Request $request)
    {
        $activeUser = $this->activeUserService->getActiveUser();
        $simulation_id = $request->course_id;

        // STEP 1: CHECK IF THE SIMULATION EXISTS.
        Simulation::findOrFail($simulation_id);

        // STEP 2: INHERIT THE COMMON HEADER.
        $simulationOverview = $this->simulationOverview($simulation_id);

        // STEP 3: LIST OF INVITED STUDENTS FOR SIMULATION.
        $students = StudentEnroll::with([
            'student.profile',
            'teamMember' => function ($q) use ($simulation_id) {
                $q->where('simulation_id', $simulation_id)
                    ->where('faculty_id', $this->getOwnerFacultyId($simulation_id));
            },
        ])
            ->where([
                'simulation_id' => $simulation_id,
                'faculty_id' => $this->getOwnerFacultyId($simulation_id),
            ])
            ->get()
            ->sortBy(function ($item) {

                $group = $item->teamMember->simulation_team_id ?? 9999;
                $name = strtolower($item->student->first_name);

                return sprintf('%04d-%s', $group, $name);
            })
            ->values();

        return view('faculty.simulation.students', compact('simulationOverview', 'students'));
    }

    /**
     * (6) LIST OF FACULTIES WHO HAVE JOINED THIS SIMULATION.
     */
    public function faculties(Request $request)
    {
        $activeUser = $this->activeUserService->getActiveUser();
        $simulation_id = $request->course_id;

        // STEP 1: CHECK IF THE SIMULATION EXISTS.
        Simulation::findOrFail($simulation_id);

        // STEP 2: INHERIT THE COMMON HEADER.
        $simulationOverview = $this->simulationOverview($simulation_id);

        // STEP 3: LIST OF PARTICIPATING FACULTIES FOR SIMULATION.
        $faculties = SimulationFaculty::with('faculty.profile')
            ->where('simulation_id', $simulation_id)->get();

        return view('faculty.simulation.faculties', compact('simulationOverview', 'faculties'));
    }

    /**
     * (7) STUDENTS WHO HAVE JOINED THE SIMULATION—DISTRIBUTE THEM RANDOMLY INTO GROUPS.
     */
    public function studentsDistribute(Request $request)
    {
        try {
            DB::beginTransaction();

            $activeUser = $this->activeUserService->getActiveUser();
            $simulation_id = $request->course_id;

            // STEP 1: CHECK IF THE SIMULATION EXISTS.
            $simulation = Simulation::findOrFail($simulation_id);

            // STEP 2: DELETE THE EXISTING RECORD IF IT HAS ALREADY BEEN CREATED
            SimulationTeamMember::where('simulation_id', $simulation_id)
                ->where('faculty_id', $this->getOwnerFacultyId($simulation_id))
                ->delete();

            // STEP 3: FETCH TEAMS (actual rows) and LICENCE COUNT
            $teams = SimulationTeam::where('simulation_id', $simulation_id)
                ->orderBy('group_number')
                ->get();

            $totalTeams = $teams->count();
            $licenceCount = (int) $simulation->licence_count;

            // We reserve the last team (AiFirm) and the second-last (BaseFirm)
            $reservedCount = 0;

            if ($totalTeams <= $reservedCount) {
                DB::rollBack();

                return redirect()->route('faculty.courses.show', $simulation_id)
                    ->with('error', 'Not enough teams available for distribution (teams are reserved).');
            }

            // Active teams are all except the last two
            $activeTeams = $teams->slice(0, $totalTeams - $reservedCount)->values();
            $activeTeamIds = $activeTeams->pluck('id')->values()->all(); // actual simulation_team ids to use

            $activeTeamsCount = count($activeTeamIds);
            if ($activeTeamsCount === 0) {
                DB::rollBack();

                return redirect()->route('faculty.courses.show', $simulation_id)
                    ->with('error', 'No active teams available for distribution.');
            }

            // STEP 4: FETCH STUDENT IDS (shuffle & limit to licence_count)
            $studentIds = StudentEnroll::where([
                'simulation_id' => $simulation_id,
                'faculty_id' => $this->getOwnerFacultyId($simulation_id),
            ])->pluck('student_id')
                ->shuffle()
                ->take($licenceCount)
                ->values();

            if ($studentIds->isEmpty()) {
                DB::rollBack();

                return redirect()->route('faculty.courses.show', $simulation_id)
                    ->with('error', 'No enrolled students found to distribute.');
            }

            // STEP 4: DIVIDE THE STUDENT IDS INTO GROUPS
            $groups = [];
            $index = 0;
            foreach ($studentIds as $studentId) {
                $groups[$index % $activeTeamsCount][] = $studentId;
                $index++;
            }
            $groups = collect($groups);

            // STEP 5: ENSURE NO DUPLICATES ACROSS GROUPS
            $groupedStudentIds = $groups->values(); // Ensure sequential keys

            // STEP 6: SAVE RECORDS INTO THE SIMULATIONTEAMMEMBERS TABLE
            foreach ($groupedStudentIds as $index => $group) {
                $simulationTeamId = $index + 1; // Assign team IDs starting from 1
                foreach ($group as $studentId) {
                    SimulationTeamMember::create([
                        'simulation_team_id' => $simulationTeamId,
                        'student_id' => $studentId,
                        'simulation_id' => $simulation_id,
                        'faculty_id' => $this->getOwnerFacultyId($simulation_id),
                    ]);
                }
            }

            // INCASE RE-DISTRIBUTION >> STEP 07: IF SIMULATION IS ASSIGNED TO ACTIVE FACULTY
            $simulationFaculties = SimulationFaculty::where([
                'faculty_id' => $this->getOwnerFacultyId($simulation_id),
                'simulation_id' => $simulation_id,
            ])->first();

            if (! $simulationFaculties) {
                return response()->json(['message' => 'Simulation not found or unauthorized.'], 404);
            }

            // STEP 08: IF YES PUBLISH THE SIMULATION
            $updated = $simulationFaculties->update(['is_published' => 'No']);

            if (! $updated) {
                return response()->json(['message' => 'Failed to publish simulation.'], 500);
            }

            DB::commit();

            return redirect()->route('faculty.courses.show', $simulation_id)
                ->with('success', 'The students have been successfully distributed across the groups.');
        } catch (Exception $e) {
            DB::rollBack();

            return response()->json(['error' => 'An error occurred: '.$e->getMessage()], 500);
        }
    }

    /**
     * (8) DETAILED INFORMATION OF THE GROUP,
     *     INCLUDING STUDENTS, ACTIVITY LOG, ETC.
     */
    public function studentsGroup(Request $request)
    {
        $activeUser = $this->activeUserService->getActiveUser();
        $simulation_id = $request->course_id;
        $group_id = $request->group_id;

        // STEP 1: CHECK IF THE SIMULATION EXISTS.
        Simulation::findOrFail($simulation_id);

        // STEP 2: INHERIT THE COMMON HEADER.
        $simulationOverview = $this->simulationOverview($simulation_id);

        $students = SimulationTeamMember::with('student')
            ->where([
                'simulation_id' => $simulation_id,
                'simulation_team_id' => $group_id,
                'faculty_id' => $this->getOwnerFacultyId($simulation_id),
            ])->get();

        return view('faculty.simulation.student-group', compact('simulationOverview', 'students'));
    }

    /**
     * (9) DETAILED PROFILE OF THE STUDENT.
     */
    public function studentProfile(Request $request)
    {
        $activeUser = $this->activeUserService->getActiveUser();
        $simulation_id = $request->course_id;
        $student_id = $request->student_id;

        // STEP 1: CHECK IF THE SIMULATION EXISTS.
        Simulation::findOrFail($simulation_id);

        // STEP 2: INHERIT THE COMMON HEADER.
        $simulationOverview = $this->simulationOverview($simulation_id);

        // STEP 3: CHECK IF THE STUDENT EXISTS IF YES GET PROFILE.
        $student = Student::findOrFail($student_id);
        $student->load('profile');

        $teamMember = SimulationTeamMember::firstWhere([
            'simulation_id' => $simulation_id,
            'student_id' => $student_id,
            'faculty_id' => $this->getOwnerFacultyId($simulation_id),
        ]);

        $student->is_course_joined = $teamMember->student_joined ?? null;
        $student->is_blocked = $teamMember->is_blocked ?? null;
        $student->is_removed = $teamMember->is_removed ?? null;
        $student->payment_status = $teamMember->payment_status ?? null;
        $student->group_assign = $teamMember->simulation_team_id ?? null;
        $student->payment_due_date = $teamMember->payment_due_date
          ? Carbon::parse($teamMember->payment_due_date)->format('m/d/Y')
          : null;

        // Simulation Team
        $teams = SimulationTeam::where('simulation_id', $simulation_id)
            ->orderBy('group_number')
            ->get();

        // Payment Mode:
        $paymentMode = SimulationFaculty::where([
            'simulation_id' => $simulation_id,
            'faculty_id' => $this->getOwnerFacultyId($simulation_id),
        ])->value('payment_mode');

        return view('faculty.simulation.student-profile', compact('simulationOverview', 'student', 'teams', 'paymentMode'));
    }

    /**
     * (10) DETAILED PROFILE OF THE FACULTY.
     */
    public function facultyProfile(Request $request)
    {
        $activeUser = $this->activeUserService->getActiveUser();
        $simulation_id = $request->course_id;
        $faculty_id = $request->faculty_id;

        // STEP 1: CHECK IF THE SIMULATION EXISTS.
        Simulation::findOrFail($simulation_id);

        // STEP 2: INHERIT THE COMMON HEADER.
        $simulationOverview = $this->simulationOverview($simulation_id);

        // STEP 3: CHECK IF THE STUDENT EXISTS IF YES GET PROFILE.
        $faculty = Faculty::findOrFail($faculty_id);
        $faculty->profile;

        return view('faculty.simulation.faculty-profile', compact('simulationOverview', 'faculty'));
    }

    /**
     * (11) DETAILED INFORMATION OF THE QUARTER,
     * INCLUDING DEMAND, DURATION, START AND END DATES, ETC.
     */
    public function quarterInfo(Request $request)
    {
        $activeUser = $this->activeUserService->getActiveUser();
        $simulation_id = $request->course_id;
        $quarter_id = $request->quarter_id;

        // STEP 1: CHECK IF THE SIMULATION EXISTS.
        Simulation::findOrFail($simulation_id);

        // STEP 2: INHERIT THE COMMON HEADER.
        $simulationOverview = $this->simulationOverview($simulation_id);

        // // STEP 3: CHECK IF QUARTER EXISTS
        // Forecasting::where(['simulation_id' => $simulation_id, 'quarter' => $quarter_id])->firstOrFail();

        // STEP 3.1 REGION (TOP LEVEL).
        $forecastDemands = Region::select('id', 'name', 'custome_name')->get();
        foreach ($forecastDemands as $key => $region) {
            // STEP 3.2. PRODUCT (UNDER EACH REGION).
            $region->products = $products = Product::select('id', 'name')->get();
            foreach ($region->products as $key => $product) {
                // STEP 3.3. FIRM (UNDER EACH PRODUCT IN EACH REGION).
                $firmCount = Simulation::where('id', $simulation_id)->first();
                $product->firms = Firm::limit($firmCount->firms)->select('id', 'name', 'custome_name')->get();
                foreach ($product->firms as $key => $firm) {
                    $firm->demandChannels = DemandChannel::select('id', 'title')->get();
                    // STEP 3.4. DEMAND (UNDER EACH FIRM IN EACH PRODUCT AND REGION):
                    foreach ($firm->demandChannels as $key => $channel) {
                        $channel->initialDemand = 20;
                        $channel->forecastDemand = 30;
                        if ($channel->id == 1) {
                            $channel->initialDemand = QuarterInitialDemand::where([
                                'simulation_id' => $simulation_id,
                                'product_id' => $product->id,
                                'region_id' => $region->id,
                                'firm_id' => $firm->id,
                                'quarter' => $quarter_id,
                            ])->value('demand_channel_01');
                            $channel->forecastDemand = Forecasting::where([
                                'simulation_id' => $simulation_id,
                                'product_id' => $product->id,
                                'region_id' => $region->id,
                                'firm_id' => $firm->id,
                                'quarter' => $quarter_id,
                            ])->value('demand_channel_01');
                        } else {
                            $channel->initialDemand = QuarterInitialDemand::where([
                                'simulation_id' => $simulation_id,
                                'product_id' => $product->id,
                                'region_id' => $region->id,
                                'firm_id' => $firm->id,
                                'quarter' => $quarter_id,
                            ])->value('demand_channel_02');
                            $channel->forecastDemand = Forecasting::where([
                                'simulation_id' => $simulation_id,
                                'product_id' => $product->id,
                                'region_id' => $region->id,
                                'firm_id' => $firm->id,
                                'quarter' => $quarter_id,
                            ])->value('demand_channel_02');
                        }
                    }
                }
            }
        }

        $quarterInfo = SimulationQuarter::where(['simulation_id' => $simulation_id, 'quarter_name' => $quarter_id])->firstOrFail();
        $quarterInfo->forecast_channel_01 = QuarterForecastDemand::where([
            'simulation_id' => $simulation_id,
            'quarter' => $quarterInfo->quarter_name,
        ])->sum('demand_channel_01');
        $quarterInfo->forecast_channel_02 = QuarterForecastDemand::where([
            'simulation_id' => $simulation_id,
            'quarter' => $quarterInfo->quarter_name,
        ])->sum('demand_channel_02');

        $summary = app(RegionalSummaryService::class)->regionalSummary($simulation_id, $this->getOwnerFacultyId($simulation_id), $quarter_id);

        return view('faculty.simulation.quarter-detail', compact('simulationOverview', 'forecastDemands', 'quarterInfo', 'summary'));
    }

    /**
     * (12) DEPLOY SIMULATION, AND ALLOW STUDENTS TO PERFROM
     */
    public function deploySimulation($course_id)
    {
        DB::beginTransaction();

        try {
            $activeUser = $this->activeUserService->getActiveUser();
            $faculty_id = $this->getOwnerFacultyId($course_id);

            // STEP 01: Verify simulation is assigned to this faculty
            $simulationFaculties = SimulationFaculty::where([
                'faculty_id' => $faculty_id,
                'simulation_id' => $course_id,
            ])->first();

            if (! $simulationFaculties) {
                return response()->json(['message' => 'Simulation not found or unauthorized.'], 404);
            }

            // STEP 02: Publish the simulation
            $updated = $simulationFaculties->update(['is_published' => now()]);
            if (! $updated) {
                DB::rollBack();

                return response()->json(['message' => 'Failed to publish simulation.'], 500);
            }

            // STEP 04: Allow students to perform the simulation
            SimulationTeamMember::where('simulation_id', $course_id)
                ->where('faculty_id', $faculty_id)
                ->update([
                    'student_allowed' => 'Yes',
                    'student_joined' => Carbon::today()->toDateString(),
                    'payment_due_date' => Carbon::now()->addDays(14)->toDateString(),
                ]);

            // STEP 05: Customize each quarter for this faculty
            $quarters = SimulationQuarter::where('simulation_id', $course_id)->get();
            foreach ($quarters as $quarter) {

                $decisionIds = [];
                if (! empty($quarter->decisions)) {
                    $decisionIds = array_map('intval', explode(',', $quarter->decisions));
                }

                // Convert IDs → full decision JSON objects
                $decisionObjects = Decision::whereIn('id', $decisionIds)
                    ->select('id', 'title', 'slug')
                    ->get()
                    ->map(function ($d) {
                        return [
                            'id' => (int) $d->id,
                            'title' => $d->title,
                            'route' => $d->slug,
                        ];
                    })
                    ->values()
                    ->toArray();

                $data = [
                    'faculty_id' => $faculty_id,
                    'simulation_id' => $quarter->simulation_id,
                    'quarter_name' => $quarter->quarter_name,
                    'start_date' => $quarter->start_date,
                    'end_date' => $quarter->end_date,
                    'start_time' => $quarter->start_time,
                    'end_time' => $quarter->end_time,
                    'decisions' => json_encode($decisionObjects),
                    'status' => $quarter->status,
                    'deleted_at' => $quarter->deleted_at,
                ];

                if (in_array((int) $quarter->quarter_name, [1, 2, 3])) {
                    $data['closing_sync'] = 'yes';
                    $data['sync_date'] = $quarter->end_date.' 23:59:59';
                }

                SimulationQuarterCustomize::create($data);
            }

            // STEP 06: Dispatch jobs: ONE random student per team (since max 10 teams this is efficient)
            $teamIds = SimulationTeamMember::where('simulation_id', $course_id)
                ->where('faculty_id', $faculty_id)
                ->distinct()
                ->pluck('simulation_team_id');

            Simulation::where('id', $course_id)->update([
                'is_deploying' => true,
            ]);

            $initializeJobs = [];

            foreach ($teamIds as $teamId) {
                $member = SimulationTeamMember::where('simulation_id', $course_id)
                    ->where('simulation_team_id', $teamId)
                    ->inRandomOrder()
                    ->first();

                if ($member) {
                    $initializeJobs[] = new RunInitializeStudentJob($course_id, $member->id);
                }
            }

            Bus::batch($initializeJobs)
                ->onQueue('simulation-setup')
                ->then(function () use ($course_id) {
                    Simulation::where('id', $course_id)->update([
                        'is_deploying' => false,
                    ]);
                })
                ->catch(function ($e) use ($course_id) {
                    Simulation::where('id', $course_id)->update([
                        'is_deploying' => false,
                    ]);
                })
                ->dispatch();

            DB::commit();

            return response()->json(['message' => 'Simulation published and quarters customized successfully.']);
        } catch (Exception $e) {
            DB::rollBack();

            return response()->json(['message' => 'An error occurred.'], 500);
        }
    }

    /**
     * (13) Quarters Closed
     */
    public function quartersClosed(Request $request)
    {
        DB::beginTransaction();

        try {
            $course_id = (int) $request->course_id;
            $quarter_id = (int) $request->quarter_id;
            $faculty_id = $this->getOwnerFacultyId($course_id);

            // ✅ Load simulation + timezone
            $simulation = Simulation::findOrFail($course_id);
            $tz = $simulation->timezone ?? 'America/New_York';

            // ✅ Local time (simulation timezone)
            $nowLocal = Carbon::now($tz);
            $todayLocal = $nowLocal->copy()->startOfDay();

            $quarters = SimulationQuarterCustomize::where([
                'simulation_id' => $course_id,
                'faculty_id' => $faculty_id,
            ])->get();

            $quarterOne = $quarters->firstWhere('quarter_name', 1);

            if (! $quarterOne) {
                return response()->json(['error' => 'Quarter 1 not found'], 404);
            }

            $daysBetween = (int) Carbon::parse($quarterOne->start_date)->diffInDays(Carbon::parse($quarterOne->end_date), true);

            $currentQuarter = $quarters->firstWhere('quarter_name', $quarter_id);

            if (! $currentQuarter) {
                return response()->json(['error' => 'Current quarter not found'], 404);
            }

            // IDEMPOTENCY GUARD: Prevent Duplicate Quarter Closure Triggers
            if (
                $currentQuarter->closing_sync === 'yes' &&
                $currentQuarter->sync_date &&
                Carbon::parse($currentQuarter->sync_date, 'UTC')
                    ->gt(Carbon::now('UTC')->subMinutes(5))
            ) {
                return redirect()
                    ->route('faculty.courses.show', ['course_id' => $course_id])
                    ->with('warning', 'Quarter closure already triggered recently. Please wait a few minutes.');
            }

            // Step 1: Manually close the current quarter
            $currentQuarter->update([
                'end_date' => $todayLocal->format('Y-m-d'),
                'end_time' => $nowLocal->format('H:i:s'),
                'close_manualy' => 'Yes',
            ]);

            // Step 2: Get upcoming quarters sorted
            $remainingQuarters = $quarters->filter(function ($q) use ($quarter_id) {
                return $q->quarter_name > $quarter_id;
            })->sortBy('quarter_name')->values();

            $nextStartDate = $todayLocal->copy();

            // Step 3: Handle first upcoming quarter (with time)
            $firstUpcoming = $remainingQuarters->first();

            if ($firstUpcoming) {

                $startDate = $nextStartDate->copy();
                $endDate = $startDate->copy()->addDays($daysBetween);

                $endDateTimeLocal = Carbon::createFromFormat(
                    'Y-m-d H:i:s',
                    $todayLocal->format('Y-m-d').' '.$nowLocal->format('H:i:s'),
                    $tz
                );

                $nextStartTime = $endDateTimeLocal
                    ->copy()
                    ->addMinutes((int) config('app.simulation_synchronization_time', 45));

                $firstUpcoming->update([
                    'start_date' => $startDate->format('Y-m-d'),
                    'end_date' => $endDate->format('Y-m-d'),
                    'start_time' => $nextStartTime->format('H:i:s'),
                    'close_manualy' => null,
                ]);

                $nextStartDate = $endDate->copy()->addDay(); // for the rest
            }

            // Step 4: Update remaining future quarters (without time)
            $remainingWithoutFirst = $remainingQuarters->slice(1); // skip first

            foreach ($remainingWithoutFirst as $quarter) {
                $startDate = $nextStartDate->copy();
                $endDate = $startDate->copy()->addDays($daysBetween);

                $quarter->update([
                    'start_date' => $startDate->format('Y-m-d'),
                    'end_date' => $endDate->format('Y-m-d'),
                    'close_manualy' => null,
                ]);

                $nextStartDate = $endDate->copy()->addDay();
            }

            // Step 5: Update SimulationFaculty Freez Time
            $simulationUpdate = SimulationFaculty::where([
                'simulation_id' => $course_id,
                'faculty_id' => $faculty_id,
            ])->first();

            if ($simulationUpdate) {
                $simulationUpdate->frozen_until = Carbon::now($tz)
                    ->addMinutes(config('app.simulation_synchronization_time', 45));

                $simulationUpdate->save();
            }

            // Step 6: Quarter Calculation Insight
            $groupNumbers = SimulationTeamMember::where([
                'simulation_id' => $course_id,
                'faculty_id' => $faculty_id,
            ])->whereNotNull('student_joined')
                ->pluck('simulation_team_id')
                ->unique()
                ->values();

            $currentQuarter->closing_sync = 'yes';
            $currentQuarter->sync_date = Carbon::now($tz);
            $currentQuarter->save();

            DB::commit();

            Simulation::where('id', $course_id)->update([
                'is_syncing' => true,
            ]);

            try {

                Log::info('Quarter closure chain dispatched', [
                    'simulation_id' => $course_id,
                    'faculty_id' => $faculty_id,
                    'quarter_id' => $quarter_id,
                    'groups/firms' => $groupNumbers->count(),
                ]);

                $groups = $groupNumbers->toArray();

                Bus::chain([
                    new QuarterClosureJob($course_id, $faculty_id, $quarter_id),
                    new DispatchGroupChainsJob($course_id, $faculty_id, $quarter_id, $groups),
                ])->onQueue('quarter-closure')->dispatch();
            } catch (\Throwable $th) {
                Log::error('Failed to dispatch quarter closure chain', [
                    'simulation_id' => $course_id,
                    'faculty_id' => $faculty_id,
                    'quarter_id' => $quarter_id,
                    'error' => $th->getMessage(),
                ]);
            }

            return redirect()
                ->route('faculty.courses.show', ['course_id' => $course_id])
                ->with('message', 'Quarter re-scheduled successfully!');
        } catch (Exception $e) {
            DB::rollBack();

            return redirect()
                ->route('faculty.courses.show', ['course_id' => $course_id])
                ->with('error', 'Failed to close quarter');
        }
    }

    // Add More Quarter
    public function addQuarter(Request $request)
    {
        DB::beginTransaction();

        try {
            $activeUser = $this->activeUserService->getActiveUser();
            $simulation_id = $request->course_id;

            // Fetch last quarter (based on quarter_name)
            $lastQuarter = SimulationQuarterCustomize::where('simulation_id', $simulation_id)
                ->where('faculty_id', $this->getOwnerFacultyId($simulation_id))
                ->orderByDesc('id')
                ->first();

            // Determine new quarter_name (as next number)
            $nextQuarterName = $lastQuarter ? ((int) $lastQuarter->quarter_name + 1) : 1;

            // Determine start date: next day of last quarter end OR today (whichever is later)
            $today = now()->startOfDay();
            if ($lastQuarter && $lastQuarter->end_date) {
                $lastEndDate = Carbon::parse($lastQuarter->end_date)->addDay();
                $start_date = $lastEndDate->greaterThan($today) ? $lastEndDate : $today;
            } else {
                $start_date = $today;
            }

            // Reference Q1 to calculate duration
            $firstQuarter = SimulationQuarterCustomize::where('simulation_id', $simulation_id)
                ->where('quarter_name', 1)
                ->first();

            if (! $firstQuarter || ! $firstQuarter->start_date || ! $firstQuarter->end_date) {
                throw new Exception('Quarter 1 must be scheduled to use it as reference.');
            }

            $q1Start = Carbon::parse($firstQuarter->start_date);
            $q1End = Carbon::parse($firstQuarter->end_date);
            $durationDays = (int) $q1End->diffInDays($q1Start, true);

            $end_date = $start_date->copy()->addDays($durationDays);

            // Save new quarter
            SimulationQuarterCustomize::create([
                'faculty_id' => $this->getOwnerFacultyId($simulation_id),
                'simulation_id' => $simulation_id,
                'quarter_name' => (string) $nextQuarterName,
                'start_date' => $start_date,
                'end_date' => $end_date,
                'start_time' => '10:00:00',
                'end_time' => '18:00:00',
                'is_schedule' => 'Yes',
                'decisions' => 'all',
                'status' => 1,
                'deleted_at' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::commit();

            return redirect()
                ->route('faculty.courses.show', ['course_id' => $simulation_id])
                ->with('success', 'Quarter added successfully!');
        } catch (Exception $e) {
            DB::rollBack();

            return redirect()
                ->route('faculty.courses.show', ['course_id' => $request->course_id])
                ->with('error', 'Failed to add quarter: '.$e->getMessage());
        }
    }

    // Remove Quarter
    public function removeQuarter($course_id, $quarter_id)
    {
        DB::beginTransaction();

        try {
            $activeUser = $this->activeUserService->getActiveUser();

            $quarter = SimulationQuarterCustomize::where([
                'faculty_id' => $this->getOwnerFacultyId($course_id),
                'simulation_id' => $course_id,
                'quarter_name' => $quarter_id,
            ])->first();

            if ($quarter) {
                $quarter->delete();
            }

            DB::commit();

            return redirect()
                ->route('faculty.courses.show', ['course_id' => $course_id])
                ->with('success', 'Quarter deleted successfully!');
        } catch (Exception $e) {
            DB::rollBack();

            return redirect()
                ->route('faculty.courses.show', ['course_id' => $course_id])
                ->with('error', 'Failed to delete quarter: '.$e->getMessage());
        }
    }

    /**
     * (14) Assign Group
     */
    public function assignStudentToGroup(Request $request)
    {
        DB::beginTransaction();

        try {
            $course_id = (int) $request->course_id;
            $faculty_id = $this->getOwnerFacultyId($course_id);
            $group_number = (int) $request->group_number;
            $student_id = (int) $request->student_id;

            // Get licence limit
            $licenceLimit = Simulation::where('id', $course_id)->value('licence_count');

            // Count how many students already assigned
            $assignedCount = SimulationTeamMember::where('simulation_id', $course_id)
                ->where('faculty_id', $faculty_id)
                ->count();

            // If limit reached, redirect with error
            if ($assignedCount >= $licenceLimit) {
                return redirect()
                    ->route('faculty.courses.show', ['course_id' => $course_id])
                    ->with('error', "You have reached the maximum limit of {$licenceLimit} students as per your licence.");
            }

            // Assign student
            SimulationTeamMember::create([
                'simulation_team_id' => $group_number,
                'student_id' => $student_id,
                'simulation_id' => $course_id,
                'faculty_id' => $faculty_id,
                'student_allowed' => 'Yes',
                'payment_due_date' => Carbon::now()->addDays(14)->toDateString(),
            ]);

            DB::commit();

            return redirect()
                ->route('faculty.courses.show', ['course_id' => $course_id])
                ->with('success', 'Student assigned to the group successfully!');
        } catch (Exception $e) {
            DB::rollBack();

            return redirect()
                ->route('faculty.courses.show', ['course_id' => $course_id])
                ->with('error', 'Failed to assign the student to the group.');
        }
    }

    // (15) Remove From Group
    public function removeStudentFromGroup(Request $request)
    {
        DB::beginTransaction();

        try {
            $course_id = (int) $request->course_id;
            $faculty_id = $this->getOwnerFacultyId($course_id);
            $group_number = (int) $request->group_number;
            $student_id = (int) $request->student_id;

            SimulationTeamMember::where([
                'simulation_team_id' => $group_number,
                'student_id' => $student_id,
                'simulation_id' => $course_id,
                'faculty_id' => $faculty_id,
            ])->delete();

            DB::commit();

            return redirect()
                ->route('faculty.courses.show', ['course_id' => $course_id])
                ->with('success', 'Student removed successfully!');
        } catch (Exception $e) {
            DB::rollBack();

            return redirect()
                ->route('faculty.courses.show', ['course_id' => $course_id])
                ->with('error', 'Failed to remove the student.');
        }
    }

    // 16
    public function studResetPassword(Request $request)
    {
        $simulation_id = $request->simulation_id;
        $student_id = $request->student_id;

        try {
            DB::beginTransaction();

            $student = Student::find($student_id);

            if ($student) {
                $newPassword = strtolower($student->first_name.'@'.$student->student_code);

                $student->password = Hash::make($newPassword);
                $student->save();

                DB::commit();

                return redirect()
                    ->route('faculty.courses.studentProfile', [
                        'course_id' => $simulation_id,
                        'student_id' => $student_id,
                    ])->with('success', 'Student password has been reset successfully.');
            } else {
                DB::rollBack();

                return redirect()
                    ->route('faculty.courses.studentProfile', [
                        'course_id' => $simulation_id,
                        'student_id' => $student_id,
                    ])->with('error', 'Student not found.');
            }
        } catch (\Throwable $th) {
            DB::rollBack();

            return redirect()
                ->route('faculty.courses.studentProfile', [
                    'course_id' => $simulation_id,
                    'student_id' => $student_id,
                ])->with('error', 'An error occurred while resetting the password.');
        }
    }

    // 17
    public function toggleTemporaryBlock(Request $request)
    {
        $member = SimulationTeamMember::where('simulation_id', $request->simulation_id)
            ->where('student_id', $request->student_id)
            ->firstOrFail();

        $member->is_blocked = ! $member->is_blocked;
        $member->blocked_at = $member->is_blocked ? now() : null;
        $member->save();

        return back()->with('success', $member->is_blocked ? 'Student blocked.' : 'Student unblocked.');
    }

    // 18
    public function toggleRemoveFromCourse(Request $request)
    {
        $member = SimulationTeamMember::where('simulation_id', $request->simulation_id)
            ->where('student_id', $request->student_id)
            ->firstOrFail();

        $member->is_removed = ! $member->is_removed;
        $member->removed_at = $member->is_removed ? now() : null;
        $member->save();

        return back()->with('success', $member->is_removed ? 'Student removed from course.' : 'Student rejoined.');
    }

    // 19
    public function extendPaymentDueDate(Request $request)
    {
        $member = SimulationTeamMember::where('simulation_id', $request->simulation_id)
            ->where('student_id', $request->student_id)
            ->firstOrFail();

        $currentDueDate = $member->payment_due_date;

        $newDueDate = now()->toDateString(); // default to today
        if ($currentDueDate) {
            $isPast = Carbon::parse($currentDueDate)->lt(now());
            $newDueDate = $isPast
              ? now()->addDays(3)->toDateString()
              : Carbon::parse($currentDueDate)->addDays(3)->toDateString();
        } else {
            $newDueDate = now()->addDays(3)->toDateString();
        }

        $member->payment_due_date = $newDueDate;
        $member->save();

        return back()->with('success', 'Payment due date extended to '.Carbon::parse($newDueDate)->format('F j, Y').'.');
    }

    // 20
    public function extendQtrDuration(Request $request)
    {
        $request->validate([
            'quarter_id' => 'required', // this is quarter_name (string or int)
            'simulation_id' => 'required|exists:simulations,id',
            'days' => 'required|integer|min:1|max:5',
        ]);

        DB::beginTransaction();

        try {
            $facultyId = $this->getOwnerFacultyId($request->simulation_id);
            $extensionDays = (int) $request->days;

            // 1. Fetch current quarter using quarter_name (not id)
            $currentQuarter = SimulationQuarterCustomize::where('simulation_id', $request->simulation_id)
                ->where('faculty_id', $facultyId)
                ->where('quarter_name', $request->quarter_id)
                ->firstOrFail();

            // 2. Extend current quarter's end date
            $currentQuarter->end_date = Carbon::parse($currentQuarter->end_date)->addDays($extensionDays)->format('Y-m-d');
            $currentQuarter->save();

            // 3. Get reference duration from Q1
            $q1 = SimulationQuarterCustomize::where('simulation_id', $request->simulation_id)
                ->where('faculty_id', $facultyId)
                ->where('quarter_name', 1)
                ->first();

            if (! $q1 || ! $q1->start_date || ! $q1->end_date) {
                throw new Exception('Quarter 1 must be scheduled to reference its duration.');
            }

            $q1Duration = (int) Carbon::parse($q1->end_date)->diffInDays(Carbon::parse($q1->start_date), true);

            // 4. Get all quarters after current one by quarter_name (as string/integer)
            $upcomingQuarters = SimulationQuarterCustomize::where('simulation_id', $request->simulation_id)
                ->where('faculty_id', $facultyId)
                ->where('quarter_name', '>', $currentQuarter->quarter_name)
                ->orderBy('quarter_name')
                ->get();

            // 5. Reschedule future quarters based on updated current quarter
            $prevEndDate = Carbon::parse($currentQuarter->end_date);

            foreach ($upcomingQuarters as $quarter) {
                $newStartDate = $prevEndDate->copy()->addDay();
                $newEndDate = $newStartDate->copy()->addDays($q1Duration);

                $quarter->start_date = $newStartDate->format('Y-m-d');
                $quarter->end_date = $newEndDate->format('Y-m-d');
                $quarter->save();

                $prevEndDate = $newEndDate->copy();
            }

            DB::commit();

            return back()->with('success', 'Quarter extended and future schedule adjusted successfully.');
        } catch (Exception $e) {
            DB::rollBack();

            return back()->with('error', 'Failed to extend quarter: '.$e->getMessage());
        }
    }

    // 21
    public function institutePayment($course_id)
    {
        $activeUser = $this->activeUserService->getActiveUser();
        $simulation_id = $course_id;

        // STEP 1: CALL COURSE OVERVIEW FUNCTIOM.
        $simulationOverview = $this->simulationOverview($simulation_id);

        try {

            $students = SimulationTeamMember::with('student')
                ->where('simulation_id', $simulation_id)
                ->where('faculty_id', $this->getOwnerFacultyId($simulation_id))
                ->whereNotNull('student_joined')
                ->get();

            return view('faculty.simulation.institute-payment', compact('simulationOverview', 'students'))
                ->with([
                    'course_fee' => 100,
                ]);
        } catch (Exception $e) {
            return redirect()
                ->route('faculty.courses.show', ['course_id' => $course_id])
                ->with('error', 'Failed to delete quarter: '.$e->getMessage());
        }
    }

    // 22
    public function groupPerformance($course_id, $group_id, $quarter_id = 1)
    {
        try {
            // Get the active faculty user
            $activeUser = $this->activeUserService->getActiveUser();

            // Retrieve simulation overview
            $simulationOverview = $this->simulationOverview($course_id);

            // Ensure quarter_id is integer
            $quarter_id = (int) $quarter_id;

            // Detect protocol (http or https) to avoid Chrome blocking mixed content
            $protocol = request()->isSecure() ? 'https' : 'http';

            // Build Group Performance Metrics URL
            $metrics = sprintf(
                '%s://analysis.flexee.org/api/multi-panel-dashboard?simulation_id=%d&group_id=%d&quarter=1&end_quarter=%d',
                $protocol,
                $course_id,
                $group_id,
                $quarter_id
            );

            // Fetch customized quarters for this faculty in the given simulation
            $quarters = SimulationQuarterCustomize::where([
                'faculty_id' => $this->getOwnerFacultyId($course_id),
                'simulation_id' => $course_id,
            ])->get();

            // Return view with the required data
            return view('faculty.simulation.group-performance', compact(
                'simulationOverview',
                'metrics',
                'quarters'
            ));
        } catch (ModelNotFoundException $e) {
            return redirect()->back()->with('error', 'Simulation data not found.');
        } catch (Exception $e) {
            Log::error('Group Performance Error: '.$e->getMessage(), ['trace' => $e->getTraceAsString()]);

            return redirect()->back()->with('error', 'An unexpected error occurred. Please try again later.');
        }
    }

    // 23
    public function simulationPerformance($course_id, $quarter_id = 1)
    {
        try {
            // Get the active user (faculty)
            $activeUser = $this->activeUserService->getActiveUser();

            // Ensure quarter_id is an integer
            $quarter_id = (int) $quarter_id;

            // Retrieve simulation overview
            $simulationOverview = $this->simulationOverview($course_id);

            // Detect protocol (http or https) to avoid Chrome blocking mixed content
            $protocol = request()->isSecure() ? 'https' : 'http';

            // Build Metrics URL for performance data
            $metrics = sprintf(
                '%s://%s/api/multi-panel-all-firms?simulation_id=%d&quarter=1&end_quarter=%d',
                $protocol,
                config('services.analysis.host'),
                $course_id,
                $quarter_id
            );

            // Fetch customized quarters for this faculty in the given simulation
            $quarters = SimulationQuarterCustomize::where([
                'faculty_id' => $this->getOwnerFacultyId($course_id),
                'simulation_id' => $course_id,
            ])->get();

            // Return view with compacted data
            return view('faculty.simulation.overall-metrics', compact(
                'simulationOverview',
                'metrics',
                'quarters'
            ));
        } catch (ModelNotFoundException $e) {
            return redirect()->back()->with('error', 'Simulation data not found.');
        } catch (Exception $e) {
            Log::error('Simulation Performance Error: '.$e->getMessage(), ['trace' => $e->getTraceAsString()]);

            return redirect()->back()->with('error', 'An unexpected error occurred. Please try again later.');
        }
    }

    // 24 Show Add Student Form
    public function addStudent($course_id)
    {
        try {
            // Get the active user (faculty)
            $activeUser = $this->activeUserService->getActiveUser();

            // Retrieve simulation overview
            $simulationOverview = $this->simulationOverview($course_id);

            return view('faculty.simulation.add-students', compact('simulationOverview'));
        } catch (ModelNotFoundException $e) {
            return redirect()->back()->with('error', 'Simulation data not found.');
        } catch (Exception $e) {
            Log::error('Add Student Page Error: '.$e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);

            return redirect()->back()->with('error', 'An unexpected error occurred. Please try again later.');
        }
    }

    // 25 Store Student
    public function storeStudent(Request $request)
    {
        $request->validate([
            'simulation_id' => 'required|exists:simulations,id',
            'first_name' => 'required|string|max:70',
            'last_name' => 'required|string|max:70',
            'email' => 'required|email|unique:students,email',
            'phone' => 'nullable|string|max:15',
            'emergency_contact' => 'nullable|string|max:15',
        ]);

        DB::beginTransaction();

        try {
            $course_id = $request->simulation_id;
            $activeUser = $this->activeUserService->getActiveUser();
            $simulationOverview = $this->simulationOverview($course_id);

            // Create Student
            $student = Student::create([
                'student_code' => rand(10000000, 99999999),
                'first_name' => $request->first_name,
                'last_name' => $request->last_name,
                'email' => $request->email,
                'password' => NewAccountPassword::placeholder(),
                'phone' => $request->phone,
                'dob' => null, // optional, can be added later
                'gender' => null,
                'department' => null,
                'join_date' => now(),
                'graduation_date' => null,
            ]);

            // Create Student Profile with defaults
            StudentProfile::create([
                'student_id' => $student->id,
                'profile_picture' => null,
                'address' => null,
                'nationality' => null,
                'emergency_contact' => $request->emergency_contact,
                'bio' => null,
                'social_links' => json_encode([]),
                'academic_interests' => null,
                'skills' => null,
            ]);

            // Create Invite Record
            FacultyInvite::create([
                'faculty_id' => $this->getOwnerFacultyId($course_id),
                'simulation_id' => $course_id,
                'email' => $student->email,
                'sent_intro_email' => 'Yes',
                'accepted_at' => now(),
            ]);

            // Enroll Student in Simulation
            StudentEnroll::create([
                'student_id' => $student->id,
                'faculty_id' => $this->getOwnerFacultyId($course_id),
                'simulation_id' => $course_id,
            ]);

            DB::commit();

            // A link to set their own password (there's no shared default any more).
            NewAccountPassword::sendSetLink($student, 'student');

            return redirect()->back()->with('success', 'Student added. We’ve emailed them a link to set their password.');
        } catch (ModelNotFoundException $e) {
            DB::rollBack();

            return redirect()->back()->with('error', 'Simulation data not found.');
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Add Student Error: '.$e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);

            return redirect()->back()->with('error', 'An unexpected error occurred. Please try again later.');
        }
    }

    // 26 Firm Dashboard Performance
    public function firmScorecardSummary($course_id)
    {
        try {
            // Active faculty
            $activeUser = $this->activeUserService->getActiveUser();
            $faculty_id = $this->getOwnerFacultyId($course_id);

            // Header / overview (already used elsewhere in controller)
            $simulationOverview = $this->simulationOverview($course_id);

            // All firms (teams)
            $teams = SimulationTeam::where('simulation_id', $course_id)
                ->orderBy('group_number')
                ->get();

            // ✅ Completed quarters ONLY (from Scorecard, skip quarter 0)
            $completedQuarters = Scorecard::where([
                'simulation_id' => $course_id,
                'faculty_id' => $faculty_id,
            ])
                ->where('quarter', '>', 0)
                ->distinct()
                ->orderBy('quarter')
                ->pluck('quarter');

            $quarterWiseData = [];

            foreach ($completedQuarters as $quarter_id) {

                // Fetch all scorecard rows for this quarter (single query)
                $rows = Scorecard::where([
                    'simulation_id' => $course_id,
                    'faculty_id' => $faculty_id,
                    'quarter' => $quarter_id,
                ])
                    ->where('sub_measure', 'Overall Performance')
                    ->get();

                $firmPerformance = [];

                foreach ($teams as $team) {

                    $group_id = $team->group_number;

                    $firmPerformance[$group_id] = [
                        'name' => $team->team_name ?? 'Firm '.$group_id,
                        'financial' => 0,
                        'operational' => 0,
                        'customer' => 0,
                        'external' => 0,
                        'weighted' => 0,
                    ];

                    // Read SINGLE row per category (NO SUM)
                    foreach (['financial', 'operational', 'customer', 'external'] as $category) {

                        $row = $rows->where('group_id', $group_id)
                            ->where('category', $category)
                            ->first();

                        $firmPerformance[$group_id][$category] = round($row->your_firm ?? 0, 2);
                    }

                    // Summary / Weighted score
                    $summaryRow = $rows->where('group_id', $group_id)
                        ->where('category', 'summary')
                        ->first();

                    $firmPerformance[$group_id]['weighted'] = round($summaryRow->your_firm ?? 0, 2);
                }

                // Determine winners for this quarter
                $winners = [];
                foreach (['financial', 'operational', 'customer', 'external', 'weighted'] as $metric) {
                    $maxValue = collect($firmPerformance)->max($metric);
                    $winner = collect($firmPerformance)->firstWhere($metric, $maxValue);
                    $winners[$metric] = $winner['name'] ?? '-';
                }

                $quarterWiseData[] = [
                    'quarter' => $quarter_id,
                    'firms' => $firmPerformance,
                    'winners' => $winners,
                ];
            }

            return view('faculty.simulation.scorecard-summary', compact(
                'simulationOverview',
                'quarterWiseData'
            ));
        } catch (Exception $e) {
            Log::error('Faculty Scorecard Summary Error: '.$e->getMessage());

            return redirect()->back()->with('error', 'Unable to load scorecard summary.');
        }
    }

    // 27 Submit Institute Payment
    public function submitInstitutePayment(Request $request, $course_id)
    {
        $activeUser = $this->activeUserService->getActiveUser();
        $simulation_id = $course_id;

        $request->validate([
            'institute_name' => 'required|string|max:255',
            'acceptance' => 'accepted',
        ]);

        DB::beginTransaction();

        try {
            // 1️⃣ Update simulation-level payment status
            Simulation::where('id', $simulation_id)
                ->update([
                    'payment_status' => 'paid',
                ]);

            // 2️⃣ Update faculty payment mode
            SimulationFaculty::where('simulation_id', $simulation_id)
                ->where('faculty_id', $this->getOwnerFacultyId($simulation_id))
                ->update([
                    'payment_mode' => 'institute',
                ]);

            // 3️⃣ Mark all students as paid
            SimulationTeamMember::where('simulation_id', $simulation_id)
                ->where('faculty_id', $this->getOwnerFacultyId($simulation_id))
                ->update([
                    'payment_status' => 'paid',
                ]);

            DB::commit();

            return redirect()
                ->route('faculty.courses.show', ['course_id' => $course_id])
                ->with('success', 'Institute payment submitted successfully.');
        } catch (Exception $e) {
            DB::rollBack();

            return back()->with('error', 'Something went wrong while submitting institute payment.');
        }
    }

    // 28 Change Student Group (After Deployment)
    public function changeStudentGroup(Request $request)
    {
        $request->validate([
            'simulation_id' => 'required|exists:simulations,id',
            'student_id' => 'required|exists:students,id',
            'new_group_id' => 'required|integer',
        ]);

        DB::beginTransaction();

        try {

            $faculty_id = $this->getOwnerFacultyId($request->simulation_id);
            $simulationId = $request->simulation_id;
            $studentId = $request->student_id;
            $newGroupId = $request->new_group_id;

            // Prevent change during syncing
            $isSyncing = Simulation::where('id', $simulationId)->value('is_syncing');
            if ($isSyncing) {
                return back()->with('error', 'Simulation is currently syncing. Please try later.');
            }

            $member = SimulationTeamMember::where([
                'simulation_id' => $simulationId,
                'faculty_id' => $faculty_id,
                'student_id' => $studentId,
            ])->firstOrFail();

            // Prevent moving removed students
            if ($member->is_removed) {
                return back()->with('error', 'Removed students cannot be reassigned.');
            }

            // 🚨 NEW CHECK: Count students in current group
            $currentGroupCount = SimulationTeamMember::where([
                'simulation_id' => $simulationId,
                'faculty_id' => $faculty_id,
                'simulation_team_id' => $member->simulation_team_id,
            ])->count();

            if ($currentGroupCount <= 1) {
                return back()->with(
                    'error',
                    'You cannot move this student. A group must have at least one student.'
                );
            }

            // Prevent moving to same group
            if ($member->simulation_team_id == $newGroupId) {
                return back()->with('error', 'Student is already in this group.');
            }

            // Update group
            $member->simulation_team_id = $newGroupId;
            $member->save();

            DB::commit();

            return back()->with('success', 'Student team changed successfully.');
        } catch (Exception $e) {

            DB::rollBack();

            return back()->with('error', 'Failed to change student team.');
        }
    }

    // 29
    public function togglePaymentStatus(Request $request)
    {
        $member = SimulationTeamMember::where('simulation_id', $request->simulation_id)
            ->where('student_id', $request->student_id)
            ->firstOrFail();

        if ($member->payment_status === 'paid') {
            $member->payment_status = 'unpaid';
        } else {
            $member->payment_status = 'paid';
        }

        $member->save();

        return back()->with('success', $member->payment_status === 'paid' ? 'Student marked as paid.' : 'Student marked as unpaid.');
    }

    // 30
    public function permanentDeleteStudent(Request $request)
    {
        DB::beginTransaction();

        try {

            $simulation_id = (int) $request->simulation_id;
            $student_id = (int) $request->student_id;
            $faculty_id = $this->getOwnerFacultyId($simulation_id);

            // Get the team member record
            $member = SimulationTeamMember::where([
                'simulation_id' => $simulation_id,
                'student_id' => $student_id,
                'faculty_id' => $faculty_id,
            ])->firstOrFail();

            // Count students in the same group
            $currentGroupCount = SimulationTeamMember::where([
                'simulation_id' => $simulation_id,
                'faculty_id' => $faculty_id,
                'simulation_team_id' => $member->simulation_team_id,
            ])->count();

            // Prevent deleting the last student in a group
            if ($currentGroupCount <= 1) {
                return back()->with(
                    'error',
                    'You cannot delete this student. A group must have at least one student.'
                );
            }

            // Delete from simulation_team_members
            SimulationTeamMember::where([
                'simulation_id' => $simulation_id,
                'student_id' => $student_id,
                'faculty_id' => $faculty_id,
            ])->delete();

            // Delete from student_enrolls
            StudentEnroll::where([
                'simulation_id' => $simulation_id,
                'student_id' => $student_id,
                'faculty_id' => $faculty_id,
            ])->delete();

            DB::commit();

            return redirect()
                ->route('faculty.courses.show', ['course_id' => $simulation_id])
                ->with('success', 'Student permanently removed from this simulation.');
        } catch (Exception $e) {
            DB::rollBack();

            return back()->with('error', 'Failed to permanently delete the student.');
        }
    }

    // 31 Add Firm
    public function addFirm(Request $request)
    {
        DB::beginTransaction();

        try {
            $course_id = (int) $request->course_id;
            $faculty_id = $this->getOwnerFacultyId($course_id);

            // 1. Get simulation
            $simulation = Simulation::findOrFail($course_id);

            // 2. Verify simulation belongs to current faculty
            $simulationFaculty = SimulationFaculty::where([
                'simulation_id' => $course_id,
                'faculty_id' => $faculty_id,
            ])->first();

            if (! $simulationFaculty) {
                DB::rollBack();

                return back()->with(
                    'error',
                    'Simulation not found or unauthorized.'
                );
            }

            // 3. Do not allow adding firms after deployment
            if ($simulationFaculty->is_published != 'No') {

                DB::rollBack();

                return back()->with(

                    'error',

                    'Firms cannot be added after the simulation has been deployed.'

                );
            }

            // 4. Get the highest existing group number
            $lastGroupNumber = SimulationTeam::where(
                'simulation_id',
                $course_id
            )->max('group_number');

            $newGroupNumber = ((int) $lastGroupNumber) + 1;

            // 5. Create the new simulation team / firm
            SimulationTeam::create([
                'simulation_id' => $course_id,
                'group_number' => $newGroupNumber,
                'team_name' => (string) $newGroupNumber,
                'status' => 1,
                'deleted_at' => 0,
            ]);

            // 6. Update simulation team count
            $simulation->teams = ((int) $simulation->teams) + 1;
            $simulation->save();

            DB::commit();

            return redirect()
                ->route('faculty.courses.show', [
                    'course_id' => $course_id,
                ])
                ->with(
                    'success',
                    "Firm {$newGroupNumber} added successfully."
                );
        } catch (Exception $e) {

            DB::rollBack();

            Log::error('Add Firm Error', [
                'simulation_id' => $request->course_id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return back()->with(
                'error',
                'Failed to add firm.'
            );
        }
    }

    // 32 Remove Firm
    public function removeFirm(Request $request)
    {
        DB::beginTransaction();

        try {
            $course_id = (int) $request->course_id;
            $faculty_id = $this->getOwnerFacultyId($course_id);

            // 1. Get simulation
            $simulation = Simulation::findOrFail($course_id);

            // 2. Verify simulation belongs to current faculty
            $simulationFaculty = SimulationFaculty::where([
                'simulation_id' => $course_id,
                'faculty_id' => $faculty_id,
            ])->first();

            if (! $simulationFaculty) {
                DB::rollBack();

                return back()->with(
                    'error',
                    'Simulation not found or unauthorized.'
                );
            }

            // 3. Do not allow removing firms after deployment
            if ($simulationFaculty->is_published != 'No') {
                DB::rollBack();

                return back()->with(
                    'error',
                    'Firms cannot be removed after the simulation has been deployed.'
                );
            }

            // 4. Get the last actual firm
            $lastTeam = SimulationTeam::where(
                'simulation_id',
                $course_id
            )
                ->orderByDesc('group_number')
                ->first();

            if (! $lastTeam) {
                DB::rollBack();

                return back()->with(
                    'error',
                    'No firm is available to remove.'
                );
            }

            $groupNumber = (int) $lastTeam->group_number;

            // 5. Check whether students are assigned to this firm
            $studentCount = SimulationTeamMember::where([
                'simulation_id' => $course_id,
                'simulation_team_id' => $groupNumber,
                'faculty_id' => $faculty_id,
            ])->count();

            if ($studentCount > 0) {
                DB::rollBack();

                return back()->with(
                    'error',
                    "Firm {$groupNumber} cannot be removed because students are assigned to it."
                );
            }

            // 6. Remove the actual team
            $lastTeam->delete();

            // 7. Decrease simulation team count
            $simulation->teams = max(
                0,
                ((int) $simulation->teams) - 1
            );

            $simulation->save();

            DB::commit();

            return redirect()
                ->route('faculty.courses.show', [
                    'course_id' => $course_id,
                ])
                ->with(
                    'success',
                    "Firm {$groupNumber} removed successfully."
                );
        } catch (Exception $e) {

            DB::rollBack();

            Log::error('Remove Firm Error', [
                'simulation_id' => $request->course_id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return back()->with(
                'error',
                'Failed to remove firm.'
            );
        }
    }

    // 33
    private function getOwnerFacultyId($simulation_id)
    {
        return SimulationFaculty::where('simulation_id', $simulation_id)
            ->where('role', 'faculty')
            ->value('faculty_id');
    }

    // **** REMOVE LATER (TESTING PURPOSE ONLY) */
    public function inviteStudents(Request $request)
    {
        try {
            DB::beginTransaction();

            $activeUser = $this->activeUserService->getActiveUser();

            // DELETE EXISTING RECORDS.
            FacultyInvite::where('faculty_id', $this->getOwnerFacultyId($request->simulation_id))
                ->where('simulation_id', $request->simulation_id)
                ->delete();

            StudentEnroll::where('faculty_id', $this->getOwnerFacultyId($request->simulation_id))
                ->where('simulation_id', $request->simulation_id)
                ->delete();

            // Up to 30 test students (is_test). The old code took ids 3 to 200,
            // which on a live site are real students.
            $students = Student::where('is_test', true)
                ->inRandomOrder()
                ->limit(30)
                ->get();

            foreach ($students as $student) {
                FacultyInvite::create([
                    'faculty_id' => $this->getOwnerFacultyId($request->simulation_id),
                    'simulation_id' => $request->simulation_id,
                    'email' => $student->email,
                    'sent_intro_email' => 'Yes',
                    'accepted_at' => now(),
                ]);

                StudentEnroll::create([
                    'student_id' => $student->id,
                    'faculty_id' => $this->getOwnerFacultyId($request->simulation_id),
                    'simulation_id' => $request->simulation_id,
                ]);
            }

            DB::commit();

            return response()->json(['message' => 'Invite sent and student enrolled successfully!'], 200);
        } catch (Exception $e) {
            DB::rollBack();

            return response()->json(['error' => 'An error occurred: '.$e->getMessage()], 500);
        }
    }

    public function clearInvite(Request $request)
    {
        try {

            DB::beginTransaction();

            $activeUser = $this->activeUserService->getActiveUser();
            // DELETE EXISTING RECORDS.
            FacultyInvite::where('faculty_id', $this->getOwnerFacultyId($request->simulation_id))
                ->where('simulation_id', $request->simulation_id)
                ->delete();

            StudentEnroll::where('faculty_id', $this->getOwnerFacultyId($request->simulation_id))
                ->where('simulation_id', $request->simulation_id)
                ->delete();

            SimulationTeamMember::where('faculty_id', $this->getOwnerFacultyId($request->simulation_id))
                ->where('simulation_id', $request->simulation_id)
                ->delete();

            DB::commit();

            return response()->json(['message' => 'Faculty invite cleared successfully!'], 200);
        } catch (Exception $e) {
            DB::rollBack();

            return response()->json(['error' => 'Failed to truncate tables: '.$e->getMessage()], 500);
        }
    }
}
