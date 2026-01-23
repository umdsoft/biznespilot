<?php

namespace App\Http\Controllers\Business;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Traits\HasCurrentBusiness;
use App\Models\WeeklyAnalytics;
use App\Services\WeeklyAnalyticsPdfService;
use App\Services\WeeklyAnalyticsService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class WeeklyAnalyticsController extends Controller
{
    use HasCurrentBusiness;

    public function __construct(
        protected WeeklyAnalyticsService $analyticsService,
        protected WeeklyAnalyticsPdfService $pdfService
    ) {}

    /**
     * Display the weekly analytics page
     */
    public function index(Request $request): Response
    {
        try {
            $business = $this->getCurrentBusiness($request);

            if (! $business) {
                return Inertia::render('Business/Analytics/WeeklyReport', [
                    'error' => 'Biznes topilmadi',
                    'weeks' => [],
                    'currentWeekStart' => now()->startOfWeek()->format('Y-m-d'),
                ]);
            }

            // Get last 4 weeks of analytics
            $weeks = WeeklyAnalytics::getLastWeeks($business->id, 4);

            return Inertia::render('Business/Analytics/WeeklyReport', [
                'weeks' => $weeks->map(fn ($w) => [
                    'id' => $w->id,
                    'week_start' => $w->week_start->format('Y-m-d'),
                    'week_end' => $w->week_end->format('Y-m-d'),
                    'week_label' => $w->week_label,
                    'has_ai' => $w->hasAiAnalysis(),
                ]),
                'currentWeekStart' => now()->startOfWeek()->format('Y-m-d'),
            ]);
        } catch (\Exception $e) {
            \Log::error('Weekly analytics index error', [
                'error' => $e->getMessage(),
            ]);

            return Inertia::render('Business/Analytics/WeeklyReport', [
                'error' => 'Ma\'lumotlarni yuklashda xatolik yuz berdi',
                'weeks' => [],
                'currentWeekStart' => now()->startOfWeek()->format('Y-m-d'),
            ]);
        }
    }

    /**
     * Get data for a specific week
     */
    public function getWeekData(Request $request, ?string $weekStart = null): JsonResponse
    {
        try {
            $business = $this->getCurrentBusiness($request);

            if (! $business) {
                return response()->json(['error' => 'Biznes topilmadi'], 404);
            }

            $weekStartDate = $weekStart
                ? Carbon::parse($weekStart)->startOfWeek()
                : now()->startOfWeek();

            // Try to find existing analytics
            $analytics = WeeklyAnalytics::where('business_id', $business->id)
                ->where('week_start', $weekStartDate->format('Y-m-d'))
                ->first();

            // If not found and it's current or past week, generate it
            if (! $analytics && $weekStartDate->lte(now()->startOfWeek())) {
                $analytics = $this->analyticsService->generateWeeklyReport($business, $weekStartDate);
            }

            if (! $analytics) {
                return response()->json([
                    'error' => 'Bu hafta uchun ma\'lumot mavjud emas',
                ], 404);
            }

            return response()->json([
                'id' => $analytics->id,
                'week_start' => $analytics->week_start->format('Y-m-d'),
                'week_end' => $analytics->week_end->format('Y-m-d'),
                'week_label' => $analytics->week_label,
                'summary' => $analytics->summary_stats ?? [],
                'channels' => $analytics->channel_stats ?? [],
                'operators' => $analytics->operator_stats ?? [],
                'time_stats' => $analytics->time_stats ?? [],
                'lost_reasons' => $analytics->lost_reason_stats ?? [],
                'trends' => $analytics->trend_stats ?? [],
                // Extended stats
                'regional' => $analytics->regional_stats ?? [],
                'qualification' => $analytics->qualification_stats ?? [],
                'calls' => $analytics->call_stats ?? [],
                'tasks' => $analytics->task_stats ?? [],
                'pipeline' => $analytics->pipeline_stats ?? [],
                'ai' => [
                    'has_analysis' => $analytics->hasAiAnalysis(),
                    'good_results' => $analytics->ai_good_results ?? [],
                    'problems' => $analytics->ai_problems ?? [],
                    'recommendations' => $analytics->ai_recommendations ?? [],
                    'next_week_goal' => $analytics->ai_next_week_goal,
                    'generated_at' => $analytics->generated_at?->format('Y-m-d H:i'),
                    'tokens_used' => $analytics->tokens_used ?? 0,
                ],
                'created_at' => $analytics->created_at->format('Y-m-d H:i'),
            ]);
        } catch (\Exception $e) {
            \Log::error('Weekly analytics getWeekData error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'error' => 'Ma\'lumotlarni olishda xatolik: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Generate AI analysis for a week
     */
    public function generateAiAnalysis(Request $request, string $id): JsonResponse
    {
        try {
            $business = $this->getCurrentBusiness($request);

            if (! $business) {
                return response()->json(['error' => 'Biznes topilmadi'], 404);
            }

            $analytics = WeeklyAnalytics::where('business_id', $business->id)
                ->where('id', $id)
                ->first();

            if (! $analytics) {
                return response()->json(['error' => 'Haftalik hisobot topilmadi'], 404);
            }

            $analytics = $this->analyticsService->generateAiAnalysis($analytics);

            return response()->json([
                'success' => true,
                'ai' => [
                    'has_analysis' => $analytics->hasAiAnalysis(),
                    'good_results' => $analytics->ai_good_results ?? [],
                    'problems' => $analytics->ai_problems ?? [],
                    'recommendations' => $analytics->ai_recommendations ?? [],
                    'next_week_goal' => $analytics->ai_next_week_goal,
                    'generated_at' => $analytics->generated_at?->format('Y-m-d H:i'),
                    'tokens_used' => $analytics->tokens_used ?? 0,
                ],
            ]);
        } catch (\Exception $e) {
            \Log::error('Weekly analytics AI generation error', [
                'id' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'error' => 'AI tahlil yaratishda xatolik: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Force regenerate weekly report (for admins/debugging)
     */
    public function regenerate(Request $request, ?string $weekStart = null): JsonResponse
    {
        try {
            $business = $this->getCurrentBusiness($request);

            if (! $business) {
                return response()->json(['error' => 'Biznes topilmadi'], 404);
            }

            $weekStartDate = $weekStart
                ? Carbon::parse($weekStart)->startOfWeek()
                : now()->startOfWeek();

            // Delete existing if any
            WeeklyAnalytics::where('business_id', $business->id)
                ->where('week_start', $weekStartDate->format('Y-m-d'))
                ->delete();

            // Generate new
            $analytics = $this->analyticsService->generateWeeklyReport($business, $weekStartDate);

            return response()->json([
                'success' => true,
                'id' => $analytics->id,
                'week_label' => $analytics->week_label,
            ]);
        } catch (\Exception $e) {
            \Log::error('Weekly analytics regenerate error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'error' => 'Hisobotni qayta yaratishda xatolik: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Download PDF report
     */
    public function downloadPdf(Request $request, string $id)
    {
        try {
            $business = $this->getCurrentBusiness($request);

            if (! $business) {
                return response()->json(['error' => 'Biznes topilmadi'], 404);
            }

            $analytics = WeeklyAnalytics::where('business_id', $business->id)
                ->where('id', $id)
                ->first();

            if (! $analytics) {
                return response()->json(['error' => 'Haftalik hisobot topilmadi'], 404);
            }

            return $this->pdfService->downloadPdf($analytics);
        } catch (\Exception $e) {
            \Log::error('Weekly analytics PDF download error', [
                'id' => $id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'error' => 'PDF yuklab olishda xatolik: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Stream PDF in browser
     */
    public function streamPdf(Request $request, string $id)
    {
        try {
            $business = $this->getCurrentBusiness($request);

            if (! $business) {
                return response()->json(['error' => 'Biznes topilmadi'], 404);
            }

            $analytics = WeeklyAnalytics::where('business_id', $business->id)
                ->where('id', $id)
                ->first();

            if (! $analytics) {
                return response()->json(['error' => 'Haftalik hisobot topilmadi'], 404);
            }

            return $this->pdfService->streamPdf($analytics);
        } catch (\Exception $e) {
            \Log::error('Weekly analytics PDF stream error', [
                'id' => $id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'error' => 'PDF ko\'rishda xatolik: '.$e->getMessage(),
            ], 500);
        }
    }
}
