<?php

namespace App\Services\Agent\CallCenter;

use App\Models\CallAnalysis;
use App\Models\OperatorCoachingTask;
use App\Models\SalesScript;
use Illuminate\Support\Facades\Log;

/**
 * Call analysis natijalariga qarab avtomatik coaching vazifa yaratadi.
 *
 * Logika:
 *   - Overall score < 60 → coaching task
 *   - Script compliance < 50 → compliance task
 *   - Talk ratio balanssiz → balance task
 *   - Customer sentiment negative → rapport task
 *   - Bosqich ball < 40 → stage-specific task
 */
class CoachingTaskGenerator
{
    /**
     * Tahlildan keyin coaching taskslar yaratish
     */
    public function generateFromAnalysis(CallAnalysis $analysis): array
    {
        if (!$analysis->operator_id || !$analysis->business_id) {
            return [];
        }

        $tasks = [];

        // 1. Overall score past
        if ($analysis->overall_score !== null && $analysis->overall_score < 60) {
            $tasks[] = $this->createTask($analysis, [
                'weak_area' => $this->identifyWeakestStage($analysis),
                'title' => 'Umumiy ko\'rsatkich past (' . round($analysis->overall_score) . '/100)',
                'description' => 'Qo\'ng\'iroqingiz umumiy ball 60 dan past. Kuchsiz bosqichlarni ko\'rib chiqing va mashq qiling.',
                'priority' => $analysis->overall_score < 40 ? 'urgent' : 'high',
            ]);
        }

        // 2. Script compliance past
        if ($analysis->script_compliance_score !== null && $analysis->script_compliance_score < 50) {
            $tasks[] = $this->createTask($analysis, [
                'weak_area' => 'script_compliance',
                'title' => 'Skriptga amal qilmagan (' . round($analysis->script_compliance_score) . '%)',
                'description' => 'Majburiy frazalarni ishlating. Skriptni qayta o\'rganing.',
                'priority' => 'high',
            ]);
        }

        // 3. Talk ratio balanssiz
        if ($analysis->talk_ratio_operator !== null) {
            $ratio = (float) $analysis->talk_ratio_operator;
            if ($ratio < 25) {
                $tasks[] = $this->createTask($analysis, [
                    'weak_area' => 'talk_ratio',
                    'title' => 'Juda kam gapirdingiz (' . round($ratio) . '%)',
                    'description' => 'Operator mijozga taklif bermagan. Ko\'proq gapiring, yo\'naltiring.',
                    'priority' => 'medium',
                ]);
            } elseif ($ratio > 75) {
                $tasks[] = $this->createTask($analysis, [
                    'weak_area' => 'talk_ratio',
                    'title' => 'Juda ko\'p gapirdingiz (' . round($ratio) . '%)',
                    'description' => 'Mijozga gapirish imkoni bermagansiz. Ko\'proq tinglang va savol bering.',
                    'priority' => 'medium',
                ]);
            }
        }

        // 4. Mijoz norozi
        if ($analysis->sentiment_customer === 'negative') {
            $tasks[] = $this->createTask($analysis, [
                'weak_area' => 'sentiment',
                'title' => 'Mijoz norozi bo\'lgan',
                'description' => 'Suhbatda mijoz kayfiyati salbiy bo\'lgan. Empatiya, munosabat qurishni o\'rganing.',
                'priority' => 'high',
            ]);
        }

        // 5. Kritik anti-patterns
        $antiPatterns = $analysis->anti_patterns ?? [];
        foreach ($antiPatterns as $pattern) {
            if (($pattern['severity'] ?? '') === 'critical') {
                $tasks[] = $this->createTask($analysis, [
                    'weak_area' => $this->mapAntiPatternToArea($pattern['type'] ?? ''),
                    'title' => 'Jiddiy xato: ' . ($pattern['description'] ?? $pattern['type'] ?? 'Noma\'lum'),
                    'description' => $pattern['suggestion'] ?? 'Ushbu xatoni takrorlamaslik uchun mashq qiling.',
                    'priority' => 'urgent',
                ]);
            }
        }

        $createdCount = count(array_filter($tasks));
        Log::info('Coaching tasks yaratildi', [
            'analysis_id' => $analysis->id,
            'operator_id' => $analysis->operator_id,
            'count' => $createdCount,
        ]);

        return array_filter($tasks);
    }

    /**
     * Bitta coaching task yaratish
     */
    private function createTask(CallAnalysis $analysis, array $data): ?OperatorCoachingTask
    {
        try {
            return OperatorCoachingTask::create([
                'business_id' => $analysis->business_id,
                'operator_id' => $analysis->operator_id,
                'call_analysis_id' => $analysis->id,
                'title' => $data['title'],
                'description' => $data['description'],
                'weak_area' => $data['weak_area'] ?? 'greeting',
                'score_at_creation' => $analysis->overall_score,
                'priority' => $data['priority'] ?? 'medium',
                'status' => 'pending',
                'due_date' => now()->addDays(3),
            ]);
        } catch (\Exception $e) {
            Log::warning('Coaching task yaratishda xato', ['error' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * Eng zaif bosqichni topish
     */
    private function identifyWeakestStage(CallAnalysis $analysis): string
    {
        $scores = $analysis->stage_scores ?? [];
        if (empty($scores)) return 'greeting';

        $lowest = 'greeting';
        $lowestScore = 101;
        foreach ($scores as $stage => $score) {
            if ($score < $lowestScore && in_array($stage, array_keys(SalesScript::STAGES))) {
                $lowestScore = $score;
                $lowest = $stage;
            }
        }
        return $lowest;
    }

    /**
     * Anti-pattern turini weak_area ga map qilish
     */
    private function mapAntiPatternToArea(string $type): string
    {
        return match ($type) {
            'no_discovery' => 'discovery',
            'price_early', 'weak_closing' => 'closing',
            'no_objection_handle' => 'objection_handling',
            'interruption', 'monologue' => 'talk_ratio',
            'negative_language' => 'sentiment',
            'no_followup' => 'cta',
            default => 'greeting',
        };
    }
}
