<?php

namespace App\Jobs\QuarterClosing;

use App\Services\AiDecisionSaveService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class SyncAiDecisionsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $simulationId;

    public ?int $facultyId;

    public ?int $groupNumber;

    public int $quarterId;

    public int $timeoutSeconds;

    public $timeout = 300;

    public $tries = 3;

    public function __construct(int $simulationId, ?int $facultyId, ?int $groupNumber, int $quarterId, int $timeoutSeconds = 180)
    {
        $this->simulationId = $simulationId;
        $this->facultyId = $facultyId;
        $this->groupNumber = $groupNumber;
        $this->quarterId = $quarterId;
        $this->timeoutSeconds = $timeoutSeconds;
    }

    public function middleware(): array
    {
        $key = sprintf(
            'sync-ai:%d:%s:%s:%d',
            $this->simulationId,
            $this->facultyId === null ? 'null' : (string) $this->facultyId,
            $this->groupNumber === null ? 'null' : (string) $this->groupNumber,
            $this->quarterId
        );

        // TTL: slightly more than expected runtime
        $ttl = max(300, $this->timeoutSeconds + 120);

        return [new WithoutOverlapping($key, $ttl)];
    }

    public function handle(AiDecisionSaveService $aiDecisionSaveService)
    {
        $start = microtime(true);
        $reqId = (string) Str::uuid();

        $keySuffixFaculty = $this->facultyId === null ? 'null' : (string) $this->facultyId;
        $keySuffixGroup = $this->groupNumber === null ? 'null' : (string) $this->groupNumber;

        $lockKey = sprintf(
            'sync-ai-lock:%d:%s:%s:%d',
            $this->simulationId,
            $keySuffixFaculty,
            $keySuffixGroup,
            $this->quarterId
        );

        // Keep lock TTL consistent with middleware TTL (choose one policy)
        $lockTtl = max(900, $this->timeoutSeconds + 300);

        Log::info('SyncAiDecisionsJob starting', [
            'request_id' => $reqId,
            'simulation_id' => $this->simulationId,
            'faculty_id' => $this->facultyId,
            'group' => $this->groupNumber,
            'quarter' => $this->quarterId,
            'lock_key' => $lockKey,
        ]);

        $lock = Cache::lock($lockKey, $lockTtl);

        if (! $lock->get()) {
            Log::warning('SyncAiDecisionsJob lock already present — skipping execution', [
                'request_id' => $reqId,
                'simulation_id' => $this->simulationId,
                'faculty_id' => $this->facultyId,
                'group' => $this->groupNumber,
                'quarter' => $this->quarterId,
                'lock_key' => $lockKey,
            ]);

            return;
        }

        try {
            $result = $aiDecisionSaveService->fetchAndSaveAllRecommendations(
                $this->simulationId,
                $this->facultyId,
                $this->groupNumber,
                $this->quarterId,
                [], // extra payload
                $reqId // request id for traceability
            );

            $duration = round(microtime(true) - $start, 3);

            if (! ($result['success'] ?? false)) {
                Log::error('SyncAiDecisionsJob: fetch/save failed', [
                    'request_id' => $reqId,
                    'simulation_id' => $this->simulationId,
                    'faculty_id' => $this->facultyId,
                    'group' => $this->groupNumber,
                    'quarter' => $this->quarterId,
                    'errors' => $result['errors'] ?? [],
                    'duration_s' => $duration,
                ]);
            } else {
                Log::info('SyncAiDecisionsJob: fetched and saved successfully', [
                    'request_id' => $reqId,
                    'simulation_id' => $this->simulationId,
                    'faculty_id' => $this->facultyId,
                    'group' => $this->groupNumber,
                    'quarter' => $this->quarterId,
                    'modules_saved' => array_keys($result['inserted'] ?? []),
                    'duration_s' => $duration,
                ]);

                // === NEW: set ai_ready cache key so ProcessLastGroupPostAiJob can proceed ===
                // Use the exact key format ProcessLastGroupPostAiJob is checking:
                // "ai_ready:{simulation}:{faculty}:{group}:{quarter}"
                $facultyKeyPart = $this->facultyId === null ? 'null' : (string) $this->facultyId;
                $groupKeyPart = $this->groupNumber === null ? 'null' : (string) $this->groupNumber;
                $aiReadyKey = sprintf('ai_ready:%d:%s:%s:%d', $this->simulationId, $facultyKeyPart, $groupKeyPart, $this->quarterId);

                try {
                    // TTL 30 minutes (tunable)
                    Cache::put($aiReadyKey, true, now()->addMinutes(30));

                    Log::info('SyncAiDecisionsJob: set ai_ready flag', [
                        'request_id' => $reqId,
                        'ai_ready_key' => $aiReadyKey,
                        'simulation_id' => $this->simulationId,
                        'faculty_id' => $this->facultyId,
                        'group' => $this->groupNumber,
                        'quarter' => $this->quarterId,
                    ]);
                } catch (\Throwable $cacheEx) {
                    Log::warning('SyncAiDecisionsJob: failed to set ai_ready cache key', [
                        'request_id' => $reqId,
                        'ai_ready_key' => $aiReadyKey,
                        'error' => $cacheEx->getMessage(),
                    ]);
                }
            }
        } catch (\Throwable $e) {
            Log::error('SyncAiDecisionsJob exception', [
                'request_id' => $reqId,
                'simulation_id' => $this->simulationId,
                'faculty_id' => $this->facultyId,
                'group' => $this->groupNumber,
                'quarter' => $this->quarterId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        } finally {
            try {
                $lock->release();
            } catch (\Throwable $releaseEx) {
                Log::warning('Failed to release SyncAiDecisionsJob lock', [
                    'request_id' => $reqId,
                    'lock_key' => $lockKey,
                    'error' => $releaseEx->getMessage(),
                ]);
            }
        }
    }
}
