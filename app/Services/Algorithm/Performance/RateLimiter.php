<?php

namespace App\Services\Algorithm\Performance;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Log;

/**
 * Algorithm Rate Limiter
 *
 * Prevents system overload by limiting algorithm execution rate.
 * Uses sliding window algorithm for smooth rate limiting.
 *
 * @version 1.0.0
 */
class RateLimiter
{
    /**
     * Rate limit configurations - Optimized for 500+ concurrent requests
     */
    protected array $limits = [
        'diagnostic' => [
            'requests' => 600,      // Max 600 requests per minute
            'window' => 60,         // Per 60 seconds
            'burst' => 100,         // Max burst for spikes
        ],
        'algorithm' => [
            'requests' => 2000,     // Single algorithm calls
            'window' => 60,
            'burst' => 200,
        ],
        'batch' => [
            'requests' => 50,       // Batch processing
            'window' => 60,
            'burst' => 10,
        ],
        'global' => [
            'requests' => 3000,     // Global system limit
            'window' => 60,
            'burst' => 300,
        ],
    ];

    /**
     * Check if request is allowed
     */
    public function attempt(string $key, string $type = 'diagnostic'): bool
    {
        $config = $this->limits[$type] ?? $this->limits['diagnostic'];
        $cacheKey = $this->getCacheKey($key, $type);

        // Sliding window counter
        $current = $this->getCurrentCount($cacheKey, $config['window']);

        if ($current >= $config['requests']) {
            Log::warning("Rate limit exceeded", [
                'key' => $key,
                'type' => $type,
                'current' => $current,
                'limit' => $config['requests'],
            ]);
            return false;
        }

        $this->incrementCounter($cacheKey, $config['window']);
        return true;
    }

    /**
     * Check if request is allowed without incrementing
     */
    public function check(string $key, string $type = 'diagnostic'): bool
    {
        $config = $this->limits[$type] ?? $this->limits['diagnostic'];
        $cacheKey = $this->getCacheKey($key, $type);

        $current = $this->getCurrentCount($cacheKey, $config['window']);
        return $current < $config['requests'];
    }

    /**
     * Get remaining requests
     */
    public function remaining(string $key, string $type = 'diagnostic'): int
    {
        $config = $this->limits[$type] ?? $this->limits['diagnostic'];
        $cacheKey = $this->getCacheKey($key, $type);

        $current = $this->getCurrentCount($cacheKey, $config['window']);
        return max(0, $config['requests'] - $current);
    }

    /**
     * Get time until reset in seconds
     */
    public function availableIn(string $key, string $type = 'diagnostic'): int
    {
        $config = $this->limits[$type] ?? $this->limits['diagnostic'];
        $cacheKey = $this->getCacheKey($key, $type);

        // Get oldest request timestamp
        $timestamps = Cache::get($cacheKey . ':timestamps', []);

        if (empty($timestamps)) {
            return 0;
        }

        $now = time();
        $windowStart = $now - $config['window'];

        // Find when the oldest request will expire
        foreach ($timestamps as $timestamp) {
            if ($timestamp > $windowStart) {
                return ($timestamp + $config['window']) - $now;
            }
        }

        return 0;
    }

    /**
     * Hit rate limiter and execute callback if allowed
     */
    public function throttle(string $key, callable $callback, string $type = 'diagnostic'): mixed
    {
        if (!$this->attempt($key, $type)) {
            $availableIn = $this->availableIn($key, $type);
            throw new \RuntimeException(
                "Rate limit exceeded. Try again in {$availableIn} seconds.",
                429
            );
        }

        return $callback();
    }

    /**
     * Execute with retry on rate limit
     */
    public function retryOnLimit(string $key, callable $callback, string $type = 'diagnostic', int $maxRetries = 3): mixed
    {
        $retries = 0;

        while ($retries < $maxRetries) {
            if ($this->attempt($key, $type)) {
                return $callback();
            }

            $retries++;
            $waitTime = $this->availableIn($key, $type) + 1;

            if ($retries < $maxRetries) {
                Log::info("Rate limited, waiting to retry", [
                    'key' => $key,
                    'retry' => $retries,
                    'wait_seconds' => $waitTime,
                ]);
                sleep(min($waitTime, 10)); // Max 10 second wait
            }
        }

        throw new \RuntimeException("Rate limit exceeded after {$maxRetries} retries", 429);
    }

