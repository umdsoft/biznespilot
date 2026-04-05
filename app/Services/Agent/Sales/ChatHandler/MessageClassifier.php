<?php

namespace App\Services\Agent\Sales\ChatHandler;

/**
 * Xabar turini aniqlash — qoidaga asoslangan (bepul, AI chaqirilmaydi).
 * 80% xabarlar qoida bilan aniqlanadi, faqat 20% AI kerak.
 */
class MessageClassifier
{
    // Xabar turlari
    public const TYPE_GREETING = 'greeting';
    public const TYPE_MENU = 'menu';
    public const TYPE_PRODUCT = 'product';
    public const TYPE_PRICE = 'price';
    public const TYPE_ORDER = 'order';
    public const TYPE_OPERATOR = 'operator';
    public const TYPE_OBJECTION = 'objection';
    public const TYPE_COMPLEX = 'complex';
    public const TYPE_UNKNOWN = 'unknown';

    // Salomlashish naqshlari
    // MUHIM: 'hi', 'hey' kabi qisqa so'zlar qo'shilmagan — boshqa so'zlar ichida mos kelishi mumkin
    private const GREETING_PATTERNS = [
        'salom', 'assalomu', 'hello', 'hayrli kun',
        'hayrli tong', 'hayrli kech', 'привет', 'здравствуйте',
    ];

    // Menyu so'rovlari
    private const MENU_PATTERNS = [
        'menyu', 'menu', 'ro\'yxat', 'nima bor', 'xizmat', 'mahsulot',
        'assortiment', 'katalog', 'catalog',
    ];

    // Narx so'rovlari
    private const PRICE_PATTERNS = [
        'narx', 'narxi', 'qancha', 'necha', 'price', 'qiymati',
        'to\'lov', 'pul', 'сколько', 'цена', 'стоимость',
    ];

    // Buyurtma
    private const ORDER_PATTERNS = [
        'buyurtma', 'zakaz', 'order', 'sotib olish', 'xarid',
        'olmoqchi', 'bermoqchi', 'заказ', 'купить',
    ];

    // Operator
    private const OPERATOR_PATTERNS = [
        'operator', 'menejer', 'odam', 'jonli', 'real',
        'менеджер', 'оператор', 'живой',
    ];

    // E'tiroz kalit so'zlari
    private const OBJECTION_PATTERNS = [
        'qimmat' => 'price',
        'arzon' => 'price',
        'byudjet' => 'price',
        'narx baland' => 'price',
        'дорого' => 'price',
        'ishonch' => 'trust',
        'bilmayman' => 'trust',
        'tanish emas' => 'trust',
        'не знаю' => 'trust',
        'keyinroq' => 'timing',
        'o\'ylab ko\'raman' => 'timing',
        'hozir emas' => 'timing',
        'подумаю' => 'timing',
        'kerak emas' => 'need',
        'shunchaki' => 'need',
        'не нужно' => 'need',
    ];

    /**
     * Xabar turini aniqlash
     *
     * @return array{type: string, objection_type: string|null, confidence: string}
     */
    public function classify(string $message): array
    {
        $normalized = mb_strtolower(trim($message));

        // Salomlashish
        if ($this->matchesAny($normalized, self::GREETING_PATTERNS)) {
            return ['type' => self::TYPE_GREETING, 'objection_type' => null, 'confidence' => 'high'];
        }

        // Operator talabi — ustuvor
        if ($this->matchesAny($normalized, self::OPERATOR_PATTERNS)) {
            return ['type' => self::TYPE_OPERATOR, 'objection_type' => null, 'confidence' => 'high'];
        }

        // E'tiroz
        foreach (self::OBJECTION_PATTERNS as $pattern => $objectionType) {
            if (str_contains($normalized, $pattern)) {
                return ['type' => self::TYPE_OBJECTION, 'objection_type' => $objectionType, 'confidence' => 'medium'];
            }
        }

        // Buyurtma
        if ($this->matchesAny($normalized, self::ORDER_PATTERNS)) {
            return ['type' => self::TYPE_ORDER, 'objection_type' => null, 'confidence' => 'high'];
        }

        // Narx
        if ($this->matchesAny($normalized, self::PRICE_PATTERNS)) {
            return ['type' => self::TYPE_PRICE, 'objection_type' => null, 'confidence' => 'high'];
        }

        // Menyu / mahsulot
        if ($this->matchesAny($normalized, self::MENU_PATTERNS)) {
            return ['type' => self::TYPE_MENU, 'objection_type' => null, 'confidence' => 'high'];
        }

        // Mahsulot nomi bo'lishi mumkin — boshqa hech narsaga mos kelmasa
        if (mb_strlen($normalized) < 30 && !str_contains($normalized, '?')) {
            return ['type' => self::TYPE_PRODUCT, 'objection_type' => null, 'confidence' => 'low'];
        }

        // Murakkab savol — AI kerak
        return ['type' => self::TYPE_COMPLEX, 'objection_type' => null, 'confidence' => 'low'];
    }

    private function matchesAny(string $text, array $patterns): bool
    {
        foreach ($patterns as $pattern) {
            if (str_contains($text, $pattern)) {
                return true;
            }
        }
        return false;
    }
}
