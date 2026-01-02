<?php

namespace App\Services\Algorithm\Performance;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Log;
use App\Models\Business;
use Closure;

/**
 * Algorithm Cache Manager
 *
 * Intelligent multi-layer caching for algorithm results.
 * Handles 100+ concurrent requests with minimal database load.
 *
 * Cache Layers:
 * 1. Request-level (in-memory) - same request reuse
 * 2. Application cache (Redis/File) - cross-request
 * 3. Computed cache - expensive calculations
 *
 * @version 1.0.0
 */
class AlgorithmCacheManager
{
    /**
     * In-memory request cache
     */
    protected static array $requestCache = [];

    /**
     * Cache key prefixes
     */
    protected const PREFIX_DIAGNOSTIC = 'diag:';
    protected const PREFIX_ALGORITHM = 'algo:';
    protected const PREFIX_METRICS = 'metrics:';
    protected const PREFIX_BENCHMARK = 'bench:';

    /**
     * TTL configurations (in seconds)
     */
    protected const TTL_SHORT = 300;      // 5 minutes - volatile data
    protected const TTL_MEDIUM = 1800;    // 30 minutes - diagnostic results
    protected const TTL_LONG = 3600;      // 1 hour - metrics
    protected const TTL_BENCHMARK = 86400; // 24 hours - benchmarks

    /**
     * Cache tags for invalidation
     */
    protected const TAG_BUSINESS = 'business';
    protected const TAG_DIAGNOSTIC = 'diagnostic';
    protected const TAG_ALGORITHM = 'algorithm';

    /**
     * Get or compute diagnostic result
     */
    public function diagnostic(Business $business, Closure $callback, ?int $ttl = null): array
    {
        $key = $this->diagnosticKey($business);
        $ttl = $ttl ?? self::TTL_MEDIUM;

        return $this->remember($key, $callback, $ttl, [
            self::TAG_DIAGNOSTIC,
            self::TAG_BUSINESS . ':' . $business->id,
        ]);
    }

    /**
     * Get or compute algorithm result
     */
    public function algorithm(string $name, Business $business, Closure $callback, ?int $ttl = null): array
    {
        $key = $this->algorithmKey($name, $business);
        $ttl = $ttl ?? self::TTL_MEDIUM;

        return $this->remember($key, $callback, $ttl, [
            self::TAG_ALGORITHM,
            self::TAG_BUSINESS . ':' . $business->id,
        ]);
    }

    /**
     * Get or compute metrics
     */
    public function metrics(Business $business, string $type, Closure $callback, ?int $ttl = null): array
    {
        $key = $this->metricsKey($business, $type);
        $ttl = $ttl ?? self::TTL_LONG;

        return $this->remember($key, $callback, $ttl, [
            self::TAG_BUSINESS . ':' . $business->id,
        ]);
    }

    /**
     * Get or compute benchmark data
     */
    public function benchmark(string $industry, Closure $callback): array
    {
        $key = self::PREFIX_BENCHMARK . $industry;
        return $this->remember($key, $callback, self::TTL_BENCHMARK);
    }

    /**
     * Smart remember with request cache
     */
    protected function remember(string $key, Closure $callback, int $ttl, array $tags = []): mixed
    {
        // Layer 1: Request cache (in-memory)
        if (isset(self::$requestCache[$key])) {
            return self::$requestCache[$key];
        }

        // Layer 2: Application cache
        $value = $this->getFromCache($key, $tags);
        if ($value !== null) {
            self::$requestCache[$key] = $value;
            return $value;
        }

        // Layer 3: Compute and cache
        $startTime = microtime(true);
        $value = $callback();
        $computeTime = round((microtime(true) - $startTime) * 1000, 2);

        // Store in both layers
        $this->putToCache($key, $value, $ttl, $tags);
        self::$requestCache[$key] = $value;

        // Log slow computations
        if ($computeTime > 500) {
            Log::warning("Slow algorithm computation", [
                'key' => $key,
                'time_ms' => $computeTime,
            ]);
        }

        return $value;
    }

