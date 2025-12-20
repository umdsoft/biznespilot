<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FacebookMetric extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'marketing_channel_id',
        'metric_date',
        'page_likes',
        'page_followers',
        'new_likes',
        'new_followers',
        'posts_count',
        'reach',
        'impressions',
        'likes',
        'comments',
        'shares',
        'reactions',
        'video_views',
        'video_reach',
        'average_watch_time',
        'page_views',
        'page_views_unique',
        'cta_clicks',
        'website_clicks',
        'phone_clicks',
        'direction_clicks',
        'engagement_rate',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'metric_date' => 'date',
        'average_watch_time' => 'decimal:2',
        'engagement_rate' => 'decimal:2',
    ];

    /**
     * Get the marketing channel for this metric.
     */
    public function marketingChannel(): BelongsTo
    {
        return $this->belongsTo(MarketingChannel::class);
    }

    /**
     * Calculate total engagement.
     */
    public function getTotalEngagementAttribute(): int
    {
        return $this->likes + $this->comments + $this->shares + $this->reactions;
    }

    /**
     * Calculate engagement rate.
     */
    public function calculateEngagementRate(): float
    {
        if ($this->reach === 0) {
            return 0;
        }

        return round(($this->getTotalEngagementAttribute() / $this->reach) * 100, 2);
    }

    /**
     * Calculate total CTA clicks.
     */
    public function getTotalCtaClicksAttribute(): int
    {
        return $this->cta_clicks + $this->website_clicks + $this->phone_clicks + $this->direction_clicks;
    }
}
