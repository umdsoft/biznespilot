<?php

namespace App\Models\Store;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StoreLoyaltyReward extends Model
{
    use HasUuids;

    protected $table = 'store_loyalty_rewards';

    protected $fillable = [
        'program_id', 'name', 'description', 'image_url',
        'points_required', 'reward_type', 'reward_value',
        'stock', 'is_active', 'sort_order',
    ];

    protected $casts = [
        'points_required' => 'integer',
        'reward_value' => 'decimal:2',
        'stock' => 'integer',
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    public function program(): BelongsTo
    {
        return $this->belongsTo(StoreLoyaltyProgram::class, 'program_id');
    }

    public function redemptions(): HasMany
    {
        return $this->hasMany(StoreLoyaltyRedemption::class, 'reward_id');
    }
}
