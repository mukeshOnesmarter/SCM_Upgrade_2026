<?php

namespace App\Http\Controllers;

use App\Models\Admin;
use App\Services\ActiveUserService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class AdminProfileController extends Controller
{
    protected $activeUserService;

    public function __construct(ActiveUserService $activeUserService)
    {
        $this->activeUserService = $activeUserService;
    }

    public function edit(Request $request)
    {
        $activeUser = $this->activeUserService->getActiveUser();
        $user = Admin::find($activeUser->id);

        return view('admin.profile.edit', compact('user'));
    }

    public function update(Request $request)
    {
        try {
            // Get currently logged-in user via your session service
            $activeUser = $this->activeUserService->getActiveUser();

            $user = Admin::find($activeUser->id);

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

    public function updatePassword(Request $request)
    {
        try {
            // Get currently logged-in user via your session service
            $activeUser = $this->activeUserService->getActiveUser();

            $user = Admin::find($activeUser->id);

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
