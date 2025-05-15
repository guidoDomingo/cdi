<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Services\Icd11Service;
use App\Services\Icd11EnhancedBrowserService;

class Icd11ServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        // Register the enhanced browser service
        $this->app->singleton(Icd11EnhancedBrowserService::class, function ($app) {
            return new Icd11EnhancedBrowserService();
        });

        // Register the main ICD11 service with dependency
        $this->app->singleton(Icd11Service::class, function ($app) {
            return new Icd11Service(
                $app->make(Icd11EnhancedBrowserService::class)
            );
        });
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        //
    }
}
