<?php

namespace App\Services;

use App\Models\Admin;
use App\Models\Faculty;
use App\Models\Student;

class ActiveUserService
{
    public function getActiveUser()
    {
        if (session()->has('mimic_student_id')) {
            // ✅ Return mimicked student user
            return Student::find(session('mimic_student_id'));
        }

        if (session()->has('admin.logged_in') && session()->has('admin.user')) {
            return Admin::find(session('admin.user')['id']);
        }

        if (session()->has('faculty.logged_in') && session()->has('faculty.user')) {
            return Faculty::with('profile')->find(session('faculty.user')['id']);
        }

        if (session()->has('student.logged_in') && session()->has('student.user')) {
            return Student::find(session('student.user')['id']);
        }
    }
}
