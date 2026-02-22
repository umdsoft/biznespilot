<?php

namespace App\Models\Store;

use App\Contracts\Store\CatalogableInterface;
use App\Traits\HasPolymorphicCatalog;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StoreTour extends Model implements CatalogableInterface
{
    use HasPolymorphicCatalog, HasUuids;

    protected $table = 'store_tours';

    protected $fillable = [
        'store_id',
        'category_id',
        'name',
        'slug',
        'description',
        'image_url',
        'price',
        'compare_price',
        'duration_days',
        'destination',
        'departure_city',
        'start_date',
        'end_date',
        'max_travelers',
        'booked_count',
        'included',
        'not_included',
        'difficulty',
        'is_active',
        'is_featured',
        'sort_order',
        'metadata',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'compare_price' => 'decimal:2',
        'duration_days' => 'integer',
        'start_date' => 'date',
        'end_date' => 'date',
        'max_travelers' => 'integer',
        'booked_count' => 'integer',
        'included' => 'array',
        'not_included' => 'array',
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

    public function days(): HasMany
    {
        return $this->hasMany(StoreTourDay::class, 'tour_id')->orderBy('day_number');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true);
    }

    // Helpers
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

    public function hasAvailableSpots(): bool
    {
        if (is_null($this->max_travelers)) {
            return true;
        }

        return $this->booked_count < $this->max_travelers;
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
        return $this->image_url;
    }

    public function isAvailable(): bool
    {
        return $this->is_active && $this->hasAvailableSpots();
    }

    public function getCatalogDescription(): ?string
    {
        return $this->description;
    }

    public function getCatalogAttributes(): array
    {
        return [
            'duration_days' => $this->duration_days,
            'destination' => $this->destination,
            'departure_city' => $this->departure_city,
            'start_date' => $this->start_date?->toDateString(),
            'end_date' => $this->end_date?->toDateString(),
            'max_travelers' => $this->max_travelers,
            'booked_count' => $this->booked_count,
            'difficulty' => $this->difficulty,
            'compare_price' => $this->compare_price,
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
            'price' => $this->price,
            'destination' => $this->destination,
            'departure_city' => $this->departure_city,
            'difficulty' => $this->difficulty,
            'category' => $this->category?->name,
        ];
    }

    public function getCatalogType(): string
    {
        return 'tour';
    }
}
