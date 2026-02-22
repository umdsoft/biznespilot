<?php

namespace App\Models\Store;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StoreLoyaltyProgram extends Model
{
    use HasUuids;

    protected $table = 'store_loyalty_programs';

    protected $fillable = [
        'store_id', 'name', 'description', 'points_per_currency',
        'currency_per_point', 'is_active', 'settings',
    ];

    protected $casts = [
        'points_per_currency' => 'decimal:2',
        'currency_per_point' => 'decimal:2',
        'is_active' => 'boolean',
        'settings' => 'array',
    ];

    public function store(): BelongsTo
    {
        return $this->belongsTo(TelegramStore::class, 'store_id');
    }

    public function tiers(): HasMany
    {
        return $this->hasMany(StoreLoyaltyTier::class, 'program_id')->orderBy('min_points');
    }

    public function rewards(): HasMany
    {
        return $this->hasMany(StoreLoyaltyReward::class, 'program_id');
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(StoreLoyaltyTransaction::class, 'program_id');
    }
}
