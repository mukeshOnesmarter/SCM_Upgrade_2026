<?php

namespace App\Jobs;

use App\Services\Reports\FlexScorecardService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class GenerateScorecardJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected int $simulation_id;

    protected int $faculty_id;

    protected int $group_id;

    protected int $quarter;

    /**
     * Max retries handled by Laravel
     */
    public int $tries = 20;

    /**
     * Job timeout (seconds)
     */
    public int $timeout = 1200; // 20 minutes

    /**
     * Delay between retries
     */
    public function backoff(): array
    {
        return [30, 60, 120, 300, 600];
    }

    public function __construct(
        int $simulation_id,
        int $faculty_id,
        int $group_id,
        int $quarter
    ) {
        $this->simulation_id = $simulation_id;
        $this->faculty_id = $faculty_id;
        $this->group_id = $group_id;
        $this->quarter = $quarter;
    }

    public function handle(FlexScorecardService $flexScorecardService): void
    {
        $sim = $this->simulation_id;
        $fac = $this->faculty_id;
        $group = $this->group_id;
        $q = $this->quarter;

        /* ------------------------------------------------------------
         |  SKIP Q0 — explicitly not required
         ------------------------------------------------------------ */
        if ($q <= 0) {
            Log::info('[Scorecard] Q0 skipped by design', [
                'simulation' => $sim,
                'group' => $group,
                'quarter' => $q,
            ]);

            return;
        }

        /* ------------------------------------------------------------
         |  IDEMPOTENCY GUARD — already generated
         ------------------------------------------------------------ */
        $doneKey = "scorecard-done:sim:$sim:grp:$group:q:$q";

        if (Cache::has($doneKey)) {
            Log::info('[Scorecard] Already generated, skipping', [
                'simulation' => $sim,
                'group' => $group,
                'quarter' => $q,
            ]);

            return;
        }

        /* ------------------------------------------------------------
         |  DISTRIBUTED LOCK (shared hosting safe)
         ------------------------------------------------------------ */
        $lockKey = "scorecard-lock:sim:$sim:grp:$group:q:$q";
        $lockTtl = 600; // 10 minutes

        $lock = Cache::lock($lockKey, $lockTtl);

        if (! $lock->get()) {
            Log::info('[Scorecard] Lock busy, retrying later', [
                'lock' => $lockKey,
                'attempt' => $this->attempts(),
            ]);

            $this->release(60);

            return;
        }

        try {
            /* ------------------------------------------------------------
             |  CORE EXECUTION
             ------------------------------------------------------------ */
            $flexScorecardService->storeScorecardAtomic(
                $sim,
                $fac,
                $group,
                $q
            );

            /* ------------------------------------------------------------
             |  MARK COMPLETED (cron-safe)
             ------------------------------------------------------------ */
            Cache::put($doneKey, true, now()->addHours(6));

            Log::info('[Scorecard] Generated successfully', [
                'simulation' => $sim,
                'group' => $group,
                'quarter' => $q,
            ]);
        } catch (\Throwable $e) {
            Log::error('[Scorecard] Generation failed', [
                'simulation' => $sim,
                'group' => $group,
                'quarter' => $q,
                'exception' => $e,
            ]);

            throw $e; // let Laravel retry
        } finally {
            try {
                optional($lock)->release();
            } catch (\Throwable $e) {
                Log::warning('[Scorecard] Failed to release lock', [
                    'lock' => $lockKey,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }
}
