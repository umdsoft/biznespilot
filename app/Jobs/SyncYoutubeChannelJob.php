<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\YoutubeChannel;
use App\Models\YoutubeVideo;
use App\Services\Content\ContentMatcher;
use App\Services\Youtube\YoutubeApiClient;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * YouTube kanaldan yangi videolarni sinxronlaydi va ContentCalendar bilan match qiladi.
 *
 * Algoritm:
 *   1. Aktiv YoutubeChannel'larni topish
 *   2. Har biri uchun playlistItems.list (uploads playlist) — yangi videolar
 *   3. Yangi yoki tahrir qilinganlar uchun videos.list (statistics)
 *   4. ContentMatcher::matchYoutubeVideo har bir yangi/yangilangan video uchun
 *
 * Quota: bir kanal uchun ~3 unit (playlistItems.list 1 + videos.list 1 + safety 1)
 */
class SyncYoutubeChannelJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;

    public int $backoff = 120;

    public int $timeout = 600;

    public function __construct(
        public ?string $channelUuid = null,
    ) {}

    public function handle(YoutubeApiClient $api, ContentMatcher $matcher): void
    {
        $query = YoutubeChannel::where('is_active', true)
            ->whereNotNull('uploads_playlist_id');

        if ($this->channelUuid) {
            $query->where('id', $this->channelUuid);
        }

        $channels = $query->get();
        if ($channels->isEmpty()) {
            return;
        }

        foreach ($channels as $channel) {
            try {
                $this->syncChannel($channel, $api, $matcher);
            } catch (\Throwable $e) {
                Log::error('SyncYoutubeChannelJob: channel sync failed', [
                    'channel_id' => $channel->channel_id,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    protected function syncChannel(YoutubeChannel $channel, YoutubeApiClient $api, ContentMatcher $matcher): void
    {
        $items = $api->listUploads($channel, 50);
        if (empty($items)) {
            $channel->update(['last_synced_at' => now()]);
            return;
        }

        $videoIds = [];
        foreach ($items as $item) {
            $videoId = $item['contentDetails']['videoId'] ?? null;
            if ($videoId) {
                $videoIds[] = $videoId;
            }
        }

        $statsMap = $api->fetchVideosBatch($channel, $videoIds);

        foreach ($items as $item) {
            $videoId = (string) ($item['contentDetails']['videoId'] ?? '');
            if ($videoId === '') {
                continue;
            }
            $stats = $statsMap[$videoId] ?? [];
            $snippet = $item['snippet'] ?? [];
            $statistics = $stats['statistics'] ?? [];
            $contentDetails = $stats['contentDetails'] ?? [];
            $statusObj = $stats['status'] ?? [];

            $publishedAt = isset($snippet['publishedAt'])
                ? Carbon::parse($snippet['publishedAt'])
                : null;

            // YouTube Shorts aniqlash — duration < 60s + vertical (taxminiy)
            $isShort = $this->detectShort($contentDetails['duration'] ?? '');

            $video = YoutubeVideo::updateOrCreate(
                ['video_id' => $videoId],
                [
                    'youtube_channel_id' => $channel->id,
                    'title' => (string) ($snippet['title'] ?? ''),
                    'description' => (string) ($snippet['description'] ?? ''),
                    'thumbnail_url' => $snippet['thumbnails']['high']['url'] ?? null,
                    'published_at' => $publishedAt,
                    'privacy_status' => $statusObj['privacyStatus'] ?? null,
                    'is_short' => $isShort,
                    'view_count' => (int) ($statistics['viewCount'] ?? 0),
                    'like_count' => (int) ($statistics['likeCount'] ?? 0),
                    'comment_count' => (int) ($statistics['commentCount'] ?? 0),
                    'stats_updated_at' => now(),
                    'raw_payload' => array_merge($item, $stats),
                ],
            );

            // Yangi yoki title/description o'zgargan bo'lsa match qil
            if ($video->wasRecentlyCreated || $video->wasChanged(['title', 'description'])) {
                try {
                    $matcher->matchYoutubeVideo($video);
                } catch (\Throwable $e) {
                    Log::warning('ContentMatcher: youtube video match failed', [
                        'video_id' => $video->video_id,
                        'error' => $e->getMessage(),
                    ]);
                }
            }
        }

        $channel->update(['last_synced_at' => now()]);
    }

    /**
     * ISO 8601 duration (PT1M30S) — agar 60s'dan kam bo'lsa Shorts.
     */
    protected function detectShort(string $duration): bool
    {
        if ($duration === '') {
            return false;
        }
        // Soat bo'lsa qisqa emas
        if (str_contains($duration, 'H')) {
            return false;
        }
        // Daqiqa bormi
        if (preg_match('/(\d+)M/', $duration, $m)) {
            $minutes = (int) $m[1];
            if ($minutes >= 1) {
                return false;
            }
        }
        // Soniya
        if (preg_match('/(\d+)S/', $duration, $s)) {
            return ((int) $s[1]) <= 60;
        }
        return false;
    }
}
