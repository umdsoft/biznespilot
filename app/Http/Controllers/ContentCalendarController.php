<?php

namespace App\Http\Controllers;

use App\Models\ContentCalendar;
use App\Models\MonthlyPlan;
use App\Models\WeeklyPlan;
use App\Services\ContentStrategyService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Carbon\Carbon;

class ContentCalendarController extends Controller
{
    public function __construct(
        private ContentStrategyService $contentService
    ) {}

    /**
     * Content calendar view
     */
    public function index(Request $request)
    {
        $business = $request->user()->currentBusiness;

        $view = $request->input('view', 'month'); // month, week, day
        $date = $request->input('date', now()->toDateString());
        $channel = $request->input('channel');

        $currentDate = Carbon::parse($date);

        // Calculate date range based on view
        [$startDate, $endDate] = match($view) {
            'month' => [$currentDate->copy()->startOfMonth(), $currentDate->copy()->endOfMonth()],
            'week' => [$currentDate->copy()->startOfWeek(), $currentDate->copy()->endOfWeek()],
            'day' => [$currentDate->copy()->startOfDay(), $currentDate->copy()->endOfDay()],
        };

        // Get content items
        $items = $this->contentService->getCalendar(
            $business,
            $startDate->toDateString(),
            $endDate->toDateString(),
            $channel
        );

        // Group by date for calendar view
        $groupedItems = $items->groupBy(function ($item) {
            return $item->scheduled_date->toDateString();
        });

        // Get channels for filter
        $channels = ContentCalendar::CHANNELS;

        // Get today's items
        $todaysContent = $this->contentService->getTodaysContent($business);

        // Get upcoming items
        $upcomingContent = $this->contentService->getUpcomingContent($business, 7);

        return Inertia::render('Strategy/ContentCalendar/Index', [
            'items' => $items,
            'grouped_items' => $groupedItems,
            'view' => $view,
            'current_date' => $currentDate->toDateString(),
            'start_date' => $startDate->toDateString(),
            'end_date' => $endDate->toDateString(),
            'channels' => $channels,
            'selected_channel' => $channel,
            'todays_content' => $todaysContent,
            'upcoming_content' => $upcomingContent,
            'content_types' => ContentCalendar::CONTENT_TYPES,
            'statuses' => ContentCalendar::STATUSES,
        ]);
    }

    /**
     * Create content item
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'content_text' => 'nullable|string',
            'content_type' => 'required|string|in:' . implode(',', array_keys(ContentCalendar::CONTENT_TYPES)),
            'channel' => 'required|string',
            'scheduled_date' => 'required|date',
            'scheduled_time' => 'nullable|date_format:H:i',
            'theme' => 'nullable|string|max:100',
            'goal' => 'nullable|string|in:' . implode(',', array_keys(ContentCalendar::GOALS)),
            'hashtags' => 'nullable|array',
            'tags' => 'nullable|array',
            'weekly_plan_id' => 'nullable|exists:weekly_plans,id',
            'monthly_plan_id' => 'nullable|exists:monthly_plans,id',
        ]);

        $business = $request->user()->currentBusiness;

        $item = ContentCalendar::create([
            ...$validated,
            'business_id' => $business->id,
            'status' => 'draft',
            'created_by' => $request->user()->id,
        ]);

        return back()->with('success', 'Kontent qo\'shildi');
    }

    /**
     * Show content item
     */
    public function show(Request $request, ContentCalendar $content)
    {
        $this->authorize('view', $content);

        return response()->json([
            'content' => $content,
        ]);
    }

    /**
     * Update content item
     */
    public function update(Request $request, ContentCalendar $content)
    {
        $this->authorize('update', $content);

        $validated = $request->validate([
            'title' => 'sometimes|string|max:255',
            'description' => 'nullable|string|max:1000',
            'content_text' => 'nullable|string',
            'content_type' => 'sometimes|string|in:' . implode(',', array_keys(ContentCalendar::CONTENT_TYPES)),
            'channel' => 'sometimes|string',
            'scheduled_date' => 'sometimes|date',
            'scheduled_time' => 'nullable|date_format:H:i',
            'status' => 'sometimes|string|in:' . implode(',', array_keys(ContentCalendar::STATUSES)),
            'theme' => 'nullable|string|max:100',
            'goal' => 'nullable|string',
            'hashtags' => 'nullable|array',
            'tags' => 'nullable|array',
            'notes' => 'nullable|string|max:500',
        ]);

        $content->update($validated);

        return back()->with('success', 'Kontent yangilandi');
    }

