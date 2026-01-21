<?php

namespace App\Services\BusinessSystematization;

use App\Models\MarketingKpi;
use App\Models\MarketingCampaign;
use App\Models\MarketingChannel;
use App\Models\MarketingBudget;
use App\Models\SalesTarget;
use App\Models\LeadFlowTracking;
use Carbon\Carbon;
use Illuminate\Support\Collection;

/**
 * Marketing-Sales Integration Service
 * Implements book methodology: Marketing bonus depends on Sales results (70/30 rule)
 */
class MarketingSalesIntegrationService
{
    /**
     * Get marketing dashboard data with sales linkage
     */
    public function getDashboardData(string $businessId, ?Carbon $date = null): array
    {
        $date = $date ?? now();
        $monthStart = $date->copy()->startOfMonth();
        $monthEnd = $date->copy()->endOfMonth();

        return [
            'sales_linkage' => $this->getSalesLinkageData($businessId, $monthStart, $monthEnd),
            'channel_performance' => $this->getChannelPerformance($businessId, $monthStart, $monthEnd),
            'budget_status' => $this->getBudgetStatus($businessId, $date->year, $date->month),
            'lead_quality' => $this->getLeadQualityMetrics($businessId, $monthStart, $monthEnd),
            'campaign_roi' => $this->getCampaignRoiAnalysis($businessId, $monthStart, $monthEnd),
        ];
    }

    /**
     * Get sales linkage data (how marketing bonus depends on sales)
     */
    public function getSalesLinkageData(string $businessId, Carbon $start, Carbon $end): array
    {
        // Get department sales target
        $salesTarget = SalesTarget::where('business_id', $businessId)
            ->where('target_type', 'department')
            ->where('period_start', '<=', $start)
            ->where('period_end', '>=', $end)
            ->first();

        $salesCompletion = $salesTarget?->revenue_completion_percent ?? 0;

        // Get marketing KPI records
        $marketingKpis = MarketingKpi::where('business_id', $businessId)
            ->where('period_start', '>=', $start)
            ->where('period_end', '<=', $end)
            ->get();

        return [
            'sales_plan_completion' => $salesCompletion,
            'marketing_bonus_impact' => $this->calculateMarketingBonusFromSales($salesCompletion),
            'total_marketing_kpis' => $marketingKpis->count(),
            'avg_tasks_completion' => $marketingKpis->avg('tasks_completion_percent') ?? 0,
            'explanation' => $this->getMarketingSalesBonusExplanation($salesCompletion),
        ];
    }

    /**
     * Calculate marketing bonus portion from sales (70% rule from book)
     */
    protected function calculateMarketingBonusFromSales(float $salesCompletion): array
    {
        // From book: Marketing gets 70% from sales + 30% from own tasks
        $salesWeight = 70;
        $salesBonusPercent = min(100, $salesCompletion) * ($salesWeight / 100);

        return [
            'weight' => $salesWeight,
            'contribution_percent' => round($salesBonusPercent, 2),
            'max_possible' => $salesWeight,
            'status' => $salesCompletion >= 100 ? 'full' : ($salesCompletion >= 80 ? 'partial' : 'low'),
        ];
    }

    /**
     * Get explanation for marketing-sales bonus linkage
     */
    protected function getMarketingSalesBonusExplanation(float $salesCompletion): string
    {
        if ($salesCompletion >= 100) {
            return "Sotuv rejasi 100% bajarildi. Marketing jamoasi to'liq bonus olish huquqiga ega.";
        }

        if ($salesCompletion >= 80) {
            return "Sotuv rejasi {$salesCompletion}% bajarildi. Marketing bonusining 70% qismi sotuv natijasiga bog'liq.";
        }

        return "Sotuv rejasi {$salesCompletion}% bajarildi. Sotuv natijasi past bo'lgani uchun Marketing bonusi kamayadi.";
    }

