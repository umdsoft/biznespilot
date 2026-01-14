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
use App\Http\Middleware\SalesHeadMiddleware;
use App\Http\Middleware\MarketingMiddleware;
use App\Http\Middleware\FinanceMiddleware;
use App\Http\Middleware\HRMiddleware;
use App\Http\Middleware\OperatorMiddleware;
use App\Http\Middleware\SecurityHeaders;
use App\Http\Middleware\ForceHttps;
use App\Http\Middleware\TrustProxies;

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

            // KPI Sync endpoints rate limiter (DDoS protection)
            RateLimiter::for('kpi-sync', function (Request $request) {
                return Limit::perHour(5)->by($request->user()?->id ?: $request->ip());
            });

            // KPI monitoring endpoints (admin only, higher limit)
            RateLimiter::for('kpi-monitoring', function (Request $request) {
                return Limit::perMinute(30)->by($request->user()?->id ?: $request->ip());
            });
        },
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Trust all proxies (cloudflared, ngrok, etc.)
        $middleware->trustProxies(at: '*');

        // Global security middleware (production only)
        $middleware->prepend([
            ForceHttps::class,
            SecurityHeaders::class,
        ]);

        // Register Inertia middleware
        $middleware->web(append: [
            HandleInertiaRequests::class,
            SetBusinessContext::class,
        ]);

        // Exclude public survey and lead form routes from CSRF verification
        $middleware->validateCsrfTokens(except: [
            's/*',
            'f/*',
            'api/lead-forms/*',
            'webhooks/*',
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
            'sales.head' => SalesHeadMiddleware::class,
            'marketing' => MarketingMiddleware::class,
            'finance' => FinanceMiddleware::class,
            'hr' => HRMiddleware::class,
            'operator' => OperatorMiddleware::class,
            'throttle' => \Illuminate\Routing\Middleware\ThrottleRequests::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Handle CSRF token mismatch (419) specially for better UX
        $exceptions->render(function (\Illuminate\Session\TokenMismatchException $e, Request $request) {
            // For Inertia requests
            if ($request->hasHeader('X-Inertia')) {
                return response()->json([
                    'message' => 'Sessiya muddati tugadi. Sahifa yangilanmoqda...',
                    'csrf_refresh_required' => true,
                ], 419);
            }

            // For API/AJAX requests
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Sessiya muddati tugadi. Qayta urinib ko\'ring.',
                    'csrf_refresh_required' => true,
                ], 419);
            }

            // For regular web requests, redirect to login
            return redirect()->route('login')
                ->with('error', 'Sessiya muddati tugadi. Qayta kiring.');
        });

        // Production error handling
        $exceptions->render(function (\Throwable $e, Request $request) {
            // Skip CSRF exceptions (already handled above)
            if ($e instanceof \Illuminate\Session\TokenMismatchException) {
                return null;
            }

            // API error responses
            if ($request->is('api/*') || $request->expectsJson()) {
                $status = method_exists($e, 'getStatusCode')
                    ? $e->getStatusCode()
                    : 500;

                $response = [
                    'success' => false,
                    'message' => app()->environment('production')
                        ? __('Xatolik yuz berdi. Iltimos, qaytadan urinib ko\'ring.')
                        : $e->getMessage(),
                ];

                // Add debug info in non-production
                if (!app()->environment('production')) {
                    $response['debug'] = [
                        'exception' => get_class($e),
                        'file' => $e->getFile(),
                        'line' => $e->getLine(),
                        'trace' => collect($e->getTrace())->take(5)->toArray(),
                    ];
                }

                return response()->json($response, $status);
            }

            // Log critical errors
            if ($e instanceof \Error || $e->getCode() >= 500) {
                \Illuminate\Support\Facades\Log::critical('Critical error occurred', [
                    'exception' => get_class($e),
                    'message' => $e->getMessage(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                    'url' => $request->fullUrl(),
                    'user_id' => $request->user()?->id,
                    'ip' => $request->ip(),
                ]);
            }

            return null; // Use default rendering
        });

        // Don't report certain exceptions
        $exceptions->dontReport([
            \Illuminate\Auth\AuthenticationException::class,
            \Illuminate\Auth\Access\AuthorizationException::class,
            \Illuminate\Session\TokenMismatchException::class,
            \Symfony\Component\HttpKernel\Exception\HttpException::class,
            \Illuminate\Database\Eloquent\ModelNotFoundException::class,
            \Illuminate\Validation\ValidationException::class,
        ]);

        // Throttle exception reporting to prevent log flooding
        $exceptions->throttle(function (\Throwable $e) {
            return \Illuminate\Cache\RateLimiting\Limit::perMinute(5)->by(
                get_class($e) . '|' . $e->getFile() . '|' . $e->getLine()
            );
        });
    })->create();
