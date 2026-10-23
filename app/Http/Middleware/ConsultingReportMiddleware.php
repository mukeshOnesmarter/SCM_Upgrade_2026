<?php

namespace App\Http\Middleware;

use App\Models\Simulation;
use App\Models\SimulationQuarter;
use App\Models\SimulationTeamMember;
use App\Services\ActiveUserService;
use Carbon\Carbon;
use Closure;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class ConsultingReportMiddleware
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
        // CHECK IF SIMULATION EXISTS.
        $simulation = Simulation::findOrFail($request->simulation_id);

        $user = $this->activeUserService->getActiveUser();

        $teamMember = SimulationTeamMember::where([
            'simulation_id' => $request->simulation_id,
            'student_id' => $user->id,
        ])->first();

        // Extract faculty ID and group number, handling potential null values
        $faculty_id = $teamMember->faculty_id ?? null;

        // CHECK IF QUARTER EXISTS UNDER THIS SIMULATION.
        SimulationQuarter::where('simulation_id', $request->simulation_id)
            ->where('quarter_name', $request->quarter_id)
            ->firstOrFail();

        // 5. Determine which quarters to use: customized or default
        $customQuarters = $simulation->simulationQuarterCustomize->where('faculty_id', $faculty_id);
        $quarters = $customQuarters->isNotEmpty() ? $customQuarters : $simulation->simulationQuarters;

        $simulation->simulationQuarters = $quarters;
        $simulation->quarter_selected = $request->quarter_id;

        $quarterInProcess = $quarters->first(function ($quarter) {

            return $quarter->is_schedule === 'Yes'
                && is_null($quarter->close_manualy)
                && Carbon::parse($quarter->start_date)->startOfDay()->lte(now())
                && Carbon::parse($quarter->end_date)->endOfDay()->gte(now());
        });

        if ($simulation->quarter_selected > $quarterInProcess->quarter_name) {

            return redirect()->route('student.simulation.buyreports.upcoming-quarter', [
                'simulation_id' => $request->simulation_id,
                'quarter_id' => $request->quarter_id,
            ]);
        }

        if ($simulation->quarter_selected < $quarterInProcess->quarter_name) {
            return redirect()->route('student.simulation.reports.extra.consulting-reports', [
                'simulation_id' => $request->simulation_id,
                'quarter_id' => $request->quarter_id,
            ]);
        }

        return $next($request);
    }
}
