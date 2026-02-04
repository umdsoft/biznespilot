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
    private string $apiBaseUrl;

    public function __construct()
    {
        $this->apiBaseUrl = 'https://graph.facebook.com/' . config('services.meta.api_version', 'v21.0');
    }

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
            $response = Http::get($this->apiBaseUrl."/{$pageId}/insights", [
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
            $pageResponse = Http::get($this->apiBaseUrl."/{$pageId}", [
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
            $postsResponse = Http::get($this->apiBaseUrl."/{$pageId}/posts", [
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
                $postInsightsResponse = Http::get($this->apiBaseUrl."/{$postId}/insights", [
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
                $reactionsResponse = Http::get($this->apiBaseUrl."/{$postId}", [
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
            $videosResponse = Http::get($this->apiBaseUrl."/{$pageId}/videos", [
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
                $videoInsightsResponse = Http::get($this->apiBaseUrl."/{$videoId}/video_insights", [
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

            $response = Http::get($this->apiBaseUrl."/{$pageId}/insights", [
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
            $response = Http::get($this->apiBaseUrl.'/debug_token', [
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
    public function exchangeForLongLivedToken(string $shortLivedToken): ?string
    {
        try {
            $response = Http::get($this->apiBaseUrl.'/oauth/access_token', [
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

    // ==========================================
    // QATIY BITTA AKKAUNT TIZIMI METODLARI
    // ==========================================

    /**
     * Get all available accounts for selection UI
     * Tanlash UI uchun barcha mavjud akkauntlarni olish
     *
     * @param string $accessToken User Access Token
     * @return array{ad_accounts: array, instagram_accounts: array, facebook_pages: array}
     */
    public function getAvailableAccounts(string $accessToken): array
    {
        $adAccounts = [];
        $instagramAccounts = [];
        $facebookPages = [];

        try {
            // 1. Get Ad Accounts with pagination
            $adAccounts = $this->fetchAllAdAccounts($accessToken);

            // 2. Get Facebook Pages (which may have linked Instagram accounts)
            $pagesData = $this->fetchAllPages($accessToken);
            $facebookPages = $pagesData['pages'];

            // 3. Get Instagram Business Accounts from Pages
            foreach ($pagesData['pages'] as $page) {
                if (!empty($page['instagram_business_account'])) {
                    $igAccount = $this->getInstagramAccountDetails(
                        $accessToken,
                        $page['instagram_business_account']['id']
                    );

                    if ($igAccount) {
                        $igAccount['linked_page_id'] = $page['id'];
                        $igAccount['linked_page_name'] = $page['name'];
                        $instagramAccounts[] = $igAccount;
                    }
                }
            }

            Log::info('Available accounts fetched', [
                'ad_accounts_count' => count($adAccounts),
                'instagram_accounts_count' => count($instagramAccounts),
                'facebook_pages_count' => count($facebookPages),
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to fetch available accounts', [
                'error' => $e->getMessage(),
            ]);
        }

        return [
            'ad_accounts' => $adAccounts,
            'instagram_accounts' => $instagramAccounts,
            'facebook_pages' => $facebookPages,
        ];
    }

    /**
     * Fetch all Ad Accounts with pagination
     * Pagination bilan barcha Ad Accountlarni olish
     */
    protected function fetchAllAdAccounts(string $accessToken): array
    {
        $accounts = [];
        $url = $this->apiBaseUrl . '/me/adaccounts';
        $params = [
            'fields' => 'id,name,account_id,currency,timezone_name,account_status,amount_spent,balance,business_name',
            'access_token' => $accessToken,
            'limit' => 100,
        ];

        try {
            do {
                $response = Http::get($url, $params);

                if (!$response->successful()) {
                    Log::error('Failed to fetch ad accounts', [
                        'status' => $response->status(),
                        'body' => $response->body(),
                    ]);
                    break;
                }

                $data = $response->json();
                $pageAccounts = $data['data'] ?? [];

                foreach ($pageAccounts as $account) {
                    // Clean account ID (remove 'act_' prefix if present)
                    $accountId = str_replace('act_', '', $account['id']);

                    $accounts[] = [
                        'id' => $accountId,
                        'full_id' => $account['id'],
                        'name' => $account['name'] ?? 'Ad Account ' . $accountId,
                        'currency' => $account['currency'] ?? 'USD',
                        'timezone_name' => $account['timezone_name'] ?? 'Asia/Tashkent',
                        'account_status' => $account['account_status'] ?? 0,
                        'business_name' => $account['business_name'] ?? null,
                        'amount_spent' => ($account['amount_spent'] ?? 0) / 100, // Convert from cents
                    ];
                }

                // Check for next page (pagination)
                $url = $data['paging']['next'] ?? null;
                $params = []; // Next URL includes all params

            } while ($url);

        } catch (\Exception $e) {
            Log::error('Exception fetching ad accounts', [
                'error' => $e->getMessage(),
            ]);
        }

        return $accounts;
    }

    /**
     * Fetch all Facebook Pages with pagination
     * Pagination bilan barcha sahifalarni olish
     */
    protected function fetchAllPages(string $accessToken): array
    {
        $pages = [];
        $url = $this->apiBaseUrl . '/me/accounts';
        $params = [
            'fields' => 'id,name,username,category,fan_count,access_token,instagram_business_account{id,username}',
            'access_token' => $accessToken,
            'limit' => 100,
        ];

        try {
            do {
                $response = Http::get($url, $params);

                if (!$response->successful()) {
                    Log::error('Failed to fetch pages', [
                        'status' => $response->status(),
                        'body' => $response->body(),
                    ]);
                    break;
                }

                $data = $response->json();
                $pageData = $data['data'] ?? [];

                foreach ($pageData as $page) {
                    $pages[] = [
                        'id' => $page['id'],
                        'name' => $page['name'],
                        'username' => $page['username'] ?? null,
                        'category' => $page['category'] ?? null,
                        'fan_count' => $page['fan_count'] ?? 0,
                        'access_token' => $page['access_token'] ?? null,
                        'instagram_business_account' => $page['instagram_business_account'] ?? null,
                    ];
                }

                // Check for next page (pagination)
                $url = $data['paging']['next'] ?? null;
                $params = [];

            } while ($url);

        } catch (\Exception $e) {
            Log::error('Exception fetching pages', [
                'error' => $e->getMessage(),
            ]);
        }

        return ['pages' => $pages];
    }

    /**
     * Get detailed Ad Account info
     */
    public function getAdAccountDetails(string $accessToken, string $accountId): ?array
    {
        try {
            // Add 'act_' prefix if not present
            $fullAccountId = str_starts_with($accountId, 'act_') ? $accountId : 'act_' . $accountId;

            $response = Http::get($this->apiBaseUrl . '/' . $fullAccountId, [
                'fields' => 'id,name,account_id,currency,timezone_name,account_status,business_name,funding_source_details',
                'access_token' => $accessToken,
            ]);

            if (!$response->successful()) {
                Log::error('Failed to get ad account details', [
                    'account_id' => $accountId,
                    'status' => $response->status(),
                ]);
                return null;
            }

            $data = $response->json();

            return [
                'id' => str_replace('act_', '', $data['id']),
                'full_id' => $data['id'],
                'name' => $data['name'] ?? 'Ad Account',
                'currency' => $data['currency'] ?? 'USD',
                'timezone_name' => $data['timezone_name'] ?? 'Asia/Tashkent',
                'account_status' => $data['account_status'] ?? 0,
                'business_name' => $data['business_name'] ?? null,
            ];

        } catch (\Exception $e) {
            Log::error('Exception getting ad account details', [
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Get detailed Instagram Account info
     */
    public function getInstagramAccountDetails(string $accessToken, string $igUserId): ?array
    {
        try {
            $response = Http::get($this->apiBaseUrl . '/' . $igUserId, [
                'fields' => 'id,username,name,profile_picture_url,followers_count,follows_count,media_count,biography,website',
                'access_token' => $accessToken,
            ]);

            if (!$response->successful()) {
                Log::error('Failed to get Instagram account details', [
                    'ig_user_id' => $igUserId,
                    'status' => $response->status(),
                ]);
                return null;
            }

            return $response->json();

        } catch (\Exception $e) {
            Log::error('Exception getting Instagram account details', [
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Get detailed Facebook Page info
     */
    public function getPageDetails(string $accessToken, string $pageId): ?array
    {
        try {
            $response = Http::get($this->apiBaseUrl . '/' . $pageId, [
                'fields' => 'id,name,username,category,fan_count,access_token,about,cover,picture',
                'access_token' => $accessToken,
            ]);

            if (!$response->successful()) {
                Log::error('Failed to get page details', [
                    'page_id' => $pageId,
                    'status' => $response->status(),
                ]);
                return null;
            }

            return $response->json();

        } catch (\Exception $e) {
            Log::error('Exception getting page details', [
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Sync historical data for an integration
     * 6 oylik tarixiy ma'lumotlarni sinxronlash
     *
     * @param string $integrationId Integration UUID
     * @param int $months Number of months to sync
     * @return array{success: bool, synced: array, errors: array}
     */
    public function syncHistoricalData(string $integrationId, int $months = 6): array
    {
        $integration = \App\Models\Integration::find($integrationId);

        if (!$integration) {
            return ['success' => false, 'errors' => ['Integration topilmadi']];
        }

        $accessToken = $integration->getAccessToken();

        if (!$accessToken) {
            return ['success' => false, 'errors' => ['Access token topilmadi']];
        }

        $since = now()->subMonths($months);
        $until = now();
        $synced = [];
        $errors = [];

        Log::info('Starting historical data sync', [
            'integration_id' => $integrationId,
            'months' => $months,
            'since' => $since->toDateString(),
            'until' => $until->toDateString(),
        ]);

        // Sync Ad Account Insights
        try {
            $adAccount = \App\Models\MetaAdAccount::where('integration_id', $integrationId)
                ->where('is_primary', true)
                ->first();

            if ($adAccount) {
                $insightsCount = $this->syncAdInsightsHistorical(
                    $accessToken,
                    $adAccount,
                    $since,
                    $until
                );
                $synced['ad_insights'] = $insightsCount;
            }
        } catch (\Exception $e) {
            $errors[] = 'Ad Insights: ' . $e->getMessage();
            Log::error('Failed to sync ad insights', ['error' => $e->getMessage()]);
        }

        // Sync Instagram Media
        try {
            $instagramAccount = \App\Models\InstagramAccount::where('integration_id', $integrationId)
                ->where('is_primary', true)
                ->first();

            if ($instagramAccount) {
                $mediaCount = $this->syncInstagramMediaHistorical(
                    $accessToken,
                    $instagramAccount,
                    $since,
                    $until
                );
                $synced['instagram_media'] = $mediaCount;
            }
        } catch (\Exception $e) {
            $errors[] = 'Instagram Media: ' . $e->getMessage();
            Log::error('Failed to sync Instagram media', ['error' => $e->getMessage()]);
        }

        // Update integration last sync time
        $integration->update([
            'last_sync_at' => now(),
            'sync_count' => ($integration->sync_count ?? 0) + 1,
        ]);

        Log::info('Historical data sync completed', [
            'integration_id' => $integrationId,
            'synced' => $synced,
            'errors' => $errors,
        ]);

        return [
            'success' => empty($errors),
            'synced' => $synced,
            'errors' => $errors,
        ];
    }

    /**
     * Sync Ad Account insights with pagination
     * Pagination bilan Ad Insights larni sinxronlash
     */
    protected function syncAdInsightsHistorical(
        string $accessToken,
        \App\Models\MetaAdAccount $adAccount,
        Carbon $since,
        Carbon $until
    ): int {
        $syncedCount = 0;
        $accountId = 'act_' . $adAccount->meta_account_id;

        // Fetch daily insights
        $url = $this->apiBaseUrl . '/' . $accountId . '/insights';
        $params = [
            'fields' => 'impressions,reach,clicks,spend,cpc,cpm,ctr,actions,conversions,cost_per_conversion',
            'time_range' => json_encode([
                'since' => $since->format('Y-m-d'),
                'until' => $until->format('Y-m-d'),
            ]),
            'time_increment' => 1, // Daily breakdown
            'level' => 'account',
            'access_token' => $accessToken,
            'limit' => 500,
        ];

        try {
            do {
                $response = Http::get($url, $params);

                if (!$response->successful()) {
                    Log::error('Failed to fetch ad insights', [
                        'account_id' => $accountId,
                        'status' => $response->status(),
                        'body' => $response->body(),
                    ]);
                    break;
                }

                $data = $response->json();
                $insights = $data['data'] ?? [];

                foreach ($insights as $insight) {
                    // Save to meta_insights table
                    \App\Models\MetaInsight::updateOrCreate(
                        [
                            'ad_account_id' => $adAccount->id,
                            'date_start' => $insight['date_start'],
                            'date_stop' => $insight['date_stop'],
                        ],
                        [
                            'impressions' => $insight['impressions'] ?? 0,
                            'reach' => $insight['reach'] ?? 0,
                            'clicks' => $insight['clicks'] ?? 0,
                            'spend' => ($insight['spend'] ?? 0),
                            'cpc' => $insight['cpc'] ?? 0,
                            'cpm' => $insight['cpm'] ?? 0,
                            'ctr' => $insight['ctr'] ?? 0,
                            'actions' => $insight['actions'] ?? null,
                            'conversions' => $this->extractConversions($insight['actions'] ?? []),
                        ]
                    );
                    $syncedCount++;
                }

                // Pagination
                $url = $data['paging']['next'] ?? null;
                $params = [];

            } while ($url);

        } catch (\Exception $e) {
            Log::error('Exception syncing ad insights', [
                'error' => $e->getMessage(),
            ]);
        }

        // Update ad account last sync
        $adAccount->update(['last_sync_at' => now()]);

        return $syncedCount;
    }

    /**
     * Extract conversions count from actions array
     */
    protected function extractConversions(array $actions): int
    {
        $conversionActions = ['purchase', 'lead', 'complete_registration', 'add_to_cart'];
        $total = 0;

        foreach ($actions as $action) {
            if (in_array($action['action_type'] ?? '', $conversionActions)) {
                $total += (int) ($action['value'] ?? 0);
            }
        }

        return $total;
    }

    /**
     * Sync Instagram media with pagination
     * Pagination bilan Instagram postlarni sinxronlash
     */
    protected function syncInstagramMediaHistorical(
        string $accessToken,
        \App\Models\InstagramAccount $account,
        Carbon $since,
        Carbon $until
    ): int {
        $syncedCount = 0;

        $url = $this->apiBaseUrl . '/' . $account->instagram_id . '/media';
        $params = [
            'fields' => 'id,caption,media_type,media_url,thumbnail_url,permalink,timestamp,like_count,comments_count,insights.metric(impressions,reach,saved,shares,video_views,plays)',
            'access_token' => $accessToken,
            'limit' => 50,
        ];

        try {
            do {
                $response = Http::get($url, $params);

                if (!$response->successful()) {
                    Log::error('Failed to fetch Instagram media', [
                        'ig_id' => $account->instagram_id,
                        'status' => $response->status(),
                    ]);
                    break;
                }

                $data = $response->json();
                $mediaItems = $data['data'] ?? [];

                foreach ($mediaItems as $media) {
                    $postedAt = Carbon::parse($media['timestamp']);

                    // Skip if outside date range
                    if ($postedAt->lt($since)) {
                        // Media is ordered by date desc, so we can stop here
                        $url = null;
                        break;
                    }

                    if ($postedAt->gt($until)) {
                        continue;
                    }

                    // Extract insights
                    $insights = $this->extractMediaInsights($media['insights']['data'] ?? []);

                    // Determine media product type
                    $mediaProductType = match ($media['media_type'] ?? 'IMAGE') {
                        'VIDEO' => isset($media['thumbnail_url']) ? 'REELS' : 'FEED',
                        'CAROUSEL_ALBUM' => 'FEED',
                        default => 'FEED',
                    };

                    // Save to instagram_media table
                    \App\Models\InstagramMedia::updateOrCreate(
                        [
                            'account_id' => $account->id,
                            'media_id' => $media['id'],
                        ],
                        [
                            'media_type' => $media['media_type'] ?? 'IMAGE',
                            'media_product_type' => $mediaProductType,
                            'caption' => $media['caption'] ?? null,
                            'permalink' => $media['permalink'] ?? null,
                            'media_url' => $media['media_url'] ?? null,
                            'thumbnail_url' => $media['thumbnail_url'] ?? null,
                            'like_count' => $media['like_count'] ?? 0,
                            'comments_count' => $media['comments_count'] ?? 0,
                            'reach' => $insights['reach'] ?? 0,
                            'impressions' => $insights['impressions'] ?? 0,
                            'saved' => $insights['saved'] ?? 0,
                            'shares' => $insights['shares'] ?? 0,
                            'plays' => $insights['plays'] ?? $insights['video_views'] ?? 0,
                            'posted_at' => $postedAt,
                        ]
                    );
                    $syncedCount++;
                }

                // Pagination
                $url = $data['paging']['next'] ?? null;
                $params = [];

            } while ($url);

        } catch (\Exception $e) {
            Log::error('Exception syncing Instagram media', [
                'error' => $e->getMessage(),
            ]);
        }

        // Update account last sync
        $account->update(['last_synced_at' => now()]);

        return $syncedCount;
    }

    /**
     * Extract insights from media insights array
     */
    protected function extractMediaInsights(array $insightsData): array
    {
        $result = [];

        foreach ($insightsData as $insight) {
            $name = $insight['name'] ?? '';
            $value = $insight['values'][0]['value'] ?? 0;
            $result[$name] = $value;
        }

        return $result;
    }
}
