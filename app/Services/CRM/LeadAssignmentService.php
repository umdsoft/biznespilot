<?php

namespace App\Services\CRM;

use App\Models\Lead;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Auto Lead Assignment — operatorlarga avtomatik biriktirish.
 *
 * Strategiyalar:
 *   - round_robin (navbatma-navbat)
 *   - workload_based (eng kam yuk)
 *   - skill_based (mahorat bo'yicha — kelajakda)
 */
class LeadAssignmentService
{
    /**
     * Lid uchun avtomatik operator topish va biriktirish
     */
    public function autoAssign(Lead $lead, string $strategy = 'workload_based'): ?string
    {
        if ($lead->assigned_to) return $lead->assigned_to;

        $operators = $this->getActiveOperators($lead->business_id);
        if (empty($operators)) {
            Log::info('Auto-assign: faol operator yo\'q', ['lead_id' => $lead->id]);
            return null;
        }

        $operatorId = match ($strategy) {
            'round_robin' => $this->roundRobin($lead->business_id, $operators),
            'workload_based' => $this->byWorkload($lead->business_id, $operators),
            default => $operators[0],
        };

        if ($operatorId) {
            $lead->update(['assigned_to' => $operatorId]);
            Log::info('Auto-assign: lid biriktirildi', [
                'lead_id' => $lead->id,
                'operator_id' => $operatorId,
                'strategy' => $strategy,
            ]);
        }

        return $operatorId;
    }

    /**
     * Bir nechta lidni toplu assign qilish
     */
    public function bulkAssign(string $businessId, array $leadIds, string $strategy = 'workload_based'): array
    {
        $assigned = 0;
        $skipped = 0;

        foreach ($leadIds as $leadId) {
            $lead = Lead::find($leadId);
            if (!$lead || $lead->business_id !== $businessId) {
                $skipped++;
                continue;
            }
            if ($this->autoAssign($lead, $strategy)) {
                $assigned++;
            } else {
                $skipped++;
            }
        }

        return ['assigned' => $assigned, 'skipped' => $skipped];
    }

    /**
     * Barcha unassigned lidlarni avto-biriktirish
     */
    public function assignAllUnassigned(string $businessId, string $strategy = 'workload_based'): array
    {
        $unassigned = Lead::where('business_id', $businessId)
            ->whereNull('assigned_to')
            ->whereNotIn('status', ['won', 'lost'])
            ->limit(500)
            ->pluck('id')
            ->toArray();

        return $this->bulkAssign($businessId, $unassigned, $strategy);
    }

    /**
     * Round robin (navbatma-navbat)
     */
    private function roundRobin(string $businessId, array $operators): ?string
    {
        // Cache'da indeks saqlash
        $key = "lead_assign_rr:{$businessId}";
        $index = (int) cache()->get($key, 0);
        $operatorId = $operators[$index % count($operators)];
        cache()->put($key, $index + 1, now()->addDays(30));
        return $operatorId;
    }

    /**
     * Eng kam yuk operatorga biriktirish
     */
    private function byWorkload(string $businessId, array $operators): ?string
    {
        // Har operator uchun in-progress lid sonini hisoblash
        $workloads = DB::table('leads')
            ->where('business_id', $businessId)
            ->whereIn('assigned_to', $operators)
            ->whereNotIn('status', ['won', 'lost'])
            ->select('assigned_to', DB::raw('COUNT(*) as load'))
            ->groupBy('assigned_to')
            ->pluck('load', 'assigned_to')
            ->toArray();

        // 0 dan boshlash hammada
        $balanced = [];
        foreach ($operators as $opId) {
            $balanced[$opId] = $workloads[$opId] ?? 0;
        }

        // Eng kam yukli operatorni topish
        asort($balanced);
        return array_key_first($balanced);
    }

    /**
     * Faol operatorlarni olish (sales bo'limi)
     */
    private function getActiveOperators(string $businessId): array
    {
        try {
            return DB::table('business_user as bu')
                ->join('users as u', 'bu.user_id', '=', 'u.id')
                ->where('bu.business_id', $businessId)
                ->whereIn('bu.department', ['sales_head', 'sales_operator', 'sales'])
                ->where(function ($q) {
                    $q->whereNull('bu.is_active')->orWhere('bu.is_active', true);
                })
                ->pluck('u.id')
                ->toArray();
        } catch (\Exception $e) {
            // Fallback — barcha userlar
            return DB::table('business_user')
                ->where('business_id', $businessId)
                ->pluck('user_id')
                ->toArray();
        }
    }

    /**
     * Operator workload statistikasi
     */
    public function getWorkloadStats(string $businessId): array
    {
        $operators = $this->getActiveOperators($businessId);
        if (empty($operators)) return [];

        $stats = DB::table('leads as l')
            ->leftJoin('users as u', 'l.assigned_to', '=', 'u.id')
            ->where('l.business_id', $businessId)
            ->whereIn('l.assigned_to', $operators)
            ->whereNotIn('l.status', ['won', 'lost'])
            ->select('l.assigned_to', 'u.name')
            ->selectRaw('
                COUNT(*) as total,
                SUM(CASE WHEN l.score >= 70 THEN 1 ELSE 0 END) as hot,
                AVG(l.score) as avg_score
            ')
            ->groupBy('l.assigned_to', 'u.name')
            ->orderByDesc('total')
            ->get();

        return $stats->map(fn($s) => [
            'operator_id' => $s->assigned_to,
            'operator_name' => $s->name,
            'total_leads' => (int) $s->total,
            'hot_leads' => (int) $s->hot,
            'avg_score' => round($s->avg_score ?? 0, 1),
        ])->toArray();
    }
}
