<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class ExternalUserApi
{
    protected string $baseUrl;

    public function __construct()
    {
        $this->baseUrl = config('services.user_api.url', 'https://api.example.com');
    }

    public function syncBatch(array $subscribers): array
    {
        $requestId = uniqid('sync_');

        Log::info('Making API request', [
            'request_id' => $requestId,
            'subscriber_count' => count($subscribers),
            'endpoint' => $this->baseUrl . '/batch'
        ]);

        $startTime = microtime(true);

        try {
            $response = Http::withHeaders([
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
            ])->post($this->baseUrl . '/batch', [
                'batches' => [
                    [
                        'subscribers' => $subscribers
                    ]
                ]
            ]);

            $duration = round((microtime(true) - $startTime) * 1000);

            if ($response->failed()) {
                Log::error('API sync failed', [
                    'request_id' => $requestId,
                    'status' => $response->status(),
                    'duration_ms' => $duration,
                    'error' => $response->body()
                ]);

                throw new RuntimeException("API sync failed: " . $response->body());
            }

            Log::info('API sync completed', [
                'request_id' => $requestId,
                'status' => $response->status(),
                'duration_ms' => $duration,
                'subscriber_count' => count($subscribers)
            ]);

            return $response->json();
        } catch (\Exception $e) {
            Log::error('API sync exception', [
                'request_id' => $requestId,
                'exception' => get_class($e),
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            throw $e;
        }
    }
}
