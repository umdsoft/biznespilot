<?php

namespace App\Models\Store;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StorePaymentTransaction extends Model
{
    use HasUuids;

    const STATUS_PENDING = 'pending';
    const STATUS_PROCESSING = 'processing';
    const STATUS_COMPLETED = 'completed';
    const STATUS_CANCELLED = 'cancelled';
    const STATUS_FAILED = 'failed';
    const STATUS_REFUNDED = 'refunded';

    const PROVIDER_PAYME = 'payme';
    const PROVIDER_CLICK = 'click';
    const PROVIDER_CASH = 'cash';

    protected $fillable = [
        'store_id',
        'order_id',
        'provider',
        'provider_transaction_id',
        'amount',
        'currency',
        'status',
        'metadata',
        'paid_at',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'metadata' => 'array',
        'paid_at' => 'datetime',
    ];

    public function store(): BelongsTo
    {
        return $this->belongsTo(TelegramStore::class, 'store_id');
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(StoreOrder::class, 'order_id');
    }

    public function markCompleted(?string $providerTransactionId = null): void
    {
        $this->update([
            'status' => self::STATUS_COMPLETED,
            'provider_transaction_id' => $providerTransactionId ?? $this->provider_transaction_id,
            'paid_at' => now(),
        ]);
    }

    public function markFailed(): void
    {
        $this->update(['status' => self::STATUS_FAILED]);
    }

    public function markCancelled(): void
    {
        $this->update(['status' => self::STATUS_CANCELLED]);
    }

    public function getAmountInTiyin(): int
    {
        return (int) ($this->amount * 100);
    }

    public function isPayme(): bool
    {
        return $this->provider === self::PROVIDER_PAYME;
    }

    public function isClick(): bool
    {
        return $this->provider === self::PROVIDER_CLICK;
    }

    public function isCash(): bool
    {
        return $this->provider === self::PROVIDER_CASH;
    }
}
