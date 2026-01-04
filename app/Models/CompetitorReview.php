<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CompetitorReview extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'source_id',
        'competitor_id',
        'review_id',
        'author_name',
        'author_avatar',
        'review_text',
        'rating',
        'review_date',
        'likes_count',
        'has_owner_response',
        'owner_response',
        'owner_response_date',
        'sentiment',
        'sentiment_score',
        'topics',
        'keywords',
        'is_fake_suspected',
        'is_featured',
        'is_critical',
    ];

    protected $casts = [
        'rating' => 'integer',
        'review_date' => 'datetime',
        'likes_count' => 'integer',
        'has_owner_response' => 'boolean',
        'owner_response_date' => 'datetime',
        'sentiment_score' => 'decimal:4',
        'topics' => 'array',
        'keywords' => 'array',
        'is_fake_suspected' => 'boolean',
        'is_featured' => 'boolean',
        'is_critical' => 'boolean',
    ];

    public function source(): BelongsTo
    {
        return $this->belongsTo(CompetitorReviewSource::class, 'source_id');
    }

    public function competitor(): BelongsTo
    {
        return $this->belongsTo(Competitor::class);
    }

    /**
     * Check if review is positive
     */
    public function isPositive(): bool
    {
        return $this->rating >= 4 || $this->sentiment === 'positive';
    }

    /**
     * Check if review is negative
     */
    public function isNegative(): bool
    {
        return $this->rating <= 2 || $this->sentiment === 'negative';
    }

    /**
     * Scope for positive reviews
     */
    public function scopePositive($query)
    {
        return $query->where(function ($q) {
            $q->where('rating', '>=', 4)
                ->orWhere('sentiment', 'positive');
        });
    }

    /**
     * Scope for negative reviews
     */
    public function scopeNegative($query)
    {
        return $query->where(function ($q) {
            $q->where('rating', '<=', 2)
                ->orWhere('sentiment', 'negative');
        });
    }

    /**
     * Scope for critical reviews that need attention
     */
    public function scopeCritical($query)
    {
        return $query->where('is_critical', true);
    }

    /**
     * Scope for recent reviews
     */
    public function scopeRecent($query, int $days = 30)
    {
        return $query->where('review_date', '>=', now()->subDays($days));
    }

    /**
     * Scope for reviews without owner response
     */
    public function scopeUnanswered($query)
    {
        return $query->where('has_owner_response', false);
    }
}
