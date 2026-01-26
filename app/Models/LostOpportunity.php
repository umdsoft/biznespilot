<?php

namespace App\Models;

use App\Traits\BelongsToBusiness;
use App\Traits\HasUuid;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * LostOpportunity - Yo'qotilgan imkoniyatlar.
 *
 * Bu model "Black Box" konsepsiyasining asosiy qismi:
 * Qancha pul yo'qotilganini kuzatish va marketing ROI ni hisoblash.
 *
 * @property string $id
 * @property string $business_id
 * @property string $lead_id
 * @property string|null $lost_by
 * @property string|null $assigned_to
 * @property string|null $campaign_id
 * @property string|null $marketing_channel_id
 * @property string|null $source_id
 * @property string|null $utm_source
 * @property string|null $utm_medium
 * @property string|null $utm_campaign
 * @property string|null $utm_content
 * @property string|null $utm_term
 * @property string|null $attribution_source_type
 * @property float $estimated_value
 * @property float $acquisition_cost
 * @property string $currency
 * @property string $lost_reason
 * @property string|null $lost_reason_details
 * @property string|null $lost_stage
 * @property Carbon $lost_at
 * @property string|null $lost_to_competitor
 * @property string|null $competitor_notes
 * @property bool $is_recoverable
 * @property int $recovery_attempts
 * @property Carbon|null $last_recovery_attempt_at
 * @property Carbon|null $recovered_at
 * @property string|null $recovered_lead_id
 * @property string|null $lessons_learned
 * @property array|null $data
 */
class LostOpportunity extends Model
{
    use BelongsToBusiness, HasUuid, SoftDeletes;

    /**
     * Loss reason codes with translations.
     */
    public const LOST_REASONS = [
        'price' => 'Narx qimmat',
        'competitor' => 'Raqobatchini tanladi',
        'no_budget' => 'Byudjet yo\'q',
        'no_need' => 'Ehtiyoj yo\'q',
        'no_response' => 'Javob bermadi',
        'wrong_contact' => 'Noto\'g\'ri kontakt',
        'low_quality' => 'Sifatsiz lid',
        'timing' => 'Vaqt mos kelmadi',
        'product_fit' => 'Mahsulot mos kelmadi',
        'service_issue' => 'Xizmat bilan muammo',
        'other' => 'Boshqa sabab',
    ];

    /**
     * Attribution source types.
     */
    public const SOURCE_TYPES = [
        'digital' => 'Raqamli marketing',
        'offline' => 'Offline marketing',
        'organic' => 'Organik',
        'referral' => 'Referral',
        'direct' => 'To\'g\'ridan-to\'g\'ri',
    ];

    protected $fillable = [
        'business_id',
        'lead_id',
        'lost_by',
        'assigned_to',
        // Marketing Attribution
        'campaign_id',
        'marketing_channel_id',
        'source_id',
        'utm_source',
        'utm_medium',
        'utm_campaign',
        'utm_content',
        'utm_term',
        'attribution_source_type',
        // Financial
        'estimated_value',
        'acquisition_cost',
        'currency',
        // Loss tracking
        'lost_reason',
        'lost_reason_details',
        'lost_stage',
        'lost_at',
        // Competitor
        'lost_to_competitor',
        'competitor_notes',
        // Recovery
        'is_recoverable',
        'recovery_attempts',
        'last_recovery_attempt_at',
        'recovered_at',
        'recovered_lead_id',
        // Analysis
        'lessons_learned',
        'data',
    ];

    protected $casts = [
        'estimated_value' => 'decimal:2',
        'acquisition_cost' => 'decimal:2',
        'is_recoverable' => 'boolean',
        'recovery_attempts' => 'integer',
        'lost_at' => 'datetime',
        'last_recovery_attempt_at' => 'datetime',
        'recovered_at' => 'datetime',
        'data' => 'array',
    ];

