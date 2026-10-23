<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreStudentProfileRequest;
use App\Http\Requests\UpdateStudentProfileRequest;
use App\Models\Student;
use App\Models\StudentProfile;
use App\Services\ActiveUserService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class StudentProfileController extends Controller
{
    protected $activeUserService;

    public function __construct(ActiveUserService $activeUserService)
    {
        $this->activeUserService = $activeUserService;
    }

    /**
     * Display a listing of the resource.
     *
     * @return Response
     */
    public function index()
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @return Response
     */
    public function store(StoreStudentProfileRequest $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @return Response
     */
    public function show(StudentProfile $studentProfile)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @return Response
     */
    public function edit(StudentProfile $studentProfile)
    {
        $activeUser = $this->activeUserService->getActiveUser();

        $user = Student::find($activeUser->id);

        return view('student.profile.edit', compact('user'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  UpdateStudentProfileRequest  $request
     * @param  StudentProfile  $studentProfile
     * @return Response
     */
    public function update(Request $request)
    {
        try {
            // Get currently logged-in user via your session service
            $activeUser = $this->activeUserService->getActiveUser();

            $user = Student::find($activeUser->id);

            if (! $user) {
                return back()->with('error', 'User not found.');
            }

            // Manual validation with single flash message
            if (! $request->filled('first_name')) {
                return back()->with('error', 'First name is required.');
            }

            if (strlen($request->first_name) > 100) {
                return back()->with('error', 'First name cannot exceed 100 characters.');
            }

            if (! $request->filled('last_name')) {
                return back()->with('error', 'Last name is required.');
            }

            if (strlen($request->last_name) > 100) {
                return back()->with('error', 'Last name cannot exceed 100 characters.');
            }

            // Update user profile
            $user->update($request->only('first_name', 'last_name'));

            return back()->with('success', 'Profile updated successfully.');
        } catch (\Exception $e) {
            Log::error('Profile update error: '.$e->getMessage());

            return back()->with('error', 'An unexpected error occurred. Please try again later.');
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @return Response
     */
    public function destroy(StudentProfile $studentProfile)
    {
        //
    }

    public function updatePassword(Request $request)
    {
        try {
            // Get currently logged-in user via your session service
            $activeUser = $this->activeUserService->getActiveUser();

            $user = Student::find($activeUser->id);

            if (! $user) {
                return back()->withErrors(['error' => 'User not found.']);
            }

            // Validate input
            $request->validate([
                'current_password' => 'required',
                'password' => 'required|string|min:8|confirmed',
            ]);

            // Check current password
            if (! Hash::check($request->current_password, $user->password)) {
                return back()->withErrors(['current_password' => 'Current password is incorrect.']);
            }

            // Update password
            $user->update([
                'password' => Hash::make($request->password),
            ]);

            return back()->with('success', 'Password updated successfully.');
        } catch (ValidationException $e) {
            return back()->withErrors($e->validator->errors());
        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'An unexpected error occurred. Please try again later.']);
        }
    }
}
