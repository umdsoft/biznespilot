<?php

namespace App\Services\BusinessSystematization;

use App\Models\SalesTarget;
use App\Models\SalesActivity;
use App\Models\Receivable;
use App\Models\LostDeal;
use App\Models\RejectionReason;
use App\Models\SalesFunnelStage;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Sales Analytics Service
 * Provides comprehensive sales analytics for ROP (Sales Manager) dashboard
 */
class SalesAnalyticsService
{
    /**
     * Get sales dashboard data for a business
     */
    public function getDashboardData(string $businessId, ?Carbon $date = null): array
    {
        $date = $date ?? now();
        $monthStart = $date->copy()->startOfMonth();
        $monthEnd = $date->copy()->endOfMonth();

        return [
            'current_period' => $this->getCurrentPeriodStats($businessId, $monthStart, $monthEnd),
            'manager_rankings' => $this->getManagerRankings($businessId, $monthStart, $monthEnd),
            'receivables' => $this->getReceivablesStats($businessId),
            'funnel' => $this->getFunnelStats($businessId, $monthStart, $monthEnd),
            'rejection_analysis' => $this->getRejectionAnalysis($businessId, $monthStart, $monthEnd),
            'trend' => $this->getTrendData($businessId, 6),
        ];
    }

    /**
     * Get current period statistics
     */
    public function getCurrentPeriodStats(string $businessId, Carbon $start, Carbon $end): array
    {
        $target = SalesTarget::where('business_id', $businessId)
            ->where('target_type', 'department')
            ->where('period_start', '<=', $start)
            ->where('period_end', '>=', $end)
            ->first();

        if (!$target) {
            return [
                'has_target' => false,
                'message' => 'Ushbu davr uchun reja mavjud emas',
            ];
        }

        $daysInPeriod = $start->diffInDays($end) + 1;
        $daysElapsed = $start->diffInDays(now()) + 1;
        $daysRemaining = max(0, now()->diffInDays($end));

        // Expected progress based on days elapsed
        $expectedProgress = min(100, ($daysElapsed / $daysInPeriod) * 100);

        // KPI Score: (Fact - Base) / (Plan - Base)
        $kpiScore = $target->calculateKpiScore();

        return [
            'has_target' => true,
            'plan_revenue' => $target->plan_revenue,
            'fact_revenue' => $target->fact_revenue,
            'base_revenue' => $target->base_revenue,
            'remaining_revenue' => $target->remaining_revenue,
            'completion_percent' => $target->revenue_completion_percent,
            'kpi_score' => $kpiScore,
            'kpi_interpretation' => $this->interpretKpiScore($kpiScore),
            'days_remaining' => $daysRemaining,
            'expected_progress' => round($expectedProgress, 1),
            'is_on_track' => $target->revenue_completion_percent >= $expectedProgress,
            'daily_target' => $daysRemaining > 0
                ? round($target->remaining_revenue / $daysRemaining, 2)
                : 0,
            'deals' => [
                'plan' => $target->plan_deals,
                'fact' => $target->fact_deals,
                'percent' => $target->deals_completion_percent,
            ],
            'new_clients' => [
                'plan' => $target->plan_new_clients,
                'fact' => $target->fact_new_clients,
            ],
        ];
    }

    /**
     * Interpret KPI score based on book's methodology
     */
    protected function interpretKpiScore(float $kpiScore): array
    {
        if ($kpiScore < 0) {
            return [
                'status' => 'critical',
                'color' => 'red',
                'label' => 'Kritik holat',
                'message' => 'Natija baza nuqtasidan ham past',
            ];
        }

        if ($kpiScore < 0.5) {
            return [
                'status' => 'poor',
                'color' => 'orange',
                'label' => 'Yomon',
                'message' => 'Rejaning yarmidan kam bajarildi',
            ];
        }

        if ($kpiScore < 1) {
            return [
                'status' => 'below_target',
                'color' => 'yellow',
                'label' => 'Reja ostida',
                'message' => 'Reja to\'liq bajarilmadi',
            ];
        }

        if ($kpiScore == 1) {
            return [
                'status' => 'on_target',
                'color' => 'green',
                'label' => 'Reja bajarildi',
                'message' => 'A\'lo natija!',
            ];
        }

        return [
            'status' => 'above_target',
            'color' => 'blue',
            'label' => 'Reja oshirildi',
            'message' => 'Zo\'r! Rejadan oshib ketildi!',
        ];
    }

