<?php

namespace App\Models\Store;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StoreMenuModifier extends Model
{
    use HasUuids;

    protected $table = 'store_menu_modifiers';

    protected $fillable = [
        'menu_item_id',
        'name',
        'type',
        'is_required',
        'min_selections',
        'max_selections',
        'sort_order',
    ];

    protected $casts = [
        'is_required' => 'boolean',
        'min_selections' => 'integer',
        'max_selections' => 'integer',
        'sort_order' => 'integer',
    ];

    public function menuItem(): BelongsTo
    {
        return $this->belongsTo(StoreMenuItem::class, 'menu_item_id');
    }

    public function options(): HasMany
    {
        return $this->hasMany(StoreModifierOption::class, 'modifier_id')->orderBy('sort_order');
    }
}
