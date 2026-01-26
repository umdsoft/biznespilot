<?php

declare(strict_types=1);

namespace App\Services\TrendSee;

use App\Models\Business;
use App\Models\CompetitorMonitor;
use App\Models\InstagramAccount;
use App\Models\Lead;
use App\Models\Order;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * CompetitorSpyService - Hybrid Spy Module
 *
 * "Internal vs External" intelligence gathering.
 * If target is our client -> use internal data (Cost = $0).
 * If external -> use RapidAPI (Cost = $$$).
 *
 * Features:
 * - Automatic internal matching by domain/instagram
 * - Anonymized stats calculation from internal data
 * - 14-day data freshness cache
 * - API cost optimization
 */
class CompetitorSpyService
{
    private const CACHE_DAYS = 14;
    private const CACHE_PREFIX = 'competitor_';

    private RocketApiService $rocketApi;

    public function __construct(RocketApiService $rocketApi)
    {
        $this->rocketApi = $rocketApi;
    }

    /**
     * Get competitor intelligence (Cache-First + Hybrid Logic).
     *
     * @param string $targetUrl Target URL (Instagram or Website)
     * @return array{success: bool, data: array, source: string, cost: float}
     */
    public function getCompetitorData(string $targetUrl): array
    {
        $cacheKey = self::CACHE_PREFIX . md5($targetUrl);

        // Check memory cache first (short-term)
        if (Cache::has($cacheKey)) {
            return Cache::get($cacheKey);
        }

        // Check database for existing fresh data
        $existing = CompetitorMonitor::where('target_url', $targetUrl)
            ->fresh(self::CACHE_DAYS)
            ->first();

        if ($existing) {
            // HIT: Return cached data
            $result = [
                'success' => true,
                'data' => $this->formatCompetitorData($existing),
                'source' => $existing->data_source ?? 'database',
                'cost' => 0,
                'is_internal' => $existing->is_internal,
                'last_updated' => $existing->last_scraped_at?->toDateTimeString(),
            ];

            // Store in memory cache
            Cache::put($cacheKey, $result, now()->addHours(6));

            return $result;
        }

        // MISS: Need to fetch fresh data
        Log::info('CompetitorSpy: Cache miss, fetching data', ['url' => $targetUrl]);

        return $this->fetchAndStoreCompetitorData($targetUrl);
    }

    /**
     * Force refresh competitor data.
     */
    public function refresh(string $targetUrl): array
    {
        // Clear cache
        $cacheKey = self::CACHE_PREFIX . md5($targetUrl);
        Cache::forget($cacheKey);

        return $this->fetchAndStoreCompetitorData($targetUrl);
    }

    /**
     * Fetch fresh competitor data with hybrid logic.
     */
    private function fetchAndStoreCompetitorData(string $targetUrl): array
    {
        $targetType = $this->detectTargetType($targetUrl);
        $targetUsername = null;
        $targetDomain = null;

        if ($targetType === 'instagram') {
            $targetUsername = CompetitorMonitor::extractInstagramUsername($targetUrl);
        } else {
            $targetDomain = CompetitorMonitor::extractDomain($targetUrl);
        }

        // === HYBRID LOGIC: Check Internal First ===
        $internalMatch = $this->findInternalMatch($targetUsername, $targetDomain, $targetType);

        if ($internalMatch) {
            // INTERNAL: Use our data (Cost = $0)
            return $this->processInternalCompetitor(
                $targetUrl,
                $targetType,
                $targetUsername,
                $targetDomain,
                $internalMatch
            );
        }

        // EXTERNAL: Use RapidAPI (Cost = $$$)
        return $this->processExternalCompetitor(
            $targetUrl,
            $targetType,
            $targetUsername,
            $targetDomain
        );
    }

