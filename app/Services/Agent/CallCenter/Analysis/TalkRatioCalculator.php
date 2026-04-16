<?php

namespace App\Services\Agent\CallCenter\Analysis;

/**
 * Talk-listen ratio hisoblash.
 * Operator va mijozning gapirish nisbatini aniqlaydi.
 *
 * Input: "Operator: ..." va "Mijoz: ..." formatidagi transcript
 * Output: operator_words, customer_words, talk_ratio_operator (0-100)
 */
class TalkRatioCalculator
{
    /**
     * Formatlangan transcript'dan talk ratio hisoblash
     */
    public function calculate(?string $formattedTranscript): array
    {
        if (!$formattedTranscript) {
            return $this->empty();
        }

        $operatorWords = 0;
        $customerWords = 0;

        $lines = preg_split('/\r?\n/', $formattedTranscript);

        foreach ($lines as $line) {
            $line = trim($line);
            if (!$line) continue;

            // "Operator:" yoki "Оператор:" bo'lsa
            if (preg_match('/^(operator|оператор|опер)[\s]*:/iu', $line, $m)) {
                $text = trim(mb_substr($line, mb_strlen($m[0])));
                $operatorWords += $this->countWords($text);
                continue;
            }

            // "Mijoz:" yoki "Клиент:" bo'lsa
            if (preg_match('/^(mijoz|клиент|xaridor|покупатель)[\s]*:/iu', $line, $m)) {
                $text = trim(mb_substr($line, mb_strlen($m[0])));
                $customerWords += $this->countWords($text);
                continue;
            }
        }

        $total = $operatorWords + $customerWords;
        $talkRatio = $total > 0 ? round($operatorWords / $total * 100, 2) : 0;

        return [
            'operator_words' => $operatorWords,
            'customer_words' => $customerWords,
            'total_words' => $total,
            'talk_ratio_operator' => $talkRatio,
            'balance' => $this->assessBalance($talkRatio),
        ];
    }

    /**
     * Balansni baholash
     */
    private function assessBalance(float $ratio): string
    {
        return match (true) {
            $ratio < 30 => 'operator_too_quiet',   // operator kam gapirdi
            $ratio > 70 => 'operator_too_much',    // operator juda ko'p gapirdi
            $ratio >= 40 && $ratio <= 60 => 'balanced',
            default => 'acceptable',
        };
    }

    private function countWords(string $text): int
    {
        $text = trim($text);
        if (!$text) return 0;
        return count(preg_split('/\s+/u', $text, -1, PREG_SPLIT_NO_EMPTY));
    }

    private function empty(): array
    {
        return [
            'operator_words' => 0,
            'customer_words' => 0,
            'total_words' => 0,
            'talk_ratio_operator' => 0,
            'balance' => 'no_data',
        ];
    }
}
