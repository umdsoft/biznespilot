<?php

namespace App\Models;

use App\Traits\BelongsToBusiness;
use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GoogleAdsKeyword extends Model
{
    use BelongsToBusiness, HasUuid;

    // Match type constants
    public const MATCH_EXACT = 'EXACT';

    public const MATCH_PHRASE = 'PHRASE';

    public const MATCH_BROAD = 'BROAD';

    protected $fillable = [
        'ad_group_id',
        'business_id',
        'google_criterion_id',
        'keyword_text',
        'match_type',
        'status',
        'cpc_bid',
        'quality_score',
        'expected_ctr',
        'ad_relevance',
        'landing_page_experience',
        // Metrics
        'total_cost',
        'total_impressions',
        'total_clicks',
        'total_conversions',
        'avg_cpc',
        'avg_position',
        'metadata',
    ];

    protected $casts = [
        'cpc_bid' => 'decimal:2',
        'quality_score' => 'decimal:2',
        'total_cost' => 'decimal:2',
        'avg_cpc' => 'decimal:4',
        'avg_position' => 'decimal:2',
        'total_impressions' => 'integer',
        'total_clicks' => 'integer',
        'total_conversions' => 'integer',
        'metadata' => 'array',
    ];

    public function adGroup(): BelongsTo
    {
        return $this->belongsTo(GoogleAdsAdGroup::class, 'ad_group_id');
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'ENABLED');
    }

    public function scopeByMatchType($query, string $type)
    {
        return $query->where('match_type', $type);
    }

    public function getMatchTypeLabelAttribute(): string
    {
        return match ($this->match_type) {
            self::MATCH_EXACT => 'Aniq moslik',
            self::MATCH_PHRASE => 'Ibora mosligi',
            self::MATCH_BROAD => 'Keng moslik',
            default => $this->match_type ?? 'Noma\'lum',
        };
    }

    public function getMatchTypeIconAttribute(): string
    {
        return match ($this->match_type) {
            self::MATCH_EXACT => '[kalit]',
            self::MATCH_PHRASE => '"kalit"',
            self::MATCH_BROAD => '+kalit',
            default => 'kalit',
        };
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'ENABLED' => 'Faol',
            'PAUSED' => 'Pauza',
            'REMOVED' => 'O\'chirilgan',
            default => $this->status ?? 'Noma\'lum',
        };
    }

    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            'ENABLED' => 'green',
            'PAUSED' => 'yellow',
            'REMOVED' => 'red',
            default => 'gray',
        };
    }

    public function getQualityScoreColorAttribute(): string
    {
        if (! $this->quality_score) {
            return 'gray';
        }

        return match (true) {
            $this->quality_score >= 7 => 'green',
            $this->quality_score >= 4 => 'yellow',
            default => 'red',
        };
    }

    /**
     * Get CTR (Click Through Rate)
     */
    public function getCtrAttribute(): float
    {
        if ($this->total_impressions <= 0) {
            return 0;
        }

        return ($this->total_clicks / $this->total_impressions) * 100;
    }

    /**
     * Get formatted keyword with match type indicator
     */
    public function getFormattedKeywordAttribute(): string
    {
        return match ($this->match_type) {
            self::MATCH_EXACT => '['.$this->keyword_text.']',
            self::MATCH_PHRASE => '"'.$this->keyword_text.'"',
            default => $this->keyword_text,
        };
    }
}
