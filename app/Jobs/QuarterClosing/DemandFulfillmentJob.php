<?php

namespace App\Jobs\QuarterClosing;

use App\Services\DemandFulfillmentService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class DemandFulfillmentJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 2;

    public $backoff = 30;

    public $timeout = 300;

    protected int $simulationId;

    protected int $facultyId;

    protected int $groupNumber;

    protected int $quarter;

    public function __construct(int $simulationId, int $facultyId, int $groupNumber, int $quarter)
    {
        $this->simulationId = $simulationId;
        $this->facultyId = $facultyId;
        $this->groupNumber = $groupNumber;
        $this->quarter = $quarter;

        $this->onQueue('quarter-closure');
    }

    public function middleware()
    {
        return [new WithoutOverlapping("demand-fulfill:{$this->simulationId}:{$this->groupNumber}:{$this->quarter}", 600)];
    }

    public function handle(DemandFulfillmentService $service)
    {
        $service->generateForGroupQuarter($this->simulationId, $this->facultyId, $this->groupNumber, $this->quarter);

        Log::info('[Demand Fulfillment Calculation] completed', [
            'simulation' => $this->simulationId,
            'group' => $this->groupNumber,
            'quarter' => $this->quarter,
        ]);
    }
}
