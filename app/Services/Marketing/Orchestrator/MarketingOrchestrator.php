<?php

namespace App\Services\Marketing\Orchestrator;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * MARKETING ORCHESTRATOR — Yagona marketer aqli (brain)
 *
 * Barcha marketing ma'lumotlarini yig'adi va sinxron tahlil beradi.
 * Har bir qism (content, competitor, channel, campaign, offer) alohida emas —
 * yaxlit marketer sifatida ishlaydi.
 *
 * Vazifalari:
 *   1. Yagona snapshot (hamma joyga bir tomonli)
 *   2. Marketing health score
 *   3. Priority actions (nimani birinchi qilish kerak)
 *   4. Cross-module insights (qismlar orasidagi bog'liqlik)
 *   5. Daily briefing (ertalab nimani qilish kerak)
 */
class MarketingOrchestrator
{
    private const CACHE_TTL = 600; // 10 daqiqa

    public function __construct(
        private HealthCalculator $healthCalc,
        private PriorityRanker $priorityRanker,
        private CrossInsightsDetector $insightsDetector,
    ) {}

    /**
     * Yagona marketing snapshot
     */
    public function getSnapshot(string $businessId): array
    {
        return Cache::remember("marketing_snapshot:{$businessId}", self::CACHE_TTL, function () use ($businessId) {
            try {
                $data = $this->gatherAllData($businessId);

                $health = $this->healthCalc->calculate($data);
                $priorities = $this->priorityRanker->rank($data, $health);
                $insights = $this->insightsDetector->detect($data);

                return [
                    'business_id' => $businessId,
                    'generated_at' => now()->toISOString(),
                    'health' => $health,
                    'priorities' => $priorities,
                    'insights' => $insights,
                    'data_summary' => $this->summarize($data),
                ];
            } catch (\Exception $e) {
                Log::error('MarketingOrchestrator xato', [
                    'business_id' => $businessId,
                    'error' => $e->getMessage(),
                ]);
                return ['error' => $e->getMessage()];
            }
        });
    }

    /**
     * Snapshot ni yangilash (kesh tozalash)
     */
    public function invalidate(string $businessId): void
    {
        Cache::forget("marketing_snapshot:{$businessId}");
    }

    /**
     * Kunlik briefing — marketer ertalab nimani ko'rishi kerak
     */
    public function dailyBriefing(string $businessId): array
    {
        $snapshot = $this->getSnapshot($businessId);

        return [
            'greeting' => $this->buildGreeting($snapshot),
            'yesterday_highlights' => $this->getYesterdayHighlights($businessId),
            'top_3_actions' => array_slice($snapshot['priorities'] ?? [], 0, 3),
            'alerts' => $this->getUrgentAlerts($businessId),
            'health_score' => $snapshot['health']['overall'] ?? 0,
        ];
    }

    /**
     * Barcha ma'lumotlarni yig'ish
     */
    private function gatherAllData(string $businessId): array
    {
        return [
            'channels' => $this->getChannelsData($businessId),
            'content' => $this->getContentData($businessId),
            'offers' => $this->getOffersData($businessId),
            'competitors' => $this->getCompetitorsData($businessId),
            'campaigns' => $this->getCampaignsData($businessId),
            'dream_buyer' => $this->getDreamBuyerData($businessId),
            'kpi' => $this->getKpiData($businessId),
        ];
    }

    private function getChannelsData(string $businessId): array
    {
        try {
            $channels = DB::table('marketing_channels')
                ->where('business_id', $businessId)
                ->where('is_active', true)
                ->get();

            return [
                'active_count' => $channels->count(),
                'types' => $channels->pluck('type')->toArray(),
                'list' => $channels->toArray(),
            ];
        } catch (\Exception $e) {
            return ['active_count' => 0, 'types' => [], 'list' => []];
        }
    }

    private function getContentData(string $businessId): array
    {
        try {
            $since = now()->subDays(30)->format('Y-m-d');

            $stats = DB::table('content_generations')
                ->where('business_id', $businessId)
                ->where('created_at', '>=', $since)
                ->selectRaw('
                    COUNT(*) as total,
                    SUM(CASE WHEN status = "completed" THEN 1 ELSE 0 END) as completed,
                    SUM(CASE WHEN was_published = 1 THEN 1 ELSE 0 END) as published,
                    AVG(post_engagement_rate) as avg_engagement,
                    SUM(post_likes) as total_likes,
                    SUM(post_comments) as total_comments
                ')->first();

            return [
                'total_30d' => (int) ($stats->total ?? 0),
                'completed' => (int) ($stats->completed ?? 0),
                'published' => (int) ($stats->published ?? 0),
                'avg_engagement' => round($stats->avg_engagement ?? 0, 2),
                'total_likes' => (int) ($stats->total_likes ?? 0),
                'total_comments' => (int) ($stats->total_comments ?? 0),
                'has_style_guide' => DB::table('content_style_guides')
                    ->where('business_id', $businessId)->exists(),
            ];
        } catch (\Exception $e) {
            return ['total_30d' => 0, 'published' => 0, 'avg_engagement' => 0];
        }
    }

    private function getOffersData(string $businessId): array
    {
        try {
            $offers = DB::table('offers')
                ->where('business_id', $businessId)
                ->selectRaw('COUNT(*) as total, SUM(CASE WHEN status = "active" THEN 1 ELSE 0 END) as active')
                ->first();

            return [
                'total' => (int) ($offers->total ?? 0),
                'active' => (int) ($offers->active ?? 0),
            ];
        } catch (\Exception $e) {
            return ['total' => 0, 'active' => 0];
        }
    }

    private function getCompetitorsData(string $businessId): array
    {
        try {
            $count = DB::table('competitors')->where('business_id', $businessId)->count();

            $recentActivities = 0;
            if (DB::getSchemaBuilder()->hasTable('competitor_activities')) {
                $recentActivities = DB::table('competitor_activities')
                    ->whereIn('competitor_id', DB::table('competitors')->where('business_id', $businessId)->pluck('id'))
                    ->where('created_at', '>=', now()->subDays(7))
                    ->count();
            }

            return [
                'total' => $count,
                'recent_activities_7d' => $recentActivities,
                'monitoring_active' => $recentActivities > 0,
            ];
        } catch (\Exception $e) {
            return ['total' => 0, 'recent_activities_7d' => 0, 'monitoring_active' => false];
        }
    }

    private function getCampaignsData(string $businessId): array
    {
        try {
            if (!DB::getSchemaBuilder()->hasTable('marketing_campaigns')) {
                return ['total' => 0, 'active' => 0];
            }

            $data = DB::table('marketing_campaigns')
                ->where('business_id', $businessId)
                ->selectRaw('
                    COUNT(*) as total,
                    SUM(CASE WHEN status = "active" THEN 1 ELSE 0 END) as active
                ')->first();

            return [
                'total' => (int) ($data->total ?? 0),
                'active' => (int) ($data->active ?? 0),
            ];
        } catch (\Exception $e) {
            return ['total' => 0, 'active' => 0];
        }
    }

    private function getDreamBuyerData(string $businessId): array
    {
        try {
            $buyer = DB::table('dream_buyers')
                ->where('business_id', $businessId)
                ->first(['id', 'name']);

            return [
                'exists' => (bool) $buyer,
                'name' => $buyer->name ?? null,
                'count' => DB::table('dream_buyers')->where('business_id', $businessId)->count(),
            ];
        } catch (\Exception $e) {
            return ['exists' => false, 'name' => null, 'count' => 0];
        }
    }

    private function getKpiData(string $businessId): array
    {
        try {
            $since = now()->subDays(30)->format('Y-m-d');

            $kpi = DB::table('kpi_daily_entries')
                ->where('business_id', $businessId)
                ->where('date', '>=', $since)
                ->selectRaw('
                    SUM(leads_total) as leads,
                    SUM(sales_total) as sales,
                    SUM(revenue_total) as revenue,
                    SUM(spend_total) as spend
                ')->first();

            $roas = ($kpi->spend ?? 0) > 0 ? round(($kpi->revenue ?? 0) / $kpi->spend, 2) : 0;

            return [
                'leads_30d' => (int) ($kpi->leads ?? 0),
                'sales_30d' => (int) ($kpi->sales ?? 0),
                'revenue_30d' => (float) ($kpi->revenue ?? 0),
                'spend_30d' => (float) ($kpi->spend ?? 0),
                'roas' => $roas,
            ];
        } catch (\Exception $e) {
            return ['leads_30d' => 0, 'sales_30d' => 0, 'revenue_30d' => 0, 'spend_30d' => 0, 'roas' => 0];
        }
    }

    private function summarize(array $data): array
    {
        return [
            'channels_active' => $data['channels']['active_count'],
            'content_published_30d' => $data['content']['published'],
            'avg_engagement' => $data['content']['avg_engagement'],
            'offers_active' => $data['offers']['active'],
            'campaigns_active' => $data['campaigns']['active'],
            'competitors_tracked' => $data['competitors']['total'],
            'competitor_monitoring' => $data['competitors']['monitoring_active'],
            'dream_buyer_set' => $data['dream_buyer']['exists'],
            'roas_30d' => $data['kpi']['roas'],
            'revenue_30d' => $data['kpi']['revenue_30d'],
        ];
    }

    private function buildGreeting(array $snapshot): string
    {
        $hour = (int) now()->format('H');
        $greeting = $hour < 12 ? 'Xayrli tong' : ($hour < 18 ? 'Xayrli kun' : 'Xayrli kech');
        $health = $snapshot['health']['overall'] ?? 0;

        return $greeting . '! Marketing sog\'ligi: ' . $health . '/100';
    }

    private function getYesterdayHighlights(string $businessId): array
    {
        try {
            $yesterday = now()->subDay()->format('Y-m-d');

            $contentYesterday = DB::table('content_generations')
                ->where('business_id', $businessId)
                ->whereDate('created_at', $yesterday)
                ->count();

            $kpi = DB::table('kpi_daily_entries')
                ->where('business_id', $businessId)
                ->where('date', $yesterday)
                ->first();

            return [
                'content_created' => $contentYesterday,
                'leads' => $kpi->leads_total ?? 0,
                'sales' => $kpi->sales_total ?? 0,
                'revenue' => $kpi->revenue_total ?? 0,
            ];
        } catch (\Exception $e) {
            return [];
        }
    }

    private function getUrgentAlerts(string $businessId): array
    {
        $alerts = [];

        try {
            // Lost yesterday's day analytics (placeholder — real alerts FBI keyin)
            $kpi = DB::table('kpi_daily_entries')
                ->where('business_id', $businessId)
                ->where('date', '>=', now()->subDays(7))
                ->selectRaw('AVG(spend_total) as avg_spend, AVG(revenue_total) as avg_revenue')
                ->first();

            if ($kpi && $kpi->avg_spend > 0) {
                $roas = $kpi->avg_revenue / $kpi->avg_spend;
                if ($roas < 1) {
                    $alerts[] = [
                        'type' => 'low_roas',
                        'severity' => 'high',
                        'message' => 'Oxirgi 7 kun ROAS 1 dan past (' . round($roas, 2) . 'x) — zarar!',
                    ];
                }
            }
        } catch (\Exception $e) {}

        return $alerts;
    }
}
