<?php

namespace App\Models;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GoogleAdsMetric extends Model
{
    use HasUuid;
    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'marketing_channel_id',
        'metric_date',
        'campaign_id',
        'campaign_name',
        'ad_group_id',
        'ad_group_name',
        'impressions',
        'clicks',
        'conversions',
        'cost',
        'avg_cpc',
        'avg_cpm',
        'avg_cpa',
        'quality_score',
        'ctr',
        'conversion_rate',
        'conversion_value',
        'roas',
        'video_views',
        'video_quartile_25',
        'video_quartile_50',
        'video_quartile_75',
        'video_quartile_100',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'metric_date' => 'date',
        'quality_score' => 'decimal:1',
        'ctr' => 'decimal:2',
        'conversion_rate' => 'decimal:2',
        'roas' => 'decimal:2',
    ];

    /**
     * Get the marketing channel for this metric.
     */
    public function marketingChannel(): BelongsTo
    {
        return $this->belongsTo(MarketingChannel::class);
    }

    /**
     * Get cost in currency format (convert from kopeks).
     */
    public function getCostInCurrencyAttribute(): float
    {
        return $this->cost / 100;
    }

    /**
     * Get average CPC in currency format.
     */
    public function getAvgCpcInCurrencyAttribute(): float
    {
        return $this->avg_cpc / 100;
    }

    /**
     * Get average CPM in currency format.
     */
    public function getAvgCpmInCurrencyAttribute(): float
    {
        return $this->avg_cpm / 100;
    }

    /**
     * Get average CPA in currency format.
     */
    public function getAvgCpaInCurrencyAttribute(): float
    {
        return $this->avg_cpa / 100;
    }

    /**
     * Get conversion value in currency format.
     */
    public function getConversionValueInCurrencyAttribute(): float
    {
        return $this->conversion_value / 100;
    }

    /**
     * Calculate actual CTR.
     */
    public function calculateCtr(): float
    {
        if ($this->impressions === 0) {
            return 0;
        }

        return round(($this->clicks / $this->impressions) * 100, 2);
    }

    /**
     * Calculate actual conversion rate.
     */
    public function calculateConversionRate(): float
    {
        if ($this->clicks === 0) {
            return 0;
        }

        return round(($this->conversions / $this->clicks) * 100, 2);
    }

    /**
     * Calculate actual ROAS.
     */
    public function calculateRoas(): float
    {
        if ($this->cost === 0) {
            return 0;
        }

        return round($this->conversion_value / $this->cost, 2);
    }
}
