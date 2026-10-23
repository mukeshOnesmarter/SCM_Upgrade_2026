<?php

namespace App\Jobs\QuarterClosing;

use App\Jobs\EmergencyProcurementJob;
use App\Jobs\EmergencyProductionJob;
use App\Jobs\EmergencyTransportationJob;
use App\Jobs\ExportSimulationReportsJob;
use App\Jobs\SendAiAnalysisReportJob;
use App\Jobs\SendQuarterClosedEmailsJob;
use App\Jobs\StoreEndingFGIJob;
use App\Jobs\StoreEndingInventoryJob;
use App\Models\Simulation;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class DispatchGroupChainsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;

    public $backoff = 60;

    public $timeout = 300;

    protected int $simulationId;

    protected int $facultyId;

    protected int $quarterId;

    protected array $groupIds;

    protected $lastGroup;

    /**
     * @param  mixed|null  $lastGroup  explicit last group id (optional)
     */
    public function __construct(int $simulationId, int $facultyId, int $quarterId, array $groupIds, $lastGroup = null)
    {
        $this->simulationId = $simulationId;
        $this->facultyId = $facultyId;
        $this->quarterId = $quarterId;
        $this->groupIds = $groupIds;
        $this->lastGroup = $lastGroup;
        $this->onQueue('quarter-closure');
    }

    public function middleware()
    {
        return [
            new WithoutOverlapping("dispatch-group-chains-{$this->simulationId}-{$this->quarterId}", 600),
        ];
    }

    public function handle()
    {
        $cacheKey = "flexee:quarter-closure:dispatched:{$this->simulationId}:{$this->quarterId}";

        if (Cache::has($cacheKey)) {
            Log::info('Skipping duplicate DispatchGroupChainsJob', [
                'simulation_id' => $this->simulationId,
                'quarter_id' => $this->quarterId,
            ]);

            return;
        }

        $totalGroups = count($this->groupIds);

        Cache::put(
            $cacheKey,
            $totalGroups,
            true,
            now()->addHours(6)
        );

        if (empty($this->groupIds)) {
            Log::warning('DispatchGroupChainsJob: No teams found', [
                'simulation_id' => $this->simulationId,
                'quarter_id' => $this->quarterId,
            ]);

            return;
        }

        foreach (array_chunk($this->groupIds, 20) as $chunk) {
            foreach ($chunk as $group) {
                $this->dispatchPerGroupChain($group);
            }
        }
    }

    protected function dispatchPerGroupChain(int $group)
    {
        try {

            $simulation = Simulation::select('id', 'is_analysis_enabled')
                ->find($this->simulationId);

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

            $jobs = [
                new EmergencyProcurementJob($this->simulationId, $this->facultyId, $group, $this->quarterId),
                new EmergencyProductionJob($this->simulationId, $this->facultyId, $group, $this->quarterId),
                new EmergencyTransportationJob($this->simulationId, $this->facultyId, $group, $this->quarterId),
                new StoreEndingFGIJob($this->simulationId, $this->facultyId, $group, $this->quarterId),
                new StoreEndingInventoryJob($this->simulationId, $this->facultyId, $group, $this->quarterId),
                new DemandFulfillmentJob($this->simulationId, $this->facultyId, $group, $this->quarterId),
                new ExportSimulationReportsJob($this->simulationId, $this->facultyId, $group, $this->quarterId),
            ];

            if ($simulation && $simulation->is_analysis_enabled) {
                $jobs[] = new SendAiAnalysisReportJob($aiAnalysisUrl);
            }

            // Always send emails
            $jobs[] = new SendQuarterClosedEmailsJob(
                $this->simulationId,
                $this->facultyId,
                $group,
                $this->quarterId
            );

            $jobs[] = new MarkGroupCompletedJob(
                $this->simulationId,
                $this->quarterId
            );

            Bus::chain($jobs)
                ->onQueue('quarter-closure')
                ->dispatch();
        } catch (\Throwable $th) {
            Log::error('Failed to dispatch group chain', [
                'simulation_id' => $this->simulationId,
                'faculty_id' => $this->facultyId,
                'quarter' => $this->quarterId,
                'group' => $group,
                'error' => $th->getMessage(),
            ]);
        }
    }
}
