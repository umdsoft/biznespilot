<?php

namespace App\Models;

use App\Traits\BelongsToBusiness;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Lost Deal tracking - Why deals were lost
 * Provides insights for Sales and Marketing improvement
 */
class LostDeal extends Model
{
    use HasUuids, BelongsToBusiness;

    protected $fillable = [
        'business_id',
        'deal_id',
        'client_id',
        'rejection_reason_id',
        'lost_to_competitor_id',
        'responsible_user_id',
        'potential_value',
        'lost_at_stage_id',
        'lost_date',
        'notes',
        'lessons_learned',
    ];

    protected $casts = [
        'potential_value' => 'decimal:2',
        'lost_date' => 'date',
    ];

    protected static function booted(): void
    {
        static::created(function ($lostDeal) {
            // Increment rejection reason count
            $lostDeal->rejectionReason?->incrementCount();
        });
    }

    // Relationships
    public function rejectionReason(): BelongsTo
    {
        return $this->belongsTo(RejectionReason::class);
    }

    public function lostToCompetitor(): BelongsTo
    {
        return $this->belongsTo(Competitor::class, 'lost_to_competitor_id');
    }

    public function responsibleUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'responsible_user_id');
    }

    public function lostAtStage(): BelongsTo
    {
        return $this->belongsTo(SalesFunnelStage::class, 'lost_at_stage_id');
    }

    // Scopes
    public function scopeForUser($query, string $userId)
    {
        return $query->where('responsible_user_id', $userId);
    }

    public function scopeForReason($query, string $reasonId)
    {
        return $query->where('rejection_reason_id', $reasonId);
    }

    public function scopeLostToCompetitor($query, string $competitorId)
    {
        return $query->where('lost_to_competitor_id', $competitorId);
    }

    public function scopeForPeriod($query, $startDate, $endDate)
    {
        return $query->whereBetween('lost_date', [$startDate, $endDate]);
    }

    public function scopeThisMonth($query)
    {
        return $query->whereBetween('lost_date', [now()->startOfMonth(), now()->endOfMonth()]);
    }

    public function scopeHighValue($query, float $threshold = 1000000)
    {
        return $query->where('potential_value', '>=', $threshold);
    }
}
