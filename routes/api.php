<?php

use App\Http\Controllers\Api\AgentController;
use App\Http\Controllers\Api\AIUsageController;
use App\Http\Controllers\Api\Auth\AuthController;
use App\Http\Controllers\Api\CallCenter\CallAnalysisController;
use App\Http\Controllers\Api\IntegrationsController;
use App\Http\Controllers\Api\KpiConfigurationController;
use App\Http\Controllers\Api\KpiDailyDataController;
use App\Http\Controllers\Api\KpiDashboardController;
use App\Http\Controllers\Api\KpiEntryController;
use App\Http\Controllers\Api\OnboardingController;
use App\Http\Controllers\Api\TranslationController;
use App\Http\Controllers\MoiZvonkiWebhookController;
use App\Http\Controllers\PbxWebhookController;
use App\Http\Controllers\UtelWebhookController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

// Public routes - API v1 (Rate limited to prevent brute force attacks)
Route::prefix('v1')->group(function () {
    // Authentication routes - SECURITY: Rate limited (5 attempts per minute for login/register)
    Route::prefix('auth')->middleware('throttle:5,1')->group(function () {
        Route::post('register', [AuthController::class, 'register']);
        Route::post('login', [AuthController::class, 'login']);
    });

    // Public onboarding data - Rate limited (60 requests per minute)
    Route::middleware('throttle:60,1')->group(function () {
        Route::get('industries', [OnboardingController::class, 'industries']);
        Route::get('onboarding/steps', [OnboardingController::class, 'steps']);
    });
});

// Public translations endpoint (no auth required)
Route::get('translations/{locale}', [TranslationController::class, 'index'])
    ->middleware('throttle:60,1')
    ->name('api.translations');

// ========== WEBHOOK ROUTES (No auth required) ==========
// PBX Webhooks - OnlinePBX
Route::prefix('webhooks/pbx')->group(function () {
    // Generic OnlinePBX webhook
    Route::post('onlinepbx', [PbxWebhookController::class, 'handleOnlinePbx'])
        ->name('webhooks.pbx.onlinepbx');

    // Business-specific OnlinePBX webhook
    Route::post('onlinepbx/{businessId}', [PbxWebhookController::class, 'handleOnlinePbxWithBusiness'])
        ->name('webhooks.pbx.onlinepbx.business');

    // Test endpoint
    Route::get('test', [PbxWebhookController::class, 'test'])
        ->name('webhooks.pbx.test');
});

// MoiZvonki Webhooks
Route::prefix('webhooks/moizvonki')->group(function () {
    // Generic MoiZvonki webhook
    Route::post('/', [MoiZvonkiWebhookController::class, 'handle'])
        ->name('webhooks.moizvonki');

    // Business-specific MoiZvonki webhook
    Route::post('{businessId}', [MoiZvonkiWebhookController::class, 'handleWithBusiness'])
        ->name('webhooks.moizvonki.business');

    // Test endpoint
    Route::get('test', [MoiZvonkiWebhookController::class, 'test'])
        ->name('webhooks.moizvonki.test');
});

// UTEL Webhooks (O'zbekiston)
Route::prefix('webhooks/utel')->group(function () {
    // Generic UTEL webhook
    Route::post('/', [UtelWebhookController::class, 'handle'])
        ->name('webhooks.utel');

    // Business-specific UTEL webhook
    Route::post('{businessId}', [UtelWebhookController::class, 'handleWithBusiness'])
        ->name('webhooks.utel.business');

    // Test endpoint
    Route::get('test', [UtelWebhookController::class, 'test'])
        ->name('webhooks.utel.test');
});

// ========== SYSTEM BOT WEBHOOK (Dual Bot Strategy) ==========
// BiznesPilot System Bot - Business Owner larga notification yuborish
// Bu Tenant Bot lardan alohida va faqat system-to-admin xabarlar uchun
Route::post('webhooks/system-bot', [\App\Http\Controllers\Telegram\SystemBotController::class, 'webhook'])
    ->name('webhooks.system-bot');

// ========== BILLING WEBHOOKS (Payme & Click) ==========
// SaaS to'lov tizimlari uchun merchant API'lar
Route::prefix('billing')->middleware('throttle:billing-webhooks')->group(function () {
    // Payme Merchant API (JSON-RPC)
    // https://developer.help.paycom.uz/
    Route::post('payme', [\App\Http\Controllers\Billing\PaymeMerchantController::class, 'handle'])
        ->middleware('payme.auth')
        ->name('billing.payme.webhook');

    // Click Merchant API (REST)
    // https://docs.click.uz/
    Route::post('click/prepare', [\App\Http\Controllers\Billing\ClickMerchantController::class, 'prepare'])
        ->name('billing.click.prepare');
    Route::post('click/complete', [\App\Http\Controllers\Billing\ClickMerchantController::class, 'complete'])
        ->name('billing.click.complete');
});

// ========== CALL RECORDING ROUTES (Auth required) ==========
// UTEL va OnlinePBX to'g'ridan-to'g'ri URL qaytaradi - streaming kerak emas
Route::prefix('v1/calls')->middleware(['web', 'auth'])->group(function () {
    // Get recording URL (provider dan to'g'ridan-to'g'ri URL)
    Route::get('{callId}/recording', [\App\Http\Controllers\Api\CallRecordingController::class, 'getUrl'])
        ->name('api.calls.recording');

    // Get recording info (metadata)
    Route::get('{callId}/recording/info', [\App\Http\Controllers\Api\CallRecordingController::class, 'info'])
        ->name('api.calls.recording.info');

    // Check if recording is available
    Route::get('{callId}/recording/check', [\App\Http\Controllers\Api\CallRecordingController::class, 'check'])
        ->name('api.calls.recording.check');
});

// ========== CALL CENTER / AI ANALYSIS ROUTES (Auth required) ==========
// Qo'ng'iroqlarni AI tahlil qilish - Groq Whisper + Claude Haiku
Route::prefix('v1/call-center')->middleware(['web', 'auth'])->group(function () {
    // Qo'ng'iroqlar ro'yxati
    Route::get('calls', [CallAnalysisController::class, 'index'])
        ->name('api.call-center.calls.index');

    // Bitta qo'ng'iroq detail
    Route::get('calls/{id}', [CallAnalysisController::class, 'show'])
        ->name('api.call-center.calls.show');

    // Bitta qo'ng'iroqni tahlilga yuborish
    Route::post('calls/{id}/analyze', [CallAnalysisController::class, 'analyze'])
        ->name('api.call-center.calls.analyze');

    // Bir nechta qo'ng'iroqni tahlilga yuborish (bulk)
    Route::post('calls/analyze-bulk', [CallAnalysisController::class, 'analyzeBulk'])
        ->name('api.call-center.calls.analyze-bulk');

    // Qo'ng'iroq tahlil natijalarini olish
    Route::get('calls/{id}/analysis', [CallAnalysisController::class, 'getAnalysis'])
        ->name('api.call-center.calls.analysis');

    // Tahlil xarajatini hisoblash (oldindan)
    Route::post('calls/estimate-cost', [CallAnalysisController::class, 'estimateCost'])
        ->name('api.call-center.calls.estimate-cost');

    // Tahlil statistikasi
    Route::get('stats', [CallAnalysisController::class, 'stats'])
        ->name('api.call-center.stats');

    // ========== OPERATOR STATISTIKASI ==========
    // Umumiy ko'rinish
    Route::get('overview', [\App\Http\Controllers\Api\CallCenter\OperatorStatsController::class, 'overview'])
        ->name('api.call-center.overview');

    // Liderlar ro'yxati
    Route::get('leaderboard', [\App\Http\Controllers\Api\CallCenter\OperatorStatsController::class, 'leaderboard'])
        ->name('api.call-center.leaderboard');

    // Operatorlar ro'yxati
    Route::get('operators', [\App\Http\Controllers\Api\CallCenter\OperatorStatsController::class, 'index'])
        ->name('api.call-center.operators.index');

    // Bitta operator
    Route::get('operators/{userId}', [\App\Http\Controllers\Api\CallCenter\OperatorStatsController::class, 'show'])
        ->name('api.call-center.operators.show');

    // Operator tarix (barcha tahlillar)
    Route::get('operators/{userId}/history', [\App\Http\Controllers\Api\CallCenter\OperatorStatsController::class, 'history'])
        ->name('api.call-center.operators.history');

    // Operator statistikasi (davrlar bo'yicha)
    Route::get('operators/{userId}/stats', [\App\Http\Controllers\Api\CallCenter\OperatorStatsController::class, 'stats'])
        ->name('api.call-center.operators.stats');

    // Statistikani qayta hisoblash
    Route::post('operators/{userId}/recalculate', [\App\Http\Controllers\Api\CallCenter\OperatorStatsController::class, 'recalculate'])
        ->name('api.call-center.operators.recalculate');
});

