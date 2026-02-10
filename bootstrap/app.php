<?php

use App\Http\Middleware\AdminMiddleware;
use App\Http\Middleware\CheckFeatureLimit;
use App\Http\Middleware\CheckSubscription;
use App\Http\Middleware\CheckSubscriptionQuota;
use App\Http\Middleware\EnsureBusinessAccess;
use App\Http\Middleware\EnsureFeatureEnabled;
use App\Http\Middleware\EnsureHasBusiness;
use App\Http\Middleware\FinanceMiddleware;
use App\Http\Middleware\ForceHttps;
use App\Http\Middleware\HandleInertiaRequests;
use App\Http\Middleware\HRMiddleware;
use App\Http\Middleware\MarketingMiddleware;
use App\Http\Middleware\OperatorMiddleware;
use App\Http\Middleware\PaymeBasicAuth;
use App\Http\Middleware\SalesHeadMiddleware;
use App\Http\Middleware\SecurityHeaders;
use App\Http\Middleware\SetBusinessContext;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
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

        // Exclude cookies read by JavaScript from encryption
        $middleware->encryptCookies(except: [
            'landing_locale',
            'locale',
        ]);

        // CSRF verification temporarily disabled
        $middleware->validateCsrfTokens(except: [
            '*',
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
            // Yangi middleware'lar - SubscriptionGate pattern
            'feature' => EnsureFeatureEnabled::class,      // Route::middleware('feature:hr_tasks')
            'quota' => CheckSubscriptionQuota::class,       // Route::middleware('quota:users')
            'admin' => AdminMiddleware::class,
            'sales.head' => SalesHeadMiddleware::class,
            'marketing' => MarketingMiddleware::class,
            'finance' => FinanceMiddleware::class,
            'hr' => HRMiddleware::class,
            'operator' => OperatorMiddleware::class,
            'throttle' => \Illuminate\Routing\Middleware\ThrottleRequests::class,
            // Billing middleware
            'payme.auth' => PaymeBasicAuth::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Handle unauthenticated users for API requests
        $exceptions->render(function (\Illuminate\Auth\AuthenticationException $e, Request $request) {
            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json([
                    'message' => 'Unauthenticated.',
                ], 401);
            }

            return redirect()->guest(route('login'));
        });

        // =================================================================
        // SUBSCRIPTION EXCEPTIONS - Graceful Handling
        // =================================================================

        // Handle QuotaExceededException (limit tugagan)
        $exceptions->render(function (\App\Exceptions\QuotaExceededException $e, Request $request) {
            // API so'rovlari uchun JSON qaytarish
            if ($request->is('api/*') || ($request->expectsJson() && !$request->hasHeader('X-Inertia'))) {
                return response()->json([
                    'success' => false,
                    'message' => $e->getMessage(),
                    'error_code' => 'QUOTA_EXCEEDED',
                    'limit_key' => $e->getLimitKey(),
                    'limit_label' => $e->getLimitLabel(),
                    'limit' => $e->getLimit(),
                    'current_usage' => $e->getCurrentUsage(),
                    'upgrade_required' => true,
                ], 403);
            }

            // Inertia so'rovlari uchun - flash xabar bilan qaytish
            if ($request->hasHeader('X-Inertia')) {
                return redirect()->back()->with([
                    'error' => $e->getMessage(),
                    'upgrade_required' => true,
                    'upgrade_data' => [
                        'type' => 'quota',
                        'limit_key' => $e->getLimitKey(),
                        'limit_label' => $e->getLimitLabel(),
                        'limit' => $e->getLimit(),
                        'current_usage' => $e->getCurrentUsage(),
                    ],
                ]);
            }

            // Oddiy web so'rovlar uchun
            return redirect()->back()->with([
                'error' => $e->getMessage(),
                'upgrade_required' => true,
            ]);
        });

        // Handle FeatureNotAvailableException (feature mavjud emas)
        $exceptions->render(function (\App\Exceptions\FeatureNotAvailableException $e, Request $request) {
            // API so'rovlari uchun JSON qaytarish
            if ($request->is('api/*') || ($request->expectsJson() && !$request->hasHeader('X-Inertia'))) {
                return response()->json([
                    'success' => false,
                    'message' => $e->getMessage(),
                    'error_code' => 'FEATURE_NOT_AVAILABLE',
                    'feature_key' => $e->getFeatureKey(),
                    'feature_label' => $e->getFeatureLabel(),
                    'upgrade_required' => true,
                ], 403);
            }

            // Inertia so'rovlari uchun - flash xabar bilan qaytish
            if ($request->hasHeader('X-Inertia')) {
                return redirect()->back()->with([
                    'error' => $e->getMessage(),
                    'upgrade_required' => true,
                    'upgrade_data' => [
                        'type' => 'feature',
                        'feature_key' => $e->getFeatureKey(),
                        'feature_label' => $e->getFeatureLabel(),
                    ],
                ]);
            }

            // Oddiy web so'rovlar uchun
            return redirect()->back()->with([
                'error' => $e->getMessage(),
                'upgrade_required' => true,
            ]);
        });

        // Handle NoActiveSubscriptionException (obuna yo'q)
        $exceptions->render(function (\App\Exceptions\NoActiveSubscriptionException $e, Request $request) {
            // API so'rovlari uchun JSON qaytarish
            if ($request->is('api/*') || ($request->expectsJson() && !$request->hasHeader('X-Inertia'))) {
                return response()->json([
                    'success' => false,
                    'message' => $e->getMessage(),
                    'error_code' => 'NO_ACTIVE_SUBSCRIPTION',
                    'upgrade_required' => true,
                ], 402);
            }

            // Inertia so'rovlari uchun - pricing sahifasiga yo'naltirish
            if ($request->hasHeader('X-Inertia')) {
                return redirect()->route('pricing')->with([
                    'warning' => $e->getMessage(),
                    'upgrade_required' => true,
                    'upgrade_data' => [
                        'type' => 'no_subscription',
                    ],
                ]);
            }

            // Oddiy web so'rovlar uchun
            return redirect()->route('pricing')->with([
                'warning' => $e->getMessage(),
                'upgrade_required' => true,
            ]);
        });

        // =================================================================

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
                if (! app()->environment('production')) {
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
                    'url' => preg_replace('/([?&])(token|api_key|password|secret|access_token|code)=[^&]*/i', '$1$2=***', $request->fullUrl()),
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
            // Subscription exceptions - bu normal UX flow
            \App\Exceptions\QuotaExceededException::class,
            \App\Exceptions\FeatureNotAvailableException::class,
            \App\Exceptions\NoActiveSubscriptionException::class,
        ]);

        // Throttle exception reporting to prevent log flooding
        $exceptions->throttle(function (\Throwable $e) {
            return \Illuminate\Cache\RateLimiting\Limit::perMinute(5)->by(
                get_class($e).'|'.$e->getFile().'|'.$e->getLine()
            );
        });
    })->create();
