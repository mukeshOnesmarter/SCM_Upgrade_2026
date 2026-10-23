<?php

namespace App\Console\Commands;

use App\Models\Admin;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

/**
 * Creates an admin, or resets an existing admin's password. Replaces the old
 * AdminSeeder, which wrote real people's passwords into the code.
 *
 *     php artisan scm:create-admin you@flexee.org --first=Vikram --last=Sethi --super
 *
 * It asks for the password (hidden). Press Enter to have a random one made
 * and shown once.
 */
class CreateAdmin extends Command
{
    protected $signature = 'scm:create-admin
        {email : The admin\'s email address}
        {--first= : First name (for a new admin)}
        {--last= : Last name (for a new admin)}
        {--super : Make them a super-admin}';

    protected $description = 'Create an admin, or reset an admin\'s password';

    public function handle(): int
    {
        $email = strtolower(trim((string) $this->argument('email')));

        if (Validator::make(['email' => $email], ['email' => 'required|email'])->fails()) {
            $this->error('That isn\'t a valid email address.');

            return self::FAILURE;
        }

        $password = (string) $this->secret('Password (leave empty for a random one)');
        $generated = $password === '';

        if ($generated) {
            $password = Str::password(16, symbols: false);
        } elseif (strlen($password) < 12) {
            $this->error('Use at least 12 characters.');

            return self::FAILURE;
        }

        $admin = Admin::where('email', $email)->first();
        $isNew = $admin === null;

        $admin ??= new Admin;
        $admin->email = $email;
        $admin->first_name = (string) ($this->option('first') ?: $admin->first_name ?: Str::before($email, '@'));
        $admin->last_name = (string) ($this->option('last') ?: $admin->last_name ?: '');
        $admin->password = Hash::make($password);
        $admin->status = 'Active';

        if ($isNew || $this->option('super')) {
            $admin->role = $this->option('super') ? 'super-admin' : 'admin';
        }

        $admin->save();

        $this->info(($isNew ? 'Created' : 'Updated')." {$admin->role} {$email}.");

        if ($generated) {
            $this->warn("Password (shown once): {$password}");
        }

        return self::SUCCESS;
    }
}
