<?php

use App\Jobs\AggregateMonthlyKpisJob;
use App\Jobs\AggregateWeeklyKpisJob;
use App\Jobs\AnomalyDetectionJob;
use App\Jobs\CustomerSegmentationJob;
use App\Jobs\Marketing\CalculateMarketingKpiSnapshotsJob;
use App\Jobs\SyncDailyKpisFromIntegrationsJob;
use App\Jobs\Telegram\RollupTelegramChannelDailyStatsJob;
use App\Jobs\Telegram\SendTelegramChannelDigestJob;
use App\Jobs\Telegram\SyncTelegramChannelStatsJob;
use App\Models\Business;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// ==========================================
// TELEGRAM CHANNEL ANALYTICS
// ==========================================

// Sync channel core info + snapshot post views — every 30 minutes
Schedule::job(new SyncTelegramChannelStatsJob)
    ->everyThirtyMinutes()
    ->timezone('Asia/Tashkent')
    ->name('telegram-channels-sync')
    ->withoutOverlapping(15)
    ->onOneServer();

// Daily rollup at 23:55 — captures today's metrics
Schedule::job(new RollupTelegramChannelDailyStatsJob)
    ->dailyAt('23:55')
    ->timezone('Asia/Tashkent')
    ->name('telegram-channels-rollup')
    ->onOneServer();

// Daily digest to channel owner at 08:00 (yesterday's numbers)
Schedule::job(new SendTelegramChannelDigestJob)
    ->dailyAt('08:00')
    ->timezone('Asia/Tashkent')
    ->name('telegram-channels-digest')
    ->onOneServer();

// ==========================================
// [FB/IG] DISABLED — Meta review kutilmoqda
// Meta tasdiqlagandan keyin quyidagi 5 ta jobni qayta yoqish kerak
// ==========================================

// Schedule marketing metrics sync daily at 2:00 AM
// Schedule::job(new SyncAllChannelsMetrics)
//     ->dailyAt('02:00')
//     ->timezone('Asia/Tashkent')
//     ->name('sync-marketing-metrics')
//     ->onOneServer();

// Schedule Meta Ads sync every 2 hours for all connected businesses
// Schedule::call(function () {
//     $integrations = Integration::where('type', 'meta_ads')
//         ->where('status', 'connected')
//         ->get();
//     foreach ($integrations as $integration) {
//         SyncMetaInsightsJob::dispatch($integration->business_id, false)->onQueue('integrations');
//     }
// })->everyTwoHours()
//     ->timezone('Asia/Tashkent')
//     ->name('sync-meta-ads-incremental')
//     ->onOneServer();

// Schedule Instagram sync every 2 hours for all connected businesses
// Schedule::call(function () {
//     $integrations = Integration::where('type', 'meta_ads')
//         ->where('status', 'connected')
//         ->get();
//     foreach ($integrations as $integration) {
//         SyncInstagramDataJob::dispatch($integration->business_id, false)->onQueue('integrations');
//     }
// })->everyTwoHours()
//     ->timezone('Asia/Tashkent')
//     ->name('sync-instagram-incremental')
//     ->onOneServer();

// Full sync daily at 1:00 AM for Meta Ads (12 months data refresh)
// Schedule::call(function () {
//     $integrations = Integration::where('type', 'meta_ads')
//         ->where('status', 'connected')
//         ->get();
//     foreach ($integrations as $integration) {
//         SyncMetaInsightsJob::dispatch($integration->business_id, true)->onQueue('integrations');
//     }
// })->dailyAt('01:00')
//     ->timezone('Asia/Tashkent')
//     ->name('sync-meta-ads-full')
//     ->onOneServer();

// Full sync daily at 01:30 AM for Instagram
// Schedule::call(function () {
//     $integrations = Integration::where('type', 'meta_ads')
//         ->where('status', 'connected')
//         ->get();
//     foreach ($integrations as $integration) {
//         SyncInstagramDataJob::dispatch($integration->business_id, true)->onQueue('integrations');
//     }
// })->dailyAt('01:30')
//     ->timezone('Asia/Tashkent')
//     ->name('sync-instagram-full')
//     ->onOneServer();

// ==========================================
// AI-FREE ALGORITHM SCHEDULED JOBS
// ==========================================

// Daily Business Diagnostic - DISABLED (diagnostic_reports jadvali olib tashlandi)
// AiDiagnostic modelga o'tkazilgach qayta yoqiladi
// Schedule::call(function () {
//     Business::where('status', 'active')->chunk(100, function ($businesses) {
//         foreach ($businesses as $business) {
//             DailyBusinessDiagnosticJob::dispatch($business);
//         }
//     });
// })->dailyAt('06:00')
//     ->timezone('Asia/Tashkent')
//     ->name('daily-business-diagnostic')
//     ->onOneServer();

// Anomaly Detection - Har 4 soatda (optimallashtirish: 24/kun → 6/kun)
Schedule::call(function () {
    Business::where('status', 'active')->chunk(100, function ($businesses) {
        foreach ($businesses as $business) {
            AnomalyDetectionJob::dispatch($business)->onQueue('analytics');
        }
    });
})->everyFourHours()
    ->timezone('Asia/Tashkent')
    ->name('anomaly-detection')
    ->onOneServer();

