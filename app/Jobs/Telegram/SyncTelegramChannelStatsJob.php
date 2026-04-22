<?php

declare(strict_types=1);

namespace App\Jobs\Telegram;

use App\Models\TelegramChannel;
use App\Services\Telegram\TelegramChannelAnalyticsService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * SyncTelegramChannelStatsJob
 *
 * Har 30 daqiqada bitta yoki hamma kanal uchun Bot API orqali yangi
 * subscriber_count va post snapshot'larini yangilaydi.
 *
 * Usage:
 *   SyncTelegramChannelStatsJob::dispatch();                 // barcha active kanal
 *   SyncTelegramChannelStatsJob::dispatch($channelId);       // bitta kanal
 */
class SyncTelegramChannelStatsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $backoff = 60;

    public int $timeout = 120;

    public function __construct(
        public ?string $channelId = null,
    ) {}

    public function handle(TelegramChannelAnalyticsService $service): void
    {
        $query = TelegramChannel::query()->active();

        if ($this->channelId) {
            $query->where('id', $this->channelId);
        }

        $channels = $query->get();

        Log::info('SyncTelegramChannelStatsJob: starting', [
            'channel_count' => $channels->count(),
            'specific_channel' => $this->channelId,
        ]);

        $synced = 0;
        foreach ($channels as $channel) {
            try {
                $service->syncChannelCore($channel);
                $service->snapshotRecentPosts($channel);
                $synced++;
            } catch (\Throwable $e) {
                Log::error('SyncTelegramChannelStatsJob: channel sync failed', [
                    'channel_id' => $channel->id,
                    'telegram_chat_id' => $channel->telegram_chat_id,
                    'error' => $e->getMessage(),
                ]);
            }

            // Respect Bot API global rate limit (~30 req/s)
            usleep(100_000); // 100ms between channels
        }

        Log::info('SyncTelegramChannelStatsJob: finished', [
            'synced' => $synced,
            'failed' => $channels->count() - $synced,
        ]);
    }
}
