<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CompetitorContentStat extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'competitor_id',
        'platform',
        'stat_date',
        'posts_count',
        'reels_count',
        'stories_count',
        'videos_count',
        'total_likes',
        'total_comments',
        'total_shares',
        'total_views',
        'avg_engagement_rate',
        'top_content_id',
        'top_content_engagement',
        'top_hashtags',
    ];

    protected $casts = [
        'stat_date' => 'date',
        'posts_count' => 'integer',
        'reels_count' => 'integer',
        'stories_count' => 'integer',
        'videos_count' => 'integer',
        'total_likes' => 'integer',
        'total_comments' => 'integer',
        'total_shares' => 'integer',
        'total_views' => 'integer',
        'avg_engagement_rate' => 'decimal:4',
        'top_content_engagement' => 'integer',
        'top_hashtags' => 'array',
    ];

    public function competitor(): BelongsTo
    {
        return $this->belongsTo(Competitor::class);
    }

    public function topContent(): BelongsTo
    {
        return $this->belongsTo(CompetitorContent::class, 'top_content_id');
    }

    /**
     * Get total content count for the day
     */
    public function getTotalContentCountAttribute(): int
    {
        return $this->posts_count + $this->reels_count + $this->stories_count + $this->videos_count;
    }

    /**
     * Get total engagement for the day
     */
    public function getTotalEngagementAttribute(): int
    {
        return $this->total_likes + $this->total_comments + $this->total_shares;
    }
}
