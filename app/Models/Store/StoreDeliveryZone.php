<?php

namespace App\Models\Store;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StoreDeliveryZone extends Model
{
    use HasUuids;

    protected $fillable = [
        'store_id',
        'name',
        'delivery_fee',
        'min_order_amount',
        'estimated_time',
        'is_active',
    ];

    protected $casts = [
        'delivery_fee' => 'decimal:2',
        'min_order_amount' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    public function store(): BelongsTo
    {
        return $this->belongsTo(TelegramStore::class, 'store_id');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
