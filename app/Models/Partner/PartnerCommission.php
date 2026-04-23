<?php

namespace App\Models\Partner;

use App\Models\Billing\BillingTransaction;
use App\Models\Business;
use App\Models\Subscription;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PartnerCommission extends Model
{
    use HasUuids;

    public const STATUS_PENDING = 'pending';     // 30-day refund buffer
    public const STATUS_AVAILABLE = 'available'; // ready for payout
    public const STATUS_PAID = 'paid';           // partnerga to'langan
    public const STATUS_REVERSED = 'reversed';   // refund/cancellation
    public const STATUS_CLAWBACK = 'clawback';   // manual/fraud

    public const RATE_TYPE_FIRST_PAYMENT = 'first_payment';
    public const RATE_TYPE_LIFETIME = 'lifetime';

    /**
     * @deprecated 2026-04-23 — use RATE_TYPE_FIRST_PAYMENT.
     *             Eski commissionlar uchun qoldirilgan (DB compatibility).
     */
    public const RATE_TYPE_YEAR_ONE = 'first_payment';

    public const CLAWBACK_BUFFER_DAYS = 30;

    protected $fillable = [
        'partner_id', 'referral_id', 'business_id',
        'subscription_id', 'billing_transaction_id',
        'gross_amount', 'rate_applied', 'commission_amount', 'rate_type',
        'period_start', 'period_end',
        'status', 'available_at', 'paid_at',
        'payout_id', 'clawback_reason', 'admin_note',
    ];

    protected $casts = [
        'gross_amount' => 'decimal:2',
        'rate_applied' => 'decimal:4',
        'commission_amount' => 'decimal:2',
        'period_start' => 'date',
        'period_end' => 'date',
        'available_at' => 'datetime',
        'paid_at' => 'datetime',
    ];

    // ========== RELATIONSHIPS ==========

    public function partner(): BelongsTo
    {
        return $this->belongsTo(Partner::class);
    }

    public function referral(): BelongsTo
    {
        return $this->belongsTo(PartnerReferral::class, 'referral_id');
    }

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    public function subscription(): BelongsTo
    {
        return $this->belongsTo(Subscription::class);
    }

    public function billingTransaction(): BelongsTo
    {
        return $this->belongsTo(BillingTransaction::class);
    }

    public function payout(): BelongsTo
    {
        return $this->belongsTo(PartnerPayout::class, 'payout_id');
    }

    // ========== SCOPES ==========

    public function scopePending($q)
    {
        return $q->where('status', self::STATUS_PENDING);
    }

    public function scopeAvailable($q)
    {
        return $q->where('status', self::STATUS_AVAILABLE);
    }

    public function scopePaid($q)
    {
        return $q->where('status', self::STATUS_PAID);
    }

    public function scopeReadyForPromotion($q)
    {
        return $q->where('status', self::STATUS_PENDING)
            ->where('available_at', '<=', now());
    }
}
