<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;

class ForgotPasswordController extends Controller
{
    public function showLinkRequestForm($role)
    {
        // load different views for student/faculty/admin
        return view("auth.$role.passwords.email", compact('role'));
    }

    public function sendResetLinkEmail(Request $request, $role)
    {
        $request->validate(['email' => 'required|email']);

        // Map role to broker
        $broker = $this->getBroker($role);

        $status = Password::broker($broker)->sendResetLink(
            $request->only('email')
        );

        if ($status === Password::RESET_LINK_SENT) {
            return redirect()->route('password.linksent', [
                'role' => $role,
                'email' => $request->email,
            ]);
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