    /**
     * Get channel performance with ROI
     */
    public function getChannelPerformance(string $businessId, Carbon $start, Carbon $end): array
    {
        $channels = MarketingChannel::where('business_id', $businessId)
            ->active()
            ->get();

        $performance = $channels->map(function ($channel) use ($start, $end) {
            $campaigns = $channel->campaigns()
                ->where('start_date', '<=', $end)
                ->where(function($q) use ($start) {
                    $q->whereNull('end_date')
                      ->orWhere('end_date', '>=', $start);
                })
                ->get();

            $totalSpent = $campaigns->sum('budget_spent');
            $totalLeads = $campaigns->sum('leads_generated');
            $totalDeals = $campaigns->sum('deals_closed');
            $totalRevenue = $campaigns->sum('revenue_generated');

            $cpl = $totalLeads > 0 ? round($totalSpent / $totalLeads, 2) : 0;
            $cpa = $totalDeals > 0 ? round($totalSpent / $totalDeals, 2) : 0;
            $roi = $totalSpent > 0 ? round((($totalRevenue - $totalSpent) / $totalSpent) * 100, 2) : 0;

            return [
                'channel_id' => $channel->id,
                'channel_name' => $channel->name,
                'channel_type' => $channel->type,
                'spent' => $totalSpent,
                'leads' => $totalLeads,
                'deals' => $totalDeals,
                'revenue' => $totalRevenue,
                'cpl' => $cpl,
                'cpa' => $cpa,
                'roi' => $roi,
                'roi_color' => $this->getRoiColor($roi),
                'recommendation' => $this->getChannelRecommendation($roi, $cpl),
            ];
        })->sortByDesc('roi')->values();

        return [
            'channels' => $performance,
            'best_performer' => $performance->first(),
            'worst_performer' => $performance->last(),
            'total_spent' => $performance->sum('spent'),
            'total_revenue' => $performance->sum('revenue'),
            'overall_roi' => $performance->sum('spent') > 0
                ? round((($performance->sum('revenue') - $performance->sum('spent')) / $performance->sum('spent')) * 100, 2)
                : 0,
        ];
    }

    /**
     * Get ROI color for visualization
     */
    protected function getRoiColor(float $roi): string
    {
        if ($roi >= 200) return 'green';
        if ($roi >= 100) return 'blue';
        if ($roi >= 50) return 'yellow';
        if ($roi >= 0) return 'orange';
        return 'red';
    }

    /**
     * Get channel recommendation based on performance
     */
    protected function getChannelRecommendation(float $roi, float $cpl): string
    {
        if ($roi >= 200) {
            return "Ajoyib! Bu kanalga investitsiyani oshiring.";
        }

        if ($roi >= 100) {
            return "Yaxshi natija. Davom eting va optimizatsiya qiling.";
        }

        if ($roi >= 0) {
            return "O'rtacha. Strategiyani qayta ko'rib chiqing.";
        }

        return "Salbiy ROI. Bu kanalni to'xtatish yoki tubdan o'zgartirish kerak.";
    }

    /**
     * Get budget status for the month
     */
    public function getBudgetStatus(string $businessId, int $year, int $month): array
    {
        $budgets = MarketingBudget::where('business_id', $businessId)
            ->where('year', $year)
            ->where('month', $month)
            ->with('channel')
            ->get();

        $totalLimit = $budgets->sum('budget_limit');
        $totalSpent = $budgets->sum('spent_amount');
        $overBudgetCount = $budgets->where('is_over_budget', true)->count();

        $byChannel = $budgets->map(function ($budget) {
            return [
                'channel_id' => $budget->channel_id,
                'channel_name' => $budget->channel?->name ?? 'Umumiy',
                'limit' => $budget->budget_limit,
                'spent' => $budget->spent_amount,
                'remaining' => $budget->remaining,
                'usage_percent' => $budget->usage_percent,
                'is_over_budget' => $budget->is_over_budget,
                'status_color' => $budget->status_color,
            ];
        });

        return [
            'total_limit' => $totalLimit,
            'total_spent' => $totalSpent,
            'total_remaining' => $totalLimit - $totalSpent,
            'usage_percent' => $totalLimit > 0 ? round(($totalSpent / $totalLimit) * 100, 2) : 0,
            'over_budget_count' => $overBudgetCount,
            'is_within_budget' => $totalSpent <= $totalLimit,
            'by_channel' => $byChannel,
        ];
    }

    /**
     * Get lead quality metrics (feedback from sales)
     */
    public function getLeadQualityMetrics(string $businessId, Carbon $start, Carbon $end): array
    {
        $tracking = LeadFlowTracking::where('business_id', $businessId)
            ->whereBetween('tracking_date', [$start, $end])
            ->get();

        $totalGenerated = $tracking->sum('leads_generated');
        $totalAccepted = $tracking->sum('leads_accepted');
        $totalRejected = $tracking->sum('leads_rejected');
        $totalConverted = $tracking->sum('leads_converted');
        $avgQualityScore = $tracking->avg('lead_quality_score') ?? 0;

        // Rejection reasons summary
        $rejectionReasons = $tracking->pluck('rejection_reasons_summary')
            ->filter()
            ->flatten(1)
            ->groupBy('reason')
            ->map(fn($items) => $items->count())
            ->sortDesc();

        return [
            'total_generated' => $totalGenerated,
            'total_accepted' => $totalAccepted,
            'total_rejected' => $totalRejected,
            'total_converted' => $totalConverted,
            'acceptance_rate' => $totalGenerated > 0 ? round(($totalAccepted / $totalGenerated) * 100, 2) : 0,
            'conversion_rate' => $totalAccepted > 0 ? round(($totalConverted / $totalAccepted) * 100, 2) : 0,
            'avg_quality_score' => round($avgQualityScore, 2),
            'quality_label' => $this->getQualityLabel($avgQualityScore),
            'rejection_reasons' => $rejectionReasons,
            'improvement_areas' => $this->getLeadQualityImprovementAreas($avgQualityScore, $rejectionReasons),
        ];
    }

