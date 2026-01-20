<?php

namespace App\Models;

use App\Traits\BelongsToBusiness;
use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class MarketingPenalty extends Model
{
    use HasUuid, BelongsToBusiness, SoftDeletes;

    protected $fillable = [
        'business_id',
        'user_id',
        'bonus_id',
        'date',
        'type',
        'reason',
        'description',
        'amount',
        'percentage',
        'reference_type',
        'reference_id',
        'status',
        'applied_by',
        'applied_at',
        'dispute_reason',
    ];

    protected $casts = [
        'date' => 'date',
        'amount' => 'decimal:2',
        'percentage' => 'decimal:2',
        'applied_at' => 'datetime',
    ];

    // Penalty types
    public const TYPE_TARGET_MISS = 'target_miss';
    public const TYPE_CPL_EXCEED = 'cpl_exceed';
    public const TYPE_DEADLINE_MISS = 'deadline_miss';
    public const TYPE_DATA_ENTRY = 'data_entry';
    public const TYPE_ROI_NEGATIVE = 'roi_negative';
    public const TYPE_QUALITY_ISSUE = 'quality_issue';
    public const TYPE_BUDGET_OVERSPEND = 'budget_overspend';

    public const TYPES = [
        self::TYPE_TARGET_MISS => 'Target bajarilmadi',
        self::TYPE_CPL_EXCEED => 'CPL limitdan oshdi',
        self::TYPE_DEADLINE_MISS => 'Deadline o\'tkazildi',
        self::TYPE_DATA_ENTRY => 'Ma\'lumot kiritilmadi',
        self::TYPE_ROI_NEGATIVE => 'ROI manfiy',
        self::TYPE_QUALITY_ISSUE => 'Sifat muammosi',
        self::TYPE_BUDGET_OVERSPEND => 'Byudjet oshirildi',
    ];

    // RELATIONSHIPS

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function bonus(): BelongsTo
    {
        return $this->belongsTo(MarketingBonus::class, 'bonus_id');
    }

    public function appliedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'applied_by');
    }

    // SCOPES

    public function scopeForUser(Builder $query, $userId): Builder
    {
        return $query->where('user_id', $userId);
    }

    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', 'pending');
    }

    public function scopeApplied(Builder $query): Builder
    {
        return $query->where('status', 'applied');
    }

    public function scopeDisputed(Builder $query): Builder
    {
        return $query->where('status', 'disputed');
    }

    public function scopeForDateRange(Builder $query, $from, $to): Builder
    {
        return $query->whereBetween('date', [$from, $to]);
    }

    // HELPERS

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isApplied(): bool
    {
        return $this->status === 'applied';
    }

    public function canBeDisputed(): bool
    {
        return in_array($this->status, ['pending', 'applied']);
    }

    public function getTypeLabel(): string
    {
        return self::TYPES[$this->type] ?? $this->type;
    }

    public function apply(?string $appliedBy = null): void
    {
        $this->update([
            'status' => 'applied',
            'applied_by' => $appliedBy ?? auth()->id(),
            'applied_at' => now(),
        ]);
    }

    public function dispute(string $reason): void
    {
        $this->update([
            'status' => 'disputed',
            'dispute_reason' => $reason,
        ]);
    }

    public function waive(): void
    {
        $this->update(['status' => 'waived']);
    }
}
