<?php

namespace App\Http\Middleware;

use App\Models\Decisions\DemandPlanning;
use App\Models\Decisions\Distribution;
use App\Models\Decisions\Forecasting;
use App\Models\Decisions\InformationTechnology;
use App\Models\Decisions\Manufacturing;
use App\Models\Decisions\Procurement;
use App\Models\Decisions\ServiceManagement;
use App\Models\SimulationTeamMember;
use App\Services\ActiveUserService;
use Closure;
use Illuminate\Http\Request;

class StudentDashboardMiddleware
{
    protected $activeUserService;

    public function __construct(ActiveUserService $activeUserService)
    {
        $this->activeUserService = $activeUserService;
    }

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next)
    {
        $user = $this->activeUserService->getActiveUser();

        $teamMember = SimulationTeamMember::where([
            'simulation_id' => $request->simulation_id,
            'student_id' => $user->id,
        ])->first();

        $simulation_id = $request->simulation_id;
        $quarter_id = $request->quarter_id;
        $faculty_id = $teamMember->faculty_id ?? null;
        $group_id = $teamMember->simulation_team_id ?? null;

        $modelsToCheck = [
            Forecasting::class,
            Procurement::class,
            Manufacturing::class,
            Distribution::class,
            ServiceManagement::class,
            DemandPlanning::class,
            InformationTechnology::class,
        ];

        $errorMessage = 'The dashboard will be available after this Quarter is closed and the data sync is completed.';

        foreach ($modelsToCheck as $model) {
            if ($this->filterBySimulation($model, $simulation_id, $faculty_id, $group_id, $quarter_id)->isEmpty()) {
                return redirect()->route('student.simulation.decisions.forecasting', ['simulation_id' => $simulation_id, 'quarter_id' => $quarter_id])
                    ->with('error', $errorMessage);
            }
        }

        return $next($request);
    }

    protected function filterBySimulation($model, $simulation_id, $faculty_id, $group_id, $quarter_id)
    {
        return $model::where([
            'simulation_id' => $simulation_id,
            'faculty_id' => $faculty_id,
            'group_number' => $group_id,
            'quarter' => $quarter_id,
        ])->get();
    }
}
