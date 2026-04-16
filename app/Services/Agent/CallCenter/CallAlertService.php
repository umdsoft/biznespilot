<?php

namespace App\Services\Agent\CallCenter;

use App\Models\CallAnalysis;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Post-call alertlar — yuqori xavfli holatlarni aniqlaydi.
 *
 * Alert turlari:
 *   - CRITICAL_SCORE: Ball 30 dan past
 *   - HIGH_VALUE_LOST: Katta summa bor lid yo'qotildi
 *   - REPEATED_FAILURE: Operator ketma-ket 3+ past ball
 *   - NEGATIVE_SENTIMENT_SPIKE: Haftalik norozi mijozlar soni 5 dan oshdi
 *   - SCRIPT_IGNORED: Script compliance 30 dan past
 */
class CallAlertService
{
    public const ALERT_CRITICAL_SCORE = 'critical_score';
    public const ALERT_HIGH_VALUE_LOST = 'high_value_lost';
    public const ALERT_REPEATED_FAILURE = 'repeated_failure';
    public const ALERT_NEGATIVE_SPIKE = 'negative_sentiment_spike';
    public const ALERT_SCRIPT_IGNORED = 'script_ignored';

    /**
     * Tahlil paytida tekshiruv — alertlar aniqlash
     */
    public function checkForAlerts(CallAnalysis $analysis): array
    {
        $alerts = [];

        // 1. Kritik past ball
        if ($analysis->overall_score !== null && $analysis->overall_score < 30) {
            $alerts[] = $this->buildAlert(self::ALERT_CRITICAL_SCORE, [
                'severity' => 'critical',
                'title' => 'Juda past ball: ' . round($analysis->overall_score),
                'message' => 'Operator juda yomon ishlagan. Darhol coaching kerak.',
                'analysis_id' => $analysis->id,
                'operator_id' => $analysis->operator_id,
            ]);
        }

        // 2. Skript butunlay e'tiborsiz qoldirilgan
        if ($analysis->script_compliance_score !== null && $analysis->script_compliance_score < 30) {
            $alerts[] = $this->buildAlert(self::ALERT_SCRIPT_IGNORED, [
                'severity' => 'high',
                'title' => 'Skript e\'tiborsiz qoldirilgan (' . round($analysis->script_compliance_score) . '%)',
                'message' => 'Operator skriptga amal qilmagan. Majburiy frazalarni o\'tkazib yuborgan.',
                'analysis_id' => $analysis->id,
                'operator_id' => $analysis->operator_id,
            ]);
        }

        // 3. Katta summali lid yo'qotildi
        if ($analysis->lead_id) {
            $lead = DB::table('leads')->where('id', $analysis->lead_id)->first(['estimated_value', 'status']);
            if ($lead && $lead->status === 'lost' && ($lead->estimated_value ?? 0) >= 5_000_000) {
                $alerts[] = $this->buildAlert(self::ALERT_HIGH_VALUE_LOST, [
                    'severity' => 'critical',
                    'title' => 'Qimmatli lid yo\'qotildi: ' . number_format($lead->estimated_value) . " so'm",
                    'message' => 'Yuqori qiymatli lid "lost" statusiga o\'tdi. Suhbat tahlilini ko\'ring.',
                    'analysis_id' => $analysis->id,
                    'operator_id' => $analysis->operator_id,
                    'lead_id' => $analysis->lead_id,
                ]);
            }
        }

        // 4. Ketma-ket past ball (3+)
        if ($analysis->operator_id) {
            $recent = DB::table('call_analyses')
                ->where('operator_id', $analysis->operator_id)
                ->where('business_id', $analysis->business_id)
                ->orderByDesc('created_at')
                ->limit(3)
                ->pluck('overall_score')
                ->toArray();

            if (count($recent) === 3 && max($recent) < 50) {
                $alerts[] = $this->buildAlert(self::ALERT_REPEATED_FAILURE, [
                    'severity' => 'high',
                    'title' => 'Operator ketma-ket 3 ta past ball',
                    'message' => 'Operator so\'nggi 3 ta qo\'ng\'iroqda 50 dan past ball olgan. Tezkor coaching kerak.',
                    'operator_id' => $analysis->operator_id,
                ]);
            }
        }

        if (!empty($alerts)) {
            Log::warning('Call alerts chiqarildi', [
                'analysis_id' => $analysis->id,
                'count' => count($alerts),
            ]);
        }

        return $alerts;
    }

    /**
     * Haftalik trend tekshiruvi — negative sentiment spike
     */
    public function checkNegativeSentimentSpike(string $businessId): ?array
    {
        $thisWeek = DB::table('call_analyses')
            ->where('business_id', $businessId)
            ->where('sentiment_customer', 'negative')
            ->where('created_at', '>=', now()->subDays(7))
            ->count();

        $prevWeek = DB::table('call_analyses')
            ->where('business_id', $businessId)
            ->where('sentiment_customer', 'negative')
            ->whereBetween('created_at', [now()->subDays(14), now()->subDays(7)])
            ->count();

        if ($thisWeek >= 5 && $thisWeek > $prevWeek * 1.5) {
            return $this->buildAlert(self::ALERT_NEGATIVE_SPIKE, [
                'severity' => 'high',
                'title' => 'Norozi mijozlar ko\'paydi: ' . $thisWeek . ' ta',
                'message' => "Oxirgi haftada norozi mijozlar {$thisWeek} ta (avvalgi hafta {$prevWeek}). Jamoa reytingini ko'ring.",
            ]);
        }

        return null;
    }

    /**
     * Alert struktura yaratish
     */
    private function buildAlert(string $type, array $data): array
    {
        return array_merge([
            'type' => $type,
            'timestamp' => now()->toISOString(),
        ], $data);
    }
}
