<?php

namespace App\Services\Agent\CallCenter;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Operator scorecard — har operator bo'yicha chuqur statistika.
 *
 * Metrics:
 *   - Overall score (o'rtacha ball)
 *   - Script compliance (o'rtacha)
 *   - Talk ratio
 *   - Win probability
 *   - Sotuv konversiyasi
 *   - Eng zaif bosqich
 *   - Eng ko'p anti-pattern
 */
class OperatorScorecardService
{
    /**
     * Biznesdagi barcha operatorlar reytingi
     */
    public function leaderboard(string $businessId, int $days = 30): array
    {
        $since = now()->subDays($days);

        $operators = DB::table('call_analyses')
            ->where('business_id', $businessId)
            ->whereNotNull('operator_id')
            ->where('created_at', '>=', $since)
            ->select('operator_id')
            ->selectRaw('
                COUNT(*) as total_calls,
                AVG(overall_score) as avg_score,
                AVG(script_compliance_score) as avg_compliance,
                AVG(talk_ratio_operator) as avg_talk_ratio,
                AVG(win_probability) as avg_win_prob,
                SUM(CASE WHEN outcome = "sale" THEN 1 ELSE 0 END) as sales,
                SUM(CASE WHEN outcome = "lost" THEN 1 ELSE 0 END) as lost,
                SUM(CASE WHEN sentiment_customer = "negative" THEN 1 ELSE 0 END) as negative_customers
            ')
            ->groupBy('operator_id')
            ->orderByDesc('avg_score')
            ->get();

        $result = [];
        foreach ($operators as $i => $op) {
            $user = DB::table('users')->where('id', $op->operator_id)->first(['name', 'email']);
            $conversion = $op->total_calls > 0 ? round($op->sales / $op->total_calls * 100, 1) : 0;

            $result[] = [
                'rank' => $i + 1,
                'operator_id' => $op->operator_id,
                'operator_name' => $user->name ?? 'Noma\'lum',
                'total_calls' => (int) $op->total_calls,
                'avg_score' => round($op->avg_score ?? 0, 1),
                'avg_compliance' => round($op->avg_compliance ?? 0, 1),
                'avg_talk_ratio' => round($op->avg_talk_ratio ?? 0, 1),
                'avg_win_prob' => round($op->avg_win_prob ?? 0, 1),
                'sales' => (int) $op->sales,
                'lost' => (int) $op->lost,
                'conversion_rate' => $conversion,
                'negative_customers' => (int) $op->negative_customers,
                'grade' => $this->getGrade($op->avg_score ?? 0),
            ];
        }

        return $result;
    }

    /**
     * Bitta operator uchun batafsil scorecard
     */
    public function detailed(string $businessId, string $operatorId, int $days = 30): array
    {
        $since = now()->subDays($days);

        // Asosiy statistika
        $base = DB::table('call_analyses')
            ->where('business_id', $businessId)
            ->where('operator_id', $operatorId)
            ->where('created_at', '>=', $since)
            ->selectRaw('
                COUNT(*) as total_calls,
                AVG(overall_score) as avg_score,
                AVG(script_compliance_score) as avg_compliance,
                AVG(talk_ratio_operator) as avg_talk_ratio,
                AVG(win_probability) as avg_win_prob,
                MAX(overall_score) as best_score,
                MIN(overall_score) as worst_score,
                SUM(CASE WHEN outcome = "sale" THEN 1 ELSE 0 END) as sales,
                SUM(CASE WHEN outcome = "lost" THEN 1 ELSE 0 END) as lost,
                SUM(CASE WHEN outcome = "lead" THEN 1 ELSE 0 END) as leads
            ')->first();

        if (!$base || $base->total_calls == 0) {
            return ['success' => false, 'message' => 'Ma\'lumot yo\'q'];
        }

        // Bosqichlar o'rtacha ball
        $stageAvgs = $this->calculateStageAverages($businessId, $operatorId, $since);
        $weakest = $this->findWeakestStage($stageAvgs);

        // Sentiment taqsimi
        $sentiments = DB::table('call_analyses')
            ->where('business_id', $businessId)
            ->where('operator_id', $operatorId)
            ->where('created_at', '>=', $since)
            ->select('sentiment_customer', DB::raw('count(*) as cnt'))
            ->groupBy('sentiment_customer')
            ->pluck('cnt', 'sentiment_customer')
            ->toArray();

        // Trend (haftalik ball)
        $trend = DB::table('call_analyses')
            ->where('business_id', $businessId)
            ->where('operator_id', $operatorId)
            ->where('created_at', '>=', $since)
            ->select(DB::raw('DATE(created_at) as day'), DB::raw('AVG(overall_score) as avg_score'), DB::raw('COUNT(*) as cnt'))
            ->groupBy('day')
            ->orderBy('day')
            ->get();

        $user = DB::table('users')->where('id', $operatorId)->first(['name', 'email']);

        return [
            'success' => true,
            'operator' => [
                'id' => $operatorId,
                'name' => $user->name ?? 'Noma\'lum',
                'email' => $user->email ?? null,
            ],
            'period_days' => $days,
            'stats' => [
                'total_calls' => (int) $base->total_calls,
                'avg_score' => round($base->avg_score ?? 0, 1),
                'avg_compliance' => round($base->avg_compliance ?? 0, 1),
                'avg_talk_ratio' => round($base->avg_talk_ratio ?? 0, 1),
                'avg_win_prob' => round($base->avg_win_prob ?? 0, 1),
                'best_score' => round($base->best_score ?? 0, 1),
                'worst_score' => round($base->worst_score ?? 0, 1),
                'sales' => (int) $base->sales,
                'lost' => (int) $base->lost,
                'leads' => (int) $base->leads,
                'conversion_rate' => $base->total_calls > 0
                    ? round($base->sales / $base->total_calls * 100, 1) : 0,
            ],
            'stage_avgs' => $stageAvgs,
            'weakest_stage' => $weakest,
            'sentiments' => [
                'positive' => (int) ($sentiments['positive'] ?? 0),
                'neutral' => (int) ($sentiments['neutral'] ?? 0),
                'negative' => (int) ($sentiments['negative'] ?? 0),
            ],
            'trend' => $trend->map(fn($t) => [
                'day' => $t->day,
                'score' => round($t->avg_score, 1),
                'calls' => $t->cnt,
            ]),
            'grade' => $this->getGrade($base->avg_score ?? 0),
        ];
    }

    /**
     * Bosqichlar bo'yicha o'rtacha ball
     */
    private function calculateStageAverages(string $businessId, string $operatorId, $since): array
    {
        $analyses = DB::table('call_analyses')
            ->where('business_id', $businessId)
            ->where('operator_id', $operatorId)
            ->where('created_at', '>=', $since)
            ->whereNotNull('stage_scores')
            ->get(['stage_scores']);

        $stages = ['greeting', 'discovery', 'presentation', 'objection_handling', 'closing', 'rapport', 'cta'];
        $sums = array_fill_keys($stages, 0);
        $counts = array_fill_keys($stages, 0);

        foreach ($analyses as $a) {
            $scores = json_decode($a->stage_scores, true);
            if (!is_array($scores)) continue;
            foreach ($stages as $s) {
                if (isset($scores[$s])) {
                    $sums[$s] += $scores[$s];
                    $counts[$s]++;
                }
            }
        }

        $result = [];
        foreach ($stages as $s) {
            $result[$s] = $counts[$s] > 0 ? round($sums[$s] / $counts[$s], 1) : 0;
        }
        return $result;
    }

    /**
     * Eng zaif bosqichni topish
     */
    private function findWeakestStage(array $stageAvgs): ?array
    {
        $lowest = null;
        $lowestScore = 101;
        foreach ($stageAvgs as $stage => $score) {
            if ($score > 0 && $score < $lowestScore) {
                $lowestScore = $score;
                $lowest = $stage;
            }
        }

        if (!$lowest) return null;

        $labels = [
            'greeting' => 'Salomlashish',
            'discovery' => 'Ehtiyoj aniqlash',
            'presentation' => 'Taqdimot',
            'objection_handling' => 'E\'tirozlarni hal qilish',
            'closing' => 'Yopish',
            'rapport' => 'Munosabat qurish',
            'cta' => 'Keyingi qadam',
        ];

        return [
            'stage' => $lowest,
            'label' => $labels[$lowest] ?? $lowest,
            'score' => $lowestScore,
        ];
    }

    private function getGrade(float $score): string
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