    /**
     * Find matching internal user/business.
     *
     * @return array{user: User|null, business: Business|null}|null
     */
    private function findInternalMatch(
        ?string $username,
        ?string $domain,
        string $type
    ): ?array {
        // Check by Instagram username
        if ($type === 'instagram' && $username) {
            $instagramAccount = InstagramAccount::where('username', $username)->first();

            if ($instagramAccount && $instagramAccount->business_id) {
                $business = Business::find($instagramAccount->business_id);
                if ($business) {
                    Log::info('CompetitorSpy: Internal match found by Instagram', [
                        'username' => $username,
                        'business_id' => $business->id,
                    ]);

                    return [
                        'user' => $business->owner,
                        'business' => $business,
                    ];
                }
            }
        }

        // Check by domain
        if ($domain) {
            $business = Business::where('website', 'LIKE', "%{$domain}%")->first();

            if ($business) {
                Log::info('CompetitorSpy: Internal match found by domain', [
                    'domain' => $domain,
                    'business_id' => $business->id,
                ]);

                return [
                    'user' => $business->owner,
                    'business' => $business,
                ];
            }
        }

        return null;
    }

    /**
     * Process internal competitor (use our own data).
     */
    private function processInternalCompetitor(
        string $targetUrl,
        string $targetType,
        ?string $targetUsername,
        ?string $targetDomain,
        array $internalMatch
    ): array {
        /** @var User $user */
        $user = $internalMatch['user'];
        /** @var Business $business */
        $business = $internalMatch['business'];

        // Calculate anonymized stats from internal data
        $stats = $this->calculateInternalStats($business);

        // Create or update competitor monitor
        $monitor = CompetitorMonitor::updateOrCreate(
            ['target_url' => $targetUrl],
            [
                'target_type' => $targetType,
                'target_username' => $targetUsername,
                'target_domain' => $targetDomain,
                'internal_user_id' => $user->id,
                'internal_business_id' => $business->id,
                'is_internal' => true,
                'stats_json' => $stats,
                'followers_count' => $stats['followers_count'] ?? null,
                'engagement_rate' => $stats['engagement_rate'] ?? null,
                'posts_count' => $stats['posts_count'] ?? null,
                'avg_likes' => $stats['avg_likes'] ?? null,
                'avg_comments' => $stats['avg_comments'] ?? null,
                'growth_json' => $stats['growth'] ?? null,
                'weekly_growth_rate' => $stats['weekly_growth_rate'] ?? null,
                'monthly_growth_rate' => $stats['monthly_growth_rate'] ?? null,
                'content_analysis_json' => $stats['content_analysis'] ?? null,
                'top_hashtags' => $stats['top_hashtags'] ?? null,
                'posting_frequency' => $stats['posting_frequency'] ?? null,
                'data_source' => 'internal',
                'api_cost' => 0,
                'is_active' => true,
                'last_scraped_at' => now(),
                'expires_at' => now()->addDays(self::CACHE_DAYS),
            ]
        );

        $result = [
            'success' => true,
            'data' => $this->formatCompetitorData($monitor),
            'source' => 'internal',
            'cost' => 0,
            'is_internal' => true,
            'last_updated' => now()->toDateTimeString(),
        ];

        // Store in memory cache
        $cacheKey = self::CACHE_PREFIX . md5($targetUrl);
        Cache::put($cacheKey, $result, now()->addHours(6));

        Log::info('CompetitorSpy: Internal data processed', [
            'url' => $targetUrl,
            'business_id' => $business->id,
        ]);

        return $result;
    }

