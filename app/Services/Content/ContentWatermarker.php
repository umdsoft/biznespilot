<?php

declare(strict_types=1);

namespace App\Services\Content;

use App\Models\ContentCalendar;

/**
 * Yashirin steganografik watermark — matn ichida ko'rinmaydigan
 * zero-width Unicode belgilar bilan plan UUID'ni kodlaydi.
 *
 * Maqsad: foydalanuvchi hashtag'ni o'chirib tashlasa ham, watermark
 * matn ichida qoladi va webhook orqali kelgan postni 100% identifikatsiya
 * qilish imkonini beradi.
 *
 * Algoritm:
 *   - 16-bit nibble = ContentCalendar.id ning 4 hex belgisi
 *   - Har bit: ZWSP (U+200B) = 0, ZWNJ (U+200C) = 1
 *   - Boshi/oxiri WORD JOINER (U+2060) marker
 *   - Joylashuv: matn boshida (1-so'zdan keyin) + oxirida — ikki nusxa
 *
 * Telegram, Instagram, va YouTube zero-width belgilarni saqlaydi
 * (test qilingan, 2026-04 holatiga ko'ra).
 */
class ContentWatermarker
{
    /** Bit 0 — Zero Width Space */
    public const ZWSP = "\u{200B}";

    /** Bit 1 — Zero Width Non-Joiner */
    public const ZWNJ = "\u{200C}";

    /** Marker (start/end) — Word Joiner (ko'rinmaydigan, line-break-blocking) */
    public const MARK = "\u{2060}";

    /** Watermark uzunligi: 16 bit = 4 hex char */
    private const SHORTCODE_HEX_LEN = 4;

    private const PAYLOAD_BIT_LEN = self::SHORTCODE_HEX_LEN * 4;

    /**
     * Plan ID prefiksini matnga 2 marta yashirin joylash (boshida + oxirida).
     */
    public function embed(string $text, string $planId): string
    {
        $shortcode = $this->extractShortcodeFromUuid($planId);
        if ($shortcode === '') {
            return $text;
        }

        $marker = $this->buildMarker($shortcode);

        // Matn 1-so'zdan keyin va oxirida — ikki marta zaxira
        $words = preg_split('/(\s+)/u', $text, 3, PREG_SPLIT_DELIM_CAPTURE);
        if (! is_array($words) || count($words) < 1) {
            return $text . $marker;
        }

        $firstWord = $words[0] ?? '';
        $separator = $words[1] ?? ' ';
        $rest = isset($words[2]) ? implode('', array_slice($words, 2)) : '';

        // Boshi: 1-so'zdan keyin (foydalanuvchi matnni tahrirlasa ham
        // 1-so'zni odatda saqlab qoladi).
        // Oxiri: matn oxirida (zaxira nusxa).
        return $firstWord . $marker . $separator . $rest . $marker;
    }

    /**
     * Matndan watermark'ni dekod qilish — agar topilmasa null.
     *
     * Ikkala nusxani ham sinab ko'radi va birinchi muvaffaqiyatlisini qaytaradi.
     */
    public function extract(string $text): ?string
    {
        $pattern = '/' . preg_quote(self::MARK, '/')
            . '([' . preg_quote(self::ZWSP . self::ZWNJ, '/') . ']{' . self::PAYLOAD_BIT_LEN . '})'
            . preg_quote(self::MARK, '/') . '/u';

        if (! preg_match($pattern, $text, $m)) {
            return null;
        }

        return $this->bitsToHex($m[1]);
    }

    /**
     * Foydalanuvchiga ko'rsatish uchun watermark'ni tozalash
     * (Vue UI'da "Toza ko'rinish" tugmasi uchun).
     */
    public function strip(string $text): string
    {
        return preg_replace(
            '/[' . preg_quote(self::ZWSP . self::ZWNJ . self::MARK, '/') . ']/u',
            '',
            $text
        ) ?? $text;
    }

    /**
     * Watermark mavjudligini tekshirish (yo'q-bor).
     */
    public function has(string $text): bool
    {
        return $this->extract($text) !== null;
    }

    // ============================================================

    protected function buildMarker(string $shortcode): string
    {
        $bits = $this->hexToBits($shortcode);
        $payload = '';
        foreach (str_split($bits) as $bit) {
            $payload .= ($bit === '0') ? self::ZWSP : self::ZWNJ;
        }
        return self::MARK . $payload . self::MARK;
    }

    protected function extractShortcodeFromUuid(string $uuid): string
    {
        $hex = preg_replace('/[^a-f0-9]/i', '', $uuid) ?? '';
        return strtolower(substr($hex, 0, self::SHORTCODE_HEX_LEN));
    }

    protected function hexToBits(string $hex): string
    {
        $bits = '';
        foreach (str_split($hex) as $char) {
            $bits .= str_pad(decbin(hexdec($char)), 4, '0', STR_PAD_LEFT);
        }
        return $bits;
    }

    protected function bitsToHex(string $bitsAsZwspZwnj): string
    {
        $bits = '';
        // mb_str_split — har bir kodlangan belgini olish
        $chars = preg_split('//u', $bitsAsZwspZwnj, -1, PREG_SPLIT_NO_EMPTY) ?: [];
        foreach ($chars as $char) {
            $bits .= ($char === self::ZWSP) ? '0' : '1';
        }
        $hex = '';
        foreach (str_split($bits, 4) as $nibble) {
            $hex .= dechex(bindec($nibble));
        }
        return strtolower($hex);
    }
}
