<?php

namespace App\Services\Telephony\Utel;

use App\Models\UtelAccount;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * UTEL Sync Service — sync holatini kuzatish, retry, error handling.
 *
 * Eski UtelService::syncCallHistory ishlashda davom etadi —
 * bu yangi service uning natijasini track qiladi.
 */
class UtelSyncService
{
    private const SYNC_LOG_KEY = 'utel_sync_log:';
    private const SYNC_HISTORY_LIMIT = 20;

    /**
     * Sync urinishini boshlash (log)
     */
    public function recordSyncStart(UtelAccount $account): string
    {
        $syncId = uniqid('sync_', true);

        $this->appendSyncHistory($account, [
            'id' => $syncId,
            'started_at' => now()->toISOString(),
            'status' => 'in_progress',
        ]);

        return $syncId;
    }

    /**
     * Sync muvaffaqiyatli yakunlandi
     */
    public function recordSyncSuccess(UtelAccount $account, string $syncId, array $stats): void
    {
        $this->updateLastSync($account, [
            'id' => $syncId,
            'status' => 'success',
            'finished_at' => now()->toISOString(),
            'stats' => $stats,
        ]);

        $account->update(['last_sync_at' => now()]);
    }

    /**
     * Sync xato bilan yakunlandi
     */
    public function recordSyncFailure(UtelAccount $account, string $syncId, string $error): void
    {
        $this->updateLastSync($account, [
            'id' => $syncId,
            'status' => 'failed',
            'finished_at' => now()->toISOString(),
            'error' => $error,
        ]);

        Log::error('UTEL sync failed', [
            'account_id' => $account->id,
            'sync_id' => $syncId,
            'error' => $error,
        ]);
    }

    /**
     * So'nggi sync holati
     */
    public function getLastSyncStatus(UtelAccount $account): array
    {
        $history = $this->getSyncHistory($account);
        $last = $history[0] ?? null;

        return [
            'has_synced' => !empty($history),
            'last_sync' => $last,
            'last_sync_at' => $account->last_sync_at?->toISOString(),
            'consecutive_failures' => $this->countConsecutiveFailures($history),
            'health' => $this->assessHealth($history, $account),
        ];
    }

    /**
     * Sync tarixini olish
     */
    public function getSyncHistory(UtelAccount $account): array
    {
        $key = self::SYNC_LOG_KEY . $account->id;
        return Cache::get($key, []);
    }

    /**
     * Tarixga yangi yozuv qo'shish
     */
    private function appendSyncHistory(UtelAccount $account, array $entry): void
    {
        $key = self::SYNC_LOG_KEY . $account->id;
        $history = Cache::get($key, []);
        array_unshift($history, $entry);
        $history = array_slice($history, 0, self::SYNC_HISTORY_LIMIT);
        Cache::put($key, $history, now()->addDays(7));
    }

    /**
     * So'nggi sync yozuvini yangilash
     */
    private function updateLastSync(UtelAccount $account, array $entry): void
    {
        $key = self::SYNC_LOG_KEY . $account->id;
        $history = Cache::get($key, []);

        if (!empty($history)) {
            $history[0] = array_merge($history[0], $entry);
        } else {
            array_unshift($history, $entry);
        }

        Cache::put($key, $history, now()->addDays(7));
    }

    /**
     * Ketma-ket xato sonini hisoblash
     */
    private function countConsecutiveFailures(array $history): int
    {
        $count = 0;
        foreach ($history as $entry) {
            if (($entry['status'] ?? '') === 'failed') {
                $count++;
            } else {
                break;
            }
        }
        return $count;
    }

    /**
     * Sync salomatligi
     */
    private function assessHealth(array $history, UtelAccount $account): string
    {
        if (empty($history)) return 'never_synced';

        $failures = $this->countConsecutiveFailures($history);
        if ($failures >= 3) return 'critical';
        if ($failures >= 1) return 'warning';

        // Oxirgi sync 24 soatdan oshganmi
        if ($account->last_sync_at && $account->last_sync_at->diffInHours(now()) > 24) {
            return 'stale';
        }

        return 'healthy';
    }
}
