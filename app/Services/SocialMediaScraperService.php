<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SocialMediaScraperService
{
    protected int $cacheTTL = 3600; // 1 hour cache

    /**
     * Get Instagram profile metrics
     * Uses Instagram's public web API to fetch basic profile data
     */
    public function getInstagramMetrics(string $username): ?array
    {
        $username = $this->cleanHandle($username);
        $cacheKey = "instagram_metrics_{$username}";

        // Check cache first
        if ($cached = Cache::get($cacheKey)) {
            return $cached;
        }

        try {
            // Method 1: Try Instagram's public API endpoint
            $metrics = $this->fetchInstagramViaWebAPI($username);

            if ($metrics) {
                Cache::put($cacheKey, $metrics, $this->cacheTTL);
                return $metrics;
            }

            // Method 2: Try web scraping as fallback
            $metrics = $this->fetchInstagramViaScraping($username);

            if ($metrics) {
                Cache::put($cacheKey, $metrics, $this->cacheTTL);
                return $metrics;
            }

            return null;

        } catch (\Exception $e) {
            Log::error('Instagram metrics fetch error', [
                'username' => $username,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Fetch Instagram data via public web API
     */
    protected function fetchInstagramViaWebAPI(string $username): ?array
    {
        try {
            // Instagram's public API endpoint (may require session cookies)
            $response = Http::withHeaders([
                'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
                'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8',
                'Accept-Language' => 'en-US,en;q=0.5',
                'Connection' => 'keep-alive',
            ])->timeout(15)->get("https://www.instagram.com/api/v1/users/web_profile_info/?username={$username}");

            if ($response->successful()) {
                $data = $response->json();

                if (isset($data['data']['user'])) {
                    $user = $data['data']['user'];

                    return [
                        'instagram_followers' => $user['edge_followed_by']['count'] ?? null,
                        'instagram_following' => $user['edge_follow']['count'] ?? null,
                        'instagram_posts' => $user['edge_owner_to_timeline_media']['count'] ?? null,
                        'instagram_engagement_rate' => $this->calculateInstagramEngagement($user),
                        'instagram_is_business' => $user['is_business_account'] ?? false,
                        'instagram_is_verified' => $user['is_verified'] ?? false,
                        'instagram_bio' => $user['biography'] ?? null,
                        'instagram_profile_pic' => $user['profile_pic_url_hd'] ?? null,
                    ];
                }
            }

            return null;

        } catch (\Exception $e) {
            Log::warning('Instagram web API failed', ['error' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * Fetch Instagram data via web scraping
     */
    protected function fetchInstagramViaScraping(string $username): ?array
    {
        try {
            $response = Http::withHeaders([
                'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
                'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
                'Accept-Language' => 'en-US,en;q=0.9',
            ])->timeout(15)->get("https://www.instagram.com/{$username}/");

            if (!$response->successful()) {
                return null;
            }

            $html = $response->body();

            // Extract JSON data from page
            if (preg_match('/<script type="application\/ld\+json">(.*?)<\/script>/s', $html, $matches)) {
                $jsonData = json_decode($matches[1], true);

                if ($jsonData && isset($jsonData['mainEntityofPage'])) {
                    return [
                        'instagram_followers' => $this->extractNumber($jsonData['mainEntityofPage']['interactionStatistic'][0]['userInteractionCount'] ?? null),
                        'instagram_posts' => $this->extractNumber($jsonData['mainEntityofPage']['interactionStatistic'][1]['userInteractionCount'] ?? null),
                        'instagram_bio' => $jsonData['description'] ?? null,
                    ];
                }
            }

            // Alternative: parse meta tags
            if (preg_match('/content="([\d,\.]+[KMB]?) Followers/i', $html, $matches)) {
                return [
                    'instagram_followers' => $this->parseShortNumber($matches[1]),
                ];
            }

            return null;

        } catch (\Exception $e) {
            Log::warning('Instagram scraping failed', ['error' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * Calculate Instagram engagement rate
     */
    protected function calculateInstagramEngagement(array $user): ?float
    {
        $followers = $user['edge_followed_by']['count'] ?? 0;

        if ($followers < 100) {
            return null;
        }

        $posts = $user['edge_owner_to_timeline_media']['edges'] ?? [];
        if (empty($posts)) {
            return null;
        }

        $totalEngagement = 0;
        $postCount = 0;

        foreach (array_slice($posts, 0, 12) as $post) {
            $node = $post['node'] ?? [];
            $likes = $node['edge_liked_by']['count'] ?? 0;
            $comments = $node['edge_media_to_comment']['count'] ?? 0;
            $totalEngagement += $likes + $comments;
            $postCount++;
        }

        if ($postCount === 0) {
            return null;
        }

        $avgEngagement = $totalEngagement / $postCount;
        return round(($avgEngagement / $followers) * 100, 2);
    }

    /**
     * Get Facebook page metrics
     * Uses Facebook's public page info
     */
    public function getFacebookMetrics(string $pageIdentifier): ?array
    {
        $pageIdentifier = $this->cleanHandle($pageIdentifier);
        $cacheKey = "facebook_metrics_{$pageIdentifier}";

        if ($cached = Cache::get($cacheKey)) {
            return $cached;
        }

        try {
            // Try to fetch public page info
            $metrics = $this->fetchFacebookPageInfo($pageIdentifier);

            if ($metrics) {
                Cache::put($cacheKey, $metrics, $this->cacheTTL);
                return $metrics;
            }

            return null;

        } catch (\Exception $e) {
            Log::error('Facebook metrics fetch error', [
                'page' => $pageIdentifier,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Fetch Facebook page info from public page
     */
    protected function fetchFacebookPageInfo(string $pageIdentifier): ?array
    {
        try {
            $response = Http::withHeaders([
                'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
                'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
                'Accept-Language' => 'en-US,en;q=0.9',
            ])->timeout(15)->get("https://www.facebook.com/{$pageIdentifier}");

            if (!$response->successful()) {
                return null;
            }

            $html = $response->body();

            $metrics = [];

            // Extract followers count from meta or page content
            if (preg_match('/([\d,\.]+[KMB]?)\s*(?:people follow|followers|people like)/i', $html, $matches)) {
                $metrics['facebook_followers'] = $this->parseShortNumber($matches[1]);
            }

            // Extract likes count
            if (preg_match('/([\d,\.]+[KMB]?)\s*(?:likes|people like this)/i', $html, $matches)) {
                $metrics['facebook_likes'] = $this->parseShortNumber($matches[1]);
            }

            // Extract from structured data
            if (preg_match('/"userInteractionCount":\s*"?([\d]+)"?/i', $html, $matches)) {
                if (!isset($metrics['facebook_followers'])) {
                    $metrics['facebook_followers'] = (int) $matches[1];
                }
            }

            return !empty($metrics) ? $metrics : null;

        } catch (\Exception $e) {
            Log::warning('Facebook page fetch failed', ['error' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * Get Facebook metrics using Graph API (requires access token)
     */
    public function getFacebookMetricsViaAPI(string $pageId, string $accessToken): ?array
    {
        try {
            $response = Http::get("https://graph.facebook.com/v18.0/{$pageId}", [
                'fields' => 'followers_count,fan_count,name,about,website,category',
                'access_token' => $accessToken,
            ]);

            if ($response->successful()) {
                $data = $response->json();

                return [
                    'facebook_followers' => $data['followers_count'] ?? null,
                    'facebook_likes' => $data['fan_count'] ?? null,
                    'facebook_name' => $data['name'] ?? null,
                    'facebook_category' => $data['category'] ?? null,
                ];
            }

            return null;

        } catch (\Exception $e) {
            Log::error('Facebook Graph API error', ['error' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * Get Instagram metrics using Graph API (requires connected account)
     */
    public function getInstagramMetricsViaAPI(string $instagramAccountId, string $accessToken): ?array
    {
        try {
            $response = Http::get("https://graph.facebook.com/v18.0/{$instagramAccountId}", [
                'fields' => 'followers_count,follows_count,media_count,username,name,biography,website,profile_picture_url',
                'access_token' => $accessToken,
            ]);

            if ($response->successful()) {
                $data = $response->json();

                return [
                    'instagram_followers' => $data['followers_count'] ?? null,
                    'instagram_following' => $data['follows_count'] ?? null,
                    'instagram_posts' => $data['media_count'] ?? null,
                    'instagram_bio' => $data['biography'] ?? null,
                    'instagram_profile_pic' => $data['profile_picture_url'] ?? null,
                ];
            }

            return null;

        } catch (\Exception $e) {
            Log::error('Instagram Graph API error', ['error' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * Clean social media handle
     */
    protected function cleanHandle(string $handle): string
    {
        // Remove @ symbol
        $handle = ltrim($handle, '@');

        // Remove URL parts if full URL provided
        $handle = preg_replace('#^https?://(www\.)?(instagram|facebook|fb)\.com/#i', '', $handle);

        // Remove trailing slash
        $handle = rtrim($handle, '/');

        // Remove query params
        $handle = explode('?', $handle)[0];

        return $handle;
    }

    /**
     * Parse short number format (1.5K, 2.3M, etc.)
     */
    protected function parseShortNumber(string $value): ?int
    {
        $value = str_replace([',', ' '], '', $value);

        if (preg_match('/^([\d.]+)([KMB])?$/i', $value, $matches)) {
            $number = (float) $matches[1];
            $multiplier = strtoupper($matches[2] ?? '');

            switch ($multiplier) {
                case 'K':
                    return (int) ($number * 1000);
                case 'M':
                    return (int) ($number * 1000000);
                case 'B':
                    return (int) ($number * 1000000000);
                default:
                    return (int) $number;
            }
        }

        return null;
    }

    /**
     * Extract number from various formats
     */
    protected function extractNumber($value): ?int
    {
        if (is_null($value)) {
            return null;
        }

        if (is_numeric($value)) {
            return (int) $value;
        }

        return $this->parseShortNumber((string) $value);
    }

    /**
     * Get Telegram channel metrics
     * Uses Telegram's public preview API
     */
    public function getTelegramMetrics(string $channelHandle): ?array
    {
        $channelHandle = $this->cleanTelegramHandle($channelHandle);
        $cacheKey = "telegram_metrics_{$channelHandle}";

        if ($cached = Cache::get($cacheKey)) {
            return $cached;
        }

        try {
            // Method 1: Try t.me web preview
            $metrics = $this->fetchTelegramViaWebPreview($channelHandle);

            if ($metrics) {
                Cache::put($cacheKey, $metrics, $this->cacheTTL);
                return $metrics;
            }

            // Method 2: Try Telegram's public API
            $metrics = $this->fetchTelegramViaAPI($channelHandle);

            if ($metrics) {
                Cache::put($cacheKey, $metrics, $this->cacheTTL);
                return $metrics;
            }

            return null;

        } catch (\Exception $e) {
            Log::error('Telegram metrics fetch error', [
                'channel' => $channelHandle,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Fetch Telegram data via t.me web preview
     */
    protected function fetchTelegramViaWebPreview(string $channelHandle): ?array
    {
        try {
            $response = Http::withHeaders([
                'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
                'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
                'Accept-Language' => 'en-US,en;q=0.9',
            ])->timeout(15)->get("https://t.me/{$channelHandle}");

            if (!$response->successful()) {
                return null;
            }

            $html = $response->body();
            $metrics = [];

            // Extract members count - multiple patterns for different page formats
            $patterns = [
                // Pattern 1: "1.5K members" or "15 000 members"
                '/([\d\s,\.]+[KMB]?)\s*(?:members|subscribers|a\'zolar|obunachilar)/iu',
                // Pattern 2: data attribute
                '/data-(?:members|subscribers)="([\d]+)"/i',
                // Pattern 3: tgme_page_extra div
                '/class="tgme_page_extra"[^>]*>([\d\s,\.]+[KMB]?)\s*(?:members|subscribers)/iu',
                // Pattern 4: counter div
                '/class="tgme_channel_info_counter"[^>]*>.*?<span[^>]*>([\d\s,\.]+[KMB]?)<\/span>/isu',
            ];

            foreach ($patterns as $pattern) {
                if (preg_match($pattern, $html, $matches)) {
                    $members = $this->parseShortNumber(str_replace(' ', '', $matches[1]));
                    if ($members && $members > 0) {
                        $metrics['telegram_members'] = $members;
                        break;
                    }
                }
            }

            // Extract channel title
            if (preg_match('/class="tgme_page_title"[^>]*>.*?<span[^>]*>([^<]+)<\/span>/isu', $html, $matches)) {
                $metrics['telegram_title'] = trim(strip_tags($matches[1]));
            }

            // Extract channel description
            if (preg_match('/class="tgme_page_description"[^>]*>([^<]+)</isu', $html, $matches)) {
                $metrics['telegram_description'] = trim($matches[1]);
            }

            // Extract channel photo
            if (preg_match('/class="tgme_page_photo_image"[^>]*style="background-image:\s*url\([\'"]([^\'"]+)[\'"]\)/i', $html, $matches)) {
                $metrics['telegram_photo'] = $matches[1];
            }

            // Check if it's a channel or group
            $metrics['telegram_is_channel'] = strpos($html, 'Channel') !== false || strpos($html, 'kanal') !== false;

            return !empty($metrics) ? $metrics : null;

        } catch (\Exception $e) {
            Log::warning('Telegram web preview failed', ['error' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * Fetch Telegram data via public widget API
     */
    protected function fetchTelegramViaAPI(string $channelHandle): ?array
    {
        try {
            // Try the widget info endpoint
            $response = Http::withHeaders([
                'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
            ])->timeout(10)->get("https://t.me/s/{$channelHandle}");

            if (!$response->successful()) {
                return null;
            }

            $html = $response->body();
            $metrics = [];

            // Parse channel header info
            if (preg_match('/class="tgme_channel_info_header_counter"[^>]*>.*?([\d\s,\.]+[KMB]?)\s*(?:members|subscribers)/isu', $html, $matches)) {
                $metrics['telegram_members'] = $this->parseShortNumber(str_replace(' ', '', $matches[1]));
            }

            // Count posts if available (for engagement estimation)
            $postCount = preg_match_all('/class="tgme_widget_message"/i', $html);
            if ($postCount > 0) {
                $metrics['telegram_recent_posts'] = min($postCount, 20);

                // Try to extract view counts for engagement calculation
                if (preg_match_all('/class="tgme_widget_message_views"[^>]*>([\d\.]+[KMB]?)/iu', $html, $viewMatches)) {
                    $totalViews = 0;
                    foreach ($viewMatches[1] as $viewStr) {
                        $totalViews += $this->parseShortNumber($viewStr) ?? 0;
                    }
                    if (count($viewMatches[1]) > 0) {
                        $metrics['telegram_avg_views'] = (int) ($totalViews / count($viewMatches[1]));
                    }
                }
            }

            return !empty($metrics) ? $metrics : null;

        } catch (\Exception $e) {
            Log::warning('Telegram API fetch failed', ['error' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * Clean Telegram channel handle
     */
    protected function cleanTelegramHandle(string $handle): string
    {
        // Remove @ symbol
        $handle = ltrim($handle, '@');

        // Remove URL parts if full URL provided
        $handle = preg_replace('#^https?://(www\.)?t\.me/#i', '', $handle);
        $handle = preg_replace('#^https?://(www\.)?telegram\.me/#i', '', $handle);

        // Remove trailing slash and query params
        $handle = rtrim($handle, '/');
        $handle = explode('?', $handle)[0];

        // Remove /s/ prefix (public post view)
        $handle = preg_replace('#^s/#i', '', $handle);

        return $handle;
    }

    /**
     * Get Instagram posts/content
     */
    public function getInstagramPosts(string $username, int $limit = 12): ?array
    {
        $username = $this->cleanHandle($username);
        $cacheKey = "instagram_posts_{$username}";

        if ($cached = Cache::get($cacheKey)) {
            return $cached;
        }

        try {
            $response = Http::withHeaders([
                'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
                'Accept' => 'application/json',
            ])->timeout(15)->get("https://www.instagram.com/api/v1/users/web_profile_info/?username={$username}");

            if (!$response->successful()) {
                return null;
            }

            $data = $response->json();
            $posts = [];

            if (isset($data['data']['user']['edge_owner_to_timeline_media']['edges'])) {
                foreach (array_slice($data['data']['user']['edge_owner_to_timeline_media']['edges'], 0, $limit) as $edge) {
                    $node = $edge['node'];
                    $posts[] = [
                        'id' => $node['id'] ?? null,
                        'shortcode' => $node['shortcode'] ?? null,
                        'type' => $this->getInstagramPostType($node),
                        'caption' => $node['edge_media_to_caption']['edges'][0]['node']['text'] ?? null,
                        'likes' => $node['edge_liked_by']['count'] ?? 0,
                        'comments' => $node['edge_media_to_comment']['count'] ?? 0,
                        'views' => $node['video_view_count'] ?? 0,
                        'media_type' => $node['is_video'] ? 'video' : 'image',
                        'media_url' => $node['display_url'] ?? null,
                        'thumbnail_url' => $node['thumbnail_src'] ?? null,
                        'permalink' => "https://www.instagram.com/p/{$node['shortcode']}/",
                        'timestamp' => isset($node['taken_at_timestamp'])
                            ? date('Y-m-d H:i:s', $node['taken_at_timestamp'])
                            : null,
                    ];
                }
            }

            if (!empty($posts)) {
                Cache::put($cacheKey, $posts, $this->cacheTTL);
            }

            return $posts;

        } catch (\Exception $e) {
            Log::warning('Instagram posts fetch failed', ['error' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * Get Instagram post type
     */
    protected function getInstagramPostType(array $node): string
    {
        if (isset($node['__typename'])) {
            return match($node['__typename']) {
                'GraphVideo' => 'video',
                'GraphSidecar' => 'carousel',
                'GraphImage' => 'post',
                default => 'post',
            };
        }

        if ($node['is_video'] ?? false) {
            return 'video';
        }

        return 'post';
    }

    /**
     * Get Telegram channel posts
     */
    public function getTelegramPosts(string $channelHandle, int $limit = 20): ?array
    {
        $channelHandle = $this->cleanTelegramHandle($channelHandle);
        $cacheKey = "telegram_posts_{$channelHandle}";

        if ($cached = Cache::get($cacheKey)) {
            return $cached;
        }

        try {
            $response = Http::withHeaders([
                'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
            ])->timeout(15)->get("https://t.me/s/{$channelHandle}");

            if (!$response->successful()) {
                return null;
            }

            $html = $response->body();
            $posts = [];

            // Extract posts from HTML
            if (preg_match_all('/class="tgme_widget_message[^"]*"[^>]*data-post="([^"]+)".*?class="tgme_widget_message_text[^"]*"[^>]*>(.*?)<\/div>.*?class="tgme_widget_message_views"[^>]*>([^<]+)/isu', $html, $matches, PREG_SET_ORDER)) {
                foreach (array_slice($matches, 0, $limit) as $match) {
                    $postId = $match[1];
                    $text = strip_tags($match[2]);
                    $views = $this->parseShortNumber(trim($match[3]));

                    $posts[] = [
                        'id' => $postId,
                        'type' => 'post',
                        'text' => $text,
                        'views' => $views ?? 0,
                        'permalink' => "https://t.me/{$postId}",
                        'timestamp' => null,
                    ];
                }
            }

            // Alternative extraction pattern
            if (empty($posts) && preg_match_all('/data-post="([^\/]+\/\d+)".*?tgme_widget_message_text[^>]*>(.*?)<\/div>/isu', $html, $matches, PREG_SET_ORDER)) {
                foreach (array_slice($matches, 0, $limit) as $match) {
                    $posts[] = [
                        'id' => $match[1],
                        'type' => 'post',
                        'text' => strip_tags($match[2]),
                        'views' => 0,
                        'permalink' => "https://t.me/{$match[1]}",
                        'timestamp' => null,
                    ];
                }
            }

            if (!empty($posts)) {
                Cache::put($cacheKey, $posts, $this->cacheTTL);
            }

            return $posts;

        } catch (\Exception $e) {
            Log::warning('Telegram posts fetch failed', ['error' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * Clear cache for a specific account
     */
    public function clearCache(string $platform, string $handle): void
    {
        $handle = $this->cleanHandle($handle);
        Cache::forget("{$platform}_metrics_{$handle}");
    }
}
