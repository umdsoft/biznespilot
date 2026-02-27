<?php

namespace App\Models\Bot\Delivery;

use App\Models\Business;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DeliverySetting extends Model
{
    use HasUuids;

    protected $fillable = [
        'business_id', 'min_order_amount', 'delivery_fee', 'free_delivery_from',
        'service_fee_percent', 'estimated_delivery_min', 'estimated_delivery_max',
        'working_hours', 'delivery_zones', 'auto_accept_orders', 'order_notifications',
    ];

    protected $casts = [
        'min_order_amount' => 'decimal:2',
        'delivery_fee' => 'decimal:2',
        'free_delivery_from' => 'decimal:2',
        'service_fee_percent' => 'decimal:2',
        'estimated_delivery_min' => 'integer',
        'estimated_delivery_max' => 'integer',
        'working_hours' => 'array',
        'delivery_zones' => 'array',
        'auto_accept_orders' => 'boolean',
        'order_notifications' => 'array',
    ];

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    public static function getForBusiness(string $businessId): self
    {
        return static::firstOrCreate(
            ['business_id' => $businessId],
            [
                'min_order_amount' => 0,
                'delivery_fee' => 15000,
                'estimated_delivery_min' => 30,
                'estimated_delivery_max' => 60,
            ]
        );
    }

    public function isWithinWorkingHours(): bool
    {
        if (! $this->working_hours) {
            return true;
        }

        $dayOfWeek = strtolower(now()->format('D'));
        $dayMap = ['mon' => 'mon', 'tue' => 'tue', 'wed' => 'wed', 'thu' => 'thu', 'fri' => 'fri', 'sat' => 'sat', 'sun' => 'sun'];
        $day = $dayMap[$dayOfWeek] ?? null;

        if (! $day || ! isset($this->working_hours[$day])) {
            return false;
        }

        $hours = $this->working_hours[$day];
        $now = now()->format('H:i');

        return $now >= ($hours['from'] ?? '00:00') && $now <= ($hours['to'] ?? '23:59');
    }

    public function calculateDeliveryFee(float $subtotal): float
    {
        if ($this->free_delivery_from && $subtotal >= $this->free_delivery_from) {
            return 0;
        }

        return (float) $this->delivery_fee;
    }

    public function calculateServiceFee(float $subtotal): float
    {
        if ($this->service_fee_percent <= 0) {
            return 0;
        }

        return round($subtotal * $this->service_fee_percent / 100, 2);
    }
}
