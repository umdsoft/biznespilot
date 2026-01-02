<?php

namespace App\Console\Commands;

use App\Services\Integration\CircuitBreaker;
use Illuminate\Console\Command;

class KpiCircuitBreakerResetCommand extends Command
{
    protected $signature = 'kpi:circuit-breaker-reset
                            {--service= : Specific service to reset (instagram_api, facebook_api, pos_system)}
                            {--business-id= : Specific business ID}
                            {--all : Reset all services}';

    protected $description = 'Manually reset circuit breaker to CLOSED state';

    public function handle(CircuitBreaker $circuitBreaker): int
    {
        $service = $this->option('service');
        $businessId = $this->option('business-id');
        $all = $this->option('all');

        if (!$service && !$all) {
            $this->error('Please specify --service or --all');
            return Command::FAILURE;
        }

        $services = $all
            ? ['instagram_api', 'facebook_api', 'pos_system']
            : [$service];

        $this->info('Resetting circuit breaker(s)...');

        foreach ($services as $svc) {
            $circuitBreaker->reset($svc, $businessId);

            $scope = $businessId ? "business {$businessId}" : "global";
            $this->line("<fg=green>✓</> Reset {$svc} ({$scope})");
        }

        $this->newLine();
        $this->info('Circuit breaker reset complete');

        return Command::SUCCESS;
    }
}