// Camera Attendance Webhooks (Kamera orqali davomat)
Route::prefix('webhooks/attendance')->group(function () {
    // Kameradan check-in/check-out qabul qilish
    Route::post('camera', [\App\Http\Controllers\Api\HR\CameraAttendanceController::class, 'webhook'])
        ->name('webhooks.attendance.camera');

    // Test endpoint
    Route::get('test', function () {
        return response()->json([
            'success' => true,
            'message' => 'Camera attendance webhook is working',
            'timestamp' => now()->toISOString(),
        ]);
    })->name('webhooks.attendance.test');
});

// Protected routes - API v1 (Rate limited - 120 requests per minute for authenticated users)
Route::prefix('v1')->middleware(['web', 'auth', 'throttle:120,1'])->group(function () {
    // Authentication routes
    Route::prefix('auth')->group(function () {
        Route::post('logout', [AuthController::class, 'logout']);
        Route::get('me', [AuthController::class, 'me']);
    });

    // Onboarding routes
    Route::prefix('onboarding')->group(function () {
        // Progress
        Route::get('progress', [OnboardingController::class, 'progress']);
        Route::post('initialize', [OnboardingController::class, 'initialize']);
        Route::get('steps/{stepCode}', [OnboardingController::class, 'stepDetail']);

        // Business profile (Steps 1-3)
        Route::put('business/basic', [OnboardingController::class, 'updateBusinessBasic']);
        Route::put('business/details', [OnboardingController::class, 'updateBusinessDetails']);
        Route::put('business/maturity', [OnboardingController::class, 'updateMaturityAssessment']);
        Route::get('maturity-score', [OnboardingController::class, 'maturityScore']);

        // Problems (Step 6)
        Route::get('problems', [OnboardingController::class, 'problems']);
        Route::post('problems', [OnboardingController::class, 'storeProblem']);
        Route::put('problems/{problem}', [OnboardingController::class, 'updateProblem']);
        Route::delete('problems/{problem}', [OnboardingController::class, 'deleteProblem']);

        // Dream Buyer (Step 7)
        Route::get('dream-buyer', [OnboardingController::class, 'dreamBuyer']);
        Route::put('dream-buyer', [OnboardingController::class, 'updateDreamBuyer']);

        // Competitors (Step 8)
        Route::get('competitors', [OnboardingController::class, 'competitors']);
        Route::post('competitors', [OnboardingController::class, 'storeCompetitor']);
        Route::put('competitors/{competitor}', [OnboardingController::class, 'updateCompetitor']);
        Route::delete('competitors/{competitor}', [OnboardingController::class, 'deleteCompetitor']);

        // Hypotheses (Step 9)
        Route::get('hypotheses', [OnboardingController::class, 'hypotheses']);
        Route::post('hypotheses', [OnboardingController::class, 'storeHypothesis']);
        Route::put('hypotheses/{hypothesis}', [OnboardingController::class, 'updateHypothesis']);
        Route::delete('hypotheses/{hypothesis}', [OnboardingController::class, 'deleteHypothesis']);

        // Sales Metrics
        Route::get('sales-metrics', [OnboardingController::class, 'salesMetrics']);
        Route::put('sales-metrics', [OnboardingController::class, 'updateSalesMetrics']);
        Route::get('sales-metrics/history', [OnboardingController::class, 'salesMetricsHistory']);

        // Marketing Metrics
        Route::get('marketing-metrics', [OnboardingController::class, 'marketingMetrics']);
        Route::put('marketing-metrics', [OnboardingController::class, 'updateMarketingMetrics']);
        Route::get('marketing-metrics/history', [OnboardingController::class, 'marketingMetricsHistory']);

        // Phase 2
        Route::post('start-phase-2', [OnboardingController::class, 'startPhase2']);
    });

    // Business routes with middleware
    Route::middleware(['business.access', 'subscription'])->group(function () {
        // Add your protected business routes here
        // Example:
        // Route::apiResource('businesses', BusinessController::class);
        // Route::apiResource('leads', LeadController::class)->middleware('feature.limit:leads');
        // Route::apiResource('team-members', TeamMemberController::class)->middleware('feature.limit:team_members');
        // Route::apiResource('chatbot-configs', ChatbotConfigController::class)->middleware('feature.limit:chatbot_channels');
    });

    // ========== AI AGENT ROUTES ==========
    // AI Agent suhbat tizimi — rol va tarif bo'yicha cheklov
    Route::prefix('agent')->group(function () {
        Route::post('/ask', [AgentController::class, 'ask'])
            ->middleware('agent.access:ask')
            ->name('api.agent.ask');
        Route::get('/job/{jobId}', [AgentController::class, 'jobStatus'])
            ->middleware('agent.access:ask')
            ->name('api.agent.job');
        Route::get('/conversations', [AgentController::class, 'conversations'])
            ->middleware('agent.access:view_conversations')
            ->name('api.agent.conversations');
        Route::get('/conversations/{id}', [AgentController::class, 'conversation'])
            ->middleware('agent.access:view_conversations')
            ->name('api.agent.conversation');
        Route::get('/conversations/{id}/messages', [AgentController::class, 'messages'])
            ->middleware('agent.access:view_conversations')
            ->name('api.agent.messages');
    });

    // ========== DELIVERABLES ROUTES ==========
    Route::prefix('deliverables')->group(function () {
        Route::get('/', [\App\Http\Controllers\Api\DeliverableController::class, 'index'])->name('api.deliverables.index');
        Route::get('/{id}', [\App\Http\Controllers\Api\DeliverableController::class, 'show'])->name('api.deliverables.show');
        Route::post('/{id}/approve', [\App\Http\Controllers\Api\DeliverableController::class, 'approve'])->name('api.deliverables.approve');
        Route::post('/{id}/reject', [\App\Http\Controllers\Api\DeliverableController::class, 'reject'])->name('api.deliverables.reject');
    });

    // ========== AI USAGE TRACKING ROUTES ==========
    // AI xarajat kuzatuvi — faqat owner/admin
    Route::prefix('ai-usage')->middleware('agent.access:view_usage')->group(function () {
        Route::get('/summary', [AIUsageController::class, 'summary'])
            ->name('api.ai-usage.summary');
        Route::get('/daily', [AIUsageController::class, 'daily'])
            ->name('api.ai-usage.daily');
    });

    // Integrations Management Routes
    Route::prefix('integrations')->group(function () {
        Route::get('/status', [IntegrationsController::class, 'getStatus']);
        Route::post('/{integrationId}/disconnect', [IntegrationsController::class, 'disconnect']);
        Route::post('/{integrationId}/sync', [IntegrationsController::class, 'sync']);
    });

    // PBX Sync Routes (Authenticated)
    Route::prefix('pbx')->group(function () {
        Route::post('/sync-calls', [PbxWebhookController::class, 'syncCallHistory'])
            ->name('api.pbx.sync-calls');
        Route::post('/link-orphan-calls', [PbxWebhookController::class, 'linkOrphanCalls'])
            ->name('api.pbx.link-orphan-calls');
    });

    // KPI System Routes - Protected with business access validation
    Route::prefix('businesses/{businessId}')->middleware(['business.access'])->group(function () {

        // KPI Configuration Routes
        Route::prefix('kpi-configuration')->group(function () {
            Route::get('/', [KpiConfigurationController::class, 'show']);
            Route::post('/', [KpiConfigurationController::class, 'store']);
            Route::put('/', [KpiConfigurationController::class, 'update']);
            Route::post('/generate-recommendations', [KpiConfigurationController::class, 'generateRecommendations']);
            Route::post('/activate', [KpiConfigurationController::class, 'activate']);
            Route::post('/pause', [KpiConfigurationController::class, 'pause']);

            // KPI Management
            Route::post('/kpis', [KpiConfigurationController::class, 'addKpi']);
            Route::delete('/kpis/{kpiCode}', [KpiConfigurationController::class, 'removeKpi']);
            Route::put('/kpis/{kpiCode}/priority', [KpiConfigurationController::class, 'updateKpiPriority']);
            Route::put('/kpis/{kpiCode}/weight', [KpiConfigurationController::class, 'updateKpiWeight']);

            // Available KPIs & Suggestions
            Route::get('/available-kpis', [KpiConfigurationController::class, 'getAvailableKpis']);
            Route::get('/suggest-additional', [KpiConfigurationController::class, 'suggestAdditionalKpis']);
            Route::post('/benchmark-targets', [KpiConfigurationController::class, 'getBenchmarkTargets']);
        });

        // KPI Daily Data Routes
        Route::prefix('kpi-daily')->group(function () {
            Route::get('/', [KpiDailyDataController::class, 'index']);
            Route::post('/', [KpiDailyDataController::class, 'store']);
            Route::post('/bulk', [KpiDailyDataController::class, 'bulkStore']);
            Route::get('/{id}', [KpiDailyDataController::class, 'show']);
            Route::put('/{id}', [KpiDailyDataController::class, 'update']);
            Route::delete('/{id}', [KpiDailyDataController::class, 'destroy']);

            // Daily Data Actions
            Route::post('/{id}/verify', [KpiDailyDataController::class, 'verify']);
            Route::post('/{id}/mark-anomaly', [KpiDailyDataController::class, 'markAnomaly']);

            // Week & Anomaly Data
            Route::get('/week/data', [KpiDailyDataController::class, 'getWeekData']);
            Route::get('/anomalies', [KpiDailyDataController::class, 'getAnomalies']);

            // Integration Sync Routes (Rate limited to prevent DDoS - 5 requests per hour)
            Route::post('/sync-from-integrations', [KpiDailyDataController::class, 'syncFromIntegrations'])
                ->middleware('throttle:kpi-sync');
            Route::get('/integration-sync-status', [KpiDailyDataController::class, 'getIntegrationSyncStatus']);
            Route::post('/{id}/manual-override', [KpiDailyDataController::class, 'manualOverride']);
            Route::post('/{id}/restore-auto-calculated', [KpiDailyDataController::class, 'restoreAutoCalculated']);
            Route::get('/manual-overrides', [KpiDailyDataController::class, 'getManualOverrides']);

            // Monitoring & Health Check Routes (Rate limited - 30 requests per minute)
            Route::middleware('throttle:kpi-monitoring')->group(function () {
                Route::get('/sync-health', [KpiDailyDataController::class, 'getSyncHealth']);
                Route::get('/sync-dashboard', [KpiDailyDataController::class, 'getSyncDashboard']);
                Route::get('/batch-stats', [KpiDailyDataController::class, 'getBatchStats']);
                Route::get('/failed-businesses', [KpiDailyDataController::class, 'getFailedBusinesses']);
                Route::get('/performance-trends', [KpiDailyDataController::class, 'getPerformanceTrends']);
                Route::get('/integration-statistics', [KpiDailyDataController::class, 'getIntegrationStatistics']);
                Route::get('/sync-running', [KpiDailyDataController::class, 'isSyncRunning']);
            });
        });

        // KPI Entry Routes - Qo'lda kiritish va tahlil
        Route::prefix('kpi-entry')->group(function () {
            // Справочnik
            Route::get('/reference-data', [KpiEntryController::class, 'getReferenceData']);

            // Tezkor kiritish
            Route::get('/quick-entry', [KpiEntryController::class, 'getQuickEntryData']);
            Route::post('/quick-entry', [KpiEntryController::class, 'storeQuickEntry']);

            // To'liq kiritish
            Route::get('/full-entry', [KpiEntryController::class, 'getFullEntryData']);
            Route::post('/full-entry', [KpiEntryController::class, 'storeFullEntry']);

            // Kunlik ma'lumotlar
            Route::get('/daily', [KpiEntryController::class, 'getDailyEntries']);
            Route::get('/daily/{date}', [KpiEntryController::class, 'getDailyEntry']);
            Route::delete('/daily/{date}', [KpiEntryController::class, 'deleteDailyEntry']);
            Route::post('/daily/{id}/verify', [KpiEntryController::class, 'verifyEntry']);

            // Haftalik va oylik
            Route::get('/weekly', [KpiEntryController::class, 'getWeeklyData']);
            Route::get('/monthly', [KpiEntryController::class, 'getMonthlyData']);

            // Dashboard va trendlar
            Route::get('/dashboard', [KpiEntryController::class, 'getDashboard']);
            Route::get('/trends', [KpiEntryController::class, 'getTrends']);

            // Manba tahlili
            Route::get('/source-analysis', [KpiEntryController::class, 'getSourceAnalysis']);
            Route::get('/category-analysis', [KpiEntryController::class, 'getCategoryAnalysis']);

            // Agregatsiya
            Route::post('/aggregate-weekly', [KpiEntryController::class, 'triggerWeeklyAggregation']);
            Route::post('/aggregate-monthly', [KpiEntryController::class, 'triggerMonthlyAggregation']);
        });

        // KPI Dashboard Routes
        Route::prefix('kpi-dashboard')->group(function () {
            // NEW: Professional Business Dashboard with Table View
            Route::get('/', [\App\Http\Controllers\Api\KpiBusinessDashboardController::class, 'getDashboard']);

            // Original dashboard endpoints
            Route::get('/overview', [KpiDashboardController::class, 'getOverview']);
            Route::get('/weekly', [KpiDashboardController::class, 'getWeeklyDashboard']);
            Route::get('/monthly', [KpiDashboardController::class, 'getMonthlyDashboard']);
            Route::get('/performance-comparison', [KpiDashboardController::class, 'getPerformanceComparison']);
            Route::get('/trend-analysis/{kpiCode}', [KpiDashboardController::class, 'getTrendAnalysis']);
            Route::get('/aggregation-status', [KpiDashboardController::class, 'getAggregationStatus']);
            Route::post('/trigger-aggregation', [KpiDashboardController::class, 'triggerAggregation']);
        });

        // ========== HR SYSTEM API ROUTES ==========
        // HR Dashboard - Asosiy HR boshqaruv paneli
        Route::prefix('hr')->group(function () {
            // Dashboard
            Route::get('/dashboard', [\App\Http\Controllers\Api\HR\HRDashboardController::class, 'index']);
            Route::get('/dashboard/employees', [\App\Http\Controllers\Api\HR\HRDashboardController::class, 'employeeOverview']);
            Route::get('/dashboard/departments', [\App\Http\Controllers\Api\HR\HRDashboardController::class, 'departmentStats']);

            // Engagement - Hodimlar ishga qiziqishi
            Route::prefix('engagement')->group(function () {
                Route::get('/', [\App\Http\Controllers\Api\HR\EngagementController::class, 'index']);
                Route::get('/statistics', [\App\Http\Controllers\Api\HR\EngagementController::class, 'statistics']);
                Route::get('/{userId}', [\App\Http\Controllers\Api\HR\EngagementController::class, 'show']);
                Route::post('/', [\App\Http\Controllers\Api\HR\EngagementController::class, 'store']);
                Route::post('/{userId}/recalculate', [\App\Http\Controllers\Api\HR\EngagementController::class, 'recalculate']);
            });

            // Flight Risk - Ketish xavfi
            Route::prefix('flight-risk')->group(function () {
                Route::get('/', [\App\Http\Controllers\Api\HR\FlightRiskController::class, 'index']);
                Route::get('/statistics', [\App\Http\Controllers\Api\HR\FlightRiskController::class, 'statistics']);
                Route::get('/{userId}', [\App\Http\Controllers\Api\HR\FlightRiskController::class, 'show']);
                Route::post('/{userId}/recalculate', [\App\Http\Controllers\Api\HR\FlightRiskController::class, 'recalculate']);
                Route::post('/{userId}/mitigation', [\App\Http\Controllers\Api\HR\FlightRiskController::class, 'addMitigationAction']);
                Route::post('/{userId}/mitigation/{actionIndex}/complete', [\App\Http\Controllers\Api\HR\FlightRiskController::class, 'completeMitigationAction']);
            });

            // Onboarding - Yangi hodimlar adaptatsiyasi
            Route::prefix('onboarding')->group(function () {
                Route::get('/', [\App\Http\Controllers\Api\HR\OnboardingController::class, 'index']);
                Route::get('/statistics', [\App\Http\Controllers\Api\HR\OnboardingController::class, 'statistics']);
                Route::post('/', [\App\Http\Controllers\Api\HR\OnboardingController::class, 'store']);
                Route::get('/{planId}', [\App\Http\Controllers\Api\HR\OnboardingController::class, 'show']);
                Route::put('/{planId}', [\App\Http\Controllers\Api\HR\OnboardingController::class, 'update']);
                Route::post('/{planId}/tasks', [\App\Http\Controllers\Api\HR\OnboardingController::class, 'addTask']);
                Route::put('/{planId}/tasks/{taskId}', [\App\Http\Controllers\Api\HR\OnboardingController::class, 'updateTaskStatus']);
                Route::post('/{planId}/milestone', [\App\Http\Controllers\Api\HR\OnboardingController::class, 'completeMilestone']);
            });

            // Surveys - So'rovnomalar
            Route::prefix('surveys')->group(function () {
                Route::get('/', [\App\Http\Controllers\Api\HR\SurveyController::class, 'index']);
                Route::get('/statistics', [\App\Http\Controllers\Api\HR\SurveyController::class, 'statistics']);
                Route::get('/my-available', [\App\Http\Controllers\Api\HR\SurveyController::class, 'myAvailableSurveys']);
                Route::post('/', [\App\Http\Controllers\Api\HR\SurveyController::class, 'store']);
                Route::post('/from-template', [\App\Http\Controllers\Api\HR\SurveyController::class, 'createFromTemplate']);
                Route::get('/{surveyId}', [\App\Http\Controllers\Api\HR\SurveyController::class, 'show']);
                Route::put('/{surveyId}', [\App\Http\Controllers\Api\HR\SurveyController::class, 'update']);
                Route::post('/{surveyId}/activate', [\App\Http\Controllers\Api\HR\SurveyController::class, 'activate']);
                Route::post('/{surveyId}/close', [\App\Http\Controllers\Api\HR\SurveyController::class, 'close']);
                Route::post('/{surveyId}/respond', [\App\Http\Controllers\Api\HR\SurveyController::class, 'submitResponse']);
                Route::get('/{surveyId}/results', [\App\Http\Controllers\Api\HR\SurveyController::class, 'results']);
                Route::get('/{surveyId}/engagement', [\App\Http\Controllers\Api\HR\SurveyController::class, 'surveyEngagement']);
                Route::get('/{surveyId}/flight-risk', [\App\Http\Controllers\Api\HR\SurveyController::class, 'surveyFlightRisk']);
            });

            // Alerts - HR ogohlantirishlari
            Route::prefix('alerts')->group(function () {
                Route::get('/', [\App\Http\Controllers\Api\HR\AlertsController::class, 'index']);
                Route::get('/unread-count', [\App\Http\Controllers\Api\HR\AlertsController::class, 'unreadCount']);
                Route::get('/statistics', [\App\Http\Controllers\Api\HR\AlertsController::class, 'statistics']);
                Route::post('/mark-all-seen', [\App\Http\Controllers\Api\HR\AlertsController::class, 'markAllAsSeen']);
                Route::get('/{alertId}', [\App\Http\Controllers\Api\HR\AlertsController::class, 'show']);
                Route::post('/{alertId}/acknowledge', [\App\Http\Controllers\Api\HR\AlertsController::class, 'acknowledge']);
                Route::post('/{alertId}/resolve', [\App\Http\Controllers\Api\HR\AlertsController::class, 'resolve']);
            });

            // Turnover - Hodimlar ketishi tahlili
            Route::prefix('turnover')->group(function () {
                Route::get('/', [\App\Http\Controllers\Api\HR\TurnoverController::class, 'index']);
                Route::get('/statistics', [\App\Http\Controllers\Api\HR\TurnoverController::class, 'statistics']);
                Route::get('/report', [\App\Http\Controllers\Api\HR\TurnoverController::class, 'report']);
                Route::post('/', [\App\Http\Controllers\Api\HR\TurnoverController::class, 'store']);
                Route::get('/{recordId}', [\App\Http\Controllers\Api\HR\TurnoverController::class, 'show']);
                Route::post('/{recordId}/exit-interview', [\App\Http\Controllers\Api\HR\TurnoverController::class, 'storeExitInterview']);
                Route::put('/{recordId}/replacement-status', [\App\Http\Controllers\Api\HR\TurnoverController::class, 'updateReplacementStatus']);
            });

            // Employee Management - Yagona xodimlar boshqaruvi
            Route::prefix('employees')->group(function () {
                Route::put('/{employeeId}/contract', [\App\Http\Controllers\HR\EmployeeManagementController::class, 'updateContract']);
                Route::post('/{employeeId}/terminate', [\App\Http\Controllers\HR\EmployeeManagementController::class, 'terminate']);
            });

            // Leave Requests - Ta'til so'rovlari
            Route::post('/leave-requests', [\App\Http\Controllers\HR\EmployeeManagementController::class, 'createLeaveRequest']);
        });

        // ========== BUSINESS SYSTEMATIZATION API ROUTES ==========
        // Denis Shenukov metodologiyasi asosida biznes tizimlash

        // Sales Analytics - ROP (Sotuv bo'limi rahbari) dashboard
        Route::prefix('sales-analytics')->group(function () {
            // Dashboard
            Route::get('/dashboard', [\App\Http\Controllers\Api\BusinessSystematization\SalesAnalyticsController::class, 'dashboard']);
            Route::get('/current-period', [\App\Http\Controllers\Api\BusinessSystematization\SalesAnalyticsController::class, 'currentPeriod']);
            Route::get('/manager-rankings', [\App\Http\Controllers\Api\BusinessSystematization\SalesAnalyticsController::class, 'managerRankings']);
            Route::get('/receivables', [\App\Http\Controllers\Api\BusinessSystematization\SalesAnalyticsController::class, 'receivables']);
            Route::get('/funnel', [\App\Http\Controllers\Api\BusinessSystematization\SalesAnalyticsController::class, 'funnel']);
            Route::get('/rejection-analysis', [\App\Http\Controllers\Api\BusinessSystematization\SalesAnalyticsController::class, 'rejectionAnalysis']);
            Route::get('/trend', [\App\Http\Controllers\Api\BusinessSystematization\SalesAnalyticsController::class, 'trend']);
            Route::get('/manager-activity/{userId}', [\App\Http\Controllers\Api\BusinessSystematization\SalesAnalyticsController::class, 'managerActivity']);

            // Sales Targets - Sotuv rejalari
            Route::get('/targets', [\App\Http\Controllers\Api\BusinessSystematization\SalesAnalyticsController::class, 'listTargets']);
            Route::post('/targets', [\App\Http\Controllers\Api\BusinessSystematization\SalesAnalyticsController::class, 'createTarget']);
            Route::put('/targets/{target}', [\App\Http\Controllers\Api\BusinessSystematization\SalesAnalyticsController::class, 'updateTarget']);

            // Sales Activities - Kunlik faoliyat
            Route::post('/activities', [\App\Http\Controllers\Api\BusinessSystematization\SalesAnalyticsController::class, 'recordActivity']);

            // Receivables - Debitorka
            Route::get('/receivables/list', [\App\Http\Controllers\Api\BusinessSystematization\SalesAnalyticsController::class, 'listReceivables']);
            Route::post('/receivables', [\App\Http\Controllers\Api\BusinessSystematization\SalesAnalyticsController::class, 'createReceivable']);
            Route::post('/receivables/{receivable}/payment', [\App\Http\Controllers\Api\BusinessSystematization\SalesAnalyticsController::class, 'recordPayment']);

            // Funnel & Rejections - Voronka va rad etishlar
            Route::get('/funnel-stages', [\App\Http\Controllers\Api\BusinessSystematization\SalesAnalyticsController::class, 'listFunnelStages']);
            Route::get('/rejection-reasons', [\App\Http\Controllers\Api\BusinessSystematization\SalesAnalyticsController::class, 'listRejectionReasons']);
            Route::post('/lost-deals', [\App\Http\Controllers\Api\BusinessSystematization\SalesAnalyticsController::class, 'recordLostDeal']);
        });

        // Motivation System - Motivatsiya tizimi
        Route::prefix('motivation')->group(function () {
            // Schemes - Motivatsiya sxemalari
            Route::get('/schemes', [\App\Http\Controllers\Api\BusinessSystematization\MotivationController::class, 'listSchemes']);
            Route::get('/schemes/{scheme}', [\App\Http\Controllers\Api\BusinessSystematization\MotivationController::class, 'getScheme']);
            Route::post('/schemes', [\App\Http\Controllers\Api\BusinessSystematization\MotivationController::class, 'createScheme']);
            Route::put('/schemes/{scheme}', [\App\Http\Controllers\Api\BusinessSystematization\MotivationController::class, 'updateScheme']);

            // Components - Motivatsiya komponentlari
            Route::post('/schemes/{scheme}/components', [\App\Http\Controllers\Api\BusinessSystematization\MotivationController::class, 'addComponent']);
            Route::put('/components/{component}', [\App\Http\Controllers\Api\BusinessSystematization\MotivationController::class, 'updateComponent']);
            Route::delete('/components/{component}', [\App\Http\Controllers\Api\BusinessSystematization\MotivationController::class, 'deleteComponent']);

            // Employee Motivation - Xodimga tayinlash
            Route::post('/assign', [\App\Http\Controllers\Api\BusinessSystematization\MotivationController::class, 'assignToEmployee']);
            Route::get('/employee/{userId}', [\App\Http\Controllers\Api\BusinessSystematization\MotivationController::class, 'getEmployeeMotivation']);

            // Calculations - Hisoblashlar
            Route::post('/calculate', [\App\Http\Controllers\Api\BusinessSystematization\MotivationController::class, 'calculate']);
            Route::get('/calculations/{userId}', [\App\Http\Controllers\Api\BusinessSystematization\MotivationController::class, 'getCalculationHistory']);
            Route::post('/calculations/{calculation}/approve', [\App\Http\Controllers\Api\BusinessSystematization\MotivationController::class, 'approveCalculation']);

            // KPI Helper - KPI hisoblash
            Route::post('/calculate-kpi', [\App\Http\Controllers\Api\BusinessSystematization\MotivationController::class, 'calculateKpi']);
            Route::post('/generate-scale', [\App\Http\Controllers\Api\BusinessSystematization\MotivationController::class, 'generateScaleTable']);

            // Key Task Maps - Asosiy vazifalar kartasi
            Route::get('/key-task-maps', [\App\Http\Controllers\Api\BusinessSystematization\MotivationController::class, 'listKeyTaskMaps']);
            Route::post('/key-task-maps', [\App\Http\Controllers\Api\BusinessSystematization\MotivationController::class, 'createKeyTaskMap']);
            Route::put('/key-tasks/{task}', [\App\Http\Controllers\Api\BusinessSystematization\MotivationController::class, 'updateKeyTask']);
            Route::get('/key-task-maps/{map}/bonus', [\App\Http\Controllers\Api\BusinessSystematization\MotivationController::class, 'calculateKeyTaskMapBonus']);
        });

        // Marketing Integration - Marketing-Sotuv integratsiyasi
        Route::prefix('marketing-integration')->group(function () {
            // Dashboard
            Route::get('/dashboard', [\App\Http\Controllers\Api\BusinessSystematization\MarketingIntegrationController::class, 'dashboard']);
            Route::get('/sales-linkage', [\App\Http\Controllers\Api\BusinessSystematization\MarketingIntegrationController::class, 'salesLinkage']);
            Route::get('/channel-performance', [\App\Http\Controllers\Api\BusinessSystematization\MarketingIntegrationController::class, 'channelPerformance']);
            Route::get('/budget-status', [\App\Http\Controllers\Api\BusinessSystematization\MarketingIntegrationController::class, 'budgetStatus']);
            Route::get('/lead-quality', [\App\Http\Controllers\Api\BusinessSystematization\MarketingIntegrationController::class, 'leadQuality']);
            Route::get('/campaign-roi', [\App\Http\Controllers\Api\BusinessSystematization\MarketingIntegrationController::class, 'campaignRoi']);
            Route::post('/calculate-bonus', [\App\Http\Controllers\Api\BusinessSystematization\MarketingIntegrationController::class, 'calculateBonus']);

            // KPIs - Marketing KPI'lar
            Route::get('/kpis', [\App\Http\Controllers\Api\BusinessSystematization\MarketingIntegrationController::class, 'listKpis']);
            Route::post('/kpis', [\App\Http\Controllers\Api\BusinessSystematization\MarketingIntegrationController::class, 'createKpi']);
            Route::put('/kpis/{kpi}', [\App\Http\Controllers\Api\BusinessSystematization\MarketingIntegrationController::class, 'updateKpi']);

            // Campaigns - Kampaniyalar
            Route::get('/campaigns', [\App\Http\Controllers\Api\BusinessSystematization\MarketingIntegrationController::class, 'listCampaigns']);
            Route::post('/campaigns', [\App\Http\Controllers\Api\BusinessSystematization\MarketingIntegrationController::class, 'createCampaign']);
            Route::put('/campaigns/{campaign}', [\App\Http\Controllers\Api\BusinessSystematization\MarketingIntegrationController::class, 'updateCampaignMetrics']);

            // Budgets - Byudjetlar
            Route::get('/budgets', [\App\Http\Controllers\Api\BusinessSystematization\MarketingIntegrationController::class, 'listBudgets']);
            Route::post('/budgets', [\App\Http\Controllers\Api\BusinessSystematization\MarketingIntegrationController::class, 'saveBudget']);
            Route::post('/budgets/{budget}/spend', [\App\Http\Controllers\Api\BusinessSystematization\MarketingIntegrationController::class, 'recordSpending']);

            // Lead Flow - Lid oqimi
            Route::post('/lead-flow', [\App\Http\Controllers\Api\BusinessSystematization\MarketingIntegrationController::class, 'recordLeadFlow']);
            Route::get('/lead-flow/history', [\App\Http\Controllers\Api\BusinessSystematization\MarketingIntegrationController::class, 'getLeadFlowHistory']);

            // Channels - Kanallar
            Route::get('/channels', [\App\Http\Controllers\Api\BusinessSystematization\MarketingIntegrationController::class, 'listChannels']);
            Route::post('/channels', [\App\Http\Controllers\Api\BusinessSystematization\MarketingIntegrationController::class, 'createChannel']);
        });

        // Employee Classification - Xodim klassifikatsiyasi (HR kengaytma)
        Route::prefix('employee-classification')->group(function () {
            // Dashboard
            Route::get('/dashboard', [\App\Http\Controllers\Api\BusinessSystematization\EmployeeClassificationController::class, 'dashboard']);
            Route::get('/summary', [\App\Http\Controllers\Api\BusinessSystematization\EmployeeClassificationController::class, 'classificationSummary']);
            Route::get('/star-risks', [\App\Http\Controllers\Api\BusinessSystematization\EmployeeClassificationController::class, 'starEmployeeRisks']);
            Route::get('/position-mismatches', [\App\Http\Controllers\Api\BusinessSystematization\EmployeeClassificationController::class, 'positionMismatches']);
            Route::get('/knowledge-risks', [\App\Http\Controllers\Api\BusinessSystematization\EmployeeClassificationController::class, 'knowledgeRisks']);

            // Classifications - Klassifikatsiyalar
            Route::get('/', [\App\Http\Controllers\Api\BusinessSystematization\EmployeeClassificationController::class, 'listClassifications']);
            Route::post('/classify', [\App\Http\Controllers\Api\BusinessSystematization\EmployeeClassificationController::class, 'classifyEmployee']);
            Route::post('/mark-star', [\App\Http\Controllers\Api\BusinessSystematization\EmployeeClassificationController::class, 'markAsStarEmployee']);

            // Function Knowledge - Funksiya bilimi
            Route::get('/functions', [\App\Http\Controllers\Api\BusinessSystematization\EmployeeClassificationController::class, 'listFunctionKnowledge']);
            Route::post('/functions', [\App\Http\Controllers\Api\BusinessSystematization\EmployeeClassificationController::class, 'registerFunctionKnowledge']);
            Route::put('/functions/{function}', [\App\Http\Controllers\Api\BusinessSystematization\EmployeeClassificationController::class, 'updateKnowledgeHolders']);

            // Vacancies - Vakansiyalar
            Route::get('/vacancies', [\App\Http\Controllers\Api\BusinessSystematization\EmployeeClassificationController::class, 'listVacancies']);
            Route::post('/vacancies', [\App\Http\Controllers\Api\BusinessSystematization\EmployeeClassificationController::class, 'createVacancy']);
            Route::put('/vacancies/{vacancy}', [\App\Http\Controllers\Api\BusinessSystematization\EmployeeClassificationController::class, 'updateVacancy']);

            // Interviews - Intervyular
            Route::get('/vacancies/{vacancy}/interviews', [\App\Http\Controllers\Api\BusinessSystematization\EmployeeClassificationController::class, 'listInterviews']);
            Route::post('/vacancies/{vacancy}/interviews', [\App\Http\Controllers\Api\BusinessSystematization\EmployeeClassificationController::class, 'createInterview']);
            Route::put('/interviews/{interview}', [\App\Http\Controllers\Api\BusinessSystematization\EmployeeClassificationController::class, 'updateInterview']);

            // Business Diagnostics - Biznes diagnostikasi
            Route::get('/diagnostics', [\App\Http\Controllers\Api\BusinessSystematization\EmployeeClassificationController::class, 'getLatestDiagnostics']);
            Route::post('/diagnostics', [\App\Http\Controllers\Api\BusinessSystematization\EmployeeClassificationController::class, 'createDiagnostics']);
            Route::get('/diagnostics/history', [\App\Http\Controllers\Api\BusinessSystematization\EmployeeClassificationController::class, 'getDiagnosticsHistory']);
        });
    });
});

