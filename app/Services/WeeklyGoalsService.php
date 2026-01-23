<?php

namespace App\Services;

use App\Models\Business;
use App\Models\Lead;
use App\Models\OperatorWeeklyKpi;
use App\Models\User;
use App\Models\WeeklyAnalytics;
use App\Models\WeeklyGoal;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class WeeklyGoalsService
{
    /**
     * Get or create weekly goal for a business
     */
    public function getOrCreateGoal(Business $business, ?Carbon $weekStart = null): WeeklyGoal
    {
        $weekStart = $weekStart ?? now()->startOfWeek();
        $weekEnd = $weekStart->copy()->endOfWeek();

        $goal = WeeklyGoal::firstOrCreate(
            [
                'business_id' => $business->id,
                'week_start' => $weekStart->format('Y-m-d'),
            ],
            [
                'week_end' => $weekEnd->format('Y-m-d'),
                'status' => $weekStart->lte(now()) && $weekEnd->gte(now()) ? 'in_progress' : 'pending',
            ]
        );

        // If newly created, set suggested targets based on previous performance
        if ($goal->wasRecentlyCreated) {
            $this->setSuggestedTargets($goal, $business);
        }

        return $goal;
    }

    /**
     * Set suggested targets based on historical data
     */
    public function setSuggestedTargets(WeeklyGoal $goal, Business $business): void
    {
        // Get average from last 4 weeks
        $lastWeeks = WeeklyAnalytics::where('business_id', $business->id)
            ->where('week_start', '<', $goal->week_start)
            ->orderByDesc('week_start')
            ->limit(4)
            ->get();

        if ($lastWeeks->isEmpty()) {
            // Set default targets
            $goal->target_leads = 10;
            $goal->target_won = 3;
            $goal->target_conversion = 30;
            $goal->target_revenue = 5000000;
            $goal->target_calls = 50;
            $goal->target_meetings = 5;
            $goal->save();

            return;
        }

        // Calculate averages
        $avgLeads = 0;
        $avgWon = 0;
        $avgConversion = 0;
        $avgRevenue = 0;
        $avgCalls = 0;

        foreach ($lastWeeks as $week) {
            $summary = $week->summary_stats ?? [];
            $avgLeads += ($summary['total_leads'] ?? 0);
            $avgWon += ($summary['won'] ?? 0);
            $avgConversion += ($summary['conversion_rate'] ?? 0);
            $avgRevenue += ($summary['total_revenue'] ?? 0);

            $callStats = $week->call_stats ?? [];
            $avgCalls += ($callStats['total'] ?? 0);
        }

        $count = $lastWeeks->count();
        $avgLeads = round($avgLeads / $count);
        $avgWon = round($avgWon / $count);
        $avgConversion = round($avgConversion / $count, 1);
        $avgRevenue = round($avgRevenue / $count);
        $avgCalls = round($avgCalls / $count);

        // Set targets slightly higher than average (10% improvement)
        $goal->target_leads = max(1, ceil($avgLeads * 1.1));
        $goal->target_won = max(1, ceil($avgWon * 1.1));
        $goal->target_conversion = min(100, round($avgConversion * 1.1, 1));
        $goal->target_revenue = round($avgRevenue * 1.1);
        $goal->target_calls = max(1, ceil($avgCalls * 1.1));
        $goal->target_meetings = max(1, ceil($avgWon * 0.5)); // Meetings = ~50% of won deals

        // Set AI suggested goal from last week's analysis
        $lastWeek = $lastWeeks->first();
        if ($lastWeek && $lastWeek->ai_next_week_goal) {
            $goal->ai_suggested_goal = $lastWeek->ai_next_week_goal;
            $goal->ai_focus_areas = $lastWeek->ai_recommendations ?? [];
        }

        $goal->save();
    }

    /**
     * Update goal targets manually
     */
    public function updateTargets(WeeklyGoal $goal, array $targets): WeeklyGoal
    {
        $allowedFields = [
            'target_leads',
            'target_won',
            'target_conversion',
            'target_revenue',
            'target_calls',
            'target_meetings',
            'notes',
        ];

        foreach ($targets as $field => $value) {
            if (in_array($field, $allowedFields)) {
                $goal->{$field} = $value;
            }
        }

        $goal->save();

        return $goal;
    }

    /**
     * Update actual results from WeeklyAnalytics
     */
    public function updateActuals(WeeklyGoal $goal): WeeklyGoal
    {
        $analytics = WeeklyAnalytics::where('business_id', $goal->business_id)
            ->where('week_start', $goal->week_start->format('Y-m-d'))
            ->first();

        if (!$analytics) {
            return $goal;
        }

        $summary = $analytics->summary_stats ?? [];
        $callStats = $analytics->call_stats ?? [];

        $goal->actual_leads = $summary['total_leads'] ?? 0;
        $goal->actual_won = $summary['won'] ?? 0;
        $goal->actual_conversion = $summary['conversion_rate'] ?? 0;
        $goal->actual_revenue = $summary['total_revenue'] ?? 0;
        $goal->actual_calls = $callStats['total'] ?? 0;
        $goal->actual_meetings = $summary['meetings'] ?? 0;

        // Link to analytics
        $goal->weekly_analytics_id = $analytics->id;

        // Update achievements and status
        $goal->updateAchievements();
        $goal->updateStatus();
        $goal->save();

        return $goal;
    }

    /**
     * Get current progress for a goal
     */
    public function getCurrentProgress(WeeklyGoal $goal): array
    {
        $weekStart = $goal->week_start;
        $weekEnd = $goal->week_end;
        $today = now();

        // Calculate real-time actuals
        $leads = Lead::where('business_id', $goal->business_id)
            ->whereBetween('created_at', [$weekStart, $weekEnd->endOfDay()])
            ->selectRaw('
                COUNT(*) as total,
                SUM(CASE WHEN status = "won" THEN 1 ELSE 0 END) as won,
                SUM(CASE WHEN status = "won" THEN COALESCE(estimated_value, 0) ELSE 0 END) as revenue
            ')
            ->first();

        $callsCount = 0;
        if (Schema::hasTable('calls')) {
            $callsCount = DB::table('calls')
                ->where('business_id', $goal->business_id)
                ->whereBetween('created_at', [$weekStart, $weekEnd->endOfDay()])
                ->count();
        }

        // Calculate days progress
        $totalDays = $weekStart->diffInDays($weekEnd) + 1;
        $daysElapsed = $weekStart->diffInDays(min($today, $weekEnd)) + 1;
        $daysProgress = round(($daysElapsed / $totalDays) * 100);

        // Calculate metric progress
        $metrics = [
            'leads' => [
                'target' => $goal->target_leads,
                'actual' => $leads->total ?? 0,
                'progress' => $goal->target_leads > 0
                    ? round((($leads->total ?? 0) / $goal->target_leads) * 100)
                    : 0,
            ],
            'won' => [
                'target' => $goal->target_won,
                'actual' => $leads->won ?? 0,
                'progress' => $goal->target_won > 0
                    ? round((($leads->won ?? 0) / $goal->target_won) * 100)
                    : 0,
            ],
            'revenue' => [
                'target' => $goal->target_revenue,
                'actual' => $leads->revenue ?? 0,
                'progress' => $goal->target_revenue > 0
                    ? round((($leads->revenue ?? 0) / $goal->target_revenue) * 100)
                    : 0,
            ],
            'calls' => [
                'target' => $goal->target_calls,
                'actual' => $callsCount,
                'progress' => $goal->target_calls > 0
                    ? round(($callsCount / $goal->target_calls) * 100)
                    : 0,
            ],
        ];

        // Determine overall status
        $avgProgress = array_sum(array_column($metrics, 'progress')) / count($metrics);
        $status = 'on_track';

        if ($avgProgress < ($daysProgress * 0.7)) {
            $status = 'behind';
        } elseif ($avgProgress >= ($daysProgress * 1.2)) {
            $status = 'ahead';
        }

        return [
            'days_elapsed' => $daysElapsed,
            'total_days' => $totalDays,
            'days_progress' => $daysProgress,
            'metrics' => $metrics,
            'avg_progress' => round($avgProgress),
            'status' => $status,
        ];
    }

    /**
     * Get operator KPIs for a goal
     */
    public function getOperatorKpis(WeeklyGoal $goal): Collection
    {
        return OperatorWeeklyKpi::where('weekly_goal_id', $goal->id)
            ->with('user:id,name')
            ->orderBy('rank')
            ->get();
    }

    /**
     * Update operator KPIs for a week
     */
    public function updateOperatorKpis(Business $business, Carbon $weekStart): void
    {
        $weekEnd = $weekStart->copy()->endOfWeek();
        $goal = $this->getOrCreateGoal($business, $weekStart);

        // Get all operators with leads this week
        $operatorStats = Lead::where('business_id', $business->id)
            ->whereBetween('created_at', [$weekStart, $weekEnd->endOfDay()])
            ->whereNotNull('assigned_to')
            ->selectRaw('
                assigned_to,
                COUNT(*) as total_leads,
                SUM(CASE WHEN status = "won" THEN 1 ELSE 0 END) as won,
                SUM(CASE WHEN status = "won" THEN COALESCE(estimated_value, 0) ELSE 0 END) as revenue
            ')
            ->groupBy('assigned_to')
            ->get();

        // Get calls per operator
        $callStats = collect();
        if (Schema::hasTable('calls')) {
            $callStats = DB::table('calls')
                ->where('business_id', $business->id)
                ->whereBetween('created_at', [$weekStart, $weekEnd->endOfDay()])
                ->whereNotNull('user_id')
                ->selectRaw('user_id, COUNT(*) as total_calls')
                ->groupBy('user_id')
                ->pluck('total_calls', 'user_id');
        }

        foreach ($operatorStats as $stat) {
            $kpi = OperatorWeeklyKpi::getOrCreateForWeek(
                $business->id,
                $stat->assigned_to,
                $weekStart
            );

            $kpi->weekly_goal_id = $goal->id;
            $kpi->actual_leads = $stat->total_leads;
            $kpi->actual_won = $stat->won;
            $kpi->actual_revenue = $stat->revenue;
            $kpi->actual_calls = $callStats[$stat->assigned_to] ?? 0;

            // Set targets based on business goal, distributed evenly
            $operatorCount = $operatorStats->count();
            if ($operatorCount > 0) {
                $kpi->target_leads = ceil($goal->target_leads / $operatorCount);
                $kpi->target_won = ceil($goal->target_won / $operatorCount);
                $kpi->target_revenue = round($goal->target_revenue / $operatorCount);
                $kpi->target_calls = ceil($goal->target_calls / $operatorCount);
            }

            $kpi->updateScore();
            $kpi->save();
        }

        // Update ranks
        OperatorWeeklyKpi::updateRanks($business->id, $weekStart);
    }

    /**
     * Get history of weekly goals
     */
    public function getGoalHistory(Business $business, int $weeks = 8): Collection
    {
        return WeeklyGoal::where('business_id', $business->id)
            ->orderByDesc('week_start')
            ->limit($weeks)
            ->get()
            ->map(function ($goal) {
                return [
                    'id' => $goal->id,
                    'week_start' => $goal->week_start->format('Y-m-d'),
                    'week_end' => $goal->week_end->format('Y-m-d'),
                    'week_label' => $goal->week_label,
                    'status' => $goal->status,
                    'overall_score' => $goal->overall_score,
                    'targets' => [
                        'leads' => $goal->target_leads,
                        'won' => $goal->target_won,
                        'conversion' => $goal->target_conversion,
                        'revenue' => $goal->target_revenue,
                    ],
                    'actuals' => [
                        'leads' => $goal->actual_leads,
                        'won' => $goal->actual_won,
                        'conversion' => $goal->actual_conversion,
                        'revenue' => $goal->actual_revenue,
                    ],
                    'achievements' => [
                        'leads' => $goal->leads_achievement,
                        'won' => $goal->won_achievement,
                        'conversion' => $goal->conversion_achievement,
                        'revenue' => $goal->revenue_achievement,
                    ],
                ];
            });
    }

    /**
     * Calculate streak (consecutive weeks meeting goals)
     */
    public function getStreak(Business $business): int
    {
        $goals = WeeklyGoal::where('business_id', $business->id)
            ->where('status', 'completed')
            ->orderByDesc('week_start')
            ->get();

        $streak = 0;
        $lastWeekEnd = null;

        foreach ($goals as $goal) {
            if ($lastWeekEnd === null) {
                $streak++;
                $lastWeekEnd = $goal->week_end;
            } elseif ($goal->week_end->addDays(1)->isSameDay($lastWeekEnd->startOfWeek())) {
                $streak++;
                $lastWeekEnd = $goal->week_end;
            } else {
                break;
            }
        }

        return $streak;
    }
}
