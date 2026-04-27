<?php

declare(strict_types=1);

namespace App\Services\Content;

use App\Models\Business;
use App\Models\ContentCalendar;
use Illuminate\Support\Str;

/**
 * Content reja item uchun unique tracking hashtag generatsiya qiladi.
 *
 * Format: #{brand}_{topic}_{shortcode}
 * Misol:  #mybiz_yangiaksiya_a3f9
 *
 * - brand:     biznesning Telegram channel username yoki Business slug (max 12 char)
 * - topic:     reja sarlavhasidan tabiiy slug (max 15 char, stop-word'lar olib tashlanadi)
 * - shortcode: ContentCalendar UUID birinchi 4 hex belgi (collision: 1/65k bir biznesda)
 *
 * Maqsad — visible tracking marker:
 *  - foydalanuvchi ko'radi va brendi sifatida qoldirishni xohlaydi
 *  - SEO uchun foydali (Telegram/Instagram qidiruvida brend topiladi)
 *  - Webhook'da regex bilan extract qilinadi → exact plan ID match
 */
class ContentHashtagGenerator
{
    /**
     * Stop-word'lar uz/ru/en — topic'da olib tashlanadi.
     */
    protected const STOP_WORDS = [
        'va', 'bilan', 'uchun', 'haqida', 'orqali', 'kun', 'kuni',
        'и', 'для', 'на', 'в', 'с', 'из', 'по', 'к', 'до',
        'and', 'for', 'with', 'the', 'a', 'an', 'of', 'to', 'in', 'on',
    ];

    /**
     * Asosiy hashtag generatsiya — DB'ga ham yozish uchun.
     */
    public function generate(ContentCalendar $item): string
    {
        $business = $item->business ?? Business::find($item->business_id);
        if (! $business) {
            return $this->fallback($item);
        }

        $brand = $this->normalizeBrand($business);
        $topic = $this->extractTopic($item);
        $shortcode = $this->shortcode($item);

        return "#{$brand}_{$topic}_{$shortcode}";
    }

    /**
     * Item save qilinganda chaqiriladi — `auto_hashtag` ustuniga yoziladi.
     * Idempotent: agar mavjud bo'lsa qaytaradi.
     */
    public function ensureForItem(ContentCalendar $item): string
    {
        if (! empty($item->auto_hashtag)) {
            return $item->auto_hashtag;
        }
        $hashtag = $this->generate($item);
        $item->update(['auto_hashtag' => $hashtag]);
        return $hashtag;
    }

    /**
     * Brand prefix — 1) Telegram channel username, 2) Business slug, 3) Business name.
     */
    public function normalizeBrand(Business $business): string
    {
        // 1. Telegram kanalining username (eng tabiiy)
        if (method_exists($business, 'telegramChannels')) {
            $channel = $business->telegramChannels()->where('is_active', true)->first();
            if ($channel && ! empty($channel->chat_username)) {
                $brand = $this->slugify($channel->chat_username, 12);
                if ($brand !== '') {
                    return $brand;
                }
            }
        }

        // 2. Business slug
        if (! empty($business->slug)) {
            $brand = $this->slugify($business->slug, 12);
            if ($brand !== '') {
                return $brand;
            }
        }

        // 3. Business name'dan slug
        if (! empty($business->name)) {
            $brand = $this->slugify($business->name, 12);
            if ($brand !== '') {
                return $brand;
            }
        }

        // 4. ID ning birinchi 8 belgisi — fallback
        return 'biz' . substr(str_replace('-', '', (string) $business->id), 0, 5);
    }

    /**
     * Topic slug — plan title'dan stop-word'lar olib tashlangan.
     */
    protected function extractTopic(ContentCalendar $item): string
    {
        $title = trim((string) ($item->title ?? ''));
        if ($title === '') {
            return 'post';
        }

        // Lowercase + transliteration
        $normalized = mb_strtolower(Str::ascii($title));
        $words = preg_split('/[^a-z0-9]+/u', $normalized) ?: [];

        // Stop-word'lar va qisqa so'zlarni filtrlash
        $words = array_filter($words, function ($w) {
            return mb_strlen($w) >= 3 && ! in_array($w, self::STOP_WORDS, true);
        });

        if (empty($words)) {
            return 'post';
        }

        // Eng kuchli 1-2 so'z (max 15 char)
        $topic = implode('', array_slice(array_values($words), 0, 2));
        $topic = substr($topic, 0, 15);
        return $topic !== '' ? $topic : 'post';
    }

    /**
     * Plan UUID birinchi 4 hex belgi.
     * Bir biznes ichida collision: 65,536 ta unique kombinatsiya.
     */
    public function shortcode(ContentCalendar $item): string
    {
        $id = (string) $item->id;
        $hex = preg_replace('/[^a-f0-9]/i', '', $id);
        return strtolower(substr($hex, 0, 4));
    }

    /**
     * Hashtag'dan shortcode'ni extract qilish (matcher uchun).
     *
     * Brand match qilmasdan ham ishlaydi — chunki shortcode + business_id
     * birgalikda DB'da unique.
     */
    public function extractShortcode(string $text, string $brand): ?string
    {
        // #{brand}_{any}_{4hex}
        $pattern = '/#' . preg_quote($brand, '/') . '_[a-z0-9]+_([a-f0-9]{4})\b/iu';
        if (preg_match($pattern, $text, $m)) {
            return strtolower($m[1]);
        }
        return null;
    }

    /**
     * Telegram entity'lardan hashtag'ni topish (text_preview cheklangan bo'lsa
     * to'liq matn yoki entities orqali aniqroq topiladi).
     */
    public function findInEntities(array $message): array
    {
        $text = $message['text'] ?? $message['caption'] ?? '';
        $entities = $message['entities'] ?? $message['caption_entities'] ?? [];
        $hashtags = [];

        foreach ($entities as $entity) {
            if (($entity['type'] ?? null) !== 'hashtag') {
                continue;
            }
            $offset = (int) ($entity['offset'] ?? 0);
            $length = (int) ($entity['length'] ?? 0);
            if ($length <= 0) {
                continue;
            }
            $hashtags[] = mb_substr($text, $offset, $length);
        }

        return $hashtags;
    }

    protected function fallback(ContentCalendar $item): string
    {
        $shortcode = $this->shortcode($item);
        return "#bp_post_{$shortcode}";
    }

    protected function slugify(string $text, int $maxLen): string
    {
        $text = ltrim($text, '@');
        $text = Str::ascii($text);
        $text = preg_replace('/[^a-zA-Z0-9]/', '', mb_strtolower($text)) ?? '';
        return substr($text, 0, $maxLen);
    }
}
