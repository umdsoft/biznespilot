<?php

namespace App\Models\Store;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StoreCart extends Model
{
    use HasUuids;

    protected $fillable = [
        'store_id',
        'session_id',
        'customer_id',
        'expires_at',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
    ];

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
        return $this->hasMany(StoreCartItem::class, 'cart_id');
    }

    public function isExpired(): bool
    {
        return $this->expires_at && $this->expires_at->isPast();
    }

    public function getSubtotal(): float
    {
        return $this->items->sum(fn ($item) => $item->price * $item->quantity);
    }

    public function getItemsCount(): int
    {
        return $this->items->sum('quantity');
    }

    public function clear(): void
    {
        $this->items()->delete();
    }
}
