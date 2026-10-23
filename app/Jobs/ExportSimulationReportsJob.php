<?php

namespace App\Jobs;

use App\Services\ExportReportService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ExportSimulationReportsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $simulation_id;

    protected $faculty_id;

    protected $group;

    protected $quarter_id;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($simulation_id, $faculty_id, $group, $quarter_id)
    {
        $this->simulation_id = $simulation_id;
        $this->faculty_id = $faculty_id;
        $this->group = $group;
        $this->quarter_id = $quarter_id;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle(ExportReportService $exportReportService)
    {
        try {
            $exportReportService->corporatePnl($this->simulation_id, $this->faculty_id, $this->group, $this->quarter_id);
            $exportReportService->productPnl($this->simulation_id, $this->faculty_id, $this->group, $this->quarter_id);
            $exportReportService->balanceSheet($this->simulation_id, $this->faculty_id, $this->group, $this->quarter_id);
            $exportReportService->cashFlow($this->simulation_id, $this->faculty_id, $this->group, $this->quarter_id);
            $exportReportService->finishedGoods($this->simulation_id, $this->faculty_id, $this->group, $this->quarter_id);
            $exportReportService->procurement($this->simulation_id, $this->faculty_id, $this->group, $this->quarter_id);
            $exportReportService->forecastAccuracy($this->simulation_id, $this->faculty_id, $this->group, $this->quarter_id);
            $exportReportService->serviceOps($this->simulation_id, $this->faculty_id, $this->group, $this->quarter_id);
            $exportReportService->decisionVars($this->simulation_id, $this->faculty_id, $this->group, $this->quarter_id);

            Log::info('[Export Financial Reports] completed', [
                'quarter' => $this->quarter_id,
                'group' => $this->group,
                'simulation_id' => $this->simulation_id,
            ]);
        } catch (\Throwable $e) {
            Log::error("Failed to generate export reports for Q{$this->quarter_id}: ".$e->getMessage(), [
                'quarter' => $this->quarter_id,
                'group' => $this->group,
                'simulation_id' => $this->simulation_id,
            ]);
        }
    }
}
