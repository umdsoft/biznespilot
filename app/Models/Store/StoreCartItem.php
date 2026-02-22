<?php

namespace App\Models\Store;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class StoreCartItem extends Model
{
    use HasUuids;

    protected $fillable = [
        'cart_id',
        'product_id',
        'variant_id',
        'item_type',
        'item_id',
        'quantity',
        'price',
        'selections',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'price' => 'decimal:2',
        'selections' => 'array',
    ];

    public function cart(): BelongsTo
    {
        return $this->belongsTo(StoreCart::class, 'cart_id');
    }

    /**
     * Eski direct FK — backward compat
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(StoreProduct::class, 'product_id');
    }

    public function variant(): BelongsTo
    {
        return $this->belongsTo(StoreProductVariant::class, 'variant_id');
    }

    /**
     * Polimorfik relationship
     */
    public function item(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Hybrid accessor
     */
    public function getCatalogItem()
    {
        if ($this->item_type && $this->item_id) {
            return $this->item;
        }

        return $this->product;
    }

    public function getTotal(): float
    {
        return $this->price * $this->quantity;
    }
}
