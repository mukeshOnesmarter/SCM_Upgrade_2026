<?php

namespace App\Http\Controllers;

use App\Models\Admin;
use App\Services\ActiveUserService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AdminLoginController extends Controller
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
        if (session()->has('admin_logged_in')) {
            return redirect()->route('admin.dashboard');
        }

        // OTHERWISE, SHOW THE LOGIN PAGE
        return view('auth.admin.login');
    }

    public function login(Request $request)
    {
        try {

            $request->validate([
                'email' => 'required|email',
                'password' => 'required',
            ]);

            $admin = Admin::where('email', $request->email)->first();

            if (! $admin || ! Hash::check($request->password, $admin->password)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid login credentials',
                ], 401);
            }

            // Only active accounts sign in.
            if (($admin->status ?? 'Active') !== 'Active') {
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
                'faculty.logged_in',
                'faculty.user',
                'faculty.has_course_access',
            ]);

            // STORE SESSION DATA
            session([
                'admin.logged_in' => true,
                'admin.user' => [
                    'first_name' => $admin->first_name,
                    'last_name' => $admin->last_name,
                    'role' => $admin->role,
                    'id' => $admin->id,
                ],
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Login successful',
                'redirect' => route('admin.dashboard'),
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
        session()->forget(['admin.logged_in', 'admin.user']);

        return redirect()->route('admin.login');
    }

    public function dashboard()
    {
        $activeUser = $this->activeUserService->getActiveUser();

        // return view('admin.dashboard', compact('activeUser'));

        return redirect()->route('admin.simulation.index');

    }
}