    /**
     * Process external competitor (use RapidAPI).
     */
    private function processExternalCompetitor(
        string $targetUrl,
        string $targetType,
        ?string $targetUsername,
        ?string $targetDomain
    ): array {
        $stats = [];
        $cost = 0;

        if ($targetType === 'instagram' && $targetUsername && $this->rocketApi->isConfigured()) {
            // Step 1: Fetch user stats (followers, bio, etc.)
            $userStatsResult = $this->rocketApi->fetchUserStats($targetUsername);

            if ($userStatsResult['success']) {
                $userData = $userStatsResult['data'];
                $stats = [
                    'followers_count' => $userData['follower_count'] ?? null,
                    'following_count' => $userData['following_count'] ?? null,
                    'posts_count' => $userData['media_count'] ?? null,
                    'is_verified' => $userData['is_verified'] ?? false,
                    'is_business' => $userData['is_business'] ?? false,
                    'category' => $userData['category'] ?? null,
                    'biography' => $userData['biography'] ?? null,
                    'profile_pic_url' => $userData['profile_pic_url'] ?? null,
                    'external_url' => $userData['external_url'] ?? null,
                ];
                $cost += 0.005; // Approximate API cost per call
            }

            // Step 2: Fetch recent posts for engagement analysis
            $postsResult = $this->rocketApi->fetchUserPosts($targetUsername, 12);

            if ($postsResult['success']) {
                $contentStats = $this->calculateExternalStats($postsResult['data']);
                $stats = array_merge($stats, $contentStats);
                $cost += 0.005; // Approximate API cost per call
            }
        }

        // Create or update competitor monitor
        $monitor = CompetitorMonitor::updateOrCreate(
            ['target_url' => $targetUrl],
            [
                'target_type' => $targetType,
                'target_username' => $targetUsername,
                'target_domain' => $targetDomain,
                'is_internal' => false,
                'stats_json' => $stats,
                'followers_count' => $stats['followers_count'] ?? null,
                'engagement_rate' => $stats['engagement_rate'] ?? null,
                'posts_count' => $stats['posts_count'] ?? null,
                'avg_likes' => $stats['avg_likes'] ?? null,
                'avg_comments' => $stats['avg_comments'] ?? null,
                'growth_json' => $stats['growth'] ?? null,
                'content_analysis_json' => $stats['content_analysis'] ?? null,
                'top_hashtags' => $stats['top_hashtags'] ?? null,
                'posting_frequency' => $stats['posting_frequency'] ?? null,
                'data_source' => 'rapidapi',
                'api_cost' => $cost,
                'is_active' => true,
                'last_scraped_at' => now(),
                'expires_at' => now()->addDays(self::CACHE_DAYS),
            ]
        );

        $monitor->increment('api_calls_count');

        $result = [
            'success' => true,
            'data' => $this->formatCompetitorData($monitor),
            'source' => 'rapidapi',
            'cost' => $cost,
            'is_internal' => false,
            'last_updated' => now()->toDateTimeString(),
        ];

        // Store in memory cache
        $cacheKey = self::CACHE_PREFIX . md5($targetUrl);
        Cache::put($cacheKey, $result, now()->addHours(6));

        Log::info('CompetitorSpy: External data processed', [
            'url' => $targetUrl,
            'cost' => $cost,
        ]);

        return $result;
    }

