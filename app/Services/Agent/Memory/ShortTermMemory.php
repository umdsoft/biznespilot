<?php

namespace App\Services\Agent\Memory;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Lahzalik xotira (1-qatlam) — Laravel Cache orqali saqlanadi.
 * Hozirgi suhbat konteksti: oxirgi xabarlar, agent rejasi, oraliq natijalar.
 * TTL: 15 daqiqa (suhbat davomida)
 */
class ShortTermMemory
{
    private const PREFIX = 'agent:session';
    private const TTL = 900; // 15 daqiqa

    public function get(string $businessId, string $conversationId): ?array
    {
        try {
            return Cache::get($this->buildKey($businessId, $conversationId));
        } catch (\Exception $e) {
            Log::warning('ShortTermMemory: olish xatosi', ['error' => $e->getMessage()]);
        }

        return null;
    }

    public function set(string $businessId, string $conversationId, array $context): void
    {
        try {
            Cache::put($this->buildKey($businessId, $conversationId), $context, self::TTL);
        } catch (\Exception $e) {
            Log::warning('ShortTermMemory: saqlash xatosi', ['error' => $e->getMessage()]);
        }
    }

    public function addMessage(string $businessId, string $conversationId, array $message): void
    {
        $context = $this->get($businessId, $conversationId) ?? [
            'messages' => [],
            'agent_plan' => null,
            'intermediate_results' => [],
        ];

        $context['messages'][] = $message;

        if (count($context['messages']) > 10) {
            $context['messages'] = array_slice($context['messages'], -10);
        }

        $this->set($businessId, $conversationId, $context);
    }

    public function getRecentMessages(string $businessId, string $conversationId, int $limit = 5): array
    {
        $context = $this->get($businessId, $conversationId);

        if (! $context || empty($context['messages'])) {
            return [];
        }

        return array_slice($context['messages'], -$limit);
    }

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

    public function clear(string $businessId, string $conversationId): void
    {
        try {
            Cache::forget($this->buildKey($businessId, $conversationId));
        } catch (\Exception $e) {
            Log::warning('ShortTermMemory: tozalash xatosi', ['error' => $e->getMessage()]);
        }
    }

    private function buildKey(string $businessId, string $conversationId): string
    {
        return self::PREFIX . ":{$businessId}:{$conversationId}";
    }
}
