<?php

namespace App\Models\Store;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class StoreOrderItem extends Model
{
    use HasUuids;

    protected $fillable = [
        'order_id',
        'product_id',
        'variant_id',
        'item_type',
        'item_id',
        'product_name',
        'variant_name',
        'price',
        'quantity',
        'total',
        'item_metadata',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'quantity' => 'integer',
        'total' => 'decimal:2',
        'item_metadata' => 'array',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(StoreOrder::class, 'order_id');
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
     * Polimorfik relationship — yangi catalog elementlar uchun
     */
    public function item(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Hybrid accessor — polimorfik mavjud bo'lsa uni, aks holda product_id ni qaytaradi
     */
    public function getCatalogItem()
    {
        if ($this->item_type && $this->item_id) {
            return $this->item;
        }

        return $this->product;
    }
}
