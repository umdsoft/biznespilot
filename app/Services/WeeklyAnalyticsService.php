<?php

namespace App\Services;

use App\Models\Business;
use App\Models\CallLog;
use App\Models\Lead;
use App\Models\MarketingChannel;
use App\Models\Task;
use App\Models\User;
use App\Models\WeeklyAnalytics;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WeeklyAnalyticsService
{
    /**
     * Generate weekly analytics for a business
     */
    public function generateWeeklyReport(Business $business, ?Carbon $weekStart = null): WeeklyAnalytics
    {
        $weekStart = $weekStart ?? now()->startOfWeek();
        $weekEnd = $weekStart->copy()->endOfWeek();

        // Check if already exists
        $existing = WeeklyAnalytics::where('business_id', $business->id)
            ->whereDate('week_start', $weekStart->format('Y-m-d'))
            ->first();

        if ($existing) {
            return $existing;
        }

        try {
            // Collect all statistics with error handling
            $summaryStats = $this->collectSummaryStats($business, $weekStart, $weekEnd);
            $channelStats = $this->collectChannelStats($business, $weekStart, $weekEnd);
            $operatorStats = $this->collectOperatorStats($business, $weekStart, $weekEnd);
            $timeStats = $this->collectTimeStats($business, $weekStart, $weekEnd);
            $lostReasonStats = $this->collectLostReasonStats($business, $weekStart, $weekEnd);
            $trendStats = $this->collectTrendStats($business, $weekStart);

            // NEW: Additional valuable stats
            $regionalStats = $this->collectRegionalStats($business, $weekStart, $weekEnd);
            $qualificationStats = $this->collectQualificationStats($business, $weekStart, $weekEnd);
            $callStats = $this->collectCallStats($business, $weekStart, $weekEnd);
            $taskStats = $this->collectTaskStats($business, $weekStart, $weekEnd);
            $pipelineStats = $this->collectPipelineStats($business, $weekStart, $weekEnd);

            // Create the record
            $analytics = WeeklyAnalytics::create([
                'business_id' => $business->id,
                'week_start' => $weekStart->format('Y-m-d'),
                'week_end' => $weekEnd->format('Y-m-d'),
                'summary_stats' => $summaryStats,
                'channel_stats' => $channelStats,
                'operator_stats' => $operatorStats,
                'time_stats' => $timeStats,
                'lost_reason_stats' => $lostReasonStats,
                'trend_stats' => $trendStats,
                // Extended stats stored in summary for AI
                'regional_stats' => $regionalStats,
                'qualification_stats' => $qualificationStats,
                'call_stats' => $callStats,
                'task_stats' => $taskStats,
                'pipeline_stats' => $pipelineStats,
            ]);

            return $analytics;
        } catch (\Exception $e) {
            Log::error('Failed to generate weekly report', [
                'business_id' => $business->id,
                'week_start' => $weekStart->format('Y-m-d'),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }

    /**
     * Generate AI analysis for the weekly report
     */
    public function generateAiAnalysis(WeeklyAnalytics $analytics): WeeklyAnalytics
    {
        if ($analytics->hasAiAnalysis()) {
            return $analytics;
        }

        $data = $this->prepareDataForAi($analytics);
        $prompt = $this->buildAiPrompt($data);

        try {
            $response = $this->callClaudeHaiku($prompt);
            $parsed = $this->parseAiResponse($response['content']);

            $analytics->update([
                'ai_good_results' => $parsed['good_results'],
                'ai_problems' => $parsed['problems'],
                'ai_recommendations' => $parsed['recommendations'],
                'ai_next_week_goal' => $parsed['next_week_goal'],
                'ai_raw_response' => $response['content'],
                'tokens_used' => $response['tokens_used'] ?? 0,
                'generated_at' => now(),
            ]);
        } catch (\Exception $e) {
            Log::error('Weekly AI Analysis failed', [
                'business_id' => $analytics->business_id,
                'error' => $e->getMessage(),
            ]);
        }

        return $analytics->fresh();
    }

    /**
     * Collect summary statistics - ENHANCED VERSION
     */
    protected function collectSummaryStats(Business $business, Carbon $start, Carbon $end): array
    {
        // Current week leads
        $leads = Lead::where('business_id', $business->id)
            ->whereBetween('created_at', [$start, $end])
            ->get();

        $wonLeads = $leads->where('status', 'won');
        $lostLeads = $leads->where('status', 'lost');
        $inProgress = $leads->whereNotIn('status', ['won', 'lost']);

        $totalRevenue = (float) $wonLeads->sum('estimated_value');
        $lostRevenue = (float) $lostLeads->sum('estimated_value');
        $pipelineValue = (float) $inProgress->sum('estimated_value');

        $conversionRate = $leads->count() > 0
            ? round(($wonLeads->count() / $leads->count()) * 100, 1)
            : 0;

        // Win/Loss ratio
        $winLossRatio = $lostLeads->count() > 0
            ? round($wonLeads->count() / $lostLeads->count(), 2)
            : ($wonLeads->count() > 0 ? $wonLeads->count() : 0);

        // Average sales cycle for won leads (only with converted_at)
        $avgSalesCycleDays = null;
        $wonWithConversion = $wonLeads->filter(fn ($l) => $l->converted_at !== null);
        if ($wonWithConversion->isNotEmpty()) {
            $avgSalesCycleDays = round($wonWithConversion
                ->map(fn ($l) => $l->created_at->diffInDays($l->converted_at))
                ->avg(), 1);
        }

        // Lead velocity (how fast are we getting leads)
        $leadsPerDay = $leads->count() > 0
            ? round($leads->count() / 7, 1)
            : 0;

        // Previous week comparison
        $prevStart = $start->copy()->subWeek();
        $prevEnd = $end->copy()->subWeek();

        $prevLeads = Lead::where('business_id', $business->id)
            ->whereBetween('created_at', [$prevStart, $prevEnd])
            ->get();

        $prevWon = $prevLeads->where('status', 'won');
        $prevLost = $prevLeads->where('status', 'lost');
        $prevConversion = $prevLeads->count() > 0
            ? round(($prevWon->count() / $prevLeads->count()) * 100, 1)
            : 0;
        $prevRevenue = (float) $prevWon->sum('estimated_value');
        $prevLostRevenue = (float) $prevLost->sum('estimated_value');

        // Assigned vs Unassigned
        $assignedLeads = $leads->whereNotNull('assigned_to')->count();
        $unassignedLeads = $leads->whereNull('assigned_to')->count();

        // Hot leads (high score)
        $hotLeads = $leads->filter(fn ($l) => ($l->score ?? 0) >= 80)->count();

        return [
            'total_leads' => $leads->count(),
            'won' => $wonLeads->count(),
            'lost' => $lostLeads->count(),
            'in_progress' => $inProgress->count(),
            'conversion_rate' => $conversionRate,
            'total_revenue' => $totalRevenue,
            'lost_revenue' => $lostRevenue,
            'pipeline_value' => $pipelineValue,
            'avg_deal_value' => $wonLeads->count() > 0 ? round($totalRevenue / $wonLeads->count(), 0) : 0,
            'win_loss_ratio' => $winLossRatio,
            'avg_sales_cycle_days' => $avgSalesCycleDays,
            'leads_per_day' => $leadsPerDay,
            'assigned_leads' => $assignedLeads,
            'unassigned_leads' => $unassignedLeads,
            'hot_leads' => $hotLeads,
            'vs_last_week' => [
                'leads' => $this->calcPercentChange($prevLeads->count(), $leads->count()),
                'leads_diff' => $leads->count() - $prevLeads->count(),
                'won' => $this->calcPercentChange($prevWon->count(), $wonLeads->count()),
                'won_diff' => $wonLeads->count() - $prevWon->count(),
                'lost' => $this->calcPercentChange($prevLost->count(), $lostLeads->count()),
                'lost_diff' => $lostLeads->count() - $prevLost->count(),
                'conversion' => round($conversionRate - $prevConversion, 1),
                'revenue' => $this->calcPercentChange($prevRevenue, $totalRevenue),
                'revenue_diff' => $totalRevenue - $prevRevenue,
                'lost_revenue_diff' => $lostRevenue - $prevLostRevenue,
            ],
        ];
    }

    /**
     * Collect channel statistics - ENHANCED VERSION
     */
    protected function collectChannelStats(Business $business, Carbon $start, Carbon $end): array
    {
        $channels = MarketingChannel::where('business_id', $business->id)
            ->where('is_active', true)
            ->get();

        $result = [];
        $totalWeekLeads = 0;
        $totalWeekRevenue = 0;

        foreach ($channels as $channel) {
            $leads = Lead::where('business_id', $business->id)
                ->where('marketing_channel_id', $channel->id)
                ->whereBetween('created_at', [$start, $end])
                ->get();

            $won = $leads->where('status', 'won');
            $lost = $leads->where('status', 'lost');
            $inProgress = $leads->whereNotIn('status', ['won', 'lost']);

            $revenue = (float) $won->sum('estimated_value');
            $lostRevenue = (float) $lost->sum('estimated_value');
            $pipelineValue = (float) $inProgress->sum('estimated_value');

            $totalWeekLeads += $leads->count();
            $totalWeekRevenue += $revenue;

            // Lost reasons breakdown
            $lostReasons = $lost->groupBy('lost_reason')->map->count()->toArray();

            // Previous week for comparison
            $prevStart = $start->copy()->subWeek();
            $prevEnd = $end->copy()->subWeek();
            $prevLeads = Lead::where('business_id', $business->id)
                ->where('marketing_channel_id', $channel->id)
                ->whereBetween('created_at', [$prevStart, $prevEnd])
                ->get();
            $prevWon = $prevLeads->where('status', 'won');
            $prevConv = $prevLeads->count() > 0
                ? round(($prevWon->count() / $prevLeads->count()) * 100, 1)
                : 0;
            $prevRevenue = (float) $prevWon->sum('estimated_value');

            $conversion = $leads->count() > 0
                ? round(($won->count() / $leads->count()) * 100, 1)
                : 0;

            // Average sales cycle (only for leads with converted_at)
            $avgCycle = null;
            $wonWithConversion = $won->filter(fn ($l) => $l->converted_at !== null);
            if ($wonWithConversion->isNotEmpty()) {
                $avgCycle = $wonWithConversion
                    ->map(fn ($l) => $l->created_at->diffInDays($l->converted_at))
                    ->avg();
            }

            // Cost metrics (CPL, ROI) - if channel has cost data
            $channelCost = $channel->weekly_budget ?? $channel->monthly_budget / 4 ?? 0;
            $cpl = $leads->count() > 0 && $channelCost > 0
                ? round($channelCost / $leads->count(), 0)
                : null;
            $cpa = $won->count() > 0 && $channelCost > 0
                ? round($channelCost / $won->count(), 0)
                : null;
            $roi = $channelCost > 0 && $revenue > 0
                ? round((($revenue - $channelCost) / $channelCost) * 100, 1)
                : null;

            // Lead quality score (average score)
            $avgLeadScore = $leads->avg('score');

            // MQL/SQL breakdown
            $mqlCount = $leads->where('qualification_status', 'mql')->count();
            $sqlCount = $leads->where('qualification_status', 'sql')->count();

            // Hot leads from this channel
            $hotLeads = $leads->filter(fn ($l) => ($l->score ?? 0) >= 80)->count();

            $result[] = [
                'id' => $channel->id,
                'name' => $channel->name,
                'type' => $channel->type,
                'leads' => $leads->count(),
                'won' => $won->count(),
                'lost' => $lost->count(),
                'in_progress' => $inProgress->count(),
                'conversion' => $conversion,
                'revenue' => $revenue,
                'lost_revenue' => $lostRevenue,
                'pipeline_value' => $pipelineValue,
                'avg_deal_value' => $won->count() > 0 ? round($revenue / $won->count(), 0) : 0,
                // Cost metrics
                'cost' => $channelCost,
                'cpl' => $cpl, // Cost per lead
                'cpa' => $cpa, // Cost per acquisition
                'roi' => $roi, // Return on investment %
                // Quality metrics
                'avg_lead_score' => $avgLeadScore ? round($avgLeadScore, 1) : null,
                'mql_count' => $mqlCount,
                'sql_count' => $sqlCount,
                'hot_leads' => $hotLeads,
                // Comparison
                'vs_last_week' => [
                    'conversion' => round($conversion - $prevConv, 1),
                    'leads' => $this->calcPercentChange($prevLeads->count(), $leads->count()),
                    'leads_diff' => $leads->count() - $prevLeads->count(),
                    'revenue' => $this->calcPercentChange($prevRevenue, $revenue),
                    'revenue_diff' => $revenue - $prevRevenue,
                ],
                'lost_reasons' => $lostReasons,
                'avg_sales_cycle_days' => $avgCycle ? round($avgCycle, 1) : null,
            ];
        }

        // Calculate share percentages
        foreach ($result as &$item) {
            $item['leads_share'] = $totalWeekLeads > 0
                ? round(($item['leads'] / $totalWeekLeads) * 100, 1)
                : 0;
            $item['revenue_share'] = $totalWeekRevenue > 0
                ? round(($item['revenue'] / $totalWeekRevenue) * 100, 1)
                : 0;
        }

        // Sort by revenue (highest first)
        usort($result, fn ($a, $b) => $b['revenue'] <=> $a['revenue']);

        return $result;
    }

    /**
     * Collect operator statistics - ENHANCED VERSION
     */
    protected function collectOperatorStats(Business $business, Carbon $start, Carbon $end): array
    {
        $leads = Lead::where('business_id', $business->id)
            ->whereBetween('created_at', [$start, $end])
            ->whereNotNull('assigned_to')
            ->get();

        $grouped = $leads->groupBy('assigned_to');
        $result = [];
        $teamTotalLeads = $leads->count();
        $teamTotalRevenue = (float) $leads->where('status', 'won')->sum('estimated_value');

        foreach ($grouped as $userId => $userLeads) {
            $user = User::find($userId);
            if (! $user) {
                continue;
            }

            $won = $userLeads->where('status', 'won');
            $lost = $userLeads->where('status', 'lost');
            $inProgress = $userLeads->whereNotIn('status', ['won', 'lost']);

            $revenue = (float) $won->sum('estimated_value');
            $lostRevenue = (float) $lost->sum('estimated_value');

            $conversion = $userLeads->count() > 0
                ? round(($won->count() / $userLeads->count()) * 100, 1)
                : 0;

            // Win/Loss ratio
            $winLossRatio = $lost->count() > 0
                ? round($won->count() / $lost->count(), 2)
                : ($won->count() > 0 ? $won->count() : 0);

            // By channel performance
            $byChannel = [];
            foreach ($userLeads->groupBy('marketing_channel_id') as $channelId => $channelLeads) {
                $channel = MarketingChannel::find($channelId);
                $channelWon = $channelLeads->where('status', 'won');
                $channelLost = $channelLeads->where('status', 'lost');
                $byChannel[] = [
                    'channel_id' => $channelId,
                    'channel_name' => $channel?->name ?? 'Noma\'lum',
                    'leads' => $channelLeads->count(),
                    'won' => $channelWon->count(),
                    'lost' => $channelLost->count(),
                    'revenue' => (float) $channelWon->sum('estimated_value'),
                    'conversion' => $channelLeads->count() > 0
                        ? round(($channelWon->count() / $channelLeads->count()) * 100, 1)
                        : 0,
                ];
            }

            // Sort by conversion
            usort($byChannel, fn ($a, $b) => $b['conversion'] <=> $a['conversion']);

            // Lost reasons for this operator
            $lostReasons = $lost->groupBy('lost_reason')->map->count()->toArray();
            $topLostReason = null;
            if (! empty($lostReasons)) {
                $topLostReasonKey = array_keys($lostReasons, max($lostReasons))[0];
                $reasons = defined(Lead::class.'::LOST_REASONS') ? Lead::LOST_REASONS : [];
                $topLostReason = [
                    'reason' => $topLostReasonKey,
                    'label' => $reasons[$topLostReasonKey] ?? $topLostReasonKey,
                    'count' => $lostReasons[$topLostReasonKey],
                ];
            }

            // Average sales cycle for this operator
            $avgSalesCycle = null;
            $wonWithConversion = $won->filter(fn ($l) => $l->converted_at !== null);
            if ($wonWithConversion->isNotEmpty()) {
                $avgSalesCycle = round($wonWithConversion
                    ->map(fn ($l) => $l->created_at->diffInDays($l->converted_at))
                    ->avg(), 1);
            }

            // Hot leads handled
            $hotLeads = $userLeads->filter(fn ($l) => ($l->score ?? 0) >= 80)->count();
            $hotLeadsWon = $won->filter(fn ($l) => ($l->score ?? 0) >= 80)->count();

            // Workload metrics
            $leadsPerDay = round($userLeads->count() / 7, 1);

            // Previous week comparison
            $prevStart = $start->copy()->subWeek();
            $prevEnd = $end->copy()->subWeek();
            $prevLeads = Lead::where('business_id', $business->id)
                ->where('assigned_to', $userId)
                ->whereBetween('created_at', [$prevStart, $prevEnd])
                ->get();
            $prevWon = $prevLeads->where('status', 'won');
            $prevConv = $prevLeads->count() > 0
                ? round(($prevWon->count() / $prevLeads->count()) * 100, 1)
                : 0;
            $prevRevenue = (float) $prevWon->sum('estimated_value');

            // Rank calculation (will be set after sorting)
            $result[] = [
                'id' => $userId,
                'name' => $user->name,
                'email' => $user->email,
                'leads' => $userLeads->count(),
                'won' => $won->count(),
                'lost' => $lost->count(),
                'in_progress' => $inProgress->count(),
                'conversion' => $conversion,
                'win_loss_ratio' => $winLossRatio,
                'revenue' => $revenue,
                'lost_revenue' => $lostRevenue,
                'avg_deal_value' => $won->count() > 0 ? round($revenue / $won->count(), 0) : 0,
                'avg_sales_cycle_days' => $avgSalesCycle,
                // Workload
                'leads_per_day' => $leadsPerDay,
                'leads_share' => $teamTotalLeads > 0 ? round(($userLeads->count() / $teamTotalLeads) * 100, 1) : 0,
                'revenue_share' => $teamTotalRevenue > 0 ? round(($revenue / $teamTotalRevenue) * 100, 1) : 0,
                // Hot leads
                'hot_leads' => $hotLeads,
                'hot_leads_won' => $hotLeadsWon,
                'hot_conversion' => $hotLeads > 0 ? round(($hotLeadsWon / $hotLeads) * 100, 1) : 0,
                // Lost analysis
                'top_lost_reason' => $topLostReason,
                'lost_reasons' => $lostReasons,
                // Comparison
                'vs_last_week' => [
                    'conversion' => round($conversion - $prevConv, 1),
                    'leads' => $this->calcPercentChange($prevLeads->count(), $userLeads->count()),
                    'leads_diff' => $userLeads->count() - $prevLeads->count(),
                    'won' => $this->calcPercentChange($prevWon->count(), $won->count()),
                    'won_diff' => $won->count() - $prevWon->count(),
                    'revenue' => $this->calcPercentChange($prevRevenue, $revenue),
                    'revenue_diff' => $revenue - $prevRevenue,
                ],
                'by_channel' => $byChannel,
                'best_channel' => $byChannel[0]['channel_name'] ?? null,
                'worst_channel' => count($byChannel) > 1 ? end($byChannel)['channel_name'] : null,
            ];
        }

        // Sort by revenue
        usort($result, fn ($a, $b) => $b['revenue'] <=> $a['revenue']);

        // Add ranks
        foreach ($result as $index => &$item) {
            $item['rank'] = $index + 1;
        }

        return $result;
    }

    /**
     * Collect time-based statistics
     */
    protected function collectTimeStats(Business $business, Carbon $start, Carbon $end): array
    {
        $leads = Lead::where('business_id', $business->id)
            ->whereBetween('created_at', [$start, $end])
            ->get();

        // By day of week
        $byDay = [];
        $dayNames = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];
        $dayLabels = ['Dushanba', 'Seshanba', 'Chorshanba', 'Payshanba', 'Juma', 'Shanba', 'Yakshanba'];

        foreach ($dayNames as $index => $day) {
            $dayLeads = $leads->filter(fn ($l) => strtolower($l->created_at->format('l')) === $day);
            $won = $dayLeads->where('status', 'won');

            $byDay[$day] = [
                'label' => $dayLabels[$index],
                'leads' => $dayLeads->count(),
                'won' => $won->count(),
                'conversion' => $dayLeads->count() > 0
                    ? round(($won->count() / $dayLeads->count()) * 100, 1)
                    : 0,
            ];
        }

        // By hour ranges
        $byHour = [
            '09-12' => ['label' => '09:00-12:00', 'leads' => 0, 'won' => 0],
            '12-14' => ['label' => '12:00-14:00', 'leads' => 0, 'won' => 0],
            '14-18' => ['label' => '14:00-18:00', 'leads' => 0, 'won' => 0],
            '18-21' => ['label' => '18:00-21:00', 'leads' => 0, 'won' => 0],
        ];

        foreach ($leads as $lead) {
            $hour = (int) $lead->created_at->format('H');

            if ($hour >= 9 && $hour < 12) {
                $byHour['09-12']['leads']++;
                if ($lead->status === 'won') {
                    $byHour['09-12']['won']++;
                }
            } elseif ($hour >= 12 && $hour < 14) {
                $byHour['12-14']['leads']++;
                if ($lead->status === 'won') {
                    $byHour['12-14']['won']++;
                }
            } elseif ($hour >= 14 && $hour < 18) {
                $byHour['14-18']['leads']++;
                if ($lead->status === 'won') {
                    $byHour['14-18']['won']++;
                }
            } elseif ($hour >= 18 && $hour < 21) {
                $byHour['18-21']['leads']++;
                if ($lead->status === 'won') {
                    $byHour['18-21']['won']++;
                }
            }
        }

        // Calculate conversion for hours
        foreach ($byHour as $key => $data) {
            $byHour[$key]['conversion'] = $data['leads'] > 0
                ? round(($data['won'] / $data['leads']) * 100, 1)
                : 0;
        }

        // Find best/worst
        $bestDay = collect($byDay)->sortByDesc('conversion')->keys()->first();
        $worstDay = collect($byDay)->filter(fn ($d) => $d['leads'] > 0)->sortBy('conversion')->keys()->first();
        $bestHour = collect($byHour)->sortByDesc('conversion')->keys()->first();
        $worstHour = collect($byHour)->filter(fn ($d) => $d['leads'] > 0)->sortBy('conversion')->keys()->first();

        return [
            'by_day' => $byDay,
            'by_hour' => $byHour,
            'best_day' => $bestDay,
            'worst_day' => $worstDay,
            'best_hour' => $bestHour,
            'worst_hour' => $worstHour,
        ];
    }

    /**
     * Collect lost reason statistics
     */
    protected function collectLostReasonStats(Business $business, Carbon $start, Carbon $end): array
    {
        $lostLeads = Lead::where('business_id', $business->id)
            ->where('status', 'lost')
            ->whereBetween('updated_at', [$start, $end])
            ->get();

        $total = $lostLeads->count();
        $grouped = $lostLeads->groupBy('lost_reason');

        // Previous week for comparison
        $prevStart = $start->copy()->subWeek();
        $prevEnd = $end->copy()->subWeek();
        $prevLost = Lead::where('business_id', $business->id)
            ->where('status', 'lost')
            ->whereBetween('updated_at', [$prevStart, $prevEnd])
            ->get();
        $prevGrouped = $prevLost->groupBy('lost_reason');
        $prevTotal = $prevLost->count();

        $result = [];
        $reasons = defined(Lead::class.'::LOST_REASONS') ? Lead::LOST_REASONS : [];

        foreach ($grouped as $reason => $leads) {
            $count = $leads->count();
            $percentage = $total > 0 ? round(($count / $total) * 100, 1) : 0;

            $prevCount = $prevGrouped->get($reason)?->count() ?? 0;
            $prevPercentage = $prevTotal > 0 ? round(($prevCount / $prevTotal) * 100, 1) : 0;

            $result[] = [
                'reason' => $reason ?: 'unknown',
                'label' => $reasons[$reason] ?? ($reason ?: 'Noma\'lum'),
                'count' => $count,
                'percentage' => $percentage,
                'value_lost' => $leads->sum('estimated_value'),
                'vs_last_week' => round($percentage - $prevPercentage, 1),
            ];
        }

        // Sort by count
        usort($result, fn ($a, $b) => $b['count'] <=> $a['count']);

        return [
            'total_lost' => $total,
            'total_value_lost' => $lostLeads->sum('estimated_value'),
            'reasons' => $result,
        ];
    }

    /**
     * Collect trend statistics (last 4 weeks)
     */
    protected function collectTrendStats(Business $business, Carbon $currentWeekStart): array
    {
        $trends = [
            'conversion' => [],
            'leads' => [],
            'revenue' => [],
            'weeks' => [],
        ];

        for ($i = 3; $i >= 0; $i--) {
            $weekStart = $currentWeekStart->copy()->subWeeks($i);
            $weekEnd = $weekStart->copy()->endOfWeek();

            $leads = Lead::where('business_id', $business->id)
                ->whereBetween('created_at', [$weekStart, $weekEnd])
                ->get();

            $won = $leads->where('status', 'won');
            $conversion = $leads->count() > 0
                ? round(($won->count() / $leads->count()) * 100, 1)
                : 0;

            $trends['weeks'][] = $weekStart->format('d.m');
            $trends['leads'][] = $leads->count();
            $trends['conversion'][] = $conversion;
            $trends['revenue'][] = $won->sum('estimated_value');
        }

        return $trends;
    }

    /**
     * Collect regional statistics
     */
    protected function collectRegionalStats(Business $business, Carbon $start, Carbon $end): array
    {
        $leads = Lead::where('business_id', $business->id)
            ->whereBetween('created_at', [$start, $end])
            ->whereNotNull('region')
            ->get();

        $grouped = $leads->groupBy('region');
        $result = [];
        $regions = defined(Lead::class.'::REGIONS') ? Lead::REGIONS : [];

        foreach ($grouped as $region => $regionLeads) {
            $won = $regionLeads->where('status', 'won');
            $lost = $regionLeads->where('status', 'lost');

            $result[] = [
                'region' => $region,
                'label' => $regions[$region] ?? $region,
                'leads' => $regionLeads->count(),
                'won' => $won->count(),
                'lost' => $lost->count(),
                'conversion' => $regionLeads->count() > 0
                    ? round(($won->count() / $regionLeads->count()) * 100, 1)
                    : 0,
                'revenue' => (float) $won->sum('estimated_value'),
                'avg_deal_value' => $won->count() > 0
                    ? round((float) $won->sum('estimated_value') / $won->count(), 0)
                    : 0,
            ];
        }

        // Sort by leads count
        usort($result, fn ($a, $b) => $b['leads'] <=> $a['leads']);

        // Add totals
        $totalLeads = $leads->count();
        $totalWon = $leads->where('status', 'won')->count();

        return [
            'total_with_region' => $totalLeads,
            'regions' => array_slice($result, 0, 10), // Top 10 regions
            'best_region' => $result[0] ?? null,
            'best_converting_region' => collect($result)->sortByDesc('conversion')->first(),
        ];
    }

    /**
     * Collect qualification statistics (MQL/SQL funnel)
     */
    protected function collectQualificationStats(Business $business, Carbon $start, Carbon $end): array
    {
        $leads = Lead::where('business_id', $business->id)
            ->whereBetween('created_at', [$start, $end])
            ->get();

        $newLeads = $leads->where('qualification_status', 'new')->count();
        $mqlLeads = $leads->where('qualification_status', 'mql');
        $sqlLeads = $leads->where('qualification_status', 'sql');
        $disqualified = $leads->where('qualification_status', 'disqualified')->count();

        $total = $leads->count();

        // MQL to SQL conversion
        $mqlToSql = $mqlLeads->count() > 0
            ? round(($sqlLeads->count() / ($mqlLeads->count() + $sqlLeads->count())) * 100, 1)
            : 0;

        // SQL to Won conversion
        $sqlWon = $sqlLeads->where('status', 'won')->count();
        $sqlToWon = $sqlLeads->count() > 0
            ? round(($sqlWon / $sqlLeads->count()) * 100, 1)
            : 0;

        // Previous week
        $prevStart = $start->copy()->subWeek();
        $prevEnd = $end->copy()->subWeek();
        $prevLeads = Lead::where('business_id', $business->id)
            ->whereBetween('created_at', [$prevStart, $prevEnd])
            ->get();
        $prevMql = $prevLeads->where('qualification_status', 'mql')->count();
        $prevSql = $prevLeads->where('qualification_status', 'sql')->count();

        return [
            'new' => $newLeads,
            'mql' => $mqlLeads->count(),
            'sql' => $sqlLeads->count(),
            'disqualified' => $disqualified,
            'mql_rate' => $total > 0 ? round(($mqlLeads->count() / $total) * 100, 1) : 0,
            'sql_rate' => $total > 0 ? round(($sqlLeads->count() / $total) * 100, 1) : 0,
            'disqualification_rate' => $total > 0 ? round(($disqualified / $total) * 100, 1) : 0,
            'mql_to_sql' => $mqlToSql,
            'sql_to_won' => $sqlToWon,
            'mql_revenue' => (float) $mqlLeads->where('status', 'won')->sum('estimated_value'),
            'sql_revenue' => (float) $sqlLeads->where('status', 'won')->sum('estimated_value'),
            'vs_last_week' => [
                'mql' => $this->calcPercentChange($prevMql, $mqlLeads->count()),
                'sql' => $this->calcPercentChange($prevSql, $sqlLeads->count()),
            ],
        ];
    }

    /**
     * Collect call statistics
     */
    protected function collectCallStats(Business $business, Carbon $start, Carbon $end): array
    {
        // Check if CallLog model exists
        if (! class_exists(CallLog::class)) {
            return [];
        }

        $calls = CallLog::where('business_id', $business->id)
            ->whereBetween('created_at', [$start, $end])
            ->get();

        $totalCalls = $calls->count();
        $outbound = $calls->where('direction', 'outbound');
        $inbound = $calls->where('direction', 'inbound');
        $completed = $calls->where('status', 'completed');
        $missed = $calls->whereIn('status', ['missed', 'no_answer']);

        $totalDuration = $calls->sum('duration') ?? 0;
        $avgDuration = $completed->count() > 0
            ? round($completed->avg('duration'), 0)
            : 0;

        // Answer rate
        $answerRate = $totalCalls > 0
            ? round(($completed->count() / $totalCalls) * 100, 1)
            : 0;

        // Calls per operator
        $byOperator = $calls->groupBy('user_id')->map(fn ($c) => [
            'total' => $c->count(),
            'completed' => $c->where('status', 'completed')->count(),
            'duration' => $c->sum('duration'),
        ])->toArray();

        // Previous week
        $prevStart = $start->copy()->subWeek();
        $prevEnd = $end->copy()->subWeek();
        $prevCalls = CallLog::where('business_id', $business->id)
            ->whereBetween('created_at', [$prevStart, $prevEnd])
            ->count();

        return [
            'total_calls' => $totalCalls,
            'outbound' => $outbound->count(),
            'inbound' => $inbound->count(),
            'completed' => $completed->count(),
            'missed' => $missed->count(),
            'answer_rate' => $answerRate,
            'total_duration_minutes' => round($totalDuration / 60, 1),
            'avg_duration_seconds' => $avgDuration,
            'calls_per_day' => round($totalCalls / 7, 1),
            'by_operator' => $byOperator,
            'vs_last_week' => [
                'total' => $this->calcPercentChange($prevCalls, $totalCalls),
                'diff' => $totalCalls - $prevCalls,
            ],
        ];
    }

    /**
     * Collect task statistics
     */
    protected function collectTaskStats(Business $business, Carbon $start, Carbon $end): array
    {
        // Check if Task model exists
        if (! class_exists(Task::class)) {
            return [];
        }

        $tasks = Task::where('business_id', $business->id)
            ->whereBetween('created_at', [$start, $end])
            ->get();

        $completed = $tasks->where('status', 'completed');
        $pending = $tasks->where('status', 'pending');
        $overdue = $tasks->filter(fn ($t) => $t->due_date && $t->due_date < now() && $t->status !== 'completed');

        $totalTasks = $tasks->count();
        $completionRate = $totalTasks > 0
            ? round(($completed->count() / $totalTasks) * 100, 1)
            : 0;

        // By type
        $byType = $tasks->groupBy('type')->map(fn ($t) => [
            'total' => $t->count(),
            'completed' => $t->where('status', 'completed')->count(),
        ])->toArray();

        // By operator
        $byOperator = $tasks->groupBy('assigned_to')->map(fn ($t) => [
            'total' => $t->count(),
            'completed' => $t->where('status', 'completed')->count(),
            'completion_rate' => $t->count() > 0
                ? round(($t->where('status', 'completed')->count() / $t->count()) * 100, 1)
                : 0,
        ])->toArray();

        return [
            'total_tasks' => $totalTasks,
            'completed' => $completed->count(),
            'pending' => $pending->count(),
            'overdue' => $overdue->count(),
            'completion_rate' => $completionRate,
            'overdue_rate' => $totalTasks > 0 ? round(($overdue->count() / $totalTasks) * 100, 1) : 0,
            'by_type' => $byType,
            'by_operator' => $byOperator,
        ];
    }

    /**
     * Collect pipeline statistics
     */
    protected function collectPipelineStats(Business $business, Carbon $start, Carbon $end): array
    {
        $leads = Lead::where('business_id', $business->id)
            ->whereBetween('created_at', [$start, $end])
            ->get();

        // Group by status
        $byStatus = $leads->groupBy('status');
        $statusStats = [];

        foreach ($byStatus as $status => $statusLeads) {
            $statusStats[$status] = [
                'count' => $statusLeads->count(),
                'value' => (float) $statusLeads->sum('estimated_value'),
                'avg_value' => $statusLeads->count() > 0
                    ? round((float) $statusLeads->sum('estimated_value') / $statusLeads->count(), 0)
                    : 0,
            ];
        }

        // Pipeline velocity (how fast leads move through pipeline)
        $avgTimeToClose = null;
        $wonLeads = $leads->where('status', 'won')->filter(fn ($l) => $l->converted_at !== null);
        if ($wonLeads->isNotEmpty()) {
            $avgTimeToClose = round($wonLeads
                ->map(fn ($l) => $l->created_at->diffInDays($l->converted_at))
                ->avg(), 1);
        }

        // Stage change activity
        $stageChanges = $leads->filter(fn ($l) => $l->stage_changed_at !== null)->count();

        return [
            'by_status' => $statusStats,
            'total_pipeline_value' => (float) $leads->whereNotIn('status', ['won', 'lost'])->sum('estimated_value'),
            'avg_time_to_close_days' => $avgTimeToClose,
            'active_leads' => $leads->whereNotIn('status', ['won', 'lost'])->count(),
            'stage_changes_count' => $stageChanges,
        ];
    }

    /**
     * Prepare data for AI prompt - ENHANCED VERSION
     */
    protected function prepareDataForAi(WeeklyAnalytics $analytics): array
    {
        $summary = $analytics->summary_stats ?? [];
        $channels = $analytics->channel_stats ?? [];
        $operators = $analytics->operator_stats ?? [];
        $timeStats = $analytics->time_stats ?? [];
        $lostReasons = $analytics->lost_reason_stats ?? [];
        $trends = $analytics->trend_stats ?? [];

        // Additional stats (may be stored in model or need to be accessed)
        $regionalStats = $analytics->regional_stats ?? [];
        $qualificationStats = $analytics->qualification_stats ?? [];
        $callStats = $analytics->call_stats ?? [];
        $taskStats = $analytics->task_stats ?? [];
        $pipelineStats = $analytics->pipeline_stats ?? [];

        // Prepare concise data for AI
        return [
            'hafta' => [
                'boshi' => $analytics->week_start->format('Y-m-d'),
                'oxiri' => $analytics->week_end->format('Y-m-d'),
            ],
            'umumiy' => [
                'jami_lidlar' => $summary['total_leads'] ?? 0,
                'yutilgan' => $summary['won'] ?? 0,
                'yoqotilgan' => $summary['lost'] ?? 0,
                'jarayonda' => $summary['in_progress'] ?? 0,
                'konversiya' => ($summary['conversion_rate'] ?? 0).'%',
                'daromad' => $this->formatMoney($summary['total_revenue'] ?? 0),
                'yoqotilgan_summa' => $this->formatMoney($summary['lost_revenue'] ?? 0),
                'pipeline_qiymati' => $this->formatMoney($summary['pipeline_value'] ?? 0),
                'ortacha_deal' => $this->formatMoney($summary['avg_deal_value'] ?? 0),
                'win_loss_ratio' => $summary['win_loss_ratio'] ?? 0,
                'sotuv_sikli_kunlar' => $summary['avg_sales_cycle_days'] ?? '-',
                'issiq_lidlar' => $summary['hot_leads'] ?? 0,
                'tayinlanmagan' => $summary['unassigned_leads'] ?? 0,
                'otgan_hafta_vs' => $summary['vs_last_week'] ?? [],
            ],
            'kanallar' => array_map(fn ($ch) => [
                'nom' => $ch['name'],
                'turi' => $ch['type'],
                'lidlar' => $ch['leads'],
                'yutilgan' => $ch['won'],
                'konversiya' => $ch['conversion'].'%',
                'daromad' => $this->formatMoney($ch['revenue']),
                'ulushi' => ($ch['revenue_share'] ?? 0).'%',
                'roi' => $ch['roi'] !== null ? $ch['roi'].'%' : '-',
                'cpl' => $ch['cpl'] !== null ? $this->formatMoney($ch['cpl']) : '-',
                'ortacha_skor' => $ch['avg_lead_score'] ?? '-',
                'mql' => $ch['mql_count'] ?? 0,
                'sql' => $ch['sql_count'] ?? 0,
            ], array_slice($channels, 0, 5)),
            'operatorlar' => array_map(fn ($op) => [
                'ism' => $op['name'],
                'rank' => $op['rank'] ?? '-',
                'lidlar' => $op['leads'],
                'yutilgan' => $op['won'],
                'yoqotilgan' => $op['lost'],
                'konversiya' => $op['conversion'].'%',
                'daromad' => $this->formatMoney($op['revenue']),
                'ulushi' => ($op['revenue_share'] ?? 0).'%',
                'win_loss' => $op['win_loss_ratio'] ?? 0,
                'issiq_lid_konversiya' => ($op['hot_conversion'] ?? 0).'%',
                'asosiy_yoqotish_sababi' => $op['top_lost_reason']['label'] ?? '-',
                'eng_yaxshi_kanal' => $op['best_channel'] ?? '-',
                'otgan_hafta_vs' => $op['vs_last_week'] ?? [],
            ], array_slice($operators, 0, 5)),
            'vaqt' => [
                'eng_yaxshi_kun' => $timeStats['best_day'] ?? '-',
                'eng_yomon_kun' => $timeStats['worst_day'] ?? '-',
                'eng_yaxshi_soat' => $timeStats['best_hour'] ?? '-',
                'kunlar_boyicha' => $timeStats['by_day'] ?? [],
            ],
            'yoqotish_sabablari' => array_map(fn ($r) => [
                'sabab' => $r['label'],
                'soni' => $r['count'],
                'foiz' => $r['percentage'].'%',
                'summa' => $this->formatMoney($r['value_lost'] ?? 0),
            ], array_slice($lostReasons['reasons'] ?? [], 0, 5)),
            'kvalifikatsiya' => [
                'mql' => $qualificationStats['mql'] ?? 0,
                'sql' => $qualificationStats['sql'] ?? 0,
                'disqualified' => $qualificationStats['disqualified'] ?? 0,
                'mql_to_sql' => ($qualificationStats['mql_to_sql'] ?? 0).'%',
                'sql_to_won' => ($qualificationStats['sql_to_won'] ?? 0).'%',
            ],
            'qongiroqlar' => ! empty($callStats) ? [
                'jami' => $callStats['total_calls'] ?? 0,
                'javob_berilgan' => $callStats['completed'] ?? 0,
                'javob_rate' => ($callStats['answer_rate'] ?? 0).'%',
                'kunlik_ortacha' => $callStats['calls_per_day'] ?? 0,
            ] : null,
            'vazifalar' => ! empty($taskStats) ? [
                'jami' => $taskStats['total_tasks'] ?? 0,
                'bajarilgan' => $taskStats['completed'] ?? 0,
                'bajarilish_rate' => ($taskStats['completion_rate'] ?? 0).'%',
                'muddati_otgan' => $taskStats['overdue'] ?? 0,
            ] : null,
            'regionlar' => ! empty($regionalStats['regions']) ? array_map(fn ($r) => [
                'nom' => $r['label'],
                'lidlar' => $r['leads'],
                'konversiya' => $r['conversion'].'%',
            ], array_slice($regionalStats['regions'], 0, 3)) : null,
            'trendlar' => [
                'haftalar' => $trends['weeks'] ?? [],
                'lidlar' => $trends['leads'] ?? [],
                'konversiya' => $trends['conversion'] ?? [],
                'daromad' => $trends['revenue'] ?? [],
            ],
        ];
    }

    /**
     * Format money for display
     */
    protected function formatMoney($amount): string
    {
        if ($amount >= 1000000000) {
            return round($amount / 1000000000, 1).' mlrd';
        }
        if ($amount >= 1000000) {
            return round($amount / 1000000, 1).' mln';
        }
        if ($amount >= 1000) {
            return round($amount / 1000, 1).'k';
        }

        return number_format($amount, 0, '.', ' ');
    }

    /**
     * Build AI prompt - ENHANCED VERSION
     */
    protected function buildAiPrompt(array $data): string
    {
        $json = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

        return <<<PROMPT
Sen professional biznes analitik AI san. CRM/Sotuv ma'lumotlarini tahlil qilib, amaliy tavsiyalar berasan.

KONTEKST:
- Bu O'zbekistondagi biznes uchun haftalik sotuv hisoboti
- Lidlar = potensial mijozlar, Won = sotilgan, Lost = yo'qotilgan
- Konversiya = yutish foizi, Pipeline = jarayondagi summalar
- MQL = Marketing qualified, SQL = Sales qualified lidlar

HAFTALIK MA'LUMOTLAR:
{$json}

TAHLIL QOIDALARI:
1. Raqamlarni solishtir - o'tgan hafta bilan, o'rtacha qiymatlar bilan
2. Muammolarni aniqlashda: past konversiya (<20%), ko'p yo'qotish, kam faollik
3. Yaxshi natijalarni topishda: o'sish (+), yuqori konversiya, katta deallar
4. Tavsiyalar ANIQ va AMALIY bo'lsin - kim, nima, qachon

JAVOB FORMATI (faqat shu formatda):

## YAXSHI_NATIJALAR
- 🎯 [konkret natija va raqam]
- 📈 [konkret natija va raqam]
- ⭐ [konkret natija va raqam]

## MUAMMOLAR
- ⚠️ [konkret muammo va raqam]
- 📉 [konkret muammo va raqam]
- ❗ [konkret muammo va raqam]

## TAVSIYALAR
1. [KIM]: [NIMA QILISH KERAK] - [KUTILGAN NATIJA]
2. [KIM]: [NIMA QILISH KERAK] - [KUTILGAN NATIJA]
3. [KIM]: [NIMA QILISH KERAK] - [KUTILGAN NATIJA]

## KEYINGI_HAFTA_MAQSAD
[Aniq raqamli maqsad - masalan: "Konversiyani 25% ga oshirish" yoki "50 ta yangi lid olish"]

MUHIM:
- Har bir punkt 1-2 jumla, ortiqcha so'z yo'q
- Raqamlar va foizlar bilan dalilla
- Agar ma'lumot kam bo'lsa, shuni ayt
- O'zbek tilida yoz
PROMPT;
    }

    /**
     * Call Claude Haiku API
     */
    protected function callClaudeHaiku(string $prompt): array
    {
        $apiKey = config('services.anthropic.api_key');

        if (! $apiKey) {
            throw new \Exception('Anthropic API key not configured');
        }

        $response = Http::withHeaders([
            'x-api-key' => $apiKey,
            'anthropic-version' => '2023-06-01',
            'content-type' => 'application/json',
        ])->post('https://api.anthropic.com/v1/messages', [
            'model' => 'claude-haiku-4-5-20251001',
            'max_tokens' => 1024,
            'messages' => [
                ['role' => 'user', 'content' => $prompt],
            ],
        ]);

        if (! $response->successful()) {
            throw new \Exception('Claude API error: '.$response->body());
        }

        $result = $response->json();

        return [
            'content' => $result['content'][0]['text'] ?? '',
            'tokens_used' => ($result['usage']['input_tokens'] ?? 0) + ($result['usage']['output_tokens'] ?? 0),
        ];
    }

    /**
     * Parse AI response into structured data
     */
    protected function parseAiResponse(string $response): array
    {
        $goodResults = [];
        $problems = [];
        $recommendations = [];
        $nextWeekGoal = '';

        // Parse good results
        if (preg_match('/## YAXSHI_NATIJALAR\s*(.*?)(?=## MUAMMOLAR)/s', $response, $matches)) {
            preg_match_all('/- (.+)/m', $matches[1], $items);
            $goodResults = $items[1] ?? [];
        }

        // Parse problems
        if (preg_match('/## MUAMMOLAR\s*(.*?)(?=## TAVSIYALAR)/s', $response, $matches)) {
            preg_match_all('/- (.+)/m', $matches[1], $items);
            $problems = $items[1] ?? [];
        }

        // Parse recommendations
        if (preg_match('/## TAVSIYALAR\s*(.*?)(?=## KEYINGI_HAFTA_MAQSAD)/s', $response, $matches)) {
            preg_match_all('/\d+\.\s*(.+)/m', $matches[1], $items);
            $recommendations = $items[1] ?? [];
        }

        // Parse next week goal
        if (preg_match('/## KEYINGI_HAFTA_MAQSAD\s*(.+)/s', $response, $matches)) {
            $nextWeekGoal = trim($matches[1]);
        }

        return [
            'good_results' => array_slice($goodResults, 0, 5),
            'problems' => array_slice($problems, 0, 5),
            'recommendations' => array_slice($recommendations, 0, 5),
            'next_week_goal' => $nextWeekGoal,
        ];
    }

    /**
     * Calculate percent change
     */
    protected function calcPercentChange($old, $new): string
    {
        if ($old == 0) {
            return $new > 0 ? '+100%' : '0%';
        }

        $change = round((($new - $old) / $old) * 100, 1);

        return ($change >= 0 ? '+' : '').$change.'%';
    }
}