    // ==========================================
    // RELATIONSHIPS
    // ==========================================

    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class)->withTrashed();
    }

    public function lostByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'lost_by');
    }

    public function assignedToUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(Campaign::class);
    }

    public function marketingChannel(): BelongsTo
    {
        return $this->belongsTo(MarketingChannel::class);
    }

    public function source(): BelongsTo
    {
        return $this->belongsTo(LeadSource::class, 'source_id');
    }

    public function recoveredLead(): BelongsTo
    {
        return $this->belongsTo(Lead::class, 'recovered_lead_id');
    }

    // ==========================================
    // SCOPES
    // ==========================================

    /**
     * Scope: Filter by date range.
     */
    public function scopeLostBetween(Builder $query, Carbon $from, Carbon $to): Builder
    {
        return $query->whereBetween('lost_at', [$from, $to]);
    }

    /**
     * Scope: Filter by campaign.
     */
    public function scopeFromCampaign(Builder $query, string $campaignId): Builder
    {
        return $query->where('campaign_id', $campaignId);
    }

    /**
     * Scope: Filter by marketing channel.
     */
    public function scopeFromChannel(Builder $query, string $channelId): Builder
    {
        return $query->where('marketing_channel_id', $channelId);
    }

    /**
     * Scope: Filter by lost reason.
     */
    public function scopeByReason(Builder $query, string $reason): Builder
    {
        return $query->where('lost_reason', $reason);
    }

    /**
     * Scope: Filter by competitor.
     */
    public function scopeLostToCompetitor(Builder $query, ?string $competitor = null): Builder
    {
        if ($competitor) {
            return $query->where('lost_to_competitor', $competitor);
        }

        return $query->whereNotNull('lost_to_competitor');
    }

    /**
     * Scope: Recoverable opportunities.
     */
    public function scopeRecoverable(Builder $query): Builder
    {
        return $query->where('is_recoverable', true)
            ->whereNull('recovered_at');
    }

    /**
     * Scope: Recovered opportunities.
     */
    public function scopeRecovered(Builder $query): Builder
    {
        return $query->whereNotNull('recovered_at');
    }

    /**
     * Scope: With attribution (has campaign or channel).
     */
    public function scopeWithAttribution(Builder $query): Builder
    {
        return $query->where(function ($q) {
            $q->whereNotNull('campaign_id')
              ->orWhereNotNull('marketing_channel_id')
              ->orWhereNotNull('utm_source');
        });
    }

    /**
     * Scope: High value opportunities.
     */
    public function scopeHighValue(Builder $query, float $threshold = 1000000): Builder
    {
        return $query->where('estimated_value', '>=', $threshold);
    }

    // ==========================================
    // ATTRIBUTE ACCESSORS
    // ==========================================

    /**
     * Get lost reason label.
     */
    public function getLostReasonLabelAttribute(): string
    {
        return self::LOST_REASONS[$this->lost_reason] ?? $this->lost_reason;
    }

    /**
     * Get attribution source type label.
     */
    public function getSourceTypeLabelAttribute(): string
    {
        return self::SOURCE_TYPES[$this->attribution_source_type] ?? $this->attribution_source_type ?? 'Noma\'lum';
    }

    /**
     * Check if opportunity has marketing attribution.
     */
    public function hasAttribution(): bool
    {
        return $this->campaign_id !== null
            || $this->marketing_channel_id !== null
            || $this->utm_source !== null;
    }

    /**
     * Get UTM parameters as array.
     */
    public function getUtmArray(): array
    {
        return [
            'utm_source' => $this->utm_source,
            'utm_medium' => $this->utm_medium,
            'utm_campaign' => $this->utm_campaign,
            'utm_content' => $this->utm_content,
            'utm_term' => $this->utm_term,
        ];
    }

    /**
     * Get full attribution summary.
     */
    public function getAttributionSummary(): array
    {
        return [
            'campaign_id' => $this->campaign_id,
            'campaign_name' => $this->campaign?->name,
            'channel_id' => $this->marketing_channel_id,
            'channel_name' => $this->marketingChannel?->name,
            'source_id' => $this->source_id,
            'source_name' => $this->source?->name,
            'source_type' => $this->attribution_source_type,
            'utm' => $this->getUtmArray(),
        ];
    }

    /**
     * Calculate wasted marketing spend (CAC for lost lead).
     */
    public function getWastedSpendAttribute(): float
    {
        return (float) $this->acquisition_cost;
    }

    /**
     * Get total loss (estimated value + wasted spend).
     */
    public function getTotalLossAttribute(): float
    {
        return (float) $this->estimated_value + (float) $this->acquisition_cost;
    }

    /**
     * Check if recovered.
     */
    public function isRecovered(): bool
    {
        return $this->recovered_at !== null;
    }

    /**
     * Get days since lost.
     */
    public function getDaysSinceLostAttribute(): int
    {
        return $this->lost_at->diffInDays(now());
    }

    // ==========================================
    // METHODS
    // ==========================================

    /**
     * Mark as recovered.
     */
    public function markAsRecovered(?string $newLeadId = null): void
    {
        $this->update([
            'recovered_at' => now(),
            'recovered_lead_id' => $newLeadId,
            'is_recoverable' => false,
        ]);
    }

    /**
     * Log recovery attempt.
     */
    public function logRecoveryAttempt(): void
    {
        $this->increment('recovery_attempts');
        $this->update(['last_recovery_attempt_at' => now()]);
    }

    /**
     * Mark as non-recoverable.
     */
    public function markAsNonRecoverable(string $reason = null): void
    {
        $updates = ['is_recoverable' => false];

        if ($reason) {
            $data = $this->data ?? [];
            $data['non_recoverable_reason'] = $reason;
            $updates['data'] = $data;
        }

        $this->update($updates);
    }
}
