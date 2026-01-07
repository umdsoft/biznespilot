<?php

namespace App\Providers;

use App\Models\CustdevResponse;
use App\Observers\CustdevResponseObserver;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Register observers
        CustdevResponse::observe(CustdevResponseObserver::class);
    }
}
