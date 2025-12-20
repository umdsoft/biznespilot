<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use App\Http\Middleware\EnsureBusinessAccess;
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
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Register Inertia middleware
        $middleware->web(append: [
            HandleInertiaRequests::class,
            SetBusinessContext::class,
        ]);

        // Register middleware aliases
        $middleware->alias([
            'business.access' => EnsureBusinessAccess::class,
            'business.context' => SetBusinessContext::class,
            'subscription' => CheckSubscription::class,
            'feature.limit' => CheckFeatureLimit::class,
            'admin' => AdminMiddleware::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
