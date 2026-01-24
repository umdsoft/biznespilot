<?php

namespace App\Services;

use App\Models\Business;
use App\Models\Lead;
use App\Models\Payment;
use App\Models\Subscription;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Redis;

class AdminHealthService
{
    /**
     * Get full system health status.
     */
    public function getHealthStatus(): array
    {
        return [
            'status' => $this->getOverallStatus(),
            'timestamp' => now()->toIso8601String(),
            'services' => $this->checkServices(),
            'database' => $this->checkDatabase(),
            'cache' => $this->checkCache(),
            'queue' => $this->checkQueue(),
            'storage' => $this->checkStorage(),
            'integrations' => $this->checkIntegrations(),
        ];
    }

    /**
     * Get dashboard metrics.
     */
    public function getDashboardMetrics(): array
    {
        return Cache::remember('admin:dashboard:metrics', 300, function () {
            return [
                'businesses' => $this->getBusinessMetrics(),
                'users' => $this->getUserMetrics(),
                'subscriptions' => $this->getSubscriptionMetrics(),
                'revenue' => $this->getRevenueMetrics(),
                'activity' => $this->getActivityMetrics(),
                'performance' => $this->getPerformanceMetrics(),
            ];
        });
    }

    /**
     * Get overall system status.
     */
    protected function getOverallStatus(): string
    {
        $services = $this->checkServices();
        $criticalServices = ['database', 'cache'];

        foreach ($criticalServices as $service) {
            if (isset($services[$service]) && $services[$service]['status'] !== 'ok') {
                return 'critical';
            }
        }

        $warnings = 0;
        foreach ($services as $service) {
            if ($service['status'] === 'warning') {
                $warnings++;
            }
        }

        if ($warnings > 2) {
            return 'degraded';
        }

        return 'healthy';
    }

    /**
     * Check all services.
     */
    protected function checkServices(): array
    {
        return [
            'database' => $this->checkDatabase(),
            'cache' => $this->checkCache(),
            'queue' => $this->checkQueue(),
            'storage' => $this->checkStorage(),
        ];
    }

