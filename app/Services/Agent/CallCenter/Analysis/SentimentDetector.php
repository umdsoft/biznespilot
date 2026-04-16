<?php

namespace App\Services\Agent\CallCenter\Analysis;

/**
 * Transcript'da sentiment aniqlash (qoida asoslangan, bepul).
 *
 * Operator va mijoz sentimenti alohida hisoblanadi.
 * AI chaqirilmaydi — tez va arzon.
 */
class SentimentDetector
{
    private const POSITIVE_WORDS = [
        "zo'r", 'ajoyib', 'rahmat', 'yoqdi', 'yaxshi', "a'lo", 'mukammal',
        'tavsiya', 'mamnun', 'qiziq', 'yaxshilab', 'juda yaxshi',
        'отлично', 'супер', 'спасибо', 'нравится', 'хорошо', 'прекрасно',
    ];

    private const NEGATIVE_WORDS = [
        'yomon', 'shikoyat', 'ishlamaydi', 'xato', 'qimmat', 'kechikdi',
        'norozilik', 'aldash', 'yoqmadi', 'tushunmadim', 'tushunmayapman',
        'ruxsat bermayman', "ishlamayapti", 'muammo',
        'плохо', 'жалоба', 'обман', 'дорого', 'ужасно', 'не работает',
    ];

    private const EMOTIONAL_MARKERS = [
        // Jahl
        'anger' => ['jahl', 'asablanyapman', 'g\'azab', 'yetarli', "xatir", 'бесит', 'злой', 'разозлил'],
        // Shubha
        'doubt' => ['ishonmayman', 'o\'ylab ko\'raman', 'mumkin', 'balki', 'не уверен', 'сомневаюсь'],
        // Qiziqish
        'interest' => ['aniqrog', 'batafsil', 'qizig', 'yana ayting', 'подробнее', 'интересно'],
    ];

    /**
     * Sentiment aniqlash
     */
    public function detect(?string $formattedTranscript): array
    {
        if (!$formattedTranscript) {
            return $this->empty();
        }

        $operatorText = '';
        $customerText = '';

        $lines = preg_split('/\r?\n/', $formattedTranscript);
        foreach ($lines as $line) {
            $line = trim($line);
            if (!$line) continue;

            if (preg_match('/^(operator|оператор|опер)[\s]*:/iu', $line, $m)) {
                $operatorText .= ' ' . mb_strtolower(trim(mb_substr($line, mb_strlen($m[0]))));
            } elseif (preg_match('/^(mijoz|клиент|xaridor|покупатель)[\s]*:/iu', $line, $m)) {
                $customerText .= ' ' . mb_strtolower(trim(mb_substr($line, mb_strlen($m[0]))));
            }
        }

        return [
            'operator' => $this->analyzeText($operatorText),
            'customer' => $this->analyzeText($customerText),
            'emotional_moments' => $this->detectEmotionalMoments($formattedTranscript),
        ];
    }

    /**
     * Matnning umumiy kayfiyatini aniqlash
     */
    private function analyzeText(string $text): string
    {
        if (!$text) return 'neutral';

        $positive = 0;
        $negative = 0;

        foreach (self::POSITIVE_WORDS as $word) {
            $positive += substr_count($text, $word);
        }
        foreach (self::NEGATIVE_WORDS as $word) {
            $negative += substr_count($text, $word);
        }

        if ($positive > $negative * 1.5) return 'positive';
        if ($negative > $positive * 1.5) return 'negative';
        return 'neutral';
    }

    /**
     * Emotional moment'larni topish (qachon jahl chiqdi, qiziqish uyg'ondi)
     */
    private function detectEmotionalMoments(string $transcript): array
    {
        $moments = [];
        $lines = preg_split('/\r?\n/', $transcript);
        $lower = mb_strtolower($transcript);

        foreach (self::EMOTIONAL_MARKERS as $emotion => $markers) {
            foreach ($markers as $marker) {
                if (str_contains($lower, $marker)) {
                    // Liniyani topish
                    foreach ($lines as $i => $line) {
                        if (mb_stripos($line, $marker) !== false) {
                            $moments[] = [
                                'emotion' => $emotion,
                                'marker' => $marker,
                                'line_number' => $i + 1,
                                'excerpt' => mb_substr(trim($line), 0, 150),
                            ];
                            break;
                        }
                    }
                }
            }
        }

        return array_slice($moments, 0, 5); // Eng ko'pi 5 ta moment
    }

    private function empty(): array
    {
        return [
            'operator' => 'neutral',
            'customer' => 'neutral',
            'emotional_moments' => [],
        ];
    }
}
