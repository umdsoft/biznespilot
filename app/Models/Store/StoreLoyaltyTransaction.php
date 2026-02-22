<?php

namespace App\Models\Store;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StoreLoyaltyTransaction extends Model
{
    use HasUuids;

    protected $table = 'store_loyalty_transactions';

    const TYPE_EARN = 'earn';
    const TYPE_REDEEM = 'redeem';
    const TYPE_ADJUST = 'adjust';
    const TYPE_EXPIRE = 'expire';

    protected $fillable = [
        'program_id', 'customer_id', 'type', 'points',
        'balance_after', 'description', 'order_id',
    ];

    protected $casts = [
        'points' => 'integer',
        'balance_after' => 'integer',
    ];

    public function program(): BelongsTo
    {
        return $this->belongsTo(StoreLoyaltyProgram::class, 'program_id');
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(StoreCustomer::class, 'customer_id');
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(StoreOrder::class, 'order_id');
    }
}
