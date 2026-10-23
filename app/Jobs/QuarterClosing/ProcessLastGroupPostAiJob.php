<?php

namespace App\Jobs\QuarterClosing;

use App\Jobs\EmergencyProcurementJob;
use App\Jobs\ExportSimulationReportsJob;
use App\Jobs\GenerateScorecardJob;
use App\Jobs\SendAiAnalysisReportJob;
use App\Jobs\SendQuarterClosedEmailsJob;
use App\Jobs\StoreEndingFGIJob;
use App\Jobs\StoreEndingInventoryJob;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class ProcessLastGroupPostAiJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    // NOTE: removed class-level $queue to avoid trait/property conflicts
    public $tries = 5;

    public $backoff = 30;

    public $timeout = 600;

    protected int $simulationId;

    protected int $facultyId;

    protected int $group;

    protected int $quarterId;

    public function __construct(int $simulationId, int $facultyId, int $group, int $quarterId)
    {
        $this->simulationId = $simulationId;
        $this->facultyId = $facultyId;
        $this->group = $group;
        $this->quarterId = $quarterId;
    }

    public function handle()
    {
        $aiReadyKey = "ai_ready:{$this->simulationId}:{$this->facultyId}:{$this->group}:{$this->quarterId}";
        $idempotentKey = "flexee:quarter-closure:lastgroup-dispatched:{$this->simulationId}:{$this->facultyId}:{$this->group}:{$this->quarterId}";

        // Idempotency: skip if we've already dispatched Step-B for this last group
        if (Cache::has($idempotentKey)) {
            Log::info('ProcessLastGroupPostAiJob skipping — already dispatched Step-B for last group', [
                'simulation_id' => $this->simulationId,
                'faculty_id' => $this->facultyId,
                'group' => $this->group,
                'quarter' => $this->quarterId,
            ]);

            return;
        }

        // Defensive: ensure AI results are ready.
        if (! Cache::has($aiReadyKey)) {
            $delaySeconds = 30;
            Log::warning('ProcessLastGroupPostAiJob: ai_ready not found; re-dispatching self with delay', [
                'ai_ready_key' => $aiReadyKey,
                'simulation_id' => $this->simulationId,
                'faculty_id' => $this->facultyId,
                'group' => $this->group,
                'quarter' => $this->quarterId,
                'delay' => $delaySeconds,
            ]);

            // Re-dispatch self with a controlled delay.
            self::dispatch($this->simulationId, $this->facultyId, $this->group, $this->quarterId)
                ->delay(now()->addSeconds($delaySeconds))
                ->onQueue('quarter-closure');

            return;
        }

        try {
            Log::info('ProcessLastGroupPostAiJob: dispatching Step-B + per-group chain for last group', [
                'simulation_id' => $this->simulationId,
                'faculty_id' => $this->facultyId,
                'group' => $this->group,
                'quarter' => $this->quarterId,
            ]);

            // --- Step-B jobs (run in strict sequence) ---
            $syncJob = new SyncQuarterDataJob(
                $this->simulationId,
                $this->facultyId,
                $this->group,
                $this->quarterId
            );

            $processJob = new ProcessGroupJob(
                $this->simulationId,
                $this->facultyId,
                $this->group,
                $this->quarterId
            );

            $snapshotJob = new StoreFinancialSnapshotJob(
                $this->simulationId,
                $this->facultyId,
                $this->group,
                $this->quarterId
            );

            $scorecardJob = new GenerateScorecardJob(
                $this->simulationId,
                $this->facultyId,
                $this->group,
                $this->quarterId
            );

            // assign the preferred queues
            $syncJob->onQueue('quarter-closure');
            $processJob->onQueue('quarter-closure');
            $snapshotJob->onQueue('financial-reports');
            $scorecardJob->onQueue('scorecards');

            // --- Per-group chain jobs (the same jobs DispatchGroupChainsJob dispatches) ---
            $protocol = app()->environment('production') ? 'https' : 'http';
            $analysisHost = config('services.analysis.host');
            $aiAnalysisUrl = sprintf(
                '%s://%s/api/overall_insight_for_quarter/?simulation_id=%d&faculty_id=%d&group_id=%d&quarter=%d',
                $protocol,
                $analysisHost,
                $this->simulationId,
                $this->facultyId,
                $this->group,
                $this->quarterId
            );

            $emergencyProcurement = new EmergencyProcurementJob(
                $this->simulationId,
                $this->facultyId,
                $this->group,
                $this->quarterId
            );

            $storeEndingInventory = new StoreEndingInventoryJob(
                $this->simulationId,
                $this->facultyId,
                $this->group,
                $this->quarterId
            );

            $storeEndingFGI = new StoreEndingFGIJob(
                $this->simulationId,
                $this->facultyId,
                $this->group,
                $this->quarterId
            );

            $exportReports = new ExportSimulationReportsJob(
                $this->simulationId,
                $this->facultyId,
                $this->group,
                $this->quarterId
            );

            $sendAiAnalysis = new SendAiAnalysisReportJob($aiAnalysisUrl);

            $sendEmails = new SendQuarterClosedEmailsJob(
                $this->simulationId,
                $this->facultyId,
                $this->group,
                $this->quarterId
            );

            // assign queues for per-group jobs
            $emergencyProcurement->onQueue('quarter-closure');
            $storeEndingInventory->onQueue('quarter-closure');
            $storeEndingFGI->onQueue('quarter-closure');
            $exportReports->onQueue('quarter-closure');
            $sendAiAnalysis->onQueue('quarter-closure');
            $sendEmails->onQueue('quarter-closure');

            // Build combined chain: Step-B jobs followed by per-group jobs.
            $combinedChain = [
                $syncJob,
                $processJob,
                $snapshotJob,
                $scorecardJob,

                // per-group chain appended so they run only after Step-B completed
                $emergencyProcurement,
                $storeEndingInventory,
                $storeEndingFGI,
                $exportReports,
                $sendAiAnalysis,
                $sendEmails,
            ];

            Bus::chain($combinedChain)
                ->onQueue('quarter-closure')
                ->dispatch();

            // mark idempotent (avoid double-dispatch)
            Cache::put($idempotentKey, true, now()->addHours(6));

            Log::info('ProcessLastGroupPostAiJob dispatched combined chain for last group', [
                'simulation_id' => $this->simulationId,
                'faculty_id' => $this->facultyId,
                'group' => $this->group,
                'quarter' => $this->quarterId,
                'ai_ready_key' => $aiReadyKey,
                'idempotent_key' => $idempotentKey,
            ]);
        } catch (\Throwable $e) {
            Log::error('ProcessLastGroupPostAiJob failed to dispatch combined chain', [
                'simulation_id' => $this->simulationId,
                'faculty_id' => $this->facultyId,
                'group' => $this->group,
                'quarter' => $this->quarterId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            // rethrow so queue can retry
            throw $e;
        }
    }

    /**
     * (NOT used currently) helper to dispatch the per-group chain standalone.
     * Kept for reference if you want to dispatch per-group chain separately.
     */
    protected function dispatchPerGroupChain(int $group)
    {
        try {
            $protocol = app()->environment('production') ? 'https' : 'http';
            $analysisHost = config('services.analysis.host');

            $aiAnalysisUrl = sprintf(
                '%s://%s/api/overall_insight_for_quarter/?simulation_id=%d&faculty_id=%d&group_id=%d&quarter=%d',
                $protocol,
                $analysisHost,
                $this->simulationId,
                $this->facultyId,
                $group,
                $this->quarterId
            );

            Bus::chain([
                new EmergencyProcurementJob($this->simulationId, $this->facultyId, $group, $this->quarterId),
                new StoreEndingInventoryJob($this->simulationId, $this->facultyId, $group, $this->quarterId),
                new StoreEndingFGIJob($this->simulationId, $this->facultyId, $group, $this->quarterId),
                new ExportSimulationReportsJob($this->simulationId, $this->facultyId, $group, $this->quarterId),
                new SendAiAnalysisReportJob($aiAnalysisUrl),
                new SendQuarterClosedEmailsJob($this->simulationId, $this->facultyId, $group, $this->quarterId),
            ])->onQueue('quarter-closure')->dispatch();

            Log::info('Quarter Close chain dispatched (helper)', [
                'simulation_id' => $this->simulationId,
                'faculty_id' => $this->facultyId,
                'quarter' => $this->quarterId,
                'group' => $group,
            ]);
        } catch (\Throwable $th) {
            Log::error('Failed to dispatch group chain (helper)', [
                'simulation_id' => $this->simulationId,
                'faculty_id' => $this->facultyId,
                'quarter' => $this->quarterId,
                'group' => $group,
                'error' => $th->getMessage(),
            ]);
        }
    }
}
