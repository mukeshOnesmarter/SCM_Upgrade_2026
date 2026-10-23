<?php

namespace App\Jobs;

use App\Services\QuarterClosingService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class QuarterClosureJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;

    public $backoff = 60;

    public $timeout = 600;

    protected int $simulation_id;

    protected int $faculty_id;

    protected int $quarter_id;

    public function __construct(int $simulation_id, int $faculty_id, int $quarter_id)
    {
        $this->simulation_id = $simulation_id;
        $this->faculty_id = $faculty_id;
        $this->quarter_id = $quarter_id;

        $this->onQueue('quarter-closure');
    }

    /**
     * Prevent concurrent runs for the same simulation-quarter.
     */
    public function middleware()
    {
        return [
            new WithoutOverlapping(
                "quarter-closure-{$this->simulation_id}-{$this->quarter_id}",
                600
            ),
        ];
    }

    public function handle(QuarterClosingService $quarterClosingService): void
    {
        $cacheKey = "flexee:quarter-closure:processed:{$this->simulation_id}:{$this->quarter_id}";

        // Idempotency Guard
        if (Cache::has($cacheKey)) {
            Log::info('QuarterClosureJob skipped (already processed)', [
                'simulation_id' => $this->simulation_id,
                'faculty_id' => $this->faculty_id,
                'quarter_id' => $this->quarter_id,
            ]);

            return;
        }

        try {

            $quarterClosingService->quarterClosure(
                $this->simulation_id,
                $this->faculty_id,
                $this->quarter_id
            );

            Cache::put($cacheKey, true, now()->addHours(12));

            Log::info('QuarterClosureJob completed successfully', [
                'simulation_id' => $this->simulation_id,
                'faculty_id' => $this->faculty_id,
                'quarter_id' => $this->quarter_id,
            ]);

        } catch (\Throwable $e) {
            Log::error('QuarterClosureJob failed', [
                'simulation_id' => $this->simulation_id,
                'faculty_id' => $this->faculty_id,
                'quarter_id' => $this->quarter_id,
                'error' => $e->getMessage(),
            ]);

            throw $e; // allow retry
        }
    }
}
