<?php

namespace App\Http\Controllers;

use App\Models\SimulationTeamMember;
use App\Services\ActiveUserService;
use App\Services\Exports\ScorecardRawExportService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DownloadScorecardController extends Controller
{
    protected ScorecardRawExportService $exportService;

    protected $activeUserService;

    public function __construct(ScorecardRawExportService $exportService, ActiveUserService $activeUserService)
    {
        $this->exportService = $exportService;
        $this->activeUserService = $activeUserService;
    }

    public function downloadScorecardRaw(Request $request)
    {
        try {
            $user = $this->activeUserService->getActiveUser();

            $teamMember = SimulationTeamMember::where([
                'simulation_id' => $request->simulation_id,
                'student_id' => $user->id,
            ])->first();

            $simulation_id = (int) $request->simulation_id;
            $faculty_id = $teamMember->faculty_id ?? null;
            $group_id = $teamMember->simulation_team_id ?? null;
            $quarter_id = (int) $request->quarter_id;

            $rows = $this->exportService->getRawMetrics($simulation_id, $faculty_id, $group_id, $quarter_id);

            $fileName = 'scorecard-raw-'.$simulation_id.'-'.$group_id.'-q'.$quarter_id.'-'.now()->format('YmdHis').'.csv';

            $response = new StreamedResponse(function () use ($rows) {
                $handle = fopen('php://output', 'w');

                // Header
                fputcsv($handle, [
                    'MetricLabel',
                    'RawValue',
                    'ComponentInputs_JSON',
                    'FormulaUsed',

                ]);

                foreach ($rows as $r) {
                    $raw = $r['raw_value'] ?? '';
                    $normalized = $r['normalized'] ?? '';
                    $weight = $r['weight'] ?? '';
                    $weighted = (is_numeric($normalized) && is_numeric($weight)) ? ($normalized * $weight) : '';

                    fputcsv($handle, [
                        $r['label'] ?? '',
                        $raw,
                        isset($r['inputs']) ? json_encode($r['inputs']) : '',
                        $r['formula'] ?? '',
                    ]);
                }

                fclose($handle);
            }, 200, [
                'Content-Type' => 'text/csv; charset=UTF-8',
                'Cache-Control' => 'no-cache, no-store, must-revalidate',
                'Pragma' => 'no-cache',
                'Expires' => '0',
                'Content-Disposition' => "attachment; filename=\"{$fileName}\"",
            ]);

            return $response;
        } catch (\Throwable $e) {
            Log::error('Scorecard Raw Download Error: '.$e->getMessage()."\n".$e->getTraceAsString());

            return response()->json(['error' => 'Failed to generate scorecard export'], 500);
        }
    }
}
