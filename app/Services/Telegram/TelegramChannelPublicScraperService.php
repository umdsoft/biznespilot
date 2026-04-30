<?php

declare(strict_types=1);

namespace App\Services\Telegram;

use App\Models\TelegramChannel;
use App\Models\TelegramChannelPost;
use Carbon\Carbon;
use DOMDocument;
use DOMXPath;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * TelegramChannelPublicScraperService
 *
 * Public Telegram kanallar uchun `https://t.me/s/{username}` HTML preview
 * sahifasini parse qiladi va eski/joriy postlarni `TelegramChannelPost`
 * jadvaliga sinx qiladi.
 *
 * Sabab: Telegram Bot API kanaldagi POST TARIXINI o'qish imkonini bermaydi
 * — `getMessage`, `getHistory` faqat MTProto/Client API'da. Bot faqat
 * webhook'dan keyin keladigan yangi postlarni ko'radi. Shu bois bot
 * promoted bo'lgan paytdan oldingi postlar va views update'lari yo'qolgan.
 *
 * Bu service `t.me/s/{username}` sahifasidan ~20-30 oxirgi postni:
 *   - message_id (data-post atributidan)
 *   - text_preview
 *   - posted_at (time[datetime])
 *   - views (1.2K → 1200 formatda)
 *   - content_type (rasm/video/matn)
 * to'g'ridan-to'g'ri HTML'dan oladi va idempotent ravishda DB'ga yozadi.
 *
 * Cheklovlar:
 *   - Faqat PUBLIC kanal (chat_username bo'lishi shart)
 *   - Reactions ko'pincha sahifada chiqmaydi (faqat `message_reaction_count`
 *     webhook orqali keladi)
 *   - Forwards count yo'q (Bot API'da ham yo'q)
 *   - Telegram HTML'i o'zgartirilishi mumkin — guard'lar qo'yilgan
 */
class TelegramChannelPublicScraperService
{
    protected const T_ME_BASE = 'https://t.me/s/';
    protected const HTTP_TIMEOUT = 20;

    /**
     * Scrape and persist posts from t.me/s/{username}.
     *
     * @return array{scraped:int, created:int, updated:int, skipped:string|null}
     */
    public function syncChannel(TelegramChannel $channel): array
    {
        $username = $channel->chat_username;

        if (! $username) {
            return ['scraped' => 0, 'created' => 0, 'updated' => 0, 'skipped' => 'no_username'];
        }

        $url = self::T_ME_BASE . urlencode($username);

        try {
            $response = Http::withOptions(['verify' => false, 'connect_timeout' => 10])
                ->timeout(self::HTTP_TIMEOUT)
                ->withHeaders([
                    // Telegram preview sahifasi mobile UA bilan to'liqroq HTML qaytaradi
                    'User-Agent' => 'Mozilla/5.0 (compatible; BiznesPilot-Bot/1.0)',
                    'Accept' => 'text/html',
                    'Accept-Language' => 'en-US,en;q=0.9,uz;q=0.8,ru;q=0.7',
                ])
                ->get($url);
        } catch (\Throwable $e) {
            Log::warning('[ChannelScraper] HTTP error', [
                'channel_id' => $channel->id,
                'url' => $url,
                'error' => $e->getMessage(),
            ]);
            return ['scraped' => 0, 'created' => 0, 'updated' => 0, 'skipped' => 'http_error'];
        }

        if (! $response->successful()) {
            return ['scraped' => 0, 'created' => 0, 'updated' => 0, 'skipped' => 'http_' . $response->status()];
        }

        $html = $response->body();
        if (mb_strlen($html) < 500) {
            return ['scraped' => 0, 'created' => 0, 'updated' => 0, 'skipped' => 'empty_html'];
        }

        $messages = $this->parseHtml($html, $username);

        $created = 0;
        $updated = 0;
        foreach ($messages as $msg) {
            $result = $this->upsertPost($channel, $msg);
            if ($result === 'created') {
                $created++;
            } elseif ($result === 'updated') {
                $updated++;
            }
        }

        Log::info('[ChannelScraper] Sync complete', [
            'channel_id' => $channel->id,
            'username' => $username,
            'scraped' => count($messages),
            'created' => $created,
            'updated' => $updated,
        ]);

        return [
            'scraped' => count($messages),
            'created' => $created,
            'updated' => $updated,
            'skipped' => null,
        ];
    }

