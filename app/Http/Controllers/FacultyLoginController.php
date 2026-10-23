<?php

namespace App\Http\Controllers;

use App\Models\Faculty;
use App\Models\FacultyInvite;
use App\Models\Simulation;
use App\Models\SimulationFaculty;
use App\Models\Student;
use App\Models\StudentEnroll;
use App\Services\ActiveUserService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class FacultyLoginController extends Controller
{
    /**
     * Call Active User Service.
     */
    protected $activeUserService;

    public function __construct(ActiveUserService $activeUserService)
    {
        $this->activeUserService = $activeUserService;
    }

    public function index()
    {
        // CHECK IF THE FACULTY IS LOGGED IN
        if (session()->has('faculty_logged_in')) {
            return redirect()->route('faculty.dashboard');
        }

        // OTHERWISE, SHOW THE LOGIN PAGE
        return view('auth.faculty.login');
    }

    public function login(Request $request)
    {
        try {

            $request->validate([
                'email' => 'required|email',
                'password' => 'required',
            ]);

            $faculty = Faculty::where('email', $request->email)->first();

            if (! $faculty || ! Hash::check($request->password, $faculty->password)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid login credentials',
                ], 401);
            }

            // Only active accounts sign in.
            if (($faculty->status ?? 'Active') !== 'Active') {
                return response()->json([
                    'success' => false,
                    'message' => 'This account isn’t active. Please contact your instructor or support.',
                ], 403);
            }

            // A new session ID at sign-in, so an old one can't be reused.
            $request->session()->regenerate();

            // REMOVE OTHER SESSION DATA
            session()->forget([
                'student.logged_in',
                'student.user',
                'admin.logged_in',
                'admin.user',
            ]);

            // STORE SESSION DATA
            session([
                'faculty.logged_in' => true,
                'faculty.user' => [
                    'first_name' => $faculty->first_name,
                    'last_name' => $faculty->last_name,
                    'id' => $faculty->id,
                ],
                'faculty.has_course_access' => SimulationFaculty::where('faculty_id', $faculty->id)->exists(),

            ]);

            return response()->json([
                'success' => true,
                'message' => 'Login successful',
                'redirect' => route('faculty.dashboard'),
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->validator->errors()->first(),
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An unexpected error occurred. Please try again later.',
            ], 500);
        }
    }

    public function logout()
    {
        session()->forget(['faculty.logged_in', 'faculty.user', 'faculty.has_course_access']);

        return redirect()->route('faculty.login');
    }

    public function dashboard()
    {
        $activeUser = $this->activeUserService->getActiveUser();

        if (! session('faculty.has_course_access')) {
            return view('faculty.dashboard.less-access');
        } else {
            // return view('faculty.dashboard.full-access');
            return redirect()->route('faculty.courses.index');
        }
    }

    /**
     * LIST OF INVITEES FOR FACULTY INVITATIONS.
     */
    public function invitees()
    {
        $activeUser = $this->activeUserService->getActiveUser();

        $invitees = FacultyInvite::with('simulation')
            ->where(['faculty_id' => $activeUser->id])->get();

        return view('faculty.invitees', compact('invitees'));
    }

    /**
     * LIST OF STUDENTS ENROLLED IN THE SIMULATION UPON FACULTY INVITATION.
     */
    public function students()
    {
        $activeUser = $this->activeUserService->getActiveUser();
        // LIST OF STUDENTS
        $students = StudentEnroll::with('Student.profile', 'simulation')
            ->where(['faculty_id' => $activeUser->id])->simplePaginate(7);

        return view('faculty.students', compact('students'));
    }

    /**
     * DETAILED PROFILE OF THE STUDENT.
     */
    public function studentDetail(Request $request)
    {
        $student = Student::findOrFail($request->student_id);
        $student->profile;

        return view('faculty.student-profile', compact('student'));
    }
}
