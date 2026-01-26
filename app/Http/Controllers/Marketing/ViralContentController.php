<?php

declare(strict_types=1);

namespace App\Http\Controllers\Marketing;

use App\Http\Controllers\Controller;
use App\Models\ViralContent;
use App\Services\External\ApifyService;
use App\Services\TrendSee\ViralHunterService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\RateLimiter;
use Inertia\Inertia;
use Inertia\Response;

/**
 * ViralContentController - TrendSee Web Dashboard
 *
 * Displays viral Instagram Reels with AI analysis.
 * Uses ViralHunterService for "Fetch Once, Serve Many" architecture.
 */
class ViralContentController extends Controller
{
    private const PER_PAGE = 15; // 5 columns x 3 rows
    private const REFRESH_RATE_LIMIT_KEY = 'viral_refresh';
    private const REFRESH_COOLDOWN_MINUTES = 30;

    private ViralHunterService $viralHunter;

    public function __construct(ViralHunterService $viralHunter)
    {
        $this->viralHunter = $viralHunter;
    }

    /**
     * Display viral content feed with INSTANT VIRAL FEED logic.
     *
     * TrendSee UX: Users see relevant viral content immediately based on their business category.
     * Auto-seeds if no content exists for their niche (bypasses 30-min cooldown for first run).
     *
     * Optimized for Inertia partial reloads (polling):
     * - Partial reloads only return requested props without triggering new jobs
     * - This prevents duplicate API calls during real-time polling
     */
    public function index(Request $request): Response
    {
        $user = $request->user();
        $sortBy = $request->input('sort', 'play_count');
        $niche = $request->input('niche');

        // Check if this is a partial reload (polling)
        $isPartialReload = $request->header('X-Inertia-Partial-Data') !== null;

        // === PARTIAL RELOAD OPTIMIZATION ===
        // For polling requests, only return data from DB without triggering new jobs
        if ($isPartialReload) {
            return $this->handlePartialReload($request, $user, $niche, $sortBy);
        }

        // === FULL PAGE LOAD - INSTANT VIRAL FEED LOGIC ===
        // 1. Get user's business category
        $businessCategory = $this->getUserBusinessCategory($user);
        $recommendedHashtags = $this->viralHunter->getHashtagsForCategory($businessCategory);

        // 2. Check if we have content for this category
        $hasContent = $this->viralHunter->hasContentForCategory($businessCategory);
        $autoSeedTriggered = false;
        $autoSeedMessage = null;
        $isRateLimited = false;

        // Check if API is rate limited
        $apify = app(ApifyService::class);
        if ($apify->isRateLimited()) {
            $isRateLimited = true;
            $autoSeedMessage = 'API limit tugadi. Iltimos, 1 soatdan keyin qayta urinib ko\'ring.';
        }
        // Check if a refresh job is currently in progress (user clicked Refresh button)
        elseif (Cache::has("auto_seed_triggered:{$businessCategory}")) {
            $autoSeedTriggered = true;
            $autoSeedMessage = 'Viral kontentlar yuklanmoqda... Sahifa avtomatik yangilanadi.';
        }
        // 3. Auto-seed if no content exists - SYNC mode for immediate data
        elseif (!$hasContent) {
            $autoSeedKey = "auto_seed_triggered:{$businessCategory}";

            // Only trigger once per 10 minutes per category
            if (!Cache::has($autoSeedKey)) {
                // Use SYNC mode (async=false) for immediate data loading
                $result = $this->viralHunter->autoSeedForCategory($businessCategory, false);
                $autoSeedTriggered = $result['success'];

                // Mark as triggered for 10 minutes
                Cache::put($autoSeedKey, true, 600);

                // Check if we got rate limited during the fetch
                if ($apify->isRateLimited()) {
                    $isRateLimited = true;
                    $autoSeedMessage = 'API limit tugadi. Iltimos, 1 soatdan keyin qayta urinib ko\'ring.';
                }
            }
        }

        // Use ViralHunterService for data fetching
        $viralContents = $this->viralHunter->getViralFeed($niche, $sortBy, self::PER_PAGE)
            ->through(fn ($content) => $this->transformContent($content));

        // Get available niches with counts from service
        $niches = $this->viralHunter->getAvailableNiches();

        // Check if refresh is available
        $canRefresh = $this->canRefresh($request);
        $refreshCooldown = $this->getRefreshCooldown($request);

        // Stats summary from service
        $stats = $this->viralHunter->getStats();

        // Detect panel type from current route or referer for layout
        $panelType = $this->detectPanelType($request);

        return Inertia::render('Marketing/Trends/Index', [
            'viralContents' => $viralContents,
            'niches' => $niches,
            'filters' => [
                'niche' => $niche ?? 'all',
                'sort' => $sortBy,
            ],
            'canRefresh' => $canRefresh,
            'refreshCooldown' => $refreshCooldown,
            'stats' => $stats,
            'panelType' => $panelType,
            // Instant Feed info
            'businessCategory' => $businessCategory,
            'recommendedHashtags' => array_slice($recommendedHashtags, 0, 5),
            'autoSeedTriggered' => $autoSeedTriggered,
            'autoSeedMessage' => $autoSeedMessage,
            'isRateLimited' => $isRateLimited,
        ]);
    }

