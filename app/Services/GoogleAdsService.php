<?php

namespace App\Services;

use App\Models\GoogleAdsMetric;
use App\Models\MarketingChannel;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GoogleAdsService
{
    /**
     * Google Ads API version
     */
    private const API_VERSION = 'v15';

    /**
     * Google Ads API base URL
     */
    private const API_BASE_URL = 'https://googleads.googleapis.com/'.self::API_VERSION;

    /**
     * Fetch and store Google Ads metrics for a channel
     *
     * @return array Array of GoogleAdsMetric instances (one per campaign)
     */
    public function syncMetrics(MarketingChannel $channel, ?Carbon $date = null): array
    {
        if ($channel->type !== 'google_ads') {
            Log::error('Channel is not Google Ads type', ['channel_id' => $channel->id]);

            return [];
        }

        if (! $channel->access_token) {
            Log::error('Google Ads channel missing access token', ['channel_id' => $channel->id]);

            return [];
        }

        $date = $date ?? Carbon::today();

        try {
            // Get customer ID from platform_account_id
            $customerId = $channel->platform_account_id;

            // Fetch campaign performance metrics
            $campaigns = $this->fetchCampaignMetrics($channel->access_token, $customerId, $date);

            $metrics = [];

            foreach ($campaigns as $campaign) {
                $metric = GoogleAdsMetric::updateOrCreate(
                    [
                        'marketing_channel_id' => $channel->id,
                        'metric_date' => $date,
                        'campaign_id' => $campaign['campaign_id'],
                    ],
                    [
                        'campaign_name' => $campaign['campaign_name'],
                        'ad_group_id' => $campaign['ad_group_id'] ?? null,
                        'ad_group_name' => $campaign['ad_group_name'] ?? null,
                        'impressions' => $campaign['impressions'] ?? 0,
                        'clicks' => $campaign['clicks'] ?? 0,
                        'conversions' => $campaign['conversions'] ?? 0,
                        'cost' => $campaign['cost'] ?? 0, // Already in kopeks from API
                        'avg_cpc' => $campaign['avg_cpc'] ?? 0,
                        'avg_cpm' => $campaign['avg_cpm'] ?? 0,
                        'avg_cpa' => $campaign['avg_cpa'] ?? 0,
                        'quality_score' => $campaign['quality_score'] ?? 0,
                        'conversion_value' => $campaign['conversion_value'] ?? 0,
                        'video_views' => $campaign['video_views'] ?? 0,
                        'video_quartile_25' => $campaign['video_quartile_25'] ?? 0,
                        'video_quartile_50' => $campaign['video_quartile_50'] ?? 0,
                        'video_quartile_75' => $campaign['video_quartile_75'] ?? 0,
                        'video_quartile_100' => $campaign['video_quartile_100'] ?? 0,
                    ]
                );

                // Calculate CTR, conversion rate, and ROAS
                $ctr = $metric->calculateCtr();
                $conversionRate = $metric->calculateConversionRate();
                $roas = $metric->calculateRoas();

                $metric->update([
                    'ctr' => $ctr,
                    'conversion_rate' => $conversionRate,
                    'roas' => $roas,
                ]);

                $metrics[] = $metric;
            }

            Log::info('Google Ads metrics synced successfully', [
                'channel_id' => $channel->id,
                'date' => $date->toDateString(),
                'campaigns_count' => count($metrics),
            ]);

            return $metrics;

        } catch (\Exception $e) {
            Log::error('Failed to sync Google Ads metrics', [
                'channel_id' => $channel->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return [];
        }
    }

    /**
     * Fetch campaign metrics using Google Ads API
     */
    private function fetchCampaignMetrics(string $accessToken, string $customerId, Carbon $date): array
    {
        try {
            $dateString = $date->format('Y-m-d');

            // Build GAQL query for campaign metrics
            $query = "
                SELECT
                    campaign.id,
                    campaign.name,
                    ad_group.id,
                    ad_group.name,
                    metrics.impressions,
                    metrics.clicks,
                    metrics.conversions,
                    metrics.cost_micros,
                    metrics.average_cpc,
                    metrics.average_cpm,
                    metrics.average_cost,
                    metrics.conversions_value,
                    metrics.video_views,
                    metrics.video_quartile_p25_rate,
                    metrics.video_quartile_p50_rate,
                    metrics.video_quartile_p75_rate,
                    metrics.video_quartile_p100_rate,
                    metrics.search_impression_share,
                    metrics.quality_score
                FROM campaign
                WHERE
                    segments.date = '{$dateString}'
                    AND campaign.status = 'ENABLED'
            ";

            $response = Http::withHeaders([
                'Authorization' => 'Bearer '.$accessToken,
                'developer-token' => config('services.google_ads.developer_token'),
                'login-customer-id' => $customerId,
            ])->post(self::API_BASE_URL."/customers/{$customerId}/googleAds:searchStream", [
                'query' => $query,
            ]);

            if (! $response->successful()) {
                Log::error('Google Ads API request failed', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);

                return [];
            }

            $results = $response->json();
            $campaigns = [];

            foreach ($results as $result) {
                if (! isset($result['results'])) {
                    continue;
                }

                foreach ($result['results'] as $row) {
                    $campaign = $row['campaign'] ?? [];
                    $adGroup = $row['adGroup'] ?? [];
                    $metrics = $row['metrics'] ?? [];

                    // Convert cost from micros to kopeks
                    // 1 USD = 1,000,000 micros
                    // 1 USD = 100 cents (kopeks)
                    // So: micros / 10,000 = kopeks
                    $costInKopeks = isset($metrics['costMicros'])
                        ? (int) round($metrics['costMicros'] / 10000)
                        : 0;

                    $avgCpcInKopeks = isset($metrics['averageCpc'])
                        ? (int) round($metrics['averageCpc'] / 10000)
                        : 0;

                    $avgCpmInKopeks = isset($metrics['averageCpm'])
                        ? (int) round($metrics['averageCpm'] / 10000)
                        : 0;

                    $conversionValue = isset($metrics['conversionsValue'])
                        ? (int) round($metrics['conversionsValue'] * 100)
                        : 0;

                    // Calculate CPA
                    $conversions = $metrics['conversions'] ?? 0;
                    $avgCpaInKopeks = $conversions > 0
                        ? (int) round($costInKopeks / $conversions)
                        : 0;

                    $campaigns[] = [
                        'campaign_id' => (string) $campaign['id'],
                        'campaign_name' => $campaign['name'] ?? 'Unknown Campaign',
                        'ad_group_id' => isset($adGroup['id']) ? (string) $adGroup['id'] : null,
                        'ad_group_name' => $adGroup['name'] ?? null,
                        'impressions' => (int) ($metrics['impressions'] ?? 0),
                        'clicks' => (int) ($metrics['clicks'] ?? 0),
                        'conversions' => (int) ($metrics['conversions'] ?? 0),
                        'cost' => $costInKopeks,
                        'avg_cpc' => $avgCpcInKopeks,
                        'avg_cpm' => $avgCpmInKopeks,
                        'avg_cpa' => $avgCpaInKopeks,
                        'quality_score' => (float) ($metrics['qualityScore'] ?? 0),
                        'conversion_value' => $conversionValue,
                        'video_views' => (int) ($metrics['videoViews'] ?? 0),
                        'video_quartile_25' => (float) ($metrics['videoQuartileP25Rate'] ?? 0),
                        'video_quartile_50' => (float) ($metrics['videoQuartileP50Rate'] ?? 0),
                        'video_quartile_75' => (float) ($metrics['videoQuartileP75Rate'] ?? 0),
                        'video_quartile_100' => (float) ($metrics['videoQuartileP100Rate'] ?? 0),
                    ];
                }
            }

            return $campaigns;

        } catch (\Exception $e) {
            Log::error('Failed to fetch Google Ads campaign metrics', [
                'error' => $e->getMessage(),
            ]);

            return [];
        }
    }

    /**
     * Get account hierarchy (customer accounts)
     */
    public function getCustomerAccounts(string $accessToken, string $managerId): array
    {
        try {
            $query = "
                SELECT
                    customer_client.id,
                    customer_client.descriptive_name,
                    customer_client.currency_code,
                    customer_client.status
                FROM customer_client
                WHERE customer_client.status = 'ENABLED'
            ";

            $response = Http::withHeaders([
                'Authorization' => 'Bearer '.$accessToken,
                'developer-token' => config('services.google_ads.developer_token'),
                'login-customer-id' => $managerId,
            ])->post(self::API_BASE_URL."/customers/{$managerId}/googleAds:search", [
                'query' => $query,
            ]);

            if (! $response->successful()) {
                return [];
            }

            $results = $response->json()['results'] ?? [];
            $accounts = [];

            foreach ($results as $row) {
                $customer = $row['customerClient'] ?? [];
                $accounts[] = [
                    'id' => (string) $customer['id'],
                    'name' => $customer['descriptiveName'] ?? 'Unknown',
                    'currency' => $customer['currencyCode'] ?? 'USD',
                    'status' => $customer['status'] ?? 'UNKNOWN',
                ];
            }

            return $accounts;

        } catch (\Exception $e) {
            Log::error('Failed to fetch Google Ads customer accounts', [
                'error' => $e->getMessage(),
            ]);

            return [];
        }
    }

    /**
     * Refresh OAuth2 access token
     */
    public function refreshAccessToken(string $refreshToken): ?string
    {
        try {
            $response = Http::asForm()->post('https://oauth2.googleapis.com/token', [
                'client_id' => config('services.google_ads.client_id'),
                'client_secret' => config('services.google_ads.client_secret'),
                'refresh_token' => $refreshToken,
                'grant_type' => 'refresh_token',
            ]);

            if (! $response->successful()) {
                Log::error('Failed to refresh Google Ads token', [
                    'response' => $response->body(),
                ]);

                return null;
            }

            return $response->json()['access_token'] ?? null;

        } catch (\Exception $e) {
            Log::error('Failed to refresh Google Ads access token', [
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Get campaign list
     */
    public function getCampaigns(string $accessToken, string $customerId): array
    {
        try {
            $query = "
                SELECT
                    campaign.id,
                    campaign.name,
                    campaign.status,
                    campaign.advertising_channel_type
                FROM campaign
                WHERE campaign.status IN ('ENABLED', 'PAUSED')
            ";

            $response = Http::withHeaders([
                'Authorization' => 'Bearer '.$accessToken,
                'developer-token' => config('services.google_ads.developer_token'),
                'login-customer-id' => $customerId,
            ])->post(self::API_BASE_URL."/customers/{$customerId}/googleAds:search", [
                'query' => $query,
            ]);

            if (! $response->successful()) {
                return [];
            }

            $results = $response->json()['results'] ?? [];
            $campaigns = [];

            foreach ($results as $row) {
                $campaign = $row['campaign'] ?? [];
                $campaigns[] = [
                    'id' => (string) $campaign['id'],
                    'name' => $campaign['name'] ?? 'Unknown',
                    'status' => $campaign['status'] ?? 'UNKNOWN',
                    'type' => $campaign['advertisingChannelType'] ?? 'UNKNOWN',
                ];
            }

            return $campaigns;

        } catch (\Exception $e) {
            Log::error('Failed to fetch Google Ads campaigns', [
                'error' => $e->getMessage(),
            ]);

            return [];
        }
    }

    /**
     * Validate Google Ads API credentials
     */
    public function validateCredentials(string $accessToken, string $customerId): bool
    {
        try {
            $query = 'SELECT customer.id FROM customer LIMIT 1';

            $response = Http::withHeaders([
                'Authorization' => 'Bearer '.$accessToken,
                'developer-token' => config('services.google_ads.developer_token'),
                'login-customer-id' => $customerId,
            ])->post(self::API_BASE_URL."/customers/{$customerId}/googleAds:search", [
                'query' => $query,
            ]);

            return $response->successful();

        } catch (\Exception $e) {
            Log::error('Failed to validate Google Ads credentials', [
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Sync metrics for date range
     *
     * @return int Total metrics synced
     */
    public function syncMetricsRange(MarketingChannel $channel, Carbon $startDate, Carbon $endDate): int
    {
        $totalSynced = 0;
        $currentDate = $startDate->copy();

        while ($currentDate->lte($endDate)) {
            $metrics = $this->syncMetrics($channel, $currentDate);
            $totalSynced += count($metrics);
            $currentDate->addDay();
        }

        return $totalSynced;
    }

    /**
     * Get conversion actions
     */
    public function getConversionActions(string $accessToken, string $customerId): array
    {
        try {
            $query = "
                SELECT
                    conversion_action.id,
                    conversion_action.name,
                    conversion_action.category,
                    conversion_action.status
                FROM conversion_action
                WHERE conversion_action.status = 'ENABLED'
            ";

            $response = Http::withHeaders([
                'Authorization' => 'Bearer '.$accessToken,
                'developer-token' => config('services.google_ads.developer_token'),
                'login-customer-id' => $customerId,
            ])->post(self::API_BASE_URL."/customers/{$customerId}/googleAds:search", [
                'query' => $query,
            ]);

            if (! $response->successful()) {
                return [];
            }

            $results = $response->json()['results'] ?? [];
            $conversions = [];

            foreach ($results as $row) {
                $conversion = $row['conversionAction'] ?? [];
                $conversions[] = [
                    'id' => (string) $conversion['id'],
                    'name' => $conversion['name'] ?? 'Unknown',
                    'category' => $conversion['category'] ?? 'UNKNOWN',
                ];
            }

            return $conversions;

        } catch (\Exception $e) {
            Log::error('Failed to fetch Google Ads conversion actions', [
                'error' => $e->getMessage(),
            ]);

            return [];
        }
    }
}
