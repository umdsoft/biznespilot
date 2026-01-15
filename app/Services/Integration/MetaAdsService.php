<?php

namespace App\Services\Integration;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class MetaAdsService
{
    protected string $baseUrl = 'https://graph.facebook.com/v18.0';
    protected ?string $accessToken = null;

    public function setAccessToken(string $token): self
    {
        $this->accessToken = $token;
        return $this;
    }

    /**
     * Foydalanuvchi ad accounts larini olish
     */
    public function getAdAccounts(): array
    {
        return $this->get('/me/adaccounts', [
            'fields' => 'id,name,account_status,currency,timezone_name,amount_spent,balance,business_name',
            'limit' => 100,
        ]);
    }

    /**
     * Campaigns olish
     */
    public function getCampaigns(string $adAccountId): array
    {
        $accountId = $this->formatAccountId($adAccountId);
        return $this->get("/{$accountId}/campaigns", [
            'fields' => 'id,name,objective,status,effective_status,daily_budget,lifetime_budget,budget_remaining,start_time,stop_time,created_time,updated_time',
            'limit' => 500,
        ]);
    }

    /**
     * Ad Sets olish
     */
    public function getAdSets(string $adAccountId, ?string $campaignId = null): array
    {
        $accountId = $this->formatAccountId($adAccountId);
        $endpoint = $campaignId
            ? "/{$campaignId}/adsets"
            : "/{$accountId}/adsets";

        return $this->get($endpoint, [
            'fields' => 'id,name,campaign_id,status,effective_status,optimization_goal,billing_event,daily_budget,lifetime_budget,bid_amount,targeting,start_time,end_time',
            'limit' => 500,
        ]);
    }

    /**
     * Ads olish
     */
    public function getAds(string $adAccountId, ?string $adSetId = null): array
    {
        $accountId = $this->formatAccountId($adAccountId);
        $endpoint = $adSetId
            ? "/{$adSetId}/ads"
            : "/{$accountId}/ads";

        return $this->get($endpoint, [
            'fields' => 'id,name,adset_id,status,effective_status,creative{id,name,thumbnail_url,body,title,link_url,call_to_action_type}',
            'limit' => 500,
        ]);
    }

    /**
     * Account level insights
     */
    public function getAccountInsights(
        string $adAccountId,
        string $datePreset = 'last_30d',
        ?array $timeRange = null
    ): array {
        $accountId = $this->formatAccountId($adAccountId);
        return $this->getInsights($accountId, 'account', $datePreset, $timeRange);
    }

    /**
     * Campaign level insights
     */
    public function getCampaignInsights(
        string $adAccountId,
        string $datePreset = 'last_30d',
        ?array $timeRange = null
    ): array {
        $accountId = $this->formatAccountId($adAccountId);
        return $this->getInsights($accountId, 'campaign', $datePreset, $timeRange);
    }

    /**
     * Insights olish (universal)
     */
    public function getInsights(
        string $objectId,
        string $level = 'account',
        string $datePreset = 'last_30d',
        ?array $timeRange = null,
        array $breakdowns = [],
        int $timeIncrement = 0
    ): array {
        $fields = [
            'campaign_id', 'campaign_name',
            'adset_id', 'adset_name',
            'ad_id', 'ad_name',
            'impressions', 'reach', 'frequency',
            'clicks', 'unique_clicks',
            'cpc', 'cpm', 'cpp', 'ctr', 'unique_ctr',
            'spend',
            'actions', 'action_values', 'cost_per_action_type',
        ];

        $params = [
            'fields' => implode(',', $fields),
            'level' => $level,
        ];

        if ($timeRange) {
            $params['time_range'] = json_encode($timeRange);
        } else {
            $params['date_preset'] = $datePreset;
        }

        if (!empty($breakdowns)) {
            $params['breakdowns'] = implode(',', $breakdowns);
        }

        if ($timeIncrement > 0) {
            $params['time_increment'] = $timeIncrement;
        }

        return $this->get("/{$objectId}/insights", $params);
    }

    /**
     * Demographics insights (age, gender)
     */
    public function getDemographicsInsights(string $adAccountId, string $datePreset = 'last_30d'): array
    {
        $accountId = $this->formatAccountId($adAccountId);
        return $this->getInsights(
            $accountId,
            'account',
            $datePreset,
            null,
            ['age', 'gender']
        );
    }

