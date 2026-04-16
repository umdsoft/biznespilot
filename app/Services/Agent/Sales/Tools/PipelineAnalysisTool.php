<?php

namespace App\Services\Agent\Sales\Tools;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Sotuv pipeline tahlili — bosqichlar, yo'qotishlar, konversiya
 *
 * Salomatxon (Sotuv) agentiga chuqur ma'lumot beradi.
 */
class PipelineAnalysisTool
{
    /**
     * Pipeline holatini tahlil qilish
     */
    public function analyze(string $businessId): array
    {
        $cacheKey = "pipeline_analysis:{$businessId}";

        return Cache::remember($cacheKey, 300, function () use ($businessId) {
            try {
                // Bosqichlar bo'yicha lidlar
                $byStage = DB::table('leads')
                    ->where('business_id', $businessId)
                    ->select('status', DB::raw('COUNT(*) as cnt, SUM(estimated_value) as value, AVG(score) as avg_score'))
                    ->groupBy('status')
                    ->get()
                    ->keyBy('status');

                $total = $byStage->sum('cnt');
                if ($total === 0) {
                    return ['success' => false, 'message' => 'Lidlar yo\'q'];
                }

                // Asosiy ko'rsatkichlar
                $won = $byStage->get('won')->cnt ?? 0;
                $lost = $byStage->get('lost')->cnt ?? 0;
                $newLeads = $byStage->get('new')->cnt ?? 0;
                $contacted = $byStage->get('contacted')->cnt ?? 0;
                $qualified = $byStage->get('qualified')->cnt ?? 0;

                // Konversiya
                $overallConversion = $total > 0 ? round($won / $total * 100, 1) : 0;
                $contactToQualified = $contacted > 0 ? round($qualified / $contacted * 100, 1) : 0;

                // Eng katta yo'qotish bosqichi
                $stages = ['new' => 'Yangi', 'contacted' => 'Bog\'lanildi', 'qualified' => 'Malakali', 'proposal' => 'Taklif', 'negotiation' => 'Muzokara'];
                $stageOrder = array_keys($stages);
                $biggestDrop = null;
                $maxDrop = 0;

                for ($i = 0; $i < count($stageOrder) - 1; $i++) {
                    $from = $byStage->get($stageOrder[$i]);
                    $to = $byStage->get($stageOrder[$i + 1]);
                    if ($from && $from->cnt > 0) {
                        $drop = $from->cnt - ($to->cnt ?? 0);
                        if ($drop > $maxDrop) {
                            $maxDrop = $drop;
                            $biggestDrop = [
                                'from' => $stages[$stageOrder[$i]],
                                'to' => $stages[$stageOrder[$i + 1]],
                                'lost_count' => $drop,
                                'lost_percent' => round($drop / $from->cnt * 100, 1),
                            ];
                        }
                    }
                }

                // Bu hafta yangi lidlar
                $thisWeek = DB::table('leads')
                    ->where('business_id', $businessId)
                    ->where('created_at', '>=', now()->startOfWeek())
                    ->count();

                // Bu oy yutilgan
                $wonThisMonth = DB::table('leads')
                    ->where('business_id', $businessId)
                    ->where('status', 'won')
                    ->where('updated_at', '>=', now()->startOfMonth())
                    ->count();

                return [
                    'success' => true,
                    'total' => $total,
                    'won' => $won,
                    'lost' => $lost,
                    'overall_conversion' => $overallConversion,
                    'contact_to_qualified' => $contactToQualified,
                    'biggest_drop' => $biggestDrop,
                    'this_week_new' => $thisWeek,
                    'won_this_month' => $wonThisMonth,
                    'pipeline_value' => $byStage->whereNotIn('status', ['won', 'lost'])->sum('value'),
                ];
            } catch (\Exception $e) {
                Log::warning('PipelineAnalysisTool xato', ['error' => $e->getMessage()]);
                return ['success' => false, 'error' => $e->getMessage()];
            }
        });
    }

    /**
     * AI uchun matn formatida
     */
    public function asContext(string $businessId): string
    {
        $a = $this->analyze($businessId);
        if (!($a['success'] ?? false)) return '';

        $parts = ['SOTUV PIPELINE TAHLILI:'];
        $parts[] = "- Jami lidlar: {$a['total']} (yutilgan: {$a['won']}, yo'qotilgan: {$a['lost']})";
        $parts[] = "- Umumiy konversiya: {$a['overall_conversion']}%";
        $parts[] = "- Bu hafta yangi: {$a['this_week_new']} lid, bu oy yutilgan: {$a['won_this_month']}";

        if ($a['biggest_drop']) {
            $bd = $a['biggest_drop'];
            $parts[] = "- ENG KO'P YO'QOTISH: {$bd['from']} → {$bd['to']} bosqichida {$bd['lost_count']} lid ({$bd['lost_percent']}%)";
        }

        if ($a['pipeline_value']) {
            $parts[] = '- Pipeline qiymati: ' . number_format($a['pipeline_value']) . " so'm";
        }

        return implode("\n", $parts);
    }
}
