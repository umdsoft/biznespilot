<?php

namespace App\Models\Store;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class StoreWishlist extends Model
{
    use HasUuids;

    protected $table = 'store_wishlists';

    protected $fillable = [
        'store_id',
        'customer_id',
        'wishlistable_type',
        'wishlistable_id',
    ];

    public function store(): BelongsTo
    {
        return $this->belongsTo(TelegramStore::class, 'store_id');
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(StoreCustomer::class, 'customer_id');
    }

    public function wishlistable(): MorphTo
    {
        return $this->morphTo();
    }
}
