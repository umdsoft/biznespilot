<?php

namespace App\Providers;

use App\Services\Algorithm\DiagnosticAlgorithmService;
use App\Services\Algorithm\Performance\AlgorithmCacheManager;
use App\Services\Algorithm\Performance\AlgorithmQueueManager;
use App\Services\Algorithm\Performance\AsyncAlgorithmRunner;
use App\Services\Algorithm\Performance\OptimizedDiagnosticService;
use App\Services\Algorithm\Performance\RateLimiter;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Support\Facades\RateLimiter as FacadesRateLimiter;
use Illuminate\Support\ServiceProvider;

/**
 * Algorithm Service Provider
 *
 * Registers all algorithm services with proper dependency injection.
 * Configures rate limiting and queue settings.
 *
 * @version 1.0.0
 */
class AlgorithmServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        // Register base algorithm service
        $this->app->singleton(DiagnosticAlgorithmService::class, function ($app) {
            return new DiagnosticAlgorithmService;
        });

        // Register cache manager
        $this->app->singleton(AlgorithmCacheManager::class, function ($app) {
            return new AlgorithmCacheManager;
        });

        // Register async runner
        $this->app->singleton(AsyncAlgorithmRunner::class, function ($app) {
            $runner = new AsyncAlgorithmRunner;
            $runner->setMaxParallel(config('algorithm.max_parallel', 10));
            $runner->setTimeout(config('algorithm.timeout', 30));

            return $runner;
        });

        // Register rate limiter
        $this->app->singleton(RateLimiter::class, function ($app) {
            return new RateLimiter;
        });

        // Register queue manager
        $this->app->singleton(AlgorithmQueueManager::class, function ($app) {
            return new AlgorithmQueueManager;
        });

        // Register optimized service with all dependencies
        $this->app->singleton(OptimizedDiagnosticService::class, function ($app) {
            return new OptimizedDiagnosticService(
                $app->make(DiagnosticAlgorithmService::class),
                $app->make(AlgorithmCacheManager::class),
                $app->make(AsyncAlgorithmRunner::class),
                $app->make(RateLimiter::class),
                $app->make(AlgorithmQueueManager::class)
            );
        });

        // Alias for easier access
        $this->app->alias(OptimizedDiagnosticService::class, 'algorithm.diagnostic');
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Configure rate limiters for queues
        $this->configureRateLimiters();

        // Publish configuration
        $this->publishes([
            __DIR__.'/../../config/algorithm.php' => config_path('algorithm.php'),
        ], 'algorithm-config');

        // Register terminating callback to clear request cache
        $this->app->terminating(function () {
            AlgorithmCacheManager::clearRequestCache();
        });
    }

    /**
     * Configure rate limiters for algorithm jobs
     */
    protected function configureRateLimiters(): void
    {
        // Diagnostic rate limiter - 100 per minute
        FacadesRateLimiter::for('algorithm-diagnostics', function ($job) {
            return Limit::perMinute(100)
                ->by($job->business->id ?? 'global');
        });

        // Batch rate limiter - 10 per minute
        FacadesRateLimiter::for('algorithm-batch', function ($job) {
            return Limit::perMinute(10)
                ->by('batch');
        });

        // Single algorithm rate limiter - 500 per minute
        FacadesRateLimiter::for('algorithm-single', function ($job) {
            return Limit::perMinute(500)
                ->by($job->business->id ?? 'global');
        });
    }
}
