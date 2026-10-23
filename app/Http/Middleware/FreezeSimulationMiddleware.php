<?php

namespace App\Http\Middleware;

use App\Models\Simulation;
use App\Models\SimulationFaculty;
use App\Models\SimulationTeamMember;
use App\Services\ActiveUserService;
use Closure;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class FreezeSimulationMiddleware
{
    protected $activeUserService;

    public function __construct(ActiveUserService $activeUserService)
    {
        $this->activeUserService = $activeUserService;
    }

    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response|RedirectResponse)  $next
     * @return Response|RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        $user = $this->activeUserService->getActiveUser();
        $simulation_id = $request->simulation_id;

        // Skip if simulation ID is missing (optional safety)
        if (! $simulation_id) {
            return $next($request);
        }

        $teamMember = SimulationTeamMember::where([
            'simulation_id' => $simulation_id,
            'student_id' => $user->id,
        ])
            ->first();

        if (! $teamMember) {
            return $next($request);
        }

        // Get the simulation faculty record
        $simulation = Simulation::find($simulation_id);

        $simulationFaculty = SimulationFaculty::where([
            'simulation_id' => $simulation_id,
            'faculty_id' => $user->id,
        ])->first();

        $isFrozen = $simulationFaculty
            && $simulationFaculty->frozen_until
            && now()->lt($simulationFaculty->frozen_until);

        $isSyncing = $simulation && $simulation->is_syncing;

        $simulationInitialize = $simulation && $simulation->is_deploying;

        if ($isFrozen || $isSyncing || $simulationInitialize) {
            return redirect()->route('student.simulation.locked', [
                'simulation_id' => $simulation_id,
            ]);
        }

        return $next($request);
    }
}