    /**
     * Calculate anonymized stats from internal business data.
     */
    private function calculateInternalStats(Business $business): array
    {
        // === ORDER METRICS ===
        $ordersLast30Days = Order::where('business_id', $business->id)
            ->where('created_at', '>=', now()->subDays(30))
            ->get();

        $ordersLast7Days = Order::where('business_id', $business->id)
            ->where('created_at', '>=', now()->subDays(7))
            ->get();

        $totalRevenue30Days = $ordersLast30Days->sum('total_amount');
        $avgOrderValue = $ordersLast30Days->count() > 0
            ? $totalRevenue30Days / $ordersLast30Days->count()
            : 0;

        // === LEAD METRICS ===
        $leadsLast30Days = Lead::where('business_id', $business->id)
            ->where('created_at', '>=', now()->subDays(30))
            ->count();

        $leadsLast7Days = Lead::where('business_id', $business->id)
            ->where('created_at', '>=', now()->subDays(7))
            ->count();

        // === CONVERSION RATE ===
        $convertedLeads = Lead::where('business_id', $business->id)
            ->where('created_at', '>=', now()->subDays(30))
            ->where('status', 'converted')
            ->count();

        $conversionRate = $leadsLast30Days > 0
            ? ($convertedLeads / $leadsLast30Days) * 100
            : 0;

        // === INSTAGRAM METRICS (if connected) ===
        $instagramStats = $this->getInstagramStats($business);

        // === GROWTH CALCULATION ===
        $previousMonthOrders = Order::where('business_id', $business->id)
            ->whereBetween('created_at', [now()->subDays(60), now()->subDays(30)])
            ->count();

        $monthlyGrowthRate = $previousMonthOrders > 0
            ? (($ordersLast30Days->count() - $previousMonthOrders) / $previousMonthOrders) * 100
            : 0;

        $previousWeekOrders = Order::where('business_id', $business->id)
            ->whereBetween('created_at', [now()->subDays(14), now()->subDays(7)])
            ->count();

        $weeklyGrowthRate = $previousWeekOrders > 0
            ? (($ordersLast7Days->count() - $previousWeekOrders) / $previousWeekOrders) * 100
            : 0;

        return [
            // Anonymized business metrics
            'orders_count_30d' => $ordersLast30Days->count(),
            'orders_count_7d' => $ordersLast7Days->count(),
            'revenue_range_30d' => $this->anonymizeRevenue($totalRevenue30Days),
            'avg_order_value_range' => $this->anonymizeAmount($avgOrderValue),

            // Lead metrics
            'leads_count_30d' => $leadsLast30Days,
            'leads_count_7d' => $leadsLast7Days,
            'conversion_rate' => round($conversionRate, 1),

            // Growth metrics
            'weekly_growth_rate' => round($weeklyGrowthRate, 1),
            'monthly_growth_rate' => round($monthlyGrowthRate, 1),

            // Instagram metrics (if available)
            'followers_count' => $instagramStats['followers_count'] ?? null,
            'posts_count' => $instagramStats['posts_count'] ?? null,
            'engagement_rate' => $instagramStats['engagement_rate'] ?? null,
            'avg_likes' => $instagramStats['avg_likes'] ?? null,
            'avg_comments' => $instagramStats['avg_comments'] ?? null,
            'top_hashtags' => $instagramStats['top_hashtags'] ?? null,
            'posting_frequency' => $instagramStats['posting_frequency'] ?? null,

            // Growth data
            'growth' => [
                'weekly' => round($weeklyGrowthRate, 1),
                'monthly' => round($monthlyGrowthRate, 1),
                'trend' => $monthlyGrowthRate > 0 ? 'up' : ($monthlyGrowthRate < 0 ? 'down' : 'stable'),
            ],

            // Content analysis
            'content_analysis' => [
                'activity_level' => $this->calculateActivityLevel($ordersLast30Days->count(), $leadsLast30Days),
                'business_stage' => $business->business_stage ?? 'unknown',
                'industry' => $business->industry ?? $business->category,
            ],

            // Data freshness
            'calculated_at' => now()->toDateTimeString(),
        ];
    }

    /**
     * Get Instagram stats for internal business.
     */
    private function getInstagramStats(Business $business): array
    {
        $instagramAccount = $business->instagramAccount;

        if (!$instagramAccount) {
            return [];
        }

        return [
            'followers_count' => $instagramAccount->followers_count ?? null,
            'posts_count' => $instagramAccount->media_count ?? null,
            'engagement_rate' => $instagramAccount->engagement_rate ?? null,
            'avg_likes' => $instagramAccount->avg_likes ?? null,
            'avg_comments' => $instagramAccount->avg_comments ?? null,
            'top_hashtags' => $instagramAccount->top_hashtags ?? [],
            'posting_frequency' => $this->calculatePostingFrequency($instagramAccount),
        ];
    }

