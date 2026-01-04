<?php

namespace App\Models;

use App\Traits\BelongsToBusiness;
use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class GoogleAdsAdGroup extends Model
{
    use BelongsToBusiness, HasUuid;

    protected $fillable = [
        'campaign_id',
        'business_id',
        'google_ad_group_id',
        'name',
        'status',
        'type',
        'cpc_bid',
        'cpm_bid',
        'targeting',
        'audience_settings',
        // Aggregated metrics
        'total_cost',
        'total_impressions',
        'total_clicks',
        'total_conversions',
        'avg_cpc',
        'avg_ctr',
        'avg_quality_score',
        'metadata',
    ];

    protected $casts = [
        'cpc_bid' => 'decimal:2',
        'cpm_bid' => 'decimal:2',
        'total_cost' => 'decimal:2',
        'avg_cpc' => 'decimal:4',
        'avg_ctr' => 'decimal:4',
        'avg_quality_score' => 'decimal:2',
        'total_impressions' => 'integer',
        'total_clicks' => 'integer',
        'total_conversions' => 'integer',
        'targeting' => 'array',
        'audience_settings' => 'array',
        'metadata' => 'array',
    ];

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(GoogleAdsCampaign::class, 'campaign_id');
    }

    public function keywords(): HasMany
    {
        return $this->hasMany(GoogleAdsKeyword::class, 'ad_group_id');
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'ENABLED');
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

    public function getTypeLabelAttribute(): string
    {
        return match ($this->type) {
            'SEARCH_STANDARD' => 'Qidiruv (Standart)',
            'DISPLAY_STANDARD' => 'Display (Standart)',
            'VIDEO_BUMPER' => 'Video Bumper',
            'VIDEO_TRUE_VIEW_IN_STREAM' => 'TrueView In-Stream',
            'VIDEO_TRUE_VIEW_IN_DISPLAY' => 'TrueView In-Display',
            'SHOPPING_PRODUCT_ADS' => 'Shopping',
            default => $this->type ?? '-',
        };
    }

    /**
     * Get targeting summary for display
     */
    public function getTargetingSummaryAttribute(): string
    {
        $parts = [];

        if ($this->targeting) {
            if (!empty($this->targeting['age_ranges'])) {
                $parts[] = 'Yosh: ' . implode(', ', $this->targeting['age_ranges']);
            }
            if (!empty($this->targeting['genders'])) {
                $parts[] = 'Jins: ' . implode(', ', $this->targeting['genders']);
            }
        }

        return $parts ? implode(' | ', $parts) : 'Barcha auditoriya';
    }
}
