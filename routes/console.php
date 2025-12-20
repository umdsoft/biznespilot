<?php

use App\Jobs\GenerateDailyInsights;
use App\Jobs\GenerateMonthlyStrategy;
use App\Jobs\SyncAllChannelsMetrics;
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
