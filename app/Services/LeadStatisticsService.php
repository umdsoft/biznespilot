<?php

namespace App\Services;

use App\Models\Lead;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

/**
 * Centralized Lead Statistics Service
 * Barcha panellar uchun yagona lead statistika hisob-kitoblari
 */
class LeadStatisticsService
{
    protected int $cacheTTL = 300; // 5 daqiqa

    /**
     * Get basic lead statistics for a business
     */
    public function getLeadStats(string $businessId, ?string $userId = null): array
    {
        $cacheKey = "lead_stats_{$businessId}" . ($userId ? "_{$userId}" : '');

        return Cache::remember($cacheKey, $this->cacheTTL, function () use ($businessId, $userId) {
            $query = Lead::where('business_id', $businessId);

            if ($userId) {
                $query->where('assigned_to', $userId);
            }

            // Single query with aggregation
            $stats = DB::table('leads')
                ->where('business_id', $businessId)
                ->when($userId, fn($q) => $q->where('assigned_to', $userId))
                ->selectRaw('
                    COUNT(*) as total,
                    SUM(CASE WHEN status = "new" THEN 1 ELSE 0 END) as new_count,
                    SUM(CASE WHEN status = "contacted" THEN 1 ELSE 0 END) as contacted,
                    SUM(CASE WHEN status = "qualified" THEN 1 ELSE 0 END) as qualified,
                    SUM(CASE WHEN status = "proposal" THEN 1 ELSE 0 END) as proposal,
                    SUM(CASE WHEN status = "negotiation" THEN 1 ELSE 0 END) as negotiation,
                    SUM(CASE WHEN status = "won" THEN 1 ELSE 0 END) as won,
                    SUM(CASE WHEN status = "lost" THEN 1 ELSE 0 END) as lost,
                    SUM(CASE WHEN status NOT IN ("won", "lost") THEN 1 ELSE 0 END) as active
                ')
                ->first();

            $total = (int) $stats->total;
            $won = (int) $stats->won;
            $lost = (int) $stats->lost;
            $active = (int) $stats->active;

            return [
                'total' => $total,
                'new' => (int) $stats->new_count,
                'contacted' => (int) $stats->contacted,
                'qualified' => (int) $stats->qualified,
                'proposal' => (int) $stats->proposal,
                'negotiation' => (int) $stats->negotiation,
                'won' => $won,
                'lost' => $lost,
                'active' => $active,
                'conversion_rate' => $total > 0 ? round(($won / $total) * 100, 1) : 0,
                'win_rate' => ($won + $lost) > 0 ? round(($won / ($won + $lost)) * 100, 1) : 0,
            ];
        });
    }

    /**
     * Get conversion rate for a business
     */
    public function getConversionRate(string $businessId, ?Carbon $startDate = null): float
    {
        $query = Lead::where('business_id', $businessId);

        if ($startDate) {
            $query->where('updated_at', '>=', $startDate);
        }

        $totalClosed = (clone $query)->whereIn('status', ['won', 'lost'])->count();

        if ($totalClosed === 0) {
            return 0;
        }

        $won = (clone $query)->where('status', 'won')->count();

        return round(($won / $totalClosed) * 100, 1);
    }

    /**
     * Get revenue statistics
     */
    public function getRevenueStats(string $businessId, ?Carbon $startDate = null, ?Carbon $endDate = null): array
    {
        $endDate = $endDate ?? Carbon::now();
        $startDate = $startDate ?? Carbon::now()->startOfMonth();

        $cacheKey = "revenue_stats_{$businessId}_{$startDate->format('Y-m-d')}_{$endDate->format('Y-m-d')}";

        return Cache::remember($cacheKey, $this->cacheTTL, function () use ($businessId, $startDate, $endDate) {
            // Single query with aggregation
            $stats = DB::table('leads')
                ->where('business_id', $businessId)
                ->where('status', 'won')
                ->selectRaw('
                    COALESCE(SUM(estimated_value), 0) as total_revenue,
                    COUNT(*) as total_deals,
                    AVG(estimated_value) as avg_deal_size,
                    SUM(CASE WHEN DATE(converted_at) = CURDATE() THEN estimated_value ELSE 0 END) as today_revenue,
                    SUM(CASE WHEN converted_at >= ? AND converted_at <= ? THEN estimated_value ELSE 0 END) as period_revenue,
                    COUNT(CASE WHEN DATE(converted_at) = CURDATE() THEN 1 END) as today_deals,
                    COUNT(CASE WHEN converted_at >= ? AND converted_at <= ? THEN 1 END) as period_deals
                ', [$startDate, $endDate, $startDate, $endDate])
                ->first();

            return [
                'total_revenue' => (float) $stats->total_revenue,
                'total_deals' => (int) $stats->total_deals,
                'avg_deal_size' => round((float) $stats->avg_deal_size, 2),
                'today' => [
                    'revenue' => (float) $stats->today_revenue,
                    'deals' => (int) $stats->today_deals,
                ],
                'period' => [
                    'revenue' => (float) $stats->period_revenue,
                    'deals' => (int) $stats->period_deals,
                    'start_date' => $startDate->format('Y-m-d'),
                    'end_date' => $endDate->format('Y-m-d'),
                ],
            ];
        });
    }

    /**
     * Get pipeline value (active leads total)
     */
    public function getPipelineValue(string $businessId): float
    {
        return Cache::remember("pipeline_value_{$businessId}", $this->cacheTTL, function () use ($businessId) {
            return (float) Lead::where('business_id', $businessId)
                ->whereNotIn('status', ['won', 'lost'])
                ->sum('estimated_value');
        });
    }

    /**
     * Get leads by status for funnel visualization
     */
    public function getFunnelData(string $businessId): array
    {
        $cacheKey = "funnel_data_{$businessId}";

        return Cache::remember($cacheKey, $this->cacheTTL, function () use ($businessId) {
            $stages = [
                'new' => 'Yangi',
                'contacted' => 'Bog\'lanildi',
                'qualified' => 'Kvalifikatsiya',
                'proposal' => 'Taklif',
                'negotiation' => 'Muzokaralar',
                'won' => 'Yutildi',
                'lost' => 'Yo\'qotildi',
            ];

            $stats = $this->getLeadStats($businessId);
            $total = $stats['total'];
            $funnelData = [];

            foreach ($stages as $status => $label) {
                $count = $stats[$status] ?? 0;
                $funnelData[] = [
                    'stage' => $status,
                    'label' => $label,
                    'count' => $count,
                    'percentage' => $total > 0 ? round(($count / $total) * 100, 1) : 0,
                ];
            }

            return $funnelData;
        });
    }

    /**
     * Get today's statistics
     */
    public function getTodayStats(string $businessId): array
    {
        $today = Carbon::today();

        return Cache::remember("today_stats_{$businessId}_{$today->format('Y-m-d')}", $this->cacheTTL, function () use ($businessId, $today) {
            $stats = DB::table('leads')
                ->where('business_id', $businessId)
                ->whereDate('created_at', $today)
                ->selectRaw('
                    COUNT(*) as new_leads,
                    SUM(CASE WHEN status = "won" THEN 1 ELSE 0 END) as won_today,
                    SUM(CASE WHEN status = "won" THEN estimated_value ELSE 0 END) as revenue_today
                ')
                ->first();

            return [
                'new_leads' => (int) $stats->new_leads,
                'won_today' => (int) $stats->won_today,
                'revenue_today' => (float) $stats->revenue_today,
            ];
        });
    }

    /**
     * Clear cache for a business
     */
    public function clearCache(string $businessId): void
    {
        $patterns = [
            "lead_stats_{$businessId}*",
            "revenue_stats_{$businessId}*",
            "pipeline_value_{$businessId}",
            "funnel_data_{$businessId}",
            "today_stats_{$businessId}*",
        ];

        foreach ($patterns as $pattern) {
            Cache::forget($pattern);
        }
    }
}
