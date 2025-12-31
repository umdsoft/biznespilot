<?php

namespace App\Services;

use App\Models\Integration;
use App\Models\MetaAdAccount;
use App\Models\MetaCampaign;
use App\Models\MetaAdSet;
use App\Models\MetaAd;
use App\Models\MetaInsight;
use App\Models\MetaCampaignInsight;
use App\Models\MetaSyncLog;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class MetaSyncService
{
    private const API_VERSION = 'v18.0';
    private const BASE_URL = 'https://graph.facebook.com';

    private string $accessToken;
    private Integration $integration;
    private string $businessId;

    /**
     * Initialize the service with an integration
     */
    public function initialize(Integration $integration): self
    {
        $this->integration = $integration;
        $this->businessId = $integration->business_id;

        $credentials = json_decode($integration->credentials, true);
        $this->accessToken = $credentials['access_token'] ?? '';

        return $this;
    }

    /**
     * Full sync - syncs all data for the last 12 months
     */
    public function fullSync(): array
    {
        $results = [
            'success' => true,
            'accounts' => 0,
            'campaigns' => 0,
            'adsets' => 0,
            'ads' => 0,
            'insights' => 0,
            'errors' => [],
        ];

        try {
            // 1. Sync Ad Accounts
            $accounts = $this->syncAdAccounts();
            $results['accounts'] = count($accounts);

            // 2. For each account, sync campaigns, adsets, ads, and insights
            foreach ($accounts as $account) {
                try {
                    // Sync campaigns
                    $campaigns = $this->syncCampaigns($account);
                    $results['campaigns'] += count($campaigns);

                    // Sync ad sets
                    $adsets = $this->syncAdSets($account);
                    $results['adsets'] += count($adsets);

                    // Sync ads
                    $ads = $this->syncAds($account);
                    $results['ads'] += count($ads);

                    // Sync insights (12 months)
                    $insightCount = $this->syncInsights($account);
                    $results['insights'] += $insightCount;

                    // Update last sync time
                    $account->update(['last_sync_at' => now()]);

                } catch (\Exception $e) {
                    Log::error("Error syncing account {$account->meta_account_id}: " . $e->getMessage());
                    $results['errors'][] = "Account {$account->name}: " . $e->getMessage();
                }
            }

            // Update integration sync info
            $this->integration->update([
                'last_sync_at' => now(),
                'sync_count' => ($this->integration->sync_count ?? 0) + 1,
                'last_error_at' => null,
                'last_error_message' => null,
            ]);

        } catch (\Exception $e) {
            Log::error("MetaSyncService fullSync error: " . $e->getMessage());
            $results['success'] = false;
            $results['errors'][] = $e->getMessage();

            $this->integration->update([
                'last_error_at' => now(),
                'last_error_message' => $e->getMessage(),
            ]);
        }

        return $results;
    }

    /**
     * Sync Ad Accounts from Meta
     */
    public function syncAdAccounts(): array
    {
        $response = $this->makeRequest('/me/adaccounts', [
            'fields' => 'id,account_id,name,currency,timezone_name,account_status,amount_spent,business_name',
            'limit' => 100,
        ]);

        $accounts = [];
        $data = $response['data'] ?? [];

        foreach ($data as $accountData) {
            $account = MetaAdAccount::updateOrCreate(
                [
                    'integration_id' => $this->integration->id,
                    'meta_account_id' => $accountData['id'],
                ],
                [
                    'business_id' => $this->businessId,
                    'name' => $accountData['name'] ?? $accountData['id'],
                    'currency' => $accountData['currency'] ?? 'USD',
                    'timezone' => $accountData['timezone_name'] ?? null,
                    'account_status' => $accountData['account_status'] ?? 1,
                    'amount_spent' => ($accountData['amount_spent'] ?? 0) / 100, // Meta returns cents
                    'metadata' => [
                        'business_name' => $accountData['business_name'] ?? null,
                        'account_id' => $accountData['account_id'] ?? null,
                    ],
                ]
            );

            $accounts[] = $account;
        }

        // Set first account as primary if none is set
        if (!empty($accounts) && !MetaAdAccount::where('integration_id', $this->integration->id)->where('is_primary', true)->exists()) {
            $accounts[0]->update(['is_primary' => true]);
        }

        return $accounts;
    }

    /**
     * Sync Campaigns for an Ad Account
     */
    public function syncCampaigns(MetaAdAccount $account): array
    {
        $response = $this->makeRequest("/{$account->meta_account_id}/campaigns", [
            'fields' => 'id,name,objective,status,effective_status,daily_budget,lifetime_budget,budget_remaining,start_time,stop_time,created_time,updated_time',
            'limit' => 500,
        ]);

        $campaigns = [];
        $data = $response['data'] ?? [];

        foreach ($data as $campaignData) {
            $campaign = MetaCampaign::updateOrCreate(
                [
                    'ad_account_id' => $account->id,
                    'meta_campaign_id' => $campaignData['id'],
                ],
                [
                    'business_id' => $this->businessId,
                    'name' => $campaignData['name'],
                    'objective' => $campaignData['objective'] ?? null,
                    'status' => $campaignData['status'],
                    'effective_status' => $campaignData['effective_status'] ?? null,
                    'daily_budget' => isset($campaignData['daily_budget']) ? $campaignData['daily_budget'] / 100 : null,
                    'lifetime_budget' => isset($campaignData['lifetime_budget']) ? $campaignData['lifetime_budget'] / 100 : null,
                    'budget_remaining' => $campaignData['budget_remaining'] ?? null,
                    'start_time' => $this->parseDateTime($campaignData['start_time'] ?? null),
                    'stop_time' => $this->parseDateTime($campaignData['stop_time'] ?? null),
                    'metadata' => [
                        'created_time' => $campaignData['created_time'] ?? null,
                        'updated_time' => $campaignData['updated_time'] ?? null,
                    ],
                ]
            );

            $campaigns[] = $campaign;
        }

        return $campaigns;
    }

    /**
     * Sync Ad Sets for an Ad Account
     */
    public function syncAdSets(MetaAdAccount $account): array
    {
        $response = $this->makeRequest("/{$account->meta_account_id}/adsets", [
            'fields' => 'id,name,campaign_id,status,effective_status,daily_budget,lifetime_budget,budget_remaining,targeting,optimization_goal,billing_event,bid_strategy,bid_amount,start_time,end_time',
            'limit' => 500,
        ]);

        $adsets = [];
        $data = $response['data'] ?? [];

        foreach ($data as $adsetData) {
            // Find campaign
            $campaign = MetaCampaign::where('ad_account_id', $account->id)
                ->where('meta_campaign_id', $adsetData['campaign_id'])
                ->first();

            $adset = MetaAdSet::updateOrCreate(
                [
                    'ad_account_id' => $account->id,
                    'meta_adset_id' => $adsetData['id'],
                ],
                [
                    'business_id' => $this->businessId,
                    'campaign_id' => $campaign?->id,
                    'meta_campaign_id' => $adsetData['campaign_id'],
                    'name' => $adsetData['name'],
                    'status' => $adsetData['status'],
                    'effective_status' => $adsetData['effective_status'] ?? null,
                    'daily_budget' => isset($adsetData['daily_budget']) ? $adsetData['daily_budget'] / 100 : null,
                    'lifetime_budget' => isset($adsetData['lifetime_budget']) ? $adsetData['lifetime_budget'] / 100 : null,
                    'optimization_goal' => $adsetData['optimization_goal'] ?? null,
                    'billing_event' => $adsetData['billing_event'] ?? null,
                    'bid_strategy' => $adsetData['bid_strategy'] ?? null,
                    'bid_amount' => isset($adsetData['bid_amount']) ? $adsetData['bid_amount'] / 100 : null,
                    'targeting' => $adsetData['targeting'] ?? null,
                    'start_time' => $this->parseDateTime($adsetData['start_time'] ?? null),
                    'end_time' => $this->parseDateTime($adsetData['end_time'] ?? null),
                ]
            );

            $adsets[] = $adset;
        }

        return $adsets;
    }

    /**
     * Sync Ads for an Ad Account
     */
    public function syncAds(MetaAdAccount $account): array
    {
        $response = $this->makeRequest("/{$account->meta_account_id}/ads", [
            'fields' => 'id,name,adset_id,campaign_id,status,effective_status,creative{id,name,object_story_spec,image_url,thumbnail_url,video_id}',
            'limit' => 500,
        ]);

        $ads = [];
        $data = $response['data'] ?? [];

        foreach ($data as $adData) {
            // Find adset
            $adset = MetaAdSet::where('ad_account_id', $account->id)
                ->where('meta_adset_id', $adData['adset_id'] ?? '')
                ->first();

            // Find campaign
            $campaign = MetaCampaign::where('ad_account_id', $account->id)
                ->where('meta_campaign_id', $adData['campaign_id'] ?? '')
                ->first();

            $ad = MetaAd::updateOrCreate(
                [
                    'ad_account_id' => $account->id,
                    'meta_ad_id' => $adData['id'],
                ],
                [
                    'business_id' => $this->businessId,
                    'adset_id' => $adset?->id,
                    'campaign_id' => $campaign?->id,
                    'meta_adset_id' => $adData['adset_id'] ?? null,
                    'meta_campaign_id' => $adData['campaign_id'] ?? null,
                    'name' => $adData['name'],
                    'status' => $adData['status'],
                    'effective_status' => $adData['effective_status'] ?? null,
                    'creative_id' => $adData['creative']['id'] ?? null,
                    'creative_thumbnail_url' => $adData['creative']['thumbnail_url'] ?? null,
                    'creative_body' => $adData['creative']['object_story_spec']['link_data']['message']
                        ?? $adData['creative']['object_story_spec']['video_data']['message'] ?? null,
                    'creative_title' => $adData['creative']['name'] ?? null,
                    'creative_link_url' => $adData['creative']['object_story_spec']['link_data']['link']
                        ?? $adData['creative']['object_story_spec']['video_data']['image_url'] ?? null,
                    'creative_call_to_action' => $adData['creative']['object_story_spec']['link_data']['call_to_action']['type']
                        ?? $adData['creative']['object_story_spec']['video_data']['call_to_action']['type'] ?? null,
                    'metadata' => $adData,
                ]
            );

            $ads[] = $ad;
        }

        return $ads;
    }

    /**
     * Sync Insights for an Ad Account (last 12 months)
     */
    public function syncInsights(MetaAdAccount $account): int
    {
        $count = 0;
        $endDate = Carbon::today();
        $startDate = Carbon::today()->subMonths(12);

        // 1. Account level insights (daily breakdown)
        $count += $this->syncAccountInsights($account, $startDate, $endDate);

        // 2. Campaign level insights
        $count += $this->syncCampaignInsights($account, $startDate, $endDate);

        // 3. Demographics breakdown (age, gender)
        $count += $this->syncDemographicInsights($account, $startDate, $endDate);

        // 4. Platform breakdown (facebook, instagram)
        $count += $this->syncPlatformInsights($account, $startDate, $endDate);

        // 5. Update campaign aggregates from insights
        $this->updateCampaignAggregates($account);

        return $count;
    }

    /**
     * Update all campaign aggregates for an account
     */
    private function updateCampaignAggregates(MetaAdAccount $account): void
    {
        $campaigns = MetaCampaign::withoutGlobalScope('business')
            ->where('ad_account_id', $account->id)
            ->get();

        foreach ($campaigns as $campaign) {
            $campaign->updateAggregates();
        }
    }

    /**
     * Sync Account Level Insights
     */
    private function syncAccountInsights(MetaAdAccount $account, Carbon $startDate, Carbon $endDate): int
    {
        $count = 0;

        // Sync in 3-month chunks to avoid API limits
        $currentStart = clone $startDate;

        while ($currentStart < $endDate) {
            $currentEnd = (clone $currentStart)->addMonths(3);
            if ($currentEnd > $endDate) {
                $currentEnd = $endDate;
            }

            $response = $this->makeRequest("/{$account->meta_account_id}/insights", [
                'fields' => $this->getInsightFields(),
                'time_range' => json_encode([
                    'since' => $currentStart->format('Y-m-d'),
                    'until' => $currentEnd->format('Y-m-d'),
                ]),
                'time_increment' => 1, // Daily breakdown
                'level' => 'account',
                'limit' => 500,
            ]);

            $count += $this->saveInsights($account, $response['data'] ?? [], 'account', $account->meta_account_id, $account->name);

            $currentStart = $currentEnd->addDay();
        }

        return $count;
    }

    /**
     * Sync Campaign Level Insights (to meta_insights table)
     */
    private function syncCampaignInsights(MetaAdAccount $account, Carbon $startDate, Carbon $endDate): int
    {
        $count = 0;

        $response = $this->makeRequest("/{$account->meta_account_id}/insights", [
            'fields' => $this->getInsightFields() . ',campaign_id,campaign_name',
            'time_range' => json_encode([
                'since' => $startDate->format('Y-m-d'),
                'until' => $endDate->format('Y-m-d'),
            ]),
            'time_increment' => 'monthly', // Monthly for campaigns
            'level' => 'campaign',
            'limit' => 1000,
        ]);

        foreach ($response['data'] ?? [] as $insight) {
            $count += $this->saveInsights(
                $account,
                [$insight],
                'campaign',
                $insight['campaign_id'] ?? null,
                $insight['campaign_name'] ?? null
            );
        }

        // Also sync to meta_campaign_insights table with daily breakdown
        $count += $this->syncDetailedCampaignInsights($account, $startDate, $endDate);

        return $count;
    }

    /**
     * Sync Detailed Campaign Insights (to meta_campaign_insights table with daily data)
     */
    private function syncDetailedCampaignInsights(MetaAdAccount $account, Carbon $startDate, Carbon $endDate): int
    {
        $count = 0;

        // Get all campaigns for this account
        $campaigns = MetaCampaign::withoutGlobalScope('business')
            ->where('ad_account_id', $account->id)
            ->get()
            ->keyBy('meta_campaign_id');

        if ($campaigns->isEmpty()) {
            return 0;
        }

        // Sync in chunks to avoid API limits
        $currentStart = clone $startDate;

        while ($currentStart < $endDate) {
            $currentEnd = (clone $currentStart)->addMonths(3);
            if ($currentEnd > $endDate) {
                $currentEnd = $endDate;
            }

            try {
                $response = $this->makeRequest("/{$account->meta_account_id}/insights", [
                    'fields' => $this->getInsightFields() . ',campaign_id,campaign_name',
                    'time_range' => json_encode([
                        'since' => $currentStart->format('Y-m-d'),
                        'until' => $currentEnd->format('Y-m-d'),
                    ]),
                    'time_increment' => 1, // Daily breakdown
                    'level' => 'campaign',
                    'limit' => 5000,
                ]);

                foreach ($response['data'] ?? [] as $insight) {
                    $metaCampaignId = $insight['campaign_id'] ?? null;
                    $campaign = $campaigns->get($metaCampaignId);

                    if (!$campaign) {
                        continue;
                    }

                    MetaCampaignInsight::updateOrCreate(
                        [
                            'campaign_id' => $campaign->id,
                            'date' => $insight['date_start'] ?? now()->format('Y-m-d'),
                        ],
                        [
                            'business_id' => $this->businessId,
                            'spend' => $insight['spend'] ?? 0,
                            'impressions' => $insight['impressions'] ?? 0,
                            'reach' => $insight['reach'] ?? 0,
                            'clicks' => $insight['clicks'] ?? 0,
                            'cpc' => $insight['cpc'] ?? 0,
                            'cpm' => $insight['cpm'] ?? 0,
                            'ctr' => $insight['ctr'] ?? 0,
                            'frequency' => $insight['frequency'] ?? 0,
                            'conversions' => $this->getActionValue($insight['actions'] ?? [], 'omni_purchase')
                                + $this->getActionValue($insight['actions'] ?? [], 'purchase'),
                            'leads' => $this->getActionValue($insight['actions'] ?? [], 'lead'),
                            'purchases' => $this->getActionValue($insight['actions'] ?? [], 'purchase'),
                            'add_to_cart' => $this->getActionValue($insight['actions'] ?? [], 'omni_add_to_cart'),
                            'link_clicks' => $this->getActionValue($insight['actions'] ?? [], 'link_click'),
                            'video_views' => $this->getActionValue($insight['actions'] ?? [], 'video_view'),
                            'cost_per_conversion' => isset($insight['cost_per_action_type'])
                                ? $this->getActionValue($insight['cost_per_action_type'], 'purchase')
                                : 0,
                            'cost_per_lead' => isset($insight['cost_per_action_type'])
                                ? $this->getActionValue($insight['cost_per_action_type'], 'lead')
                                : 0,
                            'actions' => $insight['actions'] ?? null,
                            'action_values' => $insight['action_values'] ?? null,
                        ]
                    );
                    $count++;
                }
            } catch (\Exception $e) {
                Log::warning("Error syncing detailed campaign insights: " . $e->getMessage());
            }

            $currentStart = $currentEnd->addDay();
        }

        // Update campaign aggregates
        foreach ($campaigns as $campaign) {
            $campaign->updateAggregates();
        }

        return $count;
    }

    /**
     * Sync Demographic Insights (age, gender breakdown)
     */
    private function syncDemographicInsights(MetaAdAccount $account, Carbon $startDate, Carbon $endDate): int
    {
        $count = 0;

        // Age breakdown
        $response = $this->makeRequest("/{$account->meta_account_id}/insights", [
            'fields' => $this->getInsightFields(),
            'time_range' => json_encode([
                'since' => $startDate->format('Y-m-d'),
                'until' => $endDate->format('Y-m-d'),
            ]),
            'breakdowns' => 'age',
            'level' => 'account',
            'limit' => 500,
        ]);

        foreach ($response['data'] ?? [] as $insight) {
            $count += $this->saveInsightWithBreakdown($account, $insight, 'age_range', $insight['age'] ?? null);
        }

        // Gender breakdown
        $response = $this->makeRequest("/{$account->meta_account_id}/insights", [
            'fields' => $this->getInsightFields(),
            'time_range' => json_encode([
                'since' => $startDate->format('Y-m-d'),
                'until' => $endDate->format('Y-m-d'),
            ]),
            'breakdowns' => 'gender',
            'level' => 'account',
            'limit' => 500,
        ]);

        foreach ($response['data'] ?? [] as $insight) {
            $count += $this->saveInsightWithBreakdown($account, $insight, 'gender', $insight['gender'] ?? null);
        }

        return $count;
    }

    /**
     * Sync Platform Insights (facebook, instagram, etc.)
     */
    private function syncPlatformInsights(MetaAdAccount $account, Carbon $startDate, Carbon $endDate): int
    {
        $count = 0;

        // Publisher platform breakdown
        $response = $this->makeRequest("/{$account->meta_account_id}/insights", [
            'fields' => $this->getInsightFields(),
            'time_range' => json_encode([
                'since' => $startDate->format('Y-m-d'),
                'until' => $endDate->format('Y-m-d'),
            ]),
            'breakdowns' => 'publisher_platform',
            'level' => 'account',
            'limit' => 500,
        ]);

        foreach ($response['data'] ?? [] as $insight) {
            $count += $this->saveInsightWithBreakdown($account, $insight, 'publisher_platform', $insight['publisher_platform'] ?? null);
        }

        // Platform position breakdown
        $response = $this->makeRequest("/{$account->meta_account_id}/insights", [
            'fields' => $this->getInsightFields(),
            'time_range' => json_encode([
                'since' => $startDate->format('Y-m-d'),
                'until' => $endDate->format('Y-m-d'),
            ]),
            'breakdowns' => 'publisher_platform,platform_position',
            'level' => 'account',
            'limit' => 500,
        ]);

        foreach ($response['data'] ?? [] as $insight) {
            MetaInsight::updateOrCreate(
                [
                    'ad_account_id' => $account->id,
                    'business_id' => $this->businessId,
                    'object_type' => 'account',
                    'object_id' => $account->meta_account_id,
                    'date_start' => $insight['date_start'] ?? now()->format('Y-m-d'),
                    'publisher_platform' => $insight['publisher_platform'] ?? null,
                    'platform_position' => $insight['platform_position'] ?? null,
                ],
                $this->mapInsightData($insight)
            );
            $count++;
        }

        return $count;
    }

    /**
     * Save insights to database
     */
    private function saveInsights(MetaAdAccount $account, array $insights, string $objectType, ?string $objectId, ?string $objectName): int
    {
        $count = 0;

        foreach ($insights as $insight) {
            MetaInsight::updateOrCreate(
                [
                    'ad_account_id' => $account->id,
                    'business_id' => $this->businessId,
                    'object_type' => $objectType,
                    'object_id' => $objectId ?? $account->meta_account_id,
                    'date_start' => $insight['date_start'] ?? now()->format('Y-m-d'),
                ],
                array_merge(
                    $this->mapInsightData($insight),
                    ['object_name' => $objectName]
                )
            );
            $count++;
        }

        return $count;
    }

    /**
     * Save insight with a specific breakdown
     */
    private function saveInsightWithBreakdown(MetaAdAccount $account, array $insight, string $breakdownField, ?string $breakdownValue): int
    {
        MetaInsight::updateOrCreate(
            [
                'ad_account_id' => $account->id,
                'business_id' => $this->businessId,
                'object_type' => 'account',
                'object_id' => $account->meta_account_id,
                'date_start' => $insight['date_start'] ?? now()->format('Y-m-d'),
                $breakdownField => $breakdownValue,
            ],
            array_merge(
                $this->mapInsightData($insight),
                ['object_name' => $account->name]
            )
        );

        return 1;
    }

    /**
     * Map API insight data to database fields
     */
    private function mapInsightData(array $insight): array
    {
        return [
            'date_start' => $insight['date_start'] ?? now()->format('Y-m-d'),
            'date_stop' => $insight['date_stop'] ?? null,
            'impressions' => $insight['impressions'] ?? 0,
            'reach' => $insight['reach'] ?? 0,
            'frequency' => $insight['frequency'] ?? 0,
            'clicks' => $insight['clicks'] ?? 0,
            'unique_clicks' => $insight['unique_clicks'] ?? 0,
            'link_clicks' => $this->getActionValue($insight['actions'] ?? [], 'link_click'),
            'cpc' => $insight['cpc'] ?? 0,
            'cpm' => $insight['cpm'] ?? 0,
            'cpp' => $insight['cpp'] ?? 0,
            'ctr' => $insight['ctr'] ?? 0,
            'unique_ctr' => $insight['unique_ctr'] ?? 0,
            'spend' => $insight['spend'] ?? 0,
            'post_engagement' => $this->getActionValue($insight['actions'] ?? [], 'post_engagement'),
            'page_engagement' => $this->getActionValue($insight['actions'] ?? [], 'page_engagement'),
            'post_reactions' => $this->getActionValue($insight['actions'] ?? [], 'post_reaction'),
            'post_comments' => $this->getActionValue($insight['actions'] ?? [], 'comment'),
            'post_shares' => $this->getActionValue($insight['actions'] ?? [], 'post'),
            'post_saves' => $this->getActionValue($insight['actions'] ?? [], 'onsite_conversion.post_save'),
            'video_views' => $this->getActionValue($insight['actions'] ?? [], 'video_view'),
            'video_views_p25' => $insight['video_p25_watched_actions'][0]['value'] ?? 0,
            'video_views_p50' => $insight['video_p50_watched_actions'][0]['value'] ?? 0,
            'video_views_p75' => $insight['video_p75_watched_actions'][0]['value'] ?? 0,
            'video_views_p100' => $insight['video_p100_watched_actions'][0]['value'] ?? 0,
            'conversions' => $this->getActionValue($insight['actions'] ?? [], 'omni_purchase')
                + $this->getActionValue($insight['actions'] ?? [], 'purchase'),
            'conversion_value' => $this->getActionValueSum($insight['action_values'] ?? [], ['omni_purchase', 'purchase']),
            'cost_per_conversion' => isset($insight['cost_per_action_type'])
                ? $this->getActionValue($insight['cost_per_action_type'], 'purchase')
                : 0,
            'actions' => $insight['actions'] ?? null,
            'action_values' => $insight['action_values'] ?? null,
            'cost_per_action_type' => $insight['cost_per_action_type'] ?? null,
        ];
    }

    /**
     * Get value for a specific action type
     */
    private function getActionValue(array $actions, string $actionType): int
    {
        foreach ($actions as $action) {
            if (($action['action_type'] ?? '') === $actionType) {
                return (int) ($action['value'] ?? 0);
            }
        }
        return 0;
    }

    /**
     * Get sum of values for multiple action types
     */
    private function getActionValueSum(array $actionValues, array $actionTypes): float
    {
        $sum = 0;
        foreach ($actionValues as $action) {
            if (in_array($action['action_type'] ?? '', $actionTypes)) {
                $sum += (float) ($action['value'] ?? 0);
            }
        }
        return $sum;
    }

    /**
     * Get insight fields for API request
     */
    private function getInsightFields(): string
    {
        return implode(',', [
            'impressions',
            'reach',
            'frequency',
            'clicks',
            'unique_clicks',
            'cpc',
            'cpm',
            'cpp',
            'ctr',
            'unique_ctr',
            'spend',
            'actions',
            'action_values',
            'cost_per_action_type',
            'video_p25_watched_actions',
            'video_p50_watched_actions',
            'video_p75_watched_actions',
            'video_p100_watched_actions',
            'date_start',
            'date_stop',
        ]);
    }

    /**
     * Parse datetime value safely
     */
    private function parseDateTime($value): ?Carbon
    {
        if (empty($value) || $value === '0' || $value === 0) {
            return null;
        }
        try {
            $parsed = Carbon::parse($value);
            // Check for Unix epoch (1970-01-01) or earlier - treat as null
            if ($parsed->year < 1990) {
                return null;
            }
            return $parsed;
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Make API request to Meta Graph API
     */
    private function makeRequest(string $endpoint, array $params = []): array
    {
        $url = self::BASE_URL . '/' . self::API_VERSION . $endpoint;

        $params['access_token'] = $this->accessToken;

        $response = Http::timeout(60)->get($url, $params);

        if (!$response->successful()) {
            $error = $response->json('error') ?? ['message' => 'Unknown error'];
            throw new \Exception("Meta API Error: " . ($error['message'] ?? 'Request failed'));
        }

        return $response->json();
    }

    /**
     * Quick sync - only sync recent data (last 30 days)
     */
    public function quickSync(): array
    {
        $results = [
            'success' => true,
            'insights' => 0,
            'errors' => [],
        ];

        try {
            $accounts = MetaAdAccount::where('integration_id', $this->integration->id)->get();

            foreach ($accounts as $account) {
                $endDate = Carbon::today();
                $startDate = Carbon::today()->subDays(30);

                // Only sync account level insights for quick sync
                $count = $this->syncAccountInsights($account, $startDate, $endDate);
                $results['insights'] += $count;

                $account->update(['last_sync_at' => now()]);
            }

            $this->integration->update([
                'last_sync_at' => now(),
            ]);

        } catch (\Exception $e) {
            Log::error("MetaSyncService quickSync error: " . $e->getMessage());
            $results['success'] = false;
            $results['errors'][] = $e->getMessage();
        }

        return $results;
    }

    /**
     * Get sync status for an integration
     */
    public function getSyncStatus(): array
    {
        $accounts = MetaAdAccount::where('integration_id', $this->integration->id)->get();

        $totalInsights = MetaInsight::whereIn('ad_account_id', $accounts->pluck('id'))->count();
        $totalCampaigns = MetaCampaign::whereIn('ad_account_id', $accounts->pluck('id'))->count();

        $oldestInsight = MetaInsight::whereIn('ad_account_id', $accounts->pluck('id'))
            ->orderBy('date_start', 'asc')
            ->first();

        $newestInsight = MetaInsight::whereIn('ad_account_id', $accounts->pluck('id'))
            ->orderBy('date_start', 'desc')
            ->first();

        return [
            'accounts' => $accounts->count(),
            'campaigns' => $totalCampaigns,
            'insights' => $totalInsights,
            'date_range' => [
                'from' => $oldestInsight?->date_start,
                'to' => $newestInsight?->date_start,
            ],
            'last_sync' => $this->integration->last_sync_at?->format('Y-m-d H:i:s'),
        ];
    }
}