// Customer Segmentation - Har hafta dushanba 8:00
Schedule::call(function () {
    Business::where('status', 'active')->chunk(100, function ($businesses) {
        foreach ($businesses as $business) {
            CustomerSegmentationJob::dispatch($business)->onQueue('analytics');
        }
    });
})->weeklyOn(1, '08:00') // 1 = Monday
    ->timezone('Asia/Tashkent')
    ->name('customer-segmentation')
    ->onOneServer();

// [MERGED] Churn Prevention → ChurnAnalysisPipelineJob ga birlashtirildi
// Pipeline: CalculateChurnRiskJob → ChurnPreventionJob (ketma-ket)
// Schedule::call(function () {
//     Business::where('status', 'active')->chunk(100, function ($businesses) {
//         foreach ($businesses as $business) {
//             ChurnPreventionJob::dispatch($business)->onQueue('analytics');
//         }
//     });
// })->dailyAt('10:00')
//     ->timezone('Asia/Tashkent')
//     ->name('churn-prevention')
//     ->onOneServer();

// Churn Analysis Pipeline - Har kuni 07:00 da
// 1) CalculateChurnRiskJob — churn xavfini hisoblash
// 2) ChurnPreventionJob — xavfli mijozlar uchun retention harakatlar
Schedule::job((new \App\Jobs\ChurnAnalysisPipelineJob)->onQueue('analytics'))
    ->dailyAt('07:00')
    ->timezone('Asia/Tashkent')
    ->name('churn-analysis-pipeline')
    ->onOneServer();

// [FB/IG] DISABLED — Meta review kutilmoqda
// Social Media Sync - Har 6 soatda
// Schedule::call(function () {
//     Business::where('status', 'active')
//         ->whereHas('instagramAccounts')
//         ->chunk(100, function ($businesses) {
//             foreach ($businesses as $business) {
//                 SocialMediaSyncJob::dispatch($business)->onQueue('integrations');
//             }
//         });
// })->everySixHours()
//     ->timezone('Asia/Tashkent')
//     ->name('social-media-sync')
//     ->onOneServer();

// ==========================================
// KPI SYSTEM SCHEDULED JOBS
// ==========================================

// Daily KPI Sync from All Integrations - Har kuni ertalab 5:00
// Bu job barcha integratsiyalardan (Instagram, Facebook, POS) KPI ma'lumotlarini to'playdi
Schedule::call(function () {
    // Yesterday's data is synced because today's data might not be complete yet
    SyncDailyKpisFromIntegrationsJob::dispatch(null, null)
        ->onQueue('analytics');
})->dailyAt('05:00')
    ->timezone('Asia/Tashkent')
    ->name('sync-kpis-from-integrations')
    ->onOneServer();

// Weekly KPI Aggregation - Har dushanba kuni 7:00 da
// Haftalik aggregatsiyani bajaradi
Schedule::call(function () {
    AggregateWeeklyKpisJob::dispatch(null, null)
        ->onQueue('analytics');
})->weeklyOn(1, '07:00') // Monday at 7:00 AM
    ->timezone('Asia/Tashkent')
    ->name('aggregate-weekly-kpis')
    ->onOneServer();

// Monthly KPI Aggregation - Har oy 1-sanasida 8:00 da
// Oylik aggregatsiyani bajaradi
Schedule::call(function () {
    AggregateMonthlyKpisJob::dispatch(null, null, null)
        ->onQueue('analytics');
})->monthlyOn(1, '08:00') // 1st day of month at 8:00 AM
    ->timezone('Asia/Tashkent')
    ->name('aggregate-monthly-kpis')
    ->onOneServer();

// ==========================================
// MARKETING KPI ATTRIBUTION SCHEDULED JOBS
// ==========================================

// Marketing KPI Daily Snapshot - Har kuni 04:00 da
// Kechagi kun uchun CPL, ROAS, ROI va boshqa metrikalarni hisoblaydi
Schedule::job(new CalculateMarketingKpiSnapshotsJob(null, 'daily'))
    ->dailyAt('04:00')
    ->timezone('Asia/Tashkent')
    ->name('marketing-kpi-daily')
    ->onOneServer();

// Marketing KPI Weekly Snapshot - Har dushanba 05:00 da
// Haftalik marketing KPI aggregatsiyasi
Schedule::job(new CalculateMarketingKpiSnapshotsJob(null, 'weekly'))
    ->weeklyOn(1, '05:00') // Monday at 5:00 AM
    ->timezone('Asia/Tashkent')
    ->name('marketing-kpi-weekly')
    ->onOneServer();

// Marketing KPI Monthly Snapshot - Har oy 2-sanasida 05:00 da
// Oylik marketing KPI aggregatsiyasi (1-kundan keyin hisoblash yaxshiroq)
Schedule::job(new CalculateMarketingKpiSnapshotsJob(null, 'monthly'))
    ->monthlyOn(2, '05:00') // 2nd day of month at 5:00 AM
    ->timezone('Asia/Tashkent')
    ->name('marketing-kpi-monthly')
    ->onOneServer();

