<?php

namespace App\Services\Agent\Analytics\Tools;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Savdo bosqichlari tahlil vositasi.
 * Bazadan funnel ma'lumotlarini olib, bosqichlar bo'yicha konversiyani hisoblaydi (bepul).
 */
class FunnelAnalysisTool
{
    /**
     * Savdo bosqichlari tahlili
     */
    public function analyze(string $businessId, ?string $startDate = null, ?string $endDate = null): array
    {
        try {
            $start = $startDate ?? now()->subDays(30)->toDateString();
            $end = $endDate ?? now()->toDateString();

            // Bosqichlar bo'yicha leadlar soni
            $stages = DB::table('leads')
                ->where('business_id', $businessId)
                ->whereBetween('created_at', [$start, $end . ' 23:59:59'])
                ->select('status', DB::raw('COUNT(*) as count'))
                ->groupBy('status')
                ->pluck('count', 'status')
                ->toArray();

            $totalLeads = array_sum($stages);

            // Bosqichlar orasidagi konversiya
            $funnel = [];
            $standardStages = ['new', 'contacted', 'qualified', 'proposal', 'negotiation', 'won', 'lost'];

            $previousCount = $totalLeads;
            foreach ($standardStages as $stage) {
                $count = $stages[$stage] ?? 0;
                $conversionFromPrevious = $previousCount > 0 ? round(($count / $previousCount) * 100, 1) : 0;
                $conversionFromTotal = $totalLeads > 0 ? round(($count / $totalLeads) * 100, 1) : 0;

                $funnel[] = [
                    'stage' => $stage,
                    'count' => $count,
                    'conversion_from_previous' => $conversionFromPrevious,
                    'conversion_from_total' => $conversionFromTotal,
                ];

                if ($count > 0) {
                    $previousCount = $count;
                }
            }

            // O'rtacha vaqt (leadning bir bosqichdan keyingisiga o'tish vaqti)
            $avgTimeToClose = DB::table('leads')
                ->where('business_id', $businessId)
                ->where('status', 'won')
                ->whereBetween('created_at', [$start, $end . ' 23:59:59'])
                ->selectRaw('AVG(TIMESTAMPDIFF(HOUR, created_at, updated_at)) as avg_hours')
                ->value('avg_hours');

            return [
                'success' => true,
                'period' => ['start' => $start, 'end' => $end],
                'total_leads' => $totalLeads,
                'stages' => $funnel,
                'won_count' => $stages['won'] ?? 0,
                'lost_count' => $stages['lost'] ?? 0,
                'overall_conversion' => $totalLeads > 0
                    ? round((($stages['won'] ?? 0) / $totalLeads) * 100, 1)
                    : 0,
                'avg_time_to_close_hours' => round((float) $avgTimeToClose, 1),
            ];

        } catch (\Exception $e) {
            Log::warning('FunnelAnalysisTool: xatolik', ['error' => $e->getMessage()]);
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
}
