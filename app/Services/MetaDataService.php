<?php

namespace App\Services;

use App\Models\MetaAdAccount;
use App\Models\MetaCampaign;
use App\Models\MetaInsight;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class MetaDataService
{
    /**
     * Get overview stats from local database
     */
    public function getOverview(string $adAccountId, string $datePreset): array
    {
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
     */
    public function getCampaigns(string $adAccountId, string $datePreset): array
    {
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
    }

    /**
     * Get demographics breakdown from local database
     */
    public function getDemographics(string $adAccountId, string $datePreset): array
    {
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
    }

    /**
     * Get placements breakdown from local database
     */
    public function getPlacements(string $adAccountId, string $datePreset): array
    {
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
    }

    /**
     * Get daily trend from local database
     */
    public function getTrend(string $adAccountId, int $days = 30): array
    {
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
    }

    /**
     * Get aggregated insights for a date range
     */
    private function getAggregatedInsights(string $adAccountId, ?Carbon $startDate, ?Carbon $endDate, bool $noFilter = false): array
    {
        $query = MetaInsight::withoutGlobalScope('business')->where('ad_account_id', $adAccountId);

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
}
