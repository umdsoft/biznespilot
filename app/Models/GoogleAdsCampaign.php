<?php

namespace App\Models;

use App\Traits\BelongsToBusiness;
use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

class GoogleAdsCampaign extends Model
{
    use BelongsToBusiness, HasUuid;

    protected $fillable = [
        'ad_integration_id',
        'business_id',
        'google_campaign_id',
        'name',
        'advertising_channel_type',
        'status',
        'serving_status',
        'bidding_strategy_type',
        'daily_budget',
        'lifetime_budget',
        'budget_delivery_method',
        'start_date',
        'end_date',
        'targeting_settings',
        'geo_targets',
        'device_targets',
        'language_targets',
        // Aggregated metrics
        'total_cost',
        'total_impressions',
        'total_clicks',
        'total_conversions',
        'total_conversion_value',
        'avg_cpc',
        'avg_cpm',
        'avg_ctr',
        'avg_conversion_rate',
        'roas',
        // Sync info
        'last_synced_at',
        'sync_status',
        'metadata',
    ];

    protected $casts = [
        'daily_budget' => 'decimal:2',
        'lifetime_budget' => 'decimal:2',
        'total_cost' => 'decimal:2',
        'total_conversion_value' => 'decimal:2',
        'avg_cpc' => 'decimal:4',
        'avg_cpm' => 'decimal:4',
        'avg_ctr' => 'decimal:4',
        'avg_conversion_rate' => 'decimal:4',
        'roas' => 'decimal:4',
        'total_impressions' => 'integer',
        'total_clicks' => 'integer',
        'total_conversions' => 'integer',
        'targeting_settings' => 'array',
        'geo_targets' => 'array',
        'device_targets' => 'array',
        'language_targets' => 'array',
        'metadata' => 'array',
        'start_date' => 'date',
        'end_date' => 'date',
        'last_synced_at' => 'datetime',
    ];

    public function adIntegration(): BelongsTo
    {
        return $this->belongsTo(AdIntegration::class, 'ad_integration_id');
    }

    public function adGroups(): HasMany
    {
        return $this->hasMany(GoogleAdsAdGroup::class, 'campaign_id');
    }

    public function insights(): HasMany
    {
        return $this->hasMany(GoogleAdsCampaignInsight::class, 'campaign_id');
    }

    public function keywords(): HasManyThrough
    {
        return $this->hasManyThrough(
            GoogleAdsKeyword::class,
            GoogleAdsAdGroup::class,
            'campaign_id',
            'ad_group_id'
        );
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'ENABLED');
    }

    public function scopeByChannelType($query, string $type)
    {
        return $query->where('advertising_channel_type', $type);
    }

    public function scopeByStatus($query, $status)
    {
        if (is_array($status)) {
            return $query->whereIn('status', $status);
        }
        return $query->where('status', $status);
    }

    public function getChannelTypeLabelAttribute(): string
    {
        return match ($this->advertising_channel_type) {
            'SEARCH' => 'Qidiruv',
            'DISPLAY' => 'Display',
            'VIDEO' => 'Video',
            'SHOPPING' => 'Shopping',
            'SMART' => 'Smart',
            'PERFORMANCE_MAX' => 'Performance Max',
            'LOCAL' => 'Mahalliy',
            'DISCOVERY' => 'Discovery',
            default => $this->advertising_channel_type ?? 'Noma\'lum',
        };
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'ENABLED' => 'Faol',
            'PAUSED' => 'Pauza',
            'REMOVED' => 'O\'chirilgan',
            default => $this->status ?? 'Noma\'lum',
        };
    }

    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            'ENABLED' => 'green',
            'PAUSED' => 'yellow',
            'REMOVED' => 'red',
            default => 'gray',
        };
    }

    public function getServingStatusLabelAttribute(): string
    {
        return match ($this->serving_status) {
            'SERVING' => 'Ishlayapti',
            'NONE' => 'Yo\'q',
            'ENDED' => 'Tugagan',
            'PENDING' => 'Kutilmoqda',
            'SUSPENDED' => 'To\'xtatilgan',
            default => $this->serving_status ?? '-',
        };
    }

    /**
     * Update aggregated metrics from insights
     */
    public function updateAggregates(): void
    {
        $aggregates = $this->insights()
            ->selectRaw('
                SUM(cost) as total_cost,
                SUM(impressions) as total_impressions,
                SUM(clicks) as total_clicks,
                SUM(conversions) as total_conversions,
                SUM(conversion_value) as total_conversion_value,
                AVG(cpc) as avg_cpc,
                AVG(cpm) as avg_cpm,
                CASE WHEN SUM(impressions) > 0 THEN (SUM(clicks) / SUM(impressions)) * 100 ELSE 0 END as avg_ctr,
                CASE WHEN SUM(clicks) > 0 THEN (SUM(conversions) / SUM(clicks)) * 100 ELSE 0 END as avg_conversion_rate,
                CASE WHEN SUM(cost) > 0 THEN SUM(conversion_value) / SUM(cost) ELSE 0 END as roas
            ')
            ->first();

        $this->update([
            'total_cost' => $aggregates->total_cost ?? 0,
            'total_impressions' => $aggregates->total_impressions ?? 0,
            'total_clicks' => $aggregates->total_clicks ?? 0,
            'total_conversions' => $aggregates->total_conversions ?? 0,
            'total_conversion_value' => $aggregates->total_conversion_value ?? 0,
            'avg_cpc' => $aggregates->avg_cpc ?? 0,
            'avg_cpm' => $aggregates->avg_cpm ?? 0,
            'avg_ctr' => $aggregates->avg_ctr ?? 0,
            'avg_conversion_rate' => $aggregates->avg_conversion_rate ?? 0,
            'roas' => $aggregates->roas ?? 0,
        ]);
    }
}
