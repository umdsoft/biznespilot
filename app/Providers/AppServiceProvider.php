<?php

namespace App\Providers;

use App\Models\CustdevResponse;
use App\Observers\CustdevResponseObserver;
use Illuminate\Support\Facades\URL;
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
        // Force HTTPS when APP_URL is HTTPS (for cloudflared, ngrok, etc.)
        if (str_starts_with(config('app.url'), 'https://')) {
            URL::forceScheme('https');
        }

        // Register observers
        CustdevResponse::observe(CustdevResponseObserver::class);
    }
}