    /**
     * Get from application cache
     */
    protected function getFromCache(string $key, array $tags = []): mixed
    {
        try {
            if (!empty($tags) && $this->supportsTagging()) {
                return Cache::tags($tags)->get($key);
            }
            return Cache::get($key);
        } catch (\Exception $e) {
            Log::warning("Cache get failed", ['key' => $key, 'error' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * Put to application cache
     */
    protected function putToCache(string $key, mixed $value, int $ttl, array $tags = []): void
    {
        try {
            if (!empty($tags) && $this->supportsTagging()) {
                Cache::tags($tags)->put($key, $value, $ttl);
            } else {
                Cache::put($key, $value, $ttl);
            }
        } catch (\Exception $e) {
            Log::warning("Cache put failed", ['key' => $key, 'error' => $e->getMessage()]);
        }
    }

    /**
     * Check if cache driver supports tagging
     */
    protected function supportsTagging(): bool
    {
        $driver = config('cache.default');
        return in_array($driver, ['redis', 'memcached', 'array']);
    }

    /**
     * Invalidate all caches for a business
     */
    public function invalidateBusiness(Business $business): void
    {
        $businessId = $business->id;

        // Clear request cache
        foreach (self::$requestCache as $key => $value) {
            if (str_contains($key, ":{$businessId}:")) {
                unset(self::$requestCache[$key]);
            }
        }

        // Clear application cache
        if ($this->supportsTagging()) {
            try {
                Cache::tags([self::TAG_BUSINESS . ':' . $businessId])->flush();
            } catch (\Exception $e) {
                Log::warning("Tagged cache flush failed", ['business_id' => $businessId]);
            }
        } else {
            // Manual key invalidation for non-tagging drivers
            $this->invalidateBusinessKeys($businessId);
        }

        Log::info("Business cache invalidated", ['business_id' => $businessId]);
    }

    /**
     * Invalidate specific business keys for non-tagging drivers
     */
    protected function invalidateBusinessKeys(int $businessId): void
    {
        $patterns = [
            self::PREFIX_DIAGNOSTIC . $businessId,
            self::PREFIX_ALGORITHM . '*:' . $businessId,
            self::PREFIX_METRICS . $businessId . ':*',
        ];

        foreach ($patterns as $pattern) {
            Cache::forget($pattern);
        }
    }

    /**
     * Invalidate all diagnostic caches
     */
    public function invalidateAllDiagnostics(): void
    {
        self::$requestCache = [];

        if ($this->supportsTagging()) {
            try {
                Cache::tags([self::TAG_DIAGNOSTIC])->flush();
            } catch (\Exception $e) {
                Log::warning("Diagnostic cache flush failed");
            }
        }

        Log::info("All diagnostic caches invalidated");
    }

    /**
     * Invalidate benchmark caches
     */
    public function invalidateBenchmarks(): void
    {
        foreach (self::$requestCache as $key => $value) {
            if (str_starts_with($key, self::PREFIX_BENCHMARK)) {
                unset(self::$requestCache[$key]);
            }
        }

        Log::info("Benchmark caches invalidated");
    }

    /**
     * Pre-warm cache for a business
     *
     * Call this after business data changes to pre-compute results
     */
    public function warmCache(Business $business, Closure $diagnosticCallback): void
    {
        // Invalidate existing
        $this->invalidateBusiness($business);

        // Pre-compute diagnostic
        $key = $this->diagnosticKey($business);

        try {
            $result = $diagnosticCallback();
            $this->putToCache($key, $result, self::TTL_MEDIUM, [
                self::TAG_DIAGNOSTIC,
                self::TAG_BUSINESS . ':' . $business->id,
            ]);
            self::$requestCache[$key] = $result;

            Log::info("Cache warmed for business", ['business_id' => $business->id]);
        } catch (\Exception $e) {
            Log::warning("Cache warming failed", [
                'business_id' => $business->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Clear request cache (call at end of request)
     */
    public static function clearRequestCache(): void
    {
        self::$requestCache = [];
    }

    /**
     * Get cache statistics
     */
    public function getStats(): array
    {
        return [
            'request_cache_size' => count(self::$requestCache),
            'request_cache_keys' => array_keys(self::$requestCache),
            'supports_tagging' => $this->supportsTagging(),
            'driver' => config('cache.default'),
        ];
    }

    /**
     * Generate diagnostic cache key
     */
    protected function diagnosticKey(Business $business): string
    {
        return self::PREFIX_DIAGNOSTIC . $business->id . ':' . $this->getBusinessHash($business);
    }

    /**
     * Generate algorithm cache key
     */
    protected function algorithmKey(string $name, Business $business): string
    {
        return self::PREFIX_ALGORITHM . $name . ':' . $business->id . ':' . $this->getBusinessHash($business);
    }

    /**
     * Generate metrics cache key
     */
    protected function metricsKey(Business $business, string $type): string
    {
        return self::PREFIX_METRICS . $business->id . ':' . $type;
    }

    /**
     * Generate hash of business state for cache invalidation
     *
     * When business data changes, hash changes, cache auto-invalidates
     */
    protected function getBusinessHash(Business $business): string
    {
        $hashData = [
            'updated_at' => $business->updated_at?->timestamp ?? 0,
            'dream_buyers_count' => $business->dreamBuyers()->count(),
            'offers_count' => $business->offers()->count(),
            'integrations_count' => $business->integrations()->count(),
        ];

        return substr(md5(json_encode($hashData)), 0, 8);
    }

    /**
     * Get cache key for locking
     */
    public function getLockKey(string $key): string
    {
        return 'lock:' . $key;
    }

    /**
     * Atomic lock for expensive computations
     */
    public function atomicLock(string $key, Closure $callback, int $lockTimeout = 10): mixed
    {
        $lockKey = $this->getLockKey($key);
        $lock = Cache::lock($lockKey, $lockTimeout);

        try {
            // Try to acquire lock
            if ($lock->get()) {
                return $callback();
            }

            // Wait for existing computation
            $maxWait = $lockTimeout * 1000000; // Convert to microseconds
            $waited = 0;
            $interval = 100000; // 100ms

            while ($waited < $maxWait) {
                usleep($interval);
                $waited += $interval;

                // Check if result is now cached
                $result = Cache::get($key);
                if ($result !== null) {
                    return $result;
                }
            }

            // Timeout - compute anyway
            return $callback();

        } finally {
            optional($lock)->release();
        }
    }
}