    /**
     * Handle partial reload requests (real-time polling).
     *
     * Optimized for frequent polling:
     * - Only returns requested props (viralContents, stats, autoSeedMessage)
     * - NO new job dispatching - just reads from DB
     * - Efficient queries with minimal overhead
     */
    private function handlePartialReload(Request $request, $user, ?string $niche, string $sortBy): Response
    {
        $businessCategory = $this->getUserBusinessCategory($user);

        // Efficient data fetch - only what's needed for polling
        $viralContents = $this->viralHunter->getViralFeed($niche, $sortBy, self::PER_PAGE)
            ->through(fn ($content) => $this->transformContent($content));

        // Lightweight stats query (cached)
        $stats = $this->viralHunter->getStats();

        // Check if auto-seed is still in progress
        $autoSeedKey = "auto_seed_triggered:{$businessCategory}";
        $autoSeedMessage = null;

        // Only show message if job was triggered and no data yet
        if (Cache::has($autoSeedKey) && $viralContents->isEmpty()) {
            $autoSeedMessage = 'Viral kontentlar yuklanmoqda, iltimos kuting...';
        }

        return Inertia::render('Marketing/Trends/Index', [
            'viralContents' => $viralContents,
            'stats' => $stats,
            'autoSeedMessage' => $autoSeedMessage,
            // These remain unchanged during polling
            'niches' => Inertia::lazy(fn () => $this->viralHunter->getAvailableNiches()),
            'filters' => ['niche' => $niche ?? 'all', 'sort' => $sortBy],
            'canRefresh' => Inertia::lazy(fn () => $this->canRefresh($request)),
            'refreshCooldown' => Inertia::lazy(fn () => $this->getRefreshCooldown($request)),
            'panelType' => Inertia::lazy(fn () => $this->detectPanelType($request)),
            'businessCategory' => $businessCategory,
            'recommendedHashtags' => Inertia::lazy(fn () => array_slice($this->viralHunter->getHashtagsForCategory($businessCategory), 0, 5)),
            'autoSeedTriggered' => false, // Don't re-trigger on polling
        ]);
    }

    /**
     * Get user's business category from current business.
     */
    private function getUserBusinessCategory($user): string
    {
        if (!$user) {
            return 'general';
        }

        $currentBusiness = $user->currentBusiness;
        if (!$currentBusiness) {
            return 'general';
        }

        // Map business category to config key
        $category = $currentBusiness->category ?? $currentBusiness->industry ?? 'general';

        return strtolower(trim($category));
    }

