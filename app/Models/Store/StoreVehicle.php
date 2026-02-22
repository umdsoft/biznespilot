<?php

namespace App\Models\Store;

use App\Contracts\Store\CatalogableInterface;
use App\Traits\HasPolymorphicCatalog;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StoreVehicle extends Model implements CatalogableInterface
{
    use HasPolymorphicCatalog, HasUuids;

    protected $table = 'store_vehicles';

    protected $fillable = [
        'store_id',
        'category_id',
        'name',
        'slug',
        'description',
        'price',
        'brand',
        'model',
        'year',
        'mileage_km',
        'fuel_type',
        'transmission',
        'color',
        'engine_volume',
        'condition',
        'features',
        'is_active',
        'is_featured',
        'sort_order',
        'metadata',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'year' => 'integer',
        'mileage_km' => 'integer',
        'engine_volume' => 'decimal:1',
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
        return $this->hasMany(StoreVehicleImage::class, 'vehicle_id')->orderBy('sort_order');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true);
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
            'brand' => $this->brand,
            'model' => $this->model,
            'year' => $this->year,
            'mileage_km' => $this->mileage_km,
            'fuel_type' => $this->fuel_type,
            'transmission' => $this->transmission,
            'condition' => $this->condition,
        ];
    }

    public function toSearchableArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'price' => $this->price,
            'brand' => $this->brand,
            'model' => $this->model,
            'year' => $this->year,
            'fuel_type' => $this->fuel_type,
            'transmission' => $this->transmission,
            'condition' => $this->condition,
            'category' => $this->category?->name,
        ];
    }

    public function getCatalogType(): string
    {
        return 'vehicle';
    }
}
