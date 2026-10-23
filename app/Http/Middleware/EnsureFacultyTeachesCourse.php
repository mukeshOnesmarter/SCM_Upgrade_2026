<?php

namespace App\Http\Middleware;

use App\Models\SimulationFaculty;
use App\Models\SimulationTeamMember;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * A faculty member reaches a course's pages only if they teach it (as faculty
 * or co-faculty), and a student's pages only if that student is in one of
 * their courses.
 *
 * The old app checked the course's owner, not the person signed in, so any
 * faculty member could open any course by changing the number in the address.
 */
class EnsureFacultyTeachesCourse
{
    public function handle(Request $request, Closure $next): Response
    {
        $facultyId = (int) data_get(session('faculty.user'), 'id');
        $courseId = $request->route('course_id') ?? $request->route('simulation_id');

        if ($courseId !== null) {
            $teaches = SimulationFaculty::where('simulation_id', (int) $courseId)
                ->where('faculty_id', $facultyId)
                ->exists();

            abort_unless($teaches, 403, 'You don’t teach this course.');
        }

        $studentId = $request->route('student_id');

        if ($studentId !== null) {
            $courses = $courseId !== null
                ? [(int) $courseId]
                : SimulationFaculty::where('faculty_id', $facultyId)->pluck('simulation_id')->all();

            $inCourse = SimulationTeamMember::whereIn('simulation_id', $courses)
                ->where('student_id', (int) $studentId)
                ->exists();

            abort_unless($inCourse, 403, 'This student isn’t in one of your courses.');
        }

        return $next($request);
    }
}
