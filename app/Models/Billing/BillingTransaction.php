<?php

namespace App\Models\Billing;

use App\Models\Business;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Str;

/**
 * BillingTransaction - SaaS to'lovlari tranzaksiyalari
 *
 * Bu model Payme va Click orqali qilingan barcha to'lovlarni saqlaydi.
 *
 * Status Flow:
 * created -> waiting -> processing -> paid
 *                    -> cancelled
 *                    -> failed
 *
 * @property int $id
 * @property string $uuid
 * @property string $business_id
 * @property string $plan_id
 * @property string|null $subscription_id
 * @property string $provider
 * @property string|null $provider_transaction_id
 * @property string $order_id
 * @property float $amount
 * @property string $currency
 * @property string $status
 * @property int|null $status_code
 * @property string|null $status_message
 * @property \Carbon\Carbon|null $performed_at
 * @property \Carbon\Carbon|null $cancelled_at
 * @property \Carbon\Carbon|null $expires_at
 * @property string|null $cancel_reason
 * @property array|null $payload
 * @property array|null $metadata
 * @property string|null $ip_address
 * @property string|null $user_agent
 */
class BillingTransaction extends Model
{
    protected $table = 'billing_transactions';

    protected $fillable = [
        'uuid',
        'business_id',
        'plan_id',
        'subscription_id',
        'provider',
        'provider_transaction_id',
        'order_id',
        'amount',
        'currency',
        'status',
        'status_code',
        'status_message',
        'performed_at',
        'cancelled_at',
        'expires_at',
        'cancel_reason',
        'payload',
        'metadata',
        'ip_address',
        'user_agent',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'performed_at' => 'datetime',
        'cancelled_at' => 'datetime',
        'expires_at' => 'datetime',
        'payload' => 'array',
        'metadata' => 'array',
    ];

    // Status constants
    public const STATUS_CREATED = 'created';
    public const STATUS_WAITING = 'waiting';
    public const STATUS_PROCESSING = 'processing';
    public const STATUS_PAID = 'paid';
    public const STATUS_CANCELLED = 'cancelled';
    public const STATUS_FAILED = 'failed';
    public const STATUS_REFUNDED = 'refunded';

    // Provider constants
    public const PROVIDER_PAYME = 'payme';
    public const PROVIDER_CLICK = 'click';

