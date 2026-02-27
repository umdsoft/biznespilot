<?php

namespace App\Models\Bot\Delivery;

use App\Traits\BelongsToBusiness;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class DeliveryMenuItem extends Model
{
    use BelongsToBusiness, HasUuids, SoftDeletes;

    protected $fillable = [
        'business_id', 'category_id', 'name', 'slug', 'description',
        'image_url', 'base_price', 'sale_price', 'preparation_time',
        'calories', 'is_popular', 'is_available', 'sort_order',
        'rating_avg', 'rating_count',
    ];

    protected $casts = [
        'base_price' => 'decimal:2',
        'sale_price' => 'decimal:2',
        'rating_avg' => 'decimal:2',
        'preparation_time' => 'integer',
        'calories' => 'integer',
        'rating_count' => 'integer',
        'is_popular' => 'boolean',
        'is_available' => 'boolean',
        'sort_order' => 'integer',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(DeliveryCategory::class, 'category_id');
    }

    public function variants(): HasMany
    {
        return $this->hasMany(DeliveryItemVariant::class, 'menu_item_id');
    }

    public function addons(): HasMany
    {
        return $this->hasMany(DeliveryItemAddon::class, 'menu_item_id');
    }

    public function orderItems(): HasMany
    {
        return $this->hasMany(DeliveryOrderItem::class, 'menu_item_id');
    }

    public function getEffectivePriceAttribute(): float
    {
        return $this->sale_price ?? $this->base_price;
    }

    public function hasDiscount(): bool
    {
        return $this->sale_price !== null && $this->sale_price < $this->base_price;
    }

    public function scopeAvailable($query)
    {
        return $query->where('is_available', true);
    }

    public function scopePopular($query)
    {
        return $query->where('is_popular', true);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order');
    }
}
