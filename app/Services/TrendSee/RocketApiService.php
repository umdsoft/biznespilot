<?php

declare(strict_types=1);

namespace App\Services\TrendSee;

use Carbon\Carbon;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * RocketApiService - "The Hunter"
 *
 * External API communication layer for Hybrid Intelligence Engine.
 * Fetches viral Instagram Reels and competitor data using RapidAPI.
 *
 * CRITICAL: This service transforms raw API responses into clean DTOs.
 * No raw JSON should leak to other parts of the system.
 *
 * Provider: RapidAPI (rocketapi-for-instagram / instagram-bulk-scraper)
 */
class RocketApiService
{
    // ==========================================
    // API CONFIGURATION
    // ==========================================

    /**
     * Primary API endpoint (rocketapi-for-instagram).
     * Fallback to instagram-bulk-scraper if primary fails.
     */
    private const PRIMARY_API_HOST = 'rocketapi-for-instagram.p.rapidapi.com';
    private const PRIMARY_API_URL = 'https://rocketapi-for-instagram.p.rapidapi.com';

    private const FALLBACK_API_HOST = 'instagram-bulk-scraper-latest.p.rapidapi.com';
    private const FALLBACK_API_URL = 'https://instagram-bulk-scraper-latest.p.rapidapi.com';

    /**
     * Strict timeout to prevent hanging jobs.
     */
    private const REQUEST_TIMEOUT = 15; // seconds

    /**
     * Retry configuration.
     */
    private const RETRY_ATTEMPTS = 2;
    private const RETRY_DELAY_MS = 1000;

    // ==========================================
    // FILTERING THRESHOLDS
    // ==========================================

    /**
     * Minimum play count to consider content as "viral".
     * Filters out noise and low-engagement content.
     */
    private const MIN_PLAY_COUNT = 10000;

    /**
     * Threshold for "super viral" content (500K+ plays).
     */
    private const SUPER_VIRAL_THRESHOLD = 500000;

    /**
     * Instagram media type constants.
     */
    private const MEDIA_TYPE_IMAGE = 1;
    private const MEDIA_TYPE_VIDEO = 2;  // Reels/Videos
    private const MEDIA_TYPE_CAROUSEL = 8;

    // ==========================================
    // CACHING
    // ==========================================

    private const CACHE_TTL_HASHTAG = 3600;  // 1 hour for hashtag feeds
    private const CACHE_TTL_USER = 7200;     // 2 hours for user stats

    // ==========================================
    // PROPERTIES
    // ==========================================

    private string $apiKey;
    private string $activeHost;
    private string $activeUrl;

    public function __construct()
    {
        $this->apiKey = config('services.rapidapi.key') ?? '';

        // Default to primary API
        $this->activeHost = self::PRIMARY_API_HOST;
        $this->activeUrl = self::PRIMARY_API_URL;
    }

    // ==========================================
    // PUBLIC METHODS
    // ==========================================

