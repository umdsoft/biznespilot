<?php

namespace App\Services\Agent\Memory;

use App\Models\AgentBusinessContext;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Biznes xotirasi (2-qatlam) — MySQL + Cache.
 * Agent qarorlari, foydalanuvchi afzalliklari, biznes holati saqlanadi.
 * MySQL da 30 kun, Cache da 30 daqiqa.
 */
class BusinessContextMemory
{
    private const CACHE_PREFIX = 'agent:context';
    private const CACHE_TTL = 1800; // 30 daqiqa

    public function get(string $businessId, string $key): ?array
    {
        try {
            $cacheKey = $this->buildCacheKey($businessId, $key);
            $cached = Cache::get($cacheKey);
            if ($cached) {
                return $cached;
            }
        } catch (\Exception $e) {
            Log::warning('BusinessContextMemory: kesh xatosi', ['error' => $e->getMessage()]);
        }

        $value = AgentBusinessContext::getValue($businessId, $key);

        if ($value !== null) {
            $this->cacheValue($businessId, $key, $value);
        }

        return $value;
    }

    public function set(string $businessId, string $type, string $key, array $value, ?int $expiresInDays = 30): void
    {
        $expiresAt = $expiresInDays ? now()->addDays($expiresInDays) : null;
        AgentBusinessContext::setValue($businessId, $type, $key, $value, $expiresAt);
        $this->cacheValue($businessId, $key, $value);
    }

    public function setPreference(string $businessId, string $key, array $value): void
    {
        $this->set($businessId, 'preference', $key, $value, null);
    }

    public function setDecision(string $businessId, string $key, array $value): void
    {
        $this->set($businessId, 'decision', $key, $value, 30);
    }

    public function setSnapshot(string $businessId, string $key, array $value): void
    {
        $this->set($businessId, 'snapshot', $key, $value, 7);
    }

    public function getAllContext(string $businessId): array
    {
        try {
            return AgentBusinessContext::forBusiness($businessId)
                ->active()
                ->get()
                ->groupBy('context_type')
                ->map(fn ($items) => $items->pluck('context_value', 'context_key'))
                ->toArray();
        } catch (\Exception $e) {
            Log::warning('BusinessContextMemory: getAllContext xatosi', ['error' => $e->getMessage()]);
            return [];
        }
    }

    public function cleanExpired(): int
    {
        return AgentBusinessContext::where('expires_at', '<', now())->delete();
    }

    private function cacheValue(string $businessId, string $key, array $value): void
    {
        try {
            Cache::put($this->buildCacheKey($businessId, $key), $value, self::CACHE_TTL);
        } catch (\Exception $e) {
            // Kesh xatosi asosiy jarayonni to'xtatmasligi kerak
        }
    }

    private function buildCacheKey(string $businessId, string $key): string
    {
        return self::CACHE_PREFIX . ":{$businessId}:{$key}";
    }
}
