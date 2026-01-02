<?php

namespace App\Services\Integration;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class RateLimiter
{
    /**
     * Rate limits per integration (requests per minute)
     * Keys match data_source enum values from migration
     */
    protected const RATE_LIMITS = [
        'instagram_api' => 200,  // Instagram Graph API: 200 calls per hour per user
        'facebook_api' => 200,   // Facebook Graph API: 200 calls per hour per user
        'pos_system' => 1000,    // Internal POS system: more generous limit
        // Legacy aliases for backward compatibility
        'instagram' => 200,
        'facebook' => 200,
        'pos' => 1000,
    ];

    /**
     * Rate limit window in seconds
     */
    protected const WINDOW_SECONDS = 60;

    /**
     * Check if we can make a request to the given integration
     *
     * @param string $integration Integration name (instagram_api, facebook_api, pos_system)
     * @param int|string|null $businessId Optional business ID for per-business tracking
     * @return bool True if request is allowed, false if rate limited
     */
    public function allowRequest(string $integration, int|string|null $businessId = null): bool
    {
        $key = $this->getCacheKey($integration, $businessId);
        $limit = self::RATE_LIMITS[$integration] ?? 100;

        // Get current request count
        $currentCount = Cache::get($key, 0);

        // If we've exceeded the limit, deny the request
        if ($currentCount >= $limit) {
            Log::warning("Rate limit exceeded for {$integration}", [
                'business_id' => $businessId,
                'current_count' => $currentCount,
                'limit' => $limit,
            ]);
            return false;
        }

        return true;
    }

    /**
     * Record a request to the given integration
     *
     * @param string $integration Integration name
     * @param int|string|null $businessId Optional business ID
     * @return void
     */
    public function recordRequest(string $integration, int|string|null $businessId = null): void
    {
        $key = $this->getCacheKey($integration, $businessId);
        $limit = self::RATE_LIMITS[$integration] ?? 100;

        // Use atomic increment to prevent race condition
        $currentCount = Cache::add($key, 0, self::WINDOW_SECONDS)
            ? 1
            : Cache::increment($key);

        // Set TTL if this is a new key
        if ($currentCount === 1) {
            Cache::put($key, 1, self::WINDOW_SECONDS);
        }

        // Log if approaching limit (90% threshold)
        if ($currentCount >= $limit * 0.9) {
            Log::warning("Approaching rate limit for {$integration}", [
                'business_id' => $businessId,
                'current_count' => $currentCount,
                'limit' => $limit,
                'percentage' => round(($currentCount / $limit) * 100, 1),
            ]);
        }
    }

    /**
     * Wait if necessary to respect rate limits
     *
     * @param string $integration Integration name
     * @param string|null $businessId Optional business ID
     * @return int Seconds waited (0 if no wait needed)
     */
    public function waitIfNeeded(string $integration, ?string $businessId = null): int
    {
        $key = $this->getCacheKey($integration, $businessId);
        $limit = self::RATE_LIMITS[$integration] ?? 100;
        $currentCount = Cache::get($key, 0);

        // If we're at the limit, calculate wait time
        if ($currentCount >= $limit) {
            // Get TTL of the cache key to see when it expires
            $ttl = Cache::get($key . ':ttl', self::WINDOW_SECONDS);
            $waitSeconds = min($ttl, self::WINDOW_SECONDS);

            Log::info("Rate limit reached, waiting {$waitSeconds}s for {$integration}", [
                'business_id' => $businessId,
                'current_count' => $currentCount,
                'limit' => $limit,
            ]);

            sleep($waitSeconds);
            return $waitSeconds;
        }

        return 0;
    }

    /**
     * Get remaining requests for an integration
     *
     * @param string $integration Integration name
     * @param string|null $businessId Optional business ID
     * @return int Number of remaining requests
     */
    public function getRemainingRequests(string $integration, ?string $businessId = null): int
    {
        $key = $this->getCacheKey($integration, $businessId);
        $limit = self::RATE_LIMITS[$integration] ?? 100;
        $currentCount = Cache::get($key, 0);

        return max(0, $limit - $currentCount);
    }

