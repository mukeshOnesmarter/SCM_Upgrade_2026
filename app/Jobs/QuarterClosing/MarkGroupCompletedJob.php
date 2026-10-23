<?php

namespace App\Jobs\QuarterClosing;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class MarkGroupCompletedJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected int $simulationId;

    protected int $quarterId;

    public function __construct(int $simulationId, int $quarterId)
    {
        $this->simulationId = $simulationId;
        $this->quarterId = $quarterId;

        $this->onQueue('quarter-closure');
    }

    public function handle()
    {
        $key = "flexee:quarter-closure:remaining:{$this->simulationId}:{$this->quarterId}";

        $remaining = Cache::decrement($key);

        Log::info('Group completed', [
            'simulation_id' => $this->simulationId,
            'quarter_id' => $this->quarterId,
            'remaining' => $remaining,
        ]);

        if ($remaining !== null && $remaining <= 0) {
            Cache::forget($key);
            dispatch(new FinalizeSimulationSyncJob($this->simulationId));
        }
    }
}
