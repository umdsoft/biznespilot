<?php

namespace App\Models\Store;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StoreModifierOption extends Model
{
    use HasUuids;

    protected $table = 'store_modifier_options';

    protected $fillable = [
        'modifier_id',
        'name',
        'price',
        'is_default',
        'sort_order',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'is_default' => 'boolean',
        'sort_order' => 'integer',
    ];

    public function modifier(): BelongsTo
    {
        return $this->belongsTo(StoreMenuModifier::class, 'modifier_id');
    }
}