// ========== MINI APP STORE API ==========
// Telegram Mini App do'kon API'lari
// Store route model binding: {store:slug} orqali TelegramStore topiladi
Route::prefix('miniapp/v1/{store:slug}')->group(function () {
    // Public (autentifikatsiya shart emas) — tight-but-generous per-slug throttle
    Route::middleware('throttle:miniapp-public')->group(function () {
        Route::get('/info', [\App\Http\Controllers\Api\MiniApp\StoreController::class, 'info']);
        Route::get('/categories', [\App\Http\Controllers\Api\MiniApp\StoreController::class, 'categories']);
        Route::get('/delivery-zones', [\App\Http\Controllers\Api\MiniApp\CheckoutController::class, 'deliveryZones']);
        Route::get('/regions', [\App\Http\Controllers\Api\MiniApp\RegionController::class, 'regions']);
        Route::get('/regions/{key}/districts', [\App\Http\Controllers\Api\MiniApp\RegionController::class, 'districts']);

        // Unified Catalog API (barcha bot turlari uchun)
        Route::get('/catalog', [\App\Http\Controllers\Api\MiniApp\MiniAppCatalogController::class, 'index']);
        Route::get('/catalog/search', [\App\Http\Controllers\Api\MiniApp\MiniAppCatalogController::class, 'search']);
        Route::get('/catalog/featured', [\App\Http\Controllers\Api\MiniApp\MiniAppCatalogController::class, 'featured']);
        Route::get('/catalog/filters', [\App\Http\Controllers\Api\MiniApp\MiniAppCatalogController::class, 'filterOptions']);
        Route::get('/catalog/{slug}', [\App\Http\Controllers\Api\MiniApp\MiniAppCatalogController::class, 'show']);

        // Legacy product routes (backward compat)
        Route::get('/featured', [\App\Http\Controllers\Api\MiniApp\StoreController::class, 'featured']);
        Route::get('/products', [\App\Http\Controllers\Api\MiniApp\ProductController::class, 'index']);
        Route::get('/products/search', [\App\Http\Controllers\Api\MiniApp\ProductController::class, 'search']);
        Route::get('/products/{product:slug}', [\App\Http\Controllers\Api\MiniApp\ProductController::class, 'show']);
    });

    // Authenticated (Telegram initData talab qilinadi)
    Route::middleware(['miniapp.auth', 'throttle:miniapp-auth'])->group(function () {
        Route::post('/auth', [\App\Http\Controllers\Api\MiniApp\AuthController::class, 'authenticate']);
        Route::get('/profile', [\App\Http\Controllers\Api\MiniApp\ProfileController::class, 'show']);
        Route::post('/profile/addresses', [\App\Http\Controllers\Api\MiniApp\ProfileController::class, 'storeAddress']);
        Route::delete('/profile/addresses/{address}', [\App\Http\Controllers\Api\MiniApp\ProfileController::class, 'deleteAddress']);
        Route::put('/profile/addresses/{address}/default', [\App\Http\Controllers\Api\MiniApp\ProfileController::class, 'setDefault']);
        Route::get('/cart', [\App\Http\Controllers\Api\MiniApp\CartController::class, 'index']);
        Route::post('/cart', [\App\Http\Controllers\Api\MiniApp\CartController::class, 'addItem']);
        Route::put('/cart/{item}', [\App\Http\Controllers\Api\MiniApp\CartController::class, 'updateItem']);
        Route::delete('/cart/{item}', [\App\Http\Controllers\Api\MiniApp\CartController::class, 'removeItem']);
        Route::post('/cart/sync', [\App\Http\Controllers\Api\MiniApp\CartController::class, 'sync']);
        Route::post('/cart/promo', [\App\Http\Controllers\Api\MiniApp\CartController::class, 'applyPromo']);
        Route::post('/checkout', [\App\Http\Controllers\Api\MiniApp\CheckoutController::class, 'checkout'])
            ->middleware('throttle:miniapp-checkout');
        Route::get('/orders', [\App\Http\Controllers\Api\MiniApp\OrderController::class, 'index']);
        Route::get('/orders/{order:order_number}', [\App\Http\Controllers\Api\MiniApp\OrderController::class, 'show']);

        // ========== BOOKING API (Queue bot uchun) ==========
        Route::get('/bookings/slots', [\App\Http\Controllers\Api\MiniApp\BookingController::class, 'slots']);
        Route::get('/bookings', [\App\Http\Controllers\Api\MiniApp\BookingController::class, 'index']);
        Route::post('/bookings', [\App\Http\Controllers\Api\MiniApp\BookingController::class, 'store']);
        Route::get('/bookings/{id}', [\App\Http\Controllers\Api\MiniApp\BookingController::class, 'show']);
        Route::post('/bookings/{id}/cancel', [\App\Http\Controllers\Api\MiniApp\BookingController::class, 'cancel']);
        Route::get('/staff', [\App\Http\Controllers\Api\MiniApp\BookingController::class, 'staff']);
        Route::get('/staff/{id}', [\App\Http\Controllers\Api\MiniApp\BookingController::class, 'staffShow']);

        // ========== SERVICE REQUEST API (Service bot uchun) ==========
        Route::get('/service-requests', [\App\Http\Controllers\Api\MiniApp\ServiceRequestController::class, 'index']);
        Route::post('/service-requests', [\App\Http\Controllers\Api\MiniApp\ServiceRequestController::class, 'store']);
        Route::get('/service-requests/{id}', [\App\Http\Controllers\Api\MiniApp\ServiceRequestController::class, 'show']);
        Route::post('/service-requests/{id}/cancel', [\App\Http\Controllers\Api\MiniApp\ServiceRequestController::class, 'cancel']);
        Route::get('/masters', [\App\Http\Controllers\Api\MiniApp\ServiceRequestController::class, 'masters']);
        Route::get('/masters/{id}', [\App\Http\Controllers\Api\MiniApp\ServiceRequestController::class, 'masterShow']);

        // ========== MINI APP ADMIN (Telegram ichidan boshqarish) ==========
        // Do'kon egasi/admini uchun boshqaruv paneli
        // Admin tekshiruvi controller ichida amalga oshiriladi (isStoreAdmin)
        Route::prefix('admin')->group(function () {
            Route::get('/dashboard', [\App\Http\Controllers\Api\MiniApp\MiniAppAdminController::class, 'dashboard']);
            Route::get('/orders', [\App\Http\Controllers\Api\MiniApp\MiniAppAdminController::class, 'orders']);
            Route::get('/orders/{order}', [\App\Http\Controllers\Api\MiniApp\MiniAppAdminController::class, 'orderDetail']);
            Route::post('/orders/{order}/status', [\App\Http\Controllers\Api\MiniApp\MiniAppAdminController::class, 'updateOrderStatus']);
            Route::get('/catalog', [\App\Http\Controllers\Api\MiniApp\MiniAppAdminController::class, 'catalog']);
            Route::post('/catalog/{id}/toggle', [\App\Http\Controllers\Api\MiniApp\MiniAppAdminController::class, 'toggleCatalogItem']);
            Route::get('/stats', [\App\Http\Controllers\Api\MiniApp\MiniAppAdminController::class, 'stats']);
        });
    });
});

