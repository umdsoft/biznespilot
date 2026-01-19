<?php

namespace App\Models;

use App\Traits\BelongsToBusiness;
use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Offer extends Model
{
    use BelongsToBusiness, HasUuid, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'business_id',
        'name',
        'description',
        'value_proposition',
        'target_audience',
        'pricing',
        'pricing_model',
        'guarantees',
        'bonuses',
        'scarcity',
        'urgency',
        'status',
        'conversion_rate',
        'metadata',
        // Value Equation - "$100M Offers"
        'dream_outcome_score',
        'perceived_likelihood_score',
        'time_delay_days',
        'effort_score',
        'value_score',
        'guarantee_type',
        'guarantee_terms',
        'guarantee_period_days',
        'core_offer',
        'total_value',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'pricing' => 'decimal:2',
        'metadata' => 'array',
        'value_score' => 'decimal:2',
        'total_value' => 'decimal:2',
        'dream_outcome_score' => 'integer',
        'perceived_likelihood_score' => 'integer',
        'time_delay_days' => 'integer',
        'effort_score' => 'integer',
        'guarantee_period_days' => 'integer',
    ];

    /**
     * Calculate Value Score based on Value Equation
     * Formula: (Dream Outcome × Perceived Likelihood) / (Time Delay × Effort)
     */
    public function calculateValueScore(): float
    {
        $dreamOutcome = $this->dream_outcome_score ?? 5;
        $perceivedLikelihood = $this->perceived_likelihood_score ?? 5;
        $timeDelay = $this->time_delay_days ?? 30;
        $effort = $this->effort_score ?? 5;

        // Avoid division by zero
        $denominator = ($timeDelay * $effort);
        if ($denominator == 0) {
            return 0;
        }

        $valueScore = ($dreamOutcome * $perceivedLikelihood) / $denominator;

        return round($valueScore, 2);
    }

    /**
     * Automatically calculate and save value score before saving
     */
    protected static function boot()
    {
        parent::boot();

        static::saving(function ($offer) {
            $offer->value_score = $offer->calculateValueScore();
        });
    }

    /**
     * Get the components for the offer.
     */
    public function components(): HasMany
    {
        return $this->hasMany(OfferComponent::class);
    }

    /**
     * Get the lead assignments for this offer.
     */
    public function leadAssignments(): HasMany
    {
        return $this->hasMany(OfferLeadAssignment::class);
    }

    /**
     * Get leads assigned to this offer.
     */
    public function leads(): BelongsToMany
    {
        return $this->belongsToMany(Lead::class, 'offer_lead_assignments')
            ->withPivot([
                'status',
                'channel',
                'sent_at',
                'viewed_at',
                'converted_at',
                'view_count',
                'click_count',
                'tracking_code',
            ])
            ->withTimestamps();
    }

    /**
     * Get offer metrics.
     */
    public function metrics(): HasMany
    {
        return $this->hasMany(OfferMetric::class);
    }

    /**
     * Get aggregated metrics for this offer.
     */
    public function getAggregatedMetrics($startDate = null, $endDate = null): array
    {
        return OfferMetric::getAggregatedMetrics($this->id, $startDate, $endDate);
    }

    /**
     * Get active assignments count.
     */
    public function getActiveAssignmentsCount(): int
    {
        return $this->leadAssignments()->active()->count();
    }

    /**
     * Get conversion count.
     */
    public function getConversionsCount(): int
    {
        return $this->leadAssignments()->converted()->count();
    }

    /**
     * Check if offer is active.
     */
    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    // Scopes

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeDraft($query)
    {
        return $query->where('status', 'draft');
    }
}
