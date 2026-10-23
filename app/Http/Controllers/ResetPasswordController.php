<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;

class ResetPasswordController extends Controller
{
    public function showResetForm($role, $token)
    {
        return view("auth.$role.passwords.reset", compact('role', 'token'));
    }

    public function reset(Request $request, $role)
    {
        $request->validate([
            'token' => 'required',
            'email' => 'required|email',
            'password' => 'required|confirmed|min:8',
        ]);

        $broker = $this->getBroker($role);

        $status = Password::broker($broker)->reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function ($user, $password) {
                $user->forceFill([
                    'password' => bcrypt($password),
                ])->save();
            }
        );

        if ($status === Password::PASSWORD_RESET) {
            return view('auth.reset-success', compact('role'));
        }

        return back()->withErrors(['email' => __($status)]);
    }

    private function getBroker($role)
    {
        switch ($role) {
            case 'student':
                return 'students';
            case 'faculty':
                return 'faculties';
            case 'admin':
                return 'admins';
            default:
                throw new \Exception('Invalid role');
        }
    }
}
