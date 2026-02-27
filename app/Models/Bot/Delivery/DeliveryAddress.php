<?php

namespace App\Models\Bot\Delivery;

use App\Traits\BelongsToBusiness;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class DeliveryAddress extends Model
{
    use BelongsToBusiness, HasUuids;

    protected $fillable = [
        'business_id', 'telegram_user_id', 'label', 'address',
        'landmark', 'lat', 'lng', 'is_default',
    ];

    protected $casts = [
        'telegram_user_id' => 'integer',
        'lat' => 'decimal:8',
        'lng' => 'decimal:8',
        'is_default' => 'boolean',
    ];

    public function scopeByUser($query, int $telegramUserId)
    {
        return $query->where('telegram_user_id', $telegramUserId);
    }

    public function scopeDefault($query)
    {
        return $query->where('is_default', true);
    }
}
