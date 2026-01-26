<?php

declare(strict_types=1);

namespace App\Services\External;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

/**
 * ApifyService - Instagram Scraper via Apify
 *
 * Fetches viral Instagram reels/posts using Apify's instagram-scraper actor.
 * Uses SYNCHRONOUS endpoint for instant results.
 *
 * Cost Optimization:
 * - searchLimit: 1 (one hashtag per request)
 * - resultsLimit: 30 (enough for viral filtering)
 * - Uses Apify proxy to avoid IP bans
 *
 * @see https://apify.com/apify/instagram-scraper
 */
class ApifyService
{
    private string $apiToken;
    private string $actorId = 'apify~instagram-scraper';
    private string $baseUrl = 'https://api.apify.com/v2';

    /**
     * STRICT VIRAL QUALITY GATE
     *
     * Ruthless filtering - only truly viral content passes.
     * Better to show 3 high-quality viral videos than 50 garbage videos with 0 views.
     */

    /**
     * Minimum plays for video - STRICT threshold
     * (50k+ plays = genuinely viral content)
     */
    private const MIN_VIRAL_PLAYS = 50000;

    /**
     * Minimum likes for any content type
     * (2k+ likes = high engagement, even if views hidden)
     */
    private const MIN_VIRAL_LIKES = 2000;

    /**
     * Super viral threshold - mega trending content
     */
    private const SUPER_VIRAL_PLAYS = 100000;

    /**
     * Request timeout in seconds
     */
    private const TIMEOUT_SECONDS = 120;

    /**
     * Results limit per hashtag (increased to find more viral hits)
     */
    private const RESULTS_LIMIT = 60;

    public function __construct()
    {
        $this->apiToken = config('services.apify.token') ?? '';
    }

    /**
     * Fetch viral reels/videos for a hashtag.
     *
     * Uses Apify's SYNCHRONOUS endpoint for instant results.
     * Filters only videos with MIN_VIRAL_VIEWS+ views.
     *
     * @param string $hashtag Hashtag without # prefix
     * @return array Array of transformed viral video posts (sorted by views DESC)
     */
    public function fetchHashtagFeed(string $hashtag): array
    {
        $hashtag = ltrim($hashtag, '#');

        if (empty($this->apiToken)) {
            Log::error('Apify: API token not configured');
            return [];
        }

        // Check rate limit cache (to avoid excessive API calls)
        $rateLimitKey = "apify_rate_limit";
        if (Cache::has($rateLimitKey)) {
            Log::warning('Apify: Rate limited, skipping request', ['hashtag' => $hashtag]);
            return [];
        }

        try {
            Log::info('Apify: Fetching hashtag feed (STRICT MODE)', [
                'hashtag' => $hashtag,
                'results_limit' => self::RESULTS_LIMIT,
                'min_plays' => self::MIN_VIRAL_PLAYS,
                'min_likes' => self::MIN_VIRAL_LIKES,
            ]);

            // Build the synchronous endpoint URL
            $url = "{$this->baseUrl}/acts/{$this->actorId}/run-sync-get-dataset-items";

            // Payload for Instagram Scraper - directUrls method
            // This returns posts from the hashtag explore page
            $payload = [
                'directUrls' => ["https://www.instagram.com/explore/tags/{$hashtag}/"],
                'resultsLimit' => self::RESULTS_LIMIT,
                'resultsType' => 'posts',
                'addParentData' => false,
                'proxy' => [
                    'useApifyProxy' => true,
                ],
            ];

            $response = Http::timeout(self::TIMEOUT_SECONDS)
                ->withToken($this->apiToken)
                ->withHeaders([
                    'Content-Type' => 'application/json',
                ])
                ->post($url, $payload);

            // Handle rate limiting (402 = payment required, 429 = too many requests)
            if ($response->status() === 402) {
                Log::error('Apify: Insufficient credits (402)', ['hashtag' => $hashtag]);
                Cache::put($rateLimitKey, true, 3600);
                return [];
            }

            if ($response->status() === 429) {
                Log::warning('Apify: Rate limit hit (429)', ['hashtag' => $hashtag]);
                Cache::put($rateLimitKey, true, 300); // 5 minutes
                return [];
            }

            if ($response->failed()) {
                Log::error('Apify: Request failed', [
                    'hashtag' => $hashtag,
                    'status' => $response->status(),
                    'body' => substr($response->body(), 0, 500),
                ]);
                return [];
            }

            $data = $response->json();

            if (!is_array($data)) {
                Log::warning('Apify: Invalid response format', ['hashtag' => $hashtag]);
                return [];
            }

            // Handle nested posts structure (Apify may return hashtag metadata with nested posts)
            if (!empty($data)) {
                $firstItem = $data[0] ?? null;
                if ($firstItem && (isset($firstItem['posts']) || isset($firstItem['latestPosts']) || isset($firstItem['topPosts']))) {
                    $data = array_merge(
                        $firstItem['posts'] ?? [],
                        $firstItem['latestPosts'] ?? [],
                        $firstItem['topPosts'] ?? []
                    );
                }
            }

            Log::info('Apify: Response received', [
                'hashtag' => $hashtag,
                'items_count' => count($data),
            ]);

            // Transform and filter the response
            $posts = $this->transformResponse($data, $hashtag);

            // Filter only viral videos
            $viralPosts = $this->filterViralContent($posts);

            // Sort by engagement DESC (plays for videos, likes for images)
            usort($viralPosts, function($a, $b) {
                $aScore = ($a['metrics']['plays'] ?? 0) + ($a['metrics']['likes'] ?? 0);
                $bScore = ($b['metrics']['plays'] ?? 0) + ($b['metrics']['likes'] ?? 0);
                return $bScore <=> $aScore;
            });

            Log::info('Apify: Viral content filtered', [
                'hashtag' => $hashtag,
                'raw_count' => count($posts),
                'viral_count' => count($viralPosts),
            ]);

            return $viralPosts;

        } catch (\Exception $e) {
            Log::error('Apify: Exception', [
                'hashtag' => $hashtag,
                'error' => $e->getMessage(),
            ]);
            return [];
        }
    }

