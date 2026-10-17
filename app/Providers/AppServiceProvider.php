<?php

namespace App\Providers;

use App\Contracts\Storage\MediaStorage;
use App\Services\Storage\SupabaseMediaStorage;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(MediaStorage::class, SupabaseMediaStorage::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}

