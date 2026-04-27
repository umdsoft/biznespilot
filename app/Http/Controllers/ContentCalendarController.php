<?php

namespace App\Http\Controllers;

use App\Models\ContentCalendar;
use App\Models\MonthlyPlan;
use App\Models\WeeklyPlan;
use App\Services\Content\ContentHashtagGenerator;
use App\Services\Content\ContentWatermarker;
use App\Services\Content\Publishers\TelegramChannelPublisher;
use App\Services\ContentStrategyService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Inertia\Inertia;

class ContentCalendarController extends Controller
{
    protected int $cacheTTL = 300; // 5 minutes

    public function __construct(
        private ContentStrategyService $contentService
    ) {}

    /**
     * Content calendar view - LAZY LOADING
     */
    public function index(Request $request)
    {
        $business = $request->user()->currentBusiness;

        if (! $business) {
            return redirect()->route('business.index');
        }

        $view = $request->input('view', 'month'); // month, week, day
        $date = $request->input('date', now()->toDateString());
        $channel = $request->input('channel');

        $currentDate = Carbon::parse($date);

        // Calculate date range based on view
        [$startDate, $endDate] = match ($view) {
            'month' => [$currentDate->copy()->startOfMonth(), $currentDate->copy()->endOfMonth()],
            'week' => [$currentDate->copy()->startOfWeek(), $currentDate->copy()->endOfWeek()],
            'day' => [$currentDate->copy()->startOfDay(), $currentDate->copy()->endOfDay()],
        };

        // Static constants don't need caching
        $channels = ContentCalendar::CHANNELS;

        return Inertia::render('Strategy/ContentCalendar/Index', [
            'items' => null,
            'grouped_items' => null,
            'view' => $view,
            'current_date' => $currentDate->toDateString(),
            'start_date' => $startDate->toDateString(),
            'end_date' => $endDate->toDateString(),
            'channels' => $channels,
            'selected_channel' => $channel,
            'todays_content' => null,
            'upcoming_content' => null,
            'content_types' => ContentCalendar::CONTENT_TYPES,
            'statuses' => ContentCalendar::STATUSES,
            'lazyLoad' => true,
        ]);
    }

    /**
     * API: Get calendar data
     */
    public function getCalendarData(Request $request)
    {
        $business = $request->user()->currentBusiness;

        if (! $business) {
            return response()->json(['error' => 'Business not found'], 404);
        }

        $view = $request->input('view', 'month');
        $date = $request->input('date', now()->toDateString());
        $channel = $request->input('channel');

        $currentDate = Carbon::parse($date);

        [$startDate, $endDate] = match ($view) {
            'month' => [$currentDate->copy()->startOfMonth(), $currentDate->copy()->endOfMonth()],
            'week' => [$currentDate->copy()->startOfWeek(), $currentDate->copy()->endOfWeek()],
            'day' => [$currentDate->copy()->startOfDay(), $currentDate->copy()->endOfDay()],
        };

        $cacheKey = "content_calendar_{$business->id}_{$view}_{$startDate->format('Y-m-d')}_{$channel}";

        $data = Cache::remember($cacheKey, $this->cacheTTL, function () use ($business, $startDate, $endDate, $channel) {
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

            // Get today's items
            $todaysContent = $this->contentService->getTodaysContent($business);

            // Get upcoming items
            $upcomingContent = $this->contentService->getUpcomingContent($business, 7);

            return [
                'items' => $items,
                'grouped_items' => $groupedItems,
                'todays_content' => $todaysContent,
                'upcoming_content' => $upcomingContent,
            ];
        });

        return response()->json($data);
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
            'content_type' => 'required|string|in:'.implode(',', array_keys(ContentCalendar::CONTENT_TYPES)),
            'channel' => 'required|string',
            'scheduled_date' => 'required|date',
            'scheduled_time' => 'nullable|date_format:H:i',
            'theme' => 'nullable|string|max:100',
            'goal' => 'nullable|string|in:'.implode(',', array_keys(ContentCalendar::GOALS)),
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
            'content_type' => 'sometimes|string|in:'.implode(',', array_keys(ContentCalendar::CONTENT_TYPES)),
            'channel' => 'sometimes|string',
            'scheduled_date' => 'sometimes|date',
            'scheduled_time' => 'nullable|date_format:H:i',
            'status' => 'sometimes|string|in:'.implode(',', array_keys(ContentCalendar::STATUSES)),
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

        return back()->with('success', $items->count().' ta kontent yaratildi');
    }

    /**
     * Generate calendar for a week
     */
    public function generateWeekly(Request $request, WeeklyPlan $weekly)
    {
        $this->authorize('update', $weekly);

        $items = $this->contentService->generateWeeklyCalendar($weekly);

        return back()->with('success', $items->count().' ta kontent yaratildi');
    }

