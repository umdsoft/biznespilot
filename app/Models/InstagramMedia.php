<?php

namespace App\Models;

use App\Traits\BelongsToBusiness;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InstagramMedia extends Model
{
    use BelongsToBusiness;

    protected $table = 'instagram_media';

    protected $fillable = [
        'instagram_account_id',
        'business_id',
        'media_id',
        'media_type',
        'media_product_type',
        'caption',
        'permalink',
        'thumbnail_url',
        'media_url',
        'like_count',
        'comments_count',
        'shares_count',
        'saves_count',
        'reach',
        'impressions',
        'video_views',
        'plays',
        'replies',
        'taps_forward',
        'taps_back',
        'exits',
        'engagement_rate',
        'posted_at',
        'hashtags',
        'mentions',
        'insights_data',
    ];

    protected $casts = [
        'posted_at' => 'datetime',
        'hashtags' => 'array',
        'mentions' => 'array',
        'insights_data' => 'array',
        'engagement_rate' => 'decimal:4',
    ];

    public function instagramAccount(): BelongsTo
    {
        return $this->belongsTo(InstagramAccount::class);
    }

    public function scopeReels($query)
    {
        return $query->where('media_product_type', 'REELS');
    }

    public function scopePosts($query)
    {
        return $query->where('media_product_type', 'FEED');
    }

    public function scopeStories($query)
    {
        return $query->where('media_product_type', 'STORY');
    }

    public function scopeRecent($query, int $days = 30)
    {
        return $query->where('posted_at', '>=', now()->subDays($days));
    }

    public function getTotalEngagementAttribute(): int
    {
        return $this->like_count + $this->comments_count + $this->shares_count + $this->saves_count;
    }

    public function getIsReelAttribute(): bool
    {
        return $this->media_product_type === 'REELS' || $this->media_type === 'REELS';
    }

    public function getIsStoryAttribute(): bool
    {
        return $this->media_product_type === 'STORY' || $this->media_type === 'STORY';
    }

    public function calculateEngagementRate(int $followersCount): float
    {
        if ($followersCount <= 0) {
            return 0;
        }

        return round(($this->total_engagement / $followersCount) * 100, 4);
    }
}
