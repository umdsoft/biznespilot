<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\Auth\AuthController;
use App\Http\Controllers\Api\OnboardingController;
use App\Http\Controllers\Api\KpiConfigurationController;
use App\Http\Controllers\Api\KpiDailyDataController;
use App\Http\Controllers\Api\KpiDashboardController;
use App\Http\Controllers\Api\IntegrationsController;
use App\Http\Controllers\Api\KpiEntryController;
use App\Http\Controllers\Api\TranslationController;
use App\Http\Controllers\PbxWebhookController;
use App\Http\Controllers\MoiZvonkiWebhookController;
use App\Http\Controllers\UtelWebhookController;

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
    });
});
