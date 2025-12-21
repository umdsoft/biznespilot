<?php

namespace App\Models;

use App\Traits\BelongsToBusiness;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MetaInsight extends Model
{
    use BelongsToBusiness;

    protected $fillable = [
        'ad_account_id',
        'business_id',
        'object_type',
        'object_id',
        'object_name',
        'date',
        'impressions',
        'reach',
        'frequency',
        'clicks',
        'unique_clicks',
        'link_clicks',
        'cpc',
        'cpm',
        'cpp',
        'ctr',
        'unique_ctr',
        'spend',
        'post_engagement',
        'page_engagement',
        'post_reactions',
        'post_comments',
        'post_shares',
        'post_saves',
        'video_views',
        'video_views_p25',
        'video_views_p50',
        'video_views_p75',
        'video_views_p100',
        'video_avg_time_watched',
        'conversions',
        'conversion_value',
        'cost_per_conversion',
        'roas',
        'actions',
        'action_values',
        'cost_per_action_type',
        'age_range',
        'gender',
        'country',
        'region',
        'publisher_platform',
        'platform_position',
        'device_platform',
    ];

    protected $casts = [
        'date' => 'date',
        'impressions' => 'integer',
        'reach' => 'integer',
        'frequency' => 'decimal:4',
        'clicks' => 'integer',
        'unique_clicks' => 'integer',
        'link_clicks' => 'integer',
        'cpc' => 'decimal:4',
        'cpm' => 'decimal:4',
        'cpp' => 'decimal:4',
        'ctr' => 'decimal:4',
        'unique_ctr' => 'decimal:4',
        'spend' => 'decimal:2',
        'conversions' => 'integer',
        'conversion_value' => 'decimal:2',
        'cost_per_conversion' => 'decimal:4',
        'roas' => 'decimal:4',
        'actions' => 'array',
        'action_values' => 'array',
        'cost_per_action_type' => 'array',
    ];

    public function adAccount(): BelongsTo
    {
        return $this->belongsTo(MetaAdAccount::class, 'ad_account_id');
    }

    public function scopeForDateRange($query, string $startDate, string $endDate)
    {
        return $query->whereBetween('date', [$startDate, $endDate]);
    }

    public function scopeForAccount($query)
    {
        return $query->where('object_type', 'account');
    }

    public function scopeForCampaign($query)
    {
        return $query->where('object_type', 'campaign');
    }

    public function scopeByPlatform($query, string $platform)
    {
        return $query->where('publisher_platform', $platform);
    }

    public function scopeWithDemographics($query)
    {
        return $query->whereNotNull('age_range')
            ->orWhereNotNull('gender');
    }

    public function getPlatformLabelAttribute(): string
    {
        return match ($this->publisher_platform) {
            'facebook' => 'Facebook',
            'instagram' => 'Instagram',
            'messenger' => 'Messenger',
            'audience_network' => 'Audience Network',
            default => ucfirst($this->publisher_platform ?? 'Unknown'),
        };
    }

    public function getPositionLabelAttribute(): string
    {
        return match ($this->platform_position) {
            'feed' => 'Feed',
            'story' => 'Stories',
            'reels' => 'Reels',
            'explore' => 'Explore',
            'search' => 'Search',
            'instream_video' => 'In-Stream Video',
            'right_hand_column' => 'Right Column',
            'marketplace' => 'Marketplace',
            default => ucfirst($this->platform_position ?? 'Unknown'),
        };
    }
}
