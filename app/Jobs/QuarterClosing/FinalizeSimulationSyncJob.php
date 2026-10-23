<?php

namespace App\Jobs\QuarterClosing;

use App\Models\Simulation;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class FinalizeSimulationSyncJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected int $simulationId;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(int $simulationId)
    {
        $this->simulationId = $simulationId;

        $this->onQueue('quarter-closure');
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        Simulation::where('id', $this->simulationId)->update([
            'is_syncing' => false,
        ]);

        Log::info('Simulation sync completed', [
            'simulation_id' => $this->simulationId,
        ]);
    }

    public function failed(\Throwable $e)
    {
        // fail-safe
        Simulation::where('id', $this->simulationId)->update([
            'is_syncing' => false,
        ]);
    }
}
