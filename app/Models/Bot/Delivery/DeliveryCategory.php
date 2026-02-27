<?php

namespace App\Models\Bot\Delivery;

use App\Traits\BelongsToBusiness;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class DeliveryCategory extends Model
{
    use BelongsToBusiness, HasUuids, SoftDeletes;

    protected $fillable = [
        'business_id', 'name', 'slug', 'icon', 'image_url',
        'sort_order', 'is_active', 'parent_id',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    public function menuItems(): HasMany
    {
        return $this->hasMany(DeliveryMenuItem::class, 'category_id');
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order');
    }

    public function scopeRoots($query)
    {
        return $query->whereNull('parent_id');
    }
}
