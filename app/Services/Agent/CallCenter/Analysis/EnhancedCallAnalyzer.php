<?php

namespace App\Services\Agent\CallCenter\Analysis;

use App\Models\CallAnalysis;
use App\Models\SalesScript;
use Illuminate\Support\Facades\Log;

/**
 * Chuqur tahlil — script compliance + talk ratio + sentiment + win probability.
 * Existing CallAnalysis ustiga tahlil yozadi.
 */
class EnhancedCallAnalyzer
{
    public function __construct(
        private ScriptComplianceChecker $compliance,
        private TalkRatioCalculator $talkRatio,
        private SentimentDetector $sentiment,
    ) {}

    /**
     * CallAnalysis record'ini enrichment bilan to'ldirish
     */
    public function enhance(CallAnalysis $analysis): void
    {
        try {
            $transcript = $analysis->transcript ?? '';
            $formatted = $analysis->formatted_transcript ?: $transcript;

            // 1. Script compliance
            $script = $this->resolveScript($analysis);
            $complianceResult = $this->compliance->check($transcript, $script);

            // 2. Talk ratio
            $talkResult = $this->talkRatio->calculate($formatted);

            // 3. Sentiment
            $sentimentResult = $this->sentiment->detect($formatted);

            // 4. Win probability (oddiy formula)
            $winProbability = $this->calculateWinProbability(
                $analysis->overall_score ?? 0,
                $complianceResult['score'],
                $sentimentResult['customer'],
                $talkResult['balance']
            );

            $predictedOutcome = $winProbability >= 60 ? 'win'
                : ($winProbability < 30 ? 'lost' : 'uncertain');

            // Natijalarni yozish
            $analysis->update([
                'script_id' => $script?->id,
                'script_compliance_score' => $complianceResult['score'],
                'required_phrases_detected' => $complianceResult['required_detected'],
                'forbidden_phrases_detected' => $complianceResult['forbidden_detected'],

                'operator_words' => $talkResult['operator_words'],
                'customer_words' => $talkResult['customer_words'],
                'talk_ratio_operator' => $talkResult['talk_ratio_operator'],

                'sentiment_customer' => $sentimentResult['customer'],
                'sentiment_operator' => $sentimentResult['operator'],
                'emotional_moments' => $sentimentResult['emotional_moments'],

                'win_probability' => $winProbability,
                'predicted_outcome' => $predictedOutcome,
            ]);

            Log::info('Enhanced analysis yakunlandi', [
                'analysis_id' => $analysis->id,
                'compliance' => $complianceResult['score'],
                'talk_ratio' => $talkResult['talk_ratio_operator'],
                'sentiment_customer' => $sentimentResult['customer'],
                'win_probability' => $winProbability,
            ]);
        } catch (\Exception $e) {
            Log::error('Enhanced analyzer xato', [
                'analysis_id' => $analysis->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Biznes uchun standart skriptni topish
     */
    private function resolveScript(CallAnalysis $analysis): ?SalesScript
    {
        if ($analysis->script_id) {
            return SalesScript::find($analysis->script_id);
        }

        if (!$analysis->business_id) {
            return null;
        }

        // Standart skript
        return SalesScript::where('business_id', $analysis->business_id)
            ->where('is_active', true)
            ->where('is_default', true)
            ->first()
            ?? SalesScript::where('business_id', $analysis->business_id)
                ->where('is_active', true)
                ->orderByDesc('created_at')
                ->first();
    }

    /**
     * Win probability formulasi
     *   - overall_score: 40%
     *   - script_compliance: 30%
     *   - customer sentiment: 20%
     *   - talk balance: 10%
     */
    private function calculateWinProbability(
        float $overallScore,
        float $complianceScore,
        string $customerSentiment,
        string $talkBalance
    ): float {
        $sentimentPoints = match ($customerSentiment) {
            'positive' => 100,
            'neutral' => 60,
            'negative' => 20,
            default => 50,
        };

        $balancePoints = match ($talkBalance) {
            'balanced' => 100,
            'acceptable' => 80,
            'operator_too_quiet', 'operator_too_much' => 40,
            default => 50,
        };

        $probability = ($overallScore * 0.40)
            + ($complianceScore * 0.30)
            + ($sentimentPoints * 0.20)
            + ($balancePoints * 0.10);

        return round($probability, 2);
    }
}
