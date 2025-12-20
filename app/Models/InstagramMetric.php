<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InstagramMetric extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'marketing_channel_id',
        'metric_date',
        'followers_count',
        'following_count',
        'media_count',
        'likes',
        'comments',
        'shares',
        'saves',
        'reach',
        'impressions',
        'profile_views',
        'stories_posted',
        'stories_reach',
        'stories_impressions',
        'stories_replies',
        'reels_posted',
        'reels_plays',
        'reels_reach',
        'reels_likes',
        'reels_comments',
        'reels_shares',
        'engagement_rate',
        'new_followers',
        'lost_followers',
        'website_clicks',
        'email_contacts',
        'phone_calls',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'metric_date' => 'date',
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
        return $this->likes + $this->comments + $this->shares + $this->saves;
    }

    /**
     * Calculate engagement rate.
     */
    public function calculateEngagementRate(): float
    {
        if ($this->followers_count === 0) {
            return 0;
        }

        return round(($this->getTotalEngagementAttribute() / $this->followers_count) * 100, 2);
    }
}