    /**
     * Boot the model
     */
    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (self $model) {
            if (empty($model->uuid)) {
                $model->uuid = (string) Str::uuid();
            }
            if (empty($model->order_id)) {
                $model->order_id = self::generateOrderId();
            }
            if (empty($model->expires_at)) {
                $hours = config('billing.transaction.expiration_hours', 24);
                $model->expires_at = now()->addHours($hours);
            }
        });
    }

    /**
     * Generate unique order ID
     */
    public static function generateOrderId(): string
    {
        $prefix = config('billing.transaction.order_prefix', 'BP');
        $timestamp = now()->format('ymdHis');
        $random = strtoupper(Str::random(4));

        return "{$prefix}{$timestamp}{$random}";
    }

    // ============================================================
    // RELATIONSHIPS
    // ============================================================

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }

    public function subscription(): BelongsTo
    {
        return $this->belongsTo(Subscription::class);
    }

    public function paymeTransaction(): HasOne
    {
        return $this->hasOne(BillingPaymeTransaction::class);
    }

    public function clickTransaction(): HasOne
    {
        return $this->hasOne(BillingClickTransaction::class);
    }

    // ============================================================
    // SCOPES
    // ============================================================

    public function scopeByProvider($query, string $provider)
    {
        return $query->where('provider', $provider);
    }

    public function scopePayme($query)
    {
        return $query->where('provider', self::PROVIDER_PAYME);
    }

    public function scopeClick($query)
    {
        return $query->where('provider', self::PROVIDER_CLICK);
    }

    public function scopePaid($query)
    {
        return $query->where('status', self::STATUS_PAID);
    }

    public function scopePending($query)
    {
        return $query->whereIn('status', [
            self::STATUS_CREATED,
            self::STATUS_WAITING,
            self::STATUS_PROCESSING,
        ]);
    }

    public function scopeExpired($query)
    {
        return $query->where('expires_at', '<', now())
            ->whereIn('status', [self::STATUS_CREATED, self::STATUS_WAITING]);
    }

    public function scopeByOrderId($query, string $orderId)
    {
        return $query->where('order_id', $orderId);
    }

    // ============================================================
    // STATUS HELPERS
    // ============================================================

    public function isCreated(): bool
    {
        return $this->status === self::STATUS_CREATED;
    }

    public function isWaiting(): bool
    {
        return $this->status === self::STATUS_WAITING;
    }

    public function isProcessing(): bool
    {
        return $this->status === self::STATUS_PROCESSING;
    }

    public function isPaid(): bool
    {
        return $this->status === self::STATUS_PAID;
    }

    public function isCancelled(): bool
    {
        return $this->status === self::STATUS_CANCELLED;
    }

    public function isFailed(): bool
    {
        return $this->status === self::STATUS_FAILED;
    }

    public function isRefunded(): bool
    {
        return $this->status === self::STATUS_REFUNDED;
    }

    public function isExpired(): bool
    {
        return $this->expires_at && $this->expires_at->isPast()
            && in_array($this->status, [self::STATUS_CREATED, self::STATUS_WAITING]);
    }

    public function canBeCancelled(): bool
    {
        return in_array($this->status, [
            self::STATUS_CREATED,
            self::STATUS_WAITING,
            self::STATUS_PROCESSING,
        ]);
    }

    public function canBePerformed(): bool
    {
        return $this->status === self::STATUS_WAITING && !$this->isExpired();
    }

    // ============================================================
    // STATUS TRANSITIONS
    // ============================================================

    public function markAsWaiting(): void
    {
        $this->update(['status' => self::STATUS_WAITING]);
    }

    public function markAsProcessing(): void
    {
        $this->update(['status' => self::STATUS_PROCESSING]);
    }

    public function markAsPaid(): void
    {
        $this->update([
            'status' => self::STATUS_PAID,
            'performed_at' => now(),
        ]);
    }

    public function markAsCancelled(string $reason = null, int $statusCode = null): void
    {
        $this->update([
            'status' => self::STATUS_CANCELLED,
            'cancelled_at' => now(),
            'cancel_reason' => $reason,
            'status_code' => $statusCode,
        ]);
    }

    public function markAsFailed(string $message = null, int $statusCode = null): void
    {
        $this->update([
            'status' => self::STATUS_FAILED,
            'status_message' => $message,
            'status_code' => $statusCode,
        ]);
    }

    // ============================================================
    // AMOUNT HELPERS
    // ============================================================

    /**
     * Get amount in tiyin (for Payme)
     * 1 so'm = 100 tiyin
     */
    public function getAmountInTiyin(): int
    {
        return (int) ($this->amount * 100);
    }

    /**
     * Set amount from tiyin
     */
    public function setAmountFromTiyin(int $tiyin): void
    {
        $this->amount = $tiyin / 100;
        $this->save();
    }

    /**
     * Format amount for display
     */
    public function getFormattedAmountAttribute(): string
    {
        return number_format($this->amount, 0, '', ' ') . ' ' . $this->currency;
    }

    // ============================================================
    // PROVIDER HELPERS
    // ============================================================

    public function isPayme(): bool
    {
        return $this->provider === self::PROVIDER_PAYME;
    }

    public function isClick(): bool
    {
        return $this->provider === self::PROVIDER_CLICK;
    }

    /**
     * Get provider-specific transaction
     */
    public function getProviderTransaction()
    {
        return $this->isPayme()
            ? $this->paymeTransaction
            : $this->clickTransaction;
    }

    // ============================================================
    // PAYLOAD HELPERS
    // ============================================================

    public function appendPayload(array $data): void
    {
        $payload = $this->payload ?? [];
        $payload[] = [
            'timestamp' => now()->toIso8601String(),
            'data' => $data,
        ];
        $this->update(['payload' => $payload]);
    }

    public function setMetadata(string $key, $value): void
    {
        $metadata = $this->metadata ?? [];
        $metadata[$key] = $value;
        $this->update(['metadata' => $metadata]);
    }

    public function getMetadata(string $key, $default = null)
    {
        return $this->metadata[$key] ?? $default;
    }
}
