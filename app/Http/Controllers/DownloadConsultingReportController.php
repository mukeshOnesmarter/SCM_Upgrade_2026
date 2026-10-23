<?php

namespace App\Http\Controllers;

use App\Models\SimulationTeamMember;
use App\Services\ActiveUserService;
use App\Support\Spreadsheet\StyleBuilder;
use App\Support\Spreadsheet\WriterEntityFactory;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Str;

class DownloadConsultingReportController extends Controller
{
    protected $activeUserService;

    public function __construct(ActiveUserService $activeUserService)
    {
        $this->activeUserService = $activeUserService;
    }

    /**
     * Download a consulting report (controller entry point).
     *
     * @return \Illuminate\Http\Response
     */
    public function downloadConsultingReport(int $simulation_id, int $quarter_id, string $slug)
    {
        try {
            $user = $this->activeUserService->getActiveUser();

            if (! $user) {
                Log::warning('No active user found when requesting consulting report', compact('simulation_id', 'quarter_id', 'slug'));

                return abort(401, 'Unauthorized');
            }

            $teamMember = SimulationTeamMember::where([
                'simulation_id' => $simulation_id,
                'student_id' => $user->id,
            ])->first(['faculty_id', 'simulation_team_id']);

            if (! $teamMember) {
                Log::info('SimulationTeamMember not found', [
                    'simulation_id' => $simulation_id,
                    'student_id' => $user->id,
                    'quarter_id' => $quarter_id,
                    'slug' => $slug,
                ]);

                return abort(404, 'Team member not found for this simulation');
            }

            $faculty_id = $teamMember->faculty_id;
            $group_number = $teamMember->simulation_team_id;

            switch ($slug) {
                case 'earnings':
                    return $this->benchmarkEarnings(
                        $simulation_id,
                        (int) $faculty_id,
                        (int) $group_number,
                        $quarter_id,
                        $slug
                    );

                case 'balancesheet':
                    return $this->benchmarkBalanceSheet(
                        $simulation_id,
                        (int) $faculty_id,
                        (int) $group_number,
                        $quarter_id,
                        $slug
                    );

                case 'procurement':
                    return $this->benchmarkProcurement(
                        $simulation_id,
                        (int) $faculty_id,
                        (int) $group_number,
                        $quarter_id,
                        $slug
                    );

                case 'manufacturing':
                    return $this->benchmarkManufacturing(
                        $simulation_id,
                        (int) $faculty_id,
                        (int) $group_number,
                        $quarter_id,
                        $slug
                    );

                case 'distribution':
                    return $this->benchmarkDistribution(
                        $simulation_id,
                        (int) $faculty_id,
                        (int) $group_number,
                        $quarter_id,
                        $slug
                    );

                case 'transportation':
                    return $this->benchmarkTransportation(
                        $simulation_id,
                        (int) $faculty_id,
                        (int) $group_number,
                        $quarter_id,
                        $slug
                    );

                case 'generatedemand':
                    return $this->benchmarkGenerateDemand(
                        $simulation_id,
                        (int) $faculty_id,
                        (int) $group_number,
                        $quarter_id,
                        $slug
                    );

                case 'it':
                    return $this->benchmarkIT(
                        $simulation_id,
                        (int) $faculty_id,
                        (int) $group_number,
                        $quarter_id,
                        $slug
                    );

                case 'operating':
                    return $this->benchmarkOperatingStatistics(
                        $simulation_id,
                        (int) $faculty_id,
                        (int) $group_number,
                        $quarter_id,
                        $slug
                    );

                case 'market':
                    return $this->marketStatistics(
                        $simulation_id,
                        (int) $faculty_id,
                        (int) $group_number,
                        $quarter_id,
                        $slug
                    );

                case 'region':
                    return $this->regionalSummary(
                        $simulation_id,
                        (int) $faculty_id,
                        (int) $group_number,
                        $quarter_id,
                        $slug
                    );

                case 'customersatisfaction':
                    return $this->customerSatisfaction(
                        $simulation_id,
                        (int) $faculty_id,
                        (int) $group_number,
                        $quarter_id,
                        $slug
                    );

                case 'pricesensitivity':
                    return $this->priceSensitivity(
                        $simulation_id,
                        (int) $faculty_id,
                        (int) $group_number,
                        $quarter_id,
                        $slug
                    );

                default:
                    Log::warning('Invalid slug provided for consulting report', [
                        'simulation_id' => $simulation_id,
                        'quarter_id' => $quarter_id,
                        'slug' => $slug,
                        'user_id' => $user->id,
                    ]);

                    return abort(404, 'Invalid consulting report type.');
            }
        } catch (\Throwable $e) {
            Log::error('Download Error in Consulting Report controller: '.$e->getMessage(), [
                'exception' => $e->getTraceAsString(),
            ]);

            // Return a generic server error so clients get a proper HTTP response
            return response()->json(['message' => 'Failed to generate report'], 500);
        }
    }

