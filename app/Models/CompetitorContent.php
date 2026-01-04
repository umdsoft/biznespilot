<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CompetitorContent extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'competitor_id',
        'platform',
        'content_type',
        'external_id',
        'caption',
        'hashtags',
        'mentions',
        'media_type',
        'media_url',
        'thumbnail_url',
        'permalink',
        'likes',
        'comments',
        'shares',
        'saves',
        'views',
        'engagement_rate',
        'sentiment',
        'topics',
        'is_sponsored',
        'is_viral',
        'published_at',
        'day_of_week',
        'hour_of_day',
    ];

    protected $casts = [
        'hashtags' => 'array',
        'mentions' => 'array',
        'topics' => 'array',
        'likes' => 'integer',
        'comments' => 'integer',
        'shares' => 'integer',
        'saves' => 'integer',
        'views' => 'integer',
        'engagement_rate' => 'decimal:4',
        'is_sponsored' => 'boolean',
        'is_viral' => 'boolean',
        'published_at' => 'datetime',
        'hour_of_day' => 'integer',
    ];

    public function competitor(): BelongsTo
    {
        return $this->belongsTo(Competitor::class);
    }

    /**
     * Calculate total engagement
     */
    public function getTotalEngagementAttribute(): int
    {
        return $this->likes + $this->comments + $this->shares + $this->saves;
    }

    /**
     * Check if content is viral based on engagement
     */
    public function checkIfViral(int $followerCount): bool
    {
        if ($followerCount <= 0) return false;

        $engagementRate = ($this->total_engagement / $followerCount) * 100;
        return $engagementRate > 10; // 10% engagement = viral
    }

    /**
     * Scope for platform
     */
    public function scopePlatform($query, string $platform)
    {
        return $query->where('platform', $platform);
    }

    /**
     * Scope for content type
     */
    public function scopeType($query, string $type)
    {
        return $query->where('content_type', $type);
    }

    /**
     * Scope for recent content
     */
    public function scopeRecent($query, int $days = 30)
    {
        return $query->where('published_at', '>=', now()->subDays($days));
    }

    /**
     * Scope for viral content
     */
    public function scopeViral($query)
    {
        return $query->where('is_viral', true);
    }
}