    /**
     * Calculate stats from external API data.
     *
     * Handles both new DTO format (metrics.plays) and legacy format (play_count).
     */
    private function calculateExternalStats(array $posts): array
    {
        if (empty($posts)) {
            return [];
        }

        $totalLikes = 0;
        $totalComments = 0;
        $totalPlays = 0;
        $hashtags = [];
        $videoCount = 0;

        foreach ($posts as $post) {
            // Handle new DTO format (metrics.plays) and legacy format (play_count)
            $metrics = $post['metrics'] ?? [];

            $likes = $metrics['likes'] ?? $post['like_count'] ?? 0;
            $comments = $metrics['comments'] ?? $post['comment_count'] ?? 0;
            $plays = $metrics['plays'] ?? $post['play_count'] ?? 0;

            $totalLikes += $likes;
            $totalComments += $comments;
            $totalPlays += $plays;

            // Count videos
            $isVideo = $post['is_video'] ?? ($post['media_type'] ?? 1) === 2;
            if ($isVideo) {
                $videoCount++;
            }

            // Extract hashtags from caption
            if (!empty($post['caption'])) {
                preg_match_all('/#(\w+)/u', $post['caption'], $matches);
                if (!empty($matches[1])) {
                    foreach ($matches[1] as $tag) {
                        $hashtags[$tag] = ($hashtags[$tag] ?? 0) + 1;
                    }
                }
            }
        }

        $postCount = count($posts);
        $avgLikes = $postCount > 0 ? (int) ($totalLikes / $postCount) : 0;
        $avgComments = $postCount > 0 ? (int) ($totalComments / $postCount) : 0;
        $avgPlays = $videoCount > 0 ? (int) ($totalPlays / $videoCount) : 0;

        // Sort hashtags by frequency
        arsort($hashtags);
        $topHashtags = array_slice(array_keys($hashtags), 0, 10);

        return [
            'posts_analyzed' => $postCount,
            'video_count' => $videoCount,
            'avg_likes' => $avgLikes,
            'avg_comments' => $avgComments,
            'avg_plays' => $avgPlays,
            'total_plays' => $totalPlays,
            'top_hashtags' => $topHashtags,
            'content_analysis' => [
                'engagement_level' => $this->calculateEngagementLevel($avgLikes, $avgComments),
                'video_ratio' => $postCount > 0 ? round(($videoCount / $postCount) * 100, 1) : 0,
            ],
        ];
    }

    /**
     * Detect target type from URL.
     */
    private function detectTargetType(string $url): string
    {
        if (str_contains($url, 'instagram.com')) {
            return 'instagram';
        }

        if (str_contains($url, 'tiktok.com')) {
            return 'tiktok';
        }

        if (str_contains($url, 'youtube.com') || str_contains($url, 'youtu.be')) {
            return 'youtube';
        }

        if (str_contains($url, 'facebook.com') || str_contains($url, 'fb.com')) {
            return 'facebook';
        }

        return 'website';
    }

    /**
     * Anonymize revenue into ranges for privacy.
     */
    private function anonymizeRevenue(float $amount): string
    {
        if ($amount < 1000000) {
            return '< 1M';
        }
        if ($amount < 5000000) {
            return '1-5M';
        }
        if ($amount < 10000000) {
            return '5-10M';
        }
        if ($amount < 50000000) {
            return '10-50M';
        }
        if ($amount < 100000000) {
            return '50-100M';
        }

        return '100M+';
    }

    /**
     * Anonymize amount into ranges.
     */
    private function anonymizeAmount(float $amount): string
    {
        if ($amount < 100000) {
            return '< 100K';
        }
        if ($amount < 500000) {
            return '100-500K';
        }
        if ($amount < 1000000) {
            return '500K-1M';
        }
        if ($amount < 5000000) {
            return '1-5M';
        }

        return '5M+';
    }

    /**
     * Calculate activity level based on metrics.
     */
    private function calculateActivityLevel(int $orders, int $leads): string
    {
        $total = $orders + $leads;

        if ($total >= 100) {
            return 'very_high';
        }
        if ($total >= 50) {
            return 'high';
        }
        if ($total >= 20) {
            return 'medium';
        }
        if ($total >= 5) {
            return 'low';
        }

        return 'minimal';
    }

    /**
     * Calculate engagement level from likes/comments.
     */
    private function calculateEngagementLevel(int $avgLikes, int $avgComments): string
    {
        $total = $avgLikes + ($avgComments * 3); // Comments weighted more

        if ($total >= 10000) {
            return 'viral';
        }
        if ($total >= 5000) {
            return 'very_high';
        }
        if ($total >= 1000) {
            return 'high';
        }
        if ($total >= 500) {
            return 'medium';
        }

        return 'low';
    }

    /**
     * Calculate posting frequency.
     */
    private function calculatePostingFrequency($instagramAccount): string
    {
        $postsPerWeek = $instagramAccount->posts_per_week ?? 0;

        if ($postsPerWeek >= 14) {
            return 'multiple_daily';
        }
        if ($postsPerWeek >= 7) {
            return 'daily';
        }
        if ($postsPerWeek >= 3) {
            return 'several_weekly';
        }
        if ($postsPerWeek >= 1) {
            return 'weekly';
        }

        return 'rare';
    }