// ========== STORE TELEGRAM BOT WEBHOOK ==========
// Telegram bot webhook (do'kon uchun)
Route::post('/webhooks/store-bot/{store}', [\App\Http\Controllers\Api\MiniApp\StoreTelegramWebhookController::class, 'handle']);

// ========== STORE PAYMENT WEBHOOKS ==========
// To'lov tizimlari webhook'lari (autentifikatsiya yo'q - imzo orqali tekshiriladi)
Route::prefix('store-webhooks')->middleware('throttle:billing-webhooks')->group(function () {
    Route::post('/payme/{store}', [\App\Http\Controllers\Api\MiniApp\StorePaymeWebhookController::class, 'handle']);
    Route::post('/click/{store}/prepare', [\App\Http\Controllers\Api\MiniApp\StoreClickWebhookController::class, 'prepare']);
    Route::post('/click/{store}/complete', [\App\Http\Controllers\Api\MiniApp\StoreClickWebhookController::class, 'complete']);
});

// ========== DELIVERY BOT API ==========
Route::prefix('v1/bot/delivery')->group(function () {
    // Mini App (Public)
    Route::get('/menu', [\App\Http\Controllers\Api\Bot\Delivery\DeliveryMenuController::class, 'index']);
    Route::get('/menu/search', [\App\Http\Controllers\Api\Bot\Delivery\DeliveryMenuController::class, 'search']);
    Route::get('/menu/popular', [\App\Http\Controllers\Api\Bot\Delivery\DeliveryMenuController::class, 'popular']);
    Route::get('/menu/{item}', [\App\Http\Controllers\Api\Bot\Delivery\DeliveryMenuController::class, 'show']);

    // Orders & Addresses (Telegram auth via X-Business-Id header)
    Route::post('/orders', [\App\Http\Controllers\Api\Bot\Delivery\DeliveryOrderController::class, 'store']);
    Route::get('/orders', [\App\Http\Controllers\Api\Bot\Delivery\DeliveryOrderController::class, 'index']);
    Route::get('/orders/{order}', [\App\Http\Controllers\Api\Bot\Delivery\DeliveryOrderController::class, 'show']);
    Route::post('/orders/{order}/cancel', [\App\Http\Controllers\Api\Bot\Delivery\DeliveryOrderController::class, 'cancel']);
    Route::get('/orders/{order}/track', [\App\Http\Controllers\Api\Bot\Delivery\DeliveryOrderController::class, 'track']);

    // Kupon tekshirish
    Route::post('/validate-coupon', [\App\Http\Controllers\Api\Bot\Delivery\DeliveryOrderController::class, 'validateCoupon']);

    Route::get('/addresses', [\App\Http\Controllers\Api\Bot\Delivery\DeliveryAddressController::class, 'index']);
    Route::post('/addresses', [\App\Http\Controllers\Api\Bot\Delivery\DeliveryAddressController::class, 'store']);
    Route::put('/addresses/{addr}', [\App\Http\Controllers\Api\Bot\Delivery\DeliveryAddressController::class, 'update']);
    Route::delete('/addresses/{addr}', [\App\Http\Controllers\Api\Bot\Delivery\DeliveryAddressController::class, 'destroy']);
});

