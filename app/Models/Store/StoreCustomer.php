<?php

namespace App\Models\Store;

use App\Models\TelegramUser;
use App\Models\Store\StoreOrder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StoreCustomer extends Model
{
    use HasUuids;

    protected $fillable = [
        'store_id',
        'telegram_user_id',
        'name',
        'phone',
        'address',
        'orders_count',
        'total_spent',
        'last_order_at',
    ];

    protected $casts = [
        'address' => 'array',
        'orders_count' => 'integer',
        'total_spent' => 'decimal:2',
        'last_order_at' => 'datetime',
    ];

    public function store(): BelongsTo
    {
        return $this->belongsTo(TelegramStore::class, 'store_id');
    }

    public function telegramUser(): BelongsTo
    {
        return $this->belongsTo(TelegramUser::class, 'telegram_user_id');
    }

    public function orders(): HasMany
    {
        return $this->hasMany(StoreOrder::class, 'customer_id');
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(StoreReview::class, 'customer_id');
    }

    public function getDisplayName(): string
    {
        if ($this->name) {
            return $this->name;
        }

        if ($this->telegramUser) {
            return $this->telegramUser->first_name . ' ' . ($this->telegramUser->last_name ?? '');
        }

        return $this->phone ?? 'Noma\'lum';
    }

    public function updateStats(): void
    {
        $this->update([
            'orders_count' => $this->orders()->whereNotIn('status', StoreOrder::TERMINAL_STATUSES)->count(),
            'total_spent' => $this->orders()->where('payment_status', StoreOrder::PAYMENT_PAID)->sum('total'),
            'last_order_at' => $this->orders()->latest()->value('created_at'),
        ]);
    }
}
