<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Redis;

class HealthCheckController extends Controller
{
    /**
     * Basic health check - returns 200 if app is running.
     */
    public function ping(): JsonResponse
    {
        return response()->json([
            'status' => 'ok',
            'timestamp' => now()->toIso8601String(),
        ]);
    }

    /**
     * Detailed health check for monitoring systems.
     */
    public function status(): JsonResponse
    {
        $checks = [
            'app' => $this->checkApp(),
            'database' => $this->checkDatabase(),
            'cache' => $this->checkCache(),
            'redis' => $this->checkRedis(),
            'queue' => $this->checkQueue(),
            'storage' => $this->checkStorage(),
        ];

        $healthy = collect($checks)->every(fn ($check) => $check['status'] === 'ok');

        return response()->json([
            'status' => $healthy ? 'healthy' : 'unhealthy',
            'timestamp' => now()->toIso8601String(),
            'version' => config('app.version', '1.0.0'),
            'environment' => app()->environment(),
            'checks' => $checks,
        ], $healthy ? 200 : 503);
    }

    /**
     * Ready check for kubernetes/load balancers.
     */
    public function ready(): JsonResponse
    {
        $dbOk = $this->checkDatabase()['status'] === 'ok';
        $cacheOk = $this->checkCache()['status'] === 'ok';

        if ($dbOk && $cacheOk) {
            return response()->json(['status' => 'ready'], 200);
        }

        return response()->json(['status' => 'not_ready'], 503);
    }

    /**
     * Liveness check for kubernetes.
     */
    public function live(): JsonResponse
    {
        return response()->json(['status' => 'alive'], 200);
    }

    private function checkApp(): array
    {
        return [
            'status' => 'ok',
            'name' => config('app.name'),
            'environment' => app()->environment(),
            'debug' => config('app.debug'),
            'php_version' => PHP_VERSION,
            'laravel_version' => app()->version(),
        ];
    }

    private function checkDatabase(): array
    {
        try {
            $start = microtime(true);
            DB::connection()->getPdo();
            DB::select('SELECT 1');
            $latency = round((microtime(true) - $start) * 1000, 2);

            return [
                'status' => 'ok',
                'driver' => config('database.default'),
                'latency_ms' => $latency,
            ];
        } catch (\Throwable $e) {
            Log::channel('critical')->error('Database health check failed', [
                'error' => $e->getMessage(),
            ]);

            return [
                'status' => 'error',
                'error' => app()->environment('production')
                    ? 'Database connection failed'
                    : $e->getMessage(),
            ];
        }
    }

    private function checkCache(): array
    {
        try {
            $key = 'health_check_'.uniqid();
            $start = microtime(true);

            Cache::put($key, 'test', 10);
            $value = Cache::get($key);
            Cache::forget($key);

            $latency = round((microtime(true) - $start) * 1000, 2);

            if ($value !== 'test') {
                throw new \Exception('Cache read/write verification failed');
            }

            return [
                'status' => 'ok',
                'driver' => config('cache.default'),
                'latency_ms' => $latency,
            ];
        } catch (\Throwable $e) {
            return [
                'status' => 'error',
                'driver' => config('cache.default'),
                'error' => app()->environment('production')
                    ? 'Cache connection failed'
                    : $e->getMessage(),
            ];
        }
    }

    private function checkRedis(): array
    {
        if (config('cache.default') !== 'redis' && config('queue.default') !== 'redis') {
            return [
                'status' => 'skipped',
                'reason' => 'Redis not configured',
            ];
        }

        try {
            $start = microtime(true);
            Redis::ping();
            $latency = round((microtime(true) - $start) * 1000, 2);

            return [
                'status' => 'ok',
                'latency_ms' => $latency,
            ];
        } catch (\Throwable $e) {
            return [
                'status' => 'error',
                'error' => app()->environment('production')
                    ? 'Redis connection failed'
                    : $e->getMessage(),
            ];
        }
    }

    private function checkQueue(): array
    {
        try {
            $driver = config('queue.default');

            // For database queue, check the jobs table
            if ($driver === 'database') {
                $pendingJobs = DB::table('jobs')->count();
                $failedJobs = DB::table('failed_jobs')->count();

                return [
                    'status' => 'ok',
                    'driver' => $driver,
                    'pending_jobs' => $pendingJobs,
                    'failed_jobs' => $failedJobs,
                ];
            }

            // For redis queue
            if ($driver === 'redis') {
                $queueSize = Redis::llen('queues:default');

                return [
                    'status' => 'ok',
                    'driver' => $driver,
                    'queue_size' => $queueSize,
                ];
            }

            return [
                'status' => 'ok',
                'driver' => $driver,
            ];
        } catch (\Throwable $e) {
            return [
                'status' => 'error',
                'error' => app()->environment('production')
                    ? 'Queue check failed'
                    : $e->getMessage(),
            ];
        }
    }

    private function checkStorage(): array
    {
        try {
            $testFile = storage_path('app/health_check_test.tmp');
            $start = microtime(true);

            file_put_contents($testFile, 'test');
            $content = file_get_contents($testFile);
            unlink($testFile);

            $latency = round((microtime(true) - $start) * 1000, 2);

            if ($content !== 'test') {
                throw new \Exception('Storage read/write verification failed');
            }

            // Check disk space
            $freeSpace = disk_free_space(storage_path());
            $totalSpace = disk_total_space(storage_path());
            $usedPercent = round(100 - ($freeSpace / $totalSpace * 100), 2);

            return [
                'status' => $usedPercent > 90 ? 'warning' : 'ok',
                'latency_ms' => $latency,
                'disk_used_percent' => $usedPercent,
                'free_space_gb' => round($freeSpace / 1024 / 1024 / 1024, 2),
            ];
        } catch (\Throwable $e) {
            return [
                'status' => 'error',
                'error' => app()->environment('production')
                    ? 'Storage check failed'
                    : $e->getMessage(),
            ];
        }
    }
}