    /**
     * Fetch viral videos from a hashtag feed.
     *
     * Implements strict filtering:
     * - Only videos (media_type == 2)
     * - Only content with 10K+ plays
     *
     * @param string $hashtag Hashtag without # symbol
     * @param int $limit Maximum items to fetch (API limit)
     * @return array{success: bool, data: array, meta: array, error?: string}
     */
    public function fetchHashtagFeed(string $hashtag, int $limit = 30): array
    {
        $hashtag = $this->sanitizeHashtag($hashtag);

        if (!$this->isConfigured()) {
            return $this->errorResponse('API key not configured');
        }

        $cacheKey = "rocketapi:hashtag:{$hashtag}:{$limit}";

        // Cache-first strategy
        if ($cached = Cache::get($cacheKey)) {
            Log::debug('RocketAPI: Cache hit', ['hashtag' => $hashtag]);
            return $cached;
        }

        try {
            $response = $this->makeRequest('/hashtag/get_media', [
                'hashtag' => $hashtag,
                'count' => $limit,
            ]);

            if (!$response['success']) {
                // Try fallback API
                return $this->fetchHashtagFeedFallback($hashtag, $limit, $cacheKey);
            }

            $rawItems = $this->extractItems($response['data']);
            $mappedItems = $this->mapAndFilterHashtagItems($rawItems, $hashtag);

            $result = [
                'success' => true,
                'data' => $mappedItems,
                'meta' => [
                    'hashtag' => $hashtag,
                    'total_raw' => count($rawItems),
                    'total_filtered' => count($mappedItems),
                    'filter_applied' => 'media_type=2, plays>' . self::MIN_PLAY_COUNT,
                    'fetched_at' => now()->toIso8601String(),
                    'api_host' => $this->activeHost,
                ],
            ];

            // Cache successful result
            Cache::put($cacheKey, $result, self::CACHE_TTL_HASHTAG);

            Log::info('RocketAPI: Hashtag feed fetched', [
                'hashtag' => $hashtag,
                'raw' => count($rawItems),
                'filtered' => count($mappedItems),
            ]);

            return $result;

        } catch (ConnectionException $e) {
            Log::error('RocketAPI: Connection timeout', [
                'hashtag' => $hashtag,
                'error' => $e->getMessage(),
            ]);
            return $this->errorResponse('API connection timeout');

        } catch (RequestException $e) {
            Log::error('RocketAPI: Request failed', [
                'hashtag' => $hashtag,
                'status' => $e->response?->status(),
                'error' => $e->getMessage(),
            ]);
            return $this->errorResponse('API request failed: ' . $e->response?->status());

        } catch (\Exception $e) {
            Log::error('RocketAPI: Unexpected error', [
                'hashtag' => $hashtag,
                'error' => $e->getMessage(),
            ]);
            return $this->errorResponse($e->getMessage());
        }
    }

    /**
     * Fetch user statistics for competitor analysis.
     *
     * Returns standardized user metrics.
     *
     * @param string $username Instagram username without @
     * @return array{success: bool, data: array, error?: string}
     */
    public function fetchUserStats(string $username): array
    {
        $username = $this->sanitizeUsername($username);

        if (!$this->isConfigured()) {
            return $this->errorResponse('API key not configured');
        }

        $cacheKey = "rocketapi:user:{$username}";

        // Cache-first
        if ($cached = Cache::get($cacheKey)) {
            Log::debug('RocketAPI: User cache hit', ['username' => $username]);
            return $cached;
        }

        try {
            $response = $this->makeRequest('/user/get_info', [
                'username' => $username,
            ]);

            if (!$response['success']) {
                return $this->fetchUserStatsFallback($username, $cacheKey);
            }

            $userData = $this->extractUserData($response['data']);

            if (!$userData) {
                return $this->errorResponse('User not found or private account');
            }

            $result = [
                'success' => true,
                'data' => $this->mapUserStats($userData, $username),
            ];

            Cache::put($cacheKey, $result, self::CACHE_TTL_USER);

            Log::info('RocketAPI: User stats fetched', [
                'username' => $username,
                'followers' => $result['data']['follower_count'] ?? 0,
            ]);

            return $result;

        } catch (\Exception $e) {
            Log::error('RocketAPI: User fetch error', [
                'username' => $username,
                'error' => $e->getMessage(),
            ]);
            return $this->errorResponse($e->getMessage());
        }
    }

