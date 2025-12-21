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
    public function getOverview(int $adAccountId, string $datePreset): array
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
     * Get campaigns with insights from local database
     */
    public function getCampaigns(int $adAccountId, string $datePreset): array
    {
        $dates = $this->getDateRange($datePreset);

        $campaigns = MetaCampaign::where('ad_account_id', $adAccountId)
            ->get();

        $query = MetaInsight::where('ad_account_id', $adAccountId)
            ->where('object_type', 'campaign')
            ->whereNull('age_range')
            ->whereNull('gender')
            ->whereNull('publisher_platform');

        // Apply date filter only if not 'maximum'
        if (!($dates['no_filter'] ?? false)) {
            $query->whereBetween('date', [$dates['start'], $dates['end']]);
        }

        $insights = $query->select(
                'object_id',
                DB::raw('SUM(impressions) as impressions'),
                DB::raw('SUM(reach) as reach'),
                DB::raw('SUM(clicks) as clicks'),
                DB::raw('SUM(spend) as spend'),
                DB::raw('SUM(conversions) as conversions'),
                DB::raw('SUM(conversion_value) as conversion_value')
            )
            ->groupBy('object_id')
            ->get()
            ->keyBy('object_id');

        return $campaigns->map(function ($campaign) use ($insights) {
            $insight = $insights->get($campaign->meta_campaign_id);
            $impressions = $insight?->impressions ?? 0;
            $clicks = $insight?->clicks ?? 0;
            $spend = $insight?->spend ?? 0;

            return [
                'id' => $campaign->meta_campaign_id,
                'name' => $campaign->name,
                'objective' => $campaign->objective,
                'status' => $campaign->status,
                'created_time' => $campaign->created_time,
                'spend' => (float) $spend,
                'impressions' => (int) $impressions,
                'clicks' => (int) $clicks,
                'ctr' => $impressions > 0 ? round(($clicks / $impressions) * 100, 2) : 0,
                'cpc' => $clicks > 0 ? round($spend / $clicks, 2) : 0,
                'conversions' => (int) ($insight?->conversions ?? 0),
                'roas' => $spend > 0 ? round(($insight?->conversion_value ?? 0) / $spend, 2) : 0,
            ];
        })->sortByDesc('spend')->values()->toArray();
    }

    /**
     * Get demographics breakdown from local database
     */
    public function getDemographics(int $adAccountId, string $datePreset): array
    {
        $dates = $this->getDateRange($datePreset);
        $noFilter = $dates['no_filter'] ?? false;

        // Age breakdown
        $ageQuery = MetaInsight::where('ad_account_id', $adAccountId)
            ->whereNotNull('age_range');

        if (!$noFilter && $dates['start'] && $dates['end']) {
            $ageQuery->whereBetween('date', [$dates['start'], $dates['end']]);
        }

        $ageData = $ageQuery->select(
                'age_range',
                DB::raw('SUM(spend) as spend'),
                DB::raw('SUM(impressions) as impressions'),
                DB::raw('SUM(clicks) as clicks'),
                DB::raw('SUM(conversions) as conversions')
            )
            ->groupBy('age_range')
            ->orderBy('age_range')
            ->get();

        $totalAgeSpend = $ageData->sum('spend');

        $age = $ageData->map(function ($row) use ($totalAgeSpend) {
            return [
                'label' => $row->age_range,
                'spend' => (float) $row->spend,
                'impressions' => (int) $row->impressions,
                'clicks' => (int) $row->clicks,
                'conversions' => (int) $row->conversions,
                'percentage' => $totalAgeSpend > 0 ? round(($row->spend / $totalAgeSpend) * 100, 1) : 0,
            ];
        })->values()->toArray();

        // Gender breakdown
        $genderQuery = MetaInsight::where('ad_account_id', $adAccountId)
            ->whereNotNull('gender');

        if (!$noFilter && $dates['start'] && $dates['end']) {
            $genderQuery->whereBetween('date', [$dates['start'], $dates['end']]);
        }

        $genderData = $genderQuery->select(
                'gender',
                DB::raw('SUM(spend) as spend'),
                DB::raw('SUM(impressions) as impressions'),
                DB::raw('SUM(clicks) as clicks'),
                DB::raw('SUM(conversions) as conversions')
            )
            ->groupBy('gender')
            ->get();

        $totalGenderSpend = $genderData->sum('spend');

        $gender = $genderData->map(function ($row) use ($totalGenderSpend) {
            return [
                'label' => $row->gender === 'male' ? 'Erkak' : ($row->gender === 'female' ? 'Ayol' : $row->gender),
                'spend' => (float) $row->spend,
                'impressions' => (int) $row->impressions,
                'clicks' => (int) $row->clicks,
                'conversions' => (int) $row->conversions,
                'percentage' => $totalGenderSpend > 0 ? round(($row->spend / $totalGenderSpend) * 100, 1) : 0,
            ];
        })->values()->toArray();

        return [
            'age' => $age,
            'gender' => $gender,
        ];
    }

    /**
     * Get placements breakdown from local database
     */
    public function getPlacements(int $adAccountId, string $datePreset): array
    {
        $dates = $this->getDateRange($datePreset);
        $noFilter = $dates['no_filter'] ?? false;

        // Platform breakdown
        $platformQuery = MetaInsight::where('ad_account_id', $adAccountId)
            ->whereNotNull('publisher_platform');

        if (!$noFilter && $dates['start'] && $dates['end']) {
            $platformQuery->whereBetween('date', [$dates['start'], $dates['end']]);
        }

        $platformData = $platformQuery->select(
                'publisher_platform',
                DB::raw('SUM(spend) as spend'),
                DB::raw('SUM(impressions) as impressions'),
                DB::raw('SUM(clicks) as clicks')
            )
            ->groupBy('publisher_platform')
            ->get();

        $totalPlatformSpend = $platformData->sum('spend');

        $platforms = $platformData->map(function ($row) use ($totalPlatformSpend) {
            return [
                'label' => ucfirst($row->publisher_platform),
                'spend' => (float) $row->spend,
                'impressions' => (int) $row->impressions,
                'clicks' => (int) $row->clicks,
                'percentage' => $totalPlatformSpend > 0 ? round(($row->spend / $totalPlatformSpend) * 100, 1) : 0,
            ];
        })->sortByDesc('spend')->values()->toArray();

        // Position breakdown
        $positionQuery = MetaInsight::where('ad_account_id', $adAccountId)
            ->whereNotNull('platform_position');

        if (!$noFilter && $dates['start'] && $dates['end']) {
            $positionQuery->whereBetween('date', [$dates['start'], $dates['end']]);
        }

        $positionData = $positionQuery->select(
                'platform_position',
                DB::raw('SUM(spend) as spend'),
                DB::raw('SUM(impressions) as impressions'),
                DB::raw('SUM(clicks) as clicks')
            )
            ->groupBy('platform_position')
            ->get();

        $totalPositionSpend = $positionData->sum('spend');

        $positions = $positionData->map(function ($row) use ($totalPositionSpend) {
            return [
                'label' => str_replace('_', ' ', ucfirst($row->platform_position)),
                'spend' => (float) $row->spend,
                'impressions' => (int) $row->impressions,
                'clicks' => (int) $row->clicks,
                'percentage' => $totalPositionSpend > 0 ? round(($row->spend / $totalPositionSpend) * 100, 1) : 0,
            ];
        })->sortByDesc('spend')->values()->toArray();

        return [
            'platforms' => $platforms,
            'positions' => $positions,
        ];
    }

    /**
     * Get daily trend from local database
     */
    public function getTrend(int $adAccountId, int $days = 30): array
    {
        $startDate = Carbon::today()->subDays($days);
        $endDate = Carbon::today();

        $trend = MetaInsight::where('ad_account_id', $adAccountId)
            ->where('object_type', 'account')
            ->whereBetween('date', [$startDate, $endDate])
            ->whereNull('age_range')
            ->whereNull('gender')
            ->whereNull('publisher_platform')
            ->select(
                'date',
                DB::raw('SUM(spend) as spend'),
                DB::raw('SUM(impressions) as impressions'),
                DB::raw('SUM(clicks) as clicks'),
                DB::raw('SUM(conversions) as conversions')
            )
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        return $trend->map(function ($row) {
            $impressions = $row->impressions ?? 0;
            $clicks = $row->clicks ?? 0;
            $date = $row->date instanceof Carbon ? $row->date->format('Y-m-d') : (string) $row->date;

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
    private function getAggregatedInsights(int $adAccountId, ?Carbon $startDate, ?Carbon $endDate, bool $noFilter = false): array
    {
        $query = MetaInsight::where('ad_account_id', $adAccountId)
            ->where('object_type', 'account')
            ->whereNull('age_range')
            ->whereNull('gender')
            ->whereNull('publisher_platform');

        // Apply date filter only if not 'maximum'
        if (!$noFilter && $startDate && $endDate) {
            $query->whereBetween('date', [$startDate, $endDate]);
        }

        $data = $query->select(
                DB::raw('SUM(impressions) as impressions'),
                DB::raw('SUM(reach) as reach'),
                DB::raw('SUM(clicks) as clicks'),
                DB::raw('SUM(unique_clicks) as unique_clicks'),
                DB::raw('SUM(spend) as spend'),
                DB::raw('SUM(conversions) as conversions'),
                DB::raw('SUM(conversion_value) as conversion_value')
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
            'unique_clicks' => (int) ($data->unique_clicks ?? 0),
            'spend' => $spend,
            'ctr' => $impressions > 0 ? round(($clicks / $impressions) * 100, 2) : 0,
            'cpc' => $clicks > 0 ? round($spend / $clicks, 2) : 0,
            'cpm' => $impressions > 0 ? round(($spend / $impressions) * 1000, 2) : 0,
            'frequency' => $reach > 0 ? round($impressions / $reach, 2) : 0,
            'conversions' => $conversions,
            'conversion_value' => (float) ($data->conversion_value ?? 0),
            'cost_per_conversion' => $conversions > 0 ? round($spend / $conversions, 2) : 0,
            'roas' => $spend > 0 ? round(($data->conversion_value ?? 0) / $spend, 2) : 0,
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
    public function hasSyncedData(int $adAccountId): bool
    {
        return MetaInsight::where('ad_account_id', $adAccountId)->exists();
    }

    /**
     * Get sync status information
     */
    public function getSyncStatus(int $adAccountId): array
    {
        $account = MetaAdAccount::find($adAccountId);

        if (!$account) {
            return ['synced' => false];
        }

        $oldestInsight = MetaInsight::where('ad_account_id', $adAccountId)
            ->orderBy('date', 'asc')
            ->first();

        $newestInsight = MetaInsight::where('ad_account_id', $adAccountId)
            ->orderBy('date', 'desc')
            ->first();

        $insightCount = MetaInsight::where('ad_account_id', $adAccountId)->count();
        $campaignCount = MetaCampaign::where('ad_account_id', $adAccountId)->count();

        return [
            'synced' => $insightCount > 0,
            'last_sync' => $account->last_sync_at?->format('Y-m-d H:i:s'),
            'date_range' => [
                'from' => $oldestInsight?->date?->format('Y-m-d'),
                'to' => $newestInsight?->date?->format('Y-m-d'),
            ],
            'insights_count' => $insightCount,
            'campaigns_count' => $campaignCount,
        ];
    }

    /**
     * Generate AI-ready summary data
     */
    public function getAISummary(int $adAccountId, string $datePreset = 'last_30d'): array
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