    /**
     * Parse t.me/s HTML and extract messages.
     *
     * @return array<int, array{message_id:int, text:?string, posted_at:?Carbon, views:int, content_type:string, has_photo:bool, has_video:bool}>
     */
    protected function parseHtml(string $html, string $expectedUsername): array
    {
        $dom = new DOMDocument();
        // libxml errors'ni o'chiramiz — Telegram HTML'i strict valid emas
        libxml_use_internal_errors(true);
        $dom->loadHTML('<?xml encoding="UTF-8">' . $html);
        libxml_clear_errors();

        $xpath = new DOMXPath($dom);
        $nodes = $xpath->query("//div[contains(@class, 'tgme_widget_message') and @data-post]");

        if ($nodes === false || $nodes->length === 0) {
            return [];
        }

        $results = [];
        foreach ($nodes as $node) {
            $dataPost = $node->getAttribute('data-post'); // "username/123"
            if (! $dataPost) {
                continue;
            }

            $parts = explode('/', $dataPost);
            if (count($parts) !== 2) {
                continue;
            }
            // Tenant guard — boshqa kanal posti aralashib qolmasin
            if (mb_strtolower($parts[0]) !== mb_strtolower($expectedUsername)) {
                continue;
            }
            $messageId = (int) $parts[1];
            if ($messageId <= 0) {
                continue;
            }

            // Text
            $text = null;
            $textNodes = $xpath->query(".//div[contains(@class, 'tgme_widget_message_text')]", $node);
            if ($textNodes && $textNodes->length > 0) {
                $text = trim($textNodes->item(0)->textContent);
                $text = $text !== '' ? mb_substr($text, 0, 280) : null;
            }

            // posted_at — <time datetime="...">
            $postedAt = null;
            $timeNodes = $xpath->query(".//time[@datetime]", $node);
            if ($timeNodes && $timeNodes->length > 0) {
                $dt = $timeNodes->item(0)->getAttribute('datetime');
                try {
                    $postedAt = Carbon::parse($dt);
                } catch (\Throwable) {
                    $postedAt = null;
                }
            }

            // views — "1.2K", "523", "1.5M"
            $views = 0;
            $viewNodes = $xpath->query(".//span[contains(@class, 'tgme_widget_message_views')]", $node);
            if ($viewNodes && $viewNodes->length > 0) {
                $views = $this->parseViewCount($viewNodes->item(0)->textContent);
            }

            // Content type
            $hasPhoto = $xpath->query(".//*[contains(@class, 'tgme_widget_message_photo')]", $node)->length > 0;
            $hasVideo = $xpath->query(".//*[contains(@class, 'tgme_widget_message_video')]", $node)->length > 0;
            $contentType = match (true) {
                $hasVideo => TelegramChannelPost::TYPE_VIDEO,
                $hasPhoto => TelegramChannelPost::TYPE_PHOTO,
                $text !== null => TelegramChannelPost::TYPE_TEXT,
                default => TelegramChannelPost::TYPE_OTHER,
            };

            // Media URL — t.me/s sahifasida photo va video thumb'lar
            // background-image:url('https://cdn4.cdn-telegram.org/...') sifatida keladi.
            // Bu CDN URL public va token kerak emas.
            $mediaUrl = $this->extractMediaUrl($xpath, $node);

            $results[] = [
                'message_id' => $messageId,
                'text' => $text,
                'posted_at' => $postedAt,
                'views' => $views,
                'content_type' => $contentType,
                'has_photo' => $hasPhoto,
                'has_video' => $hasVideo,
                'media_url' => $mediaUrl,
            ];
        }

        return $results;
    }

