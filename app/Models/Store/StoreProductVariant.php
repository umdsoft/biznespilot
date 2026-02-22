<?php

namespace App\Models\Store;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StoreProductVariant extends Model
{
    use HasUuids;

    protected $fillable = [
        'product_id',
        'name',
        'sku',
        'price',
        'stock_quantity',
        'attributes',
        'is_active',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'stock_quantity' => 'integer',
        'attributes' => 'array',
        'is_active' => 'boolean',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(StoreProduct::class, 'product_id');
    }

    public function isInStock(): bool
    {
        if (! $this->product->track_stock) {
            return true;
        }

        return $this->stock_quantity > 0;
    }

    public function decrementStock(int $quantity): void
    {
        if ($this->product->track_stock) {
            $this->decrement('stock_quantity', $quantity);
        }
    }
}
