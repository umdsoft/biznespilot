<?php

namespace App\Services;

use App\Models\InstagramMetric;
use App\Models\MarketingChannel;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class InstagramService
{
    /**
     * Instagram Graph API base URL
     */
    private const API_BASE_URL = 'https://graph.facebook.com/v18.0';

    /**
     * Fetch and store Instagram metrics for a channel
     */
    public function syncMetrics(MarketingChannel $channel, ?Carbon $date = null): ?InstagramMetric
    {
        if ($channel->type !== 'instagram') {
            Log::error('Channel is not Instagram type', ['channel_id' => $channel->id]);

            return null;
        }

        if (! $channel->access_token) {
            Log::error('Instagram channel missing access token', ['channel_id' => $channel->id]);

            return null;
        }

        $date = $date ?? Carbon::today();

        try {
            // Fetch account insights
            $accountInsights = $this->fetchAccountInsights($channel->access_token, $channel->platform_account_id);

            // Fetch media insights (posts, stories, reels)
            $mediaInsights = $this->fetchMediaInsights($channel->access_token, $channel->platform_account_id, $date);

            // Combine and store metrics
            $metric = InstagramMetric::updateOrCreate(
                [
                    'marketing_channel_id' => $channel->id,
                    'metric_date' => $date,
                ],
                [
                    'followers_count' => $accountInsights['followers_count'] ?? 0,
                    'following_count' => $accountInsights['following_count'] ?? 0,
                    'media_count' => $accountInsights['media_count'] ?? 0,
                    'likes' => $mediaInsights['likes'] ?? 0,
                    'comments' => $mediaInsights['comments'] ?? 0,
                    'shares' => $mediaInsights['shares'] ?? 0,
                    'saves' => $mediaInsights['saves'] ?? 0,
                    'reach' => $mediaInsights['reach'] ?? 0,
                    'impressions' => $mediaInsights['impressions'] ?? 0,
                    'profile_views' => $accountInsights['profile_views'] ?? 0,
                    'stories_posted' => $mediaInsights['stories_posted'] ?? 0,
                    'stories_reach' => $mediaInsights['stories_reach'] ?? 0,
                    'stories_impressions' => $mediaInsights['stories_impressions'] ?? 0,
                    'stories_replies' => $mediaInsights['stories_replies'] ?? 0,
                    'reels_posted' => $mediaInsights['reels_posted'] ?? 0,
                    'reels_plays' => $mediaInsights['reels_plays'] ?? 0,
                    'reels_reach' => $mediaInsights['reels_reach'] ?? 0,
                    'reels_likes' => $mediaInsights['reels_likes'] ?? 0,
                    'reels_comments' => $mediaInsights['reels_comments'] ?? 0,
                    'reels_shares' => $mediaInsights['reels_shares'] ?? 0,
                    'website_clicks' => $accountInsights['website_clicks'] ?? 0,
                    'email_contacts' => $accountInsights['email_contacts'] ?? 0,
                    'phone_calls' => $accountInsights['phone_calls'] ?? 0,
                ]
            );

            // Calculate engagement rate
            $engagementRate = $metric->calculateEngagementRate();
            $metric->update(['engagement_rate' => $engagementRate]);

            Log::info('Instagram metrics synced successfully', [
                'channel_id' => $channel->id,
                'date' => $date->toDateString(),
            ]);

            return $metric;

        } catch (\Exception $e) {
            Log::error('Failed to sync Instagram metrics', [
                'channel_id' => $channel->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return null;
        }
    }

    /**
     * Fetch account-level insights from Instagram Graph API
     */
    private function fetchAccountInsights(string $accessToken, string $accountId): array
    {
        try {
            // Get basic account info
            $accountResponse = Http::get(self::API_BASE_URL."/{$accountId}", [
                'fields' => 'followers_count,follows_count,media_count',
                'access_token' => $accessToken,
            ]);

            if (! $accountResponse->successful()) {
                Log::error('Instagram account info request failed', [
                    'status' => $accountResponse->status(),
                    'body' => $accountResponse->body(),
                ]);

                return [];
            }

            $accountData = $accountResponse->json();

            // Get account insights (profile views, reach, etc.)
            $insightsResponse = Http::get(self::API_BASE_URL."/{$accountId}/insights", [
                'metric' => 'profile_views,reach,impressions,website_clicks,email_contacts,phone_call_clicks',
                'period' => 'day',
                'access_token' => $accessToken,
            ]);

            $insightsData = [];
            if ($insightsResponse->successful()) {
                $insights = $insightsResponse->json()['data'] ?? [];
                foreach ($insights as $insight) {
                    $metricName = $insight['name'];
                    $value = $insight['values'][0]['value'] ?? 0;
                    $insightsData[$metricName] = $value;
                }
            }

            return [
                'followers_count' => $accountData['followers_count'] ?? 0,
                'following_count' => $accountData['follows_count'] ?? 0,
                'media_count' => $accountData['media_count'] ?? 0,
                'profile_views' => $insightsData['profile_views'] ?? 0,
                'reach' => $insightsData['reach'] ?? 0,
                'impressions' => $insightsData['impressions'] ?? 0,
                'website_clicks' => $insightsData['website_clicks'] ?? 0,
                'email_contacts' => $insightsData['email_contacts'] ?? 0,
                'phone_calls' => $insightsData['phone_call_clicks'] ?? 0,
            ];

        } catch (\Exception $e) {
            Log::error('Failed to fetch Instagram account insights', [
                'error' => $e->getMessage(),
            ]);

            return [];
        }
    }

    /**
     * Fetch media-level insights (posts, stories, reels)
     */
    private function fetchMediaInsights(string $accessToken, string $accountId, Carbon $date): array
    {
        try {
            $insights = [
                'likes' => 0,
                'comments' => 0,
                'shares' => 0,
                'saves' => 0,
                'reach' => 0,
                'impressions' => 0,
                'stories_posted' => 0,
                'stories_reach' => 0,
                'stories_impressions' => 0,
                'stories_replies' => 0,
                'reels_posted' => 0,
                'reels_plays' => 0,
                'reels_reach' => 0,
                'reels_likes' => 0,
                'reels_comments' => 0,
                'reels_shares' => 0,
            ];

            // Get media published on the specified date
            $mediaResponse = Http::get(self::API_BASE_URL."/{$accountId}/media", [
                'fields' => 'id,media_type,timestamp,like_count,comments_count',
                'access_token' => $accessToken,
            ]);

            if (! $mediaResponse->successful()) {
                Log::error('Instagram media request failed', [
                    'status' => $mediaResponse->status(),
                ]);

                return $insights;
            }

            $mediaItems = $mediaResponse->json()['data'] ?? [];

            foreach ($mediaItems as $media) {
                $mediaDate = Carbon::parse($media['timestamp'])->toDateString();

                // Only process media from the specified date
                if ($mediaDate !== $date->toDateString()) {
                    continue;
                }

                $mediaType = $media['media_type'] ?? '';
                $mediaId = $media['id'];

                // Get detailed insights for this media
                $mediaInsightsResponse = Http::get(self::API_BASE_URL."/{$mediaId}/insights", [
                    'metric' => $this->getMetricsForMediaType($mediaType),
                    'access_token' => $accessToken,
                ]);

                if ($mediaInsightsResponse->successful()) {
                    $mediaInsightsData = $mediaInsightsResponse->json()['data'] ?? [];

                    if ($mediaType === 'IMAGE' || $mediaType === 'CAROUSEL_ALBUM') {
                        // Regular posts
                        $insights['likes'] += $media['like_count'] ?? 0;
                        $insights['comments'] += $media['comments_count'] ?? 0;

                        foreach ($mediaInsightsData as $insight) {
                            switch ($insight['name']) {
                                case 'reach':
                                    $insights['reach'] += $insight['values'][0]['value'] ?? 0;
                                    break;
                                case 'impressions':
                                    $insights['impressions'] += $insight['values'][0]['value'] ?? 0;
                                    break;
                                case 'saved':
                                    $insights['saves'] += $insight['values'][0]['value'] ?? 0;
                                    break;
                            }
                        }
                    } elseif ($mediaType === 'VIDEO' && str_contains($media['permalink'] ?? '', '/reel/')) {
                        // Reels
                        $insights['reels_posted']++;
                        $insights['reels_likes'] += $media['like_count'] ?? 0;
                        $insights['reels_comments'] += $media['comments_count'] ?? 0;

                        foreach ($mediaInsightsData as $insight) {
                            switch ($insight['name']) {
                                case 'reach':
                                    $insights['reels_reach'] += $insight['values'][0]['value'] ?? 0;
                                    break;
                                case 'plays':
                                    $insights['reels_plays'] += $insight['values'][0]['value'] ?? 0;
                                    break;
                                case 'shares':
                                    $insights['reels_shares'] += $insight['values'][0]['value'] ?? 0;
                                    break;
                            }
                        }
                    } elseif ($mediaType === 'STORY') {
                        // Stories
                        $insights['stories_posted']++;

                        foreach ($mediaInsightsData as $insight) {
                            switch ($insight['name']) {
                                case 'reach':
                                    $insights['stories_reach'] += $insight['values'][0]['value'] ?? 0;
                                    break;
                                case 'impressions':
                                    $insights['stories_impressions'] += $insight['values'][0]['value'] ?? 0;
                                    break;
                                case 'replies':
                                    $insights['stories_replies'] += $insight['values'][0]['value'] ?? 0;
                                    break;
                            }
                        }
                    }
                }
            }

            return $insights;

        } catch (\Exception $e) {
            Log::error('Failed to fetch Instagram media insights', [
                'error' => $e->getMessage(),
            ]);

            return [
                'likes' => 0,
                'comments' => 0,
                'shares' => 0,
                'saves' => 0,
                'reach' => 0,
                'impressions' => 0,
                'stories_posted' => 0,
                'stories_reach' => 0,
                'stories_impressions' => 0,
                'stories_replies' => 0,
                'reels_posted' => 0,
                'reels_plays' => 0,
                'reels_reach' => 0,
                'reels_likes' => 0,
                'reels_comments' => 0,
                'reels_shares' => 0,
            ];
        }
    }

    /**
     * Get metrics string based on media type
     */
    private function getMetricsForMediaType(string $mediaType): string
    {
        return match ($mediaType) {
            'IMAGE', 'CAROUSEL_ALBUM' => 'engagement,impressions,reach,saved',
            'VIDEO' => 'engagement,impressions,reach,plays,shares',
            'STORY' => 'impressions,reach,replies',
            default => 'engagement,impressions,reach',
        };
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
                Log::error('Failed to validate Instagram access token');

                return null;
            }

            $tokenData = $response->json()['data'] ?? [];
            $expiresAt = $tokenData['expires_at'] ?? 0;

            // If token expires in less than 7 days, refresh it
            if ($expiresAt > 0 && $expiresAt < (time() + (7 * 24 * 60 * 60))) {
                return $this->exchangeForLongLivedToken($accessToken);
            }

            return $accessToken;

        } catch (\Exception $e) {
            Log::error('Failed to refresh Instagram access token', [
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
            Log::error('Failed to exchange Instagram token', [
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
