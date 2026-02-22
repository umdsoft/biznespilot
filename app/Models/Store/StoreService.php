<?php

namespace App\Models\Store;

use App\Contracts\Store\CatalogableInterface;
use App\Traits\HasPolymorphicCatalog;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StoreService extends Model implements CatalogableInterface
{
    use HasPolymorphicCatalog, HasUuids;

    protected $table = 'store_services';

    protected $fillable = [
        'store_id',
        'category_id',
        'name',
        'slug',
        'description',
        'price',
        'compare_price',
        'image_url',
        'duration_minutes',
        'max_capacity',
        'requires_staff',
        'is_active',
        'is_featured',
        'sort_order',
        'metadata',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'compare_price' => 'decimal:2',
        'duration_minutes' => 'integer',
        'max_capacity' => 'integer',
        'requires_staff' => 'boolean',
        'is_active' => 'boolean',
        'is_featured' => 'boolean',
        'sort_order' => 'integer',
        'metadata' => 'array',
    ];

    public function store(): BelongsTo
    {
        return $this->belongsTo(TelegramStore::class, 'store_id');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(StoreCategory::class, 'category_id');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true);
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
        return $this->image_url;
    }

    public function isAvailable(): bool
    {
        return (bool) $this->is_active;
    }

    public function getCatalogDescription(): ?string
    {
        return $this->description;
    }

    public function getCatalogAttributes(): array
    {
        return [
            'duration_minutes' => $this->duration_minutes,
            'max_capacity' => $this->max_capacity,
            'requires_staff' => $this->requires_staff,
        ];
    }

    public function toSearchableArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'price' => $this->price,
            'category' => $this->category?->name,
        ];
    }

    public function getCatalogType(): string
    {
        return 'service';
    }
}
