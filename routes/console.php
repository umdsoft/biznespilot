<?php

use App\Jobs\GenerateDailyInsights;
use App\Jobs\GenerateMonthlyStrategy;
use App\Jobs\SyncAllChannelsMetrics;
use App\Jobs\SyncMetaInsightsJob;
use App\Jobs\SyncInstagramDataJob;
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

// Schedule AI insights generation daily at 3:00 AM (after metrics sync)
Schedule::job(new GenerateDailyInsights())
    ->dailyAt('03:00')
    ->timezone('Asia/Tashkent')
    ->name('generate-daily-insights')
    ->onOneServer();

// Schedule monthly strategy generation on the 1st of each month at 4:00 AM
Schedule::job(new GenerateMonthlyStrategy())
    ->monthlyOn(1, '04:00')
    ->timezone('Asia/Tashkent')
    ->name('generate-monthly-strategy')
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