    /**
     * Get manager rankings for the period
     */
    public function getManagerRankings(string $businessId, Carbon $start, Carbon $end): array
    {
        $targets = SalesTarget::where('business_id', $businessId)
            ->where('target_type', 'individual')
            ->where('period_start', '<=', $start)
            ->where('period_end', '>=', $end)
            ->with('user')
            ->get();

        $rankings = $targets->map(function ($target) {
            return [
                'user_id' => $target->user_id,
                'user_name' => $target->user?->name ?? 'Noma\'lum',
                'plan' => $target->plan_revenue,
                'fact' => $target->fact_revenue,
                'completion_percent' => $target->revenue_completion_percent,
                'kpi_score' => $target->calculateKpiScore(),
                'deals_closed' => $target->fact_deals,
                'status_color' => $target->status_color,
            ];
        })->sortByDesc('completion_percent')->values();

        return [
            'rankings' => $rankings,
            'total_managers' => $rankings->count(),
            'on_target_count' => $rankings->where('completion_percent', '>=', 100)->count(),
            'below_target_count' => $rankings->where('completion_percent', '<', 80)->count(),
        ];
    }

    /**
     * Get receivables statistics
     */
    public function getReceivablesStats(string $businessId): array
    {
        $receivables = Receivable::where('business_id', $businessId)
            ->whereNotIn('status', ['paid', 'written_off'])
            ->get();

        $totalAmount = $receivables->sum('remaining_amount');
        $overdueAmount = $receivables->where('status', 'overdue')->sum('remaining_amount');

        // Group by responsible user
        $byUser = $receivables->groupBy('responsible_user_id')->map(function ($items, $userId) {
            return [
                'user_id' => $userId,
                'total_amount' => $items->sum('remaining_amount'),
                'overdue_amount' => $items->where('status', 'overdue')->sum('remaining_amount'),
                'count' => $items->count(),
                'overdue_count' => $items->where('status', 'overdue')->count(),
            ];
        })->values();

        // Aging analysis
        $aging = [
            'current' => $receivables->where('overdue_days', 0)->sum('remaining_amount'),
            '1_30_days' => $receivables->whereBetween('overdue_days', [1, 30])->sum('remaining_amount'),
            '31_60_days' => $receivables->whereBetween('overdue_days', [31, 60])->sum('remaining_amount'),
            '61_90_days' => $receivables->whereBetween('overdue_days', [61, 90])->sum('remaining_amount'),
            'over_90_days' => $receivables->where('overdue_days', '>', 90)->sum('remaining_amount'),
        ];

        return [
            'total_amount' => $totalAmount,
            'overdue_amount' => $overdueAmount,
            'overdue_percent' => $totalAmount > 0 ? round(($overdueAmount / $totalAmount) * 100, 2) : 0,
            'count' => $receivables->count(),
            'overdue_count' => $receivables->where('status', 'overdue')->count(),
            'by_user' => $byUser,
            'aging' => $aging,
        ];
    }

    /**
     * Get sales funnel statistics
     */
    public function getFunnelStats(string $businessId, Carbon $start, Carbon $end): array
    {
        // This would integrate with your CRM/Deal system
        // For now, returning structure

        $stages = SalesFunnelStage::where('business_id', $businessId)
            ->active()
            ->ordered()
            ->get();

        // Calculate conversions between stages
        $funnelData = [];
        $previousCount = null;

        foreach ($stages as $stage) {
            // Get count from your deals/leads system
            $count = 0; // Replace with actual count

            $conversionRate = $previousCount && $previousCount > 0
                ? round(($count / $previousCount) * 100, 2)
                : 100;

            $funnelData[] = [
                'stage_id' => $stage->id,
                'name' => $stage->name,
                'count' => $count,
                'conversion_rate' => $conversionRate,
                'color' => $stage->color,
            ];

            $previousCount = $count;
        }

        return [
            'stages' => $funnelData,
            'total_leads' => $funnelData[0]['count'] ?? 0,
            'total_deals' => end($funnelData)['count'] ?? 0,
            'overall_conversion' => ($funnelData[0]['count'] ?? 0) > 0
                ? round((end($funnelData)['count'] ?? 0) / $funnelData[0]['count'] * 100, 2)
                : 0,
        ];
    }

