<?php

namespace App\Console\Commands;

use App\Services\Integration\SyncMonitor;
use Illuminate\Console\Command;

class KpiHealthCheckCommand extends Command
{
    protected $signature = 'kpi:health-check
                            {--date= : Date to check (Y-m-d format, default: today)}';

    protected $description = 'Check KPI sync health status';

    public function handle(SyncMonitor $monitor): int
    {
        $date = $this->option('date') ?? now()->format('Y-m-d');

        $this->info("KPI Sync Health Check - {$date}");
        $this->line('═════════════════════════════════════════');

        $health = $monitor->getHealthStatus($date);

        if ($health['status'] === 'unknown') {
            $this->warn($health['message']);
            return Command::SUCCESS;
        }

        // Overall status
        $statusColor = match ($health['status']) {
            'healthy' => 'green',
            'warning' => 'yellow',
            'critical' => 'red',
            default => 'white',
        };

        $statusIcon = match ($health['status']) {
            'healthy' => '✓',
            'warning' => '⚠',
            'critical' => '✗',
            default => '?',
        };

        $this->newLine();
        $this->line("<fg={$statusColor}>{$statusIcon} Status: " . strtoupper($health['status']) . "</>");
        $this->newLine();

        // Metrics
        $metrics = $health['metrics'];
        $this->line("<fg=cyan>Businesses:</>");
        $this->line("  Total: {$metrics['total_businesses']}");
        $this->line("  Successful: <fg=green>{$metrics['successful']}</>");
        $this->line("  Failed: <fg=red>{$metrics['failed']}</>");
        $this->newLine();

        $this->line("<fg=cyan>Performance:</>");
        $successRateColor = $metrics['success_rate'] >= 95 ? 'green' : ($metrics['success_rate'] >= 80 ? 'yellow' : 'red');
        $this->line("  Success Rate: <fg={$successRateColor}>{$metrics['success_rate']}%</>");
        $this->line("  Total Duration: {$metrics['total_duration_seconds']}s");
        $this->line("  Avg per Business: {$metrics['average_duration_seconds']}s");
        $this->newLine();

        $this->line("<fg=cyan>Batches:</>");
        $this->line("  Total: {$metrics['total_batches']}");
        $this->line("  Processed: {$metrics['processed_batches']}");
        $this->newLine();

        // Thresholds
        $thresholds = $health['thresholds'];
        $this->line("<fg=cyan>Thresholds:</>");
        $this->line("  Success Rate Warning: {$thresholds['success_rate_warning']}%");
        $this->line("  Success Rate Critical: {$thresholds['success_rate_critical']}%");
        $this->line("  Avg Duration Warning: {$thresholds['avg_duration_warning']}s");
        $this->line("  Avg Duration Critical: {$thresholds['avg_duration_critical']}s");
        $this->newLine();

        // Recommendations
        if ($health['status'] === 'critical') {
            $this->error('CRITICAL: Immediate attention required!');
            $this->warn('Recommended actions:');
            $this->line('  1. Check logs: tail -f storage/logs/laravel.log');
            $this->line('  2. Check circuit breakers: php artisan kpi:circuit-breaker-stats --all');
            $this->line('  3. Check failed businesses: php artisan kpi:failed-businesses --date=' . $date);
        } elseif ($health['status'] === 'warning') {
            $this->warn('WARNING: Monitor closely');
            $this->line('Consider investigating failed businesses');
        } else {
            $this->info('System is healthy ✓');
        }

        $this->line('═════════════════════════════════════════');

        return Command::SUCCESS;
    }
}
