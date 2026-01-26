<?php

declare(strict_types=1);

namespace App\Services\TrendSee;

use App\Models\ViralContent;
use App\Services\ClaudeAIService;
use App\Services\External\ApifyService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * ViralHunterService - Viral Content Intelligence
 *
 * "Fetch Once, Serve Many" architecture for viral Instagram Reels.
 * Data is fetched globally (not per user) and cached in database.
 *
 * Features:
 * - Deduplication by platform_id
 * - AI analysis for viral content
 * - Configurable viral threshold
 *
 * Uses Apify Instagram Scraper for fast, reliable data fetching.
 */
class ViralHunterService
{
    /**
     * STRICT VIRAL QUALITY GATE - matches ApifyService
     * Only genuinely viral content passes through
     */
    private const VIRAL_PLAYS_THRESHOLD = 50000;   // 50k+ plays
    private const VIRAL_LIKES_THRESHOLD = 2000;    // 2k+ likes
    private const SUPER_VIRAL_THRESHOLD = 100000;  // 100k+ = super viral

    private const PER_PAGE = 15; // 5 columns x 3 rows

    /**
     * Fallback hashtags when niche hashtags return 0 viral content
     * Broader, more popular tags that have higher chance of viral videos
     */
    private const FALLBACK_HASHTAGS = [
        'uzbekistan',
        'tashkent',
        'reelsuz',
        'viral',
        'trending',
    ];

    private ApifyService $apify;
    private ContentAnalyzerService $analyzer;

    public function __construct(
        ApifyService $apify,
        ContentAnalyzerService $analyzer
    ) {
        $this->apify = $apify;
        $this->analyzer = $analyzer;
    }

    /**
     * Get paginated viral feed from database.
     *
     * @param string|null $niche Filter by niche
     * @param string $sortBy Sort field: play_count, hook_score, likes, comments, recent
     * @param int $perPage Items per page
     * @return LengthAwarePaginator
     */
    public function getViralFeed(
        ?string $niche = null,
        string $sortBy = 'play_count',
        int $perPage = self::PER_PAGE
    ): LengthAwarePaginator {
        $query = ViralContent::query()
            ->where('is_processed', true);

        // Filter by niche
        if ($niche && $niche !== 'all') {
            $query->byNiche($niche);
        }

        // Sort
        $query->orderByDesc(match ($sortBy) {
            'hook_score' => 'hook_score',
            'likes' => 'like_count',
            'comments' => 'comment_count',
            'recent' => 'created_at',
            default => 'play_count',
        });

        return $query->paginate($perPage);
    }

    /**
     * Get feed metadata (last updated, total count, etc).
     */
    public function getFeedMetadata(?string $niche = null): array
    {
        $cacheKey = 'viral_feed_metadata' . ($niche ? "_{$niche}" : '');

        return Cache::remember($cacheKey, 1800, function () use ($niche) {
            $query = ViralContent::query();

            if ($niche && $niche !== 'all') {
                $query->byNiche($niche);
            }

            $lastContent = (clone $query)->orderByDesc('fetched_at')->first();

            return [
                'total_count' => $query->count(),
                'viral_count' => (clone $query)->viral(self::VIRAL_PLAYS_THRESHOLD)->count(),
                'super_viral_count' => (clone $query)->superViral()->count(),
                'avg_hook_score' => round((clone $query)->whereNotNull('hook_score')->avg('hook_score') ?? 0, 1),
                'last_updated_at' => $lastContent?->fetched_at?->toDateTimeString(),
                'last_updated_human' => $lastContent?->fetched_at?->diffForHumans(),
            ];
        });
    }