    /** Benchmark – Earnings */
    public function benchmarkEarnings(int $simulation_id, int $faculty_id, int $group_id, int $quarter_id, string $slug)
    {
        //
    }

    /** Benchmark – Balance Sheets */
    public function benchmarkBalanceSheet(int $simulation_id, int $faculty_id, int $group_id, int $quarter_id, string $slug)
    {
        //
    }

    /** Benchmark – Procurement */
    public function benchmarkProcurement(int $simulation_id, int $faculty_id, int $group_id, int $quarter_id, string $slug)
    {
        //
    }

    /** Benchmark – Manufacturing */
    public function benchmarkManufacturing(int $simulation_id, int $faculty_id, int $group_id, int $quarter_id, string $slug)
    {
        //
    }

    /** Benchmark – Distribution */
    public function benchmarkDistribution(int $simulation_id, int $faculty_id, int $group_id, int $quarter_id, string $slug)
    {
        //
    }

    /** Benchmark – Transportation */
    public function benchmarkTransportation(int $simulation_id, int $faculty_id, int $group_id, int $quarter_id, string $slug)
    {
        //
    }

    /** Benchmark – Generate Demand */
    public function benchmarkGenerateDemand(int $simulation_id, int $faculty_id, int $group_id, int $quarter_id, string $slug)
    {
        // Build a friendly filename
        $fileName = sprintf('%s-%s-%s.xlsx', $slug, Str::random(6), now()->format('YmdHis'));

        // Create a temp file path (ensure extension .xlsx)
        $tempPath = tempnam(sys_get_temp_dir(), 'consulting_');
        // tempnam may not include .xlsx extension; create a final path
        $tmpFileWithExt = $tempPath.'.xlsx';

        // If tempnam created an empty file, remove it then we'll write to the file with extension
        if (file_exists($tempPath)) {
            @unlink($tempPath);
        }

        try {
            // Create and open the writer to a file (safe for Laravel)
            $writer = WriterEntityFactory::createXLSXWriter();
            $writer->openToFile($tmpFileWithExt);

            // Header row style
            $headerStyle = (new StyleBuilder)->setFontBold()->build();

            // Add a heading row
            $writer->addRow(WriterEntityFactory::createRowFromArray(['Benchmark - Generate Demand'], $headerStyle));
            // Add an empty row
            $writer->addRow(WriterEntityFactory::createRow());

            // TODO: Populate content (example row)
            $writer->addRow(WriterEntityFactory::createRowFromArray([
                'Simulation ID',
                'Faculty ID',
                'Group ID',
                'Quarter',
            ]));
            $writer->addRow(WriterEntityFactory::createRowFromArray([
                $simulation_id,
                $faculty_id,
                $group_id,
                $quarter_id,
            ]));

            // Place where you'd query DB and write rows
            // foreach ($rows as $r) { $writer->addRow(...); }

            $writer->close();

            // Return the file as a download and delete after sending
            return response()->download($tmpFileWithExt, $fileName)->deleteFileAfterSend(true);
        } catch (\Throwable $e) {
            // Ensure the temp file is cleaned up on error
            if (file_exists($tmpFileWithExt)) {
                @unlink($tmpFileWithExt);
            }

            Log::error('Download Error in Benchmark - Generate Demand: '.$e->getMessage(), [
                'exception' => $e->getTraceAsString(),
                'simulation_id' => $simulation_id,
                'faculty_id' => $faculty_id,
                'group_id' => $group_id,
                'quarter_id' => $quarter_id,
                'slug' => $slug,
            ]);

            return response()->json(['message' => 'Failed to generate report'], 500);
        }
    }

    /** Benchmark – Information Technology & Research Studies */
    public function benchmarkIT(int $simulation_id, int $faculty_id, int $group_id, int $quarter_id, string $slug)
    {
        //
    }

    /** Benchmark – Operating Statistics */
    public function benchmarkOperatingStatistics(int $simulation_id, int $faculty_id, int $group_id, int $quarter_id, string $slug)
    {
        //
    }

    /** Market Statistics */
    public function marketStatistics(int $simulation_id, int $faculty_id, int $group_id, int $quarter_id, string $slug)
    {
        //
    }

    /** Regional Summary Analysis */
    public function regionalSummary(int $simulation_id, int $faculty_id, int $group_id, int $quarter_id, string $slug)
    {
        //
    }

    /** Customer Satisfaction */
    public function customerSatisfaction(int $simulation_id, int $faculty_id, int $group_id, int $quarter_id, string $slug)
    {
        //
    }

    /** Price Sensitivity Analysis */
    public function priceSensitivity(int $simulation_id, int $faculty_id, int $group_id, int $quarter_id, string $slug)
    {
        //
    }
}
