<?php

namespace App\Models;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PaymeTransaction extends Model
{
    use HasUuid;

    // Payme Transaction States
    public const STATE_CREATED = 1;
    public const STATE_COMPLETED = 2;
    public const STATE_CANCELLED_AFTER_COMPLETE = -1;
    public const STATE_CANCELLED = -2;

    // Cancel Reasons
    public const REASON_RECEIVER_NOT_FOUND = 1;
    public const REASON_PROCESSING_ERROR = 2;
    public const REASON_TRANSACTION_ERROR = 3;
    public const REASON_TIMEOUT = 4;
    public const REASON_REFUND = 5;
    public const REASON_UNKNOWN = 10;

    protected $fillable = [
        'payment_transaction_id',
        'payme_transaction_id',
        'payme_time',
        'state',
        'reason',
        'create_time',
        'perform_time',
        'cancel_time',
    ];

    protected $casts = [
        'payme_time' => 'integer',
        'state' => 'integer',
        'reason' => 'integer',
        'create_time' => 'integer',
        'perform_time' => 'integer',
        'cancel_time' => 'integer',
    ];

    // ==================== Relationships ====================

    public function paymentTransaction(): BelongsTo
    {
        return $this->belongsTo(PaymentTransaction::class);
    }

    // ==================== Accessors ====================

    public function getStateNameAttribute(): string
    {
        return match ($this->state) {
            self::STATE_CREATED => 'Yaratilgan',
            self::STATE_COMPLETED => 'Bajarilgan',
            self::STATE_CANCELLED_AFTER_COMPLETE => 'Bekor qilingan (to\'lovdan keyin)',
            self::STATE_CANCELLED => 'Bekor qilingan',
            default => 'Noma\'lum',
        };
    }

    public function getReasonNameAttribute(): ?string
    {
        if (!$this->reason) {
            return null;
        }

        return match ($this->reason) {
            self::REASON_RECEIVER_NOT_FOUND => 'Qabul qiluvchi topilmadi',
            self::REASON_PROCESSING_ERROR => 'Qayta ishlash xatosi',
            self::REASON_TRANSACTION_ERROR => 'Tranzaksiya xatosi',
            self::REASON_TIMEOUT => 'Vaqt tugadi',
            self::REASON_REFUND => 'Qaytarildi',
            self::REASON_UNKNOWN => 'Noma\'lum sabab',
            default => 'Sabab: ' . $this->reason,
        };
    }

    // ==================== Methods ====================

    public function isCompleted(): bool
    {
        return $this->state === self::STATE_COMPLETED;
    }

    public function isCancelled(): bool
    {
        return in_array($this->state, [self::STATE_CANCELLED, self::STATE_CANCELLED_AFTER_COMPLETE]);
    }

    public function isCreated(): bool
    {
        return $this->state === self::STATE_CREATED;
    }
}
