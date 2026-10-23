<?php

namespace App\Jobs\QuarterClosing;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class AiRecommendationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    // Job configuration
    public $timeout = 120; // seconds per attempt

    public $tries = 5;     // number of attempts

    public $backoff = 10;  // seconds between retries

    protected int $simulationId;

    protected int $facultyId;

    protected $group;

    protected int $quarterId;

    /**
     * Create a new job instance.
     *
     * @param  mixed  $group
     */
    public function __construct(int $simulationId, int $facultyId, $group, int $quarterId)
    {
        $this->simulationId = $simulationId;
        $this->facultyId = $facultyId;
        $this->group = $group;
        $this->quarterId = $quarterId;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            $protocol = app()->environment('production') ? 'https' : 'http';
            $host = config('services.analysis.host', 'dev.analysis.flexee.org');
            $url = sprintf('%s://%s/api/ai-recommendation/', $protocol, $host);

            $payload = [
                'simulation_id' => $this->simulationId,
                'group_number' => $this->group,
                'faculty_id' => $this->facultyId,
                'quarter' => $this->quarterId,
            ];

            if ($this->quarterId < 6) {
                $payload = array_merge($payload, [
                    'forecast' => true,
                    'demand' => false,
                    'distribution' => false,
                    'procurement' => true,
                    'manufacturing' => true,
                    'service' => false,
                    'transportation' => false,
                    'it' => false,
                ]);
            } elseif ($this->quarterId < 7) {
                $payload = array_merge($payload, [
                    'forecast' => true,
                    'demand' => false,
                    'distribution' => true,
                    'procurement' => true,
                    'manufacturing' => true,
                    'service' => false,
                    'transportation' => true,
                    'it' => false,
                ]);
            } else {
                $payload = array_merge($payload, [
                    'forecast' => true,
                    'demand' => true,
                    'distribution' => true,
                    'procurement' => true,
                    'manufacturing' => true,
                    'service' => true,
                    'transportation' => true,
                    'it' => true,
                ]);
            }

            // Add auth header if required (uncomment and set token in config/services.php)
            // $token = config('services.analysis.token');
            // $request = Http::withToken($token);

            // Use retry for transient errors; Http::retry uses attempts, sleep(ms)
            $response = Http::retry(3, 200)
                ->timeout(60)
                ->acceptJson()
                ->post($url, $payload);

            if ($response->successful()) {
                Log::info('AiRecommendationJob: POST successful', [
                    'simulation_id' => $this->simulationId,
                    'faculty_id' => $this->facultyId,
                    'group' => $this->group,
                    'quarter' => $this->quarterId,
                    'status' => $response->status(),
                    'body_snippet' => Str::limit($response->body(), 512),
                ]);
            } else {
                // Non-2xx — log and let job retry (or fail depending on $tries)
                Log::warning('AiRecommendationJob: POST returned non-success', [
                    'simulation_id' => $this->simulationId,
                    'faculty_id' => $this->facultyId,
                    'group' => $this->group,
                    'quarter' => $this->quarterId,
                    'status' => $response->status(),
                    'body_snippet' => Str::limit($response->body(), 1024),
                ]);

                // Optionally throw to trigger retry via queue system:
                // throw new \Exception('Non-successful response: ' . $response->status());
            }
        } catch (\Throwable $e) {
            Log::error('AiRecommendationJob: Exception during POST', [
                'simulation_id' => $this->simulationId,
                'faculty_id' => $this->facultyId,
                'group' => $this->group,
                'quarter' => $this->quarterId,
                'error' => $e->getMessage(),
            ]);

            // Rethrow to let the queue system handle retries according to $tries/backoff
            throw $e;
        }
    }

    /**
     * The job failed to process.
     */
    public function failed(\Throwable $exception): void
    {
        // Optional: alerting, metrics, or more detailed logging
        Log::error('AiRecommendationJob: permanently failed', [
            'simulation_id' => $this->simulationId,
            'faculty_id' => $this->facultyId,
            'group' => $this->group,
            'quarter' => $this->quarterId,
            'error' => $exception->getMessage(),
        ]);
    }
}
