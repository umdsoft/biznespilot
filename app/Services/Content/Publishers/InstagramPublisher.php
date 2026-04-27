<?php

declare(strict_types=1);

namespace App\Services\Content\Publishers;

use App\Models\ContentCalendar;
use App\Models\InstagramAccount;
use App\Services\Content\ContentHashtagGenerator;
use App\Services\Content\ContentWatermarker;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Content reja item'ni Instagram Business akkauntiga to'g'ridan-to'g'ri post qilish.
 *
 * Meta Graph API "Content Publishing API" ikki bosqichli flow ishlatadi:
 *   1. POST /{ig-user-id}/media          → creation_id qaytaradi (container)
 *   2. POST /{ig-user-id}/media_publish  → media_id qaytaradi (haqiqiy post)
 *
 * Qo'llab-quvvatlanadigan media turlari:
 *   - IMAGE      (image_url)
 *   - VIDEO      (video_url, max 60s feed yoki 90s reels)
 *   - REELS      (media_type=REELS, video_url)
 *   - CAROUSEL   (children[]) — kelajak iteratsiyada
 *
 * Cheklov: rasm/video URL public HTTPS bo'lishi shart (Telegram bilan teng).
 */
class InstagramPublisher
{
    private string $graphApiUrl;

    public function __construct(
        protected ContentHashtagGenerator $hashtagGenerator,
        protected ContentWatermarker $watermarker,
    ) {
        $this->graphApiUrl = 'https://graph.facebook.com/'
            . config('services.meta.api_version', 'v24.0');
    }

    /**
     * Reja item'ni Instagram'ga publish qilish.
     *
     * @return array{success: bool, media_id?: string, post_url?: string, error?: string, message?: string}
     */
    public function publish(ContentCalendar $item): array
    {
        // 1. Akkaunt + token
        $account = $this->resolveAccount($item);
        if (! $account) {
            return ['success' => false, 'error' => 'no_instagram_account'];
        }
        if (empty($account->access_token)) {
            return ['success' => false, 'error' => 'no_access_token'];
        }

        // 2. Caption tayyorlash (hashtag + watermark)
        $caption = $this->buildCaption($item);

        // 3. Media URL
        $mediaUrl = $this->primaryMediaUrl($item);
        $mediaUrl = $mediaUrl ? $this->resolvePublicUrl($mediaUrl) : null;

        // 4. Media turi aniqlash
        $kind = $this->resolveKind($item, $mediaUrl);

        if ($kind !== 'TEXT' && ! $mediaUrl) {
            return ['success' => false, 'error' => 'media_url_missing'];
        }

        // Instagram TEXT-only post'ni qo'llamaydi — agar matn bo'lsa, photo placeholder kerak
        if ($kind === 'TEXT') {
            return [
                'success' => false,
                'error' => 'instagram_requires_media',
                'message' => "Instagram faqat rasm yoki video bilan post qabul qiladi",
            ];
        }

        try {
            // BOSQICH 1: Container yaratish
            $container = $this->createContainer($account, $kind, $mediaUrl, $caption);
            if (empty($container['id'])) {
                return [
                    'success' => false,
                    'error' => 'container_creation_failed',
                    'details' => $container,
                ];
            }

            // BOSQICH 2: Container holatini tekshirish (video uchun)
            if (in_array($kind, ['VIDEO', 'REELS'], true)) {
                $ready = $this->waitForContainerReady($account, $container['id']);
                if (! $ready) {
                    return [
                        'success' => false,
                        'error' => 'container_processing_timeout',
                    ];
                }
            }

            // BOSQICH 3: Publish
            $publish = $this->publishContainer($account, $container['id']);
            if (empty($publish['id'])) {
                return [
                    'success' => false,
                    'error' => 'publish_failed',
                    'details' => $publish,
                ];
            }

            $mediaId = (string) $publish['id'];

            // BOSQICH 4: Post URL — akkaunt API'dan permalink
            $postUrl = $this->fetchPermalink($account, $mediaId);

            // 5. ContentCalendar'ni yangilash
            $item->update([
                'status' => 'published',
                'published_at' => now(),
                'external_post_id' => $mediaId,
                'post_url' => $postUrl,
                'channel' => 'instagram',
                'platform' => 'instagram',
                'match_method' => 'direct',
                'match_score' => 1.0,
                'matched_at' => now(),
            ]);

            Log::info('InstagramPublisher: published', [
                'item_id' => $item->id,
                'account_id' => $account->id,
                'media_id' => $mediaId,
            ]);

            return [
                'success' => true,
                'media_id' => $mediaId,
                'post_url' => $postUrl,
            ];
        } catch (\Throwable $e) {
            Log::error('InstagramPublisher: exception', [
                'item_id' => $item->id,
                'error' => $e->getMessage(),
            ]);
            return ['success' => false, 'error' => 'exception', 'message' => $e->getMessage()];
        }
    }

    // ============================================================

