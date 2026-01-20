<?php

namespace App\Models;

use App\Traits\BelongsToBusiness;
use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SalesBonusCalculation extends Model
{
    use BelongsToBusiness, HasFactory, HasUuid;

    /**
     * Statuslar
     */
    public const STATUSES = [
        'pending' => 'Kutilmoqda',
        'calculated' => 'Hisoblangan',
        'approved' => 'Tasdiqlangan',
        'rejected' => 'Rad etilgan',
        'paid' => 'To\'langan',
        'cancelled' => 'Bekor qilingan',
    ];

    protected $fillable = [
        'business_id',
        'bonus_setting_id',
        'user_id',
        'period_type',
        'period_start',
        'period_end',
        'kpi_score',
        'total_revenue',
        'working_days',
        'is_qualified',
        'disqualification_reason',
        'base_amount',
        'tier_multiplier',
        'applied_tier',
        'final_amount',
        'calculation_breakdown',
        'status',
        'calculated_by',
        'calculated_at',
        'approved_by',
        'approved_at',
        'approval_notes',
        'rejection_reason',
        'rejected_by',
        'rejected_at',
        'paid_at',
        'payment_reference',
    ];

    protected $casts = [
        'period_start' => 'date',
        'period_end' => 'date',
        'kpi_score' => 'integer',
        'total_revenue' => 'decimal:2',
        'working_days' => 'integer',
        'is_qualified' => 'boolean',
        'base_amount' => 'decimal:2',
        'tier_multiplier' => 'decimal:2',
        'final_amount' => 'decimal:2',
        'calculation_breakdown' => 'array',
        'calculated_at' => 'datetime',
        'approved_at' => 'datetime',
        'rejected_at' => 'datetime',
        'paid_at' => 'datetime',
    ];

    protected $appends = [
        'total_penalties',
        'net_amount',
    ];

    /**
     * Bonus sozlamasi
     */
    public function bonusSetting(): BelongsTo
    {
        return $this->belongsTo(SalesBonusSetting::class, 'bonus_setting_id');
    }

    /**
     * Foydalanuvchi
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Hisoblagan
     */
    public function calculatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'calculated_by');
    }

    /**
     * Tasdiqlagan
     */
    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /**
     * Rad etgan
     */
    public function rejectedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'rejected_by');
    }

    /**
     * Bog'liq jarimalar
     */
    public function penalties(): HasMany
    {
        return $this->hasMany(SalesPenalty::class, 'deducted_from_bonus_id');
    }

    /**
     * Status bo'yicha filter
     */
    public function scopeWithStatus(Builder $query, string $status): Builder
    {
        return $query->where('status', $status);
    }

    /**
     * Pending
     */
    public function scopePending(Builder $query): Builder
    {
        return $query->withStatus('pending');
    }

    /**
     * Tasdiqlashni kutayotgan
     */
    public function scopeAwaitingApproval(Builder $query): Builder
    {
        return $query->whereIn('status', ['pending', 'calculated']);
    }

    /**
     * Qualified
     */
    public function scopeQualified(Builder $query): Builder
    {
        return $query->where('is_qualified', true);
    }

    /**
     * Foydalanuvchi uchun
     */
    public function scopeForUser(Builder $query, string $userId): Builder
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Davr uchun
     */
    public function scopeForPeriod(Builder $query, string $periodType, $periodStart): Builder
    {
        return $query->where('period_type', $periodType)
            ->where('period_start', $periodStart);
    }

    /**
     * Status labelini olish
     */
    public function getStatusLabelAttribute(): string
    {
        return self::STATUSES[$this->status] ?? $this->status;
    }

    /**
     * Davr labelini olish
     */
    public function getPeriodLabelAttribute(): string
    {
        return match ($this->period_type) {
            'monthly' => $this->period_start->translatedFormat('F Y'),
            'quarterly' => 'Q'.ceil($this->period_start->month / 3).' '.$this->period_start->year,
            default => $this->period_start->format('d.m.Y').' - '.$this->period_end->format('d.m.Y'),
        };
    }

    /**
     * Formatlangan summa
     */
    public function getFormattedAmountAttribute(): string
    {
        return number_format($this->final_amount, 0, '.', ' ').' so\'m';
    }

    /**
     * Tasdiqlash mumkinmi
     */
    public function canBeApproved(): bool
    {
        return in_array($this->status, ['pending', 'calculated']) && $this->is_qualified;
    }

    /**
     * Rad etish mumkinmi
     */
    public function canBeRejected(): bool
    {
        return in_array($this->status, ['pending', 'calculated']);
    }

    /**
     * To'lash mumkinmi
     */
    public function canBePaid(): bool
    {
        return $this->status === 'approved';
    }

    /**
     * Tasdiqlash
     */
    public function approve(string $approverId, ?string $notes = null): void
    {
        $this->update([
            'status' => 'approved',
            'approved_by' => $approverId,
            'approved_at' => now(),
            'approval_notes' => $notes,
        ]);
    }

    /**
     * Rad etish
     */
    public function reject(string $rejectedBy, string $reason): void
    {
        $this->update([
            'status' => 'rejected',
            'rejected_by' => $rejectedBy,
            'rejected_at' => now(),
            'rejection_reason' => $reason,
        ]);
    }

    /**
     * To'langan deb belgilash
     */
    public function markAsPaid(?string $paymentReference = null): void
    {
        $this->update([
            'status' => 'paid',
            'paid_at' => now(),
            'payment_reference' => $paymentReference,
        ]);
    }

    /**
     * Bekor qilish
     */
    public function cancel(): void
    {
        $this->update([
            'status' => 'cancelled',
        ]);
    }

    /**
     * Jarimalar yig'indisi
     */
    public function getTotalPenaltiesAttribute(): float
    {
        return $this->penalties()->sum('penalty_amount');
    }

    /**
     * Sof bonus (jarimalardan keyin)
     */
    public function getNetAmountAttribute(): float
    {
        return max(0, $this->final_amount - $this->total_penalties);
    }
}
