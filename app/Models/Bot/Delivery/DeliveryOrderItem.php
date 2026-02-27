<?php

namespace App\Models\Bot\Delivery;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DeliveryOrderItem extends Model
{
    use HasUuids;

    protected $fillable = [
        'order_id', 'menu_item_id', 'item_name', 'variant_name',
        'quantity', 'unit_price', 'addons', 'addons_total',
        'subtotal', 'special_instructions',
    ];

    protected $casts = [
        'unit_price' => 'decimal:2',
        'addons_total' => 'decimal:2',
        'subtotal' => 'decimal:2',
        'quantity' => 'integer',
        'addons' => 'array',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(DeliveryOrder::class, 'order_id');
    }

    public function menuItem(): BelongsTo
    {
        return $this->belongsTo(DeliveryMenuItem::class, 'menu_item_id');
    }
}
