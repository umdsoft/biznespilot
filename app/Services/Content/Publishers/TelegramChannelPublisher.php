<?php

declare(strict_types=1);

namespace App\Services\Content\Publishers;

use App\Models\ContentCalendar;
use App\Models\TelegramChannel;
use App\Services\Content\ContentHashtagGenerator;
use App\Services\Content\ContentWatermarker;
use App\Services\Telegram\SystemBotService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Content reja item'ni Telegram kanaliga to'g'ridan-to'g'ri post qilish.
 *
 * Bu sinf 100% aniq match'ni ta'minlaydi: post API javobida `message_id`
 * darhol qaytariladi, hech qanday tahmin kerak emas.
 *
 * Qo'llab-quvvatlanadigan content_type:
 *   - text         → sendMessage
 *   - photo        → sendPhoto (URL or local file)
 *   - video        → sendVideo
 *   - document     → sendDocument
 *
 * Auth: System Bot tokeni (TELEGRAM_SYSTEM_BOT_TOKEN) — bot kanalga admin
 * sifatida qo'shilgan bo'lishi shart (TelegramChannel.admin_status='administrator').
 */
class TelegramChannelPublisher
{
    public function __construct(
        protected ContentHashtagGenerator $hashtagGenerator,
        protected ContentWatermarker $watermarker,
        protected SystemBotService $bot,
    ) {}

    /**
     * Reja item'ni o'rnatilgan kanalga yuborish.
     *
     * @return array{success: bool, message_id?: int, post_url?: string, error?: string}
     */
    public function publish(ContentCalendar $item): array
    {
        // 1. Kanal va admin huquqlarini tekshirish
        $channel = $this->resolveChannel($item);
        if (! $channel) {
            return ['success' => false, 'error' => 'no_channel_connected'];
        }
        if ($channel->admin_status !== TelegramChannel::STATUS_ADMIN) {
            return ['success' => false, 'error' => 'bot_not_admin', 'channel_id' => $channel->id];
        }

        // 2. Hashtag va watermark bilan boyitilgan matnni tayyorlash
        $caption = $this->buildPublishText($item);

        // 3. Content type'ga qarab yuborish
        $contentType = (string) ($item->content_type ?? 'text');
        $mediaUrl = $this->primaryMediaUrl($item);

        $token = $this->bot->getToken();
        $chatId = (int) $channel->telegram_chat_id;

        try {
            switch ($contentType) {
                case 'photo':
                case 'image':
                    $response = $this->sendPhoto($token, $chatId, $mediaUrl, $caption);
                    break;
                case 'video':
                    $response = $this->sendVideo($token, $chatId, $mediaUrl, $caption);
                    break;
                case 'document':
                case 'file':
                    $response = $this->sendDocument($token, $chatId, $mediaUrl, $caption);
                    break;
                default:
                    $response = $this->sendMessage($token, $chatId, $caption);
            }
        } catch (\Throwable $e) {
            Log::error('TelegramChannelPublisher: HTTP error', [
                'item_id' => $item->id,
                'error' => $e->getMessage(),
            ]);
            return ['success' => false, 'error' => 'http_error', 'message' => $e->getMessage()];
        }

        if (! ($response['ok'] ?? false)) {
            Log::warning('TelegramChannelPublisher: API returned not ok', [
                'item_id' => $item->id,
                'response' => $response,
            ]);
            return [
                'success' => false,
                'error' => 'telegram_api_error',
                'description' => $response['description'] ?? 'unknown',
                'error_code' => $response['error_code'] ?? null,
            ];
        }

        $messageId = (int) ($response['result']['message_id'] ?? 0);
        $postUrl = $this->buildPostUrl($channel, $messageId);

        // 4. ContentCalendar'ni 'published' ga o'tkazish
        $item->update([
            'status' => 'published',
            'published_at' => now(),
            'external_post_id' => (string) $messageId,
            'post_url' => $postUrl,
            'channel' => 'telegram',
            'platform' => 'telegram',
            'match_method' => 'direct',
            'match_score' => 1.0,
            'matched_post_id' => null,
            'matched_at' => now(),
        ]);

        Log::info('TelegramChannelPublisher: published', [
            'item_id' => $item->id,
            'channel_id' => $channel->id,
            'message_id' => $messageId,
        ]);

        return [
            'success' => true,
            'message_id' => $messageId,
            'post_url' => $postUrl,
        ];
    }

    // ============================================================
    // Matn tayyorlash — hashtag (visible) + watermark (invisible)
    // ============================================================

    /**
     * Reja matnini publish'ga tayyorlash:
     *   - asl matn
     *   - + qo'shimcha hashtag'lar (foydalanuvchi qo'lda yozgan)
     *   - + auto_hashtag (sistema generatsiya qilgan, brand+shortcode)
     *   - + watermark (ko'rinmas Unicode)
     */
    public function buildPublishText(ContentCalendar $item): string
    {
        $base = (string) ($item->content_text ?? $item->content ?? $item->description ?? '');
        if (trim($base) === '') {
            $base = (string) ($item->title ?? '');
        }

        // Foydalanuvchi qo'lda yozgan hashtag'lar
        $userHashtags = is_array($item->hashtags) ? $item->hashtags : [];
        $userHashtags = array_filter(array_map(
            fn ($t) => '#' . ltrim((string) $t, '#'),
            $userHashtags,
        ));

        // Auto-hashtag (sistema)
        $autoTag = $this->hashtagGenerator->ensureForItem($item);

        $parts = [$base];
        if (! empty($userHashtags)) {
            $parts[] = implode(' ', array_unique($userHashtags));
        }
        if (! empty($autoTag) && ! str_contains($base, $autoTag)) {
            $parts[] = $autoTag;
        }

        $combined = trim(implode("\n\n", array_filter($parts)));

        // Watermark — invisible Unicode plan ID prefix
        return $this->watermarker->embed($combined, (string) $item->id);
    }

