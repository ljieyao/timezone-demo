<?php

namespace Tests\Feature;

use App\Jobs\SyncUserBatch;
use App\Models\User;
use App\Services\UserSyncService;
use App\Services\ExternalUserApi;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;
use RuntimeException;

class UserSyncTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Queue::fake();
    }

    public function test_can_sync_users_in_batches(): void
    {
        Http::fake([
            '*/batch' => Http::response(['status' => 'success'], 200)
        ]);

        $users = User::factory()->count(3)->create([
            'timezone' => 'UTC'
        ]);

        $service = new UserSyncService();
        $service->syncUsers($users);

        Queue::assertPushed(SyncUserBatch::class, function ($job) use ($users) {
            return $job->users->count() === 3;
        });
    }

    public function test_handles_api_failure(): void
    {
        Http::fake([
            '*/batch' => Http::response('Server Error', 500)
        ]);

        $user = User::factory()->create([
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => 'john@example.com',
            'timezone' => 'UTC'
        ]);

        $api = new ExternalUserApi();

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('API sync failed: Server Error');

        $api->syncBatch([
            [
                'email' => $user->email,
                'name' => $user->name,
                'time_zone' => $user->timezone,
            ]
        ]);
    }

    public function test_respects_batch_size_limits(): void
    {
        Http::fake([
            '*/batch' => Http::response(['status' => 'success'], 200)
        ]);

        $users = User::factory()->count(1200)->create();

        $service = new UserSyncService();
        $service->syncUsers($users);

        Queue::assertPushed(SyncUserBatch::class, 2);
    }

    public function test_observer_triggers_sync_on_relevant_changes(): void
    {
        Http::fake([
            '*/batch' => Http::response(['status' => 'success'], 200)
        ]);

        $user = User::factory()->create([
            'first_name' => 'John',
            'last_name' => 'Doe',
            'timezone' => 'UTC'
        ]);

        // Should trigger sync
        $user->update(['timezone' => 'GMT']);
        Queue::assertPushed(SyncUserBatch::class, 1);

        // Should trigger sync
        $user->update(['first_name' => 'Jane']);
        Queue::assertPushed(SyncUserBatch::class, 2);

        // Should NOT trigger sync
        $user->update(['password' => 'newpassword']);
        Queue::assertPushed(SyncUserBatch::class, 2);
    }
}
