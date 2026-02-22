<?php

namespace App\Models\Store;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StoreCustomerAddress extends Model
{
    use HasUuids;

    protected $table = 'store_customer_addresses';

    protected $fillable = [
        'customer_id', 'label', 'full_address', 'city', 'district',
        'street', 'building', 'apartment', 'entrance', 'floor',
        'latitude', 'longitude', 'instructions', 'is_default',
    ];

    protected $casts = [
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:8',
        'is_default' => 'boolean',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(StoreCustomer::class, 'customer_id');
    }
}