    /**
     * Get quality label from score
     */
    protected function getQualityLabel(float $score): string
    {
        if ($score >= 4.5) return "A'lo";
        if ($score >= 3.5) return 'Yaxshi';
        if ($score >= 2.5) return "O'rta";
        if ($score >= 1.5) return 'Past';
        return 'Juda past';
    }

    /**
     * Get improvement areas based on lead quality
     */
    protected function getLeadQualityImprovementAreas(float $score, Collection $rejectionReasons): array
    {
        $areas = [];

        if ($score < 3.5) {
            $areas[] = "Lid sifatini oshirish kerak. Target auditoriyani aniqroq belgilang.";
        }

        $topReason = $rejectionReasons->keys()->first();
        if ($topReason) {
            $areas[] = "Eng ko'p rad etish sababi: {$topReason}. Bu muammoni hal qiling.";
        }

        return $areas;
    }

    /**
     * Get campaign ROI analysis
     */
    public function getCampaignRoiAnalysis(string $businessId, Carbon $start, Carbon $end): array
    {
        $campaigns = MarketingCampaign::where('business_id', $businessId)
            ->where('start_date', '<=', $end)
            ->where(function($q) use ($start) {
                $q->whereNull('end_date')
                  ->orWhere('end_date', '>=', $start);
            })
            ->get();

        $profitable = $campaigns->where('roi', '>', 0);
        $unprofitable = $campaigns->where('roi', '<=', 0);

        return [
            'total_campaigns' => $campaigns->count(),
            'profitable_count' => $profitable->count(),
            'unprofitable_count' => $unprofitable->count(),
            'profitability_rate' => $campaigns->count() > 0
                ? round(($profitable->count() / $campaigns->count()) * 100, 2)
                : 0,
            'best_campaign' => $campaigns->sortByDesc('roi')->first()?->only(['id', 'name', 'roi', 'revenue_generated']),
            'worst_campaign' => $campaigns->sortBy('roi')->first()?->only(['id', 'name', 'roi', 'revenue_generated']),
            'avg_roi' => round($campaigns->avg('roi') ?? 0, 2),
            'total_profit' => $campaigns->sum('revenue_generated') - $campaigns->sum('budget_spent'),
        ];
    }

    /**
     * Calculate marketing team bonus based on book's 70/30 rule
     */
    public function calculateMarketingBonus(
        string $businessId,
        Carbon $periodStart,
        Carbon $periodEnd,
        float $baseBonusFund
    ): array {
        // Get sales completion
        $salesTarget = SalesTarget::where('business_id', $businessId)
            ->where('target_type', 'department')
            ->where('period_start', '<=', $periodStart)
            ->where('period_end', '>=', $periodEnd)
            ->first();

        $salesCompletion = min(100, $salesTarget?->revenue_completion_percent ?? 0);

        // Get marketing tasks completion (average)
        $marketingKpis = MarketingKpi::where('business_id', $businessId)
            ->where('period_start', '>=', $periodStart)
            ->where('period_end', '<=', $periodEnd)
            ->get();

        $tasksCompletion = min(100, $marketingKpis->avg('tasks_completion_percent') ?? 0);

        // Calculate bonus: 70% from sales, 30% from tasks
        $salesBonus = $baseBonusFund * 0.70 * ($salesCompletion / 100);
        $tasksBonus = $baseBonusFund * 0.30 * ($tasksCompletion / 100);
        $totalBonus = $salesBonus + $tasksBonus;

        return [
            'base_fund' => $baseBonusFund,
            'sales_completion' => $salesCompletion,
            'tasks_completion' => $tasksCompletion,
            'sales_bonus' => round($salesBonus, 2),
            'tasks_bonus' => round($tasksBonus, 2),
            'total_bonus' => round($totalBonus, 2),
            'bonus_percent' => $baseBonusFund > 0 ? round(($totalBonus / $baseBonusFund) * 100, 2) : 0,
            'breakdown' => [
                ['name' => 'Sotuvdan (70%)', 'amount' => $salesBonus, 'completion' => $salesCompletion],
                ['name' => 'Vazifalardan (30%)', 'amount' => $tasksBonus, 'completion' => $tasksCompletion],
            ],
        ];
    }
}
