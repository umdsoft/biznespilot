<?php

namespace App\Models\Store;

use App\Contracts\Store\CatalogableInterface;
use App\Traits\HasPolymorphicCatalog;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StoreMenuItem extends Model implements CatalogableInterface
{
    use HasPolymorphicCatalog, HasUuids;

    protected $table = 'store_menu_items';

    protected $fillable = [
        'store_id',
        'category_id',
        'name',
        'slug',
        'description',
        'price',
        'image_url',
        'preparation_time_minutes',
        'calories',
        'portion_size',
        'allergens',
        'dietary_tags',
        'is_active',
        'is_featured',
        'sort_order',
        'metadata',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'preparation_time_minutes' => 'integer',
        'calories' => 'integer',
        'allergens' => 'array',
        'dietary_tags' => 'array',
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

    public function modifiers(): HasMany
    {
        return $this->hasMany(StoreMenuModifier::class, 'menu_item_id')->orderBy('sort_order');
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
            'preparation_time_minutes' => $this->preparation_time_minutes,
            'calories' => $this->calories,
            'portion_size' => $this->portion_size,
            'allergens' => $this->allergens,
            'dietary_tags' => $this->dietary_tags,
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
            'allergens' => $this->allergens,
            'dietary_tags' => $this->dietary_tags,
        ];
    }

    public function getCatalogType(): string
    {
        return 'menu_item';
    }
}
