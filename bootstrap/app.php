<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Http\Request;
use App\Http\Middleware\EnsureBusinessAccess;
use App\Http\Middleware\EnsureHasBusiness;
use App\Http\Middleware\CheckSubscription;
use App\Http\Middleware\CheckFeatureLimit;
use App\Http\Middleware\HandleInertiaRequests;
use App\Http\Middleware\SetBusinessContext;
use App\Http\Middleware\AdminMiddleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        then: function () {
            // Configure rate limiters
            RateLimiter::for('api', function (Request $request) {
                return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
            });

            RateLimiter::for('web', function (Request $request) {
                return Limit::perMinute(120)->by($request->user()?->id ?: $request->ip());
            });

            RateLimiter::for('ai', function (Request $request) {
                return Limit::perMinute(10)->by($request->user()?->id ?: $request->ip());
            });

            RateLimiter::for('webhooks', function (Request $request) {
                return Limit::perMinute(300)->by($request->ip());
            });
        },
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Register Inertia middleware
        $middleware->web(append: [
            HandleInertiaRequests::class,
            SetBusinessContext::class,
        ]);

        // Enable throttle middleware for API
        $middleware->api(prepend: [
            \Illuminate\Routing\Middleware\ThrottleRequests::class.':api',
        ]);

        // Register middleware aliases
        $middleware->alias([
            'business.access' => EnsureBusinessAccess::class,
            'business.context' => SetBusinessContext::class,
            'has.business' => EnsureHasBusiness::class,
            'subscription' => CheckSubscription::class,
            'feature.limit' => CheckFeatureLimit::class,
            'admin' => AdminMiddleware::class,
            'throttle' => \Illuminate\Routing\Middleware\ThrottleRequests::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
