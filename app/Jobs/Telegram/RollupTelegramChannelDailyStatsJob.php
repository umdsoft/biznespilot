<?php

declare(strict_types=1);

namespace App\Jobs\Telegram;

use App\Models\TelegramChannel;
use App\Services\Telegram\TelegramChannelAnalyticsService;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * RollupTelegramChannelDailyStatsJob
 *
 * Har kuni 23:55 da ishga tushadi — har active kanal uchun shu kunlik
 * metrikalarni `telegram_channel_daily_stats` jadvaliga yozadi.
 */
class RollupTelegramChannelDailyStatsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $backoff = 120;

    public int $timeout = 300;

    public function __construct(
        public ?string $targetDate = null,
    ) {}

    public function handle(TelegramChannelAnalyticsService $service): void
    {
        $date = $this->targetDate
            ? Carbon::parse($this->targetDate)->startOfDay()
            : now()->startOfDay();

        $channels = TelegramChannel::query()->active()->get();

        Log::info('RollupTelegramChannelDailyStatsJob: starting', [
            'date' => $date->toDateString(),
            'channel_count' => $channels->count(),
        ]);

        $rolled = 0;
        foreach ($channels as $channel) {
            try {
                $service->rollupDailyStats($channel, $date);
                $rolled++;
            } catch (\Throwable $e) {
                Log::error('RollupTelegramChannelDailyStatsJob: rollup failed', [
                    'channel_id' => $channel->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        Log::info('RollupTelegramChannelDailyStatsJob: finished', [
            'rolled' => $rolled,
        ]);
    }
}
