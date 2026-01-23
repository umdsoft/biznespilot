<?php

namespace App\Http\Controllers\Api\CallCenter;

use App\Http\Controllers\Controller;
use App\Models\OperatorCallStats;
use App\Models\User;
use App\Services\CallCenter\OperatorStatsService;
use App\Http\Controllers\Traits\HasCurrentBusiness;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OperatorStatsController extends Controller
{
    use HasCurrentBusiness;

    public function __construct(
        protected OperatorStatsService $statsService
    ) {}

    /**
     * Get all operators with their stats
     * GET /api/v1/call-center/operators
     */
    public function index(Request $request): JsonResponse
    {
        $business = $this->getCurrentBusiness();
        $periodType = $request->input('period', 'monthly');
        $periodDate = $request->input('date');

        // Get all operators (users) with their stats
        $operators = User::where('business_id', $business->id)
            ->whereHas('callLogs', function ($q) {
                $q->where('analysis_status', 'completed');
            })
            ->with(['operatorStats' => function ($q) use ($periodType, $periodDate) {
                $q->where('period_type', $periodType);
                if ($periodDate) {
                    $q->where('period_date', $periodDate);
                } else {
                    $q->orderByDesc('period_date')->limit(1);
                }
            }])
            ->get();

        return response()->json([
            'success' => true,
            'data' => $operators->map(function ($user) {
                $stats = $user->operatorStats->first();
                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'stats' => $stats ? [
                        'period_date' => $stats->period_date->format('Y-m-d'),
                        'total_calls' => $stats->total_calls,
                        'analyzed_calls' => $stats->analyzed_calls,
                        'avg_score' => $stats->avg_score,
                        'score_label' => $stats->score_label,
                        'score_color' => $stats->score_color,
                        'score_change' => $stats->score_change,
                        'score_trend' => $stats->score_trend,
                    ] : null,
                ];
            }),
        ]);
    }

    /**
     * Get single operator summary
     * GET /api/v1/call-center/operators/{userId}
     */
    public function show(string $userId): JsonResponse
    {
        $business = $this->getCurrentBusiness();

        $summary = $this->statsService->getOperatorSummary($business->id, $userId);

        return response()->json([
            'success' => true,
            'data' => $summary,
        ]);
    }

    /**
     * Get operator call history with analyses
     * GET /api/v1/call-center/operators/{userId}/history
     */
    public function history(Request $request, string $userId): JsonResponse
    {
        $business = $this->getCurrentBusiness();

        $history = $this->statsService->getOperatorHistory(
            $business->id,
            $userId,
            $request->input('limit', 50),
            $request->input('start_date'),
            $request->input('end_date')
        );

        return response()->json([
            'success' => true,
            'data' => $history,
        ]);
    }

    /**
     * Get operator stats by period
     * GET /api/v1/call-center/operators/{userId}/stats
     */
    public function stats(Request $request, string $userId): JsonResponse
    {
        $business = $this->getCurrentBusiness();
        $periodType = $request->input('period', 'daily');
        $limit = $request->input('limit', 30);

        $stats = OperatorCallStats::where('business_id', $business->id)
            ->where('user_id', $userId)
            ->where('period_type', $periodType)
            ->orderByDesc('period_date')
            ->limit($limit)
            ->get();

        return response()->json([
            'success' => true,
            'data' => $stats->map(fn($s) => [
                'date' => $s->period_date->format('Y-m-d'),
                'total_calls' => $s->total_calls,
                'analyzed_calls' => $s->analyzed_calls,
                'avg_score' => $s->avg_score,
                'min_score' => $s->min_score,
                'max_score' => $s->max_score,
                'avg_stage_scores' => $s->avg_stage_scores,
                'total_anti_patterns' => $s->total_anti_patterns,
                'anti_pattern_counts' => $s->anti_pattern_counts,
                'total_duration' => $s->formatted_total_duration,
                'avg_duration' => $s->formatted_avg_duration,
                'score_change' => $s->score_change,
                'score_trend' => $s->score_trend,
            ]),
        ]);
    }

    /**
     * Get leaderboard
     * GET /api/v1/call-center/leaderboard
     */
    public function leaderboard(Request $request): JsonResponse
    {
        $business = $this->getCurrentBusiness();

        $leaderboard = $this->statsService->getLeaderboard(
            $business->id,
            $request->input('period', 'monthly'),
            $request->input('date'),
            $request->input('limit', 10)
        );

        return response()->json([
            'success' => true,
            'data' => $leaderboard,
        ]);
    }

    /**
     * Recalculate stats for an operator
     * POST /api/v1/call-center/operators/{userId}/recalculate
     */
    public function recalculate(Request $request, string $userId): JsonResponse
    {
        $business = $this->getCurrentBusiness();
        $periodType = $request->input('period', 'monthly');

        // Get date range to recalculate
        $startDate = $request->input('start_date', now()->startOfMonth()->toDateString());
        $endDate = $request->input('end_date', now()->toDateString());

        // Recalculate for each day in range (for daily stats)
        $current = \Carbon\Carbon::parse($startDate);
        $end = \Carbon\Carbon::parse($endDate);
        $updated = 0;

        while ($current <= $end) {
            $this->statsService->updatePeriodStats(
                $business->id,
                $userId,
                $periodType,
                $current->toDateString()
            );
            $updated++;

            $current = match ($periodType) {
                'daily' => $current->addDay(),
                'weekly' => $current->addWeek(),
                'monthly' => $current->addMonth(),
            };
        }

        return response()->json([
            'success' => true,
            'message' => "Statistika qayta hisoblandi: {$updated} ta davr yangilandi",
        ]);
    }

    /**
     * Get overall call center stats
     * GET /api/v1/call-center/overview
     */
    public function overview(Request $request): JsonResponse
    {
        $business = $this->getCurrentBusiness();
        $periodType = $request->input('period', 'monthly');
        $periodDate = $request->input('date') ?? match ($periodType) {
            'daily' => now()->toDateString(),
            'weekly' => now()->startOfWeek()->toDateString(),
            'monthly' => now()->startOfMonth()->toDateString(),
        };

        // Aggregate stats across all operators
        $stats = OperatorCallStats::where('business_id', $business->id)
            ->where('period_type', $periodType)
            ->where('period_date', $periodDate)
            ->selectRaw('
                SUM(total_calls) as total_calls,
                SUM(analyzed_calls) as analyzed_calls,
                SUM(successful_calls) as successful_calls,
                SUM(missed_calls) as missed_calls,
                AVG(avg_score) as avg_score,
                SUM(total_duration_seconds) as total_duration,
                SUM(total_analysis_cost) as total_cost,
                COUNT(DISTINCT user_id) as operator_count
            ')
            ->first();

        // Get top performers
        $topPerformers = $this->statsService->getLeaderboard($business->id, $periodType, $periodDate, 5);

        // Get common issues
        $issues = OperatorCallStats::where('business_id', $business->id)
            ->where('period_type', $periodType)
            ->where('period_date', $periodDate)
            ->get()
            ->pluck('anti_pattern_counts')
            ->filter()
            ->reduce(function ($carry, $counts) {
                foreach ($counts as $type => $count) {
                    $carry[$type] = ($carry[$type] ?? 0) + $count;
                }
                return $carry;
            }, []);

        arsort($issues);

        return response()->json([
            'success' => true,
            'data' => [
                'period' => [
                    'type' => $periodType,
                    'date' => $periodDate,
                ],
                'summary' => [
                    'total_calls' => (int) ($stats->total_calls ?? 0),
                    'analyzed_calls' => (int) ($stats->analyzed_calls ?? 0),
                    'successful_calls' => (int) ($stats->successful_calls ?? 0),
                    'missed_calls' => (int) ($stats->missed_calls ?? 0),
                    'avg_score' => $stats->avg_score ? round($stats->avg_score, 1) : null,
                    'operator_count' => (int) ($stats->operator_count ?? 0),
                ],
                'top_performers' => $topPerformers,
                'common_issues' => array_slice($issues, 0, 5, true),
            ],
        ]);
    }
}
