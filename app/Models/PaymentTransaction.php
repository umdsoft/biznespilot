<?php

namespace App\Models;

use App\Traits\BelongsToBusiness;
use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Str;

class PaymentTransaction extends Model
{
    use BelongsToBusiness, HasUuid;

    // Statuses
    public const STATUS_PENDING = 'pending';
    public const STATUS_PROCESSING = 'processing';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_CANCELLED = 'cancelled';
    public const STATUS_FAILED = 'failed';
    public const STATUS_REFUNDED = 'refunded';

    public const STATUSES = [
        self::STATUS_PENDING => 'Kutilmoqda',
        self::STATUS_PROCESSING => 'Jarayonda',
        self::STATUS_COMPLETED => 'To\'langan',
        self::STATUS_CANCELLED => 'Bekor qilingan',
        self::STATUS_FAILED => 'Xatolik',
        self::STATUS_REFUNDED => 'Qaytarilgan',
    ];

    public const STATUS_COLORS = [
        self::STATUS_PENDING => 'yellow',
        self::STATUS_PROCESSING => 'blue',
        self::STATUS_COMPLETED => 'green',
        self::STATUS_CANCELLED => 'gray',
        self::STATUS_FAILED => 'red',
        self::STATUS_REFUNDED => 'purple',
    ];

    protected $fillable = [
        'business_id',
        'lead_id',
        'payment_account_id',
        'created_by',
        'provider',
        'provider_transaction_id',
        'order_id',
        'amount',
        'currency',
        'status',
        'payment_url',
        'return_url',
        'description',
        'metadata',
        'paid_at',
        'cancelled_at',
        'expires_at',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'metadata' => 'array',
        'paid_at' => 'datetime',
        'cancelled_at' => 'datetime',
        'expires_at' => 'datetime',
    ];

    // ==================== Boot ====================

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->order_id)) {
                $model->order_id = self::generateOrderId();
            }
        });
    }

    // ==================== Relationships ====================

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class);
    }

    public function paymentAccount(): BelongsTo
    {
        return $this->belongsTo(PaymentAccount::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function paymeTransaction(): HasOne
    {
        return $this->hasOne(PaymeTransaction::class);
    }

    public function clickTransaction(): HasOne
    {
        return $this->hasOne(ClickTransaction::class);
    }

    // ==================== Scopes ====================

    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', self::STATUS_COMPLETED);
    }

    public function scopeByProvider($query, string $provider)
    {
        return $query->where('provider', $provider);
    }

    // ==================== Accessors ====================

    public function getStatusLabelAttribute(): string
    {
        return self::STATUSES[$this->status] ?? $this->status;
    }

    public function getStatusColorAttribute(): string
    {
        return self::STATUS_COLORS[$this->status] ?? 'gray';
    }

    public function getFormattedAmountAttribute(): string
    {
        return number_format($this->amount, 0, ',', ' ') . ' ' . $this->currency;
    }

    public function getAmountInTiyinAttribute(): int
    {
        return (int) ($this->amount * 100);
    }

    public function getIsExpiredAttribute(): bool
    {
        return $this->expires_at && $this->expires_at->isPast();
    }

    public function getIsPaidAttribute(): bool
    {
        return $this->status === self::STATUS_COMPLETED;
    }

    public function getIsPendingAttribute(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    // ==================== Methods ====================

    /**
     * Generate unique order ID
     */
    public static function generateOrderId(): string
    {
        return 'ORD-' . strtoupper(Str::random(8)) . '-' . time();
    }

    /**
     * Mark as processing
     */
    public function markAsProcessing(?string $providerTransactionId = null): void
    {
        $this->update([
            'status' => self::STATUS_PROCESSING,
            'provider_transaction_id' => $providerTransactionId ?? $this->provider_transaction_id,
        ]);
    }

    /**
     * Mark as completed (paid)
     */
    public function markAsCompleted(): void
    {
        $this->update([
            'status' => self::STATUS_COMPLETED,
            'paid_at' => now(),
        ]);

        // Update lead status if exists
        if ($this->lead) {
            $this->lead->update([
                'status' => 'won',
                'actual_value' => $this->amount,
            ]);

            // Add to lead data
            $leadData = $this->lead->data ?? [];
            $leadData['payment_completed'] = true;
            $leadData['payment_amount'] = $this->amount;
            $leadData['payment_date'] = now()->toISOString();
            $leadData['payment_provider'] = $this->provider;
            $this->lead->update(['data' => $leadData]);
        }

        // Update payment account
        $this->paymentAccount?->touchLastTransaction();
    }

    /**
     * Mark as cancelled
     */
    public function markAsCancelled(?string $reason = null): void
    {
        $metadata = $this->metadata ?? [];
        if ($reason) {
            $metadata['cancel_reason'] = $reason;
        }

        $this->update([
            'status' => self::STATUS_CANCELLED,
            'cancelled_at' => now(),
            'metadata' => $metadata,
        ]);
    }

    /**
     * Mark as failed
     */
    public function markAsFailed(?string $reason = null): void
    {
        $metadata = $this->metadata ?? [];
        if ($reason) {
            $metadata['failure_reason'] = $reason;
        }

        $this->update([
            'status' => self::STATUS_FAILED,
            'metadata' => $metadata,
        ]);
    }

    /**
     * Add metadata
     */
    public function addMetadata(array $data): void
    {
        $metadata = $this->metadata ?? [];
        $this->update(['metadata' => array_merge($metadata, $data)]);
    }

    /**
     * Check if can be cancelled
     */
    public function canBeCancelled(): bool
    {
        return in_array($this->status, [self::STATUS_PENDING, self::STATUS_PROCESSING]);
    }

    /**
     * Find by order ID
     */
    public static function findByOrderId(string $orderId): ?self
    {
        return self::where('order_id', $orderId)->first();
    }
}
