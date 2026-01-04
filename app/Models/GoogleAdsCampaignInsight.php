<?php

namespace App\Models;

use App\Traits\BelongsToBusiness;
use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GoogleAdsCampaignInsight extends Model
{
    use BelongsToBusiness, HasUuid;

    protected $fillable = [
        'campaign_id',
        'business_id',
        'date',
        // Core Metrics
        'cost',
        'impressions',
        'clicks',
        'ctr',
        'cpc',
        'cpm',
        // Conversions
        'conversions',
        'conversion_rate',
        'conversion_value',
        'cost_per_conversion',
        'roas',
        // Quality Metrics
        'avg_quality_score',
        'search_impression_share',
        // Video Metrics
        'video_views',
        'video_view_rate',
        // Additional data
        'actions',
        'breakdown_data',
    ];

    protected $casts = [
        'date' => 'date',
        'cost' => 'decimal:2',
        'ctr' => 'decimal:4',
        'cpc' => 'decimal:4',
        'cpm' => 'decimal:4',
        'conversion_rate' => 'decimal:4',
        'conversion_value' => 'decimal:2',
        'cost_per_conversion' => 'decimal:4',
        'roas' => 'decimal:4',
        'avg_quality_score' => 'decimal:2',
        'search_impression_share' => 'decimal:4',
        'video_view_rate' => 'decimal:4',
        'impressions' => 'integer',
        'clicks' => 'integer',
        'conversions' => 'integer',
        'video_views' => 'integer',
        'actions' => 'array',
        'breakdown_data' => 'array',
    ];

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(GoogleAdsCampaign::class, 'campaign_id');
    }

    public function scopeForDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('date', [$startDate, $endDate]);
    }

    public function scopeForCampaign($query, string $campaignId)
    {
        return $query->where('campaign_id', $campaignId);
    }

    /**
     * Get formatted cost in currency
     */
    public function getFormattedCostAttribute(): string
    {
        return number_format($this->cost, 0, ',', ' ') . ' so\'m';
    }

    /**
     * Get formatted CTR
     */
    public function getFormattedCtrAttribute(): string
    {
        return number_format($this->ctr, 2) . '%';
    }

    /**
     * Get formatted CPC
     */
    public function getFormattedCpcAttribute(): string
    {
        return number_format($this->cpc, 0, ',', ' ') . ' so\'m';
    }
}