    /**
     * Fetch recent posts from a user (for competitor content analysis).
     *
     * @param string $username Instagram username
     * @param int $limit Number of posts to fetch
     * @return array{success: bool, data: array, error?: string}
     */
    public function fetchUserPosts(string $username, int $limit = 12): array
    {
        $username = $this->sanitizeUsername($username);

        if (!$this->isConfigured()) {
            return $this->errorResponse('API key not configured');
        }

        try {
            $response = $this->makeRequest('/user/get_media', [
                'username' => $username,
                'count' => $limit,
            ]);

            if (!$response['success']) {
                // Try fallback
                $this->switchToFallbackApi();

                $response = Http::withHeaders($this->getHeaders())
                    ->timeout(self::REQUEST_TIMEOUT)
                    ->get($this->activeUrl . '/user_posts_v2', [
                        'username' => $username,
                        'amount' => $limit,
                    ]);

                if ($response->failed()) {
                    return $this->errorResponse('Failed to fetch user posts');
                }

                $response = ['success' => true, 'data' => $response->json()];
            }

            $rawItems = $this->extractItems($response['data']);
            $mappedItems = $this->mapUserPosts($rawItems, $username);

            return [
                'success' => true,
                'data' => $mappedItems,
                'meta' => [
                    'username' => $username,
                    'total_posts' => count($mappedItems),
                ],
            ];

        } catch (\Exception $e) {
            Log::error('RocketAPI: User posts error', [
                'username' => $username,
                'error' => $e->getMessage(),
            ]);
            return $this->errorResponse($e->getMessage());
        }
    }

    /**
     * Legacy method - wrapper for fetchHashtagFeed.
     * Kept for backward compatibility.
     */
    public function fetchViralReels(string $hashtag, int $limit = 20): array
    {
        return $this->fetchHashtagFeed($hashtag, $limit);
    }

    // ==========================================
    // DATA MAPPING (Transformation Layer)
    // ==========================================

    /**
     * Map and filter hashtag feed items.
     *
     * STRICT FILTERING:
     * 1. Only videos (media_type == 2)
     * 2. Only items with 10K+ plays
     *
     * @param array $items Raw API items
     * @param string $hashtag Source hashtag for niche tagging
     * @return array Clean mapped items
     */
    private function mapAndFilterHashtagItems(array $items, string $hashtag): array
    {
        $mapped = [];

        foreach ($items as $item) {
            // FILTER 1: Only videos/reels
            $mediaType = $this->extractMediaType($item);
            if ($mediaType !== self::MEDIA_TYPE_VIDEO) {
                continue;
            }

            // FILTER 2: Minimum play count
            $playCount = $this->extractPlayCount($item);
            if ($playCount < self::MIN_PLAY_COUNT) {
                continue;
            }

            // Map to clean DTO
            $mapped[] = $this->mapToViralVideoDTO($item, $hashtag);
        }

        // Sort by play count (most viral first)
        usort($mapped, fn($a, $b) => $b['metrics']['plays'] <=> $a['metrics']['plays']);

        return $mapped;
    }

    /**
     * Map raw API item to Clean Viral Video DTO.
     *
     * This is the single source of truth for video data structure.
     *
     * @param array $item Raw API item
     * @param string $niche Source niche/hashtag
     * @return array Clean DTO
     */
    private function mapToViralVideoDTO(array $item, string $niche): array
    {
        $playCount = $this->extractPlayCount($item);

        return [
            // Identifiers
            'platform_id' => (string) ($item['pk'] ?? $item['id'] ?? ''),
            'shortcode' => (string) ($item['code'] ?? $item['shortcode'] ?? ''),

            // Media URLs
            'video_url' => $this->extractVideoUrl($item),
            'thumbnail_url' => $this->extractThumbnailUrl($item),

            // Content
            'caption' => $this->extractCaption($item),
            'niche' => $niche,

            // User info
            'username' => $this->extractUsername($item),

            // Metrics (standardized)
            'metrics' => [
                'plays' => $playCount,
                'likes' => (int) ($item['like_count'] ?? $item['likes_count'] ?? 0),
                'comments' => (int) ($item['comment_count'] ?? $item['comments_count'] ?? 0),
                'shares' => (int) ($item['share_count'] ?? 0),
                'saves' => (int) ($item['save_count'] ?? 0),
            ],

            // Music info (important for Reels)
            'music' => $this->extractMusicInfo($item),

            // Timestamps
            'published_at' => $this->extractPublishedAt($item),
            'fetched_at' => now()->toIso8601String(),

            // Computed fields
            'is_super_viral' => $playCount >= self::SUPER_VIRAL_THRESHOLD,
            'permalink' => $this->buildPermalink($item),
        ];
    }

