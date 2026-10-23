<?php

namespace App\Jobs\QuarterClosing;

use App\Services\DecisionDataSyncService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SyncQuarterDataJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;

    public $backoff = 60;

    public $timeout = 300;

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

    public function handle(DecisionDataSyncService $service)
    {
        $service->syncQuarterData($this->simulationId, $this->facultyId, $this->group, $this->quarterId);
        Log::info('[Quarter Data Sync] Completed', [
            'simulation' => $this->simulationId,
            'group' => $this->group,
            'quarter' => $this->quarterId,
        ]);
    }
}
