<?php

namespace App\Services;

use App\Models\Integration;
use App\Models\InstagramAccount;
use App\Models\InstagramMedia;
use App\Models\InstagramDailyInsight;
use App\Models\InstagramAudience;
use App\Models\InstagramHashtagStat;
use App\Models\InstagramSyncLog;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class InstagramSyncService
{
    private const API_VERSION = 'v18.0';
    private const BASE_URL = 'https://graph.facebook.com';

    private string $accessToken;
    private Integration $integration;
    private string $businessId;

    public function initialize(Integration $integration): self
    {
        $this->integration = $integration;
        $this->businessId = $integration->business_id;

        $credentials = json_decode($integration->credentials, true);
        $this->accessToken = $credentials['access_token'] ?? '';

        return $this;
    }

    /**
     * Full sync - syncs all Instagram data (initial sync)
     */
    public function fullSync(): array
    {
        $results = [
            'success' => true,
            'accounts' => 0,
            'media' => 0,
            'insights' => 0,
            'errors' => [],
        ];

        try {
            // 1. Sync Instagram Business Accounts
            $accounts = $this->syncInstagramAccounts();
            $results['accounts'] = count($accounts);

            // 2. For each account, sync media and insights
            foreach ($accounts as $account) {
                $syncLog = $this->startSyncLog($account, 'full');

                try {
                    // Sync all media (posts, reels, stories)
                    $mediaCount = $this->syncAllMedia($account);
                    $results['media'] += $mediaCount;

                    // Sync daily insights (last 30 days available from API)
                    $insightCount = $this->syncDailyInsights($account);
                    $results['insights'] += $insightCount;

                    // Sync audience demographics
                    $this->syncAudienceDemographics($account);

                    // Calculate hashtag stats
                    $this->calculateHashtagStats($account);

                    // Update account with latest stats
                    $this->updateAccountStats($account);

                    $this->completeSyncLog($syncLog, $mediaCount + $insightCount);

                } catch (\Exception $e) {
                    Log::error("Error syncing Instagram account {$account->instagram_id}: " . $e->getMessage());
                    $results['errors'][] = "Account {$account->username}: " . $e->getMessage();
                    $this->failSyncLog($syncLog, $e->getMessage());
                }
            }

            $this->integration->update([
                'last_sync_at' => now(),
                'sync_count' => ($this->integration->sync_count ?? 0) + 1,
            ]);

        } catch (\Exception $e) {
            Log::error("InstagramSyncService fullSync error: " . $e->getMessage());
            $results['success'] = false;
            $results['errors'][] = $e->getMessage();
        }

        return $results;
    }

    /**
     * Incremental sync - only updates metrics (for 2-hour schedule)
     */
    public function incrementalSync(): array
    {
        $results = [
            'success' => true,
            'media_updated' => 0,
            'insights_updated' => 0,
            'errors' => [],
        ];

        try {
            $accounts = InstagramAccount::where('integration_id', $this->integration->id)->get();

            foreach ($accounts as $account) {
                $syncLog = $this->startSyncLog($account, 'incremental');

                try {
                    // Update metrics for recent media (last 7 days)
                    $mediaUpdated = $this->updateRecentMediaMetrics($account);
                    $results['media_updated'] += $mediaUpdated;

                    // Sync today's insights
                    $insightsUpdated = $this->syncTodayInsights($account);
                    $results['insights_updated'] += $insightsUpdated;

                    // Update account follower count
                    $this->updateAccountStats($account);

                    // Check for new media
                    $this->syncNewMedia($account);

                    $this->completeSyncLog($syncLog, $mediaUpdated + $insightsUpdated);

                } catch (\Exception $e) {
                    Log::error("Incremental sync error for {$account->username}: " . $e->getMessage());
                    $results['errors'][] = $e->getMessage();
                    $this->failSyncLog($syncLog, $e->getMessage());
                }
            }

        } catch (\Exception $e) {
            $results['success'] = false;
            $results['errors'][] = $e->getMessage();
        }

        return $results;
    }

    /**
     * Sync Instagram Business Accounts connected to Facebook Pages
     */
    public function syncInstagramAccounts(): array
    {
        $accounts = [];

        // Get all Facebook Pages connected to this user
        $pagesResponse = $this->makeRequest('/me/accounts', [
            'fields' => 'id,name,instagram_business_account',
        ]);

        foreach ($pagesResponse['data'] ?? [] as $page) {
            if (!isset($page['instagram_business_account'])) {
                continue;
            }

            $igAccountId = $page['instagram_business_account']['id'];

            // Get Instagram account details
            $igResponse = $this->makeRequest("/{$igAccountId}", [
                'fields' => 'id,username,name,biography,profile_picture_url,website,followers_count,follows_count,media_count',
            ]);

            // Use withoutGlobalScope to avoid issues with business scope during sync
            $account = InstagramAccount::withoutGlobalScope('business')->updateOrCreate(
                [
                    'integration_id' => $this->integration->id,
                    'instagram_id' => $igAccountId,
                ],
                [
                    'business_id' => $this->businessId,
                    'username' => $igResponse['username'] ?? '',
                    'name' => $igResponse['name'] ?? null,
                    'biography' => $igResponse['biography'] ?? null,
                    'profile_picture_url' => $igResponse['profile_picture_url'] ?? null,
                    'website' => $igResponse['website'] ?? null,
                    'followers_count' => $igResponse['followers_count'] ?? 0,
                    'follows_count' => $igResponse['follows_count'] ?? 0,
                    'media_count' => $igResponse['media_count'] ?? 0,
                    'last_sync_at' => now(),
                    'metadata' => ['facebook_page_id' => $page['id']],
                ]
            );

            // Set first account as primary
            if (empty($accounts)) {
                $account->update(['is_primary' => true]);
            }

            $accounts[] = $account;
        }

        return $accounts;
    }

    /**
     * Sync all media for an account
     */
    public function syncAllMedia(InstagramAccount $account): int
    {
        $count = 0;
        $after = null;

        do {
            $params = [
                'fields' => $this->getMediaFields(),
                'limit' => 50,
            ];

            if ($after) {
                $params['after'] = $after;
            }

            $response = $this->makeRequest("/{$account->instagram_id}/media", $params);

            foreach ($response['data'] ?? [] as $mediaData) {
                $this->saveMedia($account, $mediaData);
                $count++;
            }

            $after = $response['paging']['cursors']['after'] ?? null;

        } while ($after && $count < 500); // Limit to last 500 posts

        return $count;
    }

    /**
     * Sync only new media (for incremental sync)
     */
    public function syncNewMedia(InstagramAccount $account): int
    {
        $count = 0;
        $latestMedia = InstagramMedia::where('account_id', $account->id)
            ->orderByDesc('posted_at')
            ->first();

        $response = $this->makeRequest("/{$account->instagram_id}/media", [
            'fields' => $this->getMediaFields(),
            'limit' => 25,
        ]);

        foreach ($response['data'] ?? [] as $mediaData) {
            $postedAt = Carbon::parse($mediaData['timestamp'] ?? now());

            // Stop if we've reached already synced media
            if ($latestMedia && $postedAt <= $latestMedia->posted_at) {
                break;
            }

            $this->saveMedia($account, $mediaData);
            $count++;
        }

        return $count;
    }

    /**
     * Update metrics for recent media
     */
    public function updateRecentMediaMetrics(InstagramAccount $account): int
    {
        $count = 0;

        // Get media from last 7 days that needs updating
        $recentMedia = InstagramMedia::where('account_id', $account->id)
            ->where('posted_at', '>=', now()->subDays(7))
            ->get();

        foreach ($recentMedia as $media) {
            try {
                $insights = $this->getMediaInsights($media->media_id, $media->media_type);

                $media->update([
                    'reach' => $insights['reach'] ?? $media->reach,
                    'impressions' => $insights['impressions'] ?? $media->impressions,
                    'saves_count' => $insights['saved'] ?? $media->saves_count,
                    'shares_count' => $insights['shares'] ?? $media->shares_count,
                    'video_views' => $insights['video_views'] ?? $media->video_views,
                    'plays' => $insights['plays'] ?? $media->plays,
                    'insights_data' => $insights,
                ]);

                // Also update like and comment counts
                $this->updateMediaBasicMetrics($media);

                $count++;
            } catch (\Exception $e) {
                Log::warning("Failed to update metrics for media {$media->media_id}: " . $e->getMessage());
            }
        }

        return $count;
    }

    /**
     * Save media to database
     */
    private function saveMedia(InstagramAccount $account, array $mediaData): InstagramMedia
    {
        $mediaType = $mediaData['media_type'] ?? 'IMAGE';

        // Get insights for this media
        $insights = [];
        try {
            $insights = $this->getMediaInsights($mediaData['id'], $mediaType);
        } catch (\Exception $e) {
            Log::warning("Could not get insights for media {$mediaData['id']}: " . $e->getMessage());
        }

        $followersCount = $account->followers_count ?: 1;
        $engagement = ($mediaData['like_count'] ?? 0) + ($mediaData['comments_count'] ?? 0);
        $engagementRate = round(($engagement / $followersCount) * 100, 4);

        return InstagramMedia::updateOrCreate(
            ['media_id' => $mediaData['id']],
            [
                'account_id' => $account->id,
                'media_type' => $mediaType,
                'caption' => $mediaData['caption'] ?? '',
                'permalink' => $mediaData['permalink'] ?? null,
                'media_url' => $mediaData['media_url'] ?? null,
                'thumbnail_url' => $mediaData['thumbnail_url'] ?? null,
                'like_count' => $mediaData['like_count'] ?? 0,
                'comments_count' => $mediaData['comments_count'] ?? 0,
                'reach' => $insights['reach'] ?? 0,
                'impressions' => $insights['reach'] ?? 0,
                'saved' => $insights['saved'] ?? 0,
                'shares' => 0,
                'engagement_rate' => $engagementRate,
                'posted_at' => isset($mediaData['timestamp']) ? Carbon::parse($mediaData['timestamp']) : now(),
            ]
        );
    }

    /**
     * Get media insights from API
     * Meta API v22.0+: Only 'reach' and 'saved' are supported for all media types
     * impressions, plays, shares are deprecated
     */
    private function getMediaInsights(string $mediaId, string $mediaType): array
    {
        $insights = [];

        // Meta API v22.0+: Only these metrics are supported now
        $metrics = ['reach', 'saved'];

        try {
            $response = $this->makeRequest("/{$mediaId}/insights", [
                'metric' => implode(',', $metrics),
            ]);

            foreach ($response['data'] ?? [] as $metric) {
                $insights[$metric['name']] = $metric['values'][0]['value'] ?? 0;
            }
        } catch (\Exception $e) {
            Log::debug("Media insights error for {$mediaId}: " . $e->getMessage());
        }

        return $insights;
    }

    /**
     * Sync daily account insights
     * Updated for Meta API v18.0 - uses new metric names and parameters
     */
    public function syncDailyInsights(InstagramAccount $account): int
    {
        $count = 0;
        $dailyData = [];

        // Meta API v18.0: Daily metrics (period=day)
        // These metrics work with period=day
        $dailyMetrics = ['reach', 'follower_count'];

        try {
            $response = $this->makeRequest("/{$account->instagram_id}/insights", [
                'metric' => implode(',', $dailyMetrics),
                'period' => 'day',
                'since' => now()->subDays(30)->timestamp,
                'until' => now()->timestamp,
            ]);

            foreach ($response['data'] ?? [] as $metric) {
                foreach ($metric['values'] ?? [] as $value) {
                    $date = Carbon::parse($value['end_time'])->format('Y-m-d');
                    if (!isset($dailyData[$date])) {
                        $dailyData[$date] = [];
                    }
                    $dailyData[$date][$metric['name']] = $value['value'] ?? 0;
                }
            }
        } catch (\Exception $e) {
            Log::warning("Failed to fetch daily metrics: " . $e->getMessage());
        }

        // Meta API v18.0: Total value metrics (metric_type=total_value)
        // These metrics require metric_type=total_value and work with period=day
        $totalValueMetrics = ['profile_views', 'website_clicks'];

        foreach ($totalValueMetrics as $metric) {
            try {
                $response = $this->makeRequest("/{$account->instagram_id}/insights", [
                    'metric' => $metric,
                    'period' => 'day',
                    'metric_type' => 'total_value',
                    'since' => now()->subDays(30)->timestamp,
                    'until' => now()->timestamp,
                ]);

                foreach ($response['data'] ?? [] as $metricData) {
                    // total_value format returns different structure
                    if (isset($metricData['total_value']['value'])) {
                        // Single total value - distribute across dates
                        $totalValue = $metricData['total_value']['value'];
                        // For simplicity, we'll fetch individual day values if available
                    }

                    // Check for breakdown by day if available
                    foreach ($metricData['values'] ?? [] as $value) {
                        $date = Carbon::parse($value['end_time'])->format('Y-m-d');
                        if (!isset($dailyData[$date])) {
                            $dailyData[$date] = [];
                        }
                        $dailyData[$date][$metricData['name']] = $value['value'] ?? 0;
                    }
                }
            } catch (\Exception $e) {
                Log::warning("Failed to fetch {$metric}: " . $e->getMessage());
            }
        }

        // Save to database
        foreach ($dailyData as $date => $data) {
            InstagramDailyInsight::updateOrCreate(
                [
                    'account_id' => $account->id,
                    'insight_date' => $date,
                ],
                [
                    'impressions' => $data['impressions'] ?? $data['reach'] ?? 0,
                    'reach' => $data['reach'] ?? 0,
                    'profile_views' => $data['profile_views'] ?? 0,
                    'website_clicks' => $data['website_clicks'] ?? 0,
                    'email_contacts' => $data['email_contacts'] ?? 0,
                    'follower_count' => $data['follower_count'] ?? 0,
                ]
            );
            $count++;
        }

        return $count;
    }

    /**
     * Sync today's insights only (for incremental sync)
     * Updated for Meta API v18.0
     */
    public function syncTodayInsights(InstagramAccount $account): int
    {
        $todayData = [];

        // Meta API v18.0: Daily metrics
        try {
            $response = $this->makeRequest("/{$account->instagram_id}/insights", [
                'metric' => 'reach,follower_count',
                'period' => 'day',
                'since' => now()->startOfDay()->timestamp,
                'until' => now()->timestamp,
            ]);

            foreach ($response['data'] ?? [] as $metric) {
                $latestValue = end($metric['values']);
                $todayData[$metric['name']] = $latestValue['value'] ?? 0;
            }
        } catch (\Exception $e) {
            Log::warning("Failed to sync today's daily metrics: " . $e->getMessage());
        }

        // Meta API v18.0: Total value metrics (profile_views)
        try {
            $response = $this->makeRequest("/{$account->instagram_id}/insights", [
                'metric' => 'profile_views',
                'period' => 'day',
                'metric_type' => 'total_value',
                'since' => now()->startOfDay()->timestamp,
                'until' => now()->timestamp,
            ]);

            foreach ($response['data'] ?? [] as $metric) {
                if (isset($metric['total_value']['value'])) {
                    $todayData[$metric['name']] = $metric['total_value']['value'];
                }
            }
        } catch (\Exception $e) {
            Log::warning("Failed to sync today's profile_views: " . $e->getMessage());
        }

        if (!empty($todayData)) {
            InstagramDailyInsight::updateOrCreate(
                [
                    'account_id' => $account->id,
                    'insight_date' => now()->format('Y-m-d'),
                ],
                [
                    'impressions' => $todayData['reach'] ?? 0,
                    'reach' => $todayData['reach'] ?? 0,
                    'profile_views' => $todayData['profile_views'] ?? 0,
                    'follower_count' => $todayData['follower_count'] ?? 0,
                ]
            );
            return 1;
        }

        return 0;
    }

    /**
     * Sync audience demographics
     * Updated for Meta API v18.0 - uses follower_demographics with breakdown parameter
     */
    public function syncAudienceDemographics(InstagramAccount $account): void
    {
        $demographicData = [
            'age_gender' => null,
            'top_cities' => null,
            'top_countries' => null,
            'online_hours' => null,
        ];

        // Meta API v18.0: Get demographics by city
        try {
            $response = $this->makeRequest("/{$account->instagram_id}/insights", [
                'metric' => 'follower_demographics',
                'period' => 'lifetime',
                'metric_type' => 'total_value',
                'breakdown' => 'city',
            ]);

            foreach ($response['data'] ?? [] as $metric) {
                if ($metric['name'] === 'follower_demographics' && isset($metric['total_value']['breakdowns'])) {
                    $breakdowns = $metric['total_value']['breakdowns'][0]['results'] ?? [];
                    $cities = [];
                    foreach ($breakdowns as $item) {
                        $city = $item['dimension_values'][0] ?? 'Unknown';
                        $cities[$city] = $item['value'] ?? 0;
                    }
                    arsort($cities);
                    $demographicData['top_cities'] = array_slice($cities, 0, 20, true);
                }
            }
        } catch (\Exception $e) {
            Log::warning("Failed to fetch city demographics: " . $e->getMessage());
        }

        // Meta API v18.0: Get demographics by country
        try {
            $response = $this->makeRequest("/{$account->instagram_id}/insights", [
                'metric' => 'follower_demographics',
                'period' => 'lifetime',
                'metric_type' => 'total_value',
                'breakdown' => 'country',
            ]);

            foreach ($response['data'] ?? [] as $metric) {
                if ($metric['name'] === 'follower_demographics' && isset($metric['total_value']['breakdowns'])) {
                    $breakdowns = $metric['total_value']['breakdowns'][0]['results'] ?? [];
                    $countries = [];
                    foreach ($breakdowns as $item) {
                        $country = $item['dimension_values'][0] ?? 'Unknown';
                        $countries[$country] = $item['value'] ?? 0;
                    }
                    arsort($countries);
                    $demographicData['top_countries'] = array_slice($countries, 0, 20, true);
                }
            }
        } catch (\Exception $e) {
            Log::warning("Failed to fetch country demographics: " . $e->getMessage());
        }

        // Meta API v18.0: Get demographics by age
        try {
            $response = $this->makeRequest("/{$account->instagram_id}/insights", [
                'metric' => 'follower_demographics',
                'period' => 'lifetime',
                'metric_type' => 'total_value',
                'breakdown' => 'age',
            ]);

            foreach ($response['data'] ?? [] as $metric) {
                if ($metric['name'] === 'follower_demographics' && isset($metric['total_value']['breakdowns'])) {
                    $breakdowns = $metric['total_value']['breakdowns'][0]['results'] ?? [];
                    $ageGender = [];
                    foreach ($breakdowns as $item) {
                        $age = $item['dimension_values'][0] ?? 'Unknown';
                        $ageGender[$age] = $item['value'] ?? 0;
                    }
                    $demographicData['age_gender'] = $ageGender;
                }
            }
        } catch (\Exception $e) {
            Log::warning("Failed to fetch age demographics: " . $e->getMessage());
        }

        // Meta API v18.0: Get demographics by gender (separate call)
        try {
            $response = $this->makeRequest("/{$account->instagram_id}/insights", [
                'metric' => 'follower_demographics',
                'period' => 'lifetime',
                'metric_type' => 'total_value',
                'breakdown' => 'gender',
            ]);

            foreach ($response['data'] ?? [] as $metric) {
                if ($metric['name'] === 'follower_demographics' && isset($metric['total_value']['breakdowns'])) {
                    $breakdowns = $metric['total_value']['breakdowns'][0]['results'] ?? [];
                    $genderData = [];
                    foreach ($breakdowns as $item) {
                        $gender = $item['dimension_values'][0] ?? 'Unknown';
                        $genderData[$gender] = $item['value'] ?? 0;
                    }
                    // Merge gender data with age_gender if it exists
                    if ($demographicData['age_gender']) {
                        $demographicData['age_gender'] = [
                            'by_age' => $demographicData['age_gender'],
                            'by_gender' => $genderData,
                        ];
                    } else {
                        $demographicData['age_gender'] = ['by_gender' => $genderData];
                    }
                }
            }
        } catch (\Exception $e) {
            Log::warning("Failed to fetch gender demographics: " . $e->getMessage());
        }

        // Save to database
        InstagramAudience::updateOrCreate(
            ['account_id' => $account->id],
            [
                'business_id' => $this->businessId,
                'age_gender' => $demographicData['age_gender'],
                'top_cities' => $demographicData['top_cities'],
                'top_countries' => $demographicData['top_countries'],
                'online_hours' => $demographicData['online_hours'],
                'calculated_at' => now(),
            ]
        );
    }

    /**
     * Calculate hashtag performance stats
     */
    public function calculateHashtagStats(InstagramAccount $account): void
    {
        $media = InstagramMedia::where('account_id', $account->id)
            ->whereNotNull('hashtags')
            ->get();

        $hashtagStats = [];

        foreach ($media as $post) {
            foreach ($post->hashtags ?? [] as $hashtag) {
                $tag = strtolower($hashtag);

                if (!isset($hashtagStats[$tag])) {
                    $hashtagStats[$tag] = [
                        'usage_count' => 0,
                        'total_reach' => 0,
                        'total_impressions' => 0,
                        'total_engagement' => 0,
                        'last_used_at' => null,
                    ];
                }

                $hashtagStats[$tag]['usage_count']++;
                $hashtagStats[$tag]['total_reach'] += $post->reach;
                $hashtagStats[$tag]['total_impressions'] += $post->impressions;
                $hashtagStats[$tag]['total_engagement'] += $post->total_engagement;

                if (!$hashtagStats[$tag]['last_used_at'] || $post->posted_at > $hashtagStats[$tag]['last_used_at']) {
                    $hashtagStats[$tag]['last_used_at'] = $post->posted_at;
                }
            }
        }

        // Save to database
        foreach ($hashtagStats as $hashtag => $stats) {
            $avgEngagementRate = $stats['usage_count'] > 0
                ? $stats['total_engagement'] / $stats['usage_count'] / max($account->followers_count, 1) * 100
                : 0;

            InstagramHashtagStat::updateOrCreate(
                [
                    'account_id' => $account->id,
                    'hashtag' => $hashtag,
                ],
                [
                    'business_id' => $this->businessId,
                    'usage_count' => $stats['usage_count'],
                    'total_reach' => $stats['total_reach'],
                    'total_impressions' => $stats['total_impressions'],
                    'total_engagement' => $stats['total_engagement'],
                    'avg_engagement_rate' => round($avgEngagementRate, 4),
                    'last_used_at' => $stats['last_used_at'],
                ]
            );
        }
    }

    /**
     * Update account stats
     */
    private function updateAccountStats(InstagramAccount $account): void
    {
        try {
            $response = $this->makeRequest("/{$account->instagram_id}", [
                'fields' => 'followers_count,follows_count,media_count',
            ]);

            $account->update([
                'followers_count' => $response['followers_count'] ?? $account->followers_count,
                'follows_count' => $response['follows_count'] ?? $account->follows_count,
                'media_count' => $response['media_count'] ?? $account->media_count,
                'last_sync_at' => now(),
            ]);
        } catch (\Exception $e) {
            Log::warning("Failed to update account stats: " . $e->getMessage());
        }
    }

    /**
     * Update basic metrics for a media item
     */
    private function updateMediaBasicMetrics(InstagramMedia $media): void
    {
        try {
            $response = $this->makeRequest("/{$media->media_id}", [
                'fields' => 'like_count,comments_count',
            ]);

            $media->update([
                'like_count' => $response['like_count'] ?? $media->like_count,
                'comments_count' => $response['comments_count'] ?? $media->comments_count,
            ]);
        } catch (\Exception $e) {
            // Silently fail for individual media updates
        }
    }

    /**
     * Extract hashtags from caption
     */
    private function extractHashtags(string $caption): array
    {
        preg_match_all('/#(\w+)/u', $caption, $matches);
        return array_unique($matches[1] ?? []);
    }

    /**
     * Extract mentions from caption
     */
    private function extractMentions(string $caption): array
    {
        preg_match_all('/@(\w+)/u', $caption, $matches);
        return array_unique($matches[1] ?? []);
    }

    /**
     * Get media fields for API request
     */
    private function getMediaFields(): string
    {
        return implode(',', [
            'id',
            'media_type',
            'media_product_type',
            'caption',
            'permalink',
            'thumbnail_url',
            'media_url',
            'like_count',
            'comments_count',
            'timestamp',
        ]);
    }

    /**
     * Make API request
     */
    private function makeRequest(string $endpoint, array $params = []): array
    {
        $url = self::BASE_URL . '/' . self::API_VERSION . $endpoint;
        $params['access_token'] = $this->accessToken;

        $response = Http::timeout(60)->get($url, $params);

        if (!$response->successful()) {
            $error = $response->json('error') ?? ['message' => 'Unknown error'];
            throw new \Exception("Instagram API Error: " . ($error['message'] ?? 'Request failed'));
        }

        return $response->json();
    }

    /**
     * Sync log helpers
     */
    private function startSyncLog(InstagramAccount $account, string $type): InstagramSyncLog
    {
        return InstagramSyncLog::create([
            'account_id' => $account->id,
            'sync_type' => $type,
            'status' => 'running',
            'started_at' => now(),
        ]);
    }

    private function completeSyncLog(InstagramSyncLog $log, int $itemsSynced): void
    {
        $log->update([
            'status' => 'completed',
            'items_synced' => $itemsSynced,
            'completed_at' => now(),
        ]);
    }

    private function failSyncLog(InstagramSyncLog $log, string $error): void
    {
        $log->update([
            'status' => 'failed',
            'error_message' => $error,
            'completed_at' => now(),
        ]);
    }
}