    /**
     * Map user data to standardized stats DTO.
     *
     * @param array $userData Raw user data
     * @param string $username Username for reference
     * @return array Clean user stats
     */
    private function mapUserStats(array $userData, string $username): array
    {
        return [
            'username' => $username,
            'user_id' => (string) ($userData['pk'] ?? $userData['id'] ?? ''),
            'full_name' => (string) ($userData['full_name'] ?? ''),
            'biography' => (string) ($userData['biography'] ?? ''),
            'profile_pic_url' => $userData['profile_pic_url_hd'] ?? $userData['profile_pic_url'] ?? null,

            // Core metrics
            'follower_count' => (int) ($userData['follower_count'] ?? $userData['edge_followed_by']['count'] ?? 0),
            'following_count' => (int) ($userData['following_count'] ?? $userData['edge_follow']['count'] ?? 0),
            'media_count' => (int) ($userData['media_count'] ?? $userData['edge_owner_to_timeline_media']['count'] ?? 0),

            // Account info
            'is_verified' => (bool) ($userData['is_verified'] ?? false),
            'is_business' => (bool) ($userData['is_business_account'] ?? $userData['is_business'] ?? false),
            'is_private' => (bool) ($userData['is_private'] ?? false),
            'category' => $userData['category_name'] ?? $userData['category'] ?? null,
            'external_url' => $userData['external_url'] ?? null,

            // Computed
            'engagement_rate' => $this->calculateEngagementRate($userData),
            'fetched_at' => now()->toIso8601String(),
        ];
    }

    /**
     * Map user posts to standardized format.
     */
    private function mapUserPosts(array $items, string $username): array
    {
        $mapped = [];

        foreach ($items as $item) {
            $mediaType = $this->extractMediaType($item);

            $mapped[] = [
                'platform_id' => (string) ($item['pk'] ?? $item['id'] ?? ''),
                'shortcode' => (string) ($item['code'] ?? $item['shortcode'] ?? ''),
                'media_type' => $mediaType,
                'is_video' => $mediaType === self::MEDIA_TYPE_VIDEO,
                'caption' => $this->extractCaption($item),
                'thumbnail_url' => $this->extractThumbnailUrl($item),
                'video_url' => $mediaType === self::MEDIA_TYPE_VIDEO ? $this->extractVideoUrl($item) : null,
                'metrics' => [
                    'plays' => $this->extractPlayCount($item),
                    'likes' => (int) ($item['like_count'] ?? 0),
                    'comments' => (int) ($item['comment_count'] ?? 0),
                ],
                'published_at' => $this->extractPublishedAt($item),
                'permalink' => $this->buildPermalink($item),
            ];
        }

        return $mapped;
    }

    // ==========================================
    // DATA EXTRACTION HELPERS
    // ==========================================

    /**
     * Extract media type from various API response formats.
     */
    private function extractMediaType(array $item): int
    {
        return (int) ($item['media_type'] ?? $item['type'] ?? self::MEDIA_TYPE_IMAGE);
    }

    /**
     * Extract play count from various API response formats.
     */
    private function extractPlayCount(array $item): int
    {
        return (int) (
            $item['play_count']
            ?? $item['view_count']
            ?? $item['video_view_count']
            ?? $item['video_play_count']
            ?? 0
        );
    }

    /**
     * Extract video URL from various nested structures.
     */
    private function extractVideoUrl(array $item): ?string
    {
        return $item['video_url']
            ?? $item['video_versions'][0]['url']
            ?? $item['clips_metadata']['original_sound_info']['progressive_download_url']
            ?? null;
    }

    /**
     * Extract thumbnail URL.
     */
    private function extractThumbnailUrl(array $item): ?string
    {
        return $item['thumbnail_url']
            ?? $item['image_versions2']['candidates'][0]['url']
            ?? $item['display_url']
            ?? $item['thumbnail_src']
            ?? null;
    }

    /**
     * Extract and clean caption text.
     */
    private function extractCaption(array $item): string
    {
        $caption = $item['caption']['text']
            ?? $item['caption']
            ?? $item['edge_media_to_caption']['edges'][0]['node']['text']
            ?? '';

        return is_string($caption) ? trim($caption) : '';
    }

