<?php

namespace App\Jobs;

use App\Services\ExternalUserApi;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class SyncUserBatch implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     */
    public int $tries = 3;

    /**
     * The number of seconds to wait before retrying the job.
     */
    public int $backoff = 60;

    public function __construct(
        public Collection $users
    ) {}

    public function handle(ExternalUserApi $api): void
    {
        $jobId = $this->job->getJobId();

        Log::info('Processing user sync batch', [
            'job_id' => $jobId,
            'attempt' => $this->attempts(),
            'user_count' => $this->users->count()
        ]);

        $subscribers = $this->users->map(function ($user) {
            return [
                'email' => $user->email,
                'name' => "{$user->first_name} {$user->last_name}",
                'time_zone' => $user->timezone,
            ];
        })->toArray();

        try {
            $api->syncBatch($subscribers);

            Log::info('User sync batch completed', [
                'job_id' => $jobId,
                'processed_users' => count($subscribers)
            ]);
        } catch (RuntimeException $e) {
            Log::error('User sync batch failed', [
                'job_id' => $jobId,
                'attempt' => $this->attempts(),
                'will_retry' => $this->attempts() < $this->tries,
                'next_retry_in' => $this->backoff,
                'error' => $e->getMessage()
            ]);

            report($e);
            throw $e;
        }
    }

    public function failed(\Throwable $e): void
    {
        Log::error('User sync batch failed permanently', [
            'job_id' => $this->job->getJobId(),
            'final_attempt' => $this->attempts(),
            'error' => $e->getMessage()
        ]);
    }
}