    /**
     * Get rejection/lost deal analysis
     */
    public function getRejectionAnalysis(string $businessId, Carbon $start, Carbon $end): array
    {
        $lostDeals = LostDeal::where('business_id', $businessId)
            ->whereBetween('lost_date', [$start, $end])
            ->with(['rejectionReason', 'lostToCompetitor'])
            ->get();

        // By reason
        $byReason = $lostDeals->groupBy('rejection_reason_id')->map(function ($items, $reasonId) {
            $reason = $items->first()->rejectionReason;
            return [
                'reason_id' => $reasonId,
                'reason_name' => $reason?->name ?? 'Noma\'lum',
                'category' => $reason?->category ?? 'other',
                'count' => $items->count(),
                'total_value' => $items->sum('potential_value'),
            ];
        })->sortByDesc('count')->values();

        // By competitor
        $byCompetitor = $lostDeals->whereNotNull('lost_to_competitor_id')
            ->groupBy('lost_to_competitor_id')
            ->map(function ($items, $competitorId) {
                $competitor = $items->first()->lostToCompetitor;
                return [
                    'competitor_id' => $competitorId,
                    'competitor_name' => $competitor?->name ?? 'Noma\'lum',
                    'count' => $items->count(),
                    'total_value' => $items->sum('potential_value'),
                ];
            })->sortByDesc('count')->values();

        return [
            'total_lost' => $lostDeals->count(),
            'total_lost_value' => $lostDeals->sum('potential_value'),
            'by_reason' => $byReason,
            'by_competitor' => $byCompetitor,
            'top_reason' => $byReason->first(),
            'top_competitor' => $byCompetitor->first(),
        ];
    }

    /**
     * Get trend data for the last N months
     */
    public function getTrendData(string $businessId, int $months = 6): array
    {
        $data = [];

        for ($i = $months - 1; $i >= 0; $i--) {
            $date = now()->subMonths($i);
            $start = $date->copy()->startOfMonth();
            $end = $date->copy()->endOfMonth();

            $target = SalesTarget::where('business_id', $businessId)
                ->where('target_type', 'department')
                ->where('period_start', '<=', $start)
                ->where('period_end', '>=', $end)
                ->first();

            $data[] = [
                'month' => $date->format('Y-m'),
                'month_name' => $date->translatedFormat('F'),
                'plan' => $target?->plan_revenue ?? 0,
                'fact' => $target?->fact_revenue ?? 0,
                'completion_percent' => $target?->revenue_completion_percent ?? 0,
            ];
        }

        return $data;
    }

    /**
     * Get daily activity summary for a manager
     */
    public function getManagerActivitySummary(
        string $businessId,
        string $userId,
        Carbon $start,
        Carbon $end
    ): array {
        $activities = SalesActivity::where('business_id', $businessId)
            ->where('user_id', $userId)
            ->whereBetween('activity_date', [$start, $end])
            ->get();

        return [
            'total_calls' => $activities->sum('calls_made'),
            'answered_calls' => $activities->sum('calls_answered'),
            'call_answer_rate' => $activities->sum('calls_made') > 0
                ? round(($activities->sum('calls_answered') / $activities->sum('calls_made')) * 100, 2)
                : 0,
            'total_meetings' => $activities->sum('meetings_held'),
            'proposals_sent' => $activities->sum('proposals_sent'),
            'deals_closed' => $activities->sum('deals_closed'),
            'revenue_generated' => $activities->sum('revenue_generated'),
            'talk_time_hours' => round($activities->sum('talk_time_minutes') / 60, 2),
            'activity_days' => $activities->count(),
            'daily_average' => [
                'calls' => round($activities->avg('calls_made'), 1),
                'meetings' => round($activities->avg('meetings_held'), 1),
            ],
        ];
    }
}