    /**
     * Extract username from nested structures.
     */
    private function extractUsername(array $item): ?string
    {
        return $item['user']['username']
            ?? $item['owner']['username']
            ?? null;
    }

    /**
     * Extract music information for Reels.
     */
    private function extractMusicInfo(array $item): ?array
    {
        $musicInfo = $item['clips_metadata']['music_info']['music_asset_info']
            ?? $item['music_metadata']['music_info']['music_asset_info']
            ?? null;

        if (!$musicInfo) {
            return null;
        }

        return [
            'title' => $musicInfo['title'] ?? null,
            'artist' => $musicInfo['display_artist'] ?? $musicInfo['artist_name'] ?? null,
            'audio_id' => $musicInfo['audio_cluster_id'] ?? $musicInfo['audio_id'] ?? null,
        ];
    }

    /**
     * Extract and parse published timestamp.
     */
    private function extractPublishedAt(array $item): ?string
    {
        $timestamp = $item['taken_at']
            ?? $item['taken_at_timestamp']
            ?? $item['timestamp']
            ?? null;

        if (!$timestamp) {
            return null;
        }

        try {
            return Carbon::createFromTimestamp($timestamp)->toIso8601String();
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Build Instagram permalink from shortcode.
     */
    private function buildPermalink(array $item): ?string
    {
        $code = $item['code'] ?? $item['shortcode'] ?? null;

        if (!$code) {
            return null;
        }

        return "https://www.instagram.com/reel/{$code}/";
    }

    /**
     * Extract items array from various API response structures.
     */
    private function extractItems(array $data): array
    {
        return $data['response']['body']['items']
            ?? $data['data']['items']
            ?? $data['items']
            ?? $data['data']
            ?? $data['edges']
            ?? [];
    }

    /**
     * Extract user data from API response.
     */
    private function extractUserData(array $data): ?array
    {
        return $data['response']['body']['user']
            ?? $data['data']['user']
            ?? $data['user']
            ?? $data['graphql']['user']
            ?? null;
    }

    /**
     * Calculate engagement rate for a user.
     */
    private function calculateEngagementRate(array $userData): ?float
    {
        $followers = (int) ($userData['follower_count'] ?? 0);

        if ($followers < 100) {
            return null;
        }

        // Would need recent posts to calculate accurately
        // For now, return null (to be calculated by CompetitorSpyService)
        return null;
    }

    // ==========================================
    // HTTP & API HELPERS
    // ==========================================

    /**
     * Make HTTP request to API.
     */
    private function makeRequest(string $endpoint, array $params): array
    {
        $response = Http::withHeaders($this->getHeaders())
            ->timeout(self::REQUEST_TIMEOUT)
            ->retry(self::RETRY_ATTEMPTS, self::RETRY_DELAY_MS)
            ->get($this->activeUrl . $endpoint, $params);

        if ($response->failed()) {
            return [
                'success' => false,
                'status' => $response->status(),
                'error' => $response->body(),
            ];
        }

        return [
            'success' => true,
            'data' => $response->json(),
        ];
    }

    /**
     * Get API headers.
     */
    private function getHeaders(): array
    {
        return [
            'X-RapidAPI-Key' => $this->apiKey,
            'X-RapidAPI-Host' => $this->activeHost,
        ];
    }

    /**
     * Fallback to secondary API for hashtag feed.
     */
    private function fetchHashtagFeedFallback(string $hashtag, int $limit, string $cacheKey): array
    {
        $this->switchToFallbackApi();

        try {
            $response = Http::withHeaders($this->getHeaders())
                ->timeout(self::REQUEST_TIMEOUT)
                ->get($this->activeUrl . '/hashtag_posts_v2', [
                    'hashtag' => $hashtag,
                    'amount' => $limit,
                ]);

            if ($response->failed()) {
                return $this->errorResponse('Both APIs failed');
            }

            $rawItems = $this->extractItems($response->json());
            $mappedItems = $this->mapAndFilterHashtagItems($rawItems, $hashtag);

            $result = [
                'success' => true,
                'data' => $mappedItems,
                'meta' => [
                    'hashtag' => $hashtag,
                    'total_raw' => count($rawItems),
                    'total_filtered' => count($mappedItems),
                    'filter_applied' => 'media_type=2, plays>' . self::MIN_PLAY_COUNT,
                    'fetched_at' => now()->toIso8601String(),
                    'api_host' => $this->activeHost,
                    'fallback_used' => true,
                ],
            ];

            Cache::put($cacheKey, $result, self::CACHE_TTL_HASHTAG);

            return $result;

        } catch (\Exception $e) {
            return $this->errorResponse('Fallback API failed: ' . $e->getMessage());
        }
    }

    /**
     * Fallback for user stats.
     */
    private function fetchUserStatsFallback(string $username, string $cacheKey): array
    {
        $this->switchToFallbackApi();

        try {
            $response = Http::withHeaders($this->getHeaders())
                ->timeout(self::REQUEST_TIMEOUT)
                ->get($this->activeUrl . '/user_info_v2', [
                    'username' => $username,
                ]);

            if ($response->failed()) {
                return $this->errorResponse('Both APIs failed for user lookup');
            }

            $userData = $this->extractUserData($response->json());

            if (!$userData) {
                return $this->errorResponse('User not found');
            }

            $result = [
                'success' => true,
                'data' => $this->mapUserStats($userData, $username),
            ];

            Cache::put($cacheKey, $result, self::CACHE_TTL_USER);

            return $result;

        } catch (\Exception $e) {
            return $this->errorResponse('Fallback failed: ' . $e->getMessage());
        }
    }

    /**
     * Switch to fallback API.
     */
    private function switchToFallbackApi(): void
    {
        $this->activeHost = self::FALLBACK_API_HOST;
        $this->activeUrl = self::FALLBACK_API_URL;

        Log::info('RocketAPI: Switched to fallback API', [
            'host' => $this->activeHost,
        ]);
    }

    // ==========================================
    // UTILITY METHODS
    // ==========================================

    /**
     * Sanitize hashtag input.
     */
    private function sanitizeHashtag(string $hashtag): string
    {
        // Remove # symbol and trim
        return trim(ltrim($hashtag, '#'));
    }

    /**
     * Sanitize username input.
     */
    private function sanitizeUsername(string $username): string
    {
        // Remove @ symbol and trim
        return trim(ltrim($username, '@'));
    }

    /**
     * Build error response.
     */
    private function errorResponse(string $message): array
    {
        return [
            'success' => false,
            'data' => [],
            'error' => $message,
        ];
    }

    /**
     * Check if API is configured.
     */
    public function isConfigured(): bool
    {
        return !empty($this->apiKey);
    }

    /**
     * Get API quota information.
     */
    public function checkQuota(): array
    {
        return [
            'configured' => $this->isConfigured(),
            'primary_host' => self::PRIMARY_API_HOST,
            'fallback_host' => self::FALLBACK_API_HOST,
            'active_host' => $this->activeHost,
        ];
    }

    /**
     * Get trending hashtags for Uzbekistan business niche.
     */
    public static function getUzbekBusinessHashtags(): array
    {
        return [
            // Uzbek business
            'tadbirkor',
            'businessuz',
            'uzbekbusiness',
            'biznes',
            'startupuz',

            // Marketing
            'marketinguz',
            'smm',
            'reklama',
            'targetolog',

            // Sales
            'sotuv',
            'savdo',
            'onlinebusiness',

            // Location-based
            'tashkent',
            'uzbekistan',
            'samarkand',

            // General viral
            'reelsuzbekistan',
            'reelsuz',
        ];
    }

    /**
     * Clear cache for testing.
     */
    public function clearCache(string $hashtag = null, string $username = null): void
    {
        if ($hashtag) {
            Cache::forget("rocketapi:hashtag:{$hashtag}:30");
        }

        if ($username) {
            Cache::forget("rocketapi:user:{$username}");
        }
    }
}