    // ============================================================
    // Telegram Bot API chaqirishlar
    // ============================================================

    protected function sendMessage(string $token, int $chatId, string $text): array
    {
        return Http::asForm()
            ->timeout(15)
            ->post("https://api.telegram.org/bot{$token}/sendMessage", [
                'chat_id' => $chatId,
                'text' => $text,
                'parse_mode' => 'HTML',
                'disable_web_page_preview' => false,
            ])->json() ?? [];
    }

    protected function sendPhoto(string $token, int $chatId, ?string $url, string $caption): array
    {
        if (! $url) {
            return ['ok' => false, 'description' => 'photo_url_missing'];
        }
        return Http::asForm()
            ->timeout(30)
            ->post("https://api.telegram.org/bot{$token}/sendPhoto", [
                'chat_id' => $chatId,
                'photo' => $this->resolveTelegramRemoteUrl($url),
                'caption' => $caption,
                'parse_mode' => 'HTML',
            ])->json() ?? [];
    }

    protected function sendVideo(string $token, int $chatId, ?string $url, string $caption): array
    {
        if (! $url) {
            return ['ok' => false, 'description' => 'video_url_missing'];
        }
        return Http::asForm()
            ->timeout(60)
            ->post("https://api.telegram.org/bot{$token}/sendVideo", [
                'chat_id' => $chatId,
                'video' => $this->resolveTelegramRemoteUrl($url),
                'caption' => $caption,
                'parse_mode' => 'HTML',
                'supports_streaming' => true,
            ])->json() ?? [];
    }

    protected function sendDocument(string $token, int $chatId, ?string $url, string $caption): array
    {
        if (! $url) {
            return ['ok' => false, 'description' => 'document_url_missing'];
        }
        return Http::asForm()
            ->timeout(60)
            ->post("https://api.telegram.org/bot{$token}/sendDocument", [
                'chat_id' => $chatId,
                'document' => $this->resolveTelegramRemoteUrl($url),
                'caption' => $caption,
                'parse_mode' => 'HTML',
            ])->json() ?? [];
    }

    /**
     * Lokal storage URL'sini tashqariga ochiq URL'ga aylantirish.
     * Telegram remote URL'ni server'dan o'qiy oladi (https kerak).
     */
    protected function resolveTelegramRemoteUrl(string $url): string
    {
        if (str_starts_with($url, 'http://') || str_starts_with($url, 'https://')) {
            return $url;
        }
        // Storage path ('telegram/funnel/...' yoki '/storage/...')
        $appUrl = rtrim((string) config('app.url'), '/');
        if (str_starts_with($url, '/')) {
            return $appUrl . $url;
        }
        return $appUrl . '/storage/' . ltrim($url, '/');
    }

    // ============================================================

    protected function resolveChannel(ContentCalendar $item): ?TelegramChannel
    {
        // Aniq channel_account belgilangan bo'lsa, shu username
        if (! empty($item->channel_account)) {
            $username = ltrim((string) $item->channel_account, '@');
            $channel = TelegramChannel::where('business_id', $item->business_id)
                ->where('chat_username', $username)
                ->where('is_active', true)
                ->first();
            if ($channel) {
                return $channel;
            }
        }

        // Default — biznesning aktiv kanali
        return TelegramChannel::where('business_id', $item->business_id)
            ->where('is_active', true)
            ->where('admin_status', TelegramChannel::STATUS_ADMIN)
            ->orderBy('connected_at', 'desc')
            ->first();
    }

    protected function primaryMediaUrl(ContentCalendar $item): ?string
    {
        $urls = is_array($item->media_urls) ? $item->media_urls : [];
        if (empty($urls)) {
            return null;
        }
        // Birinchi element — string yoki ['url' => ...] obyekti bo'lishi mumkin
        $first = $urls[0];
        if (is_string($first)) {
            return $first;
        }
        if (is_array($first)) {
            return (string) ($first['url'] ?? $first['path'] ?? '');
        }
        return null;
    }

    protected function buildPostUrl(TelegramChannel $channel, int $messageId): ?string
    {
        if ($messageId <= 0) {
            return null;
        }
        if (! empty($channel->chat_username)) {
            $username = ltrim((string) $channel->chat_username, '@');
            return "https://t.me/{$username}/{$messageId}";
        }
        $chatId = (string) $channel->telegram_chat_id;
        if (str_starts_with($chatId, '-100')) {
            $internal = substr($chatId, 4);
            return "https://t.me/c/{$internal}/{$messageId}";
        }
        return null;
    }
}
