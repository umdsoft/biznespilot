<?php

namespace App\Services\Agent\Pipeline;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

/**
 * AgentJobState — Cache-backed job state.
 *
 * Layered AI Agent uchun progress saqlash. Cache TTL 10 daqiqa
 * (frontend polling uchun yetarli, eski jamlar avtomatik tozalanadi).
 *
 * Layers:
 * - L1 instant   — KPI snapshot (DB only, instant)
 * - L2 primary   — asosiy agent javobi (sync)
 * - L3 secondary — qo'shimcha agent + Merger (async)
 * - L4 director  — strategik xulosa Sonnet (async)
 */
class AgentJobState
{
    private const TTL_SECONDS = 600; // 10 daqiqa

    private const STATUS_PENDING = 'pending';
    private const STATUS_PROCESSING = 'processing';
    private const STATUS_COMPLETED = 'completed';
    private const STATUS_FAILED = 'failed';

    public static function create(string $businessId, string $userId, ?string $conversationId): string
    {
        $jobId = (string) Str::uuid();

        Cache::put(self::key($jobId), [
            'job_id' => $jobId,
            'business_id' => $businessId,
            'user_id' => $userId,
            'conversation_id' => $conversationId,
            'status' => self::STATUS_PENDING,
            'layers' => [
                'instant' => null,
                'primary' => null,
                'secondary' => null,
                'director' => null,
            ],
            'pending_layers' => ['instant', 'primary', 'secondary', 'director'],
            'started_at' => microtime(true),
            'completed_at' => null,
            'error' => null,
        ], self::TTL_SECONDS);

        return $jobId;
    }

    public static function get(string $jobId): ?array
    {
        return Cache::get(self::key($jobId));
    }

    /**
     * Layer natijasini saqlash va pending_layers dan olib tashlash.
     */
    public static function setLayer(string $jobId, string $layer, array $payload): void
    {
        $state = self::get($jobId);
        if (! $state) {
            return; // expired
        }

        $state['layers'][$layer] = $payload;
        $state['pending_layers'] = array_values(array_diff($state['pending_layers'], [$layer]));
        $state['status'] = empty($state['pending_layers']) ? self::STATUS_COMPLETED : self::STATUS_PROCESSING;

        if ($state['status'] === self::STATUS_COMPLETED && $state['completed_at'] === null) {
            $state['completed_at'] = microtime(true);
        }

        Cache::put(self::key($jobId), $state, self::TTL_SECONDS);
    }

    /**
     * Layer'ni "skip" sifatida belgilash (vaqt budjeti tugadi yoki kerak emas).
     */
    public static function skipLayer(string $jobId, string $layer, string $reason = ''): void
    {
        self::setLayer($jobId, $layer, [
            'skipped' => true,
            'reason' => $reason,
        ]);
    }

    public static function fail(string $jobId, string $error): void
    {
        $state = self::get($jobId);
        if (! $state) {
            return;
        }

        $state['status'] = self::STATUS_FAILED;
        $state['error'] = $error;
        $state['completed_at'] = microtime(true);

        Cache::put(self::key($jobId), $state, self::TTL_SECONDS);
    }

    /**
     * Frontend uchun yengillashtirilgan response.
     */
    public static function toResponse(string $jobId): ?array
    {
        $state = self::get($jobId);
        if (! $state) {
            return null;
        }

        $elapsed = isset($state['completed_at']) && $state['completed_at']
            ? $state['completed_at'] - $state['started_at']
            : microtime(true) - $state['started_at'];

        return [
            'job_id' => $state['job_id'],
            'conversation_id' => $state['conversation_id'],
            'status' => $state['status'],
            'layers' => $state['layers'],
            'pending_layers' => $state['pending_layers'],
            'elapsed_s' => round($elapsed, 2),
            'error' => $state['error'],
        ];
    }

    private static function key(string $jobId): string
    {
        return "agent_job:{$jobId}";
    }
}
