<?php

namespace App\Models\Store;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StoreLoyaltyRedemption extends Model
{
    use HasUuids;

    protected $table = 'store_loyalty_redemptions';

    const STATUS_PENDING = 'pending';
    const STATUS_APPLIED = 'applied';
    const STATUS_EXPIRED = 'expired';
    const STATUS_CANCELLED = 'cancelled';

    protected $fillable = [
        'reward_id', 'customer_id', 'transaction_id',
        'points_spent', 'status', 'coupon_code', 'expires_at',
    ];

    protected $casts = [
        'points_spent' => 'integer',
        'expires_at' => 'datetime',
    ];

    public function reward(): BelongsTo
    {
        return $this->belongsTo(StoreLoyaltyReward::class, 'reward_id');
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(StoreCustomer::class, 'customer_id');
    }

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(StoreLoyaltyTransaction::class, 'transaction_id');
    }
}
