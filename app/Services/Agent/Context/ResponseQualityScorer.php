<?php

namespace App\Services\Agent\Context;

/**
 * Agent javob sifatini avtomatik baholaydi (Ruflo uslubida).
 *
 * Tez skorlash (AI chaqirmasdan):
 * - Uzunlik
 * - Raqamlar soni
 * - Aniq qadamlar bor-yo'q
 * - Taqiqlangan iboralar
 *
 * Natija: 0-100 ball + A/B/C/D baho + muammolar ro'yxati
 */
class ResponseQualityScorer
{
    // Taqiqlangan iboralar (agent "maslahatchi" rejimida gapiradi — bu yomon)
    private const FORBIDDEN_PHRASES = [
        'shuni qiling', 'buni bajaring', 'ayting', 'qaysi soha', 'qancha vaqt',
        'qanday biznes', 'siz qilishingiz kerak',
    ];

    // Yaxshi iboralar (agent "bajaruvchi" rejimida)
    private const GOOD_PHRASES = [
        'men tayyorladim', 'men bajardim', 'men tahlil qildim',
        'tayyor', 'tasdiqlaymi',
    ];

    // Taqiqlangan so'zlar (til sifati)
    private const BAD_WORDS = [
        "o'shni", 'kompetitorlar', 'singari', 'geligi', 'erga keltirish',
        'ko\'z bandhan', 'qadrdan',
    ];

    /**
     * Javobni baholash
     *
     * @return array{score: int, grade: string, issues: array, strengths: array}
     */
    public function score(string $content): array
    {
        $score = 100;
        $issues = [];
        $strengths = [];

        $lower = mb_strtolower($content);
        $len = mb_strlen($content);

        // 1. Uzunlik tekshiruvi
        if ($len < 200) {
            $score -= 30;
            $issues[] = 'Juda qisqa javob (' . $len . ' belgi)';
        } elseif ($len >= 800) {
            $strengths[] = "Chuqur javob ({$len} belgi)";
        }

        // 2. Raqamlar soni
        $numCount = preg_match_all('/\b\d+\b/', $content);
        if ($numCount < 3) {
            $score -= 15;
            $issues[] = "Kam raqam ishlatilgan ({$numCount} ta)";
        } elseif ($numCount >= 8) {
            $strengths[] = "Ma'lumotli javob ({$numCount} raqam)";
        }

        // 3. Valyuta/foiz borligi
        $hasMoney = substr_count($lower, "so'm") + substr_count($lower, 'som') + substr_count($content, '%');
        if ($hasMoney === 0) {
            $score -= 10;
            $issues[] = 'Moliyaviy ko\'rsatkichlar yo\'q (so\'m/%)';
        }

        // 4. Aniq qadamlar bormi (1️⃣ 2️⃣ 3️⃣ yoki raqamli ro'yxat)
        $hasSteps = preg_match('/1[.\s️⃣]|Birinchi|1\\)/u', $content);
        if (!$hasSteps) {
            $score -= 10;
            $issues[] = 'Aniq qadamlar yo\'q';
        }

        // 5. Taqiqlangan iboralar (maslahat uslubi)
        foreach (self::FORBIDDEN_PHRASES as $phrase) {
            if (str_contains($lower, $phrase)) {
                $score -= 15;
                $issues[] = "Maslahat uslubi: \"{$phrase}\"";
                break; // Bitta tekshirish yetarli
            }
        }

        // 6. Yaxshi iboralar (bajaruvchi uslubi)
        foreach (self::GOOD_PHRASES as $phrase) {
            if (str_contains($lower, $phrase)) {
                $strengths[] = "Bajaruvchi uslub: \"{$phrase}\"";
                break;
            }
        }

        // 7. Noto'g'ri so'zlar (til sifati)
        foreach (self::BAD_WORDS as $bad) {
            if (str_contains($lower, $bad)) {
                $score -= 10;
                $issues[] = "Noto'g'ri so'z: \"{$bad}\"";
            }
        }

        // 8. Joylashuv ko'rsatilganmi (📍 Bosh sahifa > ...)
        if (str_contains($content, '📍') || str_contains($lower, 'bosh sahifa >')) {
            $strengths[] = 'Platforma yo\'nalishi ko\'rsatilgan';
        } else {
            $score -= 5;
            $issues[] = 'Platforma yo\'nalishi yo\'q';
        }

        // 9. Markdown format
        if (str_contains($content, '**') || str_contains($content, '##')) {
            $strengths[] = 'Yaxshi formatlangan';
        }

        $score = max(0, min(100, $score));

        return [
            'score' => $score,
            'grade' => $this->getGrade($score),
            'issues' => $issues,
            'strengths' => $strengths,
            'length' => $len,
            'numbers_count' => $numCount,
        ];
    }

    /**
     * Ball bo'yicha baho
     */
    private function getGrade(int $score): string
    {
        return match (true) {
            $score >= 90 => 'A',
            $score >= 75 => 'B',
            $score >= 60 => 'C',
            $score >= 40 => 'D',
            default => 'F',
        };
    }
}
