<?php

namespace App\Models\Store;

use App\Contracts\Store\CatalogableInterface;
use App\Traits\HasPolymorphicCatalog;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StoreEvent extends Model implements CatalogableInterface
{
    use HasPolymorphicCatalog, HasUuids;

    protected $table = 'store_events';

    protected $fillable = [
        'store_id',
        'category_id',
        'name',
        'slug',
        'description',
        'image_url',
        'venue',
        'address',
        'latitude',
        'longitude',
        'start_date',
        'end_date',
        'total_seats',
        'sold_seats',
        'is_active',
        'is_featured',
        'sort_order',
        'metadata',
    ];

    protected $casts = [
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:8',
        'start_date' => 'datetime',
        'end_date' => 'datetime',
        'total_seats' => 'integer',
        'sold_seats' => 'integer',
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

    public function tickets(): HasMany
    {
        return $this->hasMany(StoreEventTicket::class, 'event_id');
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
    public function hasAvailableSeats(): bool
    {
        if (is_null($this->total_seats)) {
            return true;
        }

        return $this->sold_seats < $this->total_seats;
    }

    public function isAvailable(): bool
    {
        return $this->is_active && $this->hasAvailableSeats();
    }

    // CatalogableInterface
    public function getCatalogName(): string
    {
        return $this->name;
    }

    public function getCatalogPrice(): float
    {
        if ($this->relationLoaded('tickets') && $this->tickets->isNotEmpty()) {
            return (float) $this->tickets->first()->price;
        }

        return 0;
    }

    public function getCatalogImage(): ?string
    {
        return $this->image_url;
    }

    public function getCatalogDescription(): ?string
    {
        return $this->description;
    }

    public function getCatalogAttributes(): array
    {
        return [
            'venue' => $this->venue,
            'address' => $this->address,
            'start_date' => $this->start_date?->toDateTimeString(),
            'end_date' => $this->end_date?->toDateTimeString(),
            'total_seats' => $this->total_seats,
            'sold_seats' => $this->sold_seats,
        ];
    }

    public function toSearchableArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'venue' => $this->venue,
            'address' => $this->address,
            'start_date' => $this->start_date?->toDateTimeString(),
            'category' => $this->category?->name,
        ];
    }

    public function getCatalogType(): string
    {
        return 'event';
    }
}
