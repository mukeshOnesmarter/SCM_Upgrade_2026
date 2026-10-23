<?php

namespace App\Http\Middleware;

use App\Models\Simulation;
use App\Models\SimulationTeamMember;
use Carbon\Carbon;
use Closure;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class StudentMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response|RedirectResponse)  $next
     * @return Response|RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        $isStudentLoggedIn = session()->has('student.logged_in');
        $mimicStudentId = session('mimic_student_id');

        // CHECK IF STUDENT IS LOGGED IN.
        if (! $isStudentLoggedIn && ! $mimicStudentId) {
            return redirect()->route('student.login');
        }

        // RETRIEVE THE SIMULATION ID FROM THE ROUTE.
        $simulation_id = $request->route('simulation_id');

        // RETRIEVE THE SIMULATION ID FROM THE ROUTE.
        if ($simulation_id) {

            $studentId = $isStudentLoggedIn
                ? session('student.user.id')     // real student
                : $mimicStudentId;                   // mimicked by faculty

            // Optional: fallback if studentId still missing
            if (! $studentId) {
                return redirect()->route('student.login')
                    ->with('error', 'Session expired or invalid access.');
            }

            // FIND THE SIMULATION
            $simulation = Simulation::find($simulation_id);

            if (! $simulation) {
                return abort(404, 'Simulation not found.');
            }

            // Fetch the full team membership record
            $teamMember = SimulationTeamMember::where([
                ['student_id', $studentId],
                ['simulation_id', $simulation_id],
                ['student_allowed', 'Yes'],
            ])
                ->whereNotNull('student_joined')
                ->first();

            // If not part of the team
            if (! $teamMember) {
                return redirect()->back()->with('error', 'You are not assigned to this simulation.');
            }

            // ✅ These checks only apply to real student login
            if ($isStudentLoggedIn) {

                // BLOCKED check
                if ($teamMember->is_blocked) {
                    return redirect()->route('student.simulation.blocked', $simulation_id);
                }

                // REMOVED check
                if ($teamMember->is_removed) {
                    return redirect()->route('student.simulation.removed', $simulation_id);
                }

                // PAYMENT checks
                if (
                    $teamMember->payment_status === 'unpaid' &&
                    $teamMember->payment_due_date &&
                    now()->toDateString() > Carbon::parse($teamMember->payment_due_date)->toDateString()
                ) {
                    return redirect()->route('student.simulation.unpaid', $simulation_id);
                }
            }

            // Replace active_user with mimic student for all downstream use
            if ($mimicStudentId) {
                session(['active_user.user_id' => $mimicStudentId]);
            }
        }

        return $next($request);
    }
}
