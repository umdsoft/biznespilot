<?php

namespace App\Models;

use App\Traits\BelongsToBusiness;
use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class SalesPenalty extends Model
{
    use BelongsToBusiness, HasFactory, HasUuid;

    /**
     * Statuslar
     */
    public const STATUSES = [
        'pending' => 'Kutilmoqda',
        'warning' => 'Ogohlantirish',
        'confirmed' => 'Tasdiqlangan',
        'appealed' => 'Shikoyat qilingan',
        'appeal_approved' => 'Shikoyat qabul qilindi',
        'appeal_rejected' => 'Shikoyat rad etildi',
        'cancelled' => 'Bekor qilingan',
        'deducted' => 'Bonusdan ayirildi',
    ];

    /**
     * Appeal qarorlari
     */
    public const APPEAL_DECISIONS = [
        'approved' => 'Qabul qilindi',
        'rejected' => 'Rad etildi',
    ];

    protected $fillable = [
        'business_id',
        'penalty_rule_id',
        'user_id',
        'category',
        'reason',
        'description',
        'related_type',
        'related_id',
        'trigger_data',
        'triggered_at',
        'penalty_amount',
        'status',
        'issued_by',
        'issued_at',
        'confirmed_by',
        'confirmed_at',
        'appeal_reason',
        'appealed_at',
        'appeal_reviewed_by',
        'appeal_reviewed_at',
        'appeal_decision',
        'appeal_resolution',
        'deducted_from_bonus_id',
        'deducted_at',
        'notes',
    ];

    protected $casts = [
        'trigger_data' => 'array',
        'triggered_at' => 'datetime',
        'penalty_amount' => 'decimal:2',
        'issued_at' => 'datetime',
        'confirmed_at' => 'datetime',
        'appealed_at' => 'datetime',
        'appeal_reviewed_at' => 'datetime',
        'deducted_at' => 'datetime',
    ];

    /**
     * Jarima qoidasi
     */
    public function penaltyRule(): BelongsTo
    {
        return $this->belongsTo(SalesPenaltyRule::class, 'penalty_rule_id');
    }

    /**
     * Foydalanuvchi
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Kim tomonidan berilgan
     */
    public function issuedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'issued_by');
    }

    /**
     * Kim tasdiqladi
     */
    public function confirmedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'confirmed_by');
    }

    /**
     * Appealini kim ko'rib chiqdi
     */
    public function appealReviewedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'appeal_reviewed_by');
    }

    /**
     * Qaysi bonusdan ayirildi
     */
    public function deductedFromBonus(): BelongsTo
    {
        return $this->belongsTo(SalesBonusCalculation::class, 'deducted_from_bonus_id');
    }

    /**
     * Bog'liq model (Lead, Task, CallLog)
     */
    public function related(): MorphTo
    {
        return $this->morphTo('related');
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
     * Confirmed
     */
    public function scopeConfirmed(Builder $query): Builder
    {
        return $query->withStatus('confirmed');
    }

    /**
     * Appealable (shikoyat qilsa bo'ladigan)
     */
    public function scopeAppealable(Builder $query): Builder
    {
        return $query->whereIn('status', ['pending', 'confirmed']);
    }

    /**
     * Foydalanuvchi uchun
     */
    public function scopeForUser(Builder $query, string $userId): Builder
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Sana orasida
     */
    public function scopeTriggeredBetween(Builder $query, $startDate, $endDate): Builder
    {
        return $query->whereBetween('triggered_at', [$startDate, $endDate]);
    }

    /**
     * Shu oy
     */
    public function scopeThisMonth(Builder $query): Builder
    {
        return $query->triggeredBetween(now()->startOfMonth(), now()->endOfMonth());
    }

    /**
     * Status labelini olish
     */
    public function getStatusLabelAttribute(): string
    {
        return self::STATUSES[$this->status] ?? $this->status;
    }

    /**
     * Formatlangan summa
     */
    public function getFormattedAmountAttribute(): string
    {
        return number_format($this->penalty_amount, 0, '.', ' ').' so\'m';
    }

    /**
     * Shikoyat qilsa bo'ladimi
     */
    public function canBeAppealed(): bool
    {
        if (! in_array($this->status, ['pending', 'confirmed'])) {
            return false;
        }

        $rule = $this->penaltyRule;

        if (! $rule || ! $rule->allow_appeal) {
            return false;
        }

        // Deadline tekshirish
        $deadline = $this->triggered_at->addDays($rule->appeal_deadline_days);

        return now()->isBefore($deadline);
    }

    /**
     * Appeal deadline
     */
    public function getAppealDeadlineAttribute(): ?\Carbon\Carbon
    {
        $rule = $this->penaltyRule;

        if (! $rule || ! $rule->allow_appeal) {
            return null;
        }

        return $this->triggered_at->addDays($rule->appeal_deadline_days);
    }

    /**
     * Qolgan appeal vaqti
     */
    public function getRemainingAppealTimeAttribute(): ?string
    {
        if (! $this->canBeAppealed()) {
            return null;
        }

        return $this->appeal_deadline->diffForHumans();
    }

    /**
     * Tasdiqlash
     */
    public function confirm(string $confirmedBy): void
    {
        $this->update([
            'status' => 'confirmed',
            'confirmed_by' => $confirmedBy,
            'confirmed_at' => now(),
        ]);
    }

    /**
     * Shikoyat qilish
     */
    public function submitAppeal(string $reason): void
    {
        $this->update([
            'status' => 'appealed',
            'appeal_reason' => $reason,
            'appealed_at' => now(),
        ]);
    }

    /**
     * Appealini ko'rib chiqish
     */
    public function reviewAppeal(string $reviewedBy, string $decision, ?string $resolution = null): void
    {
        $newStatus = $decision === 'approved' ? 'appeal_approved' : 'appeal_rejected';

        $this->update([
            'status' => $newStatus,
            'appeal_reviewed_by' => $reviewedBy,
            'appeal_reviewed_at' => now(),
            'appeal_decision' => $decision,
            'appeal_resolution' => $resolution,
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
     * Bonusdan ayirish
     */
    public function deductFromBonus(string $bonusId): void
    {
        $this->update([
            'status' => 'deducted',
            'deducted_from_bonus_id' => $bonusId,
            'deducted_at' => now(),
        ]);
    }
}
