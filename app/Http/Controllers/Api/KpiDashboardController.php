<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Business;
use App\Models\KpiDailyActual;
use App\Models\KpiMonthlySummary;
use App\Models\KpiWeeklySummary;
use App\Services\KpiAggregationService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class KpiDashboardController extends Controller
{
    protected $aggregationService;

    public function __construct(KpiAggregationService $aggregationService)
    {
        $this->aggregationService = $aggregationService;
    }

    /**
     * Get dashboard overview
     */
    public function getOverview(Request $request, int $businessId): JsonResponse
    {
        $business = Business::find($businessId);
        if (! $business || ! $business->kpiConfiguration) {
            return response()->json([
                'success' => false,
                'message' => 'Business or KPI configuration not found',
            ], 404);
        }

        $configuration = $business->kpiConfiguration;
        $kpiCodes = $configuration->selected_kpis ?? [];

        $today = Carbon::today();
        $weekStart = $today->copy()->startOfWeek();
        $monthStart = $today->copy()->startOfMonth();

        // Get today's data
        $todayData = KpiDailyActual::where('business_id', $businessId)
            ->whereIn('kpi_code', $kpiCodes)
            ->whereDate('date', $today)
            ->get()
            ->groupBy('kpi_code');

        // Get current week summaries
        $weekSummaries = KpiWeeklySummary::where('business_id', $businessId)
            ->whereIn('kpi_code', $kpiCodes)
            ->where('week_start_date', $weekStart)
            ->get()
            ->keyBy('kpi_code');

        // Get current month summaries
        $monthSummaries = KpiMonthlySummary::where('business_id', $businessId)
            ->whereIn('kpi_code', $kpiCodes)
            ->where('year', $today->year)
            ->where('month', $today->month)
            ->get()
            ->keyBy('kpi_code');

        // Calculate overall performance
        $overallPerformance = $this->calculateOverallPerformance($weekSummaries, $configuration);

        // Get critical KPIs status
        $criticalKpis = $this->getCriticalKpisStatus($businessId, $kpiCodes, $configuration);

        // Get recent anomalies
        $recentAnomalies = KpiDailyActual::where('business_id', $businessId)
            ->whereIn('kpi_code', $kpiCodes)
            ->where('is_anomaly', true)
            ->whereBetween('date', [$today->copy()->subDays(7), $today])
            ->with('kpiTemplate')
            ->orderBy('date', 'desc')
            ->take(5)
            ->get();

        return response()->json([
            'success' => true,
            'data' => [
                'overall_performance' => $overallPerformance,
                'critical_kpis' => $criticalKpis,
                'today_snapshot' => [
                    'date' => $today->format('Y-m-d'),
                    'kpis_logged' => $todayData->count(),
                    'total_kpis' => count($kpiCodes),
                    'completion_percentage' => round(($todayData->count() / max(count($kpiCodes), 1)) * 100, 2),
                ],
                'current_week' => [
                    'week_start' => $weekStart->format('Y-m-d'),
                    'week_label' => "Week {$weekStart->weekOfYear}, {$weekStart->year}",
                    'summaries_count' => $weekSummaries->count(),
                ],
                'current_month' => [
                    'month' => $monthStart->format('F Y'),
                    'summaries_count' => $monthSummaries->count(),
                ],
                'recent_anomalies' => $recentAnomalies,
                'configuration_summary' => $configuration->getSummary(),
            ],
        ]);
    }

    /**
     * Get weekly dashboard data
     */
    public function getWeeklyDashboard(Request $request, int $businessId): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'week_start_date' => 'nullable|date',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $business = Business::find($businessId);
        if (! $business || ! $business->kpiConfiguration) {
            return response()->json([
                'success' => false,
                'message' => 'Business or KPI configuration not found',
            ], 404);
        }

        $weekStart = $request->has('week_start_date')
            ? Carbon::parse($request->input('week_start_date'))->startOfWeek()
            : Carbon::now()->startOfWeek();

        $weekEnd = $weekStart->copy()->addDays(6);

        $kpiCodes = $business->kpiConfiguration->selected_kpis ?? [];

        // Get weekly summaries
        $weeklySummaries = KpiWeeklySummary::where('business_id', $businessId)
            ->whereIn('kpi_code', $kpiCodes)
            ->where('week_start_date', $weekStart)
            ->with('kpiTemplate')
            ->get();

        // Get daily breakdown
        $dailyActuals = KpiDailyActual::where('business_id', $businessId)
            ->whereIn('kpi_code', $kpiCodes)
            ->whereBetween('date', [$weekStart, $weekEnd])
            ->with('kpiTemplate')
            ->orderBy('date')
            ->get()
            ->groupBy('kpi_code');

        $kpisData = $weeklySummaries->map(function ($summary) use ($dailyActuals) {
            $kpiCode = $summary->kpi_code;
            $dailyData = $dailyActuals->get($kpiCode, collect());

            return [
                'kpi_code' => $kpiCode,
                'kpi_name' => $summary->kpiTemplate->kpi_name ?? $kpiCode,
                'kpi_name_uz' => $summary->kpiTemplate->kpi_name_uz ?? $kpiCode,
                'category' => $summary->kpiTemplate->category ?? null,
                'icon' => $summary->kpiTemplate->icon ?? null,
                'weekly_summary' => $summary->toSummaryArray(),
                'daily_breakdown' => $dailyData->map->toSummaryArray(),
                'trend_analysis' => [
                    'trend' => $summary->trend,
                    'trend_percentage' => $summary->trend_percentage,
                    'vs_previous_week' => $summary->vs_previous_week_change,
                    'vs_previous_week_status' => $summary->vs_previous_week_status,
                ],
                'statistics' => [
                    'daily_average' => $summary->daily_average,
                    'daily_min' => $summary->daily_min,
                    'daily_max' => $summary->daily_max,
                    'daily_std_deviation' => $summary->daily_std_deviation,
                    'best_day' => [
                        'date' => $summary->best_day_date?->format('Y-m-d'),
                        'value' => $summary->best_day_value,
                    ],
                    'worst_day' => [
                        'date' => $summary->worst_day_date?->format('Y-m-d'),
                        'value' => $summary->worst_day_value,
                    ],
                ],
            ];
        });

        return response()->json([
            'success' => true,
            'data' => [
                'week_start' => $weekStart->format('Y-m-d'),
                'week_end' => $weekEnd->format('Y-m-d'),
                'week_label' => "Week {$weekStart->weekOfYear}, {$weekStart->year}",
                'kpis' => $kpisData,
                'summary' => [
                    'total_kpis' => count($kpiCodes),
                    'tracked_kpis' => $weeklySummaries->count(),
                    'green_kpis' => $weeklySummaries->where('status', 'green')->count(),
                    'yellow_kpis' => $weeklySummaries->where('status', 'yellow')->count(),
                    'red_kpis' => $weeklySummaries->where('status', 'red')->count(),
                    'average_achievement' => round($weeklySummaries->avg('achievement_percentage'), 2),
                ],
            ],
        ]);
    }

    /**
     * Get monthly dashboard data
     */
    public function getMonthlyDashboard(Request $request, int $businessId): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'year' => 'nullable|integer|min:2020|max:2030',
            'month' => 'nullable|integer|min:1|max:12',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $business = Business::find($businessId);
        if (! $business || ! $business->kpiConfiguration) {
            return response()->json([
                'success' => false,
                'message' => 'Business or KPI configuration not found',
            ], 404);
        }

        $year = $request->input('year', Carbon::now()->year);
        $month = $request->input('month', Carbon::now()->month);
        $monthStart = Carbon::create($year, $month, 1);

        $kpiCodes = $business->kpiConfiguration->selected_kpis ?? [];

        // Get monthly summaries
        $monthlySummaries = KpiMonthlySummary::where('business_id', $businessId)
            ->whereIn('kpi_code', $kpiCodes)
            ->where('year', $year)
            ->where('month', $month)
            ->with('kpiTemplate')
            ->get();

        // Get weekly breakdown
        $weeklySummaries = KpiWeeklySummary::where('business_id', $businessId)
            ->whereIn('kpi_code', $kpiCodes)
            ->where(function ($query) use ($monthStart) {
                $query->whereBetween('week_start_date', [$monthStart, $monthStart->copy()->endOfMonth()])
                    ->orWhereBetween('week_end_date', [$monthStart, $monthStart->copy()->endOfMonth()]);
            })
            ->orderBy('week_start_date')
            ->get()
            ->groupBy('kpi_code');

        $kpisData = $monthlySummaries->map(function ($summary) use ($weeklySummaries) {
            $kpiCode = $summary->kpi_code;
            $weeklyData = $weeklySummaries->get($kpiCode, collect());

            return [
                'kpi_code' => $kpiCode,
                'kpi_name' => $summary->kpiTemplate->kpi_name ?? $kpiCode,
                'kpi_name_uz' => $summary->kpiTemplate->kpi_name_uz ?? $kpiCode,
                'category' => $summary->kpiTemplate->category ?? null,
                'icon' => $summary->kpiTemplate->icon ?? null,
                'monthly_summary' => $summary->toSummaryArray(),
                'weekly_breakdown' => $weeklyData->map->toSummaryArray(),
                'trend_analysis' => [
                    'trend' => $summary->trend,
                    'trend_percentage' => $summary->trend_percentage,
                    'vs_previous_month' => $summary->vs_previous_month_change,
                    'vs_same_month_last_year' => $summary->vs_same_month_last_year_change,
                ],
                'insights' => $summary->insights,
                'recommendations' => $summary->recommendations,
            ];
        });

        return response()->json([
            'success' => true,
            'data' => [
                'year' => $year,
                'month' => $month,
                'month_label' => $monthStart->format('F Y'),
                'kpis' => $kpisData,
                'summary' => [
                    'total_kpis' => count($kpiCodes),
                    'tracked_kpis' => $monthlySummaries->count(),
                    'green_kpis' => $monthlySummaries->where('status', 'green')->count(),
                    'yellow_kpis' => $monthlySummaries->where('status', 'yellow')->count(),
                    'red_kpis' => $monthlySummaries->where('status', 'red')->count(),
                    'average_achievement' => round($monthlySummaries->avg('achievement_percentage'), 2),
                ],
            ],
        ]);
    }

    /**
     * Get trend analysis for a KPI
     */
    public function getTrendAnalysis(Request $request, int $businessId, string $kpiCode): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'weeks' => 'nullable|integer|min:4|max:52',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $weeks = $request->input('weeks', 12);

        $analysis = $this->aggregationService->getTrendAnalysis($businessId, $kpiCode, $weeks);

        return response()->json($analysis);
    }

    /**
     * Get performance comparison across KPIs
     */
    public function getPerformanceComparison(Request $request, int $businessId): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'period' => 'nullable|string|in:week,month',
            'date' => 'nullable|date',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $business = Business::find($businessId);
        if (! $business || ! $business->kpiConfiguration) {
            return response()->json([
                'success' => false,
                'message' => 'Business or KPI configuration not found',
            ], 404);
        }

        $period = $request->input('period', 'week');
        $date = $request->has('date') ? Carbon::parse($request->input('date')) : Carbon::now();

        $kpiCodes = $business->kpiConfiguration->selected_kpis ?? [];
        $priorities = $business->kpiConfiguration->kpi_priorities ?? [];

        if ($period === 'week') {
            $weekStart = $date->copy()->startOfWeek();
            $summaries = KpiWeeklySummary::where('business_id', $businessId)
                ->whereIn('kpi_code', $kpiCodes)
                ->where('week_start_date', $weekStart)
                ->with('kpiTemplate')
                ->get();
        } else {
            $summaries = KpiMonthlySummary::where('business_id', $businessId)
                ->whereIn('kpi_code', $kpiCodes)
                ->where('year', $date->year)
                ->where('month', $date->month)
                ->with('kpiTemplate')
                ->get();
        }

        $comparison = $summaries->map(function ($summary) use ($priorities) {
            $kpiCode = $summary->kpi_code;
            $priority = $priorities[$kpiCode] ?? 'medium';

            return [
                'kpi_code' => $kpiCode,
                'kpi_name' => $summary->kpiTemplate->kpi_name ?? $kpiCode,
                'kpi_name_uz' => $summary->kpiTemplate->kpi_name_uz ?? $kpiCode,
                'priority' => $priority,
                'category' => $summary->kpiTemplate->category ?? null,
                'achievement_percentage' => $summary->achievement_percentage,
                'status' => $summary->status,
                'trend' => $summary->trend,
                'actual_value' => $summary->actual_value,
                'planned_value' => $summary->planned_value,
                'variance_percentage' => $summary->variance_percentage,
            ];
        })->sortByDesc(function ($item) {
            // Sort by priority then achievement
            $priorityScore = match ($item['priority']) {
                'critical' => 4,
                'high' => 3,
                'medium' => 2,
                'low' => 1,
                default => 0,
            };

            return ($priorityScore * 1000) + $item['achievement_percentage'];
        })->values();

        return response()->json([
            'success' => true,
            'data' => [
                'period' => $period,
                'date' => $date->format('Y-m-d'),
                'comparison' => $comparison,
                'summary' => [
                    'total_kpis' => $comparison->count(),
                    'on_target' => $comparison->where('status', 'green')->count(),
                    'needs_attention' => $comparison->where('status', 'yellow')->count(),
                    'critical_issues' => $comparison->where('status', 'red')->count(),
                    'average_achievement' => round($comparison->avg('achievement_percentage'), 2),
                ],
            ],
        ]);
    }

    /**
     * Get aggregation status
     */
    public function getAggregationStatus(Request $request, int $businessId): JsonResponse
    {
        $date = $request->has('date') ? Carbon::parse($request->input('date')) : Carbon::now();

        $status = $this->aggregationService->getAggregationStatus($businessId, $date);

        return response()->json([
            'success' => true,
            'data' => $status,
        ]);
    }

    /**
     * Trigger manual aggregation
     */
    public function triggerAggregation(Request $request, int $businessId): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'type' => 'required|string|in:weekly,monthly,all',
            'date' => 'nullable|date',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $type = $request->input('type');
        $date = $request->has('date') ? Carbon::parse($request->input('date')) : Carbon::now();

        if ($type === 'weekly' || $type === 'all') {
            $weekStart = $date->copy()->startOfWeek();
            \App\Jobs\AggregateWeeklyKpisJob::dispatch($businessId, $weekStart->format('Y-m-d'))->onQueue('kpi-aggregation');
        }

        if ($type === 'monthly' || $type === 'all') {
            \App\Jobs\AggregateMonthlyKpisJob::dispatch($businessId, $date->year, $date->month)->onQueue('kpi-aggregation');
        }

        return response()->json([
            'success' => true,
            'message' => 'Aggregation jobs dispatched successfully',
            'data' => [
                'type' => $type,
                'date' => $date->format('Y-m-d'),
            ],
        ]);
    }

    /**
     * Calculate overall performance from weekly summaries
     */
    protected function calculateOverallPerformance($weeklySummaries, $configuration): array
    {
        if ($weeklySummaries->isEmpty()) {
            return [
                'achievement_percentage' => 0,
                'status' => 'grey',
                'on_target_count' => 0,
                'total_count' => 0,
            ];
        }

        $weights = $configuration->kpi_weights ?? [];
        $totalWeight = 0;
        $weightedAchievement = 0;

        foreach ($weeklySummaries as $summary) {
            $weight = $weights[$summary->kpi_code] ?? 1.0;
            $totalWeight += $weight;
            $weightedAchievement += $summary->achievement_percentage * $weight;
        }

        $overallAchievement = $totalWeight > 0 ? $weightedAchievement / $totalWeight : 0;

        $status = match (true) {
            $overallAchievement >= 90 => 'green',
            $overallAchievement >= 70 => 'yellow',
            default => 'red',
        };

        return [
            'achievement_percentage' => round($overallAchievement, 2),
            'status' => $status,
            'on_target_count' => $weeklySummaries->where('target_met', true)->count(),
            'total_count' => $weeklySummaries->count(),
        ];
    }

    /**
     * Get critical KPIs status
     */
    protected function getCriticalKpisStatus(int $businessId, array $kpiCodes, $configuration): array
    {
        $priorities = $configuration->kpi_priorities ?? [];
        $criticalKpiCodes = array_keys(array_filter($priorities, fn ($p) => $p === 'critical'));

        if (empty($criticalKpiCodes)) {
            return [];
        }

        $weekStart = Carbon::now()->startOfWeek();

        $criticalSummaries = KpiWeeklySummary::where('business_id', $businessId)
            ->whereIn('kpi_code', $criticalKpiCodes)
            ->where('week_start_date', $weekStart)
            ->with('kpiTemplate')
            ->get();

        return $criticalSummaries->map(function ($summary) {
            return [
                'kpi_code' => $summary->kpi_code,
                'kpi_name' => $summary->kpiTemplate->kpi_name ?? $summary->kpi_code,
                'kpi_name_uz' => $summary->kpiTemplate->kpi_name_uz ?? $summary->kpi_code,
                'achievement_percentage' => $summary->achievement_percentage,
                'status' => $summary->status,
                'trend' => $summary->trend,
                'actual_value' => $summary->actual_value,
                'planned_value' => $summary->planned_value,
                'icon' => $summary->kpiTemplate->icon ?? null,
            ];
        })->values()->toArray();
    }
}
