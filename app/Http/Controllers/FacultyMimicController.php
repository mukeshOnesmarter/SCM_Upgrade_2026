<?php

namespace App\Http\Controllers;

use App\Models\Student;

class FacultyMimicController extends Controller
{
    public function startMimic($student_id, $simulation_id)
    {
        $student = Student::findOrFail($student_id);

        // Backup current faculty session
        session(['original_faculty_session' => session()->all()]);

        // Set mimic session for student
        session([
            'mimic_student_id' => $student->id,
            'student_logged_in' => true,
            'active_user' => [
                'first_name' => $student->first_name,
                'last_name' => $student->last_name,
                'user_id' => $student->id,
                'user_type' => 'student',
            ],
        ]);

        return redirect()->route('student.simulation.decisions.forecasting', [
            'simulation_id' => $simulation_id,
            'quarter_id' => 4,
        ]);
    }

    public function stopMimic($simulation_id, $student_id)
    {
        // Retrieve backed-up faculty session
        $originalSession = session('original_faculty_session', []);

        // Flush current (mimic) session
        session()->flush();

        // Restore original faculty session
        session($originalSession);

        // Optional: cleanup the backup
        session()->forget('original_faculty_session');

        return redirect()->route('faculty.courses.show', [
            'course_id' => $simulation_id,
        ]);
    }
}