    /**
     * STRICT VIRAL QUALITY GATE
     *
     * Ruthless filtering - ONLY truly viral videos pass:
     * - Must be a VIDEO (no images, no carousels)
     * - Must have 50k+ plays OR 2k+ likes
     *
     * Better to show 3 high-quality viral videos than 50 garbage.
     */
    private function filterViralContent(array $posts): array
    {
        $passed = [];
        $rejectedNotVideo = 0;
        $rejectedLowEngagement = 0;
        $maxPlays = 0;
        $maxLikes = 0;

        foreach ($posts as $post) {
            $plays = $post['metrics']['plays'] ?? 0;
            $likes = $post['metrics']['likes'] ?? 0;
            $isVideo = $post['is_video'] ?? false;

            // Track max values for debugging
            $maxPlays = max($maxPlays, $plays);
            $maxLikes = max($maxLikes, $likes);

            // CHECK 1: Must be a VIDEO - skip images and carousels
            if (!$isVideo) {
                $rejectedNotVideo++;
                continue;
            }

            // CHECK 2: THE VIRAL THRESHOLD
            // Accept if plays > 50k OR likes > 2k (sometimes views are hidden but likes are high)
            if ($plays < self::MIN_VIRAL_PLAYS && $likes < self::MIN_VIRAL_LIKES) {
                $rejectedLowEngagement++;
                continue; // SKIP THIS TRASH
            }

            // PASSED THE STRICT QUALITY GATE
            $passed[] = $post;
        }

        Log::info('Apify: STRICT Quality Gate results', [
            'total_input' => \count($posts),
            'passed' => \count($passed),
            'rejected_not_video' => $rejectedNotVideo,
            'rejected_low_engagement' => $rejectedLowEngagement,
            'max_plays_seen' => $maxPlays,
            'max_likes_seen' => $maxLikes,
            'threshold_plays' => self::MIN_VIRAL_PLAYS,
            'threshold_likes' => self::MIN_VIRAL_LIKES,
        ]);

        return $passed;
    }