    /**
     * Bulk update status
     */
    public function bulkUpdateStatus(Request $request)
    {
        $validated = $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'exists:content_calendar,id',
            'status' => 'required|string|in:'.implode(',', array_keys(ContentCalendar::STATUSES)),
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

    // ================================================================
    // AVTOMATIK PUBLISH va MATCHING uchun yangi endpointlar
    // ================================================================

    /**
     * "Copy text" tugmasi uchun — sistema generatsiya qilgan
     * hashtag + watermark bilan boyitilgan matnni qaytaradi.
     *
     * Foydalanuvchi clipboard'ga oladi va tashqarida (Telegram/Instagram'da) paste qiladi.
     */
    public function publishText(
        Request $request,
        ContentCalendar $content,
        ContentHashtagGenerator $hashtagGenerator,
        ContentWatermarker $watermarker,
    ): JsonResponse {
        $business = $request->user()->currentBusiness;
        if (! $business || $content->business_id !== $business->id) {
            return response()->json(['success' => false, 'message' => 'Forbidden'], 403);
        }

        // Auto-hashtag mavjud bo'lmasa generatsiya qilamiz
        $hashtag = $hashtagGenerator->ensureForItem($content);

        $base = trim((string) ($content->content_text ?? $content->content ?? $content->description ?? $content->title ?? ''));

        // Foydalanuvchi qo'lda yozgan hashtag'lar
        $userHashtags = is_array($content->hashtags) ? $content->hashtags : [];
        $userHashtags = array_filter(array_map(
            fn ($t) => '#' . ltrim((string) $t, '#'),
            $userHashtags,
        ));

        $parts = [$base];
        if (! empty($userHashtags)) {
            $parts[] = implode(' ', array_unique($userHashtags));
        }
        if (! empty($hashtag) && ! str_contains($base, $hashtag)) {
            $parts[] = $hashtag;
        }

        $combined = trim(implode("\n\n", array_filter($parts)));
        $watermarked = $watermarker->embed($combined, (string) $content->id);

        return response()->json([
            'success' => true,
            'text' => $watermarked,
            'visible_text' => $combined, // foydalanuvchiga ko'rsatish uchun (watermark'siz)
            'hashtag' => $hashtag,
            'has_watermark' => true,
            'media_urls' => is_array($content->media_urls) ? $content->media_urls : [],
            'platform' => (string) ($content->platform ?? 'telegram'),
        ]);
    }

    /**
     * "Avtomatik post" tugmasi uchun — sistema o'zi platformaga post qiladi.
     * 100% aniq match kafolatlanadi (API javobida message_id qaytariladi).
     */
    public function autoPublish(
        Request $request,
        ContentCalendar $content,
        TelegramChannelPublisher $telegramPublisher,
    ): JsonResponse {
        $business = $request->user()->currentBusiness;
        if (! $business || $content->business_id !== $business->id) {
            return response()->json(['success' => false, 'message' => 'Forbidden'], 403);
        }

        if ($content->status === 'published') {
            return response()->json([
                'success' => false,
                'message' => 'Bu kontent allaqachon chop etilgan',
                'post_url' => $content->post_url,
            ], 422);
        }

        $platform = strtolower((string) ($content->platform ?? $content->channel ?? ''));

        $result = match ($platform) {
            'telegram' => $telegramPublisher->publish($content->fresh()),
            default => [
                'success' => false,
                'error' => 'unsupported_platform',
                'message' => "Hozircha faqat Telegram qo'llab-quvvatlanadi (kelajakda: Instagram, YouTube)",
            ],
        };

        if (! ($result['success'] ?? false)) {
            $errorCode = (string) ($result['error'] ?? 'unknown');
            $message = match ($errorCode) {
                'no_channel_connected' => "Telegram kanal ulanmagan — avval kanalni ulang",
                'bot_not_admin' => "@biznespilot_bot kanalingizda admin emas — uni admin qiling",
                'photo_url_missing', 'video_url_missing', 'document_url_missing' => "Media fayl topilmadi",
                'telegram_api_error' => "Telegram xatosi: " . ($result['description'] ?? 'noma\'lum'),
                default => "Publish qilinmadi: {$errorCode}",
            };

            return response()->json([
                'success' => false,
                'error' => $errorCode,
                'message' => $message,
                'details' => $result,
            ], 422);
        }

        return response()->json([
            'success' => true,
            'message' => "Muvaffaqiyatli chop etildi",
            'message_id' => $result['message_id'] ?? null,
            'post_url' => $result['post_url'] ?? null,
            'item' => $content->fresh(),
        ]);
    }

    /**
     * Item save qilinganda yoki yangilanganda auto_hashtag'ni
     * tezlik bilan generatsiya qilish (UI preview uchun).
     */
    public function previewHashtag(
        Request $request,
        ContentCalendar $content,
        ContentHashtagGenerator $generator,
    ): JsonResponse {
        $business = $request->user()->currentBusiness;
        if (! $business || $content->business_id !== $business->id) {
            return response()->json(['success' => false, 'message' => 'Forbidden'], 403);
        }

        return response()->json([
            'success' => true,
            'hashtag' => $generator->ensureForItem($content),
        ]);
    }
}
