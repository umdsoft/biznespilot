<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CompetitorReviewStat extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'competitor_id',
        'platform',
        'stat_date',
        'new_reviews_count',
        'avg_rating',
        'cumulative_rating',
        'cumulative_reviews',
        'positive_reviews',
        'neutral_reviews',
        'negative_reviews',
        'sentiment_score',
        'responded_reviews',
        'response_rate',
        'avg_response_time_hours',
        'top_positive_topics',
        'top_negative_topics',
    ];

    protected $casts = [
        'stat_date' => 'date',
        'new_reviews_count' => 'integer',
        'avg_rating' => 'decimal:2',
        'cumulative_rating' => 'decimal:2',
        'cumulative_reviews' => 'integer',
        'positive_reviews' => 'integer',
        'neutral_reviews' => 'integer',
        'negative_reviews' => 'integer',
        'sentiment_score' => 'decimal:4',
        'responded_reviews' => 'integer',
        'response_rate' => 'decimal:2',
        'avg_response_time_hours' => 'integer',
        'top_positive_topics' => 'array',
        'top_negative_topics' => 'array',
    ];

    public function competitor(): BelongsTo
    {
        return $this->belongsTo(Competitor::class);
    }

    /**
     * Get total reviews for the day
     */
    public function getTotalReviewsAttribute(): int
    {
        return $this->positive_reviews + $this->neutral_reviews + $this->negative_reviews;
    }

    /**
     * Get positive percentage
     */
    public function getPositivePercentageAttribute(): ?float
    {
        if ($this->total_reviews === 0) return null;
        return round(($this->positive_reviews / $this->total_reviews) * 100, 1);
    }
}