    /**
     * Refresh viral feed for a niche (Called by Cron Job).
     *
     * STRICT QUALITY GATE: Only content with 50k+ plays or 2k+ likes is saved.
     * If niche returns 0 viral content, automatically tries fallback hashtags.
     *
     * @param string $niche Niche/hashtag to fetch
     * @param int $limit Maximum posts to fetch
     * @param bool $tryFallback Whether to try fallback hashtags if no results
     * @return array{success: bool, new: int, duplicates: int, analyzed: int, errors: int}
     */
    public function refreshFeed(string $niche, int $limit = 20, bool $tryFallback = true): array
    {
        Log::info('ViralHunter: Starting feed refresh (STRICT MODE)', [
            'niche' => $niche,
            'min_plays' => self::VIRAL_PLAYS_THRESHOLD,
            'min_likes' => self::VIRAL_LIKES_THRESHOLD,
        ]);

        $stats = [
            'success' => true,
            'fetched' => 0,
            'new' => 0,
            'duplicates' => 0,
            'analyzed' => 0,
            'errors' => 0,
            'skipped' => 0,
        ];

        // Check if API is configured
        if (!$this->apify->isConfigured()) {
            Log::warning('ViralHunter: Apify not configured');
            return array_merge($stats, ['success' => false, 'error' => 'API not configured']);
        }

        // Fetch from Apify (STRICT filtering already applied)
        $posts = $this->apify->fetchHashtagFeed($niche);

        if (empty($posts)) {
            Log::info('ViralHunter: No viral content found for niche', ['niche' => $niche]);

            // FALLBACK STRATEGY: Try global viral hashtags
            if ($tryFallback && !in_array($niche, self::FALLBACK_HASHTAGS)) {
                Log::info('ViralHunter: Trying fallback hashtags');
                return $this->tryFallbackHashtags($limit);
            }

            return array_merge($stats, ['success' => true, 'message' => 'No viral content found']);
        }

        // Limit results if needed
        $posts = array_slice($posts, 0, $limit);
        $stats['fetched'] = count($posts);

        foreach ($posts as $postData) {
            try {
                $processResult = $this->processPost($postData);

                if ($processResult === 'new') {
                    $stats['new']++;
                } elseif ($processResult === 'duplicate') {
                    $stats['duplicates']++;
                } elseif ($processResult === 'analyzed') {
                    $stats['analyzed']++;
                } elseif ($processResult === 'skipped') {
                    $stats['skipped']++;
                }
            } catch (\Exception $e) {
                Log::error('ViralHunter: Error processing post', [
                    'platform_id' => $postData['platform_id'] ?? 'unknown',
                    'error' => $e->getMessage(),
                ]);
                $stats['errors']++;
            }
        }

        // Clear metadata cache
        Cache::forget('viral_feed_metadata');
        Cache::forget("viral_feed_metadata_{$niche}");
        Cache::forget('viral_niches');
        Cache::forget('viral_stats');

        Log::info('ViralHunter: Feed refresh completed', $stats);

        return $stats;
    }

    /**
     * Try fallback hashtags when niche hashtags return no viral content.
     */
    private function tryFallbackHashtags(int $limit = 10): array
    {
        $totalStats = [
            'success' => true,
            'fetched' => 0,
            'new' => 0,
            'duplicates' => 0,
            'analyzed' => 0,
            'errors' => 0,
            'skipped' => 0,
            'fallback_used' => true,
        ];

        foreach (self::FALLBACK_HASHTAGS as $hashtag) {
            $result = $this->refreshFeed($hashtag, $limit, false); // Don't recurse

            $totalStats['fetched'] += $result['fetched'] ?? 0;
            $totalStats['new'] += $result['new'] ?? 0;
            $totalStats['duplicates'] += $result['duplicates'] ?? 0;
            $totalStats['analyzed'] += $result['analyzed'] ?? 0;
            $totalStats['errors'] += $result['errors'] ?? 0;
            $totalStats['skipped'] += $result['skipped'] ?? 0;

            // If we found some viral content, stop trying more fallbacks
            if (($result['new'] ?? 0) > 0 || ($result['analyzed'] ?? 0) > 0) {
                Log::info('ViralHunter: Found viral content via fallback', [
                    'hashtag' => $hashtag,
                    'found' => ($result['new'] ?? 0) + ($result['analyzed'] ?? 0),
                ]);
                break;
            }

            // Small delay between requests
            usleep(500000);
        }

        return $totalStats;
    }

