<?php

namespace App\Http\Controllers\Business;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Traits\HasCurrentBusiness;
use App\Models\WeeklyGoal;
use App\Services\WeeklyGoalsService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class WeeklyGoalsController extends Controller
{
    use HasCurrentBusiness;

    public function __construct(
        protected WeeklyGoalsService $goalsService
    ) {}

    /**
     * Display weekly goals page
     */
    public function index(Request $request): Response
    {
        try {
            $business = $this->getCurrentBusiness($request);

            if (!$business) {
                return Inertia::render('Business/Analytics/WeeklyGoals', [
                    'error' => 'Biznes topilmadi',
                    'currentGoal' => null,
                    'history' => [],
                ]);
            }

            $currentGoal = $this->goalsService->getOrCreateGoal($business);
            $progress = $this->goalsService->getCurrentProgress($currentGoal);
            $history = $this->goalsService->getGoalHistory($business, 8);
            $streak = $this->goalsService->getStreak($business);

            return Inertia::render('Business/Analytics/WeeklyGoals', [
                'currentGoal' => $this->formatGoal($currentGoal),
                'progress' => $progress,
                'history' => $history,
                'streak' => $streak,
            ]);
        } catch (\Exception $e) {
            \Log::error('Weekly goals index error', [
                'error' => $e->getMessage(),
            ]);

            return Inertia::render('Business/Analytics/WeeklyGoals', [
                'error' => 'Ma\'lumotlarni yuklashda xatolik yuz berdi',
                'currentGoal' => null,
                'history' => [],
            ]);
        }
    }

    /**
     * Get current goal data via API
     */
    public function getCurrentGoal(Request $request): JsonResponse
    {
        try {
            $business = $this->getCurrentBusiness($request);

            if (!$business) {
                return response()->json(['error' => 'Biznes topilmadi'], 404);
            }

            $goal = $this->goalsService->getOrCreateGoal($business);
            $progress = $this->goalsService->getCurrentProgress($goal);

            return response()->json([
                'goal' => $this->formatGoal($goal),
                'progress' => $progress,
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Ichki xatolik yuz berdi'], 500);
        }
    }

    /**
     * Get goal for specific week
     */
    public function getGoalForWeek(Request $request, string $weekStart): JsonResponse
    {
        try {
            $business = $this->getCurrentBusiness($request);

            if (!$business) {
                return response()->json(['error' => 'Biznes topilmadi'], 404);
            }

            $weekStartDate = Carbon::parse($weekStart)->startOfWeek();
            $goal = $this->goalsService->getOrCreateGoal($business, $weekStartDate);
            $progress = $this->goalsService->getCurrentProgress($goal);

            return response()->json([
                'goal' => $this->formatGoal($goal),
                'progress' => $progress,
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Ichki xatolik yuz berdi'], 500);
        }
    }

    /**
     * Update goal targets
     */
    public function updateTargets(Request $request, string $id): JsonResponse
    {
        try {
            $business = $this->getCurrentBusiness($request);

            if (!$business) {
                return response()->json(['error' => 'Biznes topilmadi'], 404);
            }

            $goal = WeeklyGoal::where('business_id', $business->id)
                ->where('id', $id)
                ->first();

            if (!$goal) {
                return response()->json(['error' => 'Maqsad topilmadi'], 404);
            }

            $validated = $request->validate([
                'target_leads' => 'nullable|integer|min:0',
                'target_won' => 'nullable|integer|min:0',
                'target_conversion' => 'nullable|numeric|min:0|max:100',
                'target_revenue' => 'nullable|numeric|min:0',
                'target_calls' => 'nullable|integer|min:0',
                'target_meetings' => 'nullable|integer|min:0',
                'notes' => 'nullable|string|max:1000',
            ]);

            $goal = $this->goalsService->updateTargets($goal, $validated);

            return response()->json([
                'success' => true,
                'goal' => $this->formatGoal($goal),
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Ichki xatolik yuz berdi'], 500);
        }
    }

    /**
     * Refresh actuals from analytics
     */
    public function refreshActuals(Request $request, string $id): JsonResponse
    {
        try {
            $business = $this->getCurrentBusiness($request);

            if (!$business) {
                return response()->json(['error' => 'Biznes topilmadi'], 404);
            }

            $goal = WeeklyGoal::where('business_id', $business->id)
                ->where('id', $id)
                ->first();

            if (!$goal) {
                return response()->json(['error' => 'Maqsad topilmadi'], 404);
            }

            $goal = $this->goalsService->updateActuals($goal);
            $progress = $this->goalsService->getCurrentProgress($goal);

            return response()->json([
                'success' => true,
                'goal' => $this->formatGoal($goal),
                'progress' => $progress,
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Ichki xatolik yuz berdi'], 500);
        }
    }

    /**
     * Get operator KPIs for a goal
     */
    public function getOperatorKpis(Request $request, string $id): JsonResponse
    {
        try {
            $business = $this->getCurrentBusiness($request);

            if (!$business) {
                return response()->json(['error' => 'Biznes topilmadi'], 404);
            }

            $goal = WeeklyGoal::where('business_id', $business->id)
                ->where('id', $id)
                ->first();

            if (!$goal) {
                return response()->json(['error' => 'Maqsad topilmadi'], 404);
            }

            // Update operator KPIs first
            $this->goalsService->updateOperatorKpis($business, $goal->week_start);

            $kpis = $this->goalsService->getOperatorKpis($goal);

            return response()->json([
                'operators' => $kpis->map(function ($kpi) {
                    return [
                        'id' => $kpi->id,
                        'user_id' => $kpi->user_id,
                        'user_name' => $kpi->user->name ?? 'Noma\'lum',
                        'rank' => $kpi->rank,
                        'targets' => [
                            'leads' => $kpi->target_leads,
                            'won' => $kpi->target_won,
                            'revenue' => $kpi->target_revenue,
                            'calls' => $kpi->target_calls,
                        ],
                        'actuals' => [
                            'leads' => $kpi->actual_leads,
                            'won' => $kpi->actual_won,
                            'revenue' => $kpi->actual_revenue,
                            'calls' => $kpi->actual_calls,
                        ],
                        'overall_score' => $kpi->overall_score,
                    ];
                }),
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Ichki xatolik yuz berdi'], 500);
        }
    }

    /**
     * Get goal history
     */
    public function getHistory(Request $request): JsonResponse
    {
        try {
            $business = $this->getCurrentBusiness($request);

            if (!$business) {
                return response()->json(['error' => 'Biznes topilmadi'], 404);
            }

            $weeks = $request->input('weeks', 8);
            $history = $this->goalsService->getGoalHistory($business, $weeks);
            $streak = $this->goalsService->getStreak($business);

            return response()->json([
                'history' => $history,
                'streak' => $streak,
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Ichki xatolik yuz berdi'], 500);
        }
    }

    /**
     * Format goal for response
     */
    protected function formatGoal(WeeklyGoal $goal): array
    {
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
                'calls' => $goal->target_calls,
                'meetings' => $goal->target_meetings,
            ],
            'actuals' => [
                'leads' => $goal->actual_leads,
                'won' => $goal->actual_won,
                'conversion' => $goal->actual_conversion,
                'revenue' => $goal->actual_revenue,
                'calls' => $goal->actual_calls,
                'meetings' => $goal->actual_meetings,
            ],
            'achievements' => [
                'leads' => $goal->leads_achievement,
                'won' => $goal->won_achievement,
                'conversion' => $goal->conversion_achievement,
                'revenue' => $goal->revenue_achievement,
                'calls' => $goal->calls_achievement,
                'meetings' => $goal->meetings_achievement,
            ],
            'ai_suggested_goal' => $goal->ai_suggested_goal,
            'ai_focus_areas' => $goal->ai_focus_areas ?? [],
            'notes' => $goal->notes,
            'created_at' => $goal->created_at->format('Y-m-d H:i'),
        ];
    }
}
