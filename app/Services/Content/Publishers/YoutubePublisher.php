<?php

declare(strict_types=1);

namespace App\Services\Content\Publishers;

use App\Models\ContentCalendar;
use App\Models\YoutubeChannel;
use App\Services\Content\ContentHashtagGenerator;
use App\Services\Content\ContentWatermarker;
use App\Services\Youtube\YoutubeApiClient;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * YouTube videolarni avtomatik publish qilish.
 *
 * MUHIM: Direct video upload `videos.insert` 1,600 quota unit ishlatadi (10K/kun
 *  default). Bu cheklovga sezgir, shuning uchun bu Publisher 2 fazada ishlaydi:
 *
 *   - PHASE A (joriy): Mavjud video uchun metadata yangilash (title/description),
 *     ContentCalendar bilan bog'lash. Foydalanuvchi videoni qo'lda yuklasa, shu
 *     servis title/description'ga hashtag + watermark qo'shadi.
 *
 *   - PHASE B (kelajak): To'liq direct upload — resumable upload session,
 *     video file streaming, OAuth quota monitoring.
 *
 * Hozirgi `publish()` faqat metadata-only rejimni qo'llab-quvvatlaydi:
 *  agar foydalanuvchi `external_post_id` qo'lda kiritsa (mavjud video URL),
 *  sistema o'sha videoni topib title/description'ni yangilaydi va match yozadi.
 */
class YoutubePublisher
{
    public function __construct(
        protected ContentHashtagGenerator $hashtagGenerator,
        protected ContentWatermarker $watermarker,
        protected YoutubeApiClient $api,
    ) {}

    /**
     * Phase A — metadata-only publish (mavjud videoni linklash).
     * Phase B (direct upload) keyingi iteratsiyada.
     *
     * @return array{success: bool, video_id?: string, post_url?: string, error?: string}
     */
    public function publish(ContentCalendar $item): array
    {
        $channel = $this->resolveChannel($item);
        if (! $channel) {
            return ['success' => false, 'error' => 'no_youtube_channel'];
        }

        // Phase B (direct upload) hozircha faol emas — explicit response
        if (empty($item->external_post_id)) {
            return [
                'success' => false,
                'error' => 'youtube_not_implemented',
                'message' => 'YouTube direct upload kelgusi versiyada — hozircha video YouTube\'da qo\'lda yuklang, sistema avtomatik aniqlaydi (sync har 30 daqiqada)',
            ];
        }

        // Mavjud video uchun metadata yangilash (Phase A)
        $videoId = (string) $item->external_post_id;
        $description = $this->buildDescription($item);

        $token = $this->api->refreshAccessToken($channel) ?? $channel->access_token;
        if (! $token) {
            return ['success' => false, 'error' => 'no_access_token'];
        }

        try {
            $response = Http::withToken($token)
                ->timeout(30)
                ->put('https://www.googleapis.com/youtube/v3/videos?part=snippet', [
                    'id' => $videoId,
                    'snippet' => [
                        'title' => (string) $item->title,
                        'description' => $description,
                        'categoryId' => '22', // People & Blogs (default)
                    ],
                ]);

            if (! $response->successful()) {
                Log::warning('YoutubePublisher: metadata update failed', [
                    'video_id' => $videoId,
                    'status' => $response->status(),
                ]);
                return [
                    'success' => false,
                    'error' => 'metadata_update_failed',
                    'details' => $response->json(),
                ];
            }
        } catch (\Throwable $e) {
            return ['success' => false, 'error' => 'exception', 'message' => $e->getMessage()];
        }

        $postUrl = "https://www.youtube.com/watch?v={$videoId}";

        $item->update([
            'status' => 'published',
            'published_at' => $item->published_at ?? now(),
            'external_post_id' => $videoId,
            'post_url' => $postUrl,
            'channel' => 'youtube',
            'platform' => 'youtube',
            'match_method' => 'direct',
            'match_score' => 1.0,
            'matched_at' => now(),
        ]);

        return [
            'success' => true,
            'video_id' => $videoId,
            'post_url' => $postUrl,
        ];
    }

    public function buildDescription(ContentCalendar $item): string
    {
        $base = trim((string) ($item->content_text ?? $item->content ?? $item->description ?? ''));

        $userHashtags = is_array($item->hashtags) ? $item->hashtags : [];
        $userHashtags = array_filter(array_map(
            fn ($t) => '#' . ltrim((string) $t, '#'),
            $userHashtags,
        ));

        $autoTag = $this->hashtagGenerator->ensureForItem($item);

        $parts = [$base];
        if (! empty($userHashtags)) {
            $parts[] = implode(' ', array_unique($userHashtags));
        }
        if (! empty($autoTag) && ! str_contains($base, $autoTag)) {
            $parts[] = $autoTag;
        }

        $combined = trim(implode("\n\n", array_filter($parts)));
        // YouTube description limit: 5000 belgi
        if (mb_strlen($combined) > 4900) {
            $combined = mb_substr($combined, 0, 4900);
        }

        return $this->watermarker->embed($combined, (string) $item->id);
    }

    protected function resolveChannel(ContentCalendar $item): ?YoutubeChannel
    {
        return YoutubeChannel::where('business_id', $item->business_id)
            ->where('is_active', true)
            ->orderByDesc('connected_at')
            ->first();
    }
}
