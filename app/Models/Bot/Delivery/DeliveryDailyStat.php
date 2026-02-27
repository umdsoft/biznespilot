<?php

namespace App\Models\Bot\Delivery;

use App\Traits\BelongsToBusiness;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class DeliveryDailyStat extends Model
{
    use BelongsToBusiness, HasUuids;

    protected $fillable = [
        'business_id', 'date', 'total_orders', 'completed_orders',
        'cancelled_orders', 'total_revenue', 'avg_order_value',
        'avg_delivery_time', 'top_items',
    ];

    protected $casts = [
        'date' => 'date',
        'total_orders' => 'integer',
        'completed_orders' => 'integer',
        'cancelled_orders' => 'integer',
        'total_revenue' => 'decimal:2',
        'avg_order_value' => 'decimal:2',
        'avg_delivery_time' => 'integer',
        'top_items' => 'array',
    ];
}
