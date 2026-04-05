<?php

namespace App\Services\Agent\Marketing\Tools;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Raqobatchi ma'lumotlarini bazadan olish vositasi (bepul).
 * Mavjud raqobatchi monitoring tizimidan ma'lumot oladi.
 */
class CompetitorDataTool
{
    /**
     * Raqobatchilar ro'yxati va asosiy ko'rsatkichlari
     */
    public function getCompetitorsSummary(string $businessId): array
    {
        try {
            $competitors = DB::table('competitors')
                ->where('business_id', $businessId)
                ->select(['id', 'name', 'instagram_handle', 'website', 'created_at'])
                ->get()
                ->toArray();

            // Har bir raqobatchi uchun oxirgi metrikalarni olish
            $result = [];
            foreach ($competitors as $comp) {
                $latestMetric = DB::table('competitor_metrics')
                    ->where('competitor_id', $comp->id)
                    ->orderByDesc('created_at')
                    ->first();

                $result[] = [
                    'id' => $comp->id,
                    'name' => $comp->name,
                    'instagram' => $comp->instagram_handle,
                    'followers' => $latestMetric->followers_count ?? null,
                    'engagement_rate' => $latestMetric->engagement_rate ?? null,
                    'posts_per_week' => $latestMetric->posts_per_week ?? null,
                ];
            }

            return ['success' => true, 'competitors' => $result];
        } catch (\Exception $e) {
            Log::warning('CompetitorDataTool: xatolik', ['error' => $e->getMessage()]);
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Raqobatchi oxirgi faoliyatlari
     */
    public function getCompetitorActivities(string $businessId, int $days = 7): array
    {
        try {
            $activities = DB::table('competitor_activities')
                ->join('competitors', 'competitors.id', '=', 'competitor_activities.competitor_id')
                ->where('competitors.business_id', $businessId)
                ->where('competitor_activities.created_at', '>=', now()->subDays($days))
                ->select([
                    'competitors.name as competitor_name',
                    'competitor_activities.activity_type',
                    'competitor_activities.description',
                    'competitor_activities.created_at',
                ])
                ->orderByDesc('competitor_activities.created_at')
                ->limit(20)
                ->get()
                ->toArray();

            return ['success' => true, 'activities' => $activities];
        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
}
