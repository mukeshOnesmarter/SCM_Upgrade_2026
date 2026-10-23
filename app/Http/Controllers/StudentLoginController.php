<?php

namespace App\Http\Controllers;

use App\Models\Student;
use App\Services\ActiveUserService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class StudentLoginController extends Controller
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
        // CHECK IF THE STUDENT IS LOGGED IN
        if (session()->has('student_logged_in')) {
            return redirect()->route('student.dashboard');
        }

        // OTHERWISE, SHOW THE LOGIN PAGE
        return view('auth.student.login');
    }

    public function login(Request $request)
    {
        try {
            // VALIDATE INPUT
            $request->validate([
                'email' => 'required|email',
                'password' => 'required',
            ]);

            // CHECK IF STUDENT EXISTS
            $student = Student::where('email', $request->email)->first();

            if (! $student || ! Hash::check($request->password, $student->password)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid login credentials',
                ], 401);
            }

            // Only active accounts sign in.
            if (($student->status ?? 'Active') !== 'Active') {
                return response()->json([
                    'success' => false,
                    'message' => 'This account isn’t active. Please contact your instructor or support.',
                ], 403);
            }

            // A new session ID at sign-in, so an old one can't be reused.
            $request->session()->regenerate();

            // REMOVE OTHER SESSION DATA
            session()->forget([
                'admin.logged_in',
                'admin.user',
                'faculty.logged_in',
                'faculty.user',
                'faculty.has_course_access',
            ]);

            // STORE SESSION DATA
            session([
                'student.logged_in' => true,
                'student.user' => [
                    'first_name' => $student->first_name,
                    'last_name' => $student->last_name,
                    'id' => $student->id,
                ],
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Login successful',
                'redirect' => route('student.dashboard'),
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
        session()->forget(['student.logged_in', 'student.user']);

        return redirect()->route('student.login');
    }

    public function dashboard()
    {
        return redirect()->route('student.simulation.index');
        // return view('student.dashboard');
    }
}