    /**
     * Refresh feed for all configured hashtags.
     */
    public function refreshAllFeeds(): array
    {
        $hashtags = config('viral_niches.general', ['businessuz', 'trenduz']);
        $results = [];

        foreach ($hashtags as $hashtag) {
            try {
                $results[$hashtag] = $this->refreshFeed($hashtag);

                // Delay between requests to avoid rate limits
                sleep(2);
            } catch (\Exception $e) {
                Log::error('ViralHunter: Failed to refresh hashtag', [
                    'hashtag' => $hashtag,
                    'error' => $e->getMessage(),
                ]);
                $results[$hashtag] = ['success' => false, 'error' => $e->getMessage()];
            }
        }

        return $results;
    }

    /**
     * Process a single post (deduplication + save + analyze).
     *
     * Handles both new DTO format (metrics.plays) and legacy format (play_count).
     *
     * @param array $postData Post data from RocketAPI (Clean DTO)
     * @return string 'new', 'duplicate', 'analyzed', or 'skipped'
     */
    private function processPost(array $postData): string
    {
        $platformId = $postData['platform_id'] ?? null;

        if (!$platformId) {
            return 'skipped';
        }

        // Extract metrics from new DTO format or legacy format
        $metrics = $postData['metrics'] ?? [];
        $playCount = $metrics['plays'] ?? $postData['play_count'] ?? 0;
        $likeCount = $metrics['likes'] ?? $postData['like_count'] ?? 0;
        $commentCount = $metrics['comments'] ?? $postData['comment_count'] ?? 0;

        // Build metrics JSON from DTO
        $metricsJson = !empty($metrics) ? $metrics : ($postData['metrics_json'] ?? null);

        // Extract music info from new DTO format
        $musicInfo = $postData['music'] ?? [];
        $musicTitle = $musicInfo['title'] ?? $postData['music_title'] ?? null;
        $musicArtist = $musicInfo['artist'] ?? $postData['music_artist'] ?? null;

        // Handle username (new: 'username', legacy: 'platform_username')
        $username = $postData['username'] ?? $postData['platform_username'] ?? null;

        // === DEDUPLICATION CHECK ===
        $existing = ViralContent::where('platform_id', $platformId)->first();

        if ($existing) {
            // Update metrics for existing post
            $existing->update([
                'play_count' => $playCount > 0 ? $playCount : $existing->play_count,
                'like_count' => $likeCount > 0 ? $likeCount : $existing->like_count,
                'comment_count' => $commentCount > 0 ? $commentCount : $existing->comment_count,
                'metrics_json' => $metricsJson ?? $existing->metrics_json,
            ]);

            // Check if became super viral
            $existing->checkSuperViral(self::SUPER_VIRAL_THRESHOLD);

            return 'duplicate';
        }

        // === STRICT VIRAL QUALITY GATE ===
        // Must have 50k+ plays OR 2k+ likes - NO EXCEPTIONS
        if ($playCount < self::VIRAL_PLAYS_THRESHOLD && $likeCount < self::VIRAL_LIKES_THRESHOLD) {
            Log::debug('ViralHunter: Post rejected by Quality Gate', [
                'platform_id' => $platformId,
                'plays' => $playCount,
                'likes' => $likeCount,
                'threshold_plays' => self::VIRAL_PLAYS_THRESHOLD,
                'threshold_likes' => self::VIRAL_LIKES_THRESHOLD,
            ]);
            return 'skipped';
        }

        // === CREATE NEW CONTENT ===
        $content = ViralContent::create([
            'platform' => 'instagram',
            'platform_id' => $platformId,
            'platform_username' => $username,
            'niche' => $postData['niche'] ?? 'general',
            'caption' => $postData['caption'] ?? null,
            'video_url' => $postData['video_url'] ?? null,
            'thumbnail_url' => $postData['thumbnail_url'] ?? null,
            'permalink' => $postData['permalink'] ?? null,
            'play_count' => $playCount,
            'like_count' => $likeCount,
            'comment_count' => $commentCount,
            'metrics_json' => $metricsJson,
            'music_title' => $musicTitle,
            'music_artist' => $musicArtist,
            'is_super_viral' => $postData['is_super_viral'] ?? ($playCount >= self::SUPER_VIRAL_THRESHOLD),
            'fetched_at' => now(),
        ]);

        // === AI ANALYSIS ===
        try {
            $this->analyzer->analyzeAndSave($content);
            return 'analyzed';
        } catch (\Exception $e) {
            Log::warning('ViralHunter: AI analysis failed, saving without analysis', [
                'content_id' => $content->id,
                'error' => $e->getMessage(),
            ]);
            // Content saved but not analyzed
            return 'new';
        }
    }

