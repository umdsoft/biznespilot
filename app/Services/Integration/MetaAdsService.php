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
}
