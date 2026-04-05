<?php

namespace App\Services\Agent\Memory;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;

/**
 * Lahzalik xotira (1-qatlam) — Redis da saqlanadi.
 * Hozirgi suhbat konteksti: oxirgi xabarlar, agent rejasi, oraliq natijalar.
 * TTL: 15 daqiqa (suhbat davomida)
 */
class ShortTermMemory
{
    // Kesh prefiksi va TTL
    private const PREFIX = 'agent:session';
    private const TTL = 900; // 15 daqiqa

    /**
     * Suhbat kontekstini olish
     */
    public function get(string $businessId, string $conversationId): ?array
    {
        try {
            $key = $this->buildKey($businessId, $conversationId);
            $data = Redis::get($key);

            if ($data) {
                return json_decode($data, true);
            }
        } catch (\Exception $e) {
            Log::warning('ShortTermMemory: olish xatosi', ['error' => $e->getMessage()]);
        }

        return null;
    }

    /**
     * Suhbat kontekstini saqlash
     */
    public function set(string $businessId, string $conversationId, array $context): void
    {
        try {
            $key = $this->buildKey($businessId, $conversationId);
            Redis::setex($key, self::TTL, json_encode($context, JSON_UNESCAPED_UNICODE));
        } catch (\Exception $e) {
            Log::warning('ShortTermMemory: saqlash xatosi', ['error' => $e->getMessage()]);
        }
    }

    /**
     * Xabarni kontekstga qo'shish (oxirgi 10 ta saqlanadi)
     */
    public function addMessage(string $businessId, string $conversationId, array $message): void
    {
        $context = $this->get($businessId, $conversationId) ?? [
            'messages' => [],
            'agent_plan' => null,
            'intermediate_results' => [],
        ];

        // Xabarni qo'shish
        $context['messages'][] = $message;

        // Faqat oxirgi 10 ta xabarni saqlash
        if (count($context['messages']) > 10) {
            $context['messages'] = array_slice($context['messages'], -10);
        }

        $this->set($businessId, $conversationId, $context);
    }

    /**
     * Oxirgi xabarlarni olish (AI ga kontekst sifatida yuborish uchun)
     */
    public function getRecentMessages(string $businessId, string $conversationId, int $limit = 5): array
    {
        $context = $this->get($businessId, $conversationId);

        if (! $context || empty($context['messages'])) {
            return [];
        }

        return array_slice($context['messages'], -$limit);
    }

    /**
     * Oraliq natijani saqlash
     */
    public function setIntermediateResult(string $businessId, string $conversationId, string $key, mixed $value): void
    {
        $context = $this->get($businessId, $conversationId) ?? [
            'messages' => [],
            'agent_plan' => null,
            'intermediate_results' => [],
        ];

        $context['intermediate_results'][$key] = $value;
        $this->set($businessId, $conversationId, $context);
    }

    /**
     * Kontekstni tozalash
     */
    public function clear(string $businessId, string $conversationId): void
    {
        try {
            Redis::del($this->buildKey($businessId, $conversationId));
        } catch (\Exception $e) {
            Log::warning('ShortTermMemory: tozalash xatosi', ['error' => $e->getMessage()]);
        }
    }

    /**
     * Redis kalitini yaratish
     */
    private function buildKey(string $businessId, string $conversationId): string
    {
        return self::PREFIX . ":{$businessId}:{$conversationId}";
    }
}
