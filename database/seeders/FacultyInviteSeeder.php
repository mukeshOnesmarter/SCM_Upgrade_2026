<?php

namespace Database\Seeders;

use App\Models\FacultyInvite;
use App\Models\Student;
use App\Models\StudentEnroll;
use Illuminate\Database\Seeder;

class FacultyInviteSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // CLEAR PREVIOUS DATA BEFORE SEEDING
        FacultyInvite::truncate();
        StudentEnroll::truncate();

        for ($i = 1; $i <= 9; $i++) {

            FacultyInvite::create([
                'faculty_id' => 1,
                'simulation_id' => 1,
                'email' => Student::where('id', $i)->value('email'),
                'sent_intro_email' => 'Yes',
                'accepted_at' => now(),
            ]);

            StudentEnroll::create([
                'student_id' => $i,
                'faculty_id' => 1,
                'simulation_id' => 1,
            ]);
        }

        FacultyInvite::create([
            'faculty_id' => 1,
            'simulation_id' => 1,
            'email' => Student::where('id', 10)->value('email'),
            'sent_intro_email' => 'Yes',
        ]);
    }
}
