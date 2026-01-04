<?php

namespace App\Models;

use App\Traits\BelongsToBusiness;
use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Competitor extends Model
{
    use BelongsToBusiness, SoftDeletes, HasUuid;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'business_id',
        'name',
        'website',
        'description',
        'industry',
        'location',
        'threat_level',
        'status',
        'social_links',
        'strengths',
        'weaknesses',
        'price_range',
        'market_position',
        'products',
        'pricing',
        'marketing_strategies',
        'notes',
        'instagram_handle',
        'telegram_handle',
        'facebook_page',
        'tiktok_handle',
        'youtube_channel',
        'auto_monitor',
        'check_frequency_hours',
        'last_checked_at',
        'tags',
        'is_active',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'social_links' => 'array',
        'strengths' => 'array',
        'weaknesses' => 'array',
        'products' => 'array',
        'pricing' => 'array',
        'marketing_strategies' => 'array',
        'tags' => 'array',
        'auto_monitor' => 'boolean',
        'is_active' => 'boolean',
        'last_checked_at' => 'datetime',
    ];

    /**
     * Get the activities for the competitor.
     */
    public function activities(): HasMany
    {
        return $this->hasMany(CompetitorActivity::class);
    }

    /**
     * Get the metrics for the competitor.
     */
    public function metrics(): HasMany
    {
        return $this->hasMany(CompetitorMetric::class);
    }

    /**
     * Get the contents/posts for the competitor.
     */
    public function contents(): HasMany
    {
        return $this->hasMany(CompetitorContent::class);
    }

    /**
     * Get the content stats for the competitor.
     */
    public function contentStats(): HasMany
    {
        return $this->hasMany(CompetitorContentStat::class);
    }

    /**
     * Get the ads for the competitor.
     */
    public function ads(): HasMany
    {
        return $this->hasMany(CompetitorAd::class);
    }

    /**
     * Get the ad stats for the competitor.
     */
    public function adStats(): HasMany
    {
        return $this->hasMany(CompetitorAdStat::class);
    }

    /**
     * Get the products for the competitor.
     */
    public function products(): HasMany
    {
        return $this->hasMany(CompetitorProduct::class);
    }

    /**
     * Get the promotions for the competitor.
     */
    public function promotions(): HasMany
    {
        return $this->hasMany(CompetitorPromotion::class);
    }

    /**
     * Get the review sources for the competitor.
     */
    public function reviewSources(): HasMany
    {
        return $this->hasMany(CompetitorReviewSource::class);
    }

    /**
     * Get the reviews for the competitor.
     */
    public function reviews(): HasMany
    {
        return $this->hasMany(CompetitorReview::class);
    }

    /**
     * Get the review stats for the competitor.
     */
    public function reviewStats(): HasMany
    {
        return $this->hasMany(CompetitorReviewStat::class);
    }

    /**
     * Get active ads count
     */
    public function getActiveAdsCountAttribute(): int
    {
        return $this->ads()->active()->count();
    }

    /**
     * Get average rating across all review sources
     */
    public function getAverageRatingAttribute(): ?float
    {
        $sources = $this->reviewSources()->whereNotNull('current_rating')->get();
        if ($sources->isEmpty()) return null;
        return round($sources->avg('current_rating'), 1);
    }
}
