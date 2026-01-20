<?php

namespace App\Services;

use App\Models\Business;
use App\Models\Lead;
use App\Models\MarketingAlert;
use App\Models\MarketingBonus;
use App\Models\MarketingChannel;
use App\Models\MarketingExpense;
use App\Models\MarketingLeaderboard;
use App\Models\MarketingTarget;
use App\Models\MarketingUserKpi;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class MarketingDashboardService
{
    public function __construct(
        private MarketingAlertService $alertService,
        private MarketingLeaderboardService $leaderboardService,
        private MarketingBonusCalculatorService $bonusService
    ) {}

    public function getDashboardData(Business $business, ?User $user = null): array
    {
        $cacheKey = "marketing_dashboard_{$business->id}_" . ($user?->id ?? 'all');

        return Cache::remember($cacheKey, 300, function () use ($business, $user) {
            return [
                'overview' => $this->getOverview($business),
                'kpi_summary' => $this->getKpiSummary($business, $user),
                'targets' => $this->getTargetProgress($business, $user),
                'channels' => $this->getChannelPerformance($business),
                'alerts' => $this->alertService->getAlertsSummary($business),
                'leaderboard' => $this->leaderboardService->getLeaderboard($business, 'weekly', null, 5),
                'bonus_preview' => $user ? $this->getBonusPreview($business, $user) : null,
                'trends' => $this->getTrends($business),
            ];
        });
    }

    public function getOverview(Business $business): array
    {
        $currentMonth = now()->startOfMonth();
        $previousMonth = now()->subMonth()->startOfMonth();

        // Current month metrics
        $currentMetrics = $this->getMonthMetrics($business, $currentMonth);
        $previousMetrics = $this->getMonthMetrics($business, $previousMonth);

        return [
            'total_leads' => $currentMetrics['leads'],
            'leads_change' => $this->calculateChange($currentMetrics['leads'], $previousMetrics['leads']),
            'qualified_leads' => $currentMetrics['qualified'],
            'qualified_change' => $this->calculateChange($currentMetrics['qualified'], $previousMetrics['qualified']),
            'converted_leads' => $currentMetrics['converted'],
            'converted_change' => $this->calculateChange($currentMetrics['converted'], $previousMetrics['converted']),
            'total_spend' => $currentMetrics['spend'],
            'spend_change' => $this->calculateChange($currentMetrics['spend'], $previousMetrics['spend']),
            'total_revenue' => $currentMetrics['revenue'],
            'revenue_change' => $this->calculateChange($currentMetrics['revenue'], $previousMetrics['revenue']),
            'cpl' => $currentMetrics['leads'] > 0 ? $currentMetrics['spend'] / $currentMetrics['leads'] : 0,
            'roas' => $currentMetrics['spend'] > 0 ? $currentMetrics['revenue'] / $currentMetrics['spend'] : 0,
            'roi' => $currentMetrics['spend'] > 0
                ? (($currentMetrics['revenue'] - $currentMetrics['spend']) / $currentMetrics['spend']) * 100
                : 0,
            'conversion_rate' => $currentMetrics['leads'] > 0
                ? ($currentMetrics['converted'] / $currentMetrics['leads']) * 100
                : 0,
        ];
    }

    public function getKpiSummary(Business $business, ?User $user = null): array
    {
        $periodStart = now()->startOfMonth();

        $query = MarketingUserKpi::where('business_id', $business->id)
            ->where('period_start', $periodStart)
            ->where('period_type', 'monthly');

        if ($user) {
            $query->where('user_id', $user->id);
        } else {
            $query->whereNull('user_id'); // Business-wide KPI
        }

        $kpi = $query->first();

        if (!$kpi) {
            return [
                'leads_count' => 0,
                'qualified_leads' => 0,
                'converted_leads' => 0,
                'cpl_actual' => 0,
                'roas_actual' => 0,
                'roi' => 0,
                'conversion_rate' => 0,
                'target_completion' => 0,
            ];
        }

        return [
            'leads_count' => $kpi->leads_count,
            'qualified_leads' => $kpi->qualified_leads,
            'converted_leads' => $kpi->converted_leads,
            'cpl_actual' => $kpi->cpl_actual,
            'roas_actual' => $kpi->roas_actual,
            'roi' => $kpi->getRoiAttribute(),
            'conversion_rate' => $kpi->getConversionRateAttribute(),
            'target_completion' => $kpi->target_completion,
            'total_spend' => $kpi->total_spend,
            'total_revenue' => $kpi->total_revenue,
        ];
    }

    public function getTargetProgress(Business $business, ?User $user = null): array
    {
        $periodStart = now()->startOfMonth();

        $targets = MarketingTarget::where('business_id', $business->id)
            ->where('period_start', $periodStart)
            ->active()
            ->where(function ($q) use ($user) {
                if ($user) {
                    $q->where('user_id', $user->id)
                        ->orWhereNull('user_id');
                } else {
                    $q->whereNull('user_id');
                }
            })
            ->get();

        $result = [];

        foreach ($targets as $target) {
            $actualValue = $this->getActualValueForTarget($business, $target, $user);
            $progress = $target->target_value > 0
                ? min(100, ($actualValue / $target->target_value) * 100)
                : 0;

            $result[] = [
                'id' => $target->id,
                'type' => $target->target_type,
                'name' => $target->getTypeLabel(),
                'target_value' => $target->target_value,
                'actual_value' => $actualValue,
                'progress' => round($progress, 1),
                'status' => $progress >= 100 ? 'completed' : ($progress >= 80 ? 'on_track' : 'behind'),
                'min_value' => $target->min_value,
                'max_value' => $target->max_value,
            ];
        }

        return $result;
    }

    public function getChannelPerformance(Business $business): Collection
    {
        $channels = MarketingChannel::where('business_id', $business->id)
            ->where('is_active', true)
            ->get();

        return $channels->map(function ($channel) {
            $monthStart = now()->startOfMonth();
            $monthEnd = now()->endOfMonth();

            $leads = $channel->leads()
                ->whereBetween('created_at', [$monthStart, $monthEnd])
                ->count();

            $spend = $channel->expenses()
                ->whereBetween('date', [$monthStart, $monthEnd])
                ->sum('amount');

            $revenue = $channel->leads()
                ->whereBetween('created_at', [$monthStart, $monthEnd])
                ->where('status', 'won')
                ->sum('deal_value');

            $converted = $channel->leads()
                ->whereBetween('created_at', [$monthStart, $monthEnd])
                ->where('status', 'won')
                ->count();

            return [
                'id' => $channel->id,
                'name' => $channel->name,
                'type' => $channel->type,
                'leads' => $leads,
                'spend' => $spend,
                'revenue' => $revenue,
                'converted' => $converted,
                'cpl' => $leads > 0 ? $spend / $leads : 0,
                'roas' => $spend > 0 ? $revenue / $spend : 0,
                'roi' => $spend > 0 ? (($revenue - $spend) / $spend) * 100 : 0,
                'conversion_rate' => $leads > 0 ? ($converted / $leads) * 100 : 0,
                'budget' => $channel->budget,
                'budget_used' => $channel->budget > 0 ? ($spend / $channel->budget) * 100 : 0,
                'target_cpl' => $channel->target_cpl,
                'target_roas' => $channel->target_roas,
            ];
        });
    }

    public function getBonusPreview(Business $business, User $user): array
    {
        $currentMonth = now();
        $periodStart = $currentMonth->copy()->startOfMonth();

        // Get current bonus calculation (preview)
        $existingBonus = MarketingBonus::where('business_id', $business->id)
            ->where('user_id', $user->id)
            ->where('period_start', $periodStart)
            ->first();

        if ($existingBonus) {
            return [
                'estimated_amount' => $existingBonus->final_amount,
                'base_amount' => $existingBonus->base_amount,
                'lead_bonus' => $existingBonus->lead_bonus,
                'cpl_bonus' => $existingBonus->cpl_bonus,
                'roas_bonus' => $existingBonus->roas_bonus,
                'accelerator_bonus' => $existingBonus->accelerator_bonus,
                'penalty_deduction' => $existingBonus->penalty_deduction,
                'status' => $existingBonus->status,
            ];
        }

        // Calculate preview
        return [
            'estimated_amount' => 0,
            'base_amount' => 0,
            'lead_bonus' => 0,
            'cpl_bonus' => 0,
            'roas_bonus' => 0,
            'accelerator_bonus' => 0,
            'penalty_deduction' => 0,
            'status' => 'preview',
            'message' => 'Bonus oy oxirida hisoblanadi',
        ];
    }

    public function getTrends(Business $business, int $days = 30): array
    {
        $startDate = now()->subDays($days);

        // Daily leads trend
        $leadsTrend = Lead::where('business_id', $business->id)
            ->where('created_at', '>=', $startDate)
            ->selectRaw('DATE(created_at) as date, COUNT(*) as count')
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->pluck('count', 'date')
            ->toArray();

        // Daily spend trend
        $spendTrend = MarketingExpense::where('business_id', $business->id)
            ->where('date', '>=', $startDate)
            ->selectRaw('date, SUM(amount) as total')
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->pluck('total', 'date')
            ->toArray();

        // Fill missing dates
        $dates = [];
        for ($i = $days; $i >= 0; $i--) {
            $date = now()->subDays($i)->format('Y-m-d');
            $dates[] = [
                'date' => $date,
                'leads' => $leadsTrend[$date] ?? 0,
                'spend' => $spendTrend[$date] ?? 0,
            ];
        }

        return [
            'daily' => $dates,
            'total_leads' => array_sum(array_column($dates, 'leads')),
            'total_spend' => array_sum(array_column($dates, 'spend')),
            'avg_leads_per_day' => round(array_sum(array_column($dates, 'leads')) / ($days + 1), 1),
            'avg_spend_per_day' => round(array_sum(array_column($dates, 'spend')) / ($days + 1), 0),
        ];
    }

    public function getRealTimeMetrics(Business $business): array
    {
        // Today's metrics
        $today = now()->startOfDay();

        $todayLeads = Lead::where('business_id', $business->id)
            ->where('created_at', '>=', $today)
            ->count();

        $todaySpend = MarketingExpense::where('business_id', $business->id)
            ->where('date', $today->format('Y-m-d'))
            ->sum('amount');

        $todayRevenue = Lead::where('business_id', $business->id)
            ->where('created_at', '>=', $today)
            ->where('status', 'won')
            ->sum('deal_value');

        // Last hour metrics
        $lastHour = now()->subHour();

        $lastHourLeads = Lead::where('business_id', $business->id)
            ->where('created_at', '>=', $lastHour)
            ->count();

        // Active alerts
        $activeAlerts = MarketingAlert::where('business_id', $business->id)
            ->active()
            ->count();

        $criticalAlerts = MarketingAlert::where('business_id', $business->id)
            ->active()
            ->critical()
            ->count();

        return [
            'today' => [
                'leads' => $todayLeads,
                'spend' => $todaySpend,
                'revenue' => $todayRevenue,
                'cpl' => $todayLeads > 0 ? $todaySpend / $todayLeads : 0,
            ],
            'last_hour' => [
                'leads' => $lastHourLeads,
            ],
            'alerts' => [
                'active' => $activeAlerts,
                'critical' => $criticalAlerts,
            ],
            'timestamp' => now()->toIso8601String(),
        ];
    }

    public function getComparisonData(Business $business, string $compareWith = 'previous_month'): array
    {
        $currentStart = now()->startOfMonth();
        $currentEnd = now();

        if ($compareWith === 'previous_month') {
            $previousStart = now()->subMonth()->startOfMonth();
            $previousEnd = now()->subMonth()->endOfMonth();
        } else {
            // Same period last year
            $previousStart = now()->subYear()->startOfMonth();
            $previousEnd = now()->subYear()->endOfMonth();
        }

        $currentMetrics = $this->getPeriodMetrics($business, $currentStart, $currentEnd);
        $previousMetrics = $this->getPeriodMetrics($business, $previousStart, $previousEnd);

        return [
            'current' => $currentMetrics,
            'previous' => $previousMetrics,
            'changes' => [
                'leads' => $this->calculateChange($currentMetrics['leads'], $previousMetrics['leads']),
                'spend' => $this->calculateChange($currentMetrics['spend'], $previousMetrics['spend']),
                'revenue' => $this->calculateChange($currentMetrics['revenue'], $previousMetrics['revenue']),
                'cpl' => $this->calculateChange($currentMetrics['cpl'], $previousMetrics['cpl']),
                'roas' => $this->calculateChange($currentMetrics['roas'], $previousMetrics['roas']),
                'roi' => $this->calculateChange($currentMetrics['roi'], $previousMetrics['roi']),
            ],
        ];
    }

    public function getTeamPerformance(Business $business): Collection
    {
        $periodStart = now()->startOfMonth();

        return MarketingUserKpi::where('business_id', $business->id)
            ->where('period_start', $periodStart)
            ->whereNotNull('user_id')
            ->with('user')
            ->get()
            ->map(function ($kpi) {
                return [
                    'user_id' => $kpi->user_id,
                    'user_name' => $kpi->user->name ?? 'Unknown',
                    'leads_count' => $kpi->leads_count,
                    'qualified_leads' => $kpi->qualified_leads,
                    'converted_leads' => $kpi->converted_leads,
                    'conversion_rate' => $kpi->getConversionRateAttribute(),
                    'total_spend' => $kpi->total_spend,
                    'total_revenue' => $kpi->total_revenue,
                    'roi' => $kpi->getRoiAttribute(),
                    'target_completion' => $kpi->target_completion,
                ];
            });
    }

    public function clearDashboardCache(Business $business): void
    {
        Cache::forget("marketing_dashboard_{$business->id}_all");

        // Clear user-specific caches
        $users = $business->users()->pluck('id');
        foreach ($users as $userId) {
            Cache::forget("marketing_dashboard_{$business->id}_{$userId}");
        }
    }

    // Helper methods

    private function getMonthMetrics(Business $business, Carbon $monthStart): array
    {
        $monthEnd = $monthStart->copy()->endOfMonth();

        $leads = Lead::where('business_id', $business->id)
            ->whereBetween('created_at', [$monthStart, $monthEnd])
            ->count();

        $qualified = Lead::where('business_id', $business->id)
            ->whereBetween('created_at', [$monthStart, $monthEnd])
            ->where('is_qualified', true)
            ->count();

        $converted = Lead::where('business_id', $business->id)
            ->whereBetween('created_at', [$monthStart, $monthEnd])
            ->where('status', 'won')
            ->count();

        $spend = MarketingExpense::where('business_id', $business->id)
            ->whereBetween('date', [$monthStart, $monthEnd])
            ->sum('amount');

        $revenue = Lead::where('business_id', $business->id)
            ->whereBetween('created_at', [$monthStart, $monthEnd])
            ->where('status', 'won')
            ->sum('deal_value');

        return compact('leads', 'qualified', 'converted', 'spend', 'revenue');
    }

    private function getPeriodMetrics(Business $business, Carbon $start, Carbon $end): array
    {
        $metrics = $this->getMonthMetrics($business, $start);

        return array_merge($metrics, [
            'cpl' => $metrics['leads'] > 0 ? $metrics['spend'] / $metrics['leads'] : 0,
            'roas' => $metrics['spend'] > 0 ? $metrics['revenue'] / $metrics['spend'] : 0,
            'roi' => $metrics['spend'] > 0
                ? (($metrics['revenue'] - $metrics['spend']) / $metrics['spend']) * 100
                : 0,
        ]);
    }

    private function getActualValueForTarget(Business $business, MarketingTarget $target, ?User $user): float
    {
        $periodStart = $target->period_start;
        $periodEnd = $target->period_end ?? now();

        $query = match ($target->target_type) {
            'leads' => Lead::where('business_id', $business->id)
                ->whereBetween('created_at', [$periodStart, $periodEnd]),
            'qualified_leads' => Lead::where('business_id', $business->id)
                ->whereBetween('created_at', [$periodStart, $periodEnd])
                ->where('is_qualified', true),
            'converted_leads' => Lead::where('business_id', $business->id)
                ->whereBetween('created_at', [$periodStart, $periodEnd])
                ->where('status', 'won'),
            'revenue' => Lead::where('business_id', $business->id)
                ->whereBetween('created_at', [$periodStart, $periodEnd])
                ->where('status', 'won'),
            'spend' => MarketingExpense::where('business_id', $business->id)
                ->whereBetween('date', [$periodStart, $periodEnd]),
            default => null,
        };

        if (!$query) {
            return 0;
        }

        // Filter by user if specified
        if ($user && $target->user_id) {
            if (in_array($target->target_type, ['leads', 'qualified_leads', 'converted_leads', 'revenue'])) {
                $query->where('assigned_to', $user->id);
            }
        }

        return match ($target->target_type) {
            'leads', 'qualified_leads', 'converted_leads' => $query->count(),
            'revenue' => $query->sum('deal_value'),
            'spend' => $query->sum('amount'),
            default => 0,
        };
    }

    private function calculateChange(float $current, float $previous): float
    {
        if ($previous == 0) {
            return $current > 0 ? 100 : 0;
        }

        return round((($current - $previous) / $previous) * 100, 1);
    }
}
