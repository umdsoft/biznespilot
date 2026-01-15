<?php

namespace App\Models;

use App\Traits\BelongsToBusiness;
use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InstagramMedia extends Model
{
    use BelongsToBusiness, HasUuid;

    protected $table = 'instagram_media';

    protected $fillable = [
        'account_id',
        'media_id',
        'media_type',
        'caption',
        'permalink',
        'media_url',
        'thumbnail_url',
        'like_count',
        'comments_count',
        'reach',
        'impressions',
        'saved',
        'shares',
        'engagement_rate',
        'posted_at',
    ];

    protected $casts = [
        'posted_at' => 'datetime',
        'engagement_rate' => 'decimal:4',
    ];

    public function instagramAccount(): BelongsTo
    {
        return $this->belongsTo(InstagramAccount::class, 'account_id');
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
