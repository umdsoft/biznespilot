<?php

namespace App\Services\Marketing;

use App\Models\Lead;
use App\Models\Sale;
use App\Models\MarketingSpend;
use App\Models\MarketingKpiSnapshot;
use App\Models\MarketingChannel;
use App\Models\Campaign;
use App\Traits\HasPeriodCalculation;
use App\Traits\HasKpiCalculation;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * MarketingKpiCalculatorService - Marketing KPI hisoblash va snapshot yaratish
 * CPL, ROAS, ROI, CAC va boshqa metrikalar
 *
 * DRY: HasPeriodCalculation va HasKpiCalculation traitlardan foydalanadi
 */
class MarketingKpiCalculatorService
{
    use HasPeriodCalculation;
    use HasKpiCalculation;
    /**
     * Calculate and save daily KPI snapshot.
     */
    public function calculateDailySnapshot(
        string $businessId,
        Carbon $date,
        ?string $channelId = null,
        ?string $campaignId = null
    ): MarketingKpiSnapshot {
        $from = $date->copy()->startOfDay();
        $to = $date->copy()->endOfDay();

        return $this->calculateSnapshot($businessId, $date, 'daily', $channelId, $campaignId, $from, $to);
    }

    /**
     * Calculate and save weekly KPI snapshot.
     */
    public function calculateWeeklySnapshot(
        string $businessId,
        Carbon $weekStart,
        ?string $channelId = null,
        ?string $campaignId = null
    ): MarketingKpiSnapshot {
        $from = $weekStart->copy()->startOfWeek();
        $to = $weekStart->copy()->endOfWeek();

        return $this->calculateSnapshot($businessId, $weekStart, 'weekly', $channelId, $campaignId, $from, $to);
    }

    /**
     * Calculate and save monthly KPI snapshot.
     */
    public function calculateMonthlySnapshot(
        string $businessId,
        Carbon $monthStart,
        ?string $channelId = null,
        ?string $campaignId = null
    ): MarketingKpiSnapshot {
        $from = $monthStart->copy()->startOfMonth();
        $to = $monthStart->copy()->endOfMonth();

        return $this->calculateSnapshot($businessId, $monthStart, 'monthly', $channelId, $campaignId, $from, $to);
    }

    /**
     * Core snapshot calculation.
     */
    private function calculateSnapshot(
        string $businessId,
        Carbon $date,
        string $periodType,
        ?string $channelId,
        ?string $campaignId,
        Carbon $from,
        Carbon $to
    ): MarketingKpiSnapshot {
        // Lead metrics
        $leadsCount = $this->getLeadsCount($businessId, $channelId, $campaignId, $from, $to);
        $mqlCount = $this->getMqlCount($businessId, $channelId, $campaignId, $from, $to);
        $sqlCount = $this->getSqlCount($businessId, $channelId, $campaignId, $from, $to);
        $wonCount = $this->getWonCount($businessId, $channelId, $campaignId, $from, $to);
        $lostCount = $this->getLostCount($businessId, $channelId, $campaignId, $from, $to);

        // Financial metrics
        $totalSpend = $this->getTotalSpend($businessId, $channelId, $campaignId, $from, $to);
        $totalRevenue = $this->getTotalRevenue($businessId, $channelId, $campaignId, $from, $to);

        // Calculate KPIs
        $cpl = $leadsCount > 0 ? $totalSpend / $leadsCount : 0;
        $cpmql = $mqlCount > 0 ? $totalSpend / $mqlCount : 0;
        $cpsql = $sqlCount > 0 ? $totalSpend / $sqlCount : 0;
        $cac = $wonCount > 0 ? $totalSpend / $wonCount : 0;
        $roas = $totalSpend > 0 ? $totalRevenue / $totalSpend : 0;
        $roi = $totalSpend > 0 ? (($totalRevenue - $totalSpend) / $totalSpend) * 100 : 0;

        // Conversion rates
        $leadToMqlRate = $leadsCount > 0 ? ($mqlCount / $leadsCount) * 100 : 0;
        $mqlToSqlRate = $mqlCount > 0 ? ($sqlCount / $mqlCount) * 100 : 0;
        $sqlToWonRate = $sqlCount > 0 ? ($wonCount / $sqlCount) * 100 : 0;
        $overallConversionRate = $leadsCount > 0 ? ($wonCount / $leadsCount) * 100 : 0;

        // Upsert snapshot
        return MarketingKpiSnapshot::updateOrCreate(
            [
                'business_id' => $businessId,
                'date' => $date->toDateString(),
                'period_type' => $periodType,
                'channel_id' => $channelId,
                'campaign_id' => $campaignId,
            ],
            [
                'leads_count' => $leadsCount,
                'mql_count' => $mqlCount,
                'sql_count' => $sqlCount,
                'won_count' => $wonCount,
                'lost_count' => $lostCount,
                'total_spend' => round($totalSpend, 2),
                'total_revenue' => round($totalRevenue, 2),
                'cpl' => round($cpl, 2),
                'cpmql' => round($cpmql, 2),
                'cpsql' => round($cpsql, 2),
                'cac' => round($cac, 2),
                'roas' => round($roas, 4),
                'roi' => round($roi, 4),
                'lead_to_mql_rate' => round($leadToMqlRate, 2),
                'mql_to_sql_rate' => round($mqlToSqlRate, 2),
                'sql_to_won_rate' => round($sqlToWonRate, 2),
                'overall_conversion_rate' => round($overallConversionRate, 2),
            ]
        );
    }

