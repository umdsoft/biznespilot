<?php

namespace App\Models\Store;

use App\Models\User;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StoreOrderStatusHistory extends Model
{
    use HasUuids;

    protected $table = 'store_order_status_history';

    protected $fillable = [
        'order_id',
        'from_status',
        'to_status',
        'comment',
        'changed_by',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(StoreOrder::class, 'order_id');
    }

    public function changedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'changed_by');
    }
}