    /**
     * Show single viral content detail.
     */
    public function show(Request $request, string $id): Response
    {
        $content = ViralContent::findOrFail($id);
        $panelType = $this->detectPanelType($request);

        return Inertia::render('Marketing/Trends/Show', [
            'content' => $this->transformContent($content, true),
            'panelType' => $panelType,
        ]);
    }

    /**
     * Manually trigger viral content refresh (rate-limited).
     * Uses ASYNC mode - dispatches job and returns immediately.
     * User will see results via polling.
     */
    public function refresh(Request $request)
    {
        $key = self::REFRESH_RATE_LIMIT_KEY . ':' . $request->user()->id;

        if (RateLimiter::tooManyAttempts($key, 1)) {
            $seconds = RateLimiter::availableIn($key);

            return back()->with('error', "Iltimos, {$seconds} soniya kuting.");
        }

        // Check if Apify is configured
        $apify = app(ApifyService::class);
        if (!$apify->isConfigured()) {
            return back()->with('error', 'Apify sozlanmagan. Admin bilan bog\'laning.');
        }

        // Check if rate limited by API
        if ($apify->isRateLimited()) {
            return back()->with('error', 'API limit tugadi. Iltimos, 1 soatdan keyin qayta urinib ko\'ring.');
        }

        // Get user's business category for targeted fetch
        $businessCategory = $this->getUserBusinessCategory($request->user());
        $hashtags = $this->viralHunter->getHashtagsForCategory($businessCategory);

        // Set rate limit BEFORE dispatching (3 minutes cooldown)
        RateLimiter::hit($key, 3 * 60);

        // Mark auto-seed as triggered (for polling detection)
        $autoSeedKey = "auto_seed_triggered:{$businessCategory}";
        Cache::put($autoSeedKey, true, 300); // 5 minutes

        // ASYNC - Dispatch job and return immediately
        \App\Jobs\ViralHunterJob::dispatch($hashtags);

        return back()->with('success', 'Viral kontentlar yuklanmoqda... Sahifa avtomatik yangilanadi.');
    }

    /**
     * Get feed metadata (API endpoint).
     */
    public function metadata(Request $request): array
    {
        $niche = $request->input('niche');

        return $this->viralHunter->getFeedMetadata($niche);
    }

    /**
     * Sync fetch viral content (API endpoint for AJAX).
     * Immediately fetches from API and returns results.
     */
    public function syncFetch(Request $request): array
    {
        $user = $request->user();
        $businessCategory = $this->getUserBusinessCategory($user);

        // Check rate limit
        $key = "sync_fetch:{$user->id}";
        if (RateLimiter::tooManyAttempts($key, 1)) {
            $seconds = RateLimiter::availableIn($key);
            return [
                'success' => false,
                'error' => "Iltimos, {$seconds} soniya kuting.",
                'cooldown' => $seconds,
            ];
        }

        // Check if Apify is configured
        $apify = app(ApifyService::class);
        if (!$apify->isConfigured()) {
            return [
                'success' => false,
                'error' => 'Apify sozlanmagan.',
            ];
        }

        // SYNC fetch - immediate data loading
        $result = $this->viralHunter->autoSeedForCategory($businessCategory, false);

        // Set rate limit (2 minutes)
        RateLimiter::hit($key, 120);

        if ($result['success']) {
            $newCount = 0;
            $totalFetched = 0;
            if (isset($result['results'])) {
                foreach ($result['results'] as $hashtagResult) {
                    $newCount += $hashtagResult['new'] ?? 0;
                    $totalFetched += $hashtagResult['fetched'] ?? 0;
                }
            }

            // Get updated content
            $contents = $this->viralHunter->getViralFeed(null, 'play_count', self::PER_PAGE)
                ->through(fn ($content) => $this->transformContent($content));

            return [
                'success' => true,
                'new_count' => $newCount,
                'total_fetched' => $totalFetched,
                'contents' => $contents,
                'stats' => $this->viralHunter->getStats(),
            ];
        }

        return [
            'success' => false,
            'error' => $result['message'] ?? 'Xatolik yuz berdi.',
        ];
    }

