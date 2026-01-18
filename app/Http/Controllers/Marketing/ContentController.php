<?php

namespace App\Http\Controllers\Marketing;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Traits\HasCurrentBusiness;
use App\Models\ContentPost;
use App\Services\ContentStrategyService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Inertia\Inertia;

class ContentController extends Controller
{
    use HasCurrentBusiness;

    protected int $cacheTTL = 300;

    public function __construct(
        protected ContentStrategyService $contentService
    ) {}

    public function index(Request $request)
    {
        $business = $this->getCurrentBusiness();

        if (! $business) {
            return redirect()->route('login');
        }

        $query = ContentPost::where('business_id', $business->id);

        // Apply filters
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('platform')) {
            $query->where(function ($q) use ($request) {
                $q->where('platform', 'like', '%'.$request->platform.'%');
            });
        }

        if ($request->filled('search')) {
            $query->where('title', 'like', '%'.$request->search.'%');
        }

        $contents = $query->orderBy('scheduled_at', 'desc')
            ->with('creator')
            ->get()
            ->map(function ($post) {
                return [
                    'id' => $post->id,
                    'title' => $post->title,
                    'content' => $post->content,
                    'platform' => $post->platform,
                    'type' => $post->type,
                    'content_type' => $post->content_type,
                    'format' => $post->format,
                    'status' => $post->status,
                    'scheduled_at' => $post->scheduled_at?->format('Y-m-d H:i'),
                    'published_at' => $post->published_at?->format('d.m.Y H:i'),
                    'author' => $post->creator?->name ?? 'Noma\'lum',
                    'views' => $post->views ?? 0,
                    'likes' => $post->likes ?? 0,
                    'comments' => $post->comments ?? 0,
                    'shares' => $post->shares ?? 0,
                    'hashtags' => $post->hashtags ?? [],
                ];
            });

        // Calculate stats
        $stats = [
            'total' => ContentPost::where('business_id', $business->id)->count(),
            'scheduled' => ContentPost::where('business_id', $business->id)
                ->where('status', 'scheduled')->count(),
            'published' => ContentPost::where('business_id', $business->id)
                ->where('status', 'published')->count(),
            'draft' => ContentPost::where('business_id', $business->id)
                ->where('status', 'draft')->count(),
        ];

