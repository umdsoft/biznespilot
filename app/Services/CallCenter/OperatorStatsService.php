<?php

namespace App\Services\CallCenter;

use App\Models\CallAnalysis;
use App\Models\CallLog;
use App\Models\OperatorCallStats;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class OperatorStatsService
{
    /**
     * Update stats for an operator after a new analysis
     */
    public function updateStatsAfterAnalysis(CallLog $callLog): void
    {
        if (!$callLog->user_id) {
            Log::warning('Cannot update operator stats: no user_id on call', [
                'call_log_id' => $callLog->id,
            ]);
            return;
        }

        $date = Carbon::parse($callLog->started_at ?? $callLog->created_at);

        // Update daily stats
        $this->updatePeriodStats(
            $callLog->business_id,
            $callLog->user_id,
            OperatorCallStats::PERIOD_DAILY,
            $date->startOfDay()->toDateString()
        );

        // Update weekly stats
        $this->updatePeriodStats(
            $callLog->business_id,
            $callLog->user_id,
            OperatorCallStats::PERIOD_WEEKLY,
            $date->startOfWeek()->toDateString()
        );

        // Update monthly stats
        $this->updatePeriodStats(
            $callLog->business_id,
            $callLog->user_id,
            OperatorCallStats::PERIOD_MONTHLY,
            $date->startOfMonth()->toDateString()
        );
    }

    /**
     * Update stats for a specific period
     */
    public function updatePeriodStats(
        string $businessId,
        string $userId,
        string $periodType,
        string $periodDate
    ): OperatorCallStats {
        $startDate = Carbon::parse($periodDate);
        $endDate = match ($periodType) {
            OperatorCallStats::PERIOD_DAILY => $startDate->copy()->endOfDay(),
            OperatorCallStats::PERIOD_WEEKLY => $startDate->copy()->endOfWeek(),
            OperatorCallStats::PERIOD_MONTHLY => $startDate->copy()->endOfMonth(),
        };

        // Get all calls for this operator in this period
        $calls = CallLog::where('business_id', $businessId)
            ->where('user_id', $userId)
            ->whereBetween('started_at', [$startDate, $endDate])
            ->get();

        // Get analyzed calls with their analyses
        $analyzedCalls = $calls->where('analysis_status', 'completed')
            ->load('analysis');

        // Calculate statistics
        $totalCalls = $calls->count();
        $analyzedCount = $analyzedCalls->count();
        $successfulCalls = $calls->where('status', 'answered')->count();
        $missedCalls = $calls->whereIn('status', ['missed', 'no_answer', 'busy'])->count();

        // Score statistics
        $scores = $analyzedCalls->pluck('analysis.overall_score')->filter()->values();
        $avgScore = $scores->avg();
        $minScore = $scores->min();
        $maxScore = $scores->max();

        // Stage scores average
        $avgStageScores = $this->calculateAvgStageScores($analyzedCalls);

        // Anti-patterns
        $antiPatternCounts = $this->calculateAntiPatternCounts($analyzedCalls);
        $totalAntiPatterns = array_sum($antiPatternCounts);

        // Duration
        $totalDuration = $calls->sum('duration');
        $avgDuration = $totalCalls > 0 ? (int) ($totalDuration / $totalCalls) : 0;

        // Cost
        $totalCost = $analyzedCalls->sum(fn($call) => $call->analysis?->total_cost ?? 0);

        // Calculate change from previous period
        $previousStats = $this->getPreviousPeriodStats($businessId, $userId, $periodType, $startDate);
        $scoreChange = null;
        $scoreChangePercent = null;

        if ($previousStats && $previousStats->avg_score !== null && $avgScore !== null) {
            $scoreChange = $avgScore - $previousStats->avg_score;
            $scoreChangePercent = $previousStats->avg_score > 0
                ? (($avgScore - $previousStats->avg_score) / $previousStats->avg_score) * 100
                : null;
        }

        // Update or create stats record
        return OperatorCallStats::updateOrCreate(
            [
                'business_id' => $businessId,
                'user_id' => $userId,
                'period_type' => $periodType,
                'period_date' => $periodDate,
            ],
            [
                'total_calls' => $totalCalls,
                'analyzed_calls' => $analyzedCount,
                'successful_calls' => $successfulCalls,
                'missed_calls' => $missedCalls,
                'avg_score' => $avgScore,
                'min_score' => $minScore,
                'max_score' => $maxScore,
                'avg_stage_scores' => $avgStageScores,
                'total_anti_patterns' => $totalAntiPatterns,
                'anti_pattern_counts' => $antiPatternCounts,
                'total_duration_seconds' => $totalDuration,
                'avg_duration_seconds' => $avgDuration,
                'total_analysis_cost' => $totalCost,
                'score_change' => $scoreChange,
                'score_change_percent' => $scoreChangePercent,
            ]
        );
    }

    /**
     * Calculate average stage scores across all analyzed calls
     */
    protected function calculateAvgStageScores($analyzedCalls): array
    {
        $stageKeys = ['greeting', 'discovery', 'presentation', 'objection_handling', 'closing', 'rapport', 'cta'];
        $stageTotals = array_fill_keys($stageKeys, ['sum' => 0, 'count' => 0]);

        foreach ($analyzedCalls as $call) {
            $stageScores = $call->analysis?->stage_scores ?? [];
            foreach ($stageKeys as $key) {
                if (isset($stageScores[$key]) && is_numeric($stageScores[$key])) {
                    $stageTotals[$key]['sum'] += $stageScores[$key];
                    $stageTotals[$key]['count']++;
                }
            }
        }

        $avgStageScores = [];
        foreach ($stageKeys as $key) {
            $avgStageScores[$key] = $stageTotals[$key]['count'] > 0
                ? round($stageTotals[$key]['sum'] / $stageTotals[$key]['count'], 1)
                : null;
        }

        return $avgStageScores;
    }

    /**
     * Calculate anti-pattern counts across all analyzed calls
     */
    protected function calculateAntiPatternCounts($analyzedCalls): array
    {
        $counts = [];

        foreach ($analyzedCalls as $call) {
            $patterns = $call->analysis?->anti_patterns ?? [];
            foreach ($patterns as $pattern) {
                $type = $pattern['type'] ?? 'unknown';
                $counts[$type] = ($counts[$type] ?? 0) + 1;
            }
        }

        return $counts;
    }

    /**
     * Get previous period stats for comparison
     */
    protected function getPreviousPeriodStats(
        string $businessId,
        string $userId,
        string $periodType,
        Carbon $currentPeriodStart
    ): ?OperatorCallStats {
        $previousDate = match ($periodType) {
            OperatorCallStats::PERIOD_DAILY => $currentPeriodStart->copy()->subDay(),
            OperatorCallStats::PERIOD_WEEKLY => $currentPeriodStart->copy()->subWeek(),
            OperatorCallStats::PERIOD_MONTHLY => $currentPeriodStart->copy()->subMonth(),
        };

        return OperatorCallStats::where('business_id', $businessId)
            ->where('user_id', $userId)
            ->where('period_type', $periodType)
            ->where('period_date', $previousDate->toDateString())
            ->first();
    }

    /**
     * Get operator history (all analyses for an operator)
     */
    public function getOperatorHistory(
        string $businessId,
        string $userId,
        int $limit = 50,
        ?string $startDate = null,
        ?string $endDate = null
    ): array {
        $query = CallLog::where('business_id', $businessId)
            ->where('user_id', $userId)
            ->where('analysis_status', 'completed')
            ->with(['analysis', 'lead:id,name,phone'])
            ->orderBy('started_at', 'desc');

        if ($startDate) {
            $query->where('started_at', '>=', $startDate);
        }

        if ($endDate) {
            $query->where('started_at', '<=', $endDate);
        }

        $calls = $query->limit($limit)->get();

        return $calls->map(function ($call) {
            return [
                'id' => $call->id,
                'date' => $call->started_at?->format('d.m.Y H:i'),
                'duration' => $this->formatDuration($call->duration),
                'direction' => $call->direction,
                'lead' => $call->lead ? [
                    'id' => $call->lead->id,
                    'name' => $call->lead->name,
                    'phone' => $call->lead->phone,
                ] : null,
                'analysis' => $call->analysis ? [
                    'overall_score' => $call->analysis->overall_score,
                    'score_label' => $call->analysis->score_label,
                    'score_color' => $call->analysis->score_color,
                    'stage_scores' => $call->analysis->stage_scores,
                    'transcript' => $call->analysis->transcript,
                    'formatted_transcript' => $call->analysis->formatted_transcript,
                    'anti_patterns' => $call->analysis->anti_patterns,
                    'recommendations' => $call->analysis->recommendations,
                    'strengths' => $call->analysis->strengths,
                    'weaknesses' => $call->analysis->weaknesses,
                ] : null,
            ];
        })->toArray();
    }

    /**
     * Get operator summary stats
     */
    public function getOperatorSummary(string $businessId, string $userId): array
    {
        $user = User::find($userId);

        // Get all-time stats
        $allTimeStats = CallLog::where('business_id', $businessId)
            ->where('user_id', $userId)
            ->where('analysis_status', 'completed')
            ->join('call_analyses', 'call_logs.id', '=', 'call_analyses.call_log_id')
            ->selectRaw('
                COUNT(*) as total_analyzed,
                AVG(call_analyses.overall_score) as avg_score,
                MIN(call_analyses.overall_score) as min_score,
                MAX(call_analyses.overall_score) as max_score,
                SUM(call_logs.duration) as total_duration,
                SUM(call_analyses.stt_cost + call_analyses.analysis_cost) as total_cost
            ')
            ->first();

        // Get this month stats
        $thisMonthStats = OperatorCallStats::where('business_id', $businessId)
            ->where('user_id', $userId)
            ->where('period_type', OperatorCallStats::PERIOD_MONTHLY)
            ->where('period_date', Carbon::now()->startOfMonth()->toDateString())
            ->first();

        // Get this week stats
        $thisWeekStats = OperatorCallStats::where('business_id', $businessId)
            ->where('user_id', $userId)
            ->where('period_type', OperatorCallStats::PERIOD_WEEKLY)
            ->where('period_date', Carbon::now()->startOfWeek()->toDateString())
            ->first();

        // Get today's stats
        $todayStats = OperatorCallStats::where('business_id', $businessId)
            ->where('user_id', $userId)
            ->where('period_type', OperatorCallStats::PERIOD_DAILY)
            ->where('period_date', Carbon::now()->toDateString())
            ->first();

        return [
            'operator' => [
                'id' => $userId,
                'name' => $user?->name ?? 'Noma\'lum',
                'email' => $user?->email,
                'avatar' => $user?->profile_photo_url ?? null,
            ],
            'all_time' => [
                'total_analyzed' => (int) ($allTimeStats->total_analyzed ?? 0),
                'avg_score' => $allTimeStats->avg_score ? round($allTimeStats->avg_score, 1) : null,
                'min_score' => $allTimeStats->min_score ? round($allTimeStats->min_score, 1) : null,
                'max_score' => $allTimeStats->max_score ? round($allTimeStats->max_score, 1) : null,
                'total_duration' => $this->formatDuration($allTimeStats->total_duration ?? 0),
                'total_cost' => $this->formatCost($allTimeStats->total_cost ?? 0),
            ],
            'this_month' => $thisMonthStats ? [
                'total_calls' => $thisMonthStats->total_calls,
                'analyzed_calls' => $thisMonthStats->analyzed_calls,
                'avg_score' => $thisMonthStats->avg_score,
                'score_change' => $thisMonthStats->score_change,
                'score_label' => $thisMonthStats->score_label,
                'score_color' => $thisMonthStats->score_color,
            ] : null,
            'this_week' => $thisWeekStats ? [
                'total_calls' => $thisWeekStats->total_calls,
                'analyzed_calls' => $thisWeekStats->analyzed_calls,
                'avg_score' => $thisWeekStats->avg_score,
                'score_change' => $thisWeekStats->score_change,
            ] : null,
            'today' => $todayStats ? [
                'total_calls' => $todayStats->total_calls,
                'analyzed_calls' => $todayStats->analyzed_calls,
                'avg_score' => $todayStats->avg_score,
            ] : null,
        ];
    }

    /**
     * Get leaderboard (top operators by score)
     */
    public function getLeaderboard(
        string $businessId,
        string $periodType = 'monthly',
        ?string $periodDate = null,
        int $limit = 10
    ): array {
        $date = $periodDate ?? match ($periodType) {
            'daily' => Carbon::now()->toDateString(),
            'weekly' => Carbon::now()->startOfWeek()->toDateString(),
            'monthly' => Carbon::now()->startOfMonth()->toDateString(),
        };

        $stats = OperatorCallStats::where('business_id', $businessId)
            ->where('period_type', $periodType)
            ->where('period_date', $date)
            ->where('analyzed_calls', '>', 0)
            ->with('user:id,name,email')
            ->orderByDesc('avg_score')
            ->limit($limit)
            ->get();

        return $stats->map(function ($stat, $index) {
            return [
                'rank' => $index + 1,
                'operator' => [
                    'id' => $stat->user_id,
                    'name' => $stat->user?->name ?? 'Noma\'lum',
                ],
                'avg_score' => $stat->avg_score,
                'score_label' => $stat->score_label,
                'score_color' => $stat->score_color,
                'total_calls' => $stat->total_calls,
                'analyzed_calls' => $stat->analyzed_calls,
                'score_change' => $stat->score_change,
                'score_trend' => $stat->score_trend,
            ];
        })->toArray();
    }

    /**
     * Format duration to human readable
     */
    protected function formatDuration(int $seconds): string
    {
        if ($seconds < 60) {
            return $seconds . ' sek';
        }

        $minutes = floor($seconds / 60);
        $secs = $seconds % 60;

        if ($minutes < 60) {
            return sprintf('%d:%02d', $minutes, $secs);
        }

        $hours = floor($minutes / 60);
        $mins = $minutes % 60;

        return sprintf('%d:%02d:%02d', $hours, $mins, $secs);
    }

    /**
     * Format cost in UZS
     */
    protected function formatCost(float $usd): string
    {
        $uzsRate = config('call-center.currency.usd_to_uzs', 12800);
        return number_format($usd * $uzsRate, 0, '.', ' ') . ' so\'m';
    }
}