    /**
     * Get current count using sliding window
     */
    protected function getCurrentCount(string $key, int $window): int
    {
        $timestamps = Cache::get($key . ':timestamps', []);
        $now = time();
        $windowStart = $now - $window;

        // Count requests within window
        $count = 0;
        foreach ($timestamps as $timestamp) {
            if ($timestamp > $windowStart) {
                $count++;
            }
        }

        return $count;
    }

    /**
     * Increment counter with sliding window
     */
    protected function incrementCounter(string $key, int $window): void
    {
        $timestamps = Cache::get($key . ':timestamps', []);
        $now = time();
        $windowStart = $now - $window;

        // Remove old timestamps
        $timestamps = array_filter($timestamps, fn($ts) => $ts > $windowStart);

        // Add new timestamp
        $timestamps[] = $now;

        // Store with TTL
        Cache::put($key . ':timestamps', $timestamps, $window * 2);
    }

    /**
     * Reset rate limiter for key
     */
    public function reset(string $key, string $type = 'diagnostic'): void
    {
        $cacheKey = $this->getCacheKey($key, $type);
        Cache::forget($cacheKey . ':timestamps');
    }

    /**
     * Reset all rate limiters for a type
     */
    public function resetAll(string $type = 'diagnostic'): void
    {
        // Note: This requires Redis SCAN or similar for pattern-based deletion
        Log::info("Rate limiter reset requested", ['type' => $type]);
    }

    /**
     * Configure custom limits
     */
    public function configure(string $type, array $config): self
    {
        $this->limits[$type] = array_merge(
            $this->limits[$type] ?? $this->limits['diagnostic'],
            $config
        );
        return $this;
    }

    /**
     * Get rate limiter stats
     */
    public function getStats(string $key, string $type = 'diagnostic'): array
    {
        $config = $this->limits[$type] ?? $this->limits['diagnostic'];
        $cacheKey = $this->getCacheKey($key, $type);

        return [
            'key' => $key,
            'type' => $type,
            'limit' => $config['requests'],
            'window_seconds' => $config['window'],
            'current' => $this->getCurrentCount($cacheKey, $config['window']),
            'remaining' => $this->remaining($key, $type),
            'available_in_seconds' => $this->availableIn($key, $type),
        ];
    }

    /**
     * Get cache key
     */
    protected function getCacheKey(string $key, string $type): string
    {
        return "rate_limit:{$type}:{$key}";
    }

    /**
     * Per-user rate limiting
     */
    public function forUser(int $userId): UserRateLimiter
    {
        return new UserRateLimiter($this, $userId);
    }

    /**
     * Per-business rate limiting
     */
    public function forBusiness(int $businessId): BusinessRateLimiter
    {
        return new BusinessRateLimiter($this, $businessId);
    }
}

/**
 * User-scoped rate limiter
 */
class UserRateLimiter
{
    public function __construct(
        protected RateLimiter $limiter,
        protected int $userId
    ) {}

    public function attempt(string $type = 'diagnostic'): bool
    {
        return $this->limiter->attempt("user:{$this->userId}", $type);
    }

    public function remaining(string $type = 'diagnostic'): int
    {
        return $this->limiter->remaining("user:{$this->userId}", $type);
    }

    public function throttle(callable $callback, string $type = 'diagnostic'): mixed
    {
        return $this->limiter->throttle("user:{$this->userId}", $callback, $type);
    }
}

/**
 * Business-scoped rate limiter
 */
class BusinessRateLimiter
{
    public function __construct(
        protected RateLimiter $limiter,
        protected int $businessId
    ) {}

    public function attempt(string $type = 'diagnostic'): bool
    {
        return $this->limiter->attempt("business:{$this->businessId}", $type);
    }

    public function remaining(string $type = 'diagnostic'): int
    {
        return $this->limiter->remaining("business:{$this->businessId}", $type);
    }

    public function throttle(callable $callback, string $type = 'diagnostic'): mixed
    {
        return $this->limiter->throttle("business:{$this->businessId}", $callback, $type);
    }
}
