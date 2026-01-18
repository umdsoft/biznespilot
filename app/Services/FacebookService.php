<?php

namespace App\Services;

use App\Models\FacebookMetric;
use App\Models\MarketingChannel;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class FacebookService
{
    /**
     * Facebook Graph API base URL
     */
    private const API_BASE_URL = 'https://graph.facebook.com/v18.0';

    /**
     * Fetch and store Facebook metrics for a channel
     */
    public function syncMetrics(MarketingChannel $channel, ?Carbon $date = null): ?FacebookMetric
    {
        if ($channel->type !== 'facebook') {
            Log::error('Channel is not Facebook type', ['channel_id' => $channel->id]);

            return null;
        }

        if (! $channel->access_token) {
            Log::error('Facebook channel missing access token', ['channel_id' => $channel->id]);

            return null;
        }

        $date = $date ?? Carbon::today();

        try {
            // Fetch page insights
            $pageInsights = $this->fetchPageInsights($channel->access_token, $channel->platform_account_id, $date);

            // Fetch post insights
            $postInsights = $this->fetchPostInsights($channel->access_token, $channel->platform_account_id, $date);

            // Fetch video insights
            $videoInsights = $this->fetchVideoInsights($channel->access_token, $channel->platform_account_id, $date);

            // Fetch CTA clicks
            $ctaClicks = $this->fetchCTAClicks($channel->access_token, $channel->platform_account_id, $date);

            // Combine and store metrics
            $metric = FacebookMetric::updateOrCreate(
                [
                    'marketing_channel_id' => $channel->id,
                    'metric_date' => $date,
                ],
                [
                    'page_likes' => $pageInsights['page_fans'] ?? 0,
                    'page_followers' => $pageInsights['page_followers'] ?? 0,
                    'new_likes' => $pageInsights['page_fan_adds'] ?? 0,
                    'new_followers' => $pageInsights['page_followers_online'] ?? 0,
                    'posts_count' => $postInsights['posts_count'] ?? 0,
                    'reach' => $postInsights['reach'] ?? 0,
                    'impressions' => $postInsights['impressions'] ?? 0,
                    'likes' => $postInsights['likes'] ?? 0,
                    'comments' => $postInsights['comments'] ?? 0,
                    'shares' => $postInsights['shares'] ?? 0,
                    'reactions' => $postInsights['reactions'] ?? 0,
                    'video_views' => $videoInsights['video_views'] ?? 0,
                    'video_reach' => $videoInsights['video_reach'] ?? 0,
                    'average_watch_time' => $videoInsights['avg_watch_time'] ?? 0,
                    'page_views' => $pageInsights['page_views_total'] ?? 0,
                    'page_views_unique' => $pageInsights['page_views_unique'] ?? 0,
                    'cta_clicks' => $ctaClicks['cta_clicks'] ?? 0,
                    'website_clicks' => $ctaClicks['website_clicks'] ?? 0,
                    'phone_clicks' => $ctaClicks['phone_clicks'] ?? 0,
                    'direction_clicks' => $ctaClicks['direction_clicks'] ?? 0,
                ]
            );

            // Calculate engagement rate
            $engagementRate = $metric->calculateEngagementRate();
            $metric->update(['engagement_rate' => $engagementRate]);

            Log::info('Facebook metrics synced successfully', [
                'channel_id' => $channel->id,
                'date' => $date->toDateString(),
            ]);

            return $metric;

        } catch (\Exception $e) {
            Log::error('Failed to sync Facebook metrics', [
                'channel_id' => $channel->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return null;
        }
    }

    /**
     * Fetch page-level insights
     */
    private function fetchPageInsights(string $accessToken, string $pageId, Carbon $date): array
    {
        try {
            $since = $date->copy()->startOfDay()->timestamp;
            $until = $date->copy()->endOfDay()->timestamp;

            // Fetch page insights
            $response = Http::get(self::API_BASE_URL."/{$pageId}/insights", [
                'metric' => implode(',', [
                    'page_fans',
                    'page_followers_count',
                    'page_fan_adds',
                    'page_fan_removes',
                    'page_views_total',
                    'page_views_unique',
                    'page_impressions',
                    'page_impressions_unique',
                    'page_engaged_users',
                ]),
                'period' => 'day',
                'since' => $since,
                'until' => $until,
                'access_token' => $accessToken,
            ]);

            if (! $response->successful()) {
                Log::error('Facebook page insights request failed', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);

                return [];
            }

            $insights = $response->json()['data'] ?? [];
            $insightsData = [];

            foreach ($insights as $insight) {
                $metricName = $insight['name'];
                $values = $insight['values'] ?? [];
                if (! empty($values)) {
                    $insightsData[$metricName] = $values[0]['value'] ?? 0;
                }
            }

            // Get current page info for likes and followers count
            $pageResponse = Http::get(self::API_BASE_URL."/{$pageId}", [
                'fields' => 'fan_count,followers_count',
                'access_token' => $accessToken,
            ]);

            if ($pageResponse->successful()) {
                $pageData = $pageResponse->json();
                $insightsData['page_fans'] = $pageData['fan_count'] ?? 0;
                $insightsData['page_followers'] = $pageData['followers_count'] ?? 0;
            }

            return [
                'page_fans' => $insightsData['page_fans'] ?? 0,
                'page_followers' => $insightsData['page_followers'] ?? 0,
                'page_fan_adds' => $insightsData['page_fan_adds'] ?? 0,
                'page_fan_removes' => $insightsData['page_fan_removes'] ?? 0,
                'page_views_total' => $insightsData['page_views_total'] ?? 0,
                'page_views_unique' => $insightsData['page_views_unique'] ?? 0,
                'page_impressions' => $insightsData['page_impressions'] ?? 0,
                'page_engaged_users' => $insightsData['page_engaged_users'] ?? 0,
            ];

        } catch (\Exception $e) {
            Log::error('Failed to fetch Facebook page insights', [
                'error' => $e->getMessage(),
            ]);

            return [];
        }
    }

    /**
     * Fetch post-level insights
     */
    private function fetchPostInsights(string $accessToken, string $pageId, Carbon $date): array
    {
        try {
            $since = $date->copy()->startOfDay()->timestamp;
            $until = $date->copy()->endOfDay()->timestamp;

            // Get posts published on the specified date
            $postsResponse = Http::get(self::API_BASE_URL."/{$pageId}/posts", [
                'fields' => 'id,created_time,message,shares',
                'since' => $since,
                'until' => $until,
                'access_token' => $accessToken,
            ]);

            if (! $postsResponse->successful()) {
                return [];
            }

            $posts = $postsResponse->json()['data'] ?? [];
            $insights = [
                'posts_count' => count($posts),
                'reach' => 0,
                'impressions' => 0,
                'likes' => 0,
                'comments' => 0,
                'shares' => 0,
                'reactions' => 0,
            ];

            foreach ($posts as $post) {
                $postId = $post['id'];

                // Get post insights
                $postInsightsResponse = Http::get(self::API_BASE_URL."/{$postId}/insights", [
                    'metric' => 'post_impressions,post_impressions_unique,post_engaged_users',
                    'access_token' => $accessToken,
                ]);

                if ($postInsightsResponse->successful()) {
                    $postInsights = $postInsightsResponse->json()['data'] ?? [];
                    foreach ($postInsights as $insight) {
                        switch ($insight['name']) {
                            case 'post_impressions':
                                $insights['impressions'] += $insight['values'][0]['value'] ?? 0;
                                break;
                            case 'post_impressions_unique':
                                $insights['reach'] += $insight['values'][0]['value'] ?? 0;
                                break;
                        }
                    }
                }

                // Get post reactions
                $reactionsResponse = Http::get(self::API_BASE_URL."/{$postId}", [
                    'fields' => 'likes.summary(true),comments.summary(true),reactions.summary(true)',
                    'access_token' => $accessToken,
                ]);

                if ($reactionsResponse->successful()) {
                    $reactionsData = $reactionsResponse->json();
                    $insights['likes'] += $reactionsData['likes']['summary']['total_count'] ?? 0;
                    $insights['comments'] += $reactionsData['comments']['summary']['total_count'] ?? 0;
                    $insights['reactions'] += $reactionsData['reactions']['summary']['total_count'] ?? 0;
                }

                // Get shares count
                $insights['shares'] += $post['shares']['count'] ?? 0;
            }

            return $insights;

        } catch (\Exception $e) {
            Log::error('Failed to fetch Facebook post insights', [
                'error' => $e->getMessage(),
            ]);

            return [
                'posts_count' => 0,
                'reach' => 0,
                'impressions' => 0,
                'likes' => 0,
                'comments' => 0,
                'shares' => 0,
                'reactions' => 0,
            ];
        }
    }

    /**
     * Fetch video insights
     */
    private function fetchVideoInsights(string $accessToken, string $pageId, Carbon $date): array
    {
        try {
            $since = $date->copy()->startOfDay()->timestamp;
            $until = $date->copy()->endOfDay()->timestamp;

            // Get videos published on the specified date
            $videosResponse = Http::get(self::API_BASE_URL."/{$pageId}/videos", [
                'fields' => 'id,created_time',
                'since' => $since,
                'until' => $until,
                'access_token' => $accessToken,
            ]);

            if (! $videosResponse->successful()) {
                return [];
            }

            $videos = $videosResponse->json()['data'] ?? [];
            $insights = [
                'video_views' => 0,
                'video_reach' => 0,
                'avg_watch_time' => 0,
            ];

            $totalWatchTime = 0;
            $videoCount = count($videos);

            foreach ($videos as $video) {
                $videoId = $video['id'];

                // Get video insights
                $videoInsightsResponse = Http::get(self::API_BASE_URL."/{$videoId}/video_insights", [
                    'metric' => 'total_video_views,total_video_views_unique,total_video_avg_time_watched',
                    'access_token' => $accessToken,
                ]);

                if ($videoInsightsResponse->successful()) {
                    $videoInsights = $videoInsightsResponse->json()['data'] ?? [];
                    foreach ($videoInsights as $insight) {
                        switch ($insight['name']) {
                            case 'total_video_views':
                                $insights['video_views'] += $insight['values'][0]['value'] ?? 0;
                                break;
                            case 'total_video_views_unique':
                                $insights['video_reach'] += $insight['values'][0]['value'] ?? 0;
                                break;
                            case 'total_video_avg_time_watched':
                                $totalWatchTime += $insight['values'][0]['value'] ?? 0;
                                break;
                        }
                    }
                }
            }

            if ($videoCount > 0) {
                $insights['avg_watch_time'] = round($totalWatchTime / $videoCount, 2);
            }

            return $insights;

        } catch (\Exception $e) {
            Log::error('Failed to fetch Facebook video insights', [
                'error' => $e->getMessage(),
            ]);

            return [
                'video_views' => 0,
                'video_reach' => 0,
                'avg_watch_time' => 0,
            ];
        }
    }

    /**
     * Fetch CTA clicks
     */
    private function fetchCTAClicks(string $accessToken, string $pageId, Carbon $date): array
    {
        try {
            $since = $date->copy()->startOfDay()->timestamp;
            $until = $date->copy()->endOfDay()->timestamp;

            $response = Http::get(self::API_BASE_URL."/{$pageId}/insights", [
                'metric' => implode(',', [
                    'page_total_actions',
                    'page_cta_clicks_logged_in_total',
                    'page_website_clicks',
                    'page_call_phone_clicks',
                    'page_get_directions_clicks',
                ]),
                'period' => 'day',
                'since' => $since,
                'until' => $until,
                'access_token' => $accessToken,
            ]);

            if (! $response->successful()) {
                return [];
            }

            $insights = $response->json()['data'] ?? [];
            $clicksData = [];

            foreach ($insights as $insight) {
                $metricName = $insight['name'];
                $values = $insight['values'] ?? [];
                if (! empty($values)) {
                    $clicksData[$metricName] = $values[0]['value'] ?? 0;
                }
            }

            return [
                'cta_clicks' => $clicksData['page_cta_clicks_logged_in_total'] ?? 0,
                'website_clicks' => $clicksData['page_website_clicks'] ?? 0,
                'phone_clicks' => $clicksData['page_call_phone_clicks'] ?? 0,
                'direction_clicks' => $clicksData['page_get_directions_clicks'] ?? 0,
            ];

        } catch (\Exception $e) {
            Log::error('Failed to fetch Facebook CTA clicks', [
                'error' => $e->getMessage(),
            ]);

            return [
                'cta_clicks' => 0,
                'website_clicks' => 0,
                'phone_clicks' => 0,
                'direction_clicks' => 0,
            ];
        }
    }

    /**
     * Validate and refresh access token if needed
     */
    public function refreshAccessToken(string $accessToken): ?string
    {
        try {
            // Check token validity
            $response = Http::get(self::API_BASE_URL.'/debug_token', [
                'input_token' => $accessToken,
                'access_token' => $accessToken,
            ]);

            if (! $response->successful()) {
                return null;
            }

            $tokenData = $response->json()['data'] ?? [];
            $expiresAt = $tokenData['expires_at'] ?? 0;

            // If token expires in less than 7 days, exchange it
            if ($expiresAt > 0 && $expiresAt < (time() + (7 * 24 * 60 * 60))) {
                return $this->exchangeForLongLivedToken($accessToken);
            }

            return $accessToken;

        } catch (\Exception $e) {
            Log::error('Failed to refresh Facebook access token', [
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Exchange short-lived token for long-lived token
     */
    private function exchangeForLongLivedToken(string $shortLivedToken): ?string
    {
        try {
            $response = Http::get(self::API_BASE_URL.'/oauth/access_token', [
                'grant_type' => 'fb_exchange_token',
                'client_id' => config('services.facebook.client_id'),
                'client_secret' => config('services.facebook.client_secret'),
                'fb_exchange_token' => $shortLivedToken,
            ]);

            if (! $response->successful()) {
                return null;
            }

            return $response->json()['access_token'] ?? null;

        } catch (\Exception $e) {
            Log::error('Failed to exchange Facebook token', [
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Sync metrics for date range
     *
     * @return int Number of days synced
     */
    public function syncMetricsRange(MarketingChannel $channel, Carbon $startDate, Carbon $endDate): int
    {
        $synced = 0;
        $currentDate = $startDate->copy();

        while ($currentDate->lte($endDate)) {
            $metric = $this->syncMetrics($channel, $currentDate);
            if ($metric) {
                $synced++;
            }
            $currentDate->addDay();
        }

        return $synced;
    }
}
