<?php

namespace App\Models;

use App\Traits\BelongsToBusiness;
use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Biznes xotirasi modeli (2-qatlam).
 * Agent qarorlari, foydalanuvchi afzalliklari, biznes holati.
 */
class AgentBusinessContext extends Model
{
    use BelongsToBusiness, HasUuid;

    protected $table = 'agent_business_context';

    protected $fillable = [
        'business_id',
        'context_type',
        'context_key',
        'context_value',
        'expires_at',
    ];

    protected $casts = [
        'context_value' => 'array',
        'expires_at' => 'datetime',
    ];

    /**
     * Muddati o'tmagan kontekstlarni olish
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where(function ($q) {
            $q->whereNull('expires_at')
              ->orWhere('expires_at', '>', now());
        });
    }

    /**
     * Kontekst turini filtrlash
     */
    public function scopeOfType(Builder $query, string $type): Builder
    {
        return $query->where('context_type', $type);
    }

    /**
     * Kalitga qarab kontekstni olish
     */
    public static function getValue(string $businessId, string $key): ?array
    {
        $context = static::forBusiness($businessId)
            ->where('context_key', $key)
            ->active()
            ->latest()
            ->first();

        return $context?->context_value;
    }

    /**
     * Kontekst saqlash yoki yangilash
     */
    public static function setValue(
        string $businessId,
        string $type,
        string $key,
        array $value,
        ?\DateTimeInterface $expiresAt = null,
    ): self {
        return static::updateOrCreate(
            [
                'business_id' => $businessId,
                'context_key' => $key,
            ],
            [
                'context_type' => $type,
                'context_value' => $value,
                'expires_at' => $expiresAt,
            ],
        );
    }
}
