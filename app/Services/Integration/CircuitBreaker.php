<?php

namespace App\Services\Integration;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Circuit Breaker Pattern Implementation
 *
 * Prevents cascading failures by stopping requests to failing external services
 * and allowing them time to recover.
 *
 * States:
 * - CLOSED: Normal operation, requests pass through
 * - OPEN: Service is failing, requests are blocked
 * - HALF_OPEN: Testing if service has recovered
 */
class CircuitBreaker
{
    /**
     * Circuit states
     */
    public const STATE_CLOSED = 'closed';
    public const STATE_OPEN = 'open';
    public const STATE_HALF_OPEN = 'half_open';

    /**
     * Configuration from config/kpi_sync.php
     */
    protected bool $enabled;
    protected int $failureThreshold;
    protected int $timeout;
    protected int $successThreshold;

    public function __construct()
    {
        $config = config('kpi_sync.circuit_breaker', []);
        $this->enabled = $config['enabled'] ?? true;
        $this->failureThreshold = $config['failure_threshold'] ?? 5;
        $this->timeout = $config['timeout'] ?? 300; // 5 minutes
        $this->successThreshold = $config['success_threshold'] ?? 3;
    }

    /**
     * Execute a callback with circuit breaker protection
     *
     * @param string $service Service identifier (instagram_api, facebook_api, pos_system)
     * @param callable $callback Function to execute
     * @param int|string|null $businessId Optional business ID for per-business tracking
     * @return mixed Result of callback
     * @throws \Exception If circuit is open or callback fails
     */
    public function execute(string $service, callable $callback, int|string|null $businessId = null)
    {
        if (!$this->enabled) {
            return $callback();
        }

        $state = $this->getState($service, $businessId);

        // If circuit is open, check if timeout has elapsed
        if ($state === self::STATE_OPEN) {
            if ($this->shouldAttemptReset($service, $businessId)) {
                $this->setState($service, self::STATE_HALF_OPEN, $businessId);
                Log::info("Circuit breaker entering HALF_OPEN state", [
                    'service' => $service,
                    'business_id' => $businessId,
                ]);
            } else {
                throw new \Exception("Circuit breaker is OPEN for {$service}. Service temporarily unavailable.");
            }
        }

        try {
            $result = $callback();
            $this->recordSuccess($service, $businessId);
            return $result;
        } catch (\Exception $e) {
            $this->recordFailure($service, $businessId);
            throw $e;
        }
    }

    /**
     * Record a successful request
     *
     * @param string $service Service identifier
     * @param int|string|null $businessId Optional business ID
     * @return void
     */
    public function recordSuccess(string $service, int|string|null $businessId = null): void
    {
        $state = $this->getState($service, $businessId);
        $successKey = $this->getSuccessCountKey($service, $businessId);

        if ($state === self::STATE_HALF_OPEN) {
            // Increment success counter in half-open state
            $successCount = Cache::increment($successKey);

            if ($successCount >= $this->successThreshold) {
                // Service has recovered, close the circuit
                $this->setState($service, self::STATE_CLOSED, $businessId);
                $this->resetCounters($service, $businessId);

                Log::info("Circuit breaker CLOSED - service recovered", [
                    'service' => $service,
                    'business_id' => $businessId,
                    'success_count' => $successCount,
                ]);
            }
        } elseif ($state === self::STATE_CLOSED) {
            // Reset failure counter on success
            $this->resetFailureCount($service, $businessId);
        }
    }

    /**
     * Record a failed request
     *
     * @param string $service Service identifier
     * @param int|string|null $businessId Optional business ID
     * @return void
     */
    public function recordFailure(string $service, int|string|null $businessId = null): void
    {
        $state = $this->getState($service, $businessId);
        $failureKey = $this->getFailureCountKey($service, $businessId);

        if ($state === self::STATE_HALF_OPEN) {
            // Single failure in half-open state reopens the circuit
            $this->setState($service, self::STATE_OPEN, $businessId);
            $this->resetCounters($service, $businessId);

            Log::warning("Circuit breaker reopened from HALF_OPEN state", [
                'service' => $service,
                'business_id' => $businessId,
            ]);
            return;
        }

        // Increment failure counter
        $failureCount = Cache::add($failureKey, 0, 3600)
            ? 1
            : Cache::increment($failureKey);

        if ($failureCount === 1) {
            Cache::put($failureKey, 1, 3600);
        }

        // Open circuit if failure threshold exceeded
        if ($failureCount >= $this->failureThreshold) {
            $this->setState($service, self::STATE_OPEN, $businessId);

            Log::critical("Circuit breaker OPENED - threshold exceeded", [
                'service' => $service,
                'business_id' => $businessId,
                'failure_count' => $failureCount,
                'threshold' => $this->failureThreshold,
            ]);
        } else {
            Log::warning("Circuit breaker failure recorded", [
                'service' => $service,
                'business_id' => $businessId,
                'failure_count' => $failureCount,
                'threshold' => $this->failureThreshold,
            ]);
        }
    }

    /**
     * Get current circuit state
     *
     * @param string $service Service identifier
     * @param int|string|null $businessId Optional business ID
     * @return string Circuit state
     */
    public function getState(string $service, int|string|null $businessId = null): string
    {
        $key = $this->getStateKey($service, $businessId);
        return Cache::get($key, self::STATE_CLOSED);
    }

    /**
     * Set circuit state
     *
     * @param string $service Service identifier
     * @param string $state New state
     * @param int|string|null $businessId Optional business ID
     * @return void
     */
    protected function setState(string $service, string $state, int|string|null $businessId = null): void
    {
        $key = $this->getStateKey($service, $businessId);
        Cache::put($key, $state, 3600); // 1 hour

        // Record state change timestamp
        if ($state === self::STATE_OPEN) {
            $openedAtKey = $this->getOpenedAtKey($service, $businessId);
            Cache::put($openedAtKey, now()->timestamp, 3600);
        }
    }

