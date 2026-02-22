<?php

namespace App\Models\Store;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class StoreReview extends Model
{
    use HasUuids;

    protected $fillable = [
        'store_id',
        'reviewable_type',
        'reviewable_id',
        'product_id',
        'customer_id',
        'order_id',
        'rating',
        'comment',
        'aspects',
        'is_approved',
    ];

    protected $casts = [
        'rating' => 'integer',
        'is_approved' => 'boolean',
        'aspects' => 'array',
    ];

    public function store(): BelongsTo
    {
        return $this->belongsTo(TelegramStore::class, 'store_id');
    }

    /**
     * Eski direct FK — backward compat
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(StoreProduct::class, 'product_id');
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(StoreCustomer::class, 'customer_id');
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(StoreOrder::class, 'order_id');
    }

    /**
     * Polimorfik relationship
     */
    public function reviewable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Hybrid accessor
     */
    public function getCatalogItem()
    {
        if ($this->reviewable_type && $this->reviewable_id) {
            return $this->reviewable;
        }

        return $this->product;
    }

    public function scopeApproved($query)
    {
        return $query->where('is_approved', true);
    }
}
