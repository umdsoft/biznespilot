<?php

namespace App\Services;

use App\Models\Competitor;
use App\Models\CompetitorActivity;
use App\Models\CompetitorAlert;
use App\Models\CompetitorMetric;
use App\Models\Integration;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class CompetitorMonitoringService
{
    protected SocialMediaScraperService $scraper;
    protected ?ContentAnalysisService $contentService = null;
    protected ?MetaAdLibraryService $adService = null;
    protected ?PriceMonitoringService $priceService = null;
    protected ?ReviewsMonitoringService $reviewsService = null;

    public function __construct(
        SocialMediaScraperService $scraper,
        ?ContentAnalysisService $contentService = null,
        ?MetaAdLibraryService $adService = null,
        ?PriceMonitoringService $priceService = null,
        ?ReviewsMonitoringService $reviewsService = null
    ) {
        $this->scraper = $scraper;
        $this->contentService = $contentService ?? app(ContentAnalysisService::class);
        $this->adService = $adService ?? app(MetaAdLibraryService::class);
        $this->priceService = $priceService ?? app(PriceMonitoringService::class);
        $this->reviewsService = $reviewsService ?? app(ReviewsMonitoringService::class);
    }

    /**
     * Get Meta integration access token for a business
     */
    protected function getMetaAccessToken(string $businessId): ?string
    {
        $integration = Integration::where('business_id', $businessId)
            ->where('type', 'meta_ads')
            ->where('status', 'connected')
            ->where('is_active', true)
            ->first();

        if ($integration && !$integration->isExpired()) {
            return $integration->getAccessToken();
        }

        return null;
    }

    /**
     * Monitor a single competitor - Full marketing intelligence
     */
    public function monitorCompetitor(Competitor $competitor): array
    {
        $results = [
            'success' => false,
            'metrics_collected' => false,
            'content_analyzed' => false,
            'ads_scanned' => false,
            'prices_checked' => false,
            'reviews_fetched' => false,
            'activities_found' => 0,
            'alerts_created' => 0,
            'errors' => [],
        ];

        try {
            // 1. Collect basic metrics from available platforms
            $metrics = $this->collectMetrics($competitor);

            if ($metrics) {
                $this->saveMetrics($competitor, $metrics);
                $results['metrics_collected'] = true;

                // Check for alerts based on metrics
                $alerts = $this->checkForAlerts($competitor, $metrics);
                $results['alerts_created'] = count($alerts);
            }

            // 2. Analyze content (posts from Instagram/Telegram)
            try {
                $contentResults = $this->contentService->analyzeCompetitor($competitor);
                $results['content_analyzed'] = $contentResults['success'];
                if (!$contentResults['success'] && !empty($contentResults['errors'])) {
                    $results['errors'] = array_merge($results['errors'], $contentResults['errors']);
                }
            } catch (\Exception $e) {
                Log::warning('Content analysis failed', ['competitor_id' => $competitor->id, 'error' => $e->getMessage()]);
            }

            // 3. Scan for ads (Meta Ad Library)
            if ($competitor->facebook_page || $competitor->instagram_handle) {
                try {
                    $adResults = $this->adService->searchCompetitorAds($competitor);
                    $results['ads_scanned'] = $adResults['success'];
                    if (!$adResults['success'] && !empty($adResults['errors'])) {
                        $results['errors'] = array_merge($results['errors'], $adResults['errors']);
                    }
                } catch (\Exception $e) {
                    Log::warning('Ad scanning failed', ['competitor_id' => $competitor->id, 'error' => $e->getMessage()]);
                }
            }

            // 4. Monitor prices (for tracked products)
            if ($competitor->products()->tracked()->exists()) {
                try {
                    $priceResults = $this->priceService->monitorPrices($competitor);
                    $results['prices_checked'] = $priceResults['success'];
                    $results['alerts_created'] += $priceResults['price_changes'] ?? 0;
                } catch (\Exception $e) {
                    Log::warning('Price monitoring failed', ['competitor_id' => $competitor->id, 'error' => $e->getMessage()]);
                }
            }

            // 5. Fetch reviews (from registered sources)
            if ($competitor->reviewSources()->exists()) {
                try {
                    $reviewResults = $this->reviewsService->monitorReviews($competitor);
                    $results['reviews_fetched'] = $reviewResults['success'];
                    if (!$reviewResults['success'] && !empty($reviewResults['errors'])) {
                        $results['errors'] = array_merge($results['errors'], $reviewResults['errors']);
                    }
                } catch (\Exception $e) {
                    Log::warning('Review fetching failed', ['competitor_id' => $competitor->id, 'error' => $e->getMessage()]);
                }
            }

            // 6. Scan for new activities (legacy)
            $activities = $this->scanActivities($competitor);
            $results['activities_found'] = count($activities);

            // Update last checked timestamp
            $competitor->update(['last_checked_at' => now()]);

            // Clear caches
            $this->clearCompetitorCaches($competitor);

            $results['success'] = true;

        } catch (\Exception $e) {
            Log::error('Competitor monitoring error', [
                'competitor_id' => $competitor->id,
                'error' => $e->getMessage(),
            ]);

            $results['errors'][] = $e->getMessage();
        }

        return $results;
    }

    /**
     * Clear all caches for competitor
     */
    protected function clearCompetitorCaches(Competitor $competitor): void
    {
        Cache::forget("competitor_content_insights_{$competitor->id}");
        Cache::forget("competitor_ad_insights_{$competitor->id}");
        Cache::forget("competitor_price_insights_{$competitor->id}");
        Cache::forget("competitor_review_insights_{$competitor->id}");
    }

    /**
     * Collect metrics from all available platforms (Hybrid approach)
     * 1. Telegram - always via scraper (works well)
     * 2. Instagram/Facebook - try Graph API if available, then scraper, else mark for manual entry
     */
    protected function collectMetrics(Competitor $competitor): array
    {
        $metrics = [];
        $dataSources = [];

        // Get Meta access token for potential Graph API use
        $accessToken = $this->getMetaAccessToken($competitor->business_id);

        // Telegram metrics - scraper always works
        if ($competitor->telegram_handle) {
            $telegramData = $this->getTelegramMetrics($competitor->telegram_handle);
            if ($telegramData) {
                $metrics = array_merge($metrics, $telegramData);
                $dataSources['telegram'] = 'scraper';
            }
        }

        // Instagram metrics - hybrid approach
        if ($competitor->instagram_handle) {
            $instagramData = null;

            // Try Graph API first if we have access token
            if ($accessToken) {
                $instagramData = $this->getInstagramMetricsViaAPI($competitor->instagram_handle, $accessToken);
                if ($instagramData) {
                    $dataSources['instagram'] = 'api';
                }
            }

            // Fallback to scraper
            if (!$instagramData) {
                $instagramData = $this->getInstagramMetrics($competitor->instagram_handle);
                if ($instagramData) {
                    $dataSources['instagram'] = 'scraper';
                }
            }

            if ($instagramData) {
                $metrics = array_merge($metrics, $instagramData);
            } else {
                $dataSources['instagram'] = 'manual_required';
            }
        }

        // Facebook metrics - hybrid approach
        if ($competitor->facebook_page) {
            $facebookData = null;

            // Try Graph API first if we have access token
            if ($accessToken) {
                $facebookData = $this->getFacebookMetricsViaAPI($competitor->facebook_page, $accessToken);
                if ($facebookData) {
                    $dataSources['facebook'] = 'api';
                }
            }

            // Fallback to scraper
            if (!$facebookData) {
                $facebookData = $this->getFacebookMetrics($competitor->facebook_page);
                if ($facebookData) {
                    $dataSources['facebook'] = 'scraper';
                }
            }

            if ($facebookData) {
                $metrics = array_merge($metrics, $facebookData);
            } else {
                $dataSources['facebook'] = 'manual_required';
            }
        }

        // Store data sources info
        if (!empty($dataSources)) {
            $metrics['_data_sources'] = $dataSources;
        }

        return $metrics;
    }

    /**
     * Get Instagram metrics via Graph API (when business has Meta integration)
     */
    protected function getInstagramMetricsViaAPI(string $handle, string $accessToken): ?array
    {
        Log::info('Trying Instagram Graph API', ['handle' => $handle]);

        try {
            // First, search for the Instagram business account
            $searchResponse = Http::get('https://graph.facebook.com/v18.0/ig_hashtag_search', [
                'user_id' => 'me',
                'q' => ltrim($handle, '@'),
                'access_token' => $accessToken,
            ]);

            // If direct API access doesn't work, try the scraper service's API method
            $metrics = $this->scraper->getInstagramMetricsViaAPI($handle, $accessToken);

            if ($metrics) {
                Log::info('Instagram Graph API success', ['handle' => $handle]);
                return $metrics;
            }

            return null;

        } catch (\Exception $e) {
            Log::warning('Instagram Graph API failed', [
                'handle' => $handle,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Get Facebook metrics via Graph API (when business has Meta integration)
     */
    protected function getFacebookMetricsViaAPI(string $pageId, string $accessToken): ?array
    {
        Log::info('Trying Facebook Graph API', ['page_id' => $pageId]);

        try {
            $metrics = $this->scraper->getFacebookMetricsViaAPI($pageId, $accessToken);

            if ($metrics) {
                Log::info('Facebook Graph API success', ['page_id' => $pageId]);
                return $metrics;
            }

            return null;

        } catch (\Exception $e) {
            Log::warning('Facebook Graph API failed', [
                'page_id' => $pageId,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Get Instagram metrics using SocialMediaScraperService
     */
    protected function getInstagramMetrics(string $handle): ?array
    {
        Log::info('Fetching Instagram metrics', ['handle' => $handle]);

        try {
            $metrics = $this->scraper->getInstagramMetrics($handle);

            if ($metrics) {
                Log::info('Instagram metrics fetched successfully', [
                    'handle' => $handle,
                    'followers' => $metrics['instagram_followers'] ?? null,
                ]);
            } else {
                Log::warning('Instagram metrics not available', ['handle' => $handle]);
            }

            return $metrics;

        } catch (\Exception $e) {
            Log::error('Instagram metrics fetch failed', [
                'handle' => $handle,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Get Telegram metrics using SocialMediaScraperService
     */
    protected function getTelegramMetrics(string $handle): ?array
    {
        Log::info('Fetching Telegram metrics', ['handle' => $handle]);

        try {
            $metrics = $this->scraper->getTelegramMetrics($handle);

            if ($metrics) {
                Log::info('Telegram metrics fetched successfully', [
                    'handle' => $handle,
                    'members' => $metrics['telegram_members'] ?? null,
                ]);
            } else {
                Log::warning('Telegram metrics not available', ['handle' => $handle]);
            }

            return $metrics;

        } catch (\Exception $e) {
            Log::error('Telegram metrics fetch failed', [
                'handle' => $handle,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Get Facebook metrics using SocialMediaScraperService
     */
    protected function getFacebookMetrics(string $pageId): ?array
    {
        Log::info('Fetching Facebook metrics', ['page_id' => $pageId]);

        try {
            $metrics = $this->scraper->getFacebookMetrics($pageId);

            if ($metrics) {
                Log::info('Facebook metrics fetched successfully', [
                    'page_id' => $pageId,
                    'followers' => $metrics['facebook_followers'] ?? null,
                ]);
            } else {
                Log::warning('Facebook metrics not available', ['page_id' => $pageId]);
            }

            return $metrics;

        } catch (\Exception $e) {
            Log::error('Facebook metrics fetch failed', [
                'page_id' => $pageId,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Save metrics to database
     */
    protected function saveMetrics(Competitor $competitor, array $metrics): void
    {
        // Extract data sources info before saving
        $dataSources = $metrics['_data_sources'] ?? [];
        unset($metrics['_data_sources']);

        // Determine overall data source
        $dataSource = 'scraper';
        if (in_array('api', $dataSources)) {
            $dataSource = 'api';
        } elseif (empty(array_filter($dataSources, fn($s) => $s !== 'manual_required'))) {
            $dataSource = 'manual';
        }

        $metric = CompetitorMetric::updateOrCreate(
            [
                'competitor_id' => $competitor->id,
                'recorded_date' => today(),
            ],
            array_merge($metrics, [
                'data_source' => $dataSource,
                'raw_data' => [
                    'sources' => $dataSources,
                    'collected_at' => now()->toIso8601String(),
                ],
            ])
        );

        // Calculate growth rates
        $metric->calculateGrowthRates();
    }

    /**
     * Scan for new activities
     */
    protected function scanActivities(Competitor $competitor): array
    {
        $activities = [];

        // Scan Instagram posts
        if ($competitor->instagram_handle) {
            $instagramPosts = $this->scanInstagramPosts($competitor);
            $activities = array_merge($activities, $instagramPosts);
        }

        // Scan Telegram posts
        if ($competitor->telegram_handle) {
            $telegramPosts = $this->scanTelegramPosts($competitor);
            $activities = array_merge($activities, $telegramPosts);
        }

        return $activities;
    }

    /**
     * Scan Instagram posts and save to competitor_contents
     */
    protected function scanInstagramPosts(Competitor $competitor): array
    {
        if (!$competitor->instagram_handle) {
            return [];
        }

        $activities = [];

        try {
            $posts = $this->scraper->getInstagramPosts($competitor->instagram_handle, 12);

            if ($posts) {
                foreach ($posts as $post) {
                    // Check if post already exists
                    $exists = $competitor->contents()
                        ->where('platform', 'instagram')
                        ->where('external_id', $post['id'])
                        ->exists();

                    if (!$exists) {
                        $content = $competitor->contents()->create([
                            'platform' => 'instagram',
                            'external_id' => $post['id'],
                            'content_type' => $post['type'] ?? 'image',
                            'caption' => $post['caption'] ?? null,
                            'permalink' => $post['permalink'] ?? null,
                            'thumbnail_url' => $post['thumbnail_url'] ?? null,
                            'media_urls' => $post['media_urls'] ?? null,
                            'likes_count' => $post['likes_count'] ?? 0,
                            'comments_count' => $post['comments_count'] ?? 0,
                            'shares_count' => $post['shares_count'] ?? 0,
                            'views_count' => $post['views_count'] ?? null,
                            'published_at' => isset($post['timestamp']) ? Carbon::parse($post['timestamp']) : now(),
                            'hashtags' => $this->extractHashtags($post['caption'] ?? ''),
                            'mentions' => $this->extractMentions($post['caption'] ?? ''),
                        ]);

                        $activities[] = $content;
                    }
                }

                Log::info('Instagram posts scanned', [
                    'competitor_id' => $competitor->id,
                    'new_posts' => count($activities),
                ]);
            }

        } catch (\Exception $e) {
            Log::warning('Instagram post scanning failed', [
                'competitor_id' => $competitor->id,
                'error' => $e->getMessage(),
            ]);
        }

        return $activities;
    }

    /**
     * Scan Telegram posts and save to competitor_contents
     */
    protected function scanTelegramPosts(Competitor $competitor): array
    {
        if (!$competitor->telegram_handle) {
            return [];
        }

        $activities = [];

        try {
            $posts = $this->scraper->getTelegramPosts($competitor->telegram_handle, 20);

            if ($posts) {
                foreach ($posts as $post) {
                    // Check if post already exists
                    $exists = $competitor->contents()
                        ->where('platform', 'telegram')
                        ->where('external_id', $post['id'])
                        ->exists();

                    if (!$exists) {
                        $content = $competitor->contents()->create([
                            'platform' => 'telegram',
                            'external_id' => $post['id'],
                            'content_type' => $post['type'] ?? 'text',
                            'caption' => $post['text'] ?? null,
                            'permalink' => $post['url'] ?? null,
                            'thumbnail_url' => $post['photo_url'] ?? null,
                            'media_urls' => $post['media_urls'] ?? null,
                            'views_count' => $post['views'] ?? null,
                            'published_at' => isset($post['date']) ? Carbon::parse($post['date']) : now(),
                            'hashtags' => $this->extractHashtags($post['text'] ?? ''),
                            'mentions' => $this->extractMentions($post['text'] ?? ''),
                        ]);

                        $activities[] = $content;
                    }
                }

                Log::info('Telegram posts scanned', [
                    'competitor_id' => $competitor->id,
                    'new_posts' => count($activities),
                ]);
            }

        } catch (\Exception $e) {
            Log::warning('Telegram post scanning failed', [
                'competitor_id' => $competitor->id,
                'error' => $e->getMessage(),
            ]);
        }

        return $activities;
    }

    /**
     * Extract hashtags from text
     */
    protected function extractHashtags(string $text): array
    {
        preg_match_all('/#(\w+)/u', $text, $matches);
        return array_unique($matches[1] ?? []);
    }

    /**
     * Extract mentions from text
     */
    protected function extractMentions(string $text): array
    {
        preg_match_all('/@(\w+)/u', $text, $matches);
        return array_unique($matches[1] ?? []);
    }

    /**
     * Check for alerts based on metrics
     */
    protected function checkForAlerts(Competitor $competitor, array $metrics): array
    {
        $alerts = [];

        // Get previous metrics
        $previousMetric = CompetitorMetric::where('competitor_id', $competitor->id)
            ->where('recorded_date', '<', today())
            ->orderBy('recorded_date', 'desc')
            ->first();

        if (!$previousMetric) {
            return $alerts;
        }

        // Check for follower surge (>10% growth)
        if (isset($metrics['instagram_followers']) && $previousMetric->instagram_followers) {
            $growthRate = (($metrics['instagram_followers'] - $previousMetric->instagram_followers) / $previousMetric->instagram_followers) * 100;

            if ($growthRate >= 10) {
                $alert = $this->createAlert($competitor, [
                    'type' => 'follower_surge',
                    'severity' => 'high',
                    'title' => "{$competitor->name} - Follower Surge Detected",
                    'message' => "Instagram followers grew by {$growthRate}% in one day ({$previousMetric->instagram_followers} → {$metrics['instagram_followers']})",
                    'data' => [
                        'growth_rate' => round($growthRate, 2),
                        'previous_count' => $previousMetric->instagram_followers,
                        'current_count' => $metrics['instagram_followers'],
                    ],
                ]);

                $alerts[] = $alert;
            }
        }

        // Check for engagement spike
        if (isset($metrics['instagram_engagement_rate']) && $previousMetric->instagram_engagement_rate) {
            $engagementGrowth = (($metrics['instagram_engagement_rate'] - $previousMetric->instagram_engagement_rate) / $previousMetric->instagram_engagement_rate) * 100;

            if ($engagementGrowth >= 50) { // 50% increase in engagement
                $alert = $this->createAlert($competitor, [
                    'type' => 'engagement_spike',
                    'severity' => 'medium',
                    'title' => "{$competitor->name} - Engagement Spike",
                    'message' => "Engagement rate increased by {$engagementGrowth}% ({$previousMetric->instagram_engagement_rate}% → {$metrics['instagram_engagement_rate']}%)",
                    'data' => [
                        'growth_rate' => round($engagementGrowth, 2),
                        'previous_rate' => $previousMetric->instagram_engagement_rate,
                        'current_rate' => $metrics['instagram_engagement_rate'],
                    ],
                ]);

                $alerts[] = $alert;
            }
        }

        return $alerts;
    }

    /**
     * Create an alert
     */
    protected function createAlert(Competitor $competitor, array $data): CompetitorAlert
    {
        return CompetitorAlert::create(array_merge($data, [
            'competitor_id' => $competitor->id,
            'business_id' => $competitor->business_id,
        ]));
    }

    /**
     * Manual metric entry
     */
    public function recordManualMetrics(Competitor $competitor, array $metrics, ?Carbon $date = null): CompetitorMetric
    {
        $metric = CompetitorMetric::updateOrCreate(
            [
                'competitor_id' => $competitor->id,
                'recorded_date' => $date ?? today(),
            ],
            array_merge($metrics, [
                'data_source' => 'manual',
            ])
        );

        // Calculate growth rates
        $metric->calculateGrowthRates();

        // Check for alerts
        $this->checkForAlerts($competitor, $metrics);

        return $metric;
    }

    /**
     * Monitor all active competitors for a business
     */
    public function monitorAllCompetitors($businessId): array
    {
        $results = [
            'total' => 0,
            'successful' => 0,
            'failed' => 0,
            'details' => [],
        ];

        $competitors = Competitor::where('business_id', $businessId)
            ->where('status', 'active')
            ->where('auto_monitor', true)
            ->get();

        $results['total'] = $competitors->count();

        foreach ($competitors as $competitor) {
            $result = $this->monitorCompetitor($competitor);

            if ($result['success']) {
                $results['successful']++;
            } else {
                $results['failed']++;
            }

            $results['details'][$competitor->id] = $result;
        }

        return $results;
    }
}