// ==========================================
// MARKETING PHASE 2 - KPI DASHBOARD & BONUS SYSTEM JOBS
// ==========================================

// Marketing User KPI yangilash - Har kuni 05:30 da
// Har bir marketing hodimi uchun individual KPIlarni hisoblaydi
Schedule::job(new \App\Jobs\Marketing\UpdateMarketingUserKpisJob(null, 'daily'))
    ->dailyAt('05:30')
    ->timezone('Asia/Tashkent')
    ->name('marketing-user-kpi-daily')
    ->onOneServer();

// Marketing User KPI Weekly - Har dushanba 06:00 da
Schedule::job(new \App\Jobs\Marketing\UpdateMarketingUserKpisJob(null, 'weekly'))
    ->weeklyOn(1, '06:00')
    ->timezone('Asia/Tashkent')
    ->name('marketing-user-kpi-weekly')
    ->onOneServer();

// Marketing User KPI Monthly - Har oy 2-sanasida 06:00 da
Schedule::job(new \App\Jobs\Marketing\UpdateMarketingUserKpisJob(null, 'monthly'))
    ->monthlyOn(2, '06:00')
    ->timezone('Asia/Tashkent')
    ->name('marketing-user-kpi-monthly')
    ->onOneServer();

// [MERGED] Marketing Alerts → UnifiedMarketingAlertsJob ga birlashtirildi
// Schedule::job((new \App\Jobs\Marketing\CheckMarketingAlertsJob)->onQueue('analytics'))
//     ->everyTwoHours()
//     ->timezone('Asia/Tashkent')
//     ->name('marketing-check-alerts')
//     ->onOneServer();

// Unified Marketing Alerts - Har 2 soatda
// 3 ta alert jobni birlashtiradi:
// 1. CheckMarketingAlertsJob (CPL, ROAS, Budget anomaliyalar)
// 2. kpi:check-alerts (KPI rule-based alertlar)
// 3. CheckCampaignPerformanceJob (Meta/Google Ads kampaniya samaradorligi)
Schedule::job((new \App\Jobs\Marketing\UnifiedMarketingAlertsJob)->onQueue('analytics'))
    ->everyTwoHours()
    ->timezone('Asia/Tashkent')
    ->name('unified-marketing-alerts')
    ->onOneServer()
    ->withoutOverlapping();

// Marketing Leaderboard yangilash - Har 2 soatda (optimallashtirish: 48/kun → 12/kun)
// Haftalik reyting taxtasini yangilaydi
Schedule::job((new \App\Jobs\Marketing\UpdateMarketingLeaderboardsJob(null, 'weekly'))->onQueue('analytics'))
    ->everyTwoHours()
    ->timezone('Asia/Tashkent')
    ->name('marketing-leaderboard-weekly')
    ->onOneServer()
    ->withoutOverlapping();

// Marketing Monthly Leaderboard - Har oy 1-sanasida 07:00 da
Schedule::job(new \App\Jobs\Marketing\UpdateMarketingLeaderboardsJob(null, 'monthly'))
    ->monthlyOn(1, '07:00')
    ->timezone('Asia/Tashkent')
    ->name('marketing-leaderboard-monthly')
    ->onOneServer();

// Marketing Bonuslarni hisoblash - Har oyning 1-kuni 08:00 da
// O'tgan oy uchun marketing hodimlarining bonuslarini hisoblaydi
Schedule::job(new \App\Jobs\Marketing\CalculateMarketingBonusesJob)
    ->monthlyOn(1, '08:00')
    ->timezone('Asia/Tashkent')
    ->name('marketing-monthly-bonuses')
    ->onOneServer();

// ==========================================
// COMPETITOR MONITORING SCHEDULED JOBS
// ==========================================

// Competitor Monitoring - Har 6 soatda (optimallashtirish: 24/kun → 4/kun)
// check_frequency_hours sozlamasiga qarab ishga tushadi
Schedule::job((new \App\Jobs\ScrapeCompetitorData)->onQueue('integrations'))
    ->everySixHours()
    ->timezone('Asia/Tashkent')
    ->name('competitor-monitoring')
    ->onOneServer();

// ==========================================
// PBX/VoIP CALL SYNC SCHEDULED JOBS
// ==========================================

// PBX Calls Sync - Har soatda (webhook fallback)
// Webhook asosiy mexanizm — bu faqat o'tkazib yuborilgan qo'ng'iroqlarni ushlash uchun
// OnlinePBX, SipUni va boshqa VoIP xizmatlari uchun ishlaydi
// Avvalgi: everyFifteenMinutes (96/kun) → hourly (24/kun) = 75% tejash
Schedule::command('pbx:sync-calls --days=1 --link-orphans')
    ->hourly()
    ->timezone('Asia/Tashkent')
    ->name('sync-pbx-calls')
    ->onOneServer()
    ->withoutOverlapping();

