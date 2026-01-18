<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CompetitorReviewSource extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'competitor_id',
        'platform',
        'place_id',
        'profile_url',
        'name',
        'current_rating',
        'total_reviews',
        'rating_trend',
        'five_star_count',
        'four_star_count',
        'three_star_count',
        'two_star_count',
        'one_star_count',
        'is_tracked',
        'last_checked_at',
        'raw_data',
    ];

    protected $casts = [
        'current_rating' => 'decimal:2',
        'total_reviews' => 'integer',
        'rating_trend' => 'decimal:2',
        'five_star_count' => 'integer',
        'four_star_count' => 'integer',
        'three_star_count' => 'integer',
        'two_star_count' => 'integer',
        'one_star_count' => 'integer',
        'is_tracked' => 'boolean',
        'last_checked_at' => 'datetime',
        'raw_data' => 'array',
    ];

    public function competitor(): BelongsTo
    {
        return $this->belongsTo(Competitor::class);
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(CompetitorReview::class, 'source_id');
    }

    /**
     * Get rating breakdown as array
     */
    public function getRatingBreakdownAttribute(): array
    {
        return [
            5 => $this->five_star_count,
            4 => $this->four_star_count,
            3 => $this->three_star_count,
            2 => $this->two_star_count,
            1 => $this->one_star_count,
        ];
    }

    /**
     * Calculate rating from breakdown
     */
    public function calculateAverageRating(): ?float
    {
        $totalRatings = $this->five_star_count + $this->four_star_count +
            $this->three_star_count + $this->two_star_count + $this->one_star_count;

        if ($totalRatings === 0) {
            return null;
        }

        $weightedSum = (5 * $this->five_star_count) + (4 * $this->four_star_count) +
            (3 * $this->three_star_count) + (2 * $this->two_star_count) + (1 * $this->one_star_count);

        return round($weightedSum / $totalRatings, 2);
    }

    /**
     * Scope for tracked sources
     */
    public function scopeTracked($query)
    {
        return $query->where('is_tracked', true);
    }

    /**
     * Scope for platform
     */
    public function scopePlatform($query, string $platform)
    {
        return $query->where('platform', $platform);
    }
}
