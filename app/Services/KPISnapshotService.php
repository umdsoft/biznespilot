<?php

namespace App\Services;

use App\Models\Business;
use App\Models\KpiDailySnapshot;
use App\Models\ChannelMetric;
use App\Models\Lead;
use App\Models\Order;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class KPISnapshotService
{
    public function captureSnapshot(Business $business, ?Carbon $date = null): KpiDailySnapshot
    {
        $date = $date ?? Carbon::today();

        // Check if snapshot already exists
        $existing = KpiDailySnapshot::where('business_id', $business->id)
            ->where('snapshot_date', $date->format('Y-m-d'))
            ->first();

        if ($existing) {
            return $this->updateSnapshot($existing, $business, $date);
        }

        $kpis = $this->calculateDailyKPIs($business, $date);

        return KpiDailySnapshot::create(array_merge([
            'business_id' => $business->id,
            'snapshot_date' => $date,
        ], $kpis));
    }

    protected function updateSnapshot(KpiDailySnapshot $snapshot, Business $business, Carbon $date): KpiDailySnapshot
    {
        $kpis = $this->calculateDailyKPIs($business, $date);
        $snapshot->update($kpis);
        return $snapshot->fresh();
    }

    public function calculateDailyKPIs(Business $business, Carbon $date): array
    {
        $startOfDay = $date->copy()->startOfDay();
        $endOfDay = $date->copy()->endOfDay();

        // Revenue metrics
        $revenue = $this->calculateRevenueMetrics($business, $startOfDay, $endOfDay);

        // Lead metrics
        $leads = $this->calculateLeadMetrics($business, $startOfDay, $endOfDay);

        // Marketing metrics
        $marketing = $this->calculateMarketingMetrics($business, $startOfDay, $endOfDay);

        // Advertising metrics
        $advertising = $this->calculateAdvertisingMetrics($business, $startOfDay, $endOfDay);

        // Sales metrics
        $sales = $this->calculateSalesMetrics($business, $startOfDay, $endOfDay);

        // Content metrics
        $content = $this->calculateContentMetrics($business, $startOfDay, $endOfDay);

        // Funnel metrics
        $funnel = $this->calculateFunnelMetrics($business, $startOfDay, $endOfDay);

        // Calculate health score
        $healthScore = $this->calculateHealthScore(array_merge(
            $revenue, $leads, $marketing, $advertising, $sales
        ));

        return array_merge(
            $revenue,
            $leads,
            $marketing,
            $advertising,
            $sales,
            $content,
            $funnel,
            ['health_score' => $healthScore]
        );
    }

    protected function calculateRevenueMetrics(Business $business, Carbon $start, Carbon $end): array
    {
        // Get orders for the day
        $orders = $this->getOrdersQuery($business, $start, $end);

        $total = $orders->sum('total_amount') ?? 0;
        $recurring = $orders->where('is_recurring', true)->sum('total_amount') ?? 0;
        $oneTime = $total - $recurring;
        $projected = $this->calculateProjectedRevenue($business, $total);
        $target = $business->monthly_revenue_target ?? 0;
        $targetProgress = $target > 0 ? ($total / ($target / 30)) * 100 : 0;

        return [
            'revenue_total' => $total,
            'revenue_recurring' => $recurring,
            'revenue_one_time' => $oneTime,
            'revenue_projected' => $projected,
            'revenue_target' => $target,
            'revenue_target_progress' => min(100, $targetProgress),
        ];
    }

    protected function calculateLeadMetrics(Business $business, Carbon $start, Carbon $end): array
    {
        $leadsQuery = Lead::where('business_id', $business->id)
            ->whereBetween('created_at', [$start, $end]);

        $total = $leadsQuery->count();
        $qualified = $leadsQuery->clone()->where('status', 'qualified')->count();
        $converted = $leadsQuery->clone()->where('status', 'converted')->count();

        // By channel
        $byChannel = $leadsQuery->clone()
            ->select('source', DB::raw('count(*) as count'))
            ->groupBy('source')
            ->pluck('count', 'source')
            ->toArray();

        // Lead quality score (0-100)
        $qualityScore = $total > 0 ? ($qualified / $total) * 100 : 0;

        return [
            'leads_total' => $total,
            'leads_qualified' => $qualified,
            'leads_converted' => $converted,
            'leads_instagram' => $byChannel['instagram'] ?? 0,
            'leads_telegram' => $byChannel['telegram'] ?? 0,
            'leads_facebook' => $byChannel['facebook'] ?? 0,
            'leads_website' => $byChannel['website'] ?? 0,
            'leads_other' => $total - (($byChannel['instagram'] ?? 0) + ($byChannel['telegram'] ?? 0) + ($byChannel['facebook'] ?? 0) + ($byChannel['website'] ?? 0)),
            'lead_quality_score' => round($qualityScore, 1),
        ];
    }

    protected function calculateMarketingMetrics(Business $business, Carbon $start, Carbon $end): array
    {
        // Get channel metrics
        $metrics = ChannelMetric::where('business_id', $business->id)
            ->whereBetween('metric_date', [$start->format('Y-m-d'), $end->format('Y-m-d')])
            ->get();

        $spend = $metrics->sum('ad_spend') ?? 0;
        $leadsTotal = Lead::where('business_id', $business->id)
            ->whereBetween('created_at', [$start, $end])
            ->count();

        $cac = $leadsTotal > 0 ? $spend / $leadsTotal : 0;

        // Calculate LTV (simplified - based on average order value * estimated lifetime orders)
        $avgOrderValue = $this->getAverageOrderValue($business);
        $ltv = $avgOrderValue * 3; // Assuming 3 lifetime orders on average

        $ltvCacRatio = $cac > 0 ? $ltv / $cac : 0;

        $emailSent = 0; // TODO: Integrate with email service
        $emailOpened = 0;
        $emailClicked = 0;

        return [
            'marketing_spend' => $spend,
            'cac' => round($cac, 2),
            'ltv' => round($ltv, 2),
            'ltv_cac_ratio' => round($ltvCacRatio, 2),
            'email_sent' => $emailSent,
            'email_open_rate' => $emailSent > 0 ? ($emailOpened / $emailSent) * 100 : 0,
            'email_click_rate' => $emailOpened > 0 ? ($emailClicked / $emailOpened) * 100 : 0,
        ];
    }

    protected function calculateAdvertisingMetrics(Business $business, Carbon $start, Carbon $end): array
    {
        $metrics = ChannelMetric::where('business_id', $business->id)
            ->whereBetween('metric_date', [$start->format('Y-m-d'), $end->format('Y-m-d')])
            ->get();

        $spend = $metrics->sum('ad_spend') ?? 0;
        $impressions = $metrics->sum('impressions') ?? 0;
        $clicks = $metrics->sum('clicks') ?? 0;
        $conversions = $metrics->sum('conversions') ?? 0;

        $revenue = $this->getOrdersQuery($business, $start, $end)->sum('total_amount') ?? 0;

        $ctr = $impressions > 0 ? ($clicks / $impressions) * 100 : 0;
        $cpc = $clicks > 0 ? $spend / $clicks : 0;
        $cpm = $impressions > 0 ? ($spend / $impressions) * 1000 : 0;
        $roas = $spend > 0 ? $revenue / $spend : 0;

        return [
            'ad_spend' => $spend,
            'ad_impressions' => $impressions,
            'ad_clicks' => $clicks,
            'ad_ctr' => round($ctr, 2),
            'ad_cpc' => round($cpc, 2),
            'ad_cpm' => round($cpm, 2),
            'ad_conversions' => $conversions,
            'ad_roas' => round($roas, 2),
        ];
    }

    protected function calculateSalesMetrics(Business $business, Carbon $start, Carbon $end): array
    {
        $orders = $this->getOrdersQuery($business, $start, $end);
        $leads = Lead::where('business_id', $business->id)
            ->whereBetween('created_at', [$start, $end]);

        $ordersCount = $orders->count();
        $leadsCount = $leads->count();

        $conversionRate = $leadsCount > 0 ? ($ordersCount / $leadsCount) * 100 : 0;
        $avgDealSize = $ordersCount > 0 ? $orders->sum('total_amount') / $ordersCount : 0;

        // Pipeline value (leads in progress * average conversion * avg deal size)
        $pipelineLeads = Lead::where('business_id', $business->id)
            ->whereIn('status', ['new', 'contacted', 'qualified'])
            ->count();
        $pipelineValue = $pipelineLeads * ($conversionRate / 100) * $avgDealSize;

        // Win rate (simplified)
        $totalAttempts = $leads->count();
        $wins = Lead::where('business_id', $business->id)
            ->whereBetween('created_at', [$start, $end])
            ->where('status', 'converted')
            ->count();
        $winRate = $totalAttempts > 0 ? ($wins / $totalAttempts) * 100 : 0;

        return [
            'conversion_rate' => round($conversionRate, 2),
            'avg_deal_size' => round($avgDealSize, 2),
            'sales_cycle_days' => $this->calculateAverageSalesCycle($business),
            'pipeline_value' => round($pipelineValue, 2),
            'win_rate' => round($winRate, 2),
            'churn_rate' => $this->calculateChurnRate($business, $start, $end),
        ];
    }

    protected function calculateContentMetrics(Business $business, Carbon $start, Carbon $end): array
    {
        $metrics = ChannelMetric::where('business_id', $business->id)
            ->whereBetween('metric_date', [$start->format('Y-m-d'), $end->format('Y-m-d')])
            ->get();

        $reach = $metrics->sum('reach') ?? 0;
        $engagement = $metrics->sum('engagement') ?? 0;
        $followers = $metrics->sum('followers_count') ?? 0;
        $postsCount = $metrics->sum('posts_count') ?? 0;

        $engagementRate = $reach > 0 ? ($engagement / $reach) * 100 : 0;
        $viralityRate = 0; // TODO: Calculate based on shares/reach

        return [
            'content_reach' => $reach,
            'content_engagement' => $engagement,
            'engagement_rate' => round($engagementRate, 2),
            'followers_total' => $followers,
            'followers_growth' => $this->calculateFollowersGrowth($business, $start),
            'posts_published' => $postsCount,
            'virality_rate' => round($viralityRate, 2),
        ];
    }

    protected function calculateFunnelMetrics(Business $business, Carbon $start, Carbon $end): array
    {
        // Simplified funnel calculation
        $metrics = ChannelMetric::where('business_id', $business->id)
            ->whereBetween('metric_date', [$start->format('Y-m-d'), $end->format('Y-m-d')])
            ->get();

        $awareness = $metrics->sum('reach') ?? 0;
        $interest = $metrics->sum('engagement') ?? 0;

        $leads = Lead::where('business_id', $business->id)
            ->whereBetween('created_at', [$start, $end]);

        $consideration = $leads->clone()->whereIn('status', ['new', 'contacted'])->count();
        $intent = $leads->clone()->where('status', 'qualified')->count();
        $purchase = $leads->clone()->where('status', 'converted')->count();

        return [
            'funnel_awareness' => $awareness,
            'funnel_interest' => $interest,
            'funnel_consideration' => $consideration,
            'funnel_intent' => $intent,
            'funnel_purchase' => $purchase,
        ];
    }

    protected function calculateHealthScore(array $metrics): int
    {
        $score = 50; // Base score

        // Revenue performance
        if (($metrics['revenue_target_progress'] ?? 0) >= 100) {
            $score += 15;
        } elseif (($metrics['revenue_target_progress'] ?? 0) >= 80) {
            $score += 10;
        } elseif (($metrics['revenue_target_progress'] ?? 0) >= 50) {
            $score += 5;
        }

        // Lead quality
        if (($metrics['lead_quality_score'] ?? 0) >= 50) {
            $score += 10;
        } elseif (($metrics['lead_quality_score'] ?? 0) >= 30) {
            $score += 5;
        }

        // CAC efficiency
        $ltvCacRatio = $metrics['ltv_cac_ratio'] ?? 0;
        if ($ltvCacRatio >= 3) {
            $score += 10;
        } elseif ($ltvCacRatio >= 2) {
            $score += 5;
        } elseif ($ltvCacRatio < 1) {
            $score -= 10;
        }

        // ROAS
        $roas = $metrics['ad_roas'] ?? 0;
        if ($roas >= 4) {
            $score += 10;
        } elseif ($roas >= 2) {
            $score += 5;
        } elseif ($roas < 1 && $roas > 0) {
            $score -= 10;
        }

        // Conversion rate
        $conversionRate = $metrics['conversion_rate'] ?? 0;
        if ($conversionRate >= 5) {
            $score += 5;
        } elseif ($conversionRate >= 3) {
            $score += 3;
        }

        return max(0, min(100, $score));
    }

    public function compareWithPrevious(Business $business, Carbon $date): array
    {
        $current = $this->captureSnapshot($business, $date);
        $previous = KpiDailySnapshot::where('business_id', $business->id)
            ->where('snapshot_date', $date->copy()->subDay()->format('Y-m-d'))
            ->first();

        if (!$previous) {
            return [
                'current' => $current->toKpiArray(),
                'previous' => null,
                'changes' => null,
            ];
        }

        $changes = [];
        $metrics = ['revenue_total', 'leads_total', 'cac', 'ad_roas', 'conversion_rate', 'health_score'];

        foreach ($metrics as $metric) {
            $currentVal = $current->{$metric} ?? 0;
            $previousVal = $previous->{$metric} ?? 0;

            $changes[$metric] = [
                'current' => $currentVal,
                'previous' => $previousVal,
                'change' => $previousVal > 0 ? (($currentVal - $previousVal) / $previousVal) * 100 : null,
            ];
        }

        return [
            'current' => $current->toKpiArray(),
            'previous' => $previous->toKpiArray(),
            'changes' => $changes,
        ];
    }

    protected function getOrdersQuery(Business $business, Carbon $start, Carbon $end)
    {
        // Check if Order model exists and has the required structure
        if (!class_exists(Order::class)) {
            return collect();
        }

        return Order::where('business_id', $business->id)
            ->whereBetween('created_at', [$start, $end])
            ->where('status', 'completed');
    }

    protected function getAverageOrderValue(Business $business): float
    {
        if (!class_exists(Order::class)) {
            return 0;
        }

        return Order::where('business_id', $business->id)
            ->where('status', 'completed')
            ->avg('total_amount') ?? 0;
    }

    protected function calculateProjectedRevenue(Business $business, float $todayRevenue): float
    {
        // Simple projection based on today's revenue * remaining days
        $remainingDays = Carbon::today()->daysInMonth - Carbon::today()->day + 1;
        return $todayRevenue * $remainingDays;
    }

    protected function calculateAverageSalesCycle(Business $business): float
    {
        // Calculate average days from lead creation to conversion
        $convertedLeads = Lead::where('business_id', $business->id)
            ->where('status', 'converted')
            ->whereNotNull('converted_at')
            ->limit(100)
            ->get();

        if ($convertedLeads->isEmpty()) {
            return 0;
        }

        $totalDays = $convertedLeads->sum(function ($lead) {
            return $lead->created_at->diffInDays($lead->converted_at);
        });

        return round($totalDays / $convertedLeads->count(), 1);
    }

    protected function calculateChurnRate(Business $business, Carbon $start, Carbon $end): float
    {
        // Simplified churn calculation
        // In a real scenario, this would track customer cancellations
        return 0;
    }

    protected function calculateFollowersGrowth(Business $business, Carbon $start): float
    {
        $current = ChannelMetric::where('business_id', $business->id)
            ->where('metric_date', $start->format('Y-m-d'))
            ->sum('followers_count') ?? 0;

        $previous = ChannelMetric::where('business_id', $business->id)
            ->where('metric_date', $start->copy()->subDay()->format('Y-m-d'))
            ->sum('followers_count') ?? 0;

        if ($previous == 0) {
            return 0;
        }

        return round((($current - $previous) / $previous) * 100, 2);
    }
}
