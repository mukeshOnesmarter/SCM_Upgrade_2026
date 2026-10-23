<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreFacultyProfileRequest;
use App\Http\Requests\UpdateFacultyProfileRequest;
use App\Models\Faculty;
use App\Models\FacultyProfile;
use App\Services\ActiveUserService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class FacultyProfileController extends Controller
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
    public function store(StoreFacultyProfileRequest $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @return Response
     */
    public function show(FacultyProfile $facultyProfile)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @return Response
     */
    public function edit(FacultyProfile $facultyProfile)
    {
        $activeUser = $this->activeUserService->getActiveUser();

        $user = Faculty::find($activeUser->id);

        return view('faculty.profile.edit', compact('user'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  UpdateFacultyProfileRequest  $request
     * @param  FacultyProfile  $facultyProfile
     * @return Response
     */
    public function update(Request $request)
    {
        try {
            // Get currently logged-in user via your session service
            $activeUser = $this->activeUserService->getActiveUser();

            $user = Faculty::find($activeUser->id);

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
    public function destroy(FacultyProfile $facultyProfile)
    {
        //
    }

    public function updatePassword(Request $request)
    {
        try {
            // Get currently logged-in user via your session service
            $activeUser = $this->activeUserService->getActiveUser();

            $user = Faculty::find($activeUser->id);

            if (! $user) {
                return back()->with('error', 'User not found.');
            }

            // Validate input
            if (! $request->filled('current_password')) {
                return back()->with('error', 'Current password is required.');
            }

            if (! $request->filled('password')) {
                return back()->with('error', 'New password is required.');
            }

            if (strlen($request->password) < 8) {
                return back()->with('error', 'Password must be at least 8 characters.');
            }

            if ($request->password !== $request->password_confirmation) {
                return back()->with('error', 'Password confirmation does not match.');
            }

            // Check current password
            if (! Hash::check($request->current_password, $user->password)) {
                return back()->with('error', 'Current password is incorrect.');
            }

            // Update password
            $user->update([
                'password' => Hash::make($request->password),
            ]);

            return back()->with('success', 'Password updated successfully.');
        } catch (\Exception $e) {
            Log::error('Password update error: '.$e->getMessage());

            return back()->with('error', 'An unexpected error occurred. Please try again later.');
        }
    }
}
