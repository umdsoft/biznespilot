<?php

namespace App\Providers;

use App\Models\Business;
use App\Models\CallLog;
use App\Models\ContentGeneration;
use App\Models\ContentPost;
use App\Models\Customer;
use App\Models\CustdevResponse;
use App\Models\Lead;
use App\Models\MarketingSpend;
use App\Models\Order;
use App\Models\Sale;
use App\Models\Store\StoreOrder;
use App\Models\Task;
use App\Models\AttendanceRecord;
use App\Models\LeaveRequest;
use App\Models\EmployeeGoal;
use App\Models\EmployeeEngagement;
use App\Models\FlightRisk;
use App\Observers\BusinessObserver;
use App\Observers\CallLogObserver;
use App\Observers\ContentGenerationObserver;
use App\Observers\ContentPostObserver;
use App\Observers\CustomerObserver;
use App\Observers\CustdevResponseObserver;
use App\Observers\LeadObserver;
use App\Observers\MarketingSpendObserver;
use App\Observers\OrderObserver;
use App\Observers\SaleObserver;
use App\Observers\StoreOrderObserver;
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
use App\Listeners\Marketing\MarketingOrchestratorListener;
use App\Listeners\HR\HRIntegrationListener;
use App\Observers\LeadActivityObserver;
use App\Events\PaymentReceived;
use App\Events\PaymentSuccessEvent;
use App\Listeners\LogAuthActivity;
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

        // ContentPost → ContentIdea (har bir saqlangan kontent avtomatik g'oyalar bankiga tushadi)
        ContentPost::observe(ContentPostObserver::class);

        // Cross-module attribution observers (Marketing ↔ Sales ↔ Finance)
        Sale::observe(SaleObserver::class);
        Customer::observe(CustomerObserver::class);
        Order::observe(OrderObserver::class);

        // Store order → Lead pipeline (o'chirildi: buyurtmalar alohida boshqariladi, CRM lidlarga tushmasligi kerak)
        // StoreOrder::observe(StoreOrderObserver::class);

        // Bot observers (Delivery, Queue, Service)
        \App\Models\Bot\Delivery\DeliveryOrder::observe(\App\Observers\Bot\DeliveryOrderObserver::class);
        \App\Models\Bot\Queue\QueueBooking::observe(\App\Observers\Bot\QueueBookingObserver::class);
        \App\Models\Bot\Service\ServiceRequest::observe(\App\Observers\Bot\ServiceRequestObserver::class);

        // Pipeline automation event subscriber
        Event::subscribe(PipelineAutomationListener::class);

        // Lead stage changed listener (KPI, Achievement, Leaderboard integratsiyasi)
        Event::listen(LeadStageChanged::class, LeadStageChangedListener::class);

        // Sales integration subscriber (Barcha sotuv eventlarini koordinatsiya qiladi)
        Event::subscribe(SalesIntegrationListener::class);

        // Marketing integration subscriber (Sales → Marketing KPI yangilash)
        Event::subscribe(MarketingIntegrationListener::class);

        // Marketing Orchestrator subscriber (content/competitor/campaign event → cache invalidate)
        Event::subscribe(MarketingOrchestratorListener::class);

        // Lead activity observer (har Lead update'da activity log)
        Lead::observe(LeadActivityObserver::class);

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

        // Auth activity log: Login, Logout, Failed events → ActivityLog jadvali
        // (admin "Faoliyat jurnali" sahifasi ma'lumot manbasi)
        Event::subscribe(LogAuthActivity::class);

        // Partner Program: har to'lovdan 10%/5% commission yozib borish
        Event::listen(
            PaymentSuccessEvent::class,
            \App\Listeners\Partner\RecordPartnerCommissionListener::class
        );

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

        // Mini App public catalog — cheap reads, higher ceiling per store slug
        RateLimiter::for('miniapp-public', function (Request $request) {
            $storeKey = $request->route('store') instanceof \App\Models\Store\TelegramStore
                ? $request->route('store')->slug
                : (string) $request->route('store');

            return Limit::perMinute(180)->by(($storeKey ?: 'anon') . '|' . $request->ip());
        });

        // Mini App authenticated writes — checkout, cart mutations, profile
        // Scoped per (telegram user | ip) to block scripted flooding.
        RateLimiter::for('miniapp-auth', function (Request $request) {
            $customer = $request->attributes->get('store_customer');
            $identity = $customer?->id ?: $request->ip();

            return Limit::perMinute(60)->by('miniapp-auth:' . $identity);
        });

        // Checkout endpoint gets its own tight bucket — prevents rapid-fire
        // duplicate orders from the same customer.
        RateLimiter::for('miniapp-checkout', function (Request $request) {
            $customer = $request->attributes->get('store_customer');
            $identity = $customer?->id ?: $request->ip();

            return Limit::perMinute(10)->by('miniapp-checkout:' . $identity);
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