    /**
     * Platform/Placement insights
     */
    public function getPlacementInsights(string $adAccountId, string $datePreset = 'last_30d'): array
    {
        $accountId = $this->formatAccountId($adAccountId);
        return $this->getInsights(
            $accountId,
            'account',
            $datePreset,
            null,
            ['publisher_platform', 'platform_position']
        );
    }

    /**
     * Location insights
     */
    public function getLocationInsights(string $adAccountId, string $datePreset = 'last_30d'): array
    {
        $accountId = $this->formatAccountId($adAccountId);
        return $this->getInsights(
            $accountId,
            'account',
            $datePreset,
            null,
            ['country', 'region']
        );
    }

    /**
     * Device insights
     */
    public function getDeviceInsights(string $adAccountId, string $datePreset = 'last_30d'): array
    {
        $accountId = $this->formatAccountId($adAccountId);
        return $this->getInsights(
            $accountId,
            'account',
            $datePreset,
            null,
            ['device_platform']
        );
    }

    /**
     * Daily trend insights
     */
    public function getDailyInsights(
        string $adAccountId,
        string $startDate,
        string $endDate,
        string $level = 'account'
    ): array {
        $accountId = $this->formatAccountId($adAccountId);
        return $this->getInsights(
            $accountId,
            $level,
            'last_30d',
            ['since' => $startDate, 'until' => $endDate],
            [],
            1 // Daily
        );
    }

    /**
     * Format account ID (ensure act_ prefix)
     */
    protected function formatAccountId(string $accountId): string
    {
        if (str_starts_with($accountId, 'act_')) {
            return $accountId;
        }
        return "act_{$accountId}";
    }