    /**
     * Format competitor data for response.
     */
    private function formatCompetitorData(CompetitorMonitor $monitor): array
    {
        return [
            'id' => $monitor->id,
            'target_url' => $monitor->target_url,
            'target_type' => $monitor->target_type,
            'target_username' => $monitor->target_username,
            'target_domain' => $monitor->target_domain,
            'is_internal' => $monitor->is_internal,
            'followers_count' => $monitor->followers_count,
            'formatted_followers' => $monitor->formatted_followers,
            'engagement_rate' => $monitor->engagement_rate,
            'posts_count' => $monitor->posts_count,
            'avg_likes' => $monitor->avg_likes,
            'avg_comments' => $monitor->avg_comments,
            'weekly_growth_rate' => $monitor->weekly_growth_rate,
            'monthly_growth_rate' => $monitor->monthly_growth_rate,
            'top_hashtags' => $monitor->top_hashtags ?? [],
            'posting_frequency' => $monitor->posting_frequency,
            'stats' => $monitor->stats_json ?? [],
            'growth' => $monitor->growth_json ?? [],
            'content_analysis' => $monitor->content_analysis_json ?? [],
            'data_source' => $monitor->data_source,
            'is_fresh' => $monitor->is_fresh,
            'is_stale' => $monitor->is_stale,
            'last_scraped_at' => $monitor->last_scraped_at?->toDateTimeString(),
            'last_scraped_human' => $monitor->last_scraped_at?->diffForHumans(),
        ];
    }

    /**
     * Get all monitored competitors for a business.
     */
    public function getMonitoredCompetitors(string $businessId): array
    {
        // Get competitors added by this business (from competitors table)
        $business = Business::find($businessId);

        if (!$business) {
            return [];
        }

        // Get competitor URLs from business's competitors
        $competitorUrls = $business->competitors()
            ->whereNotNull('website')
            ->pluck('website')
            ->toArray();

        $instagramUrls = $business->competitors()
            ->whereNotNull('instagram')
            ->pluck('instagram')
            ->map(fn ($ig) => "https://instagram.com/{$ig}")
            ->toArray();

        $allUrls = array_merge($competitorUrls, $instagramUrls);

        if (empty($allUrls)) {
            return [];
        }

        // Get monitored data for these URLs
        $monitors = CompetitorMonitor::whereIn('target_url', $allUrls)
            ->orWhereIn('target_username', $business->competitors()->pluck('instagram')->filter())
            ->orWhereIn('target_domain', $business->competitors()->pluck('website')->map(fn ($w) => CompetitorMonitor::extractDomain($w))->filter())
            ->get();

        return $monitors->map(fn ($m) => $this->formatCompetitorData($m))->toArray();
    }

    /**
     * Refresh all stale competitor data.
     */
    public function refreshStaleCompetitors(): array
    {
        $staleMonitors = CompetitorMonitor::stale(self::CACHE_DAYS)
            ->where('is_active', true)
            ->limit(10) // Process 10 at a time to avoid rate limits
            ->get();

        $results = [];

        foreach ($staleMonitors as $monitor) {
            try {
                $result = $this->refresh($monitor->target_url);
                $results[$monitor->target_url] = [
                    'success' => $result['success'],
                    'source' => $result['source'] ?? 'unknown',
                ];

                // Delay between requests
                usleep(500000); // 0.5 second
            } catch (\Exception $e) {
                Log::error('CompetitorSpy: Refresh failed', [
                    'url' => $monitor->target_url,
                    'error' => $e->getMessage(),
                ]);
                $results[$monitor->target_url] = [
                    'success' => false,
                    'error' => $e->getMessage(),
                ];
            }
        }

        return [
            'processed' => count($results),
            'results' => $results,
        ];
    }

    /**
     * Get API cost statistics.
     */
    public function getCostStats(): array
    {
        return [
            'total_api_cost' => CompetitorMonitor::sum('api_cost'),
            'total_api_calls' => CompetitorMonitor::sum('api_calls_count'),
            'internal_count' => CompetitorMonitor::internal()->count(),
            'external_count' => CompetitorMonitor::external()->count(),
            'savings_from_internal' => CompetitorMonitor::internal()->count() * 0.01, // Estimated savings
        ];
    }
}
