<?php

namespace App\Services;

use App\Jobs\SyncUserBatch;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class UserSyncService
{
    const BATCH_SIZE = 1000;

    public function syncUsers(Collection $users): void
    {
        $totalUsers = $users->count();
        $totalBatches = ceil($totalUsers / self::BATCH_SIZE);

        Log::info('Starting user sync process', [
            'total_users' => $totalUsers,
            'batch_size' => self::BATCH_SIZE,
            'total_batches' => $totalBatches
        ]);

        $users->chunk(self::BATCH_SIZE)->each(function ($batch, $index) use ($totalBatches) {
            $delay = now()->addSeconds(72 * $index);

            Log::info('Dispatching user sync batch', [
                'batch_number' => $index + 1,
                'total_batches' => $totalBatches,
                'users_in_batch' => $batch->count(),
                'scheduled_for' => $delay->toISOString()
            ]);

            SyncUserBatch::dispatch($batch)
                ->onQueue('user-sync')
                ->delay($delay);
        });
    }
}
