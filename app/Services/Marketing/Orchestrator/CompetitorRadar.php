<?php

namespace App\Services\Marketing\Orchestrator;

use App\Events\Marketing\CompetitorActivityDetected;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Competitor Radar — raqobatchilarni kuzatish va intelligence tizimi.
 *
 * Vazifalari:
 *   1. Faoliyatlarni qayd qilish (activity)
 *   2. Insight generatsiya (trend, narx, aksiya)
 *   3. Alert triggering (muhim o'zgarishlar)
 *   4. Haftalik digest
 *
 * Event'lar chiqaradi — boshqa qismlar reaksiya qilish uchun.
 */
class CompetitorRadar
{
    /**
     * Raqobatchi faoliyatini qayd qilish (manual yoki webhook orqali)
     */
    public function recordActivity(string $businessId, string $competitorId, array $data): ?string
    {
        try {
            $activityId = Str::uuid()->toString();

            DB::table('competitor_activities')->insert([
                'id' => $activityId,
                'competitor_id' => $competitorId,
                'type' => $data['type'] ?? 'general',
                'title' => $data['title'] ?? '',
                'description' => $data['description'] ?? '',
                'url' => $data['url'] ?? null,
                'activity_date' => $data['activity_date'] ?? now()->toDateString(),
                'metadata' => json_encode($data['metadata'] ?? []),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // Event fire — Orchestrator cache invalidate qiladi
            event(new CompetitorActivityDetected(
                businessId: $businessId,
                competitorId: $competitorId,
                activityType: $data['type'] ?? 'general',
                data: $data,
            ));

            return $activityId;
        } catch (\Exception $e) {
            Log::error('CompetitorRadar recordActivity xato', ['error' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * Haftalik digest — raqobatchi nima qildi
     */
    public function weeklyDigest(string $businessId): array
    {
        $since = now()->subDays(7);
        $competitorIds = DB::table('competitors')->where('business_id', $businessId)->pluck('id');

        if ($competitorIds->isEmpty()) {
            return ['success' => false, 'message' => 'Raqobatchilar yo\'q'];
        }

        $activities = DB::table('competitor_activities as ca')
            ->leftJoin('competitors as c', 'ca.competitor_id', '=', 'c.id')
            ->whereIn('ca.competitor_id', $competitorIds)
            ->where('ca.created_at', '>=', $since)
            ->orderByDesc('ca.created_at')
            ->get(['ca.id', 'ca.type', 'ca.title', 'ca.description', 'ca.activity_date',
                   'c.name as competitor_name']);

        // Har raqobatchi bo'yicha guruhlash
        $byCompetitor = $activities->groupBy('competitor_name');

        // Eng faol raqobatchi
        $mostActive = null;
        if ($byCompetitor->isNotEmpty()) {
            $mostActiveName = $byCompetitor->map(fn($g) => $g->count())->sortDesc()->keys()->first();
            $mostActive = [
                'name' => $mostActiveName,
                'count' => $byCompetitor->get($mostActiveName)->count(),
            ];
        }

        // Type bo'yicha
        $byType = $activities->groupBy('type')->map(fn($g) => $g->count());

        return [
            'success' => true,
            'period' => '7 kun',
            'total_activities' => $activities->count(),
            'most_active' => $mostActive,
            'by_type' => $byType->toArray(),
            'by_competitor' => $byCompetitor->map(fn($g) => $g->count())->toArray(),
            'recent_activities' => $activities->take(10)->values()->toArray(),
            'insights' => $this->generateInsights($activities, $competitorIds->count()),
        ];
    }

    /**
     * Raqobatchi × Bizning kontent solishtirish
     */
    public function competitorVsYou(string $businessId): array
    {
        $since = now()->subDays(30);

        // Bizning kontent
        $ourContent = DB::table('content_generations')
            ->where('business_id', $businessId)
            ->where('created_at', '>=', $since)
            ->where('was_published', true)
            ->count();

        // Raqobatchi faoliyatlari
        $competitorIds = DB::table('competitors')->where('business_id', $businessId)->pluck('id');
        $competitorActivities = 0;
        if ($competitorIds->isNotEmpty()) {
            $competitorActivities = DB::table('competitor_activities')
                ->whereIn('competitor_id', $competitorIds)
                ->where('created_at', '>=', $since)
                ->count();
        }

        $competitorCount = $competitorIds->count();
        $avgCompetitorActivity = $competitorCount > 0 ? round($competitorActivities / $competitorCount, 1) : 0;

        $verdict = 'neutral';
        if ($ourContent > $avgCompetitorActivity * 1.5) $verdict = 'ahead';
        elseif ($ourContent < $avgCompetitorActivity * 0.5) $verdict = 'behind';

        return [
            'our_content_30d' => $ourContent,
            'competitor_activities_30d' => $competitorActivities,
            'competitor_count' => $competitorCount,
            'avg_per_competitor' => $avgCompetitorActivity,
            'verdict' => $verdict,
            'recommendation' => $this->getVerdictRecommendation($verdict, $ourContent, $avgCompetitorActivity),
        ];
    }

    /**
     * Insight generatsiya (activity'lardan naqsh)
     */
    private function generateInsights($activities, int $competitorCount): array
    {
        $insights = [];

        if ($activities->count() === 0) {
            $insights[] = [
                'severity' => 'medium',
                'title' => 'Raqobatchilar kuzatilmayapti',
                'message' => 'Oxirgi 7 kunda hech qanday faoliyat yozib olinmagan. Monitoring yoqing yoki manual kiriting.',
            ];
            return $insights;
        }

        // Promotion/aksiya aniqlangan
        $promotions = $activities->filter(fn($a) => in_array($a->type, ['promotion', 'discount', 'sale']));
        if ($promotions->count() > 0) {
            $insights[] = [
                'severity' => 'high',
                'title' => "Raqobatchi {$promotions->count()} ta aksiya chiqargan",
                'message' => 'Javob aksiya yoki kampaniya tayyorlashingiz kerak.',
            ];
        }

        // Yangi post
        $posts = $activities->filter(fn($a) => in_array($a->type, ['new_post', 'content', 'post']));
        if ($posts->count() > 5) {
            $insights[] = [
                'severity' => 'medium',
                'title' => "Raqobatchi faol kontent qilmoqda ({$posts->count()} post/hafta)",
                'message' => 'Siz ham kontent tempini oshirishingiz kerak.',
            ];
        }

        return $insights;
    }

    private function getVerdictRecommendation(string $verdict, int $ours, float $theirs): string
    {
        return match ($verdict) {
            'ahead' => "Siz raqobatchilardan ko'proq kontent qilayapsiz ({$ours} vs {$theirs}). Sifatni pasaytirmang.",
            'behind' => "Raqobatchilar kontentda oldinda. Siz {$ours}, ular o'rtacha {$theirs}. Tempni oshiring.",
            default => "Raqobatchilar bilan taxminan teng. Sifat va engagement ga e'tibor bering.",
        };
    }
}
