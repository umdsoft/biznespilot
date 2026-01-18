<?php

namespace App\Services;

use App\Models\AdIntegration;
use App\Models\GoogleAdsAdGroup;
use App\Models\GoogleAdsCampaign;
use App\Models\GoogleAdsCampaignInsight;
use App\Models\GoogleAdsKeyword;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class GoogleAdsSyncService
{
    private const API_VERSION = 'v15';

    private const API_BASE_URL = 'https://googleads.googleapis.com/v15';

    private AdIntegration $integration;

    private string $businessId;

    private bool $useMockData = true; // Toggle for mock data until developer token is available

    public function initialize(AdIntegration $integration): self
    {
        $this->integration = $integration;
        $this->businessId = $integration->business_id;

        // Check if we have a developer token
        $this->useMockData = empty($integration->developer_token);

        return $this;
    }

    /**
     * Full sync - campaigns, ad groups, keywords, insights
     */
    public function fullSync(): array
    {
        $results = [
            'campaigns' => 0,
            'ad_groups' => 0,
            'keywords' => 0,
            'insights' => 0,
            'errors' => [],
        ];

        try {
            $this->integration->markSyncStarted();

            // Sync campaigns
            $campaigns = $this->syncCampaigns();
            $results['campaigns'] = count($campaigns);

            // Sync ad groups for each campaign
            foreach ($campaigns as $campaign) {
                $adGroups = $this->syncAdGroups($campaign->id);
                $results['ad_groups'] += count($adGroups);

                // Sync keywords for each ad group
                foreach ($adGroups as $adGroup) {
                    $keywords = $this->syncKeywords($adGroup->id);
                    $results['keywords'] += count($keywords);
                }
            }

            // Sync insights for last 30 days
            $results['insights'] = $this->syncInsights(
                Carbon::now()->subDays(30),
                Carbon::now()
            );

            // Update campaign aggregates
            $this->updateCampaignAggregates();

            $this->integration->markSyncCompleted();
        } catch (\Exception $e) {
            $results['errors'][] = $e->getMessage();
            $this->integration->markSyncFailed($e->getMessage());
            Log::error('Google Ads sync failed', ['error' => $e->getMessage()]);
        }

        return $results;
    }

    /**
     * Sync campaigns
     */
    public function syncCampaigns(): array
    {
        if ($this->useMockData) {
            return $this->syncMockCampaigns();
        }

        return $this->syncCampaignsFromApi();
    }

    /**
     * Sync ad groups for a campaign
     */
    public function syncAdGroups(string $campaignId): array
    {
        if ($this->useMockData) {
            return $this->syncMockAdGroups($campaignId);
        }

        return $this->syncAdGroupsFromApi($campaignId);
    }

    /**
     * Sync keywords for an ad group
     */
    public function syncKeywords(string $adGroupId): array
    {
        if ($this->useMockData) {
            return $this->syncMockKeywords($adGroupId);
        }

        return $this->syncKeywordsFromApi($adGroupId);
    }

    /**
     * Sync insights for date range
     */
    public function syncInsights(Carbon $startDate, Carbon $endDate): int
    {
        if ($this->useMockData) {
            return $this->syncMockInsights($startDate, $endDate);
        }

        return $this->syncInsightsFromApi($startDate, $endDate);
    }

    /**
     * Update all campaign aggregates
     */
    public function updateCampaignAggregates(): void
    {
        $campaigns = GoogleAdsCampaign::where('ad_integration_id', $this->integration->id)->get();

        foreach ($campaigns as $campaign) {
            $campaign->updateAggregates();
        }
    }

    // ========== MOCK DATA METHODS ==========

    private function syncMockCampaigns(): array
    {
        $mockCampaigns = $this->getMockCampaignsData();
        $campaigns = [];

        foreach ($mockCampaigns as $data) {
            $campaign = GoogleAdsCampaign::updateOrCreate(
                [
                    'ad_integration_id' => $this->integration->id,
                    'google_campaign_id' => $data['google_campaign_id'],
                ],
                array_merge($data, [
                    'business_id' => $this->businessId,
                ])
            );
            $campaigns[] = $campaign;
        }

        return $campaigns;
    }

    private function syncMockAdGroups(string $campaignId): array
    {
        $mockAdGroups = $this->getMockAdGroupsData($campaignId);
        $adGroups = [];

        foreach ($mockAdGroups as $data) {
            $adGroup = GoogleAdsAdGroup::updateOrCreate(
                [
                    'campaign_id' => $campaignId,
                    'google_ad_group_id' => $data['google_ad_group_id'],
                ],
                array_merge($data, [
                    'business_id' => $this->businessId,
                ])
            );
            $adGroups[] = $adGroup;
        }

        return $adGroups;
    }

    private function syncMockKeywords(string $adGroupId): array
    {
        $mockKeywords = $this->getMockKeywordsData();
        $keywords = [];

        foreach ($mockKeywords as $data) {
            $keyword = GoogleAdsKeyword::updateOrCreate(
                [
                    'ad_group_id' => $adGroupId,
                    'google_criterion_id' => $data['google_criterion_id'],
                ],
                array_merge($data, [
                    'business_id' => $this->businessId,
                ])
            );
            $keywords[] = $keyword;
        }

        return $keywords;
    }

    private function syncMockInsights(Carbon $startDate, Carbon $endDate): int
    {
        $campaigns = GoogleAdsCampaign::where('ad_integration_id', $this->integration->id)->get();
        $count = 0;

        foreach ($campaigns as $campaign) {
            $currentDate = $startDate->copy();

            while ($currentDate <= $endDate) {
                $insight = $this->getMockInsightData($currentDate);

                GoogleAdsCampaignInsight::updateOrCreate(
                    [
                        'campaign_id' => $campaign->id,
                        'date' => $currentDate->format('Y-m-d'),
                    ],
                    array_merge($insight, [
                        'business_id' => $this->businessId,
                    ])
                );

                $count++;
                $currentDate->addDay();
            }
        }

        return $count;
    }

    // ========== MOCK DATA GENERATORS ==========

    private function getMockCampaignsData(): array
    {
        return [
            [
                'google_campaign_id' => 'mock_campaign_1',
                'name' => 'Asosiy qidiruv kampaniyasi',
                'advertising_channel_type' => 'SEARCH',
                'status' => 'ENABLED',
                'serving_status' => 'SERVING',
                'daily_budget' => 50000,
                'start_date' => Carbon::now()->subMonths(2)->format('Y-m-d'),
            ],
            [
                'google_campaign_id' => 'mock_campaign_2',
                'name' => 'Display reklama',
                'advertising_channel_type' => 'DISPLAY',
                'status' => 'ENABLED',
                'serving_status' => 'SERVING',
                'daily_budget' => 30000,
                'start_date' => Carbon::now()->subMonths(1)->format('Y-m-d'),
            ],
            [
                'google_campaign_id' => 'mock_campaign_3',
                'name' => 'YouTube video reklama',
                'advertising_channel_type' => 'VIDEO',
                'status' => 'PAUSED',
                'serving_status' => 'NONE',
                'daily_budget' => 100000,
                'start_date' => Carbon::now()->subWeeks(3)->format('Y-m-d'),
            ],
            [
                'google_campaign_id' => 'mock_campaign_4',
                'name' => 'Performance Max',
                'advertising_channel_type' => 'PERFORMANCE_MAX',
                'status' => 'ENABLED',
                'serving_status' => 'SERVING',
                'daily_budget' => 75000,
                'start_date' => Carbon::now()->subWeeks(2)->format('Y-m-d'),
            ],
        ];
    }

    private function getMockAdGroupsData(string $campaignId): array
    {
        return [
            [
                'google_ad_group_id' => 'mock_adgroup_'.Str::random(6),
                'name' => 'Asosiy kalit so\'zlar',
                'status' => 'ENABLED',
                'type' => 'SEARCH_STANDARD',
                'cpc_bid' => 1500,
            ],
            [
                'google_ad_group_id' => 'mock_adgroup_'.Str::random(6),
                'name' => 'Qo\'shimcha kalit so\'zlar',
                'status' => 'ENABLED',
                'type' => 'SEARCH_STANDARD',
                'cpc_bid' => 1200,
            ],
        ];
    }

    private function getMockKeywordsData(): array
    {
        $keywords = [
            'marketing xizmatlari',
            'reklama agentligi',
            'SMM xizmatlari',
            'SEO optimizatsiya',
            'web sayt yaratish',
            'brending xizmatlari',
            'kontekst reklama',
            'target reklama',
        ];

        $result = [];
        foreach ($keywords as $index => $keyword) {
            $matchTypes = ['EXACT', 'PHRASE', 'BROAD'];
            $result[] = [
                'google_criterion_id' => 'mock_keyword_'.($index + 1),
                'keyword_text' => $keyword,
                'match_type' => $matchTypes[array_rand($matchTypes)],
                'status' => 'ENABLED',
                'cpc_bid' => rand(800, 2000),
                'quality_score' => rand(5, 10),
                'total_impressions' => rand(1000, 50000),
                'total_clicks' => rand(50, 2000),
                'total_cost' => rand(50000, 500000),
            ];
        }

        return $result;
    }

    private function getMockInsightData(Carbon $date): array
    {
        $impressions = rand(500, 5000);
        $clicks = rand(20, (int) ($impressions * 0.1));
        $cost = $clicks * rand(800, 2000);
        $conversions = rand(0, (int) ($clicks * 0.15));
        $conversionValue = $conversions * rand(50000, 200000);

        return [
            'cost' => $cost,
            'impressions' => $impressions,
            'clicks' => $clicks,
            'ctr' => $impressions > 0 ? ($clicks / $impressions) * 100 : 0,
            'cpc' => $clicks > 0 ? $cost / $clicks : 0,
            'cpm' => $impressions > 0 ? ($cost / $impressions) * 1000 : 0,
            'conversions' => $conversions,
            'conversion_rate' => $clicks > 0 ? ($conversions / $clicks) * 100 : 0,
            'conversion_value' => $conversionValue,
            'cost_per_conversion' => $conversions > 0 ? $cost / $conversions : 0,
            'roas' => $cost > 0 ? $conversionValue / $cost : 0,
        ];
    }

    // ========== REAL API METHODS (TO BE IMPLEMENTED) ==========

    private function syncCampaignsFromApi(): array
    {
        // TODO: Implement when developer token is available
        return [];
    }

    private function syncAdGroupsFromApi(string $campaignId): array
    {
        // TODO: Implement when developer token is available
        return [];
    }

    private function syncKeywordsFromApi(string $adGroupId): array
    {
        // TODO: Implement when developer token is available
        return [];
    }

    private function syncInsightsFromApi(Carbon $startDate, Carbon $endDate): int
    {
        // TODO: Implement when developer token is available
        return 0;
    }
}