    /**
     * Reset rate limit counter for an integration
     *
     * @param string $integration Integration name
     * @param string|null $businessId Optional business ID
     * @return void
     */
    public function reset(string $integration, ?string $businessId = null): void
    {
        $key = $this->getCacheKey($integration, $businessId);
        Cache::forget($key);
        Cache::forget($key . ':ttl');

        Log::info("Rate limit reset for {$integration}", [
            'business_id' => $businessId,
        ]);
    }

    /**
     * Sanitize cache key component to prevent cache poisoning attacks.
     * Removes colons and other special characters that could break cache key structure.
     *
     * @param string $value Value to sanitize
     * @return string Sanitized value
     */
    protected function sanitizeCacheKeyComponent(string $value): string
    {
        // Only allow alphanumeric, underscore, and dash
        return preg_replace('/[^a-zA-Z0-9_-]/', '_', $value);
    }

    /**
     * Get cache key for rate limiting
     *
     * @param string $integration Integration name
     * @param string|null $businessId Optional business ID
     * @return string Cache key
     */
    protected function getCacheKey(string $integration, ?string $businessId = null): string
    {
        $sanitizedIntegration = $this->sanitizeCacheKeyComponent($integration);

        if ($businessId) {
            $sanitizedBusinessId = $this->sanitizeCacheKeyComponent((string)$businessId);
            return "rate_limit:{$sanitizedIntegration}:business_{$sanitizedBusinessId}";
        }

        return "rate_limit:{$sanitizedIntegration}:global";
    }

    /**
     * Get rate limit statistics for monitoring
     *
     * @param string $integration Integration name
     * @param string|null $businessId Optional business ID
     * @return array Statistics
     */
    public function getStats(string $integration, ?string $businessId = null): array
    {
        $key = $this->getCacheKey($integration, $businessId);
        $limit = self::RATE_LIMITS[$integration] ?? 100;
        $currentCount = Cache::get($key, 0);
        $remaining = max(0, $limit - $currentCount);

        return [
            'integration' => $integration,
            'business_id' => $businessId,
            'limit' => $limit,
            'used' => $currentCount,
            'remaining' => $remaining,
            'usage_percentage' => $limit > 0 ? round(($currentCount / $limit) * 100, 1) : 0,
            'window_seconds' => self::WINDOW_SECONDS,
        ];
    }

    /**
     * Execute a callback with rate limiting
     *
     * @param string $integration Integration name
     * @param callable $callback Function to execute
     * @param string|null $businessId Optional business ID
     * @return mixed Result of callback
     * @throws \Exception If rate limit exceeded and max retries reached
     */
    public function execute(string $integration, callable $callback, ?string $businessId = null)
    {
        $maxRetries = 3;
        $retryCount = 0;

        while ($retryCount < $maxRetries) {
            if ($this->allowRequest($integration, $businessId)) {
                $this->recordRequest($integration, $businessId);

                try {
                    return $callback();
                } catch (\Exception $e) {
                    Log::error("Error executing rate-limited request for {$integration}", [
                        'business_id' => $businessId,
                        'error' => $e->getMessage(),
                    ]);
                    throw $e;
                }
            }

            // Rate limit exceeded, wait and retry
            $retryCount++;
            if ($retryCount < $maxRetries) {
                $waitTime = $this->waitIfNeeded($integration, $businessId);
                Log::info("Retry {$retryCount}/{$maxRetries} after waiting {$waitTime}s", [
                    'integration' => $integration,
                    'business_id' => $businessId,
                ]);
            }
        }

        throw new \Exception("Rate limit exceeded for {$integration} after {$maxRetries} retries");
    }
}
