<?php

namespace App\Models\Store;

use App\Contracts\Store\CatalogableInterface;
use App\Traits\HasPolymorphicCatalog;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StoreProperty extends Model implements CatalogableInterface
{
    use HasPolymorphicCatalog, HasUuids;

    protected $table = 'store_properties';

    protected $fillable = [
        'store_id',
        'category_id',
        'name',
        'slug',
        'description',
        'price',
        'price_type',
        'area_sqm',
        'rooms',
        'bedrooms',
        'bathrooms',
        'floor',
        'total_floors',
        'address',
        'latitude',
        'longitude',
        'district',
        'city',
        'features',
        'is_active',
        'is_featured',
        'sort_order',
        'metadata',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'area_sqm' => 'decimal:2',
        'rooms' => 'integer',
        'bedrooms' => 'integer',
        'bathrooms' => 'integer',
        'floor' => 'integer',
        'total_floors' => 'integer',
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:8',
        'features' => 'array',
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

    public function images(): HasMany
    {
        return $this->hasMany(StorePropertyImage::class, 'property_id')->orderBy('sort_order');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true);
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
        if ($this->relationLoaded('images') && $this->images->isNotEmpty()) {
            $primary = $this->images->firstWhere('is_primary', true);

            return $primary ? $primary->image_url : $this->images->first()->image_url;
        }

        return null;
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
            'rooms' => $this->rooms,
            'area_sqm' => $this->area_sqm,
            'price_type' => $this->price_type,
            'address' => $this->address,
            'district' => $this->district,
        ];
    }

    public function toSearchableArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'price' => $this->price,
            'price_type' => $this->price_type,
            'rooms' => $this->rooms,
            'area_sqm' => $this->area_sqm,
            'address' => $this->address,
            'district' => $this->district,
            'city' => $this->city,
            'category' => $this->category?->name,
        ];
    }

    public function getCatalogType(): string
    {
        return 'property';
    }
}