    // ==========================================
    // DATA RETRIEVAL METHODS
    // ==========================================

    /**
     * Get total leads count.
     */
    public function getLeadsCount(
        string $businessId,
        ?string $channelId,
        ?string $campaignId,
        Carbon $from,
        Carbon $to
    ): int {
        return Lead::where('business_id', $businessId)
            ->when($channelId, fn($q) => $q->where('marketing_channel_id', $channelId))
            ->when($campaignId, fn($q) => $q->where('campaign_id', $campaignId))
            ->whereBetween('created_at', [$from, $to])
            ->count();
    }

    /**
     * Get MQL count.
     */
    public function getMqlCount(
        string $businessId,
        ?string $channelId,
        ?string $campaignId,
        Carbon $from,
        Carbon $to
    ): int {
        return Lead::where('business_id', $businessId)
            ->when($channelId, fn($q) => $q->where('marketing_channel_id', $channelId))
            ->when($campaignId, fn($q) => $q->where('campaign_id', $campaignId))
            ->whereIn('qualification_status', ['mql', 'sql'])
            ->whereBetween('qualified_at', [$from, $to])
            ->count();
    }

    /**
     * Get SQL count.
     */
    public function getSqlCount(
        string $businessId,
        ?string $channelId,
        ?string $campaignId,
        Carbon $from,
        Carbon $to
    ): int {
        return Lead::where('business_id', $businessId)
            ->when($channelId, fn($q) => $q->where('marketing_channel_id', $channelId))
            ->when($campaignId, fn($q) => $q->where('campaign_id', $campaignId))
            ->where('qualification_status', 'sql')
            ->whereBetween('qualified_at', [$from, $to])
            ->count();
    }

    /**
     * Get won leads count.
     */
    public function getWonCount(
        string $businessId,
        ?string $channelId,
        ?string $campaignId,
        Carbon $from,
        Carbon $to
    ): int {
        return Lead::where('business_id', $businessId)
            ->when($channelId, fn($q) => $q->where('marketing_channel_id', $channelId))
            ->when($campaignId, fn($q) => $q->where('campaign_id', $campaignId))
            ->where('status', 'won')
            ->whereBetween('converted_at', [$from, $to])
            ->count();
    }

    /**
     * Get lost leads count.
     */
    public function getLostCount(
        string $businessId,
        ?string $channelId,
        ?string $campaignId,
        Carbon $from,
        Carbon $to
    ): int {
        return Lead::where('business_id', $businessId)
            ->when($channelId, fn($q) => $q->where('marketing_channel_id', $channelId))
            ->when($campaignId, fn($q) => $q->where('campaign_id', $campaignId))
            ->where('status', 'lost')
            ->whereBetween('stage_changed_at', [$from, $to])
            ->count();
    }

    /**
     * Get total marketing spend.
     */
    public function getTotalSpend(
        string $businessId,
        ?string $channelId,
        ?string $campaignId,
        Carbon $from,
        Carbon $to
    ): float {
        return (float) MarketingSpend::where('business_id', $businessId)
            ->when($channelId, fn($q) => $q->where('channel_id', $channelId))
            ->when($campaignId, fn($q) => $q->where('campaign_id', $campaignId))
            ->whereBetween('date', [$from->toDateString(), $to->toDateString()])
            ->sum('amount');
    }

    /**
     * Get total revenue from sales.
     */
    public function getTotalRevenue(
        string $businessId,
        ?string $channelId,
        ?string $campaignId,
        Carbon $from,
        Carbon $to
    ): float {
        return (float) Sale::where('business_id', $businessId)
            ->when($channelId, fn($q) => $q->where('marketing_channel_id', $channelId))
            ->when($campaignId, fn($q) => $q->where('campaign_id', $campaignId))
            ->whereBetween('closed_at', [$from, $to])
            ->sum('amount');
    }

    // ==========================================
    // DIRECT KPI CALCULATION METHODS
    // ==========================================

    /**
     * CPL - Cost Per Lead.
     */
    public function calculateCpl(
        string $businessId,
        Carbon $from,
        Carbon $to,
        ?string $channelId = null,
        ?string $campaignId = null
    ): float {
        $spend = $this->getTotalSpend($businessId, $channelId, $campaignId, $from, $to);
        $leads = $this->getLeadsCount($businessId, $channelId, $campaignId, $from, $to);

        return $leads > 0 ? round($spend / $leads, 2) : 0;
    }

