<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\Auth\AuthController;
use App\Http\Controllers\Api\OnboardingController;

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

// Public routes - API v1
Route::prefix('v1')->group(function () {
    // Authentication routes
    Route::prefix('auth')->group(function () {
        Route::post('register', [AuthController::class, 'register']);
        Route::post('login', [AuthController::class, 'login']);
    });

    // Public onboarding data
    Route::get('industries', [OnboardingController::class, 'industries']);
    Route::get('onboarding/steps', [OnboardingController::class, 'steps']);
});

// Protected routes - API v1
Route::prefix('v1')->middleware(['web', 'auth'])->group(function () {
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
});