        return Inertia::render('Marketing/Content', [
            'posts' => $contents,
            'stats' => $stats,
            'filters' => $request->only(['status', 'platform', 'search']),
        ]);
    }

    public function store(Request $request)
    {
        $business = $this->getCurrentBusiness();

        if (! $business) {
            return redirect()->route('login');
        }

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'content' => ['required', 'string'],
            'platform' => ['required', 'array', 'min:1'],
            'platform.*' => ['string', 'max:100'],
            'content_type' => ['required', 'in:educational,entertaining,inspirational,promotional,behind_scenes,ugc'],
            'format' => ['required', 'in:short_video,long_video,carousel,single_image,story,text_post,live,poll'],
            'type' => ['nullable', 'string'],
            'status' => ['required', 'in:draft,scheduled,published'],
            'scheduled_at' => ['nullable', 'date'],
            'hashtags' => ['nullable', 'array'],
        ]);

        $validated['business_id'] = $business->id;
        $validated['user_id'] = auth()->id();

        // Handle multiple platforms - store as JSON array
        if (is_array($validated['platform'])) {
            $validated['platform'] = json_encode($validated['platform']);
        }

        ContentPost::create($validated);

        return redirect()->back()
            ->with('success', 'Kontent muvaffaqiyatli qo\'shildi!');
    }

    public function show($id)
    {
        $business = $this->getCurrentBusiness();

        if (! $business) {
            return redirect()->route('login');
        }

        $content = ContentPost::where('business_id', $business->id)
            ->where('id', $id)
            ->with('creator')
            ->firstOrFail();

        // Parse platforms if stored as JSON
        $platforms = $content->platform;
        if (is_string($platforms) && str_starts_with($platforms, '[')) {
            $platforms = json_decode($platforms, true);
        }

        return Inertia::render('Marketing/ContentShow', [
            'post' => [
                'id' => $content->id,
                'title' => $content->title,
                'content' => $content->content,
                'platform' => $platforms,
                'platforms' => is_array($platforms) ? $platforms : [$platforms],
                'type' => $content->type,
                'content_type' => $content->content_type,
                'format' => $content->format,
                'status' => $content->status,
                'scheduled_at' => $content->scheduled_at?->format('Y-m-d\TH:i'),
                'scheduled_at_display' => $content->scheduled_at?->format('d.m.Y H:i'),
                'published_at' => $content->published_at?->format('d.m.Y H:i'),
                'views' => $content->views ?? 0,
                'likes' => $content->likes ?? 0,
                'comments' => $content->comments ?? 0,
                'shares' => $content->shares ?? 0,
                'hashtags' => $content->hashtags ?? [],
                'media' => $content->media ?? [],
                'external_url' => $content->external_url,
                'ai_suggestions' => $content->ai_suggestions,
                'created_at' => $content->created_at->format('d.m.Y H:i'),
                'updated_at' => $content->updated_at->format('d.m.Y H:i'),
            ],
        ]);
    }

    public function edit($id)
    {
        $business = $this->getCurrentBusiness();

        if (! $business) {
            return redirect()->route('login');
        }

        $content = ContentPost::where('business_id', $business->id)
            ->where('id', $id)
            ->with('creator')
            ->firstOrFail();

        // Parse platforms if stored as JSON
        $platforms = $content->platform;
        if (is_string($platforms) && str_starts_with($platforms, '[')) {
            $platforms = json_decode($platforms, true);
        }

        return Inertia::render('Marketing/ContentEdit', [
            'post' => [
                'id' => $content->id,
                'title' => $content->title,
                'content' => $content->content,
                'platform' => $platforms,
                'platforms' => is_array($platforms) ? $platforms : [$platforms],
                'type' => $content->type,
                'content_type' => $content->content_type,
                'format' => $content->format,
                'status' => $content->status,
                'scheduled_at' => $content->scheduled_at?->format('Y-m-d\TH:i'),
                'hashtags' => $content->hashtags ?? [],
                'external_url' => $content->external_url,
            ],
        ]);
    }

    public function update(Request $request, $id)
    {
        $business = $this->getCurrentBusiness();

        if (! $business) {
            return redirect()->route('login');
        }

        $content = ContentPost::where('business_id', $business->id)
            ->where('id', $id)
            ->firstOrFail();

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'content' => ['required', 'string'],
            'platform' => ['nullable', 'array'],
            'platform.*' => ['string', 'max:100'],
            'content_type' => ['nullable', 'in:educational,entertaining,inspirational,promotional,behind_scenes,ugc'],
            'format' => ['nullable', 'in:short_video,long_video,carousel,single_image,story,text_post,live,poll'],
            'type' => ['nullable', 'string'],
            'status' => ['nullable', 'in:draft,scheduled,published'],
            'scheduled_at' => ['nullable', 'date'],
            'hashtags' => ['nullable', 'array'],
        ]);

        // Handle multiple platforms - store as JSON array
        if (isset($validated['platform']) && is_array($validated['platform'])) {
            $validated['platform'] = json_encode($validated['platform']);
        }

        $content->update($validated);

        return redirect()->back()
            ->with('success', 'Kontent yangilandi');
    }

    public function destroy($id)
    {
        $business = $this->getCurrentBusiness();

        if (! $business) {
            return redirect()->route('login');
        }

        $content = ContentPost::where('business_id', $business->id)
            ->where('id', $id)
            ->firstOrFail();

        $content->delete();

        return redirect()->route('marketing.content.index')
            ->with('success', 'Kontent o\'chirildi');
    }

    public function publish($id)
    {
        $business = $this->getCurrentBusiness();

        if (! $business) {
            return response()->json(['error' => 'Business not found'], 404);
        }

        $content = ContentPost::where('business_id', $business->id)
            ->where('id', $id)
            ->firstOrFail();

        $content->update([
            'status' => 'published',
            'published_at' => now(),
        ]);

        return response()->json(['success' => true, 'message' => 'Kontent nashr qilindi']);
    }

    /**
     * Move content to different date (drag & drop)
     */
    public function move(Request $request, $id)
    {
        $business = $this->getCurrentBusiness();

        if (! $business) {
            return response()->json(['error' => 'Business not found'], 404);
        }

        $content = ContentPost::where('business_id', $business->id)
            ->where('id', $id)
            ->firstOrFail();

        $validated = $request->validate([
            'date' => 'required|date',
            'time' => 'nullable|date_format:H:i',
        ]);

        $scheduledAt = Carbon::parse($validated['date']);
        if (! empty($validated['time'])) {
            $time = Carbon::createFromFormat('H:i', $validated['time']);
            $scheduledAt->setTime($time->hour, $time->minute);
        }

        $content->update(['scheduled_at' => $scheduledAt]);

        return back()->with('success', 'Kontent ko\'chirildi');
    }

    /**
     * Duplicate content
     */
    public function duplicate(Request $request, $id)
    {
        $business = $this->getCurrentBusiness();

        if (! $business) {
            return redirect()->route('login');
        }

        $content = ContentPost::where('business_id', $business->id)
            ->where('id', $id)
            ->firstOrFail();

        $validated = $request->validate([
            'date' => 'nullable|date',
        ]);

        $newContent = $content->replicate();
        $newContent->title = $content->title.' (nusxa)';
        $newContent->status = 'draft';
        $newContent->published_at = null;

        if (! empty($validated['date'])) {
            $newContent->scheduled_at = Carbon::parse($validated['date']);
        } else {
            $newContent->scheduled_at = $content->scheduled_at?->addWeek();
        }

        $newContent->save();

        return back()->with('success', 'Kontent nusxalandi');
    }

    /**
     * Approve content
     */
    public function approve(Request $request, $id)
    {
        $business = $this->getCurrentBusiness();

        if (! $business) {
            return response()->json(['error' => 'Business not found'], 404);
        }

        $content = ContentPost::where('business_id', $business->id)
            ->where('id', $id)
            ->firstOrFail();

        $content->update([
            'status' => 'approved',
            'approved_by' => $request->user()->id,
            'approved_at' => now(),
        ]);

        return back()->with('success', 'Kontent tasdiqlandi');
    }

    /**
     * Schedule content
     */
    public function schedule(Request $request, $id)
    {
        $business = $this->getCurrentBusiness();

        if (! $business) {
            return response()->json(['error' => 'Business not found'], 404);
        }

        $content = ContentPost::where('business_id', $business->id)
            ->where('id', $id)
            ->firstOrFail();

        $validated = $request->validate([
            'scheduled_at' => 'required|date|after:now',
        ]);

        $content->update([
            'status' => 'scheduled',
            'scheduled_at' => $validated['scheduled_at'],
        ]);

        return back()->with('success', 'Kontent rejalashtirildi');
    }

    /**
     * Update metrics for published content
     */
    public function updateMetrics(Request $request, $id)
    {
        $business = $this->getCurrentBusiness();

        if (! $business) {
            return response()->json(['error' => 'Business not found'], 404);
        }

        $content = ContentPost::where('business_id', $business->id)
            ->where('id', $id)
            ->firstOrFail();

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

        $content->update($validated);

        return back()->with('success', 'Metrikalar yangilandi');
    }

    /**
     * Generate AI content suggestions
     */
    public function generateAI(Request $request, $id)
    {
        $business = $this->getCurrentBusiness();

        if (! $business) {
            return response()->json(['error' => 'Business not found'], 404);
        }

        $content = ContentPost::where('business_id', $business->id)
            ->where('id', $id)
            ->firstOrFail();

        try {
            // AI tavsiyalar generatsiyasi
            $suggestions = [
                'title_options' => [
                    $content->title.' - yangilangan versiya',
                    'Eng zo\'r '.strtolower($content->title),
                    $content->title.' 2.0',
                ],
                'hashtag_suggestions' => [
                    '#marketing', '#biznes', '#uzbekistan', '#content', '#smm',
                ],
                'best_time' => '18:00 - 20:00',
                'content_tips' => [
                    'Qisqaroq jumlalar ishlatish',
                    'Call-to-action qo\'shish',
                    'Emoji\'lar qo\'shish',
                ],
            ];

            $content->update(['ai_suggestions' => $suggestions]);

            return response()->json([
                'success' => true,
                'suggestions' => $suggestions,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Bulk update status
     */
    public function bulkUpdateStatus(Request $request)
    {
        $business = $this->getCurrentBusiness();

        if (! $business) {
            return response()->json(['error' => 'Business not found'], 404);
        }

        $validated = $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'integer',
            'status' => 'required|string|in:draft,scheduled,published,archived',
        ]);

        $count = ContentPost::where('business_id', $business->id)
            ->whereIn('id', $validated['ids'])
            ->update(['status' => $validated['status']]);

        return response()->json([
            'success' => true,
            'message' => "{$count} ta kontent yangilandi",
        ]);
    }

    /**
     * Get analytics
     */
    public function analytics(Request $request)
    {
        $business = $this->getCurrentBusiness();

        if (! $business) {
            return redirect()->route('login');
        }

        $startDate = $request->input('start_date', now()->startOfMonth()->toDateString());
        $endDate = $request->input('end_date', now()->endOfMonth()->toDateString());

        $cacheKey = "content_analytics_{$business->id}_{$startDate}_{$endDate}";

        $analytics = Cache::remember($cacheKey, $this->cacheTTL, function () use ($business, $startDate, $endDate) {
            $contents = ContentPost::where('business_id', $business->id)
                ->whereBetween('created_at', [$startDate, $endDate])
                ->get();

            return [
                'total_posts' => $contents->count(),
                'published_posts' => $contents->where('status', 'published')->count(),
                'scheduled_posts' => $contents->where('status', 'scheduled')->count(),
                'draft_posts' => $contents->where('status', 'draft')->count(),
                'total_views' => $contents->sum('views'),
                'total_likes' => $contents->sum('likes'),
                'total_comments' => $contents->sum('comments'),
                'total_shares' => $contents->sum('shares'),
                'avg_engagement' => $contents->count() > 0
                    ? round(($contents->sum('likes') + $contents->sum('comments') + $contents->sum('shares')) / $contents->count(), 2)
                    : 0,
                'by_platform' => $contents->groupBy('platform')->map->count(),
                'by_type' => $contents->groupBy('content_type')->map->count(),
                'top_performing' => $contents->sortByDesc(function ($item) {
                    return ($item->views ?? 0) + ($item->likes ?? 0) * 2 + ($item->comments ?? 0) * 3;
                })->take(5)->values(),
            ];
        });

        return Inertia::render('Marketing/ContentAnalytics', [
            'analytics' => $analytics,
            'start_date' => $startDate,
            'end_date' => $endDate,
        ]);
    }

    /**
     * Calendar view - API endpoint
     */
    public function calendar(Request $request)
    {
        $business = $this->getCurrentBusiness();

        if (! $business) {
            return response()->json(['error' => 'Business not found'], 404);
        }

        $view = $request->input('view', 'month');
        $date = $request->input('date', now()->toDateString());

        $currentDate = Carbon::parse($date);

        [$startDate, $endDate] = match ($view) {
            'month' => [$currentDate->copy()->startOfMonth(), $currentDate->copy()->endOfMonth()],
            'week' => [$currentDate->copy()->startOfWeek(), $currentDate->copy()->endOfWeek()],
            'day' => [$currentDate->copy()->startOfDay(), $currentDate->copy()->endOfDay()],
        };

        $contents = ContentPost::where('business_id', $business->id)
            ->whereBetween('scheduled_at', [$startDate, $endDate])
            ->orderBy('scheduled_at')
            ->get();

        $groupedItems = $contents->groupBy(function ($item) {
            return $item->scheduled_at?->toDateString();
        });

        return response()->json([
            'items' => $contents,
            'grouped_items' => $groupedItems,
            'current_date' => $currentDate->toDateString(),
            'start_date' => $startDate->toDateString(),
            'end_date' => $endDate->toDateString(),
        ]);
    }
}
