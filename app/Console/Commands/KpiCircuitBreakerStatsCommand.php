<?php

namespace App\Console\Commands;

use App\Services\Integration\CircuitBreaker;
use Illuminate\Console\Command;

class KpiCircuitBreakerStatsCommand extends Command
{
    protected $signature = 'kpi:circuit-breaker-stats
                            {--service= : Specific service (instagram_api, facebook_api, pos_system)}
                            {--business-id= : Specific business ID}';

    protected $description = 'Display circuit breaker statistics for KPI sync integrations';

    public function handle(CircuitBreaker $circuitBreaker): int
    {
        $service = $this->option('service');
        $businessId = $this->option('business-id');

        $services = $service
            ? [$service]
            : ['instagram_api', 'facebook_api', 'pos_system'];

        $this->info('Circuit Breaker Statistics');
        $this->line('─────────────────────────────────────────');

        foreach ($services as $svc) {
            $stats = $circuitBreaker->getStats($svc, $businessId);

            $this->newLine();
            $this->line("<fg=cyan>Service:</> {$stats['service']}");

            if ($businessId) {
                $this->line("<fg=cyan>Business ID:</> {$businessId}");
            } else {
                $this->line('<fg=cyan>Scope:</> Global');
            }

            // State with color
            $stateColor = match ($stats['state']) {
                'closed' => 'green',
                'half_open' => 'yellow',
                'open' => 'red',
                default => 'white',
            };
            $this->line("<fg=cyan>State:</> <fg={$stateColor}>{$stats['state']}</>");

            $this->line("<fg=cyan>Failure Count:</> {$stats['failure_count']} / {$stats['failure_threshold']}");
            $this->line("<fg=cyan>Success Count:</> {$stats['success_count']} / {$stats['success_threshold']}");
            $this->line("<fg=cyan>Timeout:</> {$stats['timeout_seconds']} seconds");

            if (isset($stats['opened_at'])) {
                $this->line("<fg=cyan>Opened At:</> {$stats['opened_at']}");
                $this->line("<fg=cyan>Elapsed:</> {$stats['elapsed_seconds']}s");
                $this->line("<fg=cyan>Remaining:</> {$stats['remaining_seconds']}s");
            }

            // Health indicator
            if ($stats['state'] === 'closed') {
                $this->line('<fg=green>✓ Service is healthy</>');
            } elseif ($stats['state'] === 'half_open') {
                $this->line('<fg=yellow>⚠ Service is recovering</>');
            } else {
                $this->line('<fg=red>✗ Service is unavailable</>');
            }

            $this->line('─────────────────────────────────────────');
        }

        return Command::SUCCESS;
    }
}
