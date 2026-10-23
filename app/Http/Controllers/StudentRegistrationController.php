<?php

namespace App\Http\Controllers;

use App\Models\Faculty;
use App\Models\FacultyInvite;
use App\Models\Simulation;
use App\Models\SimulationFaculty;
use App\Models\SimulationJoinToken;
use App\Models\Student;
use App\Models\StudentEnroll;
use App\Models\StudentProfile;
use App\Services\MailjetService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class StudentRegistrationController extends Controller
{
    protected $mailjetService;

    public function __construct(MailjetService $mailjetService)
    {

        $this->mailjetService = $mailjetService;
    }

    /**
     * Show the student registration form after validating faculty and simulation codes.
     * Validates the presence and validity of the faculty code and simulation code.
     */
    public function studentRegistration(Request $request)
    {
        $faculty = Faculty::where('faculty_code', Crypt::decryptString($request->facultycode))->first();
        $simulation = Simulation::where('share_code', Crypt::decryptString($request->simulationcode))->first();
        $student_email = Crypt::decryptString($request->studentemail);

        if (! $faculty || ! $simulation) {
            abort(404, 'Invalid faculty or simulation code.');
        }

        $simulationAssign = SimulationFaculty::where([
            'simulation_id' => $simulation->id,
            'faculty_id' => $faculty->id,
        ])->first();

        if (! $simulationAssign) {
            abort(404, 'Invalid faculty or simulation code.');
        }

        return view('student.registration.registration', [
            'faculty' => $faculty,
            'simulation' => $simulation,
            'student_email' => $student_email,
        ]);
    }

    /**
     * Handle the submission of the student registration form.
     * Validates form input, creates a new student, and enrolls them in the simulation.
     */
    public function saveStudentRegistration(Request $request)
    {
        try {
            DB::beginTransaction();

            // STEP 0: Validate Required
            $validator = Validator::make($request->all(), [
                'simulationCode' => 'required',
                'first_name' => 'required|string|max:255',
                'last_name' => 'required|string|max:255',
                'email' => 'required|email',
                'password' => [
                    'required',
                    'string',
                    'min:8',
                    'regex:/^(?=.*[a-zA-Z])(?=.*\d)(?=.*[\W_]).+$/',
                ],
                'simulation_id' => 'required|exists:simulations,id',
                'faculty_id' => 'required|exists:faculties,id',
            ], [
                'password.regex' => 'Password must contain at least one letter, one number, and one special character.',
            ]);

            if ($validator->fails()) {
                return back()->withErrors($validator)->withInput();
            }

            // STEP 1: Validate Simulation Code
            if ($request->input('simulationCode') !== $request->input('simulation_code')) {
                return back()->with('invalidCode', "The course code <strong>{$request->input('simulationCode')}</strong> you entered is not recognized.")->withInput();
            }

            // STEP 2: Check if student already exists
            $existingStudent = Student::where('email', $request->email)->first();

            /**
             * ==================================================
             * CASE A: EXISTING STUDENT → ACCEPT INVITE
             * ==================================================
             */
            if ($existingStudent) {

                $alreadyEnrolled = StudentEnroll::where('student_id', $existingStudent->id)
                    ->where('simulation_id', $request->simulation_id)
                    ->exists();

                if ($alreadyEnrolled) {
                    DB::rollBack();

                    return back()
                        ->with('alreadyEnrolled', true);
                }

                // Enroll student into simulation
                StudentEnroll::create([
                    'student_id' => $existingStudent->id,
                    'simulation_id' => $request->simulation_id,
                    'faculty_id' => $request->faculty_id,
                ]);

                // Mark invite as accepted
                FacultyInvite::where('faculty_id', $request->faculty_id)
                    ->where('simulation_id', $request->simulation_id)
                    ->where('email', $request->email)
                    ->whereNull('accepted_at')
                    ->update([
                        'accepted_at' => now(),
                    ]);

                DB::commit();

                return back()
                    ->with('inviteAccepted', true);
            }

            /**
             * ==================================================
             * CASE B: NEW STUDENT → REGISTER + ENROLL
             * ==================================================
             */
            $student = Student::create([
                'first_name' => $request->first_name,
                'last_name' => $request->last_name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'join_date' => Carbon::now()->toDateString(),
                'status' => 'Inactive',
            ]);

            // STEP 3: Create Student Profile
            StudentProfile::create([
                'student_id' => $student->id,
            ]);

            // STEP 3: Create Student Enroll record
            StudentEnroll::create([
                'student_id' => $student->id,
                'simulation_id' => $request->simulation_id,
                'faculty_id' => $request->faculty_id,
            ]);

            // STEP 4: Mark invite as accepted
            FacultyInvite::where('faculty_id', $request->faculty_id)
                ->where('simulation_id', $request->simulation_id)
                ->where('email', $request->email)
                ->whereNull('accepted_at')
                ->update([
                    'accepted_at' => now(),
                ]);

            // SEND CONFIRMATION E-MAIL
            $studentName = trim(ucfirst($request->first_name).' '.ucfirst($request->last_name));
            $loginUrl = config('app.host_url').'/student/login';
            $userEmail = $request->email;
            $userPassword = $request->password;
            $accountValidateUrl = config('app.host_url').'/account-verified/'.Crypt::encryptString($student->email).'/'.Crypt::encryptString($student->student_code);

            $emailTemplate = view('emails.account-validate', compact('studentName', 'loginUrl', 'userEmail', 'userPassword', 'accountValidateUrl'))->render();
            $this->mailjetService->send($userEmail, $userEmail, 'FLEXEE Registration Confirmation', $emailTemplate);

            DB::commit();

            return back()->with('accountCreated', "We’ve sent a confirmation email to <strong>$userEmail</strong>");
        } catch (ValidationException $e) {
            return redirect()->back()->withErrors($e->validator)->withInput();
        } catch (\Exception $e) {
            Log::error('Student registration error: '.$e->getMessage());

            return redirect()->route('student-registration', [
                'facultycode' => Crypt::encryptString($request->faculty_code),
                'simulationcode' => Crypt::encryptString($request->simulation_code),
            ])->with('error', 'An unexpected error occurred. Please try again later.');
        }
    }

    /**
     * Handle student account verification.
     *
     * This method decrypts the email and student code from the request, verifies the student,
     * and activates the account by updating the student's status to 'Active'.
     * If the verification fails or the student is not found, a 404 error is returned.
     *
     * @return View|Response
     */
    public function accountVerified(Request $request)
    {
        try {
            $student = Student::where([
                'email' => Crypt::decryptString($request->email),
                'student_code' => Crypt::decryptString($request->token),
            ])->first();

            if (! $student) {
                abort(404, 'Invalid faculty or simulation code.');
            }

            $student->update(['status' => 'Active']);

            return view('student.registration.account-verified');
        } catch (\Exception $e) {
            abort(404, 'Invalid or tampered verification link.');
        }
    }

    public function emailView()
    {
        $simulation = Simulation::with('simulationQuarters')->find(1);
        $faculty = Faculty::find(1);

        return view('emails.simulation-invite-student', compact('simulation', 'faculty'));
    }

    // OPEN REGISTRATION (Self Join Link)
    public function showDirectRegistration($token)
    {
        $tokenData = SimulationJoinToken::where('token', $token)
            ->where(function ($q) {
                $q->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            })
            ->firstOrFail();

        $faculty = Faculty::findOrFail($tokenData->faculty_id);

        $simulation = Simulation::where('id', $tokenData->simulation_id)
            ->where('share_code', $tokenData->share_code)
            ->firstOrFail();

        $simulationAssign = SimulationFaculty::where([
            'simulation_id' => $simulation->id,
            'faculty_id' => $faculty->id,
        ])->first();

        if (! $simulationAssign) {
            abort(404, 'Invalid faculty or simulation.');
        }

        return view('student.registration.open-registration', [
            'faculty' => $faculty,
            'simulation' => $simulation,
        ]);
    }

    public function storeDirectRegistration(Request $request)
    {
        DB::beginTransaction();

        try {

            $request->validate([
                'first_name' => 'required|string|max:255',
                'last_name' => 'required|string|max:255',
                'email' => 'required|email|max:255',
                'password' => 'required|min:8',
                'faculty_id' => 'required|exists:faculties,id',
                'simulation_id' => 'required|exists:simulations,id',
                'simulation_code' => 'required|string',
            ]);

            // Verify simulation using share_code
            $simulation = Simulation::where('id', $request->simulation_id)
                ->where('share_code', $request->simulation_code)
                ->first();

            if (! $simulation) {
                DB::rollBack();

                return back()->with('error', 'Invalid registration link.');
            }

            // Verify simulation Assigned to faculty
            $assignedFaculty = SimulationFaculty::where('faculty_id', $request->faculty_id)
                ->where('simulation_id', $request->simulation_id)
                ->exists();

            if (! $assignedFaculty) {
                DB::rollBack();

                return back()->with('error', 'Invalid registration link.');
            }

            // Get or create student
            $student = Student::where('email', $request->email)->first();

            if (! $student) {
                $student = Student::create([
                    'first_name' => $request->first_name,
                    'last_name' => $request->last_name,
                    'email' => $request->email,
                    'password' => Hash::make($request->password),
                    'join_date' => now()->toDateString(),
                    'status' => 'Inactive',
                ]);

                StudentProfile::create([
                    'student_id' => $student->id,
                ]);
            }

            // Check already enrolled
            $alreadyEnrolled = StudentEnroll::where('student_id', $student->id)
                ->where('simulation_id', $simulation->id)
                ->exists();

            if ($alreadyEnrolled) {
                DB::rollBack();

                return back()->with('alreadyEnrolled', true);
            }

            // Enroll student
            StudentEnroll::create([
                'student_id' => $student->id,
                'simulation_id' => $simulation->id,
                'faculty_id' => $request->faculty_id,
            ]);

            // FACULTY INVITE RECORD
            $invite = FacultyInvite::firstOrCreate(
                [
                    'faculty_id' => $request->faculty_id,
                    'simulation_id' => $simulation->id,
                    'email' => $student->email,
                ]
            );

            if (! $invite->accepted_at) {
                $invite->update([
                    'accepted_at' => now(),
                ]);
            }

            DB::commit();

            return back()
                ->with('accountCreated', true);
        } catch (\Exception $e) {

            DB::rollBack();

            return back()->with('error', 'Something went wrong. Please try again.');
        }
    }
}
