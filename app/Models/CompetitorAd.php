<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CompetitorAd extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'competitor_id',
        'platform',
        'ad_id',
        'page_id',
        'page_name',
        'headline',
        'body_text',
        'link_caption',
        'call_to_action',
        'destination_url',
        'media_type',
        'media_urls',
        'thumbnail_url',
        'ad_status',
        'started_at',
        'ended_at',
        'days_running',
        'is_active',
        'targeting_countries',
        'targeting_demographics',
        'targeting_interests',
        'reach_estimate',
        'estimated_spend_min',
        'estimated_spend_max',
        'currency',
        'detected_products',
        'detected_offers',
        'ad_category',
        'creative_score',
        'raw_data',
    ];

    protected $casts = [
        'media_urls' => 'array',
        'targeting_countries' => 'array',
        'targeting_demographics' => 'array',
        'targeting_interests' => 'array',
        'detected_products' => 'array',
        'detected_offers' => 'array',
        'raw_data' => 'array',
        'started_at' => 'date',
        'ended_at' => 'date',
        'days_running' => 'integer',
        'is_active' => 'boolean',
        'estimated_spend_min' => 'decimal:2',
        'estimated_spend_max' => 'decimal:2',
        'creative_score' => 'decimal:2',
    ];

    public function competitor(): BelongsTo
    {
        return $this->belongsTo(Competitor::class);
    }

    /**
     * Calculate days running
     */
    public function calculateDaysRunning(): int
    {
        if (! $this->started_at) {
            return 0;
        }

        $endDate = $this->ended_at ?? now();

        return $this->started_at->diffInDays($endDate);
    }

    /**
     * Get average estimated spend
     */
    public function getAvgEstimatedSpendAttribute(): ?float
    {
        if ($this->estimated_spend_min && $this->estimated_spend_max) {
            return ($this->estimated_spend_min + $this->estimated_spend_max) / 2;
        }

        return $this->estimated_spend_min ?? $this->estimated_spend_max;
    }

    /**
     * Scope for active ads
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope for platform
     */
    public function scopePlatform($query, string $platform)
    {
        return $query->where('platform', $platform);
    }

    /**
     * Scope for long-running ads (successful campaigns)
     */
    public function scopeLongRunning($query, int $minDays = 30)
    {
        return $query->where('days_running', '>=', $minDays);
    }
}
