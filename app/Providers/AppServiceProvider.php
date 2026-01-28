<?php

namespace App\Providers;

use App\Models\Business;
use App\Models\CallLog;
use App\Models\ContentGeneration;
use App\Models\Customer;
use App\Models\CustdevResponse;
use App\Models\Lead;
use App\Models\MarketingSpend;
use App\Models\Order;
use App\Models\Sale;
use App\Models\Task;
use App\Models\AttendanceRecord;
use App\Models\LeaveRequest;
use App\Models\EmployeeGoal;
use App\Models\EmployeeEngagement;
use App\Models\FlightRisk;
use App\Observers\BusinessObserver;
use App\Observers\CallLogObserver;
use App\Observers\ContentGenerationObserver;
use App\Observers\CustomerObserver;
use App\Observers\CustdevResponseObserver;
use App\Observers\LeadObserver;
use App\Observers\MarketingSpendObserver;
use App\Observers\OrderObserver;
use App\Observers\SaleObserver;
use App\Observers\TaskObserver;
use App\Observers\HR\AttendanceRecordObserver;
use App\Observers\HR\LeaveRequestObserver;
use App\Observers\HR\EmployeeGoalObserver;
use App\Observers\HR\EmployeeEngagementObserver;
use App\Observers\HR\FlightRiskObserver;
use App\Events\LeadStageChanged;
use App\Events\LeadScoreUpdated;
use App\Events\TaskCompleted;
use App\Events\LeadActivityCreated;
use App\Listeners\LeadStageChangedListener;
use App\Listeners\PipelineAutomationListener;
use App\Listeners\Sales\SalesIntegrationListener;
use App\Listeners\Marketing\MarketingIntegrationListener;
use App\Listeners\HR\HRIntegrationListener;
use App\Events\PaymentReceived;
use App\Events\PaymentSuccessEvent;
use App\Listeners\SendPaymentNotification;
use App\Listeners\ActivateSubscriptionListener;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
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

        // Configure rate limiters (must be in boot for config:cache compatibility)
        $this->configureRateLimiters();

        // Register observers
        Business::observe(BusinessObserver::class);
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
        Order::observe(OrderObserver::class);

        // Pipeline automation event subscriber
        Event::subscribe(PipelineAutomationListener::class);

        // Lead stage changed listener (KPI, Achievement, Leaderboard integratsiyasi)
        Event::listen(LeadStageChanged::class, LeadStageChangedListener::class);

        // Sales integration subscriber (Barcha sotuv eventlarini koordinatsiya qiladi)
        Event::subscribe(SalesIntegrationListener::class);

        // Marketing integration subscriber (Sales → Marketing KPI yangilash)
        Event::subscribe(MarketingIntegrationListener::class);

        // HR observers (Attendance, Leave, Goals, Engagement, FlightRisk)
        AttendanceRecord::observe(AttendanceRecordObserver::class);
        LeaveRequest::observe(LeaveRequestObserver::class);
        EmployeeGoal::observe(EmployeeGoalObserver::class);
        EmployeeEngagement::observe(EmployeeEngagementObserver::class);
        FlightRisk::observe(FlightRiskObserver::class);

        // HR integration subscriber (Sales → HR Engagement yangilash)
        Event::subscribe(HRIntegrationListener::class);

        // Payment received notification (Telegram real-time alerts)
        Event::listen(PaymentReceived::class, SendPaymentNotification::class);

        // SaaS Billing: To'lov muvaffaqiyatli bo'lganda obunani aktivlashtirish
        // (Payme/Click → PaymentSuccessEvent → ActivateSubscriptionListener)
        Event::listen(PaymentSuccessEvent::class, ActivateSubscriptionListener::class);

        // Production optimizations
        $this->configureProductionSettings();

        // Development tools
        $this->configureDevelopmentSettings();
    }

    /**
     * Configure rate limiters for API throttling.
     */
    private function configureRateLimiters(): void
    {
        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
        });

        RateLimiter::for('web', function (Request $request) {
            return Limit::perMinute(120)->by($request->user()?->id ?: $request->ip());
        });

        RateLimiter::for('ai', function (Request $request) {
            return Limit::perMinute(10)->by($request->user()?->id ?: $request->ip());
        });

        RateLimiter::for('webhooks', function (Request $request) {
            return Limit::perMinute(300)->by($request->ip());
        });

        RateLimiter::for('billing-webhooks', function (Request $request) {
            return Limit::perMinute(100)->by($request->ip());
        });

        RateLimiter::for('kpi-sync', function (Request $request) {
            return Limit::perHour(5)->by($request->user()?->id ?: $request->ip());
        });

        RateLimiter::for('kpi-monitoring', function (Request $request) {
            return Limit::perMinute(30)->by($request->user()?->id ?: $request->ip());
        });
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
