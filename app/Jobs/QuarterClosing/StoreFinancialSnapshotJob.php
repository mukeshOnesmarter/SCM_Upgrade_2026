<?php

namespace App\Jobs\QuarterClosing;

use App\Services\Reports\BalanceSheetService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class StoreFinancialSnapshotJob implements ShouldQueue
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

    public function handle(BalanceSheetService $balanceSheetService)
    {
        $balanceSheetService->storeFinancialSnapshot($this->simulationId, $this->facultyId, $this->group, $this->quarterId);
        Log::info('[Financial Snapshot] Completed', [
            'simulation' => $this->simulationId,
            'group' => $this->group,
            'quarter' => $this->quarterId,
        ]);
    }
}
