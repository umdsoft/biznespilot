<?php

namespace App\Models;

use App\Traits\BelongsToBusiness;
use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
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
        'global_competitor_id',
        'name',
        'website',
        'description',
        'industry',
        'location',
        'region',
        'district',
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
        'swot_data',
        'swot_analyzed_at',
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
        'swot_data' => 'array',
        'auto_monitor' => 'boolean',
        'is_active' => 'boolean',
        'last_checked_at' => 'datetime',
        'swot_analyzed_at' => 'datetime',
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

    /**
     * Get the global competitor record
     */
    public function globalCompetitor(): BelongsTo
    {
        return $this->belongsTo(GlobalCompetitor::class);
    }

    /**
     * Link to or create a global competitor and sync SWOT data
     */
    public function syncWithGlobalCompetitor(): void
    {
        // Find or create global competitor
        $globalCompetitor = GlobalCompetitor::findOrCreateFromCompetitor([
            'name' => $this->name,
            'website' => $this->website,
            'description' => $this->description,
            'industry' => $this->industry,
            'region' => $this->region,
            'district' => $this->district,
            'instagram_handle' => $this->instagram_handle,
            'telegram_handle' => $this->telegram_handle,
            'facebook_page' => $this->facebook_page,
            'tiktok_handle' => $this->tiktok_handle,
            'youtube_channel' => $this->youtube_channel,
        ]);

        // Link to global competitor
        $this->global_competitor_id = $globalCompetitor->id;
        $this->saveQuietly();

        // If this competitor has SWOT data, merge it to global with business_id
        if (!empty($this->swot_data)) {
            $globalCompetitor->mergeSwotData($this->swot_data, $this->business_id);
        }
    }

    /**
     * Get SWOT data - prefer global if available
     */
    public function getEffectiveSwotDataAttribute(): array
    {
        // First check local SWOT data
        if (!empty($this->swot_data)) {
            return $this->swot_data;
        }

        // Fall back to global competitor SWOT data
        if ($this->globalCompetitor && !empty($this->globalCompetitor->swot_data)) {
            return $this->globalCompetitor->swot_data;
        }

        return [
            'strengths' => [],
            'weaknesses' => [],
            'opportunities' => [],
            'threats' => [],
        ];
    }
}
