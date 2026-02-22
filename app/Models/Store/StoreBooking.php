<?php

namespace App\Models\Store;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class StoreBooking extends Model
{
    use HasUuids;

    protected $table = 'store_bookings';

    const STATUS_PENDING = 'pending';
    const STATUS_CONFIRMED = 'confirmed';
    const STATUS_IN_PROGRESS = 'in_progress';
    const STATUS_COMPLETED = 'completed';
    const STATUS_CANCELLED = 'cancelled';
    const STATUS_NO_SHOW = 'no_show';

    protected $fillable = [
        'store_id',
        'customer_id',
        'order_id',
        'bookable_type',
        'bookable_id',
        'staff_id',
        'booked_at',
        'ends_at',
        'guests_count',
        'status',
        'notes',
        'cancel_reason',
        'metadata',
    ];

    protected $casts = [
        'booked_at' => 'datetime',
        'ends_at' => 'datetime',
        'guests_count' => 'integer',
        'metadata' => 'array',
    ];

    public function store(): BelongsTo
    {
        return $this->belongsTo(TelegramStore::class, 'store_id');
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(StoreCustomer::class, 'customer_id');
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(StoreOrder::class, 'order_id');
    }

    public function bookable(): MorphTo
    {
        return $this->morphTo();
    }

    public function staff(): BelongsTo
    {
        return $this->belongsTo(StoreStaff::class, 'staff_id');
    }

    public function scopeUpcoming(Builder $query): Builder
    {
        return $query->where('booked_at', '>', now());
    }

    public function scopeByStatus(Builder $query, string $status): Builder
    {
        return $query->where('status', $status);
    }
}