// Delivery Admin Panel
Route::prefix('v1/admin/delivery')->middleware('auth:sanctum')->group(function () {
    Route::get('dashboard', [\App\Http\Controllers\Api\Admin\Delivery\DeliveryDashboardController::class, 'index']);

    Route::apiResource('categories', \App\Http\Controllers\Api\Admin\Delivery\DeliveryCategoryAdminController::class)->names('delivery.categories');
    Route::post('categories/reorder', [\App\Http\Controllers\Api\Admin\Delivery\DeliveryCategoryAdminController::class, 'reorder']);

    Route::apiResource('menu-items', \App\Http\Controllers\Api\Admin\Delivery\DeliveryMenuItemAdminController::class);
    Route::patch('menu-items/{menuItem}/toggle', [\App\Http\Controllers\Api\Admin\Delivery\DeliveryMenuItemAdminController::class, 'toggle']);

    Route::get('orders', [\App\Http\Controllers\Api\Admin\Delivery\DeliveryOrderAdminController::class, 'index']);
    Route::get('orders/{order}', [\App\Http\Controllers\Api\Admin\Delivery\DeliveryOrderAdminController::class, 'show']);
    Route::patch('orders/{order}/status', [\App\Http\Controllers\Api\Admin\Delivery\DeliveryOrderAdminController::class, 'updateStatus']);
    Route::patch('orders/{order}/courier', [\App\Http\Controllers\Api\Admin\Delivery\DeliveryOrderAdminController::class, 'assignCourier']);

    Route::get('settings', [\App\Http\Controllers\Api\Admin\Delivery\DeliverySettingsController::class, 'show']);
    Route::put('settings', [\App\Http\Controllers\Api\Admin\Delivery\DeliverySettingsController::class, 'update']);
});

