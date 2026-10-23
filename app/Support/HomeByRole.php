<?php

namespace App\Support;

/**
 * Where a signed-in person's home is. Used by "/" and by the error pages, so
 * nobody who is signed in lands on a bare page with no way back.
 */
final class HomeByRole
{
    public static function route(): ?string
    {
        return match (true) {
            session()->has('mimic_student_id'), (bool) session('student.logged_in') => 'student.dashboard',
            (bool) session('faculty.logged_in') => 'faculty.dashboard',
            (bool) session('admin.logged_in') => 'admin.dashboard',
            default => null,
        };
    }

    public static function url(): string
    {
        $route = self::route();

        return $route ? route($route) : url('/');
    }
}
