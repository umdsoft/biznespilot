<?php

namespace App\Services\Agent\CallCenter\Performance;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Operator baholash va reyting tizimi.
 * Qo'ng'iroq tahlillari asosida operator samaradorligini hisoblaydi.
 */
class OperatorScorer
{
    /**
     * Operator statistikasini hisoblash
     */
    public function getOperatorStats(string $businessId, string $operatorId, ?string $periodStart = null, ?string $periodEnd = null): array
    {
        $start = $periodStart ?? now()->subDays(30)->toDateString();
        $end = $periodEnd ?? now()->toDateString();

        try {
            $stats = DB::table('call_analyses')
                ->where('business_id', $businessId)
                ->where('operator_id', $operatorId)
                ->whereBetween('created_at', [$start, $end . ' 23:59:59'])
                ->selectRaw('
                    COUNT(*) as total_calls,
                    COALESCE(AVG(overall_score), 0) as avg_score,
                    COALESCE(MAX(overall_score), 0) as best_score,
                    COALESCE(MIN(overall_score), 0) as worst_score,
                    SUM(CASE WHEN outcome = "sale" THEN 1 ELSE 0 END) as sales,
                    SUM(CASE WHEN outcome = "lead" THEN 1 ELSE 0 END) as leads,
                    SUM(CASE WHEN outcome = "lost" THEN 1 ELSE 0 END) as lost
                ')
                ->first();

            $conversionRate = $stats->total_calls > 0
                ? round(($stats->sales / $stats->total_calls) * 100, 1)
                : 0;

            return [
                'success' => true,
                'operator_id' => $operatorId,
                'period' => ['start' => $start, 'end' => $end],
                'total_calls' => (int) $stats->total_calls,
                'avg_score' => round((float) $stats->avg_score, 1),
                'best_score' => (int) $stats->best_score,
                'worst_score' => (int) $stats->worst_score,
                'sales' => (int) $stats->sales,
                'leads' => (int) $stats->leads,
                'lost' => (int) $stats->lost,
                'conversion_rate' => $conversionRate,
            ];
        } catch (\Exception $e) {
            Log::warning('OperatorScorer: xatolik', ['error' => $e->getMessage()]);
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Jamoa reytingi (leaderboard)
     */
    public function getLeaderboard(string $businessId, int $days = 30): array
    {
        try {
            $operators = DB::table('call_analyses')
                ->where('business_id', $businessId)
                ->where('created_at', '>=', now()->subDays($days))
                ->select('operator_id')
                ->selectRaw('
                    COUNT(*) as total_calls,
                    AVG(overall_score) as avg_score,
                    SUM(CASE WHEN outcome = "sale" THEN 1 ELSE 0 END) as sales
                ')
                ->groupBy('operator_id')
                ->orderByDesc('avg_score')
                ->limit(20)
                ->get()
                ->toArray();

            // Operator ismlarini qo'shish
            foreach ($operators as $i => &$op) {
                $user = DB::table('users')->where('id', $op->operator_id)->first(['name']);
                $op->operator_name = $user->name ?? 'Noma\'lum';
                $op->rank = $i + 1;
                $op->avg_score = round($op->avg_score, 1);
                $op->conversion_rate = $op->total_calls > 0
                    ? round(($op->sales / $op->total_calls) * 100, 1)
                    : 0;
            }

            return ['success' => true, 'leaderboard' => $operators];
        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
}
