<?php

namespace App\Observers;

use App\Models\User;
use App\Services\UserSyncService;

class UserObserver
{
    public function __construct(
        protected UserSyncService $syncService
    ) {}

    public function updated(User $user): void
    {
        if ($user->wasChanged(['first_name', 'last_name', 'timezone'])) {
            $this->syncService->syncUsers(collect([$user]));
        }
    }
}