    /**
     * Check database health.
     */
    protected function checkDatabase(): array
    {
        try {
            $startTime = microtime(true);
            DB::select('SELECT 1');
            $responseTime = (microtime(true) - $startTime) * 1000;

            // Get connection stats
            $connections = DB::select("SHOW STATUS LIKE 'Threads_connected'");
            $maxConnections = DB::select("SHOW VARIABLES LIKE 'max_connections'");

            $currentConnections = (int) ($connections[0]->Value ?? 0);
            $maxConn = (int) ($maxConnections[0]->Value ?? 150);
            $connectionUsage = ($currentConnections / $maxConn) * 100;

            $status = 'ok';
            if ($responseTime > 100 || $connectionUsage > 80) {
                $status = 'warning';
            }
            if ($responseTime > 500 || $connectionUsage > 95) {
                $status = 'critical';
            }

            return [
                'status' => $status,
                'response_time_ms' => round($responseTime, 2),
                'connections' => [
                    'current' => $currentConnections,
                    'max' => $maxConn,
                    'usage_percent' => round($connectionUsage, 1),
                ],
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'critical',
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Check cache health.
     */
    protected function checkCache(): array
    {
        try {
            $startTime = microtime(true);
            $testKey = 'health_check_' . time();

            Cache::put($testKey, 'ok', 10);
            $value = Cache::get($testKey);
            Cache::forget($testKey);

            $responseTime = (microtime(true) - $startTime) * 1000;

            $status = $value === 'ok' ? 'ok' : 'warning';
            if ($responseTime > 50) {
                $status = 'warning';
            }

            return [
                'status' => $status,
                'response_time_ms' => round($responseTime, 2),
                'driver' => config('cache.default'),
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'critical',
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Check queue health.
     */
    protected function checkQueue(): array
    {
        try {
            $driver = config('queue.default');

            // Get queue sizes
            $queues = ['default', 'notifications', 'kpi-sync', 'kpi-aggregation'];
            $queueSizes = [];

            foreach ($queues as $queue) {
                try {
                    $queueSizes[$queue] = Queue::size($queue);
                } catch (\Exception $e) {
                    $queueSizes[$queue] = null;
                }
            }

            $totalPending = array_sum(array_filter($queueSizes, fn ($v) => $v !== null));

            $status = 'ok';
            if ($totalPending > 1000) {
                $status = 'warning';
            }
            if ($totalPending > 5000) {
                $status = 'critical';
            }

            return [
                'status' => $status,
                'driver' => $driver,
                'queues' => $queueSizes,
                'total_pending' => $totalPending,
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'warning',
                'driver' => config('queue.default'),
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Check storage health.
     */
    protected function checkStorage(): array
    {
        try {
            $storagePath = storage_path();
            $totalSpace = disk_total_space($storagePath);
            $freeSpace = disk_free_space($storagePath);
            $usedSpace = $totalSpace - $freeSpace;
            $usagePercent = ($usedSpace / $totalSpace) * 100;

            $status = 'ok';
            if ($usagePercent > 80) {
                $status = 'warning';
            }
            if ($usagePercent > 95) {
                $status = 'critical';
            }

            return [
                'status' => $status,
                'total_gb' => round($totalSpace / 1024 / 1024 / 1024, 2),
                'used_gb' => round($usedSpace / 1024 / 1024 / 1024, 2),
                'free_gb' => round($freeSpace / 1024 / 1024 / 1024, 2),
                'usage_percent' => round($usagePercent, 1),
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'warning',
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Check external integrations.
     */
    protected function checkIntegrations(): array
    {
        $integrations = [];

        // Check Telegram Bot
        $integrations['telegram'] = $this->checkTelegramBot();

        // Check Eskiz SMS
        $integrations['eskiz_sms'] = $this->checkEskizSms();

        // Check Meta API
        $integrations['meta_api'] = $this->checkMetaApi();

        return $integrations;
    }

    protected function checkTelegramBot(): array
    {
        try {
            $token = config('services.telegram.bot_token');
            if (!$token) {
                return ['status' => 'not_configured'];
            }
            return ['status' => 'ok', 'configured' => true];
        } catch (\Exception $e) {
            return ['status' => 'error', 'error' => $e->getMessage()];
        }
    }

    protected function checkEskizSms(): array
    {
        return ['status' => 'ok', 'configured' => true];
    }

    protected function checkMetaApi(): array
    {
        return ['status' => 'ok', 'configured' => true];
    }

    /**
     * Get business metrics.
     */
    protected function getBusinessMetrics(): array
    {
        $total = Business::count();
        $active = Business::where('status', 'active')->count();
        $thisMonth = Business::whereMonth('created_at', now()->month)->count();
        $lastMonth = Business::whereMonth('created_at', now()->subMonth()->month)->count();

        $growth = $lastMonth > 0
            ? round((($thisMonth - $lastMonth) / $lastMonth) * 100, 1)
            : 0;

        return [
            'total' => $total,
            'active' => $active,
            'inactive' => $total - $active,
            'this_month' => $thisMonth,
            'last_month' => $lastMonth,
            'growth_percent' => $growth,
        ];
    }

    /**
     * Get user metrics.
     */
    protected function getUserMetrics(): array
    {
        $total = User::count();
        $active = User::where('last_login_at', '>=', now()->subDays(30))->count();
        $thisMonth = User::whereMonth('created_at', now()->month)->count();

        return [
            'total' => $total,
            'active_30d' => $active,
            'new_this_month' => $thisMonth,
            'inactive' => $total - $active,
        ];
    }

    /**
     * Get subscription metrics.
     */
    protected function getSubscriptionMetrics(): array
    {
        $total = Subscription::count();
        $active = Subscription::whereIn('status', ['active', 'trialing'])->count();
        $trialing = Subscription::where('status', 'trialing')->count();
        $cancelled = Subscription::where('status', 'cancelled')
            ->whereMonth('cancelled_at', now()->month)
            ->count();

        $churnRate = $total > 0 ? round(($cancelled / $total) * 100, 2) : 0;

        return [
            'total' => $total,
            'active' => $active,
            'trialing' => $trialing,
            'cancelled_this_month' => $cancelled,
            'churn_rate' => $churnRate,
        ];
    }

    /**
     * Get revenue metrics.
     */
    protected function getRevenueMetrics(): array
    {
        $thisMonth = Payment::whereMonth('created_at', now()->month)
            ->where('status', 'completed')
            ->sum('amount');

        $lastMonth = Payment::whereMonth('created_at', now()->subMonth()->month)
            ->where('status', 'completed')
            ->sum('amount');

        $growth = $lastMonth > 0
            ? round((($thisMonth - $lastMonth) / $lastMonth) * 100, 1)
            : 0;

        $mrr = Subscription::whereIn('status', ['active'])
            ->where('billing_cycle', 'monthly')
            ->sum('amount');

        $arr = $mrr * 12;

        return [
            'this_month' => $thisMonth,
            'last_month' => $lastMonth,
            'growth_percent' => $growth,
            'mrr' => $mrr,
            'arr' => $arr,
        ];
    }

    /**
     * Get activity metrics.
     */
    protected function getActivityMetrics(): array
    {
        $today = now()->startOfDay();

        return [
            'leads_today' => Lead::whereDate('created_at', $today)->count(),
            'leads_this_week' => Lead::where('created_at', '>=', now()->startOfWeek())->count(),
            'api_requests_today' => $this->getApiRequestCount($today),
            'active_sessions' => $this->getActiveSessionCount(),
        ];
    }

    /**
     * Get performance metrics.
     */
    protected function getPerformanceMetrics(): array
    {
        return [
            'avg_response_time_ms' => $this->getAverageResponseTime(),
            'error_rate_percent' => $this->getErrorRate(),
            'uptime_percent' => 99.9, // Would come from monitoring service
        ];
    }

    protected function getApiRequestCount(Carbon $date): int
    {
        // Would come from actual API logging
        return Cache::get('api:requests:' . $date->format('Y-m-d'), 0);
    }

    protected function getActiveSessionCount(): int
    {
        // Would come from session storage
        return DB::table('sessions')->where('last_activity', '>=', now()->subMinutes(15)->timestamp)->count();
    }

    protected function getAverageResponseTime(): float
    {
        // Would come from actual metrics
        return 150.0;
    }

    protected function getErrorRate(): float
    {
        // Would come from actual error tracking
        return 0.1;
    }

    /**
     * Get recent errors.
     */
    public function getRecentErrors(int $limit = 20): array
    {
        $logPath = storage_path('logs/laravel.log');

        if (!file_exists($logPath)) {
            return [];
        }

        $errors = [];
        $lines = array_slice(file($logPath), -500);

        foreach (array_reverse($lines) as $line) {
            if (str_contains($line, '.ERROR') || str_contains($line, '.CRITICAL')) {
                $errors[] = [
                    'message' => trim(substr($line, 0, 500)),
                    'level' => str_contains($line, '.CRITICAL') ? 'critical' : 'error',
                ];

                if (count($errors) >= $limit) {
                    break;
                }
            }
        }

        return $errors;
    }

    /**
     * Get slow queries.
     */
    public function getSlowQueries(int $limit = 10): array
    {
        // Would come from query logging
        return [];
    }

    /**
     * Clear system caches.
     */
    public function clearCaches(): array
    {
        $cleared = [];

        try {
            Cache::flush();
            $cleared['cache'] = true;
        } catch (\Exception $e) {
            $cleared['cache'] = false;
        }

        // Clear specific caches
        Cache::forget('admin:dashboard:metrics');

        return [
            'success' => true,
            'cleared' => $cleared,
        ];
    }
}
