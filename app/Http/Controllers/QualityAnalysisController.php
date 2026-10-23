<?php

namespace App\Http\Controllers;

use App\Services\QualityAnalysis\AvailabilityQualityService;
use App\Services\QualityAnalysis\CustomerAcceptanceIndexService;
use App\Services\QualityAnalysis\ProductQualityService;
use App\Services\QualityAnalysis\RegionalSummaryService;
use App\Services\QualityAnalysis\ServiceQualityService;
use Illuminate\Http\Request;

class QualityAnalysisController extends Controller
{
    /**
     * Display Product Quality (PQ) results for the given simulation and quarter.
     */
    public function calculatePQ($simulationId, $quarter)
    {
        $pqService = new ProductQualityService;
        $pqResults = $pqService->calculatePQ($simulationId, $quarter);

        return view('quality-analysis.pq', compact('pqResults', 'simulationId', 'quarter'));
    }

    /**
     * Display Service Quality (SQ) results for the given simulation and quarter.
     */
    public function calculateSQ($simulationId, $quarter)
    {
        $sqService = new ServiceQualityService;
        $sqResults = $sqService->calculateSQ($simulationId, $quarter);

        $firmServiceLevels = [
            'Firm 1' => 0.92,
            'Firm 2' => 0.91,
            'Firm 3' => 0.90,
            'Firm 4' => 0.89,
            'Firm 5' => 0.88,
            'Firm 6' => 0.87,
            'Firm 7' => 0.86,
            'Firm 8' => 0.85,
            'Firm 9' => 0.84,
            'Firm 10' => 0.83,
        ];

        return view('quality-analysis.sq', compact('sqResults', 'firmServiceLevels', 'simulationId', 'quarter'));
    }

    /**
     * Display Availability Quality (AQ) results for the given simulation and quarter.
     */
    public function calculateAQ($simulationId, $quarter)
    {
        $aqService = new AvailabilityQualityService;
        $aqResults = $aqService->calculateAQ($simulationId, $quarter);

        return view('quality-analysis.aq', compact('aqResults', 'simulationId', 'quarter'));
    }

    /**
     * Display Customer Acceptance Index (CAI) results for the given simulation and quarter.
     */
    public function calculateCAI(Request $request)
    {
        $simulationId = $request->simulation_id;
        $facultyId = 1;
        $quarter = $request->quarter;

        $groupIds = [1, 2, 3, 4]; // Replace with dynamic logic if needed

        $rawCais = [];
        foreach ($groupIds as $groupId) {
            $rawCais[$groupId] = app(CustomerAcceptanceIndexService::class)
                ->calculateCai($simulationId, $quarter);
        }

        $standardizedCais = app(CustomerAcceptanceIndexService::class)
            ->standardizeCais($rawCais);

        return view('quality-analysis.cai', compact('rawCais', 'standardizedCais', 'quarter'));
    }

    public function regionalSummary($simulationId, $quarter)
    {

        $simulation_id = $simulationId;
        $faculty_id = 1;

        $summary = app(RegionalSummaryService::class)->regionalSummary($simulation_id, $faculty_id, $quarter);

        return view('quality-analysis.regionalSummary', compact('summary'));
    }
}