    /**
     * Transform Apify response to our standard format.
     */
    private function transformResponse(array $items, string $hashtag): array
    {
        $posts = [];

        foreach ($items as $item) {
            $transformed = $this->transformItem($item, $hashtag);
            if ($transformed) {
                $posts[] = $transformed;
            }
        }

        return $posts;
    }

    /**
     * Transform a single Apify item to our format.
     *
     * Apify instagram-scraper returns items with these fields:
     * - id, shortCode, type, url, videoUrl, displayUrl
     * - videoViewCount, likesCount, commentsCount
     * - caption, ownerUsername
     * - musicInfo (for reels)
     */
    private function transformItem(array $item, string $hashtag): ?array
    {
        // Get platform ID
        $platformId = $item['id'] ?? $item['shortCode'] ?? null;
        if (!$platformId) {
            return null;
        }

        // Determine if video - check type and productType
        $type = $item['type'] ?? '';
        $productType = $item['productType'] ?? '';
        $isVideo = in_array($type, ['Video', 'Reel', 'Clip']) ||
                   in_array($productType, ['clips', 'reels', 'igtv']) ||
                   !empty($item['videoUrl']) ||
                   ($item['isVideo'] ?? false);

        // Get metrics - try multiple field names
        $viewCount = (int) ($item['videoViewCount'] ?? $item['viewCount'] ?? $item['videoPlayCount'] ?? $item['playCount'] ?? 0);
        $likeCount = (int) ($item['likesCount'] ?? $item['likeCount'] ?? 0);
        $commentCount = (int) ($item['commentsCount'] ?? $item['commentCount'] ?? 0);

        // Build permalink
        $shortcode = $item['shortCode'] ?? $item['shortcode'] ?? '';
        $permalink = $shortcode
            ? ($isVideo ? "https://www.instagram.com/reel/{$shortcode}/" : "https://www.instagram.com/p/{$shortcode}/")
            : ($item['url'] ?? null);

        // Get username
        $username = $item['ownerUsername'] ?? $item['owner']['username'] ?? null;

        // Get video/image URLs
        $videoUrl = $item['videoUrl'] ?? null;
        $thumbnailUrl = $item['displayUrl'] ?? $item['thumbnailUrl'] ?? $item['previewUrl'] ?? null;

        // Get caption
        $caption = $item['caption'] ?? null;

        // Get music info
        $musicTitle = $item['musicInfo']['title'] ?? null;
        $musicArtist = $item['musicInfo']['artist'] ?? $item['musicInfo']['author'] ?? null;

        return [
            'platform' => 'instagram',
            'platform_id' => (string) $platformId,
            'platform_username' => $username,
            'niche' => $hashtag,
            'caption' => $caption,
            'video_url' => $videoUrl,
            'thumbnail_url' => $thumbnailUrl,
            'permalink' => $permalink,
            'metrics' => [
                'plays' => $viewCount,
                'likes' => $likeCount,
                'comments' => $commentCount,
                'shares' => 0,
            ],
            'music' => [
                'title' => $musicTitle,
                'artist' => $musicArtist,
            ],
            'is_video' => $isVideo,
            'is_super_viral' => $viewCount >= self::SUPER_VIRAL_PLAYS,
        ];
    }

    /**
     * Check if API is configured.
     */
    public function isConfigured(): bool
    {
        return !empty($this->apiToken);
    }

    /**
     * Check if API is currently rate limited.
     */
    public function isRateLimited(): bool
    {
        return Cache::has('apify_rate_limit');
    }

    /**
     * Clear rate limit cache (for testing).
     */
    public function clearRateLimit(): void
    {
        Cache::forget('apify_rate_limit');
    }

    /**
     * Get API name for debugging.
     */
    public function getApiName(): string
    {
        return 'Apify Instagram Scraper';
    }

    /**
     * Get minimum viral plays threshold.
     */
    public function getMinViralPlays(): int
    {
        return self::MIN_VIRAL_PLAYS;
    }

    /**
     * Get minimum viral likes threshold.
     */
    public function getMinViralLikes(): int
    {
        return self::MIN_VIRAL_LIKES;
    }
}