    /**
     * Get available niches with counts.
     */
    public function getAvailableNiches(): array
    {
        return Cache::remember('viral_niches_with_counts', 3600, function () {
            $niches = ViralContent::select('niche')
                ->selectRaw('COUNT(*) as count')
                ->whereNotNull('niche')
                ->where('is_processed', true)
                ->groupBy('niche')
                ->orderByDesc('count')
                ->get()
                ->map(fn ($item) => [
                    'value' => $item->niche,
                    'label' => '#' . $item->niche,
                    'count' => $item->count,
                ])
                ->toArray();

            $totalCount = array_sum(array_column($niches, 'count'));

            array_unshift($niches, [
                'value' => 'all',
                'label' => 'Hammasi',
                'count' => $totalCount,
            ]);

            return $niches;
        });
    }

    /**
     * Get statistics for dashboard.
     * Optimized: Single query instead of 6 separate queries.
     */
    public function getStats(): array
    {
        return Cache::remember('viral_hunter_stats', 1800, function () {
            // Single optimized query for all stats
            $stats = \Illuminate\Support\Facades\DB::table('viral_contents')
                ->selectRaw('
                    COUNT(*) as total,
                    SUM(CASE WHEN is_processed = 1 THEN 1 ELSE 0 END) as processed,
                    SUM(CASE WHEN is_super_viral = 1 THEN 1 ELSE 0 END) as super_viral,
                    AVG(CASE WHEN hook_score IS NOT NULL THEN hook_score END) as avg_hook_score,
                    MAX(fetched_at) as last_refresh
                ')
                ->first();

            // Early return for empty table
            if (!$stats || $stats->total == 0) {
                return [
                    'total' => 0,
                    'processed' => 0,
                    'super_viral' => 0,
                    'avg_hook_score' => 0,
                    'top_niche' => 'N/A',
                    'last_refresh' => null,
                ];
            }

            // Get top niche only if we have data
            $topNiche = \Illuminate\Support\Facades\DB::table('viral_contents')
                ->select('niche')
                ->selectRaw('COUNT(*) as count')
                ->whereNotNull('niche')
                ->groupBy('niche')
                ->orderByDesc('count')
                ->limit(1)
                ->first();

            return [
                'total' => (int) $stats->total,
                'processed' => (int) $stats->processed,
                'super_viral' => (int) $stats->super_viral,
                'avg_hook_score' => round((float) ($stats->avg_hook_score ?? 0), 1),
                'top_niche' => $topNiche?->niche ?? 'N/A',
                'last_refresh' => $stats->last_refresh,
            ];
        });
    }

    /**
     * Re-analyze content without AI analysis.
     */
    public function analyzeUnprocessed(int $limit = 20): int
    {
        $contents = ViralContent::unprocessed()
            ->orderByDesc('play_count')
            ->limit($limit)
            ->get();

        $analyzed = 0;

        foreach ($contents as $content) {
            try {
                $this->analyzer->analyzeAndSave($content);
                $analyzed++;

                // Delay between AI calls
                usleep(500000);
            } catch (\Exception $e) {
                Log::warning('ViralHunter: Analysis failed', [
                    'content_id' => $content->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $analyzed;
    }

    // ==================== INSTANT VIRAL FEED (Auto-Seed) ====================

    /**
     * Check if we have content for a specific business category.
     * Used to determine if auto-seeding is needed.
     */
    public function hasContentForCategory(string $businessCategory): bool
    {
        $hashtags = $this->getHashtagsForCategory($businessCategory);

        return ViralContent::whereIn('niche', $hashtags)
            ->where('is_processed', true)
            ->exists();
    }

    /**
     * Get content count for a business category (for quick check).
     */
    public function getContentCountForCategory(string $businessCategory): int
    {
        $cacheKey = "viral_count_{$businessCategory}";

        return Cache::remember($cacheKey, 300, function () use ($businessCategory) {
            $hashtags = $this->getHashtagsForCategory($businessCategory);

            return ViralContent::whereIn('niche', $hashtags)
                ->where('is_processed', true)
                ->count();
        });
    }

    /**
     * Auto-seed viral content for a business category.
     * Called on first visit when no content exists.
     * Bypasses the 30-minute cooldown for initial seeding.
     *
     * @param string $businessCategory Business category from config
     * @param bool $async If true, dispatches job. If false, runs synchronously.
     * @return array{success: bool, message: string, hashtags: array}
     */
    public function autoSeedForCategory(string $businessCategory, bool $async = true): array
    {
        $hashtags = $this->getHashtagsForCategory($businessCategory);

        if (empty($hashtags)) {
            return [
                'success' => false,
                'message' => 'No hashtags configured for this category',
                'hashtags' => [],
            ];
        }

        // Check if API is configured
        if (!$this->apify->isConfigured()) {
            Log::warning('ViralHunter: Auto-seed failed - API not configured', [
                'category' => $businessCategory,
            ]);

            return [
                'success' => false,
                'message' => 'RapidAPI not configured',
                'hashtags' => $hashtags,
            ];
        }

        Log::info('ViralHunter: Auto-seeding for category', [
            'category' => $businessCategory,
            'hashtags' => $hashtags,
            'async' => $async,
        ]);

        if ($async) {
            // Dispatch job for background processing
            \App\Jobs\ViralHunterJob::dispatch($hashtags);

            return [
                'success' => true,
                'message' => 'Auto-seed job dispatched',
                'hashtags' => $hashtags,
            ];
        }

        // Synchronous seeding (for testing or immediate need)
        $results = [];
        foreach (array_slice($hashtags, 0, 3) as $hashtag) { // Limit to 3 hashtags for sync
            try {
                $results[$hashtag] = $this->refreshFeed($hashtag, 10);
                usleep(500000); // 0.5s delay between requests
            } catch (\Exception $e) {
                Log::error('ViralHunter: Auto-seed hashtag failed', [
                    'hashtag' => $hashtag,
                    'error' => $e->getMessage(),
                ]);
                $results[$hashtag] = ['success' => false, 'error' => $e->getMessage()];
            }
        }

        // Clear caches
        Cache::forget("viral_count_{$businessCategory}");
        Cache::forget('viral_hunter_stats');
        Cache::forget('viral_niches_with_counts');

        return [
            'success' => true,
            'message' => 'Auto-seed completed',
            'hashtags' => $hashtags,
            'results' => $results,
        ];
    }

    /**
     * Get hashtags for a business category from config.
     *
     * @param string $businessCategory Business category (e.g., 'education', 'retail')
     * @return array<string> Array of hashtags (without #)
     */
    public function getHashtagsForCategory(string $businessCategory): array
    {
        $category = strtolower(trim($businessCategory));

        // Try exact match first
        $hashtags = config("viral_niches.{$category}");

        if ($hashtags) {
            return $hashtags;
        }

        // Try to find partial match
        $allNiches = config('viral_niches', []);
        foreach ($allNiches as $key => $tags) {
            if (str_contains($category, $key) || str_contains($key, $category)) {
                return $tags;
            }
        }

        // Fallback to default
        return config('viral_niches.default', ['businessuz', 'trenduz']);
    }

    /**
     * Map business category to primary niche for filtering.
     */
    public function mapCategoryToNiche(string $businessCategory): string
    {
        $hashtags = $this->getHashtagsForCategory($businessCategory);

        return $hashtags[0] ?? 'businessuz';
    }

    /**
     * Get all configured niches (for admin panel).
     */
    public static function getAllConfiguredNiches(): array
    {
        return array_keys(config('viral_niches', []));
    }
}