    /**
     * HTTP GET request
     */
    protected function get(string $endpoint, array $params = []): array
    {
        $params['access_token'] = $this->accessToken;

        try {
            $response = Http::timeout(30)->get("{$this->baseUrl}{$endpoint}", $params);

            if ($response->failed()) {
                $error = $response->json('error', []);
                Log::error('Meta API error', [
                    'endpoint' => $endpoint,
                    'error' => $error,
                ]);
                throw new \Exception(
                    $error['message'] ?? 'Meta API error',
                    $error['code'] ?? 0
                );
            }

            return $response->json();
        } catch (\Exception $e) {
            Log::error('Meta API request failed', [
                'endpoint' => $endpoint,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * HTTP POST request
     */
    protected function post(string $endpoint, array $params = []): array
    {
        $params['access_token'] = $this->accessToken;

        try {
            $response = Http::timeout(30)->post("{$this->baseUrl}{$endpoint}", $params);

            if ($response->failed()) {
                $error = $response->json('error', []);
                Log::error('Meta API POST error', [
                    'endpoint' => $endpoint,
                    'error' => $error,
                ]);
                throw new \Exception(
                    $error['message'] ?? 'Meta API error',
                    $error['code'] ?? 0
                );
            }

            return $response->json();
        } catch (\Exception $e) {
            Log::error('Meta API POST request failed', [
                'endpoint' => $endpoint,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    // ==========================================
    // CAMPAIGN MANAGEMENT METHODS
    // ==========================================

    /**
     * Update campaign status (ACTIVE, PAUSED, DELETED)
     */
    public function updateCampaignStatus(string $campaignId, string $status): array
    {
        $validStatuses = ['ACTIVE', 'PAUSED', 'DELETED'];
        if (!in_array($status, $validStatuses)) {
            throw new \Exception("Invalid status: {$status}. Valid statuses: " . implode(', ', $validStatuses));
        }

        return $this->post("/{$campaignId}", [
            'status' => $status,
        ]);
    }

    /**
     * Update campaign budget
     * @param string $campaignId
     * @param float $budget Amount in currency units (not cents)
     * @param string $budgetType 'daily' or 'lifetime'
     */
    public function updateCampaignBudget(string $campaignId, float $budget, string $budgetType = 'daily'): array
    {
        // Meta API expects budget in cents
        $budgetInCents = (int) ($budget * 100);

        $params = $budgetType === 'lifetime'
            ? ['lifetime_budget' => $budgetInCents]
            : ['daily_budget' => $budgetInCents];

        return $this->post("/{$campaignId}", $params);
    }

    /**
     * Update campaign name
     */
    public function updateCampaignName(string $campaignId, string $name): array
    {
        return $this->post("/{$campaignId}", [
            'name' => $name,
        ]);
    }

    /**
     * Update AdSet status
     */
    public function updateAdSetStatus(string $adSetId, string $status): array
    {
        $validStatuses = ['ACTIVE', 'PAUSED', 'DELETED'];
        if (!in_array($status, $validStatuses)) {
            throw new \Exception("Invalid status: {$status}");
        }

        return $this->post("/{$adSetId}", [
            'status' => $status,
        ]);
    }

    /**
     * Update AdSet budget
     */
    public function updateAdSetBudget(string $adSetId, float $budget, string $budgetType = 'daily'): array
    {
        $budgetInCents = (int) ($budget * 100);

        $params = $budgetType === 'lifetime'
            ? ['lifetime_budget' => $budgetInCents]
            : ['daily_budget' => $budgetInCents];

        return $this->post("/{$adSetId}", $params);
    }

    /**
     * Update Ad status
     */
    public function updateAdStatus(string $adId, string $status): array
    {
        $validStatuses = ['ACTIVE', 'PAUSED', 'DELETED'];
        if (!in_array($status, $validStatuses)) {
            throw new \Exception("Invalid status: {$status}");
        }

        return $this->post("/{$adId}", [
            'status' => $status,
        ]);
    }

    /**
     * Get single campaign details
     */
    public function getCampaign(string $campaignId): array
    {
        return $this->get("/{$campaignId}", [
            'fields' => 'id,name,objective,status,effective_status,daily_budget,lifetime_budget,budget_remaining,start_time,stop_time,created_time,updated_time',
        ]);
    }

    /**
     * Get single AdSet details
     */
    public function getAdSet(string $adSetId): array
    {
        return $this->get("/{$adSetId}", [
            'fields' => 'id,name,campaign_id,status,effective_status,optimization_goal,billing_event,daily_budget,lifetime_budget,bid_amount,targeting,start_time,end_time',
        ]);
    }

    /**
     * Get single Ad details
     */
    public function getAd(string $adId): array
    {
        return $this->get("/{$adId}", [
            'fields' => 'id,name,adset_id,campaign_id,status,effective_status,creative{id,name,thumbnail_url,body,title,link_url,call_to_action_type}',
        ]);
    }

    /**
     * Batch update multiple campaigns
     */
    public function batchUpdateCampaignStatus(array $campaignIds, string $status): array
    {
        $results = [];
        foreach ($campaignIds as $campaignId) {
            try {
                $results[$campaignId] = $this->updateCampaignStatus($campaignId, $status);
            } catch (\Exception $e) {
                $results[$campaignId] = ['error' => $e->getMessage()];
            }
        }
        return $results;
    }

    // ==========================================
    // CAMPAIGN CREATION METHODS
    // ==========================================

    /**
     * Get available campaign objectives
     */
    public function getAvailableObjectives(): array
    {
        return [
            [
                'value' => 'OUTCOME_AWARENESS',
                'label' => 'Xabardorlik',
                'description' => 'Brendingizni ko\'proq odamlarga ko\'rsatish',
                'icon' => 'eye',
            ],
            [
                'value' => 'OUTCOME_TRAFFIC',
                'label' => 'Trafik',
                'description' => 'Veb-saytga yoki ilovaga trafik yuborish',
                'icon' => 'cursor-click',
            ],
            [
                'value' => 'OUTCOME_ENGAGEMENT',
                'label' => 'Engagement',
                'description' => 'Post engagement, sahifa like va video ko\'rishlarni oshirish',
                'icon' => 'heart',
            ],
            [
                'value' => 'OUTCOME_LEADS',
                'label' => 'Lidlar',
                'description' => 'Potentsial mijozlar ma\'lumotlarini to\'plash',
                'icon' => 'user-plus',
            ],
            [
                'value' => 'OUTCOME_SALES',
                'label' => 'Sotuvlar',
                'description' => 'Onlayn sotuvlarni oshirish',
                'icon' => 'shopping-cart',
            ],
            [
                'value' => 'OUTCOME_APP_PROMOTION',
                'label' => 'Ilova targ\'iboti',
                'description' => 'Ilovani yuklab olishlarni oshirish',
                'icon' => 'device-mobile',
            ],
        ];
    }

    /**
     * Create a new campaign
     */
    public function createCampaign(string $adAccountId, array $data): array
    {
        $accountId = $this->formatAccountId($adAccountId);

        $params = [
            'name' => $data['name'],
            'objective' => $data['objective'],
            'status' => $data['status'] ?? 'PAUSED',
            // Meta API requires special_ad_categories as JSON array
            'special_ad_categories' => json_encode($data['special_ad_categories'] ?? []),
        ];

        // Budget at campaign level (CBO - Campaign Budget Optimization)
        if (!empty($data['daily_budget'])) {
            $params['daily_budget'] = (int) ($data['daily_budget'] * 100);
        } elseif (!empty($data['lifetime_budget'])) {
            $params['lifetime_budget'] = (int) ($data['lifetime_budget'] * 100);
        }

        // Bid strategy
        if (!empty($data['bid_strategy'])) {
            $params['bid_strategy'] = $data['bid_strategy'];
        }

        return $this->post("/{$accountId}/campaigns", $params);
    }

    /**
     * Create a new AdSet
     */
    public function createAdSet(string $adAccountId, string $campaignId, array $data): array
    {
        $accountId = $this->formatAccountId($adAccountId);

        $params = [
            'name' => $data['name'],
            'campaign_id' => $campaignId,
            'status' => $data['status'] ?? 'PAUSED',
            'billing_event' => $data['billing_event'] ?? 'IMPRESSIONS',
            'optimization_goal' => $data['optimization_goal'] ?? 'REACH',
        ];

        // Budget (if not using CBO)
        if (!empty($data['daily_budget'])) {
            $params['daily_budget'] = (int) ($data['daily_budget'] * 100);
        } elseif (!empty($data['lifetime_budget'])) {
            $params['lifetime_budget'] = (int) ($data['lifetime_budget'] * 100);
        }

        // Bid amount
        if (!empty($data['bid_amount'])) {
            $params['bid_amount'] = (int) ($data['bid_amount'] * 100);
        }

        // Schedule
        if (!empty($data['start_time'])) {
            $params['start_time'] = $data['start_time'];
        }
        if (!empty($data['end_time'])) {
            $params['end_time'] = $data['end_time'];
        }

        // Targeting - Meta API requires JSON encoded targeting spec
        if (!empty($data['targeting'])) {
            $params['targeting'] = is_string($data['targeting'])
                ? $data['targeting']
                : json_encode($data['targeting']);
        } else {
            // Default targeting
            $params['targeting'] = json_encode([
                'geo_locations' => [
                    'countries' => ['UZ'],
                ],
            ]);
        }

        return $this->post("/{$accountId}/adsets", $params);
    }

    /**
     * Create a new Ad
     */
    public function createAd(string $adAccountId, string $adSetId, array $data): array
    {
        $accountId = $this->formatAccountId($adAccountId);

        $params = [
            'name' => $data['name'],
            'adset_id' => $adSetId,
            'status' => $data['status'] ?? 'PAUSED',
            'creative' => $data['creative'],
        ];

        return $this->post("/{$accountId}/ads", $params);
    }

    /**
     * Create ad creative
     */
    public function createAdCreative(string $adAccountId, array $data): array
    {
        $accountId = $this->formatAccountId($adAccountId);

        $params = [
            'name' => $data['name'] ?? 'Creative ' . time(),
        ];

        // Link ad creative
        if (!empty($data['link_data'])) {
            $params['object_story_spec'] = [
                'page_id' => $data['page_id'],
                'link_data' => $data['link_data'],
            ];
        }

        // Image ad creative
        if (!empty($data['image_hash'])) {
            $params['object_story_spec'] = [
                'page_id' => $data['page_id'],
                'link_data' => [
                    'image_hash' => $data['image_hash'],
                    'link' => $data['link'] ?? '',
                    'message' => $data['message'] ?? '',
                    'call_to_action' => [
                        'type' => $data['call_to_action'] ?? 'LEARN_MORE',
                    ],
                ],
            ];
        }

        return $this->post("/{$accountId}/adcreatives", $params);
    }

    /**
     * Upload image to ad account
     */
    public function uploadImage(string $adAccountId, string $imagePath): array
    {
        $accountId = $this->formatAccountId($adAccountId);

        $params = [
            'access_token' => $this->accessToken,
        ];

        // Read image and convert to base64
        $imageData = base64_encode(file_get_contents($imagePath));
        $params['bytes'] = $imageData;

        $response = Http::timeout(60)->post("{$this->baseUrl}/{$accountId}/adimages", $params);

        if ($response->failed()) {
            $error = $response->json('error', []);
            throw new \Exception($error['message'] ?? 'Image upload failed');
        }

        return $response->json();
    }

    /**
     * Get Facebook Pages for the user
     */
    public function getPages(): array
    {
        return $this->get('/me/accounts', [
            'fields' => 'id,name,access_token,category,picture',
        ]);
    }

    /**
     * Get Instagram accounts linked to pages
     */
    public function getInstagramAccounts(string $pageId): array
    {
        return $this->get("/{$pageId}/instagram_accounts", [
            'fields' => 'id,username,profile_pic',
        ]);
    }

    /**
     * Get lead forms for a Facebook page
     */
    public function getPageLeadForms(string $pageId, string $pageAccessToken): array
    {
        try {
            $response = $this->client->get(
                "/{$pageId}/leadgen_forms",
                ['access_token' => $pageAccessToken, 'fields' => 'id,name,status,leads_count']
            );
            return $response->getDecodedBody()['data'] ?? [];
        } catch (\Exception $e) {
            \Log::warning('Error fetching lead forms', ['page_id' => $pageId, 'error' => $e->getMessage()]);
            return [];
        }
    }

    /**
     * Get targeting options - interests
     */
    public function searchInterests(string $query): array
    {
        return $this->get('/search', [
            'type' => 'adinterest',
            'q' => $query,
            'limit' => 20,
        ]);
    }

    /**
     * Get targeting options - locations
     */
    public function searchLocations(string $query, string $type = 'adgeolocation'): array
    {
        return $this->get('/search', [
            'type' => $type,
            'q' => $query,
            'limit' => 20,
        ]);
    }

    /**
     * Get reach estimate for targeting
     */
    public function getReachEstimate(string $adAccountId, array $targeting): array
    {
        $accountId = $this->formatAccountId($adAccountId);

        return $this->get("/{$accountId}/reachestimate", [
            'targeting_spec' => json_encode($targeting),
            'optimize_for' => 'REACH',
        ]);
    }

    /**
     * Get optimization goals for an objective
     */
    public function getOptimizationGoals(string $objective): array
    {
        $goals = [
            'OUTCOME_AWARENESS' => [
                ['value' => 'REACH', 'label' => 'Qamrov'],
                ['value' => 'AD_RECALL_LIFT', 'label' => 'Brand eslab qolish'],
                ['value' => 'IMPRESSIONS', 'label' => 'Ko\'rishlar'],
            ],
            'OUTCOME_TRAFFIC' => [
                ['value' => 'LINK_CLICKS', 'label' => 'Link kliklar'],
                ['value' => 'LANDING_PAGE_VIEWS', 'label' => 'Landing sahifa ko\'rishlar'],
            ],
            'OUTCOME_ENGAGEMENT' => [
                ['value' => 'POST_ENGAGEMENT', 'label' => 'Post engagement'],
                ['value' => 'PAGE_LIKES', 'label' => 'Sahifa layklari'],
                ['value' => 'THRUPLAY', 'label' => 'Video ko\'rishlar'],
            ],
            'OUTCOME_LEADS' => [
                ['value' => 'LEAD_GENERATION', 'label' => 'Lid generatsiya'],
                ['value' => 'CONVERSATIONS', 'label' => 'Suhbatlar'],
            ],
            'OUTCOME_SALES' => [
                ['value' => 'CONVERSIONS', 'label' => 'Konversiyalar'],
                ['value' => 'VALUE', 'label' => 'Qiymat optimizatsiya'],
            ],
            'OUTCOME_APP_PROMOTION' => [
                ['value' => 'APP_INSTALLS', 'label' => 'Ilovani o\'rnatish'],
                ['value' => 'APP_EVENTS', 'label' => 'Ilova hodisalari'],
            ],
        ];

        return $goals[$objective] ?? [['value' => 'REACH', 'label' => 'Qamrov']];
    }

    /**
     * Get billing events for optimization goal
     */
    public function getBillingEvents(string $optimizationGoal): array
    {
        $events = [
            'REACH' => [
                ['value' => 'IMPRESSIONS', 'label' => 'Ko\'rishlar uchun (CPM)'],
            ],
            'LINK_CLICKS' => [
                ['value' => 'LINK_CLICKS', 'label' => 'Kliklar uchun (CPC)'],
                ['value' => 'IMPRESSIONS', 'label' => 'Ko\'rishlar uchun (CPM)'],
            ],
            'CONVERSIONS' => [
                ['value' => 'IMPRESSIONS', 'label' => 'Ko\'rishlar uchun (CPM)'],
            ],
            'LEAD_GENERATION' => [
                ['value' => 'IMPRESSIONS', 'label' => 'Ko\'rishlar uchun (CPM)'],
            ],
        ];

        return $events[$optimizationGoal] ?? [['value' => 'IMPRESSIONS', 'label' => 'Ko\'rishlar uchun (CPM)']];
    }

    /**
     * Get available Call to Action types
     */
    public function getCallToActionTypes(): array
    {
        return [
            ['value' => 'LEARN_MORE', 'label' => 'Batafsil'],
            ['value' => 'SHOP_NOW', 'label' => 'Xarid qilish'],
            ['value' => 'SIGN_UP', 'label' => 'Ro\'yxatdan o\'tish'],
            ['value' => 'CONTACT_US', 'label' => 'Bog\'lanish'],
            ['value' => 'BOOK_TRAVEL', 'label' => 'Band qilish'],
            ['value' => 'DOWNLOAD', 'label' => 'Yuklab olish'],
            ['value' => 'GET_OFFER', 'label' => 'Taklifni olish'],
            ['value' => 'GET_QUOTE', 'label' => 'Narx olish'],
            ['value' => 'SUBSCRIBE', 'label' => 'Obuna bo\'lish'],
            ['value' => 'WATCH_MORE', 'label' => 'Ko\'proq ko\'rish'],
            ['value' => 'SEND_MESSAGE', 'label' => 'Xabar yuborish'],
            ['value' => 'WHATSAPP_MESSAGE', 'label' => 'WhatsApp xabar'],
            ['value' => 'CALL_NOW', 'label' => 'Qo\'ng\'iroq qilish'],
            ['value' => 'APPLY_NOW', 'label' => 'Ariza berish'],
            ['value' => 'ORDER_NOW', 'label' => 'Buyurtma berish'],
        ];
    }

    /**
     * Get available countries for targeting
     */
    public function getCountries(): array
    {
        return [
            ['code' => 'UZ', 'name' => 'O\'zbekiston'],
            ['code' => 'KZ', 'name' => 'Qozog\'iston'],
            ['code' => 'RU', 'name' => 'Rossiya'],
            ['code' => 'TJ', 'name' => 'Tojikiston'],
            ['code' => 'KG', 'name' => 'Qirg\'iziston'],
            ['code' => 'TM', 'name' => 'Turkmaniston'],
            ['code' => 'AZ', 'name' => 'Ozarbayjon'],
            ['code' => 'GE', 'name' => 'Gruziya'],
            ['code' => 'TR', 'name' => 'Turkiya'],
            ['code' => 'AE', 'name' => 'BAA'],
            ['code' => 'US', 'name' => 'AQSH'],
            ['code' => 'GB', 'name' => 'Buyuk Britaniya'],
            ['code' => 'DE', 'name' => 'Germaniya'],
        ];
    }

    /**
     * Upload image from base64 data
     */
    public function uploadImageBase64(string $adAccountId, string $base64Data): array
    {
        $accountId = $this->formatAccountId($adAccountId);

        // Remove data URL prefix if present
        if (str_contains($base64Data, ',')) {
            $base64Data = explode(',', $base64Data)[1];
        }

        $response = Http::timeout(60)
            ->asForm()
            ->post("{$this->baseUrl}/{$accountId}/adimages", [
                'access_token' => $this->accessToken,
                'bytes' => $base64Data,
            ]);

        if ($response->failed()) {
            $error = $response->json('error', []);
            throw new \Exception($error['message'] ?? 'Image upload failed');
        }

        return $response->json();
    }

    /**
     * Create ad creative with full options
     */
    public function createFullAdCreative(string $adAccountId, array $data): array
    {
        $accountId = $this->formatAccountId($adAccountId);

        $objectStorySpec = [
            'page_id' => $data['page_id'],
        ];

        // Build link_data
        $linkData = [
            'link' => $data['link'] ?? $data['website_url'] ?? '',
            'message' => $data['primary_text'] ?? $data['message'] ?? '',
        ];

        // Add image if provided
        if (!empty($data['image_hash'])) {
            $linkData['image_hash'] = $data['image_hash'];
        }

        // Add headline if provided
        if (!empty($data['headline'])) {
            $linkData['name'] = $data['headline'];
        }

        // Add description if provided
        if (!empty($data['description'])) {
            $linkData['description'] = $data['description'];
        }

        // Add CTA
        if (!empty($data['call_to_action'])) {
            $linkData['call_to_action'] = [
                'type' => $data['call_to_action'],
            ];
            // Add CTA link if different from main link
            if (!empty($data['cta_link'])) {
                $linkData['call_to_action']['value'] = [
                    'link' => $data['cta_link'],
                ];
            }
        }

        $objectStorySpec['link_data'] = $linkData;

        $params = [
            'name' => $data['name'] ?? 'Creative_' . time(),
            'object_story_spec' => json_encode($objectStorySpec),
        ];

        return $this->post("/{$accountId}/adcreatives", $params);
    }

    /**
     * Create complete ad with creative in one step
     */
    public function createAdWithCreative(
        string $adAccountId,
        string $adSetId,
        string $pageId,
        array $creativeData,
        string $status = 'PAUSED'
    ): array {
        $accountId = $this->formatAccountId($adAccountId);

        // First create the creative
        $creative = $this->createFullAdCreative($adAccountId, array_merge($creativeData, [
            'page_id' => $pageId,
        ]));

        $creativeId = $creative['id'] ?? null;
        if (!$creativeId) {
            throw new \Exception('Creative yaratishda xatolik');
        }

        // Then create the ad
        return $this->post("/{$accountId}/ads", [
            'name' => $creativeData['ad_name'] ?? $creativeData['name'] ?? 'Ad_' . time(),
            'adset_id' => $adSetId,
            'status' => $status,
            'creative' => json_encode(['creative_id' => $creativeId]),
        ]);
    }

    /**
     * Get ad preview
     */
    public function getAdPreview(string $creativeId, string $format = 'DESKTOP_FEED_STANDARD'): array
    {
        return $this->get("/{$creativeId}/previews", [
            'ad_format' => $format,
        ]);
    }

    /**
     * Validate targeting spec
     */
    public function validateTargeting(string $adAccountId, array $targeting): bool
    {
        try {
            $this->getReachEstimate($adAccountId, $targeting);
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Get detailed targeting categories
     */
    public function getTargetingCategories(string $adAccountId): array
    {
        $accountId = $this->formatAccountId($adAccountId);

        return $this->get("/{$accountId}/targetingbrowse", [
            'limit' => 100,
        ]);
    }

    /**
     * Search behaviors for targeting
     */
    public function searchBehaviors(string $query): array
    {
        return $this->get('/search', [
            'type' => 'adTargetingCategory',
            'class' => 'behaviors',
            'q' => $query,
            'limit' => 20,
        ]);
    }

    /**
     * Get ad account currency info
     */
    public function getAccountCurrency(string $adAccountId): array
    {
        $accountId = $this->formatAccountId($adAccountId);

        return $this->get("/{$accountId}", [
            'fields' => 'currency,min_daily_budget,timezone_name',
        ]);
    }
}
