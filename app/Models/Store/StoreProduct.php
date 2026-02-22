<?php

namespace App\Models\Store;

use App\Contracts\Store\CatalogableInterface;
use App\Traits\HasPolymorphicCatalog;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class StoreProduct extends Model implements CatalogableInterface
{
    use HasPolymorphicCatalog, HasUuids;

    protected $fillable = [
        'store_id',
        'category_id',
        'name',
        'slug',
        'description',
        'price',
        'compare_price',
        'sku',
        'stock_quantity',
        'track_stock',
        'is_active',
        'is_featured',
        'sort_order',
        'metadata',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'compare_price' => 'decimal:2',
        'stock_quantity' => 'integer',
        'track_stock' => 'boolean',
        'is_active' => 'boolean',
        'is_featured' => 'boolean',
        'sort_order' => 'integer',
        'metadata' => 'array',
    ];

    // Relationships
    public function store(): BelongsTo
    {
        return $this->belongsTo(TelegramStore::class, 'store_id');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(StoreCategory::class, 'category_id');
    }

    public function images(): HasMany
    {
        return $this->hasMany(StoreProductImage::class, 'product_id')->orderBy('sort_order');
    }

    public function primaryImage(): HasOne
    {
        return $this->hasOne(StoreProductImage::class, 'product_id')->where('is_primary', true);
    }

    public function variants(): HasMany
    {
        return $this->hasMany(StoreProductVariant::class, 'product_id');
    }

    public function activeVariants(): HasMany
    {
        return $this->hasMany(StoreProductVariant::class, 'product_id')->where('is_active', true);
    }

    public function orderItems(): HasMany
    {
        return $this->hasMany(StoreOrderItem::class, 'product_id');
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(StoreReview::class, 'product_id');
    }

    public function approvedReviews(): HasMany
    {
        return $this->hasMany(StoreReview::class, 'product_id')->where('is_approved', true);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true);
    }

    public function scopeInStock($query)
    {
        return $query->where(function ($q) {
            $q->where('track_stock', false)
                ->orWhere('stock_quantity', '>', 0);
        });
    }

    // Helpers
    public function isInStock(): bool
    {
        if (!$this->track_stock) {
            return true;
        }

        return $this->stock_quantity > 0;
    }

    public function hasDiscount(): bool
    {
        return $this->compare_price && $this->compare_price > $this->price;
    }

    public function getDiscountPercent(): int
    {
        if (!$this->hasDiscount()) {
            return 0;
        }

        return (int) round((($this->compare_price - $this->price) / $this->compare_price) * 100);
    }

    public function getAverageRating(): float
    {
        return round($this->approvedReviews()->avg('rating') ?? 0, 1);
    }

    public function decrementStock(int $quantity): void
    {
        if ($this->track_stock) {
            $this->decrement('stock_quantity', $quantity);
        }
    }

    public function incrementStock(int $quantity): void
    {
        if ($this->track_stock) {
            $this->increment('stock_quantity', $quantity);
        }
    }

    // CatalogableInterface
    public function getCatalogName(): string
    {
        return $this->name;
    }

    public function getCatalogPrice(): float
    {
        return (float) $this->price;
    }

    public function getCatalogImage(): ?string
    {
        if ($this->relationLoaded('primaryImage') && $this->primaryImage) {
            return $this->primaryImage->image_url;
        }

        if ($this->relationLoaded('images') && $this->images->isNotEmpty()) {
            return $this->images->first()->image_url;
        }

        return null;
    }

    public function isAvailable(): bool
    {
        return $this->is_active && $this->isInStock();
    }

    public function getCatalogDescription(): ?string
    {
        return $this->description;
    }

    public function getCatalogAttributes(): array
    {
        return [
            'sku' => $this->sku,
            'stock_quantity' => $this->stock_quantity,
            'track_stock' => $this->track_stock,
            'compare_price' => $this->compare_price,
            'is_featured' => $this->is_featured,
            'has_discount' => $this->hasDiscount(),
            'discount_percent' => $this->getDiscountPercent(),
        ];
    }

    public function toSearchableArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'sku' => $this->sku,
            'price' => $this->price,
            'category' => $this->category?->name,
        ];
    }

    public function getCatalogType(): string
    {
        return 'product';
    }
}
