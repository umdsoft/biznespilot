<?php

namespace App\Console\Commands;

use App\Services\Integration\RateLimiter;
use Illuminate\Console\Command;

class KpiRateLimitStatsCommand extends Command
{
    protected $signature = 'kpi:rate-limit-stats
                            {--service= : Specific service (instagram_api, facebook_api, pos_system)}
                            {--business-id= : Specific business ID}';

    protected $description = 'Display rate limit statistics for KPI sync integrations';

    public function handle(RateLimiter $rateLimiter): int
    {
        $service = $this->option('service');
        $businessId = $this->option('business-id');

        $services = $service
            ? [$service]
            : ['instagram_api', 'facebook_api', 'pos_system'];

        $this->info('Rate Limit Statistics');
        $this->line('─────────────────────────────────────────');

        foreach ($services as $svc) {
            $stats = $rateLimiter->getStats($svc, $businessId);

            $this->newLine();
            $this->line("<fg=cyan>Service:</> {$stats['integration']}");

            if ($businessId) {
                $this->line("<fg=cyan>Business ID:</> {$businessId}");
            } else {
                $this->line('<fg=cyan>Scope:</> Global');
            }

            $this->line("<fg=cyan>Limit:</> {$stats['limit']} requests per {$stats['window_seconds']}s");
            $this->line("<fg=cyan>Used:</> {$stats['used']}");
            $this->line("<fg=cyan>Remaining:</> {$stats['remaining']}");

            // Usage bar
            $percentage = $stats['usage_percentage'];
            $barLength = 50;
            $filled = (int) round(($percentage / 100) * $barLength);
            $empty = $barLength - $filled;

            $barColor = match (true) {
                $percentage >= 90 => 'red',
                $percentage >= 75 => 'yellow',
                default => 'green',
            };

            $bar = str_repeat('█', $filled).str_repeat('░', $empty);
            $this->line("<fg=cyan>Usage:</> <fg={$barColor}>{$bar}</> {$percentage}%");

            // Status
            if ($percentage >= 90) {
                $this->line('<fg=red>⚠ Critical: Approaching rate limit</>');
            } elseif ($percentage >= 75) {
                $this->line('<fg=yellow>⚠ Warning: High usage</>');
            } else {
                $this->line('<fg=green>✓ Normal usage</>');
            }

            $this->line('─────────────────────────────────────────');
        }

        return Command::SUCCESS;
    }
}
