<?php

namespace App\Models\Store;

use App\Contracts\Store\CatalogableInterface;
use App\Traits\HasPolymorphicCatalog;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StoreServiceRequest extends Model implements CatalogableInterface
{
    use HasPolymorphicCatalog, HasUuids;

    protected $table = 'store_service_requests';

    protected $fillable = [
        'store_id',
        'category_id',
        'name',
        'slug',
        'description',
        'image_url',
        'base_price',
        'pricing_type',
        'pricing_unit',
        'min_order_amount',
        'estimated_minutes',
        'required_fields',
        'requires_address',
        'is_active',
        'is_featured',
        'sort_order',
        'metadata',
    ];

    protected $casts = [
        'base_price' => 'decimal:2',
        'min_order_amount' => 'integer',
        'estimated_minutes' => 'integer',
        'required_fields' => 'array',
        'requires_address' => 'boolean',
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

    // CatalogableInterface
    public function getCatalogName(): string
    {
        return $this->name;
    }

    public function getCatalogPrice(): float
    {
        return (float) $this->base_price;
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
            'base_price' => $this->base_price,
            'pricing_type' => $this->pricing_type,
            'pricing_unit' => $this->pricing_unit,
            'min_order_amount' => $this->min_order_amount,
            'estimated_minutes' => $this->estimated_minutes,
            'required_fields' => $this->required_fields,
            'requires_address' => $this->requires_address,
        ];
    }

    public function toSearchableArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'base_price' => $this->base_price,
            'pricing_type' => $this->pricing_type,
            'category' => $this->category?->name,
        ];
    }

    public function getCatalogType(): string
    {
        return 'service_request';
    }
}