    public function buildCaption(ContentCalendar $item): string
    {
        $base = trim((string) ($item->content_text ?? $item->content ?? $item->description ?? $item->title ?? ''));

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
        // Instagram caption limit: 2200 belgi
        if (mb_strlen($combined) > 2150) {
            $combined = mb_substr($combined, 0, 2150);
        }

        return $this->watermarker->embed($combined, (string) $item->id);
    }

    protected function createContainer(InstagramAccount $account, string $kind, ?string $mediaUrl, string $caption): array
    {
        $payload = [
            'access_token' => $account->access_token,
            'caption' => $caption,
        ];

        switch ($kind) {
            case 'IMAGE':
                $payload['image_url'] = $mediaUrl;
                break;
            case 'VIDEO':
                $payload['media_type'] = 'VIDEO';
                $payload['video_url'] = $mediaUrl;
                break;
            case 'REELS':
                $payload['media_type'] = 'REELS';
                $payload['video_url'] = $mediaUrl;
                break;
        }

        $response = Http::asForm()
            ->timeout(60)
            ->post("{$this->graphApiUrl}/{$account->instagram_id}/media", $payload);

        return $response->json() ?? [];
    }

    /**
     * Video container ready bo'lishini kutamiz (max 90s).
     */
    protected function waitForContainerReady(InstagramAccount $account, string $containerId): bool
    {
        $attempts = 0;
        $maxAttempts = 18; // 18 * 5s = 90s
        while ($attempts < $maxAttempts) {
            $resp = Http::timeout(15)->get("{$this->graphApiUrl}/{$containerId}", [
                'fields' => 'status_code,status',
                'access_token' => $account->access_token,
            ])->json();

            $status = $resp['status_code'] ?? $resp['status'] ?? null;
            if ($status === 'FINISHED') {
                return true;
            }
            if (in_array($status, ['ERROR', 'EXPIRED'], true)) {
                return false;
            }

            sleep(5);
            $attempts++;
        }
        return false;
    }

    protected function publishContainer(InstagramAccount $account, string $containerId): array
    {
        $response = Http::asForm()
            ->timeout(30)
            ->post("{$this->graphApiUrl}/{$account->instagram_id}/media_publish", [
                'creation_id' => $containerId,
                'access_token' => $account->access_token,
            ]);

        return $response->json() ?? [];
    }

    protected function fetchPermalink(InstagramAccount $account, string $mediaId): ?string
    {
        $response = Http::timeout(15)->get("{$this->graphApiUrl}/{$mediaId}", [
            'fields' => 'permalink',
            'access_token' => $account->access_token,
        ]);
        $data = $response->json() ?? [];
        return $data['permalink'] ?? null;
    }

    protected function resolveAccount(ContentCalendar $item): ?InstagramAccount
    {
        if (! empty($item->channel_account)) {
            $username = ltrim((string) $item->channel_account, '@');
            $account = InstagramAccount::where('business_id', $item->business_id)
                ->where('username', $username)
                ->where('is_active', true)
                ->first();
            if ($account) {
                return $account;
            }
        }
        return InstagramAccount::where('business_id', $item->business_id)
            ->where('is_active', true)
            ->orderByDesc('is_primary')
            ->orderByDesc('connected_at')
            ->first();
    }

    protected function primaryMediaUrl(ContentCalendar $item): ?string
    {
        $urls = is_array($item->media_urls) ? $item->media_urls : [];
        if (empty($urls)) {
            return null;
        }
        $first = $urls[0];
        if (is_string($first)) {
            return $first;
        }
        if (is_array($first)) {
            return (string) ($first['url'] ?? $first['path'] ?? '');
        }
        return null;
    }

    protected function resolvePublicUrl(string $url): string
    {
        if (str_starts_with($url, 'https://')) {
            return $url;
        }
        // Instagram majburiy HTTPS — local URL'ni APP_URL bilan to'ldiramiz
        $appUrl = rtrim((string) config('app.url'), '/');
        if (str_starts_with($url, 'http://')) {
            return preg_replace('#^http://#', 'https://', $url) ?? $url;
        }
        if (str_starts_with($url, '/')) {
            return $appUrl . $url;
        }
        return $appUrl . '/storage/' . ltrim($url, '/');
    }

    protected function resolveKind(ContentCalendar $item, ?string $mediaUrl): string
    {
        $type = strtolower((string) ($item->content_type ?? 'post'));
        if (in_array($type, ['reel', 'reels'], true)) {
            return 'REELS';
        }
        if ($type === 'video') {
            return 'VIDEO';
        }
        if (in_array($type, ['photo', 'image', 'post'], true) && $mediaUrl) {
            return 'IMAGE';
        }
        // Media URL bo'lsa va turi noaniq — extension bo'yicha
        if ($mediaUrl) {
            $ext = strtolower(pathinfo(parse_url($mediaUrl, PHP_URL_PATH) ?? '', PATHINFO_EXTENSION));
            if (in_array($ext, ['mp4', 'mov', 'webm'], true)) {
                return 'VIDEO';
            }
            if (in_array($ext, ['jpg', 'jpeg', 'png', 'webp', 'gif'], true)) {
                return 'IMAGE';
            }
        }
        return 'TEXT';
    }
}
