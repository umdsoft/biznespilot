<?php

namespace App\Models\Bot\Delivery;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DeliveryItemVariant extends Model
{
    use HasUuids;

    protected $fillable = [
        'menu_item_id', 'group_name', 'name', 'price_modifier', 'is_default', 'sort_order',
    ];

    protected $casts = [
        'price_modifier' => 'decimal:2',
        'is_default' => 'boolean',
        'sort_order' => 'integer',
    ];

    public function menuItem(): BelongsTo
    {
        return $this->belongsTo(DeliveryMenuItem::class, 'menu_item_id');
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order');
    }
}
