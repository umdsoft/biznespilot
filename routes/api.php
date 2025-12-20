<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\Auth\AuthController;

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
});

// Protected routes - API v1
Route::prefix('v1')->middleware('auth:sanctum')->group(function () {
    // Authentication routes
    Route::prefix('auth')->group(function () {
        Route::post('logout', [AuthController::class, 'logout']);
        Route::get('me', [AuthController::class, 'me']);
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