// Full PBX Sync - Har kuni tunda 3:00 da to'liq sinxronlash (7 kunlik tarix)
Schedule::command('pbx:sync-calls --days=7 --link-orphans')
    ->dailyAt('03:00')
    ->timezone('Asia/Tashkent')
    ->name('sync-pbx-calls-full')
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

// ==========================================
// WEEKLY ANALYTICS (AI-POWERED) SCHEDULED JOBS
// ==========================================

// Haftalik Analitika Pipeline - Har dushanba 07:30 da
// 3 ta alohida command 1 ta pipeline ga birlashtirildi:
// 1) Haftalik hisobotlar yaratish
// 2) AI tahlil (--with-ai)
// 3) Xabarnomalar yuborish (--with-notify)
Schedule::command('analytics:weekly --with-ai --with-notify --notify-channels=mail,telegram')
    ->weeklyOn(1, '07:30') // Monday at 7:30 AM
    ->timezone('Asia/Tashkent')
    ->name('weekly-analytics-pipeline')
    ->onOneServer();

// Haftalik Maqsadlar Yangilash - Har kuni 23:00 da
// Barcha bizneslar uchun haftalik maqsadlarni yangilaydi
Schedule::job(new \App\Jobs\UpdateWeeklyGoalsJob)
    ->dailyAt('23:00')
    ->timezone('Asia/Tashkent')
    ->name('weekly-goals-update')
    ->onOneServer();

// ==========================================
// SALES KPI & GAMIFICATION SCHEDULED JOBS
// ==========================================

// Kunlik KPI Snapshotlarni hisoblash - Har kuni tunda 23:55 da
// Barcha sotuv operatorlari uchun kunlik natijalarni saqlaydi
Schedule::job(new \App\Jobs\Sales\CalculateDailyKpiSnapshotsJob)
    ->dailyAt('23:55')
    ->timezone('Asia/Tashkent')
    ->name('sales-daily-kpi-snapshots')
    ->onOneServer()
    ->withoutOverlapping();

// Haftalik KPI Yig'indisi - Har yakshanba kuni 23:59 da
// Haftalik natijalarni yig'indi qiladi va reyting yangilaydi
Schedule::job(new \App\Jobs\Sales\AggregatePeriodKpisJob('weekly'))
    ->weeklyOn(0, '23:59') // 0 = Sunday
    ->timezone('Asia/Tashkent')
    ->name('sales-weekly-kpi-aggregation')
    ->onOneServer();

// Oylik KPI Yig'indisi - Har oyning oxirgi kuni 23:59 da
// Oylik natijalarni yig'indi qiladi
Schedule::job(new \App\Jobs\Sales\AggregatePeriodKpisJob('monthly'))
    ->monthlyOn(28, '23:59') // Oyning oxirida
    ->timezone('Asia/Tashkent')
    ->name('sales-monthly-kpi-aggregation')
    ->onOneServer();

// Auto-Jarimalarni tekshirish - Har 4 soatda (optimallashtirish: 24/kun → 6/kun)
// Muddati o'tgan warninglarni jarimaga aylantiradi
Schedule::job((new \App\Jobs\Sales\CheckAutoPenaltiesJob)->onQueue('analytics'))
    ->everyFourHours()
    ->timezone('Asia/Tashkent')
    ->name('sales-check-auto-penalties')
    ->onOneServer();

// Oylik Bonuslarni hisoblash - Har oyning 1-kuni soat 06:00 da
// O'tgan oy uchun bonuslarni hisoblaydi
Schedule::job(new \App\Jobs\Sales\CalculateMonthlyBonusesJob)
    ->monthlyOn(1, '06:00')
    ->timezone('Asia/Tashkent')
    ->name('sales-monthly-bonuses')
    ->onOneServer();

// Leaderboardni yangilash - Har 30 daqiqada (optimallashtirish: 96/kun → 48/kun)
// Kunlik reyting taxtasini yangilaydi
Schedule::job((new \App\Jobs\Sales\UpdateLeaderboardsJob(null, 'daily'))->onQueue('analytics'))
    ->everyThirtyMinutes()
    ->timezone('Asia/Tashkent')
    ->name('sales-update-leaderboard')
    ->onOneServer()
    ->withoutOverlapping();

// Yutuqlarni tekshirish - Har kuni 00:05 da
// Foydalanuvchilar yutuqlarini tekshiradi va beradi
Schedule::job(new \App\Jobs\Sales\CheckAchievementsJob)
    ->dailyAt('00:05')
    ->timezone('Asia/Tashkent')
    ->name('sales-check-achievements')
    ->onOneServer();

// ==========================================
// SALES SMART ALERT SCHEDULED JOBS
// ==========================================

// Alert tekshirish - Har soatda
// Lead follow-up, KPI warning, penalty warning kabi alertlarni tekshiradi
Schedule::job(new \App\Jobs\Sales\ProcessSalesAlertsJob)
    ->hourly()
    ->timezone('Asia/Tashkent')
    ->name('sales-process-alerts')
    ->onOneServer();

// Kunlik xulosa yuborish - Ertalab 09:00 da
// Har bir operator uchun kunlik vazifalar va rejalarni yuboradi
Schedule::job(new \App\Jobs\Sales\SendDailySummaryJob)
    ->dailyAt('09:00')
    ->timezone('Asia/Tashkent')
    ->name('sales-daily-summary')
    ->onOneServer();

