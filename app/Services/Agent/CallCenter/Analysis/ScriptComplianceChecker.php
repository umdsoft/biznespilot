<?php

namespace App\Services\Agent\CallCenter\Analysis;

use App\Models\SalesScript;

/**
 * Transkript skriptga mos kelishini tekshiradi.
 *
 * Har bosqichda:
 *   - Majburiy frazalar ishlatilganmi (+ball)
 *   - Taqiqlangan frazalar ishlatilganmi (-ball)
 *   - Umumiy compliance ball (0-100)
 */
class ScriptComplianceChecker
{
    /**
     * Transkriptni skript bilan solishtirish
     *
     * @return array{score: float, required_detected: array, forbidden_detected: array, stage_scores: array}
     */
    public function check(string $transcript, ?SalesScript $script): array
    {
        if (!$script) {
            return [
                'score' => 0,
                'required_detected' => [],
                'forbidden_detected' => [],
                'stage_scores' => [],
                'message' => 'Skript belgilanmagan',
            ];
        }

        $lower = mb_strtolower($transcript);

        $requiredDetected = [];
        $forbiddenDetected = [];
        $stageScores = [];

        $totalRequired = 0;
        $totalMatched = 0;
        $totalForbidden = 0;
        $totalForbiddenMatched = 0;

        // Har bosqichni tekshirish
        foreach (SalesScript::STAGES as $stageKey => $stageLabel) {
            $stage = $script->stages[$stageKey] ?? null;
            if (!$stage) {
                $stageScores[$stageKey] = null;
                continue;
            }

            $required = $stage['required'] ?? [];
            $forbidden = $stage['forbidden'] ?? [];

            $matchedRequired = [];
            foreach ($required as $phrase) {
                $phraseLower = mb_strtolower(trim($phrase));
                if ($phraseLower && str_contains($lower, $phraseLower)) {
                    $matchedRequired[] = $phrase;
                    $requiredDetected[] = ['phrase' => $phrase, 'stage' => $stageKey];
                }
            }

            $matchedForbidden = [];
            foreach ($forbidden as $phrase) {
                $phraseLower = mb_strtolower(trim($phrase));
                if ($phraseLower && str_contains($lower, $phraseLower)) {
                    $matchedForbidden[] = $phrase;
                    $forbiddenDetected[] = ['phrase' => $phrase, 'stage' => $stageKey];
                }
            }

            // Bosqich bali
            $stageScore = 100;
            if (count($required) > 0) {
                $stageScore = round(count($matchedRequired) / count($required) * 100);
            }
            // Taqiqlangan fraza topilsa jarima
            $penalty = count($matchedForbidden) * 15;
            $stageScore = max(0, $stageScore - $penalty);

            $stageScores[$stageKey] = $stageScore;
            $totalRequired += count($required);
            $totalMatched += count($matchedRequired);
            $totalForbidden += count($forbidden);
            $totalForbiddenMatched += count($matchedForbidden);
        }

        // Umumiy ball
        $overallScore = 100;
        if ($totalRequired > 0) {
            $overallScore = round($totalMatched / $totalRequired * 100);
        }
        $globalPenalty = min(50, $totalForbiddenMatched * 10);
        $overallScore = max(0, $overallScore - $globalPenalty);

        return [
            'score' => $overallScore,
            'required_detected' => $requiredDetected,
            'forbidden_detected' => $forbiddenDetected,
            'stage_scores' => $stageScores,
            'totals' => [
                'required_total' => $totalRequired,
                'required_matched' => $totalMatched,
                'forbidden_total' => $totalForbidden,
                'forbidden_matched' => $totalForbiddenMatched,
            ],
        ];
    }
}