    /**
     * ROAS - Return On Ad Spend.
     */
    public function calculateRoas(
        string $businessId,
        Carbon $from,
        Carbon $to,
        ?string $channelId = null,
        ?string $campaignId = null
    ): float {
        $spend = $this->getTotalSpend($businessId, $channelId, $campaignId, $from, $to);
        $revenue = $this->getTotalRevenue($businessId, $channelId, $campaignId, $from, $to);

        return $spend > 0 ? round($revenue / $spend, 4) : 0;
    }

    /**
     * ROI - Return On Investment (%).
     */
    public function calculateRoi(
        string $businessId,
        Carbon $from,
        Carbon $to,
        ?string $channelId = null,
        ?string $campaignId = null
    ): float {
        $spend = $this->getTotalSpend($businessId, $channelId, $campaignId, $from, $to);
        $revenue = $this->getTotalRevenue($businessId, $channelId, $campaignId, $from, $to);

        return $spend > 0 ? round((($revenue - $spend) / $spend) * 100, 2) : 0;
    }

    /**
     * CAC - Customer Acquisition Cost.
     */
    public function calculateCac(
        string $businessId,
        Carbon $from,
        Carbon $to,
        ?string $channelId = null,
        ?string $campaignId = null
    ): float {
        $spend = $this->getTotalSpend($businessId, $channelId, $campaignId, $from, $to);
        $customers = $this->getWonCount($businessId, $channelId, $campaignId, $from, $to);

        return $customers > 0 ? round($spend / $customers, 2) : 0;
    }

    // ==========================================
    // DASHBOARD METHODS
    // ==========================================

    /**
     * Get comprehensive marketing dashboard data.
     */
    public function getDashboardData(string $businessId, Carbon $from, Carbon $to): array
    {
        return [
            'overview' => [
                'total_leads' => $this->getLeadsCount($businessId, null, null, $from, $to),
                'total_mql' => $this->getMqlCount($businessId, null, null, $from, $to),
                'total_sql' => $this->getSqlCount($businessId, null, null, $from, $to),
                'total_won' => $this->getWonCount($businessId, null, null, $from, $to),
                'total_lost' => $this->getLostCount($businessId, null, null, $from, $to),
                'total_spend' => $this->getTotalSpend($businessId, null, null, $from, $to),
                'total_revenue' => $this->getTotalRevenue($businessId, null, null, $from, $to),
            ],
            'kpis' => [
                'cpl' => $this->calculateCpl($businessId, $from, $to),
                'roas' => $this->calculateRoas($businessId, $from, $to),
                'roi' => $this->calculateRoi($businessId, $from, $to),
                'cac' => $this->calculateCac($businessId, $from, $to),
            ],
            'by_channel' => $this->getKpisByChannel($businessId, $from, $to),
            'by_campaign' => $this->getKpisByCampaign($businessId, $from, $to),
        ];
    }

    /**
     * Get KPIs grouped by channel.
     */
    public function getKpisByChannel(string $businessId, Carbon $from, Carbon $to): array
    {
        $channels = MarketingChannel::where('business_id', $businessId)
            ->where('is_active', true)
            ->get();

        $result = [];
        foreach ($channels as $channel) {
            $result[] = [
                'channel_id' => $channel->id,
                'channel_name' => $channel->name,
                'channel_type' => $channel->type,
                'leads' => $this->getLeadsCount($businessId, $channel->id, null, $from, $to),
                'won' => $this->getWonCount($businessId, $channel->id, null, $from, $to),
                'spend' => $this->getTotalSpend($businessId, $channel->id, null, $from, $to),
                'revenue' => $this->getTotalRevenue($businessId, $channel->id, null, $from, $to),
                'cpl' => $this->calculateCpl($businessId, $from, $to, $channel->id),
                'roas' => $this->calculateRoas($businessId, $from, $to, $channel->id),
            ];
        }

        return $result;
    }

    /**
     * Get KPIs grouped by campaign.
     */
    public function getKpisByCampaign(string $businessId, Carbon $from, Carbon $to): array
    {
        $campaigns = Campaign::where('business_id', $businessId)
            ->whereIn('status', ['active', 'completed'])
            ->get();

        $result = [];
        foreach ($campaigns as $campaign) {
            $result[] = [
                'campaign_id' => $campaign->id,
                'campaign_name' => $campaign->name,
                'campaign_status' => $campaign->status,
                'leads' => $this->getLeadsCount($businessId, null, $campaign->id, $from, $to),
                'won' => $this->getWonCount($businessId, null, $campaign->id, $from, $to),
                'spend' => $this->getTotalSpend($businessId, null, $campaign->id, $from, $to),
                'revenue' => $this->getTotalRevenue($businessId, null, $campaign->id, $from, $to),
                'cpl' => $this->calculateCpl($businessId, $from, $to, null, $campaign->id),
                'roas' => $this->calculateRoas($businessId, $from, $to, null, $campaign->id),
                'roi' => $this->calculateRoi($businessId, $from, $to, null, $campaign->id),
            ];
        }

        return $result;
    }
}