// Kun o'rtasida progress eslatmasi - Soat 14:00 da
// KPI past bo'lgan operatorlarga eslatma yuboradi
Schedule::job(new \App\Jobs\Sales\SendProgressReminderJob)
    ->dailyAt('14:00')
    ->timezone('Asia/Tashkent')
    ->name('sales-progress-reminder')
    ->onOneServer();

// ==========================================
// PIPELINE AUTOMATION SCHEDULED JOBS
// ==========================================

// Pipeline Bottleneck Detection - Har 6 soatda
// Lead larning qaysi bosqichda uzoq qolganligini aniqlaydi
Schedule::job(new \App\Jobs\Sales\CheckPipelineBottlenecksJob)
    ->everySixHours()
    ->timezone('Asia/Tashkent')
    ->name('pipeline-bottleneck-detection')
    ->onOneServer();

// ==========================================
// CONTENT AI SCHEDULED JOBS
// ==========================================

// Content Templates tahlil qilish - Har kuni 03:00 da
// Tahlil qilinmagan yoki eskirgan shablonlarni AI bilan tahlil qiladi
// va biznes uchun Style Guide yangilaydi
Schedule::job((new \App\Jobs\Marketing\AnalyzeContentTemplatesJob)->onQueue('low'))
    ->dailyAt('03:00')
    ->timezone('Asia/Tashkent')
    ->name('content-ai-analyze-templates')
    ->onOneServer();

// Muvaffaqiyatli postlarni import qilish - Har kuni 04:00 da
// Instagram/Telegram dan eng yaxshi postlarni ContentTemplate ga import qiladi
Schedule::job(new \App\Jobs\Marketing\ImportSuccessfulPostsJob)
    ->dailyAt('04:00')
    ->timezone('Asia/Tashkent')
    ->name('content-ai-import-posts')
    ->onOneServer();

// G'oyalar Quality Score ni qayta hisoblash - Har hafta dushanba 02:00 da
// Barcha g'oyalarning quality score ni yangilaydi va past sifatlilarni arxivlaydi
Schedule::job((new \App\Jobs\Marketing\RecalculateIdeaQualityScoresJob)->onQueue('low'))
    ->weeklyOn(1, '02:00')
    ->timezone('Asia/Tashkent')
    ->name('content-ai-recalculate-scores')
    ->onOneServer();

// [MERGED] Campaign Performance → UnifiedMarketingAlertsJob ga birlashtirildi
// Schedule::job(new \App\Jobs\Marketing\CheckCampaignPerformanceJob)
//     ->everyFourHours()
//     ->timezone('Asia/Tashkent')
//     ->name('marketing-check-campaign-performance')
//     ->onOneServer();

// ==========================================
// CROSS-MODULE ATTRIBUTION SCHEDULED JOBS
// ==========================================

// [MERGED] Churn Risk + Prevention → ChurnAnalysisPipelineJob ga birlashtirildi
// Pipeline: 1) CalculateChurnRiskJob 2) ChurnPreventionJob (ketma-ket)
// Schedule::job(new \App\Jobs\Marketing\CalculateChurnRiskJob)
//     ->dailyAt('07:00')
//     ->onOneServer();

// Conversion Reconciliation - Har kuni 06:00 da
// Meta/Google konversiyalarini haqiqiy sotuvlar bilan solishtiradi
Schedule::job(new \App\Jobs\Marketing\ReconcileConversionsJob(null, \Carbon\Carbon::yesterday()))
    ->dailyAt('06:00')
    ->timezone('Asia/Tashkent')
    ->name('reconcile-conversions')
    ->onOneServer();

// ==========================================
// SYSTEM MAINTENANCE SCHEDULED JOBS
// ==========================================

// Data Cleanup - Har kuni tunda 03:00 da
// Eski notification, log, temp fayllarni tozalaydi
Schedule::job((new \App\Jobs\System\DataCleanupJob)->onQueue('low'))
    ->dailyAt('03:00')
    ->timezone('Asia/Tashkent')
    ->name('system-data-cleanup')
    ->onOneServer();

// [REMOVED] Session Cleanup — Redis da kerak emas (TTL bilan o'zi expire bo'ladi)
// SESSION_DRIVER=redis bo'lganda sessions jadvali ishlatilmaydi
// Schedule::call(function () {
//     \Illuminate\Support\Facades\DB::table('sessions')
//         ->where('last_activity', '<', now()->subHours(24)->timestamp)
//         ->delete();
// })->dailyAt('03:30')
//     ->timezone('Asia/Tashkent')
//     ->name('session-cleanup')
//     ->onOneServer();

// ==========================================
// TOKEN HEALTH MONITOR (Self-Healing System)
// ==========================================

