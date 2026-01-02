<?php

namespace App\Http\Controllers;

use App\Models\MarketingChannel;
use App\Models\MarketingSpend;
use App\Models\ContentPost;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class MarketingController extends Controller
{
    /**
     * Display marketing dashboard.
     */
    public function index()
    {
        $currentBusiness = session('current_business_id')
            ? Auth::user()->businesses()->find(session('current_business_id'))
            : Auth::user()->businesses()->first();

        if (!$currentBusiness) {
            return redirect()->route('business.index')
                ->with('error', 'Avval biznes yarating');
        }

        // Get marketing channels
        $channels = $currentBusiness->marketingChannels()
            ->latest()
            ->get()
            ->map(function ($channel) {
                return [
                    'id' => $channel->id,
                    'name' => $channel->name,
                    'type' => $channel->type,
                    'status' => $channel->status,
                    'followers_count' => $channel->followers_count,
                    'monthly_reach' => $channel->monthly_reach,
                ];
            });

        // Get recent content posts
        $recentPosts = ContentPost::where('business_id', $currentBusiness->id)
            ->latest()
            ->take(5)
            ->get()
            ->map(function ($post) {
                return [
                    'id' => $post->id,
                    'title' => $post->title,
                    'platform' => $post->platform,
                    'status' => $post->status,
                    'scheduled_at' => $post->scheduled_at?->format('d.m.Y H:i'),
                    'published_at' => $post->published_at?->format('d.m.Y H:i'),
                ];
            });

        // Calculate total spend
        $totalSpend = MarketingSpend::where('business_id', $currentBusiness->id)
            ->whereMonth('created_at', now()->month)
            ->sum('amount');

        // Calculate stats
        $stats = [
            'total_channels' => $channels->count(),
            'active_channels' => $channels->where('status', 'active')->count(),
            'total_spend' => $totalSpend,
            'total_posts' => ContentPost::where('business_id', $currentBusiness->id)->count(),
            'published_posts' => ContentPost::where('business_id', $currentBusiness->id)
                ->where('status', 'published')
                ->count(),
        ];

        return Inertia::render('Business/Marketing/Index', [
            'channels' => $channels,
            'recentPosts' => $recentPosts,
            'stats' => $stats,
            'currentBusiness' => [
                'id' => $currentBusiness->id,
                'name' => $currentBusiness->name,
            ],
        ]);
    }

    /**
     * Show channels page.
     */
    public function channels()
    {
        $currentBusiness = session('current_business_id')
            ? Auth::user()->businesses()->find(session('current_business_id'))
            : Auth::user()->businesses()->first();

        if (!$currentBusiness) {
            return redirect()->route('business.index');
        }

        $channels = $currentBusiness->marketingChannels()
            ->latest()
            ->get()
            ->map(function ($channel) {
                return [
                    'id' => $channel->id,
                    'name' => $channel->name,
                    'type' => $channel->type,
                    'platform' => $channel->platform,
                    'status' => $channel->status,
                    'url' => $channel->url,
                    'followers_count' => $channel->followers_count,
                    'monthly_reach' => $channel->monthly_reach,
                    'engagement_rate' => $channel->engagement_rate,
                    'created_at' => $channel->created_at->format('d.m.Y'),
                ];
            });

        return Inertia::render('Business/Marketing/Channels', [
            'channels' => $channels,
        ]);
    }

    /**
     * Store a new marketing channel.
     */
    public function storeChannel(Request $request)
    {
        $currentBusiness = session('current_business_id')
            ? Auth::user()->businesses()->find(session('current_business_id'))
            : Auth::user()->businesses()->first();

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', 'in:social_media,email,sms,advertising,other'],
            'platform' => ['required', 'string', 'max:100'],
            'url' => ['nullable', 'url', 'max:500'],
            'status' => ['required', 'in:active,inactive,paused'],
            'followers_count' => ['nullable', 'integer', 'min:0'],
            'monthly_reach' => ['nullable', 'integer', 'min:0'],
            'engagement_rate' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'notes' => ['nullable', 'string'],
        ]);

        $validated['business_id'] = $currentBusiness->id;

        MarketingChannel::create($validated);

        return redirect()->back()
            ->with('success', 'Kanal muvaffaqiyatli qo\'shildi!');
    }

    /**
     * Update marketing channel.
     */
    public function updateChannel(Request $request, MarketingChannel $channel)
    {
        $currentBusiness = session('current_business_id')
            ? Auth::user()->businesses()->find(session('current_business_id'))
            : Auth::user()->businesses()->first();

        if ($channel->business_id !== $currentBusiness->id) {
            abort(403);
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', 'in:social_media,email,sms,advertising,other'],
            'platform' => ['required', 'string', 'max:100'],
            'url' => ['nullable', 'url', 'max:500'],
            'status' => ['required', 'in:active,inactive,paused'],
            'followers_count' => ['nullable', 'integer', 'min:0'],
            'monthly_reach' => ['nullable', 'integer', 'min:0'],
            'engagement_rate' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'notes' => ['nullable', 'string'],
        ]);

        $channel->update($validated);

        return redirect()->back()
            ->with('success', 'Kanal muvaffaqiyatli yangilandi!');
    }

    /**
     * Delete marketing channel.
     */
    public function destroyChannel(MarketingChannel $channel)
    {
        $currentBusiness = session('current_business_id')
            ? Auth::user()->businesses()->find(session('current_business_id'))
            : Auth::user()->businesses()->first();

        if ($channel->business_id !== $currentBusiness->id) {
            abort(403);
        }

        $channel->delete();

        return redirect()->back()
            ->with('success', 'Kanal muvaffaqiyatli o\'chirildi!');
    }

    /**
     * Show content calendar.
     */
    public function content()
    {
        $currentBusiness = session('current_business_id')
            ? Auth::user()->businesses()->find(session('current_business_id'))
            : Auth::user()->businesses()->first();

        if (!$currentBusiness) {
            return redirect()->route('business.index');
        }

        $posts = ContentPost::where('business_id', $currentBusiness->id)
            ->latest('scheduled_at')
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
                    'views' => $post->views,
                    'likes' => $post->likes,
                    'comments' => $post->comments,
                    'shares' => $post->shares,
                    'hashtags' => $post->hashtags,
                ];
            });

        return Inertia::render('Business/Marketing/Content', [
            'posts' => $posts,
        ]);
    }

    /**
     * Store a new content post.
     */
    public function storeContent(Request $request)
    {
        $currentBusiness = session('current_business_id')
            ? Auth::user()->businesses()->find(session('current_business_id'))
            : Auth::user()->businesses()->first();

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'content' => ['required', 'string'],
            'platform' => ['required', 'string', 'max:100'],
            'content_type' => ['required', 'in:educational,entertaining,inspirational,promotional,behind_scenes,ugc'],
            'format' => ['required', 'in:short_video,long_video,carousel,single_image,story,text_post,live,poll'],
            'type' => ['nullable', 'string'], // Legacy field
            'status' => ['required', 'in:draft,scheduled,published'],
            'scheduled_at' => ['nullable', 'date'],
            'hashtags' => ['nullable', 'array'],
        ]);

        $validated['business_id'] = $currentBusiness->id;

        // Handle multiple platforms - store as JSON array
        if (is_array($validated['platform'])) {
            $validated['platform'] = json_encode($validated['platform']);
        }

        ContentPost::create($validated);

        return redirect()->back()
            ->with('success', 'Kontent muvaffaqiyatli qo\'shildi!');
    }

    /**
     * Show content post details.
     */
    public function showContent(ContentPost $content)
    {
        $currentBusiness = session('current_business_id')
            ? Auth::user()->businesses()->find(session('current_business_id'))
            : Auth::user()->businesses()->first();

        if ($content->business_id !== $currentBusiness->id) {
            abort(403);
        }

        // Parse platforms if stored as JSON
        $platforms = $content->platform;
        if (is_string($platforms) && str_starts_with($platforms, '[')) {
            $platforms = json_decode($platforms, true);
        }

        return Inertia::render('Business/Marketing/ContentShow', [
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

    /**
     * Show edit form for content post.
     */
    public function editContent(ContentPost $content)
    {
        $currentBusiness = session('current_business_id')
            ? Auth::user()->businesses()->find(session('current_business_id'))
            : Auth::user()->businesses()->first();

        if ($content->business_id !== $currentBusiness->id) {
            abort(403);
        }

        // Parse platforms if stored as JSON
        $platforms = $content->platform;
        if (is_string($platforms) && str_starts_with($platforms, '[')) {
            $platforms = json_decode($platforms, true);
        }

        return Inertia::render('Business/Marketing/ContentEdit', [
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
                'media' => $content->media ?? [],
                'external_url' => $content->external_url,
            ],
        ]);
    }

    /**
     * Update content post.
     */
    public function updateContent(Request $request, ContentPost $content)
    {
        $currentBusiness = session('current_business_id')
            ? Auth::user()->businesses()->find(session('current_business_id'))
            : Auth::user()->businesses()->first();

        if ($content->business_id !== $currentBusiness->id) {
            abort(403);
        }

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'content' => ['required', 'string'],
            'platform' => ['required'],
            'content_type' => ['required', 'in:educational,entertaining,inspirational,promotional,behind_scenes,ugc'],
            'format' => ['required', 'in:short_video,long_video,carousel,single_image,story,text_post,live,poll'],
            'status' => ['required', 'in:draft,scheduled,published'],
            'scheduled_at' => ['nullable', 'date'],
            'hashtags' => ['nullable', 'array'],
            'external_url' => ['nullable', 'url'],
        ]);

        // Handle multiple platforms
        if (is_array($validated['platform'])) {
            $validated['platform'] = json_encode($validated['platform']);
        }

        $content->update($validated);

        return redirect()->route('business.marketing.content.show', $content)
            ->with('success', 'Kontent muvaffaqiyatli yangilandi!');
    }

    /**
     * Delete content post.
     */
    public function deleteContent(ContentPost $content)
    {
        $currentBusiness = session('current_business_id')
            ? Auth::user()->businesses()->find(session('current_business_id'))
            : Auth::user()->businesses()->first();

        if ($content->business_id !== $currentBusiness->id) {
            abort(403);
        }

        $content->delete();

        return redirect()->route('business.marketing.content')
            ->with('success', 'Kontent muvaffaqiyatli o\'chirildi!');
    }
}
