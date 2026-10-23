<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class QuarterAccessMiddleware
{
    /**
     * @param  string  $feature  Name of the feature (forecasting, procurement, etc.)
     * @return mixed
     */
    public function handle(Request $request, Closure $next, $feature)
    {
        $simulation = (int) $request->route('simulation_id');
        $quarter = (int) $request->route('quarter_id');

        // Feature-wise Access Control
        $accessRules = [
            'forecasting' => 1,  // Available always
            'procurement' => 4,  // Available from Quarter 4
            'manufacturing' => 4,  // Available from Quarter 4
            'distribution' => 6,  // Available from Quarter 6
            'transportation' => 6,  // Available from Quarter 6
            'service-management' => 7,  // Available from Quarter 7
            'demand-planning' => 7,
            'information-technology' => 7,
        ];

        $requiredQuarter = $accessRules[$feature] ?? 1; // Default allow if feature not listed

        if ($quarter < $requiredQuarter) {
            return redirect()->route('student.simulation.decisions.forecasting', ['simulation_id' => $simulation, 'quarter_id' => $quarter])->with('error', 'You cannot access this page in the current quarter.');
        }

        return $next($request);
    }
}