    /**
     * Birinchi rasm/video thumbnail URL'ni style atributidan extract qiladi.
     * t.me/s formati:
     *   <a class="tgme_widget_message_photo_wrap" style="background-image:url('https://...')"></a>
     *   <i class="tgme_widget_message_video_thumb" style="background-image:url('https://...')"></i>
     */
    protected function extractMediaUrl(DOMXPath $xpath, \DOMNode $node): ?string
    {
        // Photo va video thumb wrapper'larini bitta query bilan topamiz
        $mediaNodes = $xpath->query(
            ".//a[contains(@class, 'tgme_widget_message_photo_wrap')]"
            . " | .//i[contains(@class, 'tgme_widget_message_video_thumb')]"
            . " | .//*[contains(@class, 'link_preview_image')]",
            $node
        );

        if ($mediaNodes === false || $mediaNodes->length === 0) {
            return null;
        }

        foreach ($mediaNodes as $mediaNode) {
            if (! $mediaNode instanceof \DOMElement) {
                continue;
            }
            $style = $mediaNode->getAttribute('style');
            if (! $style) {
                continue;
            }

            // background-image:url('https://...')
            // Q'avs ichidagi qiymatni olamiz — single quote, double quote yoki bo'sh
            if (preg_match("/background-image\s*:\s*url\(\s*['\"]?([^'\"\\)]+)['\"]?\s*\)/iu", $style, $m)) {
                $url = trim($m[1]);
                if (preg_match('#^https?://#i', $url)) {
                    return mb_substr($url, 0, 1000); // DB column limit
                }
            }
        }

        return null;
    }

    /**
     * "1.2K" → 1200, "523" → 523, "1.5M" → 1500000
     */
    protected function parseViewCount(string $raw): int
    {
        $raw = trim($raw);
        if ($raw === '') {
            return 0;
        }

        if (preg_match('/^([\d.]+)\s*([KMB])?$/iu', $raw, $m)) {
            $value = (float) $m[1];
            $multiplier = match (strtoupper($m[2] ?? '')) {
                'K' => 1000,
                'M' => 1000000,
                'B' => 1000000000,
                default => 1,
            };
            return (int) round($value * $multiplier);
        }

        // Faqat raqamlarni olib qoldirib parse qilamiz (bo'shliq, vergul va h.k.)
        $cleaned = preg_replace('/[^\d]/', '', $raw);
        return $cleaned !== '' ? (int) $cleaned : 0;
    }

    /**
     * Idempotent upsert. Mavjud post bo'lsa — faqat views/text yangilanadi
     * (muhim qiymatlar webhook'dan kelgan bo'lsa o'zgartirmaymiz).
     */
    protected function upsertPost(TelegramChannel $channel, array $msg): string
    {
        $post = TelegramChannelPost::firstOrNew([
            'telegram_channel_id' => $channel->id,
            'message_id' => $msg['message_id'],
        ]);

        if (! $post->exists) {
            $post->fill([
                'posted_at' => $msg['posted_at'] ?? now(),
                'content_type' => $msg['content_type'],
                'media_count' => ($msg['has_photo'] || $msg['has_video']) ? 1 : 0,
                'text_preview' => $msg['text'],
                'media_url' => $msg['media_url'] ?? null,
                'views' => $msg['views'],
                'reactions_count' => 0,
                'forwards_count' => 0,
                'raw_payload' => [
                    'source' => 't.me/s scraper',
                    'scraped_at' => now()->toIso8601String(),
                ],
            ])->save();
            return 'created';
        }

        // Mavjud: views faqat o'sishi mumkin (Telegram'da kamaymaydi).
        // Webhook'dan kelgan qiymat bo'lsa, scraper qiymati past bo'lsa — saqlaymiz.
        $patch = [];
        if ($msg['views'] > (int) $post->views) {
            $patch['views'] = $msg['views'];
        }
        // Text webhook'dan kelmasa va scraper'dan bor bo'lsa
        if (! $post->text_preview && $msg['text']) {
            $patch['text_preview'] = $msg['text'];
        }
        // Media URL: webhook'dan kelmaydigan ma'lumot — scraper birinchi marta
        // topganda yoki yangilangan bo'lsa, doim yangilab boramiz (URL Telegram CDN'da
        // ba'zan o'zgaradi).
        if (! empty($msg['media_url']) && $msg['media_url'] !== $post->media_url) {
            $patch['media_url'] = $msg['media_url'];
        }

        if (! empty($patch)) {
            $post->update($patch);
            return 'updated';
        }

        return 'unchanged';
    }
}
