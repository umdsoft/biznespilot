<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\ContentCalendar;
use App\Services\Content\Publishers\InstagramPublisher;
use App\Services\Content\Publishers\TelegramChannelPublisher;
use App\Services\Content\Publishers\YoutubePublisher;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Cron job: rejalashtirilgan content item'larni avtomatik publish qiladi.
 *
 * Trigger:
 *   - status = 'scheduled'
 *   - scheduled_at <= now()
 *
 * Har platforma uchun alohida Publisher chaqiriladi:
 *   - telegram → TelegramChannelPublisher
 *   - instagram → (kelajakda) InstagramPublisher
 *   - youtube → (kelajakda) YoutubePublisher
 *
 * Schedule (routes/console.php'ga qo'shing):
 *   $schedule->job(new PublishScheduledContentJob)->everyMinute();
 *
 * Idempotency: status='scheduled' → 'publishing' → 'published' yoki 'failed'.
 *  Race holatlar oldini olish uchun queryda atomic update.
 */
class PublishScheduledContentJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;

    public int $timeout = 300;

    /**
     * Bir runda max nechta item qayta ishlanadi (rate-limit).
     */
    private const BATCH_LIMIT = 25;

    public function handle(): void
    {
        $items = ContentCalendar::query()
            ->where('status', 'scheduled')
            ->where(function ($q) {
                $q->whereNotNull('scheduled_at')->where('scheduled_at', '<=', now());
            })
            ->orderBy('scheduled_at', 'asc')
            ->limit(self::BATCH_LIMIT)
            ->get();

        if ($items->isEmpty()) {
            return;
        }

        Log::info('PublishScheduledContentJob: starting batch', [
            'count' => $items->count(),
        ]);

        foreach ($items as $item) {
            // Atomic claim: avval status'ni 'publishing' ga o'zgartiramiz
            $claimed = ContentCalendar::where('id', $item->id)
                ->where('status', 'scheduled')
                ->update(['status' => 'publishing']);

            if (! $claimed) {
                continue; // Boshqa ishchi tomonidan claim qilingan
            }

            try {
                $this->publishItem($item->fresh());
            } catch (\Throwable $e) {
                Log::error('PublishScheduledContentJob: item failed', [
                    'item_id' => $item->id,
                    'error' => $e->getMessage(),
                ]);
                ContentCalendar::where('id', $item->id)->update([
                    'status' => 'failed',
                    'notes' => 'Publish error: ' . $e->getMessage(),
                ]);
            }
        }
    }

    protected function publishItem(ContentCalendar $item): void
    {
        $platform = strtolower((string) ($item->platform ?? $item->channel ?? ''));

        $result = match ($platform) {
            'telegram' => app(TelegramChannelPublisher::class)->publish($item),
            'instagram' => app(InstagramPublisher::class)->publish($item),
            'youtube' => app(YoutubePublisher::class)->publish($item),
            default => ['success' => false, 'error' => 'unsupported_platform'],
        };

        if (! ($result['success'] ?? false)) {
            $item->update([
                'status' => 'failed',
                'notes' => 'Publish failed: ' . json_encode($result, JSON_UNESCAPED_UNICODE),
            ]);
            Log::warning('PublishScheduledContentJob: publish returned failure', [
                'item_id' => $item->id,
                'result' => $result,
            ]);
        }
    }
}
