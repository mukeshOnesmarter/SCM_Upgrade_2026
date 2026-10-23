<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

class SendAiAnalysisReportJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public string $url;

    public int $tries = 3; // Retry up to 3 times

    public int $timeout = 600; // seconds

    public function __construct(string $url)
    {
        $this->url = $url;
    }

    /**
     * Prevent overlapping runs for the same URL for a short window.
     */
    public function middleware(): array
    {
        // 120s lock to avoid duplicate calls
        return [
            new WithoutOverlapping('send-ai:'.md5($this->url), 120),
        ];
    }

    public function handle(): void
    {
        $reqId = (string) Str::uuid();

        try {
            $response = Http::withHeaders(['X-Request-ID' => $reqId])
                ->timeout($this->timeout)
                ->retry(3, 5000)
                ->get($this->url);

            if ($response->successful()) {
                Log::info('[Financial Report AI Analysis] Completed');
            } else {
                // include small preview of body to help debugging
                $bodyPreview = substr((string) $response->body(), 0, 500);
                Log::error('AI Analysis API returned non-success status', [
                    'url' => $this->url,
                    'status' => $response->status(),
                    'body_preview' => $bodyPreview,
                    'request_id' => $reqId,
                ]);

                throw new \Exception("AI Analysis API returned status {$response->status()}");
            }
        } catch (Throwable $e) {
            Log::error("AI Analysis trigger failed for {$this->url}: {$e->getMessage()}", ['request_id' => $reqId]);
            throw $e; // let Laravel retry
        }
    }
}
