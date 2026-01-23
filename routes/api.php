<?php

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
