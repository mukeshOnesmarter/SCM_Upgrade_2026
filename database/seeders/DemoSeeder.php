<?php

namespace Database\Seeders;

use App\Models\Faculty;
use App\Models\Student;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Demo faculty and students for a development database: 10 faculty and about
 * 200 students, every one marked is_test. Run it on an empty database after
 * DatabaseSeeder:
 *
 *     php artisan db:seed --class=DemoSeeder
 *
 * The password for every demo account is DEMO_PASSWORD from .env. If that's
 * empty, a random one is made and printed once. No password is written in
 * the code, and this seeder refuses to run on production.
 */
class DemoSeeder extends Seeder
{
    private static ?string $password = null;

    public static function password(): string
    {
        return self::$password ??= (string) (config('app.demo_password') ?: Str::password(16, symbols: false));
    }

    public function run(): void
    {
        if (app()->environment('production')) {
            throw new RuntimeException('DemoSeeder makes test accounts and never runs on production.');
        }

        $lastStudent = (int) Student::max('id');
        $lastFaculty = (int) Faculty::max('id');

        $this->call([
            StudentSeeder::class,
            StudentProfileSeeder::class,
            FacultySeeder::class,
            FacultyProfileSeeder::class,
            StudentWithProfileSeeder::class
        ]);

        Student::where('id', '>', $lastStudent)->update(['is_test' => true]);
        Faculty::where('id', '>', $lastFaculty)->update(['is_test' => true]);

        if (! config('app.demo_password')) {
            $this->command?->warn('Demo accounts use this password (shown once): '.self::password());
        }
    }
}