// [FB/IG] DISABLED — Meta review kutilmoqda
// Token Health Check - Har kuni tunda 03:00 da
// Barcha Facebook/Instagram tokenlarni tekshiradi
// Schedule::job((new \App\Jobs\CheckAllTokensJob)->onQueue('integrations'))
//     ->dailyAt('03:00')
//     ->timezone('Asia/Tashkent')
//     ->name('token-health-check')
//     ->onOneServer()
//     ->withoutOverlapping();

// ==========================================
// INSTAGRAM CONTENT PERFORMANCE SYNC
// ==========================================

// [FB/IG] DISABLED — Meta review kutilmoqda
// Instagram Content Performance Sync - Har 6 soatda
// Schedule::job((new \App\Jobs\SyncContentPerformanceJob)->onQueue('integrations'))
//     ->everySixHours()
//     ->timezone('Asia/Tashkent')
//     ->name('sync-content-performance')
//     ->onOneServer()
//     ->withoutOverlapping();

// ==========================================
// CONTENT POST LINKS SYNC (Kontent Reja analitikasi)
// ==========================================

// [FB/IG] DISABLED — Meta review kutilmoqda
// Content Post Links Sync - Har 6 soatda
// Schedule::job((new \App\Jobs\SyncContentPostLinksJob)->onQueue('integrations'))
//     ->everySixHours()
//     ->timezone('Asia/Tashkent')
//     ->name('sync-content-post-links')
//     ->onOneServer()
//     ->withoutOverlapping();

// ==========================================
// HR ENGAGEMENT & RETENTION SCHEDULED JOBS
// ==========================================

// HR Daily Pipeline - Har kuni 05:00 da
// 3 ta kunlik HR vazifani ketma-ket bajaradi:
// 1. CalculateEngagementScoresJob (Gallup Q12 engagement ballari)
// 2. CheckWorkAnniversariesJob (ish yilliklari tekshirish)
// 3. SendOnboardingRemindersJob (onboarding eslatmalari)
Schedule::job(new \App\Jobs\HR\HrDailyPipelineJob)
    ->dailyAt('05:00')
    ->timezone('Asia/Tashkent')
    ->name('hr-daily-pipeline')
    ->onOneServer();

// HR Flight Risk hisoblash - Har hafta dushanba 06:00 da
// Barcha hodimlar uchun ketish xavfini hisoblaydi va alertlar yuboradi
Schedule::job(new \App\Jobs\HR\CalculateFlightRiskJob)
    ->weeklyOn(1, '06:00') // Monday at 6:00 AM
    ->timezone('Asia/Tashkent')
    ->name('hr-calculate-flight-risk')
    ->onOneServer();

// HR Turnover Report - Har oyning 3-kuni 08:00 da
// O'tgan oy uchun turnover hisobotini yaratadi
Schedule::job(new \App\Jobs\HR\GenerateTurnoverReportJob)
    ->monthlyOn(3, '08:00') // 3rd day of month at 8:00 AM
    ->timezone('Asia/Tashkent')
    ->name('hr-turnover-report')
    ->onOneServer();

// ==========================================
// TELEPHONY - UTEL/OnlinePBX SYNC JOBS
// ==========================================

