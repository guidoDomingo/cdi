<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Services\Icd11Service;

class Icd11ServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->app->singleton(Icd11Service::class, function ($app) {
            return new Icd11Service();
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