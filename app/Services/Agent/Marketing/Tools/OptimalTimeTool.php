<?php

namespace App\Services\Agent\Marketing\Tools;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Eng yaxshi post joylash vaqtini aniqlash vositasi (bepul).
 * instagram_media jadvalidan posted_at ustuni orqali hisoblaydi.
 */
class OptimalTimeTool
{
    /**
     * Hafta kunlari va soatlar bo'yicha eng yaxshi vaqtlarni aniqlash
     */
    public function getOptimalTimes(string $businessId, int $days = 60): array
    {
        try {
            $accountIds = DB::table('instagram_accounts')
                ->where('business_id', $businessId)
                ->pluck('id')
                ->toArray();

            if (empty($accountIds)) {
                return ['success' => true, 'top_times' => [], 'today_best' => null];
            }

            $timeStats = DB::table('instagram_media')
                ->whereIn('account_id', $accountIds)
                ->where('posted_at', '>=', now()->subDays($days))
                ->selectRaw('
                    DAYOFWEEK(posted_at) as day_of_week,
                    HOUR(posted_at) as hour,
                    COUNT(*) as post_count,
                    COALESCE(AVG(engagement_rate), 0) as avg_engagement,
                    COALESCE(AVG(reach), 0) as avg_reach
                ')
                ->groupByRaw('DAYOFWEEK(posted_at), HOUR(posted_at)')
                ->orderByDesc('avg_engagement')
                ->get()
                ->toArray();

            $topTimes = array_slice($timeStats, 0, 5);

            $todayDow = now()->dayOfWeek + 1;
            $todayBest = collect($timeStats)
                ->where('day_of_week', $todayDow)
                ->sortByDesc('avg_engagement')
                ->first();

            $dayNames = [1 => 'Yakshanba', 2 => 'Dushanba', 3 => 'Seshanba', 4 => 'Chorshanba', 5 => 'Payshanba', 6 => 'Juma', 7 => 'Shanba'];

            return [
                'success' => true,
                'top_times' => array_map(fn ($t) => [
                    'day' => $dayNames[$t->day_of_week] ?? $t->day_of_week,
                    'hour' => sprintf('%02d:00', $t->hour),
                    'avg_engagement' => round($t->avg_engagement, 2),
                    'post_count' => $t->post_count,
                ], $topTimes),
                'today_best' => $todayBest ? [
                    'hour' => sprintf('%02d:00', $todayBest->hour),
                    'avg_engagement' => round($todayBest->avg_engagement, 2),
                ] : null,
            ];
        } catch (\Exception $e) {
            Log::warning('OptimalTimeTool: xatolik', ['error' => $e->getMessage()]);
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
}