    /**
     * Delete content item
     */
    public function destroy(Request $request, ContentCalendar $content)
    {
        $this->authorize('delete', $content);

        $content->delete();

        return back()->with('success', 'Kontent o\'chirildi');
    }

    /**
     * Move content to different date (drag & drop)
     */
    public function move(Request $request, ContentCalendar $content)
    {
        $this->authorize('update', $content);

        $validated = $request->validate([
            'date' => 'required|date',
            'time' => 'nullable|date_format:H:i',
        ]);

        $this->contentService->moveContent($content, $validated['date'], $validated['time']);

        return back()->with('success', 'Kontent ko\'chirildi');
    }

    /**
     * Duplicate content
     */
    public function duplicate(Request $request, ContentCalendar $content)
    {
        $this->authorize('view', $content);

        $validated = $request->validate([
            'date' => 'nullable|date',
        ]);

        $newContent = $this->contentService->duplicateContent($content, $validated['date']);

        return back()->with('success', 'Kontent nusxalandi');
    }

    /**
     * Approve content
     */
    public function approve(Request $request, ContentCalendar $content)
    {
        $this->authorize('update', $content);

        $content->approve($request->user()->id);

        return back()->with('success', 'Kontent tasdiqlandi');
    }

    /**
     * Schedule content
     */
    public function schedule(Request $request, ContentCalendar $content)
    {
        $this->authorize('update', $content);

        $content->schedule();

        return back()->with('success', 'Kontent rejalashtirildi');
    }

    /**
     * Mark as published
     */
    public function publish(Request $request, ContentCalendar $content)
    {
        $this->authorize('update', $content);

        $validated = $request->validate([
            'external_id' => 'nullable|string',
            'post_url' => 'nullable|url',
        ]);

        $content->markAsPublished($validated['external_id'] ?? null, $validated['post_url'] ?? null);

        return back()->with('success', 'Kontent joylashtirildi deb belgilandi');
    }

    /**
     * Update metrics for published content
     */
    public function updateMetrics(Request $request, ContentCalendar $content)
    {
        $this->authorize('update', $content);

        $validated = $request->validate([
            'views' => 'nullable|integer|min:0',
            'likes' => 'nullable|integer|min:0',
            'comments' => 'nullable|integer|min:0',
            'shares' => 'nullable|integer|min:0',
            'saves' => 'nullable|integer|min:0',
            'clicks' => 'nullable|integer|min:0',
            'reach' => 'nullable|integer|min:0',
            'impressions' => 'nullable|integer|min:0',
        ]);

        $content->updateMetrics($validated);

        return back()->with('success', 'Metrikalar yangilandi');
    }

    /**
     * Generate AI content suggestions
     */
    public function generateAI(Request $request, ContentCalendar $content)
    {
        $this->authorize('update', $content);

        $this->contentService->generateAIContent($content);

        return back()->with('success', 'AI tavsiyalar yaratildi');
    }

    /**
     * Generate calendar for a month
     */
    public function generateMonthly(Request $request, MonthlyPlan $monthly)
    {
        $this->authorize('update', $monthly);

        $items = $this->contentService->generateMonthlyCalendar($monthly);

        return back()->with('success', $items->count() . ' ta kontent yaratildi');
    }

    /**
     * Generate calendar for a week
     */
    public function generateWeekly(Request $request, WeeklyPlan $weekly)
    {
        $this->authorize('update', $weekly);

        $items = $this->contentService->generateWeeklyCalendar($weekly);

        return back()->with('success', $items->count() . ' ta kontent yaratildi');
    }

    /**
     * Bulk update status
     */
    public function bulkUpdateStatus(Request $request)
    {
        $validated = $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'exists:content_calendar,id',
            'status' => 'required|string|in:' . implode(',', array_keys(ContentCalendar::STATUSES)),
        ]);

        $count = $this->contentService->bulkUpdateStatus($validated['ids'], $validated['status']);

        return back()->with('success', "{$count} ta kontent yangilandi");
    }

    /**
     * Get analytics
     */
    public function analytics(Request $request)
    {
        $business = $request->user()->currentBusiness;

        $startDate = $request->input('start_date', now()->startOfMonth()->toDateString());
        $endDate = $request->input('end_date', now()->endOfMonth()->toDateString());

        $analytics = $this->contentService->getContentAnalytics($business, $startDate, $endDate);

        return Inertia::render('Strategy/ContentCalendar/Analytics', [
            'analytics' => $analytics,
            'start_date' => $startDate,
            'end_date' => $endDate,
        ]);
    }
}
