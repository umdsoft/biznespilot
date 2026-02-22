<?php

namespace App\Models\Store;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StoreAnalyticsDaily extends Model
{
    use HasUuids;

    protected $table = 'store_analytics_daily';

    protected $fillable = [
        'store_id',
        'date',
        'views',
        'unique_visitors',
        'orders_count',
        'revenue',
        'avg_order_value',
        'new_customers',
        'returning_customers',
    ];

    protected $casts = [
        'date' => 'date',
        'views' => 'integer',
        'unique_visitors' => 'integer',
        'orders_count' => 'integer',
        'revenue' => 'decimal:2',
        'avg_order_value' => 'decimal:2',
        'new_customers' => 'integer',
        'returning_customers' => 'integer',
    ];

    public function store(): BelongsTo
    {
        return $this->belongsTo(TelegramStore::class, 'store_id');
    }
}
