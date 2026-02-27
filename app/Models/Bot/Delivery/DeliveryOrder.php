<?php

namespace App\Models\Bot\Delivery;

use App\Traits\BelongsToBusiness;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class DeliveryOrder extends Model
{
    use BelongsToBusiness, HasUuids, SoftDeletes;

    const STATUS_PENDING = 'pending';
    const STATUS_CONFIRMED = 'confirmed';
    const STATUS_PREPARING = 'preparing';
    const STATUS_READY = 'ready';
    const STATUS_DELIVERING = 'delivering';
    const STATUS_DELIVERED = 'delivered';
    const STATUS_CANCELLED = 'cancelled';

    const ACTIVE_STATUSES = [
        self::STATUS_PENDING,
        self::STATUS_CONFIRMED,
        self::STATUS_PREPARING,
        self::STATUS_READY,
        self::STATUS_DELIVERING,
    ];

    const TERMINAL_STATUSES = [
        self::STATUS_DELIVERED,
        self::STATUS_CANCELLED,
    ];

    const STATUS_TRANSITIONS = [
        self::STATUS_PENDING => [self::STATUS_CONFIRMED, self::STATUS_CANCELLED],
        self::STATUS_CONFIRMED => [self::STATUS_PREPARING, self::STATUS_CANCELLED],
        self::STATUS_PREPARING => [self::STATUS_READY],
        self::STATUS_READY => [self::STATUS_DELIVERING],
        self::STATUS_DELIVERING => [self::STATUS_DELIVERED],
    ];

    const STATUS_TIMESTAMP_MAP = [
        self::STATUS_CONFIRMED => 'confirmed_at',
        self::STATUS_PREPARING => 'preparing_at',
        self::STATUS_READY => 'ready_at',
        self::STATUS_DELIVERING => 'delivering_at',
        self::STATUS_DELIVERED => 'delivered_at',
        self::STATUS_CANCELLED => 'cancelled_at',
    ];

    protected $fillable = [
        'business_id', 'order_number', 'telegram_user_id',
        'customer_name', 'customer_phone', 'status', 'delivery_type',
        'delivery_address', 'delivery_landmark', 'delivery_lat', 'delivery_lng',
        'scheduled_at', 'estimated_delivery',
        'subtotal', 'delivery_fee', 'service_fee', 'discount_amount', 'total',
        'payment_method', 'payment_status', 'coupon_code', 'notes',
        'courier_name', 'courier_phone',
        'confirmed_at', 'preparing_at', 'ready_at', 'delivering_at',
        'delivered_at', 'cancelled_at', 'cancel_reason',
    ];

    protected $casts = [
        'subtotal' => 'decimal:2',
        'delivery_fee' => 'decimal:2',
        'service_fee' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'total' => 'decimal:2',
        'delivery_lat' => 'decimal:8',
        'delivery_lng' => 'decimal:8',
        'estimated_delivery' => 'integer',
        'telegram_user_id' => 'integer',
        'scheduled_at' => 'datetime',
        'confirmed_at' => 'datetime',
        'preparing_at' => 'datetime',
        'ready_at' => 'datetime',
        'delivering_at' => 'datetime',
        'delivered_at' => 'datetime',
        'cancelled_at' => 'datetime',
    ];

    public function items(): HasMany
    {
        return $this->hasMany(DeliveryOrderItem::class, 'order_id');
    }

    public function canTransitionTo(string $newStatus): bool
    {
        $allowed = self::STATUS_TRANSITIONS[$this->status] ?? [];

        return in_array($newStatus, $allowed);
    }

    public function transitionTo(string $newStatus): bool
    {
        if (! $this->canTransitionTo($newStatus)) {
            return false;
        }

        $this->status = $newStatus;

        $tsField = self::STATUS_TIMESTAMP_MAP[$newStatus] ?? null;
        if ($tsField) {
            $this->{$tsField} = now();
        }

        return $this->save();
    }

    public function isActive(): bool
    {
        return in_array($this->status, self::ACTIVE_STATUSES);
    }

    public function isTerminal(): bool
    {
        return in_array($this->status, self::TERMINAL_STATUSES);
    }

    public function scopeActive($query)
    {
        return $query->whereIn('status', self::ACTIVE_STATUSES);
    }

    public function scopeByStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    public function scopeByUser($query, int $telegramUserId)
    {
        return $query->where('telegram_user_id', $telegramUserId);
    }

    public static function generateOrderNumber(): string
    {
        $last = static::withoutGlobalScope('business')
            ->where('order_number', 'like', 'YB-%')
            ->orderByDesc('created_at')
            ->value('order_number');

        $num = $last ? (int) substr($last, 3) + 1 : 1;

        return 'YB-' . str_pad($num, 4, '0', STR_PAD_LEFT);
    }
}
