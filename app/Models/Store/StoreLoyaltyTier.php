<?php

namespace App\Models\Store;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StoreLoyaltyTier extends Model
{
    use HasUuids;

    protected $table = 'store_loyalty_tiers';

    protected $fillable = [
        'program_id', 'name', 'min_points', 'multiplier',
        'discount_percent', 'perks', 'sort_order',
    ];

    protected $casts = [
        'min_points' => 'integer',
        'multiplier' => 'decimal:2',
        'discount_percent' => 'decimal:2',
        'perks' => 'array',
        'sort_order' => 'integer',
    ];

    public function program(): BelongsTo
    {
        return $this->belongsTo(StoreLoyaltyProgram::class, 'program_id');
    }
}
