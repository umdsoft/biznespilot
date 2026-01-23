<?php

use App\Jobs\AggregateMonthlyKpisJob;
use App\Jobs\AggregateWeeklyKpisJob;
use App\Jobs\AnomalyDetectionJob;
use App\Jobs\ChurnPreventionJob;
use App\Jobs\CustomerSegmentationJob;
use App\Jobs\DailyBusinessDiagnosticJob;
use App\Jobs\Marketing\CalculateMarketingKpiSnapshotsJob;
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
Schedule::job(new SyncAllChannelsMetrics)
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

// Marketing Alertlarni tekshirish - Har soatda
// CPL, ROAS, Budget va boshqa anomaliyalarni aniqlaydi
Schedule::job(new \App\Jobs\Marketing\CheckMarketingAlertsJob)
    ->hourly()
    ->timezone('Asia/Tashkent')
    ->name('marketing-check-alerts')
    ->onOneServer();

// Marketing Leaderboard yangilash - Har 30 daqiqada
// Haftalik reyting taxtasini real-time yangilaydi
Schedule::job(new \App\Jobs\Marketing\UpdateMarketingLeaderboardsJob(null, 'weekly'))
    ->everyThirtyMinutes()
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

// Competitor Monitoring - Har soatda auto_monitor yoqilgan raqobatchilarni kuzatish
// check_frequency_hours sozlamasiga qarab ishga tushadi
Schedule::job(new \App\Jobs\ScrapeCompetitorData)
    ->hourly()
    ->timezone('Asia/Tashkent')
    ->name('competitor-monitoring')
    ->onOneServer();

// ==========================================
// PBX/VoIP CALL SYNC SCHEDULED JOBS
// ==========================================

// PBX Calls Sync - Har 15 daqiqada barcha ulangan PBX/VoIP xizmatlaridan qo'ng'iroqlarni sinxronlaydi
// OnlinePBX, SipUni va boshqa VoIP xizmatlari uchun ishlaydi
Schedule::command('pbx:sync-calls --days=1 --link-orphans')
    ->everyFifteenMinutes()
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

// Haftalik Analitika - Har dushanba 07:30 da
// Barcha bizneslar uchun haftalik hisobotlarni yaratadi
Schedule::command('analytics:weekly')
    ->weeklyOn(1, '07:30') // Monday at 7:30 AM
    ->timezone('Asia/Tashkent')
    ->name('weekly-analytics-report')
    ->onOneServer();

// Haftalik AI Tahlil - Har dushanba 08:00 da
// Yaratilgan hisobotlar uchun AI tahlil qiladi
Schedule::command('analytics:weekly --with-ai')
    ->weeklyOn(1, '08:00') // Monday at 8:00 AM
    ->timezone('Asia/Tashkent')
    ->name('weekly-analytics-ai')
    ->onOneServer();

// Haftalik Analitika Xabarnomalar - Har dushanba 09:00 da
// Biznes egalariga haftalik hisobot xabarnomalarini yuboradi (Email va Telegram)
Schedule::command('analytics:weekly --with-notify --notify-channels=mail,telegram')
    ->weeklyOn(1, '09:00') // Monday at 9:00 AM
    ->timezone('Asia/Tashkent')
    ->name('weekly-analytics-notify')
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

// Auto-Jarimalarni tekshirish - Har soatda
// Muddati o'tgan warninglarni jarimaga aylantiradi
Schedule::job(new \App\Jobs\Sales\CheckAutoPenaltiesJob)
    ->hourly()
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

// Leaderboardni yangilash - Har 15 daqiqada
// Kunlik reyting taxtasini real-time yangilaydi
Schedule::job(new \App\Jobs\Sales\UpdateLeaderboardsJob(null, 'daily'))
    ->everyFifteenMinutes()
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
Schedule::job(new \App\Jobs\Marketing\AnalyzeContentTemplatesJob)
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
Schedule::job(new \App\Jobs\Marketing\RecalculateIdeaQualityScoresJob)
    ->weeklyOn(1, '02:00')
    ->timezone('Asia/Tashkent')
    ->name('content-ai-recalculate-scores')
    ->onOneServer();

// Kampaniya samaradorligini tekshirish - Har 4 soatda
// Budget, CPR, CTR va boshqa metrikalarni tekshirib, alertlar yuboradi
Schedule::job(new \App\Jobs\Marketing\CheckCampaignPerformanceJob)
    ->everyFourHours()
    ->timezone('Asia/Tashkent')
    ->name('marketing-check-campaign-performance')
    ->onOneServer();

// ==========================================
// CROSS-MODULE ATTRIBUTION SCHEDULED JOBS
// ==========================================

// Churn Risk hisoblash - Har kuni 07:00 da
// Barcha customerlar uchun churn xavfini hisoblaydi va alertlar yuboradi
Schedule::job(new \App\Jobs\Marketing\CalculateChurnRiskJob)
    ->dailyAt('07:00')
    ->timezone('Asia/Tashkent')
    ->name('calculate-churn-risk')
    ->onOneServer();

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
Schedule::job(new \App\Jobs\System\DataCleanupJob)
    ->dailyAt('03:00')
    ->timezone('Asia/Tashkent')
    ->name('system-data-cleanup')
    ->onOneServer();

// ==========================================
// HR ENGAGEMENT & RETENTION SCHEDULED JOBS
// ==========================================

// HR Engagement Scores - Har kuni 05:00 da
// Barcha hodimlar uchun engagement ballini hisoblaydi (Gallup Q12 asosida)
Schedule::job(new \App\Jobs\HR\CalculateEngagementScoresJob)
    ->dailyAt('05:00')
    ->timezone('Asia/Tashkent')
    ->name('hr-calculate-engagement')
    ->onOneServer();

// HR Flight Risk hisoblash - Har hafta dushanba 06:00 da
// Barcha hodimlar uchun ketish xavfini hisoblaydi va alertlar yuboradi
Schedule::job(new \App\Jobs\HR\CalculateFlightRiskJob)
    ->weeklyOn(1, '06:00') // Monday at 6:00 AM
    ->timezone('Asia/Tashkent')
    ->name('hr-calculate-flight-risk')
    ->onOneServer();

// HR Onboarding Reminders - Har kuni 08:00 da
// Onboarding vazifalari eslatmalarini yuboradi va milestonelarni tekshiradi
Schedule::job(new \App\Jobs\HR\SendOnboardingRemindersJob)
    ->dailyAt('08:00')
    ->timezone('Asia/Tashkent')
    ->name('hr-onboarding-reminders')
    ->onOneServer();

// HR Work Anniversaries - Har kuni 07:00 da
// Ish yilliklarini tekshiradi va tabriklar yuboradi
Schedule::job(new \App\Jobs\HR\CheckWorkAnniversariesJob)
    ->dailyAt('07:00')
    ->timezone('Asia/Tashkent')
    ->name('hr-work-anniversaries')
    ->onOneServer();

// HR Turnover Report - Har oyning 3-kuni 08:00 da
// O'tgan oy uchun turnover hisobotini yaratadi
Schedule::job(new \App\Jobs\HR\GenerateTurnoverReportJob)
    ->monthlyOn(3, '08:00') // 3rd day of month at 8:00 AM
    ->timezone('Asia/Tashkent')
    ->name('hr-turnover-report')
    ->onOneServer();
