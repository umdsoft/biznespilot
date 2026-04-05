<?php

namespace App\Services\Agent\Memory;

use App\Models\AgentBusinessContext;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;

/**
 * Biznes xotirasi (2-qatlam) — MySQL + Redis kesh.
 * Agent qarorlari, foydalanuvchi afzalliklari, biznes holati saqlanadi.
 * MySQL da 30 kun, Redis keshda 30 daqiqa.
 */
class BusinessContextMemory
{
    private const CACHE_PREFIX = 'agent:context';
    private const CACHE_TTL = 1800; // 30 daqiqa

    /**
     * Biznes kontekstini olish (kesh → bazadan)
     */
    public function get(string $businessId, string $key): ?array
    {
        // Avval Redis keshdan
        try {
            $cacheKey = $this->buildCacheKey($businessId, $key);
            $cached = Redis::get($cacheKey);
            if ($cached) {
                return json_decode($cached, true);
            }
        } catch (\Exception $e) {
            Log::warning('BusinessContextMemory: kesh xatosi', ['error' => $e->getMessage()]);
        }

        // Keyin bazadan
        $value = AgentBusinessContext::getValue($businessId, $key);

        // Keshga saqlash
        if ($value !== null) {
            $this->cacheValue($businessId, $key, $value);
        }

        return $value;
    }

    /**
     * Biznes kontekstini saqlash
     */
    public function set(
        string $businessId,
        string $type,
        string $key,
        array $value,
        ?int $expiresInDays = 30,
    ): void {
        $expiresAt = $expiresInDays ? now()->addDays($expiresInDays) : null;

        AgentBusinessContext::setValue($businessId, $type, $key, $value, $expiresAt);

        // Keshni yangilash
        $this->cacheValue($businessId, $key, $value);
    }

    /**
     * Foydalanuvchi afzalligini saqlash
     */
    public function setPreference(string $businessId, string $key, array $value): void
    {
        $this->set($businessId, 'preference', $key, $value, null); // cheksiz
    }

    /**
     * Agent qarorini saqlash
     */
    public function setDecision(string $businessId, string $key, array $value): void
    {
        $this->set($businessId, 'decision', $key, $value, 30);
    }

    /**
     * Biznes holatini saqlash (snapshot)
     */
    public function setSnapshot(string $businessId, string $key, array $value): void
    {
        $this->set($businessId, 'snapshot', $key, $value, 7); // haftalik
    }

    /**
     * Barcha aktiv kontekstlarni olish (agent uchun)
     */
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

    /**
     * Muddati o'tgan kontekstlarni tozalash (cron uchun)
     */
    public function cleanExpired(): int
    {
        return AgentBusinessContext::where('expires_at', '<', now())->delete();
    }

    /**
     * Redis keshga saqlash
     */
    private function cacheValue(string $businessId, string $key, array $value): void
    {
        try {
            $cacheKey = $this->buildCacheKey($businessId, $key);
            Redis::setex($cacheKey, self::CACHE_TTL, json_encode($value, JSON_UNESCAPED_UNICODE));
        } catch (\Exception $e) {
            // Kesh xatosi asosiy jarayonni to'xtatmasligi kerak
        }
    }

    /**
     * Redis kalit yaratish
     */
    private function buildCacheKey(string $businessId, string $key): string
    {
        return self::CACHE_PREFIX . ":{$businessId}:{$key}";
    }
}
