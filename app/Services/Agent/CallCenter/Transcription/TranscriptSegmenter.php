<?php

namespace App\Services\Agent\CallCenter\Transcription;

/**
 * Suhbat matnini 7 bosqichga ajratish (qoidaga asoslangan, bepul).
 * Sotuv suhbatining standart bosqichlari.
 */
class TranscriptSegmenter
{
    // 7 bosqichli sotuv suhbati
    public const STAGES = [
        'opening' => 'Ochilish (salomlashish)',
        'needs_discovery' => 'Ehtiyoj aniqlash',
        'qualification' => 'Moslik tekshirish',
        'presentation' => 'Taqdimot',
        'objection_handling' => "E'tiroz bartaraf qilish",
        'closing' => 'Yakunlash',
        'next_steps' => 'Keyingi qadamlar',
    ];

    // Bosqich kalit so'zlari
    private const STAGE_KEYWORDS = [
        'opening' => ['salom', 'assalomu', 'hayrli', 'eshitaman', 'aytingchi', 'привет', 'здравствуйте', 'слушаю'],
        'needs_discovery' => ['nimaga kerak', 'maqsad', 'qanday', 'nima xohlaysiz', 'muammo', 'что вам нужно', 'какая цель'],
        'qualification' => ['byudjet', 'vaqt', 'qaror', 'kim qaror', 'бюджет', 'кто принимает'],
        'presentation' => ['taklif', 'kurs', 'mahsulot', 'xizmat', 'imkoniyat', 'natija', 'предлагаем', 'курс'],
        'objection_handling' => ['qimmat', 'arzon', 'ishonch', "o'ylab", 'ammo', 'lekin', 'дорого', 'но', 'подумаю'],
        'closing' => ["ro'yxat", 'yozilish', 'buyurtma', "to'lov", 'kelishdik', 'записаться', 'оплата'],
        'next_steps' => ['qachon', 'ertaga', 'keyingi', "bog'lan", 'yuboraman', 'когда', 'завтра', 'отправлю'],
    ];

    /**
     * Matnni bosqichlarga ajratish
     *
     * @return array<string, array{text: string, start_position: int}>
     */
    public function segment(string $transcript): array
    {
        $sentences = $this->splitIntoSentences($transcript);
        $segments = [];
        $currentStage = 'opening';
        $stageTexts = [];

        foreach ($sentences as $i => $sentence) {
            $detectedStage = $this->detectStage($sentence, $currentStage, $i, count($sentences));

            if ($detectedStage !== $currentStage && !isset($stageTexts[$detectedStage])) {
                $currentStage = $detectedStage;
            }

            if (!isset($stageTexts[$currentStage])) {
                $stageTexts[$currentStage] = [];
            }
            $stageTexts[$currentStage][] = $sentence;
        }

        // Natijani formatlash
        foreach (self::STAGES as $key => $label) {
            $segments[$key] = [
                'label' => $label,
                'text' => implode(' ', $stageTexts[$key] ?? []),
                'sentence_count' => count($stageTexts[$key] ?? []),
                'detected' => !empty($stageTexts[$key]),
            ];
        }

        return $segments;
    }

    /**
     * Gapni qaysi bosqichga tegishli ekanini aniqlash
     */
    private function detectStage(string $sentence, string $currentStage, int $position, int $total): string
    {
        $normalized = mb_strtolower($sentence);

        // Kalit so'zlar bo'yicha tekshirish
        $scores = [];
        foreach (self::STAGE_KEYWORDS as $stage => $keywords) {
            $score = 0;
            foreach ($keywords as $keyword) {
                if (str_contains($normalized, $keyword)) {
                    $score++;
                }
            }
            if ($score > 0) {
                $scores[$stage] = $score;
            }
        }

        // Agar kalit so'z topilsa — eng ko'p mos kelganini tanlash
        if (!empty($scores)) {
            arsort($scores);
            return array_key_first($scores);
        }

        // Pozitsiyaga qarab taxmin qilish (suhbatning boshida = ochilish, oxirida = yakunlash)
        $relativePosition = $position / max($total, 1);
        if ($relativePosition < 0.15) return 'opening';
        if ($relativePosition > 0.85) return 'next_steps';

        return $currentStage;
    }

    /**
     * Matnni gaplarga ajratish
     */
    private function splitIntoSentences(string $text): array
    {
        $sentences = preg_split('/[.!?]+/', $text, -1, PREG_SPLIT_NO_EMPTY);
        return array_map('trim', array_filter($sentences));
    }
}
