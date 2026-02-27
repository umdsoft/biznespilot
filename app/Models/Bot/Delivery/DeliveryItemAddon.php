<?php

namespace App\Models\Bot\Delivery;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DeliveryItemAddon extends Model
{
    use HasUuids;

    protected $fillable = [
        'menu_item_id', 'name', 'price', 'is_available', 'sort_order',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'is_available' => 'boolean',
        'sort_order' => 'integer',
    ];

    public function menuItem(): BelongsTo
    {
        return $this->belongsTo(DeliveryMenuItem::class, 'menu_item_id');
    }
}