    /**
     * Transform content for frontend.
     */
    private function transformContent(ViralContent $content, bool $detailed = false): array
    {
        $data = [
            'id' => $content->id,
            'platform' => $content->platform,
            'platform_id' => $content->platform_id,
            'platform_username' => $content->platform_username,
            'niche' => $content->niche,
            'caption' => $content->caption,
            'caption_summary' => $content->caption_summary,
            'video_url' => $content->video_url,
            'thumbnail_url' => $content->thumbnail_url,
            'permalink' => $content->permalink,

            // Metrics
            'play_count' => $content->play_count,
            'like_count' => $content->like_count,
            'comment_count' => $content->comment_count,
            'formatted_plays' => $this->formatNumber($content->play_count),
            'formatted_likes' => $this->formatNumber($content->like_count),
            'formatted_comments' => $this->formatNumber($content->comment_count),

            // AI Analysis
            'hook_score' => $content->hook_score,
            'ai_summary' => $content->ai_summary,
            'ai_analysis' => $content->ai_analysis_json,

            // Music
            'music_title' => $content->music_title,
            'music_artist' => $content->music_artist,

            // Status
            'is_super_viral' => $content->is_super_viral,
            'viral_level' => $content->viral_level,

            // Dates
            'fetched_at' => $content->fetched_at?->diffForHumans(),
            'analyzed_at' => $content->analyzed_at?->diffForHumans(),
        ];

        if ($detailed) {
            $data['metrics_json'] = $content->metrics_json;
            $data['created_at'] = $content->created_at->format('d.m.Y H:i');
        }

        return $data;
    }

    /**
     * Check if user can trigger refresh.
     */
    private function canRefresh(Request $request): bool
    {
        $key = self::REFRESH_RATE_LIMIT_KEY . ':' . $request->user()->id;
        return !RateLimiter::tooManyAttempts($key, 1);
    }

    /**
     * Get refresh cooldown in seconds.
     */
    private function getRefreshCooldown(Request $request): int
    {
        $key = self::REFRESH_RATE_LIMIT_KEY . ':' . $request->user()->id;
        return RateLimiter::availableIn($key);
    }

    /**
     * Format number for display (1.5M, 120K, etc).
     */
    private function formatNumber(int $number): string
    {
        if ($number >= 1000000) {
            return number_format($number / 1000000, 1) . 'M';
        }

        if ($number >= 1000) {
            return number_format($number / 1000, 1) . 'K';
        }

        return number_format($number);
    }

    /**
     * Detect panel type based on user role for appropriate layout.
     * Users should see viral trends in their own panel's layout.
     */
    private function detectPanelType(Request $request): string
    {
        $user = $request->user();
        if (!$user) {
            return 'marketing';
        }

        // Check Spatie roles in order of priority
        // Admin/Super Admin
        if ($user->hasRole(['admin', 'super_admin'])) {
            return 'admin';
        }

        // Sales Head
        if ($user->hasRole('sales_head')) {
            return 'saleshead';
        }

        // Operator
        if ($user->hasRole(['operator', 'sales_operator'])) {
            return 'operator';
        }

        // Marketing
        if ($user->hasRole('marketing')) {
            return 'marketing';
        }

        // Finance
        if ($user->hasRole('finance')) {
            return 'finance';
        }

        // HR
        if ($user->hasRole('hr')) {
            return 'hr';
        }

        // Business owner fallback - check if user owns any business
        $businessId = session('current_business_id');
        if ($businessId) {
            $business = \App\Models\Business::find($businessId);
            if ($business && $business->user_id === $user->id) {
                return 'business';
            }
        }

        // Default to marketing for any user accessing marketing routes
        return 'marketing';
    }
}
