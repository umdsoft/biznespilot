<?php

namespace App\Models\Store;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Str;

class StoreOrder extends Model
{
    use HasUuids;

    // Status constants
    const STATUS_PENDING = 'pending';
    const STATUS_CONFIRMED = 'confirmed';
    const STATUS_PROCESSING = 'processing';
    const STATUS_SHIPPED = 'shipped';
    const STATUS_DELIVERED = 'delivered';
    const STATUS_CANCELLED = 'cancelled';
    const STATUS_REFUNDED = 'refunded';

    const PAYMENT_PENDING = 'pending';
    const PAYMENT_PAID = 'paid';
    const PAYMENT_REFUNDED = 'refunded';

    const ACTIVE_STATUSES = [
        self::STATUS_PENDING,
        self::STATUS_CONFIRMED,
        self::STATUS_PROCESSING,
        self::STATUS_SHIPPED,
    ];

    const TERMINAL_STATUSES = [
        self::STATUS_DELIVERED,
        self::STATUS_CANCELLED,
        self::STATUS_REFUNDED,
    ];

    // Delivery types
    const DELIVERY_DELIVERY = 'delivery';
    const DELIVERY_PICKUP = 'pickup';

    // Valid status transitions — SHIPPED -> CANCELLED added for real-world
    // cases: kuryer can't deliver (customer absent, wrong address, refused).
    const STATUS_TRANSITIONS = [
        self::STATUS_PENDING => [self::STATUS_CONFIRMED, self::STATUS_CANCELLED],
        self::STATUS_CONFIRMED => [self::STATUS_PROCESSING, self::STATUS_CANCELLED],
        self::STATUS_PROCESSING => [self::STATUS_SHIPPED, self::STATUS_CANCELLED],
        self::STATUS_SHIPPED => [self::STATUS_DELIVERED, self::STATUS_CANCELLED],
        self::STATUS_DELIVERED => [self::STATUS_REFUNDED],
    ];

    protected $fillable = [
        'store_id',
        'customer_id',
        'order_number',
        'status',
        'subtotal',
        'delivery_fee',
        'discount_amount',
        'total',
        'payment_method',
        'payment_status',
        'delivery_type',
        'delivery_address',
        'notes',
        'promo_code',
        'paid_at',
        'confirmed_at',
        'shipped_at',
        'delivered_at',
        'cancelled_at',
    ];

    protected $casts = [
        'subtotal' => 'decimal:2',
        'delivery_fee' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'total' => 'decimal:2',
        'delivery_address' => 'array',
        'paid_at' => 'datetime',
        'confirmed_at' => 'datetime',
        'shipped_at' => 'datetime',
        'delivered_at' => 'datetime',
        'cancelled_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $order) {
            if (empty($order->order_number)) {
                $order->order_number = self::generateOrderNumber();
            }
        });
    }

    public static function generateOrderNumber(): string
    {
        return 'ORD-' . strtoupper(Str::random(8));
    }

    // Relationships
    public function store(): BelongsTo
    {
        return $this->belongsTo(TelegramStore::class, 'store_id');
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(StoreCustomer::class, 'customer_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(StoreOrderItem::class, 'order_id');
    }

    public function statusHistory(): HasMany
    {
        return $this->hasMany(StoreOrderStatusHistory::class, 'order_id')->orderByDesc('created_at');
    }

    public function paymentTransaction(): HasOne
    {
        return $this->hasOne(StorePaymentTransaction::class, 'order_id')->latest();
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->whereIn('status', self::ACTIVE_STATUSES);
    }

    public function scopePaid($query)
    {
        return $query->where('payment_status', self::PAYMENT_PAID);
    }

    // Status management
    public function canTransitionTo(string $newStatus): bool
    {
        $allowed = self::STATUS_TRANSITIONS[$this->status] ?? [];

        return in_array($newStatus, $allowed);
    }

    public function transitionTo(string $newStatus, ?string $comment = null, ?string $changedBy = null): bool
    {
        if (! $this->canTransitionTo($newStatus)) {
            return false;
        }

        $oldStatus = $this->status;

        $updateData = ['status' => $newStatus];

        match ($newStatus) {
            self::STATUS_CONFIRMED => $updateData['confirmed_at'] = now(),
            self::STATUS_SHIPPED => $updateData['shipped_at'] = now(),
            self::STATUS_DELIVERED => $updateData['delivered_at'] = now(),
            self::STATUS_CANCELLED => $updateData['cancelled_at'] = now(),
            default => null,
        };

        $this->update($updateData);

        StoreOrderStatusHistory::create([
            'order_id' => $this->id,
            'from_status' => $oldStatus,
            'to_status' => $newStatus,
            'comment' => $comment,
            'changed_by' => $changedBy,
        ]);

        return true;
    }

    public function markPaid(string $paymentMethod): void
    {
        $this->update([
            'payment_status' => self::PAYMENT_PAID,
            'payment_method' => $paymentMethod,
            'paid_at' => now(),
        ]);
    }

    public function isPaid(): bool
    {
        return $this->payment_status === self::PAYMENT_PAID;
    }

    public function isActive(): bool
    {
        return in_array($this->status, self::ACTIVE_STATUSES);
    }

    public function isCancellable(): bool
    {
        return in_array($this->status, [self::STATUS_PENDING, self::STATUS_CONFIRMED, self::STATUS_PROCESSING]);
    }

    public function getStatusLabel(): string
    {
        return match ($this->status) {
            self::STATUS_PENDING => 'Kutilmoqda',
            self::STATUS_CONFIRMED => 'Tasdiqlangan',
            self::STATUS_PROCESSING => 'Tayyorlanmoqda',
            self::STATUS_SHIPPED => 'Yetkazilmoqda',
            self::STATUS_DELIVERED => 'Yetkazildi',
            self::STATUS_CANCELLED => 'Bekor qilingan',
            self::STATUS_REFUNDED => 'Qaytarilgan',
            default => $this->status,
        };
    }
}
