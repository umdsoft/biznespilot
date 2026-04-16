<?php

namespace App\Services\Agent\CallCenter;

use Illuminate\Support\Facades\DB;

/**
 * Lost lidlar tahlili — qaysi operator qaysi sabab bilan lid yo'qotadi.
 *
 * Asosiy savollar:
 *   - Qaysi operator eng ko'p yo'qotyapti?
 *   - Qaysi sabablar bilan?
 *   - Qaysi bosqichda jarayon to'xtaydi?
 *   - Anti-patterns × operator matrix
 */
class LostAnalysisService
{
    /**
     * Operator × Lost reason matritsa
     */
    public function operatorLostMatrix(string $businessId, int $days = 30): array
    {
        $since = now()->subDays($days);

        // call_analyses dan — outcome=lost yoki lead_id orqali lost lidlarga ulangan
        $data = DB::table('call_analyses as ca')
            ->leftJoin('leads as l', 'ca.lead_id', '=', 'l.id')
            ->leftJoin('users as u', 'ca.operator_id', '=', 'u.id')
            ->where('ca.business_id', $businessId)
            ->where('ca.created_at', '>=', $since)
            ->where(function ($q) {
                $q->where('ca.outcome', 'lost')
                  ->orWhere('l.status', 'lost');
            })
            ->select(
                'ca.operator_id',
                'u.name as operator_name',
                'l.lost_reason',
                DB::raw('COUNT(*) as cnt'),
                DB::raw('AVG(ca.overall_score) as avg_score'),
            )
            ->groupBy('ca.operator_id', 'u.name', 'l.lost_reason')
            ->get();

        // Matrix qurish
        $operators = [];
        $reasons = [];
        $matrix = [];

        foreach ($data as $row) {
            $opId = $row->operator_id ?: 'unknown';
            $opName = $row->operator_name ?: 'Noma\'lum';
            $reason = $row->lost_reason ?: 'Sabab yozilmagan';

            $operators[$opId] = $opName;
            $reasons[$reason] = ($reasons[$reason] ?? 0) + $row->cnt;

            if (!isset($matrix[$opId])) {
                $matrix[$opId] = [];
            }
            $matrix[$opId][$reason] = [
                'count' => (int) $row->cnt,
                'avg_score' => round($row->avg_score ?? 0, 1),
            ];
        }

        // Operatorlar bo'yicha jami lost count
        $operatorTotals = [];
        foreach ($matrix as $opId => $reasonsData) {
            $operatorTotals[$opId] = array_sum(array_column($reasonsData, 'count'));
        }
        arsort($operatorTotals);

        arsort($reasons);

        return [
            'period_days' => $days,
            'operators' => $operators,
            'reasons' => array_keys($reasons),
            'reason_totals' => $reasons,
            'operator_totals' => $operatorTotals,
            'matrix' => $matrix,
            'insights' => $this->generateInsights($operators, $reasons, $matrix, $operatorTotals),
        ];
    }

    /**
     * Anti-patterns × Operator matritsa
     */
    public function operatorAntiPatterns(string $businessId, int $days = 30): array
    {
        $since = now()->subDays($days);

        $analyses = DB::table('call_analyses')
            ->where('business_id', $businessId)
            ->where('created_at', '>=', $since)
            ->whereNotNull('operator_id')
            ->whereNotNull('anti_patterns')
            ->get(['operator_id', 'anti_patterns']);

        $opPatterns = [];
        foreach ($analyses as $a) {
            $patterns = json_decode($a->anti_patterns, true);
            if (!is_array($patterns)) continue;

            if (!isset($opPatterns[$a->operator_id])) {
                $opPatterns[$a->operator_id] = [];
            }

            foreach ($patterns as $p) {
                $type = $p['type'] ?? 'unknown';
                $opPatterns[$a->operator_id][$type] = ($opPatterns[$a->operator_id][$type] ?? 0) + 1;
            }
        }

        // Har operator uchun top-3 antipattern
        $result = [];
        foreach ($opPatterns as $opId => $patterns) {
            $user = DB::table('users')->where('id', $opId)->first(['name']);
            arsort($patterns);
            $result[] = [
                'operator_id' => $opId,
                'operator_name' => $user->name ?? 'Noma\'lum',
                'total_patterns' => array_sum($patterns),
                'top_patterns' => array_slice($patterns, 0, 3, true),
            ];
        }

        usort($result, fn($a, $b) => $b['total_patterns'] - $a['total_patterns']);
        return $result;
    }

    /**
     * Insights yaratish
     */
    private function generateInsights(array $operators, array $reasons, array $matrix, array $operatorTotals): array
    {
        $insights = [];

        // Eng ko'p yo'qotgan operator
        if (!empty($operatorTotals)) {
            $worstOp = array_key_first($operatorTotals);
            $insights[] = [
                'type' => 'worst_operator',
                'severity' => 'high',
                'title' => 'Eng ko\'p lid yo\'qotgan operator',
                'message' => ($operators[$worstOp] ?? 'Noma\'lum') . " — " . $operatorTotals[$worstOp] . " lid yo'qotgan",
                'operator_id' => $worstOp,
            ];
        }

        // Eng katta sabab
        if (!empty($reasons)) {
            $topReason = array_key_first($reasons);
            $topCount = $reasons[$topReason];
            $insights[] = [
                'type' => 'top_reason',
                'severity' => 'medium',
                'title' => 'Eng ko\'p yo\'qotish sababi',
                'message' => "\"$topReason\" — $topCount marta takrorlangan",
            ];
        }

        return $insights;
    }
}