// ========== QUEUE BOT API ==========
Route::prefix('v1/bot/queue')->group(function () {
    // Mini App (Public)
    Route::get('/services', [\App\Http\Controllers\Api\Bot\Queue\QueueServiceController::class, 'index']);
    Route::get('/branches', [\App\Http\Controllers\Api\Bot\Queue\QueueBranchController::class, 'index']);
    Route::get('/slots', [\App\Http\Controllers\Api\Bot\Queue\QueueSlotController::class, 'available']);
    Route::get('/specialists', [\App\Http\Controllers\Api\Bot\Queue\QueueBranchController::class, 'specialists']);

    // Bookings
    Route::post('/bookings', [\App\Http\Controllers\Api\Bot\Queue\QueueBookingController::class, 'store']);
    Route::get('/bookings', [\App\Http\Controllers\Api\Bot\Queue\QueueBookingController::class, 'index']);
    Route::get('/bookings/{booking}', [\App\Http\Controllers\Api\Bot\Queue\QueueBookingController::class, 'show']);
    Route::post('/bookings/{booking}/cancel', [\App\Http\Controllers\Api\Bot\Queue\QueueBookingController::class, 'cancel']);
    Route::post('/bookings/{booking}/rate', [\App\Http\Controllers\Api\Bot\Queue\QueueBookingController::class, 'rate']);
    Route::get('/bookings/{booking}/position', [\App\Http\Controllers\Api\Bot\Queue\QueueTrackingController::class, 'position']);
});