// UTEL Call History Sync - Har soatda (webhook fallback)
// Webhook asosiy mexanizm — bu faqat o'tkazib yuborilgan qo'ng'iroqlarni ushlash uchun
// Avvalgi: everyThreeMinutes (480/kun) → hourly (24/kun) = 95% tejash
Schedule::call(function () {
    $accounts = \App\Models\UtelAccount::where('is_active', true)->get();

    foreach ($accounts as $account) {
        try {
            $service = app(\App\Services\UtelService::class)->setAccount($account);

            // Sync call history - this also links outbound calls and clears missed calls
            $service->syncCallHistory(now()->subHours(2), now());

            // Reconcile any remaining missed calls that weren't caught by normal sync
            $service->reconcileMissedCalls();
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('UTEL sync failed', [
                'account_id' => $account->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
})->hourly()
    ->timezone('Asia/Tashkent')
    ->name('utel-call-sync')
    ->onOneServer()
    ->withoutOverlapping();

// [REMOVED] OnlinePBX inline polling — pbx:sync-calls command orqali qoplanadi
// Webhook asosiy mexanizm, pbx:sync-calls soatlik fallback sifatida ishlaydi
// Schedule::call(function () {
//     $accounts = \App\Models\PbxAccount::where('is_active', true)
//         ->where('provider', 'onlinepbx')
//         ->get();
//     foreach ($accounts as $account) {
//         try {
//             $service = app(\App\Services\OnlinePbxService::class)->setAccount($account);
//             $service->syncCallHistory(now()->subHours(1), now());
//         } catch (\Exception $e) {
//             \Illuminate\Support\Facades\Log::error('OnlinePBX sync failed', [
//                 'account_id' => $account->id,
//                 'error' => $e->getMessage(),
//             ]);
//         }
//     }
// })->everyFiveMinutes()
//     ->timezone('Asia/Tashkent')
//     ->name('onlinepbx-call-sync')
//     ->onOneServer();

// ==========================================
// KPI ALERT SYSTEM SCHEDULED JOBS
// ==========================================

// [MERGED] KPI Alerts → UnifiedMarketingAlertsJob ga birlashtirildi
// Schedule::command('kpi:check-alerts')
//     ->hourly()
//     ->timezone('Asia/Tashkent')
//     ->name('kpi-check-alerts')
//     ->onOneServer();

// ==========================================
// DAILY BRIEF (TELEGRAM) SCHEDULED JOBS
// ==========================================

// Daily Brief - Har kuni ertalab 07:00 da
// Barcha bizneslar uchun Telegram orqali kunlik brief yuboradi
// Brief tarkibi: Yo'qotilgan imkoniyatlar, Marketing ROI, Shoshilinch vazifalar
Schedule::job(new \App\Jobs\GenerateDailyBriefJob)
    ->dailyAt('07:00')
    ->timezone('Asia/Tashkent')
    ->name('daily-brief-telegram')
    ->onOneServer();

// ==========================================
// WEEKLY ANALYTICS REPORT SCHEDULED JOB
// ==========================================

// Weekly Analytics - Har dushanba ertalab 08:00 da
// Barcha bizneslar uchun haftalik analitika hisobotini yaratadi
// AI tahlil va Telegram bildirishnoma bilan
Schedule::command('analytics:weekly --with-ai --with-notify --notify-channels=telegram')
    ->weeklyOn(1, '08:00')
    ->timezone('Asia/Tashkent')
    ->name('weekly-analytics-report')
    ->onOneServer();

// ==========================================
// STAGNANT TASKS ALERT (TELEGRAM) SCHEDULED JOBS
// ==========================================

// Check Stagnant Tasks - Har 3 soatda (optimallashtirish: 24/kun → 8/kun)
// Muddati o'tgan vazifalarni tekshiradi va Telegram orqali eslatma yuboradi
Schedule::job(new \App\Jobs\CheckStagnantTasksJob)
    ->cron('0 */3 * * *')
    ->timezone('Asia/Tashkent')
    ->name('check-stagnant-tasks')
    ->onOneServer();

// ==========================================
// TRENDSEE - VIRAL CONTENT HUNTER JOBS
// ==========================================
// [MVP] DISABLED - Viral module removed from MVP scope
// API instability and complexity issues. Focus on CRM & Finance.

// // Viral Content Hunt - Har hafta dushanba 09:00 da
// // Instagram dan viral reelslarni qidiradi va AI bilan tahlil qiladi
// Schedule::job(new \App\Jobs\ViralHunterJob)
//     ->weeklyOn(1, '09:00')
//     ->timezone('Asia/Tashkent')
//     ->name('trendsee-viral-hunt')
//     ->onOneServer();

// // Analyze Pending Viral Content - Har kuni 10:00 da
// Schedule::call(function () {
//     $pendingContents = \App\Models\ViralContent::unprocessed()
//         ->orderBy('play_count', 'desc')
//         ->limit(20)
//         ->get();
//     foreach ($pendingContents as $content) {
//         \App\Jobs\AnalyzeViralContentJob::dispatch($content->id)
//             ->onQueue('low');
//     }
// })->dailyAt('10:00')
//     ->timezone('Asia/Tashkent')
//     ->name('trendsee-analyze-pending')
//     ->onOneServer();

// ==========================================
// SMART CONTENT LOOP - JAMOAVIY AQL & FEEDBACK
// ==========================================

// Niche Topic Scores qayta hisoblash - Har kuni 03:30 da
// Barcha sohalardagi mavzular score larini yangilaydi (Jamoaviy Aql)
Schedule::command('content-ai:recalculate-niche-scores')
    ->dailyAt('03:30')
    ->timezone('Asia/Tashkent')
    ->name('content-ai-niche-scores')
    ->onOneServer();

// Content Performance Feedback Loop - Har 6 soatda
// Published kontent natijalarini yig'adi va niche_topic_scores ni yangilaydi
Schedule::job(new \App\Jobs\Marketing\ProcessContentFeedbackJob)
    ->everySixHours()
    ->timezone('Asia/Tashkent')
    ->name('content-ai-feedback-loop')
    ->onOneServer()
    ->withoutOverlapping();

// ==========================================
// TELEGRAM STORE JOBS
// ==========================================

// Muddati o'tgan buyurtmalarni avtomatik bekor qilish - Har soatda
Schedule::job(new \App\Jobs\Store\AutoCancelExpiredOrdersJob)
    ->hourly()
    ->timezone('Asia/Tashkent')
    ->name('store-auto-cancel-orders')
    ->onOneServer()
    ->withoutOverlapping();

// Do'kon kunlik analitika - Har kuni 01:00 da
Schedule::job(new \App\Jobs\Store\UpdateStoreAnalyticsJob)
    ->dailyAt('01:00')
    ->timezone('Asia/Tashkent')
    ->name('store-daily-analytics')
    ->onOneServer()
    ->withoutOverlapping();

// ==========================================
// TRENDSEE - HYBRID INTELLIGENCE ENGINE JOBS
// ==========================================
// [MVP] DISABLED - Focus on core CRM features

// // Fetch Global Trends - Har hafta yakshanba 06:00 da
// Schedule::job(new \App\Jobs\FetchTrendsJob('UZ', 'google'))
//     ->weeklyOn(0, '06:00')
//     ->timezone('Asia/Tashkent')
//     ->name('trendsee-fetch-trends')
//     ->onOneServer();

// // Refresh Competitor Data - Har hafta seshanba 03:00 da
// Schedule::job(new \App\Jobs\RefreshCompetitorsJob(20))
//     ->weeklyOn(2, '03:00')
//     ->timezone('Asia/Tashkent')
//     ->name('trendsee-refresh-competitors')
//     ->onOneServer();

// ==========================================
// SUBSCRIPTION MANAGEMENT
// ==========================================

// Muddati o'tgan subscriptionlarni expired qilish - Har kuni 00:30
Schedule::command('subscriptions:check-expired')
    ->dailyAt('00:30')
    ->timezone('Asia/Tashkent')
    ->name('subscriptions-check-expired')
    ->onOneServer();

// Rejalashtirilgan tarif o'zgarishlarni bajarish - Har kuni 01:00
Schedule::command('subscriptions:apply-scheduled')
    ->dailyAt('01:00')
    ->timezone('Asia/Tashkent')
    ->name('subscriptions-apply-scheduled')
    ->onOneServer();

// Trial tugashi haqida ogohlantirish (3 kun va 1 kun oldin) - Har kuni 10:00
Schedule::command('subscriptions:trial-expiry-notify')
    ->dailyAt('10:00')
    ->timezone('Asia/Tashkent')
    ->name('subscriptions-trial-expiry-notify')
    ->onOneServer();

// Muddati o'tgan to'lov tranzaksiyalarni tozalash - Har kuni 02:00
Schedule::command('billing:clean-expired')
    ->dailyAt('02:00')
    ->timezone('Asia/Tashkent')
    ->name('billing-clean-expired')
    ->onOneServer();

// ==========================================
// AI AGENT TIZIMI CRON VAZIFALARI
// ==========================================

// Kundalik qisqa hisobot - Har kuni 08:00
Schedule::command('agent:daily-brief')
    ->dailyAt('08:00')
    ->timezone('Asia/Tashkent')
    ->name('agent-daily-brief')
    ->onOneServer();

// Pul oqimi bashorati - Har kuni 07:00
Schedule::command('agent:forecast-cash')
    ->dailyAt('07:00')
    ->timezone('Asia/Tashkent')
    ->name('agent-forecast-cash')
    ->onOneServer();

// Muddati o'tgan kontekstlarni tozalash - Har kuni 03:00
Schedule::command('agent:cleanup-expired-context')
    ->dailyAt('03:00')
    ->timezone('Asia/Tashkent')
    ->name('agent-cleanup-context')
    ->onOneServer();

// Haftalik samaradorlik hisoboti - Har dushanba 09:00
Schedule::command('agent:weekly-reports')
    ->weeklyOn(1, '09:00')
    ->timezone('Asia/Tashkent')
    ->name('agent-weekly-reports')
    ->onOneServer();

// Biznes sog'ligi tekshiruvi - Har dushanba 08:00
Schedule::command('agent:health-check')
    ->weeklyOn(1, '08:00')
    ->timezone('Asia/Tashkent')
    ->name('agent-health-check')
    ->onOneServer();

// Obro' bali hisoblash - Har dushanba 07:00
Schedule::command('agent:reputation-score')
    ->weeklyOn(1, '07:00')
    ->timezone('Asia/Tashkent')
    ->name('agent-reputation-score')
    ->onOneServer();

// Mavsumiy voqealar tekshiruvi - Har yakshanba 10:00
Schedule::command('agent:check-seasons')
    ->weeklyOn(0, '10:00')
    ->timezone('Asia/Tashkent')
    ->name('agent-check-seasons')
    ->onOneServer();

// Mijoz umr yo'li harakatlari - Har 30 daqiqada
Schedule::command('agent:lifecycle')
    ->everyThirtyMinutes()
    ->timezone('Asia/Tashkent')
    ->name('agent-lifecycle')
    ->onOneServer();

// ==========================================
// AI JAMOA OPERATSION TIZIMI
// ==========================================

// Ertalabki jamoa majlisi - Har kuni 08:00
Schedule::command('team:morning-standup')
    ->dailyAt('08:00')
    ->timezone('Asia/Tashkent')
    ->name('team-morning-standup')
    ->onOneServer();

// Soatlik monitoring - Har 2 soatda (08:00-22:00)
Schedule::command('team:hourly-check')
    ->everyTwoHours()
    ->between('8:00', '22:00')
    ->timezone('Asia/Tashkent')
    ->name('team-hourly-check')
    ->onOneServer();

// Kunlik xulosa - Har kuni 18:00
Schedule::command('team:daily-summary')
    ->dailyAt('18:00')
    ->timezone('Asia/Tashkent')
    ->name('team-daily-summary')
    ->onOneServer();

// Haftalik reja - Har dushanba 09:30
Schedule::command('team:weekly-planning')
    ->weeklyOn(1, '09:30')
    ->timezone('Asia/Tashkent')
    ->name('team-weekly-planning')
    ->onOneServer();