    /**
     * Check if circuit should attempt reset
     *
     * @param string $service Service identifier
     * @param int|string|null $businessId Optional business ID
     * @return bool True if timeout has elapsed
     */
    protected function shouldAttemptReset(string $service, int|string|null $businessId = null): bool
    {
        $openedAtKey = $this->getOpenedAtKey($service, $businessId);
        $openedAt = Cache::get($openedAtKey);

        if (!$openedAt) {
            return true; // No record of when it opened, allow reset
        }

        $elapsedSeconds = now()->timestamp - $openedAt;
        return $elapsedSeconds >= $this->timeout;
    }

    /**
     * Reset all counters
     *
     * @param string $service Service identifier
     * @param int|string|null $businessId Optional business ID
     * @return void
     */
    protected function resetCounters(string $service, int|string|null $businessId = null): void
    {
        Cache::forget($this->getFailureCountKey($service, $businessId));
        Cache::forget($this->getSuccessCountKey($service, $businessId));
        Cache::forget($this->getOpenedAtKey($service, $businessId));
    }

    /**
     * Reset failure counter
     *
     * @param string $service Service identifier
     * @param int|string|null $businessId Optional business ID
     * @return void
     */
    protected function resetFailureCount(string $service, int|string|null $businessId = null): void
    {
        Cache::forget($this->getFailureCountKey($service, $businessId));
    }

    /**
     * Force reset circuit to closed state
     *
     * @param string $service Service identifier
     * @param int|string|null $businessId Optional business ID
     * @return void
     */
    public function reset(string $service, int|string|null $businessId = null): void
    {
        $this->setState($service, self::STATE_CLOSED, $businessId);
        $this->resetCounters($service, $businessId);

        Log::info("Circuit breaker manually reset", [
            'service' => $service,
            'business_id' => $businessId,
        ]);
    }

    /**
     * Get circuit breaker statistics
     *
     * @param string $service Service identifier
     * @param int|string|null $businessId Optional business ID
     * @return array Statistics
     */
    public function getStats(string $service, int|string|null $businessId = null): array
    {
        $state = $this->getState($service, $businessId);
        $failureCount = Cache::get($this->getFailureCountKey($service, $businessId), 0);
        $successCount = Cache::get($this->getSuccessCountKey($service, $businessId), 0);
        $openedAt = Cache::get($this->getOpenedAtKey($service, $businessId));

        $stats = [
            'service' => $service,
            'business_id' => $businessId,
            'state' => $state,
            'failure_count' => $failureCount,
            'success_count' => $successCount,
            'failure_threshold' => $this->failureThreshold,
            'success_threshold' => $this->successThreshold,
            'timeout_seconds' => $this->timeout,
        ];

        if ($openedAt && $state === self::STATE_OPEN) {
            $elapsedSeconds = now()->timestamp - $openedAt;
            $remainingSeconds = max(0, $this->timeout - $elapsedSeconds);

            $stats['opened_at'] = date('Y-m-d H:i:s', $openedAt);
            $stats['elapsed_seconds'] = $elapsedSeconds;
            $stats['remaining_seconds'] = $remainingSeconds;
        }

        return $stats;
    }

    /**
     * Cache key generators
     */
    /**
     * Sanitize cache key component to prevent cache poisoning attacks.
     * Removes colons and other special characters that could break cache key structure.
     */
    protected function sanitizeCacheKeyComponent(string $value): string
    {
        // Only allow alphanumeric, underscore, and dash
        return preg_replace('/[^a-zA-Z0-9_-]/', '_', $value);
    }

    protected function getStateKey(string $service, int|string|null $businessId = null): string
    {
        $sanitizedService = $this->sanitizeCacheKeyComponent($service);
        if ($businessId) {
            $sanitizedBusinessId = $this->sanitizeCacheKeyComponent((string)$businessId);
            return "circuit_breaker:{$sanitizedService}:business_{$sanitizedBusinessId}:state";
        }
        return "circuit_breaker:{$sanitizedService}:global:state";
    }

    protected function getFailureCountKey(string $service, int|string|null $businessId = null): string
    {
        $sanitizedService = $this->sanitizeCacheKeyComponent($service);
        if ($businessId) {
            $sanitizedBusinessId = $this->sanitizeCacheKeyComponent((string)$businessId);
            return "circuit_breaker:{$sanitizedService}:business_{$sanitizedBusinessId}:failures";
        }
        return "circuit_breaker:{$sanitizedService}:global:failures";
    }

    protected function getSuccessCountKey(string $service, int|string|null $businessId = null): string
    {
        $sanitizedService = $this->sanitizeCacheKeyComponent($service);
        if ($businessId) {
            $sanitizedBusinessId = $this->sanitizeCacheKeyComponent((string)$businessId);
            return "circuit_breaker:{$sanitizedService}:business_{$sanitizedBusinessId}:successes";
        }
        return "circuit_breaker:{$sanitizedService}:global:successes";
    }

    protected function getOpenedAtKey(string $service, int|string|null $businessId = null): string
    {
        $sanitizedService = $this->sanitizeCacheKeyComponent($service);
        if ($businessId) {
            $sanitizedBusinessId = $this->sanitizeCacheKeyComponent((string)$businessId);
            return "circuit_breaker:{$sanitizedService}:business_{$sanitizedBusinessId}:opened_at";
        }
        return "circuit_breaker:{$sanitizedService}:global:opened_at";
    }
}
