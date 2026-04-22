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
 * SendTelegramChannelDigestJob
 *
 * Har kuni 08:00 (Asia/Tashkent) da ishga tushadi va har active kanal uchun
 * linked user'ga (ya'ni kanalni ulagan BiznesPilot user) kungi hisobotini
 * System Bot orqali DM sifatida yuboradi.
 */
class SendTelegramChannelDigestJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;

    public int $backoff = 60;

    public int $timeout = 300;

    public function __construct(
        public ?string $digestDate = null,
        public ?string $channelId = null,
    ) {}

    public function handle(TelegramChannelAnalyticsService $service): void
    {
        $date = $this->digestDate
            ? Carbon::parse($this->digestDate)->startOfDay()
            : now()->subDay()->startOfDay();

        $query = TelegramChannel::query()->active();
        if ($this->channelId) {
            $query->where('id', $this->channelId);
        }

        $channels = $query->get();

        Log::info('SendTelegramChannelDigestJob: starting', [
            'digest_date' => $date->toDateString(),
            'channel_count' => $channels->count(),
        ]);

        $sent = 0;
        foreach ($channels as $channel) {
            try {
                if ($service->sendDigestToOwner($channel, $date)) {
                    $sent++;
                }
            } catch (\Throwable $e) {
                Log::error('SendTelegramChannelDigestJob: send failed', [
                    'channel_id' => $channel->id,
                    'error' => $e->getMessage(),
                ]);
            }

            // Rate limit courtesy
            usleep(200_000);
        }

        Log::info('SendTelegramChannelDigestJob: finished', [
            'sent' => $sent,
            'skipped' => $channels->count() - $sent,
        ]);
    }
}
