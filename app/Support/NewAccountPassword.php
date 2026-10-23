<?php

namespace App\Support;

use App\Notifications\ResetPasswordWithRole;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;

/**
 * A new account's password. The old code gave every student a faculty member
 * added, and every faculty member an admin added, the same password, written
 * in the code. Now each new account gets a long random password nobody knows,
 * and an email with a link to set their own.
 */
final class NewAccountPassword
{
    /** A hash of a random password, for the new account's row. */
    public static function placeholder(): string
    {
        return Hash::make(Str::password(40));
    }

    /**
     * Emails the account a link to set their password.
     *
     * @param  'student'|'faculty'  $role
     */
    public static function sendSetLink(Model $account, string $role): void
    {
        $broker = ['student' => 'students', 'faculty' => 'faculties'][$role];
        $token = Password::broker($broker)->createToken($account);

        $account->notify(new ResetPasswordWithRole($token, $role, newAccount: true));
    }
}
