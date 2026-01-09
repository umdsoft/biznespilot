<?php

use App\Jobs\AggregateMonthlyKpisJob;
use App\Jobs\AggregateWeeklyKpisJob;
use App\Jobs\AnomalyDetectionJob;
use App\Jobs\ChurnPreventionJob;
use App\Jobs\CustomerSegmentationJob;
use App\Jobs\DailyBusinessDiagnosticJob;
use App\Jobs\SocialMediaSyncJob;
use App\Jobs\SyncAllChannelsMetrics;
use App\Jobs\SyncDailyKpisFromIntegrationsJob;
use App\Jobs\SyncInstagramDataJob;
use App\Jobs\SyncMetaInsightsJob;
use App\Models\Business;
use App\Models\Integration;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Schedule marketing metrics sync daily at 2:00 AM
Schedule::job(new SyncAllChannelsMetrics())
    ->dailyAt('02:00')
    ->timezone('Asia/Tashkent')
    ->name('sync-marketing-metrics')
    ->onOneServer();

// Schedule Meta Ads sync every 2 hours for all connected businesses
Schedule::call(function () {
    $integrations = Integration::where('type', 'meta_ads')
        ->where('status', 'connected')
        ->get();

    foreach ($integrations as $integration) {
        SyncMetaInsightsJob::dispatch($integration->business_id, false); // incremental sync
    }
})->everyTwoHours()
    ->timezone('Asia/Tashkent')
    ->name('sync-meta-ads-incremental')
    ->onOneServer();

// Schedule Instagram sync every 2 hours for all connected businesses
Schedule::call(function () {
    $integrations = Integration::where('type', 'meta_ads')
        ->where('status', 'connected')
        ->get();

    foreach ($integrations as $integration) {
        SyncInstagramDataJob::dispatch($integration->business_id, false); // incremental sync
    }
})->everyTwoHours()
    ->timezone('Asia/Tashkent')
    ->name('sync-instagram-incremental')
    ->onOneServer()
    ->after(function () {
        // Run 5 minutes after Meta sync to avoid API rate limits
    });

// Full sync daily at 1:00 AM for Meta Ads (12 months data refresh)
Schedule::call(function () {
    $integrations = Integration::where('type', 'meta_ads')
        ->where('status', 'connected')
        ->get();

    foreach ($integrations as $integration) {
        SyncMetaInsightsJob::dispatch($integration->business_id, true); // full sync
    }
})->dailyAt('01:00')
    ->timezone('Asia/Tashkent')
    ->name('sync-meta-ads-full')
    ->onOneServer();

// Full sync daily at 01:30 AM for Instagram
Schedule::call(function () {
    $integrations = Integration::where('type', 'meta_ads')
        ->where('status', 'connected')
        ->get();

    foreach ($integrations as $integration) {
        SyncInstagramDataJob::dispatch($integration->business_id, true); // full sync
    }
})->dailyAt('01:30')
    ->timezone('Asia/Tashkent')
    ->name('sync-instagram-full')
    ->onOneServer();

// ==========================================
// AI-FREE ALGORITHM SCHEDULED JOBS
// ==========================================

// Daily Business Diagnostic - Har kuni ertalab 6:00
Schedule::call(function () {
    $businesses = Business::where('status', 'active')->get();

    foreach ($businesses as $business) {
        DailyBusinessDiagnosticJob::dispatch($business);
    }
})->dailyAt('06:00')
    ->timezone('Asia/Tashkent')
    ->name('daily-business-diagnostic')
    ->onOneServer();

// Anomaly Detection - Har soatda
Schedule::call(function () {
    $businesses = Business::where('status', 'active')->get();

    foreach ($businesses as $business) {
        AnomalyDetectionJob::dispatch($business);
    }
})->hourly()
    ->timezone('Asia/Tashkent')
    ->name('anomaly-detection')
    ->onOneServer();

// Customer Segmentation - Har hafta dushanba 8:00
Schedule::call(function () {
    $businesses = Business::where('status', 'active')->get();

    foreach ($businesses as $business) {
        CustomerSegmentationJob::dispatch($business);
    }
})->weeklyOn(1, '08:00') // 1 = Monday
    ->timezone('Asia/Tashkent')
    ->name('customer-segmentation')
    ->onOneServer();

// Churn Prevention - Har kuni 10:00
Schedule::call(function () {
    $businesses = Business::where('status', 'active')->get();

    foreach ($businesses as $business) {
        ChurnPreventionJob::dispatch($business);
    }
})->dailyAt('10:00')
    ->timezone('Asia/Tashkent')
    ->name('churn-prevention')
    ->onOneServer();

// Social Media Sync - Har 6 soatda
Schedule::call(function () {
    $businesses = Business::where('status', 'active')
        ->whereHas('instagramAccounts')
        ->get();

    foreach ($businesses as $business) {
        SocialMediaSyncJob::dispatch($business);
    }
})->everySixHours()
    ->timezone('Asia/Tashkent')
    ->name('social-media-sync')
    ->onOneServer();

// ==========================================
// KPI SYSTEM SCHEDULED JOBS
// ==========================================

// Daily KPI Sync from All Integrations - Har kuni ertalab 5:00
// Bu job barcha integratsiyalardan (Instagram, Facebook, POS) KPI ma'lumotlarini to'playdi
Schedule::call(function () {
    // Yesterday's data is synced because today's data might not be complete yet
    SyncDailyKpisFromIntegrationsJob::dispatch(null, null)
        ->onQueue('kpi-sync');
})->dailyAt('05:00')
    ->timezone('Asia/Tashkent')
    ->name('sync-kpis-from-integrations')
    ->onOneServer();

// Weekly KPI Aggregation - Har dushanba kuni 7:00 da
// Haftalik aggregatsiyani bajaradi
Schedule::call(function () {
    AggregateWeeklyKpisJob::dispatch(null, null)
        ->onQueue('kpi-aggregation');
})->weeklyOn(1, '07:00') // Monday at 7:00 AM
    ->timezone('Asia/Tashkent')
    ->name('aggregate-weekly-kpis')
    ->onOneServer();

// Monthly KPI Aggregation - Har oy 1-sanasida 8:00 da
// Oylik aggregatsiyani bajaradi
Schedule::call(function () {
    AggregateMonthlyKpisJob::dispatch(null, null, null)
        ->onQueue('kpi-aggregation');
})->monthlyOn(1, '08:00') // 1st day of month at 8:00 AM
    ->timezone('Asia/Tashkent')
    ->name('aggregate-monthly-kpis')
    ->onOneServer();

// ==========================================
// COMPETITOR MONITORING SCHEDULED JOBS
// ==========================================

// Competitor Monitoring - Har soatda auto_monitor yoqilgan raqobatchilarni kuzatish
// check_frequency_hours sozlamasiga qarab ishga tushadi
Schedule::job(new \App\Jobs\ScrapeCompetitorData())
    ->hourly()
    ->timezone('Asia/Tashkent')
    ->name('competitor-monitoring')
    ->onOneServer();

// ==========================================
// TODO RECURRING TASKS
// ==========================================

// Generate Recurring Todos - Har kuni ertalab 6:00 da
// Takrorlanadigan vazifalarni avtomatik yaratadi
Schedule::command('todos:generate-recurring')
    ->dailyAt('06:00')
    ->timezone('Asia/Tashkent')
    ->name('generate-recurring-todos')
    ->onOneServer();
