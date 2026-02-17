<?php

namespace App\Jobs\Marketing;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;

/**
 * UnifiedMarketingAlertsJob — Barcha marketing alertlarni birlashtirgan job
 *
 * Quyidagilarni o'z ichiga oladi:
 * 1. CheckMarketingAlertsJob — CPL, ROAS, Budget anomaliyalar
 * 2. kpi:check-alerts — KPI rule-based alertlar
 * 3. CheckCampaignPerformanceJob — Meta/Google Ads kampaniya samaradorligi
 *
 * Har 2 soatda ishga tushiriladi (analytics queue)
 */
class UnifiedMarketingAlertsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;
    public int $timeout = 1200;

    public function handle(): void
    {
        Log::info('UnifiedMarketingAlertsJob: Starting all alert checks');

        // 1. Marketing Alerts (CPL, ROAS, Budget anomaliyalar)
        try {
            CheckMarketingAlertsJob::dispatchSync();
        } catch (\Exception $e) {
            Log::error('UnifiedMarketingAlertsJob: Marketing alerts failed', [
                'error' => $e->getMessage(),
            ]);
        }

        // 2. KPI Alerts (KPI rule-based alertlar)
        try {
            Artisan::call('kpi:check-alerts');
        } catch (\Exception $e) {
            Log::error('UnifiedMarketingAlertsJob: KPI alerts failed', [
                'error' => $e->getMessage(),
            ]);
        }

        // 3. Campaign Performance (Meta/Google Ads budget, CTR, CPR)
        try {
            CheckCampaignPerformanceJob::dispatchSync();
        } catch (\Exception $e) {
            Log::error('UnifiedMarketingAlertsJob: Campaign performance check failed', [
                'error' => $e->getMessage(),
            ]);
        }

        Log::info('UnifiedMarketingAlertsJob: All alert checks completed');
    }

    public function tags(): array
    {
        return ['unified-marketing-alerts'];
    }
}