// Queue Admin Panel
Route::prefix('v1/admin/queue')->middleware('auth:sanctum')->group(function () {
    Route::get('dashboard', [\App\Http\Controllers\Api\Admin\Queue\QueueDashboardController::class, 'index']);

    Route::apiResource('services', \App\Http\Controllers\Api\Admin\Queue\QueueServiceAdminController::class);
    Route::apiResource('branches', \App\Http\Controllers\Api\Admin\Queue\QueueBranchAdminController::class);
    Route::apiResource('specialists', \App\Http\Controllers\Api\Admin\Queue\QueueSpecialistAdminController::class);

    Route::get('bookings', [\App\Http\Controllers\Api\Admin\Queue\QueueBookingAdminController::class, 'index']);
    Route::get('bookings/{booking}', [\App\Http\Controllers\Api\Admin\Queue\QueueBookingAdminController::class, 'show']);
    Route::patch('bookings/{booking}/status', [\App\Http\Controllers\Api\Admin\Queue\QueueBookingAdminController::class, 'updateStatus']);
    Route::post('bookings/bulk-cancel', [\App\Http\Controllers\Api\Admin\Queue\QueueBookingAdminController::class, 'bulkCancel']);

    Route::post('slots/generate', [\App\Http\Controllers\Api\Admin\Queue\QueueSlotAdminController::class, 'generate']);
    Route::patch('slots/{slot}/block', [\App\Http\Controllers\Api\Admin\Queue\QueueSlotAdminController::class, 'block']);
    Route::patch('slots/{slot}/unblock', [\App\Http\Controllers\Api\Admin\Queue\QueueSlotAdminController::class, 'unblock']);

    Route::get('settings', [\App\Http\Controllers\Api\Admin\Queue\QueueSettingsController::class, 'show']);
    Route::put('settings', [\App\Http\Controllers\Api\Admin\Queue\QueueSettingsController::class, 'update']);
});

