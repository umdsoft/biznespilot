<?php

namespace App\Providers;

use App\Models\CallLog;
use App\Models\ContentGeneration;
use App\Models\Customer;
use App\Models\CustdevResponse;
use App\Models\Lead;
use App\Models\MarketingSpend;
use App\Models\Sale;
use App\Models\Task;
use App\Observers\CallLogObserver;
use App\Observers\ContentGenerationObserver;
use App\Observers\CustomerObserver;
use App\Observers\CustdevResponseObserver;
use App\Observers\LeadObserver;
use App\Observers\MarketingSpendObserver;
use App\Observers\SaleObserver;
use App\Observers\TaskObserver;
use App\Events\LeadStageChanged;
use App\Events\LeadScoreUpdated;
use App\Events\TaskCompleted;
use App\Events\LeadActivityCreated;
use App\Listeners\LeadStageChangedListener;
use App\Listeners\PipelineAutomationListener;
use App\Listeners\Sales\SalesIntegrationListener;
use App\Listeners\Marketing\MarketingIntegrationListener;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
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

        // Sales KPI & Gamification observers
        Lead::observe(LeadObserver::class);
        Task::observe(TaskObserver::class);
        CallLog::observe(CallLogObserver::class);

        // Marketing Finance integration observer (MarketingSpend → BudgetAllocation sinxronlash)
        MarketingSpend::observe(MarketingSpendObserver::class);

        // Content AI observer (Generation → Idea metrics sinxronlash)
        ContentGeneration::observe(ContentGenerationObserver::class);

        // Cross-module attribution observers (Marketing ↔ Sales ↔ Finance)
        Sale::observe(SaleObserver::class);
        Customer::observe(CustomerObserver::class);

        // Pipeline automation event subscriber
        Event::subscribe(PipelineAutomationListener::class);

        // Lead stage changed listener (KPI, Achievement, Leaderboard integratsiyasi)
        Event::listen(LeadStageChanged::class, LeadStageChangedListener::class);

        // Sales integration subscriber (Barcha sotuv eventlarini koordinatsiya qiladi)
        Event::subscribe(SalesIntegrationListener::class);

        // Marketing integration subscriber (Sales → Marketing KPI yangilash)
        Event::subscribe(MarketingIntegrationListener::class);

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
        if (! app()->environment('production')) {
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
        Model::preventLazyLoading(! app()->runningInConsole());

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
