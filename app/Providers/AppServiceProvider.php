<?php

namespace App\Providers;

use App\Models\User;
use App\Observers\UserObserver;
use App\Services\ExternalUserApi;
use App\Services\UserSyncService;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(UserSyncService::class);
        $this->app->singleton(ExternalUserApi::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        User::observe(UserObserver::class);
    }
}
