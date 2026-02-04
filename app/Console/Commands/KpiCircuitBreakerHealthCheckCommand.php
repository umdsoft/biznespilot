<?php

namespace App\Console\Commands;

use App\Services\Integration\CircuitBreaker;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class KpiCircuitBreakerHealthCheckCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'kpi:circuit-breaker-health-check
                            {--service= : Specific service to check (instagram_api, facebook_api, pos_system)}
                            {--reset-on-success : Reset circuit to CLOSED if health check succeeds}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Perform health check on circuit breakers and optionally reset if service recovered';

    protected CircuitBreaker $circuitBreaker;

    public function __construct(CircuitBreaker $circuitBreaker)
    {
        parent::__construct();
        $this->circuitBreaker = $circuitBreaker;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $services = $this->option('service')
            ? [$this->option('service')]
            : ['instagram_api', 'facebook_api', 'pos_system'];

        $resetOnSuccess = $this->option('reset-on-success');

        $this->info('Running circuit breaker health checks...');
        $this->newLine();

        $results = [];

        foreach ($services as $service) {
            $this->line("Checking <fg=cyan>{$service}</>");

            $stats = $this->circuitBreaker->getStats($service);

            $this->table(
                ['Metric', 'Value'],
                [
                    ['State', $stats['state']],
                    ['Failure Count', $stats['failure_count']],
                    ['Success Count (Half-Open)', $stats['success_count']],
                    ['Time Since Opened', $stats['seconds_since_opened'] ?? 'N/A'],
                ]
            );

            // If circuit is OPEN, try a health check
            if ($stats['state'] === 'OPEN') {
                $this->warn('Circuit is OPEN. Performing health check...');

                $healthCheckResult = $this->performHealthCheck($service);

                if ($healthCheckResult) {
                    $this->info("✓ Health check PASSED for {$service}");

                    if ($resetOnSuccess) {
                        $this->circuitBreaker->reset($service);
                        $this->info('✓ Circuit RESET to CLOSED state');

                        Log::info('Circuit breaker reset via health check', [
                            'service' => $service,
                            'command' => 'kpi:circuit-breaker-health-check',
                        ]);
                    } else {
                        $this->comment('Use --reset-on-success to automatically reset the circuit');
                    }
                } else {
                    $this->error("✗ Health check FAILED for {$service}");
                }

                $results[$service] = $healthCheckResult ? 'HEALTHY' : 'UNHEALTHY';
            } else {
                $this->info("✓ Circuit is {$stats['state']} - no action needed");
                $results[$service] = $stats['state'];
            }

            $this->newLine();
        }

        // Summary
        $this->info('Summary:');
        $this->table(
            ['Service', 'Status'],
            collect($results)->map(fn ($status, $service) => [$service, $status])->toArray()
        );

        return Command::SUCCESS;
    }

    /**
     * Perform a lightweight health check on the service
     *
     * This sends a minimal API request to check if service is responding
     */
    protected function performHealthCheck(string $service): bool
    {
        try {
            switch ($service) {
                case 'instagram_api':
                    return $this->checkInstagramHealth();

                case 'facebook_api':
                    return $this->checkFacebookHealth();

                case 'pos_system':
                    return $this->checkPosHealth();

                default:
                    return false;
            }
        } catch (\Exception $e) {
            Log::error("Health check failed for {$service}", [
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Check Instagram API health
     */
    protected function checkInstagramHealth(): bool
    {
        // Simple health check: Try to verify API is reachable
        // This doesn't require a business_id, just checks if API responds

        $apiVersion = config('services.meta.api_version', 'v21.0');
        $url = "https://graph.instagram.com/{$apiVersion}/";

        try {
            $response = @file_get_contents($url);

            return $response !== false;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Check Facebook API health
     */
    protected function checkFacebookHealth(): bool
    {
        // Simple health check for Facebook Graph API
        $apiVersion = config('services.meta.api_version', 'v21.0');
        $url = "https://graph.facebook.com/{$apiVersion}/";

        try {
            $response = @file_get_contents($url);

            return $response !== false;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Check POS system health
     */
    protected function checkPosHealth(): bool
    {
        // For internal POS system, we can do a simple database connectivity check
        // or ping a health endpoint if your POS has one

        try {
            \DB::connection()->getPdo();

            return true;
        } catch (\Exception $e) {
            return false;
        }
    }
}
