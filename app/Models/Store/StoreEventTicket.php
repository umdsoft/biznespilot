<?php

namespace App\Models\Store;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StoreEventTicket extends Model
{
    use HasUuids;

    protected $table = 'store_event_tickets';

    protected $fillable = [
        'event_id',
        'name',
        'description',
        'price',
        'quantity',
        'sold_count',
        'sale_start',
        'sale_end',
        'max_per_order',
        'sort_order',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'quantity' => 'integer',
        'sold_count' => 'integer',
        'sale_start' => 'datetime',
        'sale_end' => 'datetime',
        'max_per_order' => 'integer',
        'sort_order' => 'integer',
    ];

    public function event(): BelongsTo
    {
        return $this->belongsTo(StoreEvent::class, 'event_id');
    }

    public function isAvailable(): bool
    {
        $now = now();

        if ($this->sale_start && $now->lt($this->sale_start)) {
            return false;
        }

        if ($this->sale_end && $now->gt($this->sale_end)) {
            return false;
        }

        return ($this->quantity - $this->sold_count) > 0;
    }
}