// ========== SERVICE BOT API ==========
Route::prefix('v1/bot/service')->group(function () {
    // Mini App (Public)
    Route::get('/categories', [\App\Http\Controllers\Api\Bot\Service\ServiceCatalogController::class, 'categories']);
    Route::get('/categories/{category}', [\App\Http\Controllers\Api\Bot\Service\ServiceCatalogController::class, 'categoryDetail']);
    Route::get('/masters', [\App\Http\Controllers\Api\Bot\Service\ServiceMasterController::class, 'index']);
    Route::get('/masters/{master}', [\App\Http\Controllers\Api\Bot\Service\ServiceMasterController::class, 'show']);

    // Service Requests
    Route::post('/requests', [\App\Http\Controllers\Api\Bot\Service\ServiceRequestController::class, 'store']);
    Route::get('/requests', [\App\Http\Controllers\Api\Bot\Service\ServiceRequestController::class, 'index']);
    Route::get('/requests/{serviceRequest}', [\App\Http\Controllers\Api\Bot\Service\ServiceRequestController::class, 'show']);
    Route::post('/requests/{serviceRequest}/cancel', [\App\Http\Controllers\Api\Bot\Service\ServiceRequestController::class, 'cancel']);
    Route::post('/requests/{serviceRequest}/approve-cost', [\App\Http\Controllers\Api\Bot\Service\ServiceRequestController::class, 'approveCost']);
    Route::post('/requests/{serviceRequest}/rate', [\App\Http\Controllers\Api\Bot\Service\ServiceRequestController::class, 'rate']);
    Route::get('/requests/{serviceRequest}/tracking', [\App\Http\Controllers\Api\Bot\Service\ServiceTrackingController::class, 'status']);
});

// Service Admin Panel
Route::prefix('v1/admin/service')->middleware('auth:sanctum')->group(function () {
    Route::get('dashboard', [\App\Http\Controllers\Api\Admin\Service\ServiceDashboardController::class, 'index']);

    Route::apiResource('categories', \App\Http\Controllers\Api\Admin\Service\ServiceCategoryAdminController::class)->names('service.categories');
    Route::apiResource('service-types', \App\Http\Controllers\Api\Admin\Service\ServiceTypeAdminController::class);
    Route::apiResource('masters', \App\Http\Controllers\Api\Admin\Service\ServiceMasterAdminController::class);

    Route::get('requests', [\App\Http\Controllers\Api\Admin\Service\ServiceRequestAdminController::class, 'index']);
    Route::get('requests/{serviceRequest}', [\App\Http\Controllers\Api\Admin\Service\ServiceRequestAdminController::class, 'show']);
    Route::patch('requests/{serviceRequest}/assign', [\App\Http\Controllers\Api\Admin\Service\ServiceRequestAdminController::class, 'assign']);
    Route::patch('requests/{serviceRequest}/status', [\App\Http\Controllers\Api\Admin\Service\ServiceRequestAdminController::class, 'updateStatus']);
    Route::patch('requests/{serviceRequest}/cost', [\App\Http\Controllers\Api\Admin\Service\ServiceRequestAdminController::class, 'setCost']);

    Route::get('settings', [\App\Http\Controllers\Api\Admin\Service\ServiceSettingsController::class, 'show']);
    Route::put('settings', [\App\Http\Controllers\Api\Admin\Service\ServiceSettingsController::class, 'update']);
});
