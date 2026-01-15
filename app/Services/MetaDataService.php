<?php

namespace App\Services;

use App\Models\MetaAdAccount;
use App\Models\MetaCampaign;
use App\Models\MetaInsight;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class MetaDataService
{
    /**
     * Cache TTL in seconds (10 minutes for Meta data)
     */
    protected int $cacheTTL = 600;

    /**
     * Get overview stats from local database
     * OPTIMIZED: Results are cached for 10 minutes
     */
    public function getOverview(string $adAccountId, string $datePreset): array
    {
        $cacheKey = "meta_overview_{$adAccountId}_{$datePreset}";

        return Cache::remember($cacheKey, $this->cacheTTL, function () use ($adAccountId, $datePreset) {
            $dates = $this->getDateRange($datePreset);
            $noFilter = $dates['no_filter'] ?? false;

            $current = $this->getAggregatedInsights($adAccountId, $dates['start'], $dates['end'], $noFilter);

            // For 'maximum', no comparison data
            if ($noFilter) {
                return [
                    'current' => $current,
                    'change' => [],
                ];
            }

            $previousDates = $this->getPreviousDateRange($datePreset);
            $previous = $this->getAggregatedInsights($adAccountId, $previousDates['start'], $previousDates['end'], false);

            return [
                'current' => $current,
                'change' => $this->calculateChange($current, $previous),
            ];
        });
    }

    /**
     * Invalidate all cache for an ad account
     */
    public function invalidateCache(string $adAccountId): void
    {
        $presets = ['last_7d', 'last_14d', 'last_30d', 'last_90d', 'last_365d', 'maximum'];
        foreach ($presets as $preset) {
            Cache::forget("meta_overview_{$adAccountId}_{$preset}");
            Cache::forget("meta_campaigns_{$adAccountId}_{$preset}");
            Cache::forget("meta_demographics_{$adAccountId}_{$preset}");
            Cache::forget("meta_placements_{$adAccountId}_{$preset}");
        }
        Cache::forget("meta_trend_{$adAccountId}_30");
        Cache::forget("meta_trend_{$adAccountId}_7");
        Cache::forget("meta_trend_{$adAccountId}_90");
    }

    /**
     * Check if we have any data for this account
     */
    public function hasData(string $adAccountId): bool
    {
        return MetaInsight::withoutGlobalScope('business')->where('ad_account_id', $adAccountId)->exists();
    }

    /**
     * Get campaigns with insights from local database
     * OPTIMIZED: Results are cached for 10 minutes
     */
    public function getCampaigns(string $adAccountId, string $datePreset): array
    {
        $cacheKey = "meta_campaigns_{$adAccountId}_{$datePreset}";

        return Cache::remember($cacheKey, $this->cacheTTL, function () use ($adAccountId, $datePreset) {
            $dates = $this->getDateRange($datePreset);

            $campaigns = MetaCampaign::withoutGlobalScope('business')
                ->where('ad_account_id', $adAccountId)
                ->get();

            $query = MetaInsight::withoutGlobalScope('business')
                ->where('ad_account_id', $adAccountId)
                ->whereNotNull('campaign_id');

            // Apply date filter only if not 'maximum'
            if (!($dates['no_filter'] ?? false)) {
                $query->whereBetween('date_start', [$dates['start'], $dates['end']]);
            }

            $insights = $query->select(
                    'campaign_id',
                    DB::raw('SUM(impressions) as impressions'),
                    DB::raw('SUM(reach) as reach'),
                    DB::raw('SUM(clicks) as clicks'),
                    DB::raw('SUM(spend) as spend'),
                    DB::raw('SUM(conversions) as conversions')
                )
                ->groupBy('campaign_id')
                ->get()
                ->keyBy('campaign_id');

            return $campaigns->map(function ($campaign) use ($insights) {
                $insight = $insights->get($campaign->id);
                $impressions = $insight?->impressions ?? 0;
                $clicks = $insight?->clicks ?? 0;
                $spend = $insight?->spend ?? 0;

                return [
                    'id' => $campaign->meta_campaign_id,
                    'name' => $campaign->name,
                    'objective' => $campaign->objective,
                    'status' => $campaign->status,
                    'created_time' => $campaign->start_time,
                'spend' => (float) $spend,
                'impressions' => (int) $impressions,
                'clicks' => (int) $clicks,
                'ctr' => $impressions > 0 ? round(($clicks / $impressions) * 100, 2) : 0,
                    'cpc' => $clicks > 0 ? round($spend / $clicks, 2) : 0,
                    'conversions' => (int) ($insight?->conversions ?? 0),
                    'roas' => 0, // Not available in current schema
                ];
            })->sortByDesc('spend')->values()->toArray();
        });
    }

    /**
     * Get demographics breakdown from local database
     * OPTIMIZED: Results are cached for 10 minutes
     */
    public function getDemographics(string $adAccountId, string $datePreset): array
    {
        $cacheKey = "meta_demographics_{$adAccountId}_{$datePreset}";

        return Cache::remember($cacheKey, $this->cacheTTL, function () use ($adAccountId, $datePreset) {
            $dates = $this->getDateRange($datePreset);
            $noFilter = $dates['no_filter'] ?? false;

            // Age breakdown
            $ageQuery = MetaInsight::withoutGlobalScope('business')
            ->where('ad_account_id', $adAccountId)
            ->whereNotNull('age_range')
            ->select(
                'age_range',
                DB::raw('SUM(impressions) as impressions'),
                DB::raw('SUM(reach) as reach'),
                DB::raw('SUM(clicks) as clicks'),
                DB::raw('SUM(spend) as spend'),
                DB::raw('SUM(conversions) as conversions')
            )
            ->groupBy('age_range');

        if (!$noFilter && $dates['start'] && $dates['end']) {
            $ageQuery->whereBetween('date_start', [$dates['start'], $dates['end']]);
        }

        $ageRawData = $ageQuery->get();
        $totalAgeSpend = $ageRawData->sum('spend');

        $ageData = $ageRawData->map(function ($row) use ($totalAgeSpend) {
            $impressions = (int) ($row->impressions ?? 0);
            $clicks = (int) ($row->clicks ?? 0);
            $spend = (float) ($row->spend ?? 0);

            return [
                'age_range' => $row->age_range,
                'label' => $row->age_range, // Frontend expects 'label'
                'impressions' => $impressions,
                'reach' => (int) ($row->reach ?? 0),
                'clicks' => $clicks,
                'spend' => $spend,
                'percentage' => $totalAgeSpend > 0 ? round(($spend / $totalAgeSpend) * 100, 1) : 0,
                'ctr' => $impressions > 0 ? round(($clicks / $impressions) * 100, 2) : 0,
                'cpc' => $clicks > 0 ? round($spend / $clicks, 2) : 0,
                'conversions' => (int) ($row->conversions ?? 0),
            ];
        })->sortBy('age_range')->values()->toArray();

        // Gender breakdown
        $genderQuery = MetaInsight::withoutGlobalScope('business')
            ->where('ad_account_id', $adAccountId)
            ->whereNotNull('gender')
            ->select(
                'gender',
                DB::raw('SUM(impressions) as impressions'),
                DB::raw('SUM(reach) as reach'),
                DB::raw('SUM(clicks) as clicks'),
                DB::raw('SUM(spend) as spend'),
                DB::raw('SUM(conversions) as conversions')
            )
            ->groupBy('gender');

        if (!$noFilter && $dates['start'] && $dates['end']) {
            $genderQuery->whereBetween('date_start', [$dates['start'], $dates['end']]);
        }

        $genderRawData = $genderQuery->get();
        $totalGenderSpend = $genderRawData->sum('spend');

        $genderData = $genderRawData->map(function ($row) use ($totalGenderSpend) {
            $impressions = (int) ($row->impressions ?? 0);
            $clicks = (int) ($row->clicks ?? 0);
            $spend = (float) ($row->spend ?? 0);

            return [
                'gender' => $row->gender,
                'label' => $row->gender, // Raw value for Vue template comparison (male/female)
                'impressions' => $impressions,
                'reach' => (int) ($row->reach ?? 0),
                'clicks' => $clicks,
                'spend' => $spend,
                'percentage' => $totalGenderSpend > 0 ? round(($spend / $totalGenderSpend) * 100, 1) : 0,
                'ctr' => $impressions > 0 ? round(($clicks / $impressions) * 100, 2) : 0,
                'cpc' => $clicks > 0 ? round($spend / $clicks, 2) : 0,
                'conversions' => (int) ($row->conversions ?? 0),
            ];
        })->values()->toArray();

            $hasData = !empty($ageData) || !empty($genderData);

            return [
                'age' => $ageData,
                'gender' => $genderData,
                'message' => $hasData ? null : 'Demografik ma\'lumotlar hozircha mavjud emas',
            ];
        });
    }

    /**
     * Get placements breakdown from local database
     * OPTIMIZED: Results are cached for 10 minutes
     */
    public function getPlacements(string $adAccountId, string $datePreset): array
    {
        $cacheKey = "meta_placements_{$adAccountId}_{$datePreset}";

        return Cache::remember($cacheKey, $this->cacheTTL, function () use ($adAccountId, $datePreset) {
            $dates = $this->getDateRange($datePreset);
            $noFilter = $dates['no_filter'] ?? false;

            // Platform breakdown (Facebook, Instagram, etc.)
            $platformQuery = MetaInsight::withoutGlobalScope('business')
            ->where('ad_account_id', $adAccountId)
            ->whereNotNull('publisher_platform')
            ->select(
                'publisher_platform',
                DB::raw('SUM(impressions) as impressions'),
                DB::raw('SUM(reach) as reach'),
                DB::raw('SUM(clicks) as clicks'),
                DB::raw('SUM(spend) as spend'),
                DB::raw('SUM(conversions) as conversions')
            )
            ->groupBy('publisher_platform');

        if (!$noFilter && $dates['start'] && $dates['end']) {
            $platformQuery->whereBetween('date_start', [$dates['start'], $dates['end']]);
        }

        $platformRawData = $platformQuery->get();
        $totalPlatformSpend = $platformRawData->sum('spend');

        $platformData = $platformRawData->map(function ($row) use ($totalPlatformSpend) {
            $impressions = (int) ($row->impressions ?? 0);
            $clicks = (int) ($row->clicks ?? 0);
            $spend = (float) ($row->spend ?? 0);

            return [
                'platform' => $row->publisher_platform,
                'label' => match ($row->publisher_platform) {
                    'facebook' => 'Facebook',
                    'instagram' => 'Instagram',
                    'messenger' => 'Messenger',
                    'audience_network' => 'Audience Network',
                    default => ucfirst($row->publisher_platform ?? 'Noma\'lum'),
                },
                'impressions' => $impressions,
                'reach' => (int) ($row->reach ?? 0),
                'clicks' => $clicks,
                'spend' => $spend,
                'percentage' => $totalPlatformSpend > 0 ? round(($spend / $totalPlatformSpend) * 100, 1) : 0,
                'ctr' => $impressions > 0 ? round(($clicks / $impressions) * 100, 2) : 0,
                'cpc' => $clicks > 0 ? round($spend / $clicks, 2) : 0,
                'conversions' => (int) ($row->conversions ?? 0),
            ];
        })->sortByDesc('spend')->values()->toArray();

        // Position breakdown (Feed, Stories, Reels, etc.)
        $positionQuery = MetaInsight::withoutGlobalScope('business')
            ->where('ad_account_id', $adAccountId)
            ->whereNotNull('platform_position')
            ->select(
                'platform_position',
                'publisher_platform',
                DB::raw('SUM(impressions) as impressions'),
                DB::raw('SUM(reach) as reach'),
                DB::raw('SUM(clicks) as clicks'),
                DB::raw('SUM(spend) as spend'),
                DB::raw('SUM(conversions) as conversions')
            )
            ->groupBy('platform_position', 'publisher_platform');

        if (!$noFilter && $dates['start'] && $dates['end']) {
            $positionQuery->whereBetween('date_start', [$dates['start'], $dates['end']]);
        }

        $positionRawData = $positionQuery->get();
        $totalPositionSpend = $positionRawData->sum('spend');

        $positionData = $positionRawData->map(function ($row) use ($totalPositionSpend) {
            $impressions = (int) ($row->impressions ?? 0);
            $clicks = (int) ($row->clicks ?? 0);
            $spend = (float) ($row->spend ?? 0);

            return [
                'position' => $row->platform_position,
                'platform' => $row->publisher_platform,
                'label' => match ($row->platform_position) {
                    'feed' => 'Feed',
                    'story' => 'Stories',
                    'reels' => 'Reels',
                    'explore' => 'Explore',
                    'search' => 'Search',
                    'instream_video' => 'In-Stream Video',
                    'right_hand_column' => 'Right Column',
                    'marketplace' => 'Marketplace',
                    'an_classic' => 'Audience Network Classic',
                    default => ucfirst(str_replace('_', ' ', $row->platform_position ?? 'Noma\'lum')),
                },
                'impressions' => $impressions,
                'reach' => (int) ($row->reach ?? 0),
                'clicks' => $clicks,
                'spend' => $spend,
                'percentage' => $totalPositionSpend > 0 ? round(($spend / $totalPositionSpend) * 100, 1) : 0,
                'ctr' => $impressions > 0 ? round(($clicks / $impressions) * 100, 2) : 0,
                'cpc' => $clicks > 0 ? round($spend / $clicks, 2) : 0,
                'conversions' => (int) ($row->conversions ?? 0),
            ];
        })->sortByDesc('spend')->values()->toArray();

            $hasData = !empty($platformData) || !empty($positionData);

            return [
                'platforms' => $platformData,
                'positions' => $positionData,
                'message' => $hasData ? null : 'Joylashuv ma\'lumotlari hozircha mavjud emas',
            ];
        });
    }

    /**
     * Get daily trend from local database
     * OPTIMIZED: Results are cached for 10 minutes
     */
    public function getTrend(string $adAccountId, int $days = 30): array
    {
        $cacheKey = "meta_trend_{$adAccountId}_{$days}";

        return Cache::remember($cacheKey, $this->cacheTTL, function () use ($adAccountId, $days) {
            $startDate = Carbon::today()->subDays($days);
            $endDate = Carbon::today();

            $trend = MetaInsight::withoutGlobalScope('business')
            ->where('ad_account_id', $adAccountId)
            ->whereBetween('date_start', [$startDate, $endDate])
            ->select(
                'date_start',
                DB::raw('SUM(spend) as spend'),
                DB::raw('SUM(impressions) as impressions'),
                DB::raw('SUM(clicks) as clicks'),
                DB::raw('SUM(conversions) as conversions')
            )
            ->groupBy('date_start')
            ->orderBy('date_start')
            ->get();

            return $trend->map(function ($row) {
                $impressions = $row->impressions ?? 0;
                $clicks = $row->clicks ?? 0;
                $date = $row->date_start instanceof Carbon ? $row->date_start->format('Y-m-d') : (string) $row->date_start;

                return [
                    'date' => $date,
                    'spend' => (float) $row->spend,
                    'impressions' => (int) $impressions,
                    'clicks' => (int) $clicks,
                    'ctr' => $impressions > 0 ? round(($clicks / $impressions) * 100, 2) : 0,
                    'conversions' => (int) $row->conversions,
                ];
            })->values()->toArray();
        });
    }

    /**
     * Get aggregated insights for a date range
     * IMPORTANT: Only use account-level insights to avoid duplicates
     */
    private function getAggregatedInsights(string $adAccountId, ?Carbon $startDate, ?Carbon $endDate, bool $noFilter = false): array
    {
        $query = MetaInsight::withoutGlobalScope('business')
            ->where('ad_account_id', $adAccountId)
            ->where('object_type', 'account')
            ->whereNull('age_range')
            ->whereNull('gender')
            ->whereNull('publisher_platform')
            ->whereNull('platform_position');

        // Apply date filter only if not 'maximum'
        if (!$noFilter && $startDate && $endDate) {
            $query->whereBetween('date_start', [$startDate, $endDate]);
        }

        $data = $query->select(
                DB::raw('SUM(impressions) as impressions'),
                DB::raw('SUM(reach) as reach'),
                DB::raw('SUM(clicks) as clicks'),
                DB::raw('SUM(spend) as spend'),
                DB::raw('SUM(conversions) as conversions')
            )
            ->first();

        $impressions = (int) ($data->impressions ?? 0);
        $clicks = (int) ($data->clicks ?? 0);
        $spend = (float) ($data->spend ?? 0);
        $reach = (int) ($data->reach ?? 0);
        $conversions = (int) ($data->conversions ?? 0);

        return [
            'impressions' => $impressions,
            'reach' => $reach,
            'clicks' => $clicks,
            'unique_clicks' => 0, // Not available in current schema
            'spend' => $spend,
            'ctr' => $impressions > 0 ? round(($clicks / $impressions) * 100, 2) : 0,
            'cpc' => $clicks > 0 ? round($spend / $clicks, 2) : 0,
            'cpm' => $impressions > 0 ? round(($spend / $impressions) * 1000, 2) : 0,
            'frequency' => $reach > 0 ? round($impressions / $reach, 2) : 0,
            'conversions' => $conversions,
            'conversion_value' => 0, // Not available in current schema
            'cost_per_conversion' => $conversions > 0 ? round($spend / $conversions, 2) : 0,
            'roas' => 0, // Not available in current schema
        ];
    }

    /**
     * Calculate percentage change between current and previous periods
     */
    private function calculateChange(array $current, array $previous): array
    {
        $metrics = ['spend', 'impressions', 'reach', 'clicks', 'ctr', 'cpc', 'cpm', 'conversions'];
        $changes = [];

        foreach ($metrics as $metric) {
            $currentVal = $current[$metric] ?? 0;
            $previousVal = $previous[$metric] ?? 0;

            if ($previousVal > 0) {
                $changes[$metric] = round((($currentVal - $previousVal) / $previousVal) * 100, 1);
            } else {
                $changes[$metric] = $currentVal > 0 ? 100 : 0;
            }
        }

        return $changes;
    }

    /**
     * Get date range based on preset
     */
    private function getDateRange(string $preset): array
    {
        $end = Carbon::today();

        // For 'maximum', return null to indicate no date filtering
        if ($preset === 'maximum') {
            return ['start' => null, 'end' => null, 'no_filter' => true];
        }

        $start = match ($preset) {
            'last_7d' => Carbon::today()->subDays(7),
            'last_14d' => Carbon::today()->subDays(14),
            'last_30d' => Carbon::today()->subDays(30),
            'last_90d' => Carbon::today()->subDays(90),
            'last_365d' => Carbon::today()->subDays(365),
            default => Carbon::today()->subDays(30),
        };

        return ['start' => $start, 'end' => $end, 'no_filter' => false];
    }

    /**
     * Get previous date range for comparison
     */
    private function getPreviousDateRange(string $preset): array
    {
        $days = match ($preset) {
            'last_7d' => 7,
            'last_14d' => 14,
            'last_30d' => 30,
            'last_90d' => 90,
            'last_365d' => 365,
            default => 30,
        };

        $end = Carbon::today()->subDays($days);
        $start = Carbon::today()->subDays($days * 2);

        return ['start' => $start, 'end' => $end];
    }

    /**
     * Check if we have synced data for an account
     */
    public function hasSyncedData(string $adAccountId): bool
    {
        return MetaInsight::withoutGlobalScope('business')->where('ad_account_id', $adAccountId)->exists();
    }

    /**
     * Get sync status information
     */
    public function getSyncStatus(string $adAccountId): array
    {
        $account = MetaAdAccount::withoutGlobalScope('business')->find($adAccountId);

        if (!$account) {
            return ['synced' => false];
        }

        $oldestInsight = MetaInsight::withoutGlobalScope('business')
            ->where('ad_account_id', $adAccountId)
            ->orderBy('date_start', 'asc')
            ->first();

        $newestInsight = MetaInsight::withoutGlobalScope('business')
            ->where('ad_account_id', $adAccountId)
            ->orderBy('date_start', 'desc')
            ->first();

        $insightCount = MetaInsight::withoutGlobalScope('business')->where('ad_account_id', $adAccountId)->count();
        $campaignCount = MetaCampaign::withoutGlobalScope('business')->where('ad_account_id', $adAccountId)->count();

        return [
            'synced' => $insightCount > 0,
            'last_sync' => $account->last_sync_at?->format('Y-m-d H:i:s'),
            'date_range' => [
                'from' => $oldestInsight?->date_start,
                'to' => $newestInsight?->date_start,
            ],
            'insights_count' => $insightCount,
            'campaigns_count' => $campaignCount,
        ];
    }

    /**
     * Generate AI-ready summary data
     */
    public function getAISummary(string $adAccountId, string $datePreset = 'last_30d'): array
    {
        $overview = $this->getOverview($adAccountId, $datePreset);
        $campaigns = $this->getCampaigns($adAccountId, $datePreset);
        $demographics = $this->getDemographics($adAccountId, $datePreset);
        $placements = $this->getPlacements($adAccountId, $datePreset);

        return [
            'overview' => $overview['current'],
            'change' => $overview['change'],
            'top_campaigns' => array_slice($campaigns, 0, 5),
            'worst_campaigns' => array_slice(
                array_filter($campaigns, fn($c) => $c['spend'] > 0 && $c['ctr'] < 1),
                0,
                3
            ),
            'demographics' => $demographics,
            'placements' => $placements,
            'active_campaigns' => count(array_filter($campaigns, fn($c) => $c['status'] === 'ACTIVE')),
            'total_campaigns' => count($campaigns),
        ];
    }

    /**
     * Get analytics breakdown by campaign objectives (leads, messages, sales, etc.)
     */
    public function getObjectivesAnalytics(string $adAccountId, string $datePreset = 'last_30d'): array
    {
        $cacheKey = "meta_objectives_{$adAccountId}_{$datePreset}";

        return Cache::remember($cacheKey, $this->cacheTTL, function () use ($adAccountId) {
            // Get aggregated stats by objective from campaigns
            $objectiveStats = MetaCampaign::withoutGlobalScope('business')
                ->where('ad_account_id', $adAccountId)
                ->select(
                    'objective',
                    DB::raw('COUNT(*) as campaigns_count'),
                    DB::raw('SUM(total_spend) as total_spend'),
                    DB::raw('SUM(total_leads) as total_leads'),
                    DB::raw('SUM(total_messages) as total_messages'),
                    DB::raw('SUM(total_purchases) as total_purchases'),
                    DB::raw('SUM(total_link_clicks) as total_link_clicks'),
                    DB::raw('SUM(total_video_views) as total_video_views'),
                    DB::raw('SUM(total_impressions) as total_impressions'),
                    DB::raw('SUM(total_clicks) as total_clicks'),
                    DB::raw('SUM(total_conversions) as total_conversions')
                )
                ->groupBy('objective')
                ->get();

            $result = [];

            // Process leads objectives
            $leadObjectives = ['OUTCOME_LEADS', 'LEAD_GENERATION'];
            $leadStats = $objectiveStats->filter(fn($s) => in_array($s->objective, $leadObjectives));
            if ($leadStats->isNotEmpty()) {
                $totalLeads = $leadStats->sum('total_leads');
                $totalSpend = $leadStats->sum('total_spend');
                $result['leads'] = [
                    'label' => 'Lidlar',
                    'icon' => 'users',
                    'color' => 'blue',
                    'total' => (int) $totalLeads,
                    'spend' => (float) $totalSpend,
                    'cost_per' => $totalLeads > 0 ? round($totalSpend / $totalLeads, 2) : 0,
                    'campaigns' => (int) $leadStats->sum('campaigns_count'),
                ];
            }

            // Process messages objectives
            $messageObjectives = ['MESSAGES'];
            $messageStats = $objectiveStats->filter(fn($s) => in_array($s->objective, $messageObjectives));
            if ($messageStats->isNotEmpty()) {
                $totalMessages = $messageStats->sum('total_messages');
                $totalSpend = $messageStats->sum('total_spend');
                $result['messages'] = [
                    'label' => 'Xabarlar',
                    'icon' => 'chat',
                    'color' => 'purple',
                    'total' => (int) $totalMessages,
                    'spend' => (float) $totalSpend,
                    'cost_per' => $totalMessages > 0 ? round($totalSpend / $totalMessages, 2) : 0,
                    'campaigns' => (int) $messageStats->sum('campaigns_count'),
                ];
            }

            // Process sales/conversion objectives
            $salesObjectives = ['OUTCOME_SALES', 'CONVERSIONS', 'PRODUCT_CATALOG_SALES'];
            $salesStats = $objectiveStats->filter(fn($s) => in_array($s->objective, $salesObjectives));
            if ($salesStats->isNotEmpty()) {
                $totalPurchases = $salesStats->sum('total_purchases') ?: $salesStats->sum('total_conversions');
                $totalSpend = $salesStats->sum('total_spend');
                $result['sales'] = [
                    'label' => 'Sotuvlar',
                    'icon' => 'shopping-cart',
                    'color' => 'green',
                    'total' => (int) $totalPurchases,
                    'spend' => (float) $totalSpend,
                    'cost_per' => $totalPurchases > 0 ? round($totalSpend / $totalPurchases, 2) : 0,
                    'campaigns' => (int) $salesStats->sum('campaigns_count'),
                ];
            }

            // Process traffic objectives
            $trafficObjectives = ['OUTCOME_TRAFFIC', 'LINK_CLICKS'];
            $trafficStats = $objectiveStats->filter(fn($s) => in_array($s->objective, $trafficObjectives));
            if ($trafficStats->isNotEmpty()) {
                $totalClicks = $trafficStats->sum('total_link_clicks');
                $totalSpend = $trafficStats->sum('total_spend');
                $result['traffic'] = [
                    'label' => 'Trafik',
                    'icon' => 'cursor-click',
                    'color' => 'orange',
                    'total' => (int) $totalClicks,
                    'spend' => (float) $totalSpend,
                    'cost_per' => $totalClicks > 0 ? round($totalSpend / $totalClicks, 2) : 0,
                    'campaigns' => (int) $trafficStats->sum('campaigns_count'),
                ];
            }

            // Process engagement objectives
            $engagementObjectives = ['OUTCOME_ENGAGEMENT', 'POST_ENGAGEMENT', 'PAGE_LIKES'];
            $engagementStats = $objectiveStats->filter(fn($s) => in_array($s->objective, $engagementObjectives));
            if ($engagementStats->isNotEmpty()) {
                $totalClicks = $engagementStats->sum('total_clicks');
                $totalSpend = $engagementStats->sum('total_spend');
                $result['engagement'] = [
                    'label' => 'Engagement',
                    'icon' => 'heart',
                    'color' => 'pink',
                    'total' => (int) $totalClicks,
                    'spend' => (float) $totalSpend,
                    'cost_per' => $totalClicks > 0 ? round($totalSpend / $totalClicks, 2) : 0,
                    'campaigns' => (int) $engagementStats->sum('campaigns_count'),
                ];
            }

            // Process video views objectives
            $videoObjectives = ['VIDEO_VIEWS'];
            $videoStats = $objectiveStats->filter(fn($s) => in_array($s->objective, $videoObjectives));
            if ($videoStats->isNotEmpty()) {
                $totalViews = $videoStats->sum('total_video_views');
                $totalSpend = $videoStats->sum('total_spend');
                $result['video'] = [
                    'label' => 'Video ko\'rishlar',
                    'icon' => 'play',
                    'color' => 'red',
                    'total' => (int) $totalViews,
                    'spend' => (float) $totalSpend,
                    'cost_per' => $totalViews > 0 ? round($totalSpend / $totalViews, 4) : 0,
                    'campaigns' => (int) $videoStats->sum('campaigns_count'),
                ];
            }

            return $result;
        });
    }

    /**
     * Get comprehensive audience analytics with performance insights
     */
    public function getAudienceAnalytics(string $adAccountId, string $datePreset = 'last_30d'): array
    {
        $cacheKey = "meta_audience_{$adAccountId}_{$datePreset}";

        return Cache::remember($cacheKey, $this->cacheTTL, function () use ($adAccountId, $datePreset) {
            $dateRange = $this->getDateRange($datePreset);
            $startDate = $dateRange['start'];
            $endDate = $dateRange['end'];
            $noFilter = $dateRange['no_filter'];

            // Get age performance data
            $ageQuery = MetaInsight::withoutGlobalScope('business')
                ->where('ad_account_id', $adAccountId)
                ->whereNotNull('age_range')
                ->whereNull('gender')
                ->whereNull('publisher_platform');

            if (!$noFilter && $startDate && $endDate) {
                $ageQuery->whereBetween('date_start', [$startDate, $endDate]);
            }

            $ageData = $ageQuery->select(
                    'age_range',
                    DB::raw('SUM(impressions) as impressions'),
                    DB::raw('SUM(reach) as reach'),
                    DB::raw('SUM(clicks) as clicks'),
                    DB::raw('SUM(spend) as spend'),
                    DB::raw('CASE WHEN SUM(impressions) > 0 THEN (SUM(clicks) / SUM(impressions)) * 100 ELSE 0 END as ctr'),
                    DB::raw('CASE WHEN SUM(clicks) > 0 THEN SUM(spend) / SUM(clicks) ELSE 0 END as cpc')
                )
                ->groupBy('age_range')
                ->orderByDesc(DB::raw('SUM(spend)'))
                ->get();

            // Get gender performance data
            $genderQuery = MetaInsight::withoutGlobalScope('business')
                ->where('ad_account_id', $adAccountId)
                ->whereNotNull('gender')
                ->whereNull('age_range')
                ->whereNull('publisher_platform');

            if (!$noFilter && $startDate && $endDate) {
                $genderQuery->whereBetween('date_start', [$startDate, $endDate]);
            }

            $genderData = $genderQuery->select(
                    'gender',
                    DB::raw('SUM(impressions) as impressions'),
                    DB::raw('SUM(reach) as reach'),
                    DB::raw('SUM(clicks) as clicks'),
                    DB::raw('SUM(spend) as spend'),
                    DB::raw('CASE WHEN SUM(impressions) > 0 THEN (SUM(clicks) / SUM(impressions)) * 100 ELSE 0 END as ctr'),
                    DB::raw('CASE WHEN SUM(clicks) > 0 THEN SUM(spend) / SUM(clicks) ELSE 0 END as cpc')
                )
                ->groupBy('gender')
                ->orderByDesc(DB::raw('SUM(spend)'))
                ->get();

            // Get platform performance data
            $platformQuery = MetaInsight::withoutGlobalScope('business')
                ->where('ad_account_id', $adAccountId)
                ->whereNotNull('publisher_platform')
                ->whereNull('age_range')
                ->whereNull('gender')
                ->whereNull('platform_position');

            if (!$noFilter && $startDate && $endDate) {
                $platformQuery->whereBetween('date_start', [$startDate, $endDate]);
            }

            $platformData = $platformQuery->select(
                    'publisher_platform',
                    DB::raw('SUM(impressions) as impressions'),
                    DB::raw('SUM(reach) as reach'),
                    DB::raw('SUM(clicks) as clicks'),
                    DB::raw('SUM(spend) as spend'),
                    DB::raw('CASE WHEN SUM(impressions) > 0 THEN (SUM(clicks) / SUM(impressions)) * 100 ELSE 0 END as ctr'),
                    DB::raw('CASE WHEN SUM(clicks) > 0 THEN SUM(spend) / SUM(clicks) ELSE 0 END as cpc')
                )
                ->groupBy('publisher_platform')
                ->orderByDesc(DB::raw('SUM(spend)'))
                ->get();

            // Calculate totals for percentage calculations
            $totalSpend = $ageData->sum('spend');
            $totalClicks = $ageData->sum('clicks');
            $totalImpressions = $ageData->sum('impressions');

            // Find best performers
            $bestAge = $ageData->sortByDesc('ctr')->first();
            $bestGender = $genderData->sortByDesc('ctr')->first();
            $bestPlatform = $platformData->sortByDesc('ctr')->first();

            // Cheapest CPC performers
            $cheapestAge = $ageData->filter(fn($a) => $a->clicks > 0)->sortBy('cpc')->first();
            $cheapestGender = $genderData->filter(fn($g) => $g->clicks > 0)->sortBy('cpc')->first();

            // Calculate average CTR for performance comparison
            $avgCtr = $ageData->avg('ctr') ?? 0;

            // Format age data with performance metrics
            $agePerformance = $ageData->map(function ($item) use ($totalSpend, $bestAge, $avgCtr) {
                $isBest = $bestAge && $item->age_range === $bestAge->age_range;
                return [
                    'label' => $item->age_range,
                    'impressions' => (int) $item->impressions,
                    'reach' => (int) $item->reach,
                    'clicks' => (int) $item->clicks,
                    'spend' => round((float) $item->spend, 2),
                    'ctr' => round((float) $item->ctr, 2),
                    'cpc' => round((float) $item->cpc, 2),
                    'spend_percentage' => $totalSpend > 0 ? round(($item->spend / $totalSpend) * 100, 1) : 0,
                    'is_best' => $isBest,
                    'performance' => $this->getPerformanceLevel($item->ctr, $avgCtr),
                ];
            })->values()->toArray();

            // Format gender data with performance metrics
            $genderPerformance = $genderData->map(function ($item) use ($totalSpend, $bestGender) {
                $isBest = $bestGender && $item->gender === $bestGender->gender;
                $label = match($item->gender) {
                    'male' => 'Erkaklar',
                    'female' => 'Ayollar',
                    default => 'Noma\'lum',
                };
                return [
                    'key' => $item->gender,
                    'label' => $label,
                    'impressions' => (int) $item->impressions,
                    'reach' => (int) $item->reach,
                    'clicks' => (int) $item->clicks,
                    'spend' => round((float) $item->spend, 2),
                    'ctr' => round((float) $item->ctr, 2),
                    'cpc' => round((float) $item->cpc, 2),
                    'spend_percentage' => $totalSpend > 0 ? round(($item->spend / $totalSpend) * 100, 1) : 0,
                    'is_best' => $isBest,
                ];
            })->values()->toArray();

            // Format platform data
            $platformPerformance = $platformData->map(function ($item) use ($bestPlatform) {
                $isBest = $bestPlatform && $item->publisher_platform === $bestPlatform->publisher_platform;
                $label = match($item->publisher_platform) {
                    'facebook' => 'Facebook',
                    'instagram' => 'Instagram',
                    'messenger' => 'Messenger',
                    'audience_network' => 'Audience Network',
                    default => ucfirst($item->publisher_platform ?? 'Boshqa'),
                };
                return [
                    'key' => $item->publisher_platform,
                    'label' => $label,
                    'impressions' => (int) $item->impressions,
                    'reach' => (int) $item->reach,
                    'clicks' => (int) $item->clicks,
                    'spend' => round((float) $item->spend, 2),
                    'ctr' => round((float) $item->ctr, 2),
                    'cpc' => round((float) $item->cpc, 2),
                    'is_best' => $isBest,
                ];
            })->values()->toArray();

            // Generate audience insights
            $insights = [];

            // Best age insight
            if ($bestAge && $bestAge->ctr > 0) {
                $insights[] = [
                    'type' => 'best_age',
                    'icon' => 'users',
                    'color' => 'blue',
                    'title' => 'Eng yaxshi yosh guruhi',
                    'value' => $bestAge->age_range,
                    'metric' => round($bestAge->ctr, 2) . '% CTR',
                    'description' => "Bu yosh guruhi eng yuqori CTR ko'rsatmoqda",
                ];
            }

            // Best gender insight
            if ($bestGender && $bestGender->ctr > 0) {
                $genderLabel = $bestGender->gender === 'male' ? 'Erkaklar' : ($bestGender->gender === 'female' ? 'Ayollar' : 'Noma\'lum');
                $insights[] = [
                    'type' => 'best_gender',
                    'icon' => 'user',
                    'color' => $bestGender->gender === 'male' ? 'blue' : 'pink',
                    'title' => 'Eng faol jins',
                    'value' => $genderLabel,
                    'metric' => round($bestGender->ctr, 2) . '% CTR',
                    'description' => "Bu jins ko'proq bosimlar qilmoqda",
                ];
            }

            // Best platform insight
            if ($bestPlatform && $bestPlatform->ctr > 0) {
                $insights[] = [
                    'type' => 'best_platform',
                    'icon' => 'device-mobile',
                    'color' => 'purple',
                    'title' => 'Eng samarali platforma',
                    'value' => ucfirst($bestPlatform->publisher_platform),
                    'metric' => round($bestPlatform->ctr, 2) . '% CTR',
                    'description' => "Bu platformada reklamalar yaxshiroq ishlaydi",
                ];
            }

            // Cheapest CPC insight
            if ($cheapestAge && $cheapestAge->cpc > 0) {
                $insights[] = [
                    'type' => 'cheapest_cpc',
                    'icon' => 'currency-dollar',
                    'color' => 'green',
                    'title' => 'Eng arzon klik',
                    'value' => $cheapestAge->age_range,
                    'metric' => '$' . round($cheapestAge->cpc, 2) . '/klik',
                    'description' => "Bu yosh guruhida klik narxi eng past",
                ];
            }

            // Ideal audience recommendation
            $idealAudience = [];
            if ($bestAge) {
                $idealAudience['age'] = $bestAge->age_range;
            }
            if ($bestGender) {
                $idealAudience['gender'] = $bestGender->gender === 'male' ? 'Erkaklar' : ($bestGender->gender === 'female' ? 'Ayollar' : null);
            }
            if ($bestPlatform) {
                $idealAudience['platform'] = ucfirst($bestPlatform->publisher_platform);
            }

            return [
                'age' => $agePerformance,
                'gender' => $genderPerformance,
                'platform' => $platformPerformance,
                'insights' => $insights,
                'ideal_audience' => $idealAudience,
                'summary' => [
                    'total_spend' => round($totalSpend, 2),
                    'total_clicks' => (int) $totalClicks,
                    'total_impressions' => (int) $totalImpressions,
                    'avg_ctr' => $totalImpressions > 0 ? round(($totalClicks / $totalImpressions) * 100, 2) : 0,
                    'avg_cpc' => $totalClicks > 0 ? round($totalSpend / $totalClicks, 2) : 0,
                ],
            ];
        });
    }

    /**
     * Get performance level based on CTR comparison
     */
    private function getPerformanceLevel(float $value, float $average): string
    {
        if ($average <= 0) return 'normal';

        $ratio = $value / $average;
        if ($ratio >= 1.3) return 'excellent';
        if ($ratio >= 1.1) return 'good';
        if ($ratio >= 0.9) return 'normal';
        if ($ratio >= 0.7) return 'below';
        return 'poor';
    }
}
