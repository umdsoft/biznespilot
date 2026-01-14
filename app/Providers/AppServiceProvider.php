<?php

namespace App\Providers;

use App\Models\CustdevResponse;
use App\Observers\CustdevResponseObserver;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Database\Eloquent\Model;
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

        // Production optimizations
        $this->configureProductionSettings();

        // Development tools
        $this->configureDevelopmentSettings();
    }

    /**
     * Configure production-specific settings.
     */
    private function configureProductionSettings(): void
    {
        if (!app()->environment('production')) {
            return;
        }

        // Disable lazy loading in production to catch N+1 issues early
        Model::preventLazyLoading(false);

        // Slow query logging
        $slowQueryThreshold = (int) config('kpi_sync.slow_query_threshold', 1000);

        DB::listen(function ($query) use ($slowQueryThreshold) {
            if ($query->time > $slowQueryThreshold) {
                Log::channel('slow-queries')->warning('Slow query detected', [
                    'sql' => $query->sql,
                    'bindings' => $query->bindings,
                    'time_ms' => $query->time,
                    'connection' => $query->connectionName,
                ]);
            }
        });
    }

    /**
     * Configure development-specific settings.
     */
    private function configureDevelopmentSettings(): void
    {
        if (app()->environment('production')) {
            return;
        }

        // Prevent lazy loading in development to catch N+1 issues
        Model::preventLazyLoading(!app()->runningInConsole());

        // Log all queries in development (optional, can be heavy)
        if (config('app.debug') && config('kpi_sync.query_log', false)) {
            DB::listen(function ($query) {
                Log::debug('Query executed', [
                    'sql' => $query->sql,
                    'bindings' => $query->bindings,
                    'time_ms' => $query->time,
                ]);
            });
        }
    }
}
