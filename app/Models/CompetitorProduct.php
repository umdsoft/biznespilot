<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CompetitorProduct extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'competitor_id',
        'name',
        'description',
        'category',
        'subcategory',
        'sku',
        'url',
        'image_url',
        'current_price',
        'original_price',
        'discount_percent',
        'currency',
        'is_on_sale',
        'sale_label',
        'stock_status',
        'stock_quantity',
        'is_tracked',
        'source',
        'last_checked_at',
        'price_changed_at',
    ];

    protected $casts = [
        'current_price' => 'decimal:2',
        'original_price' => 'decimal:2',
        'discount_percent' => 'decimal:2',
        'stock_quantity' => 'integer',
        'is_on_sale' => 'boolean',
        'is_tracked' => 'boolean',
        'last_checked_at' => 'datetime',
        'price_changed_at' => 'datetime',
    ];

    public function competitor(): BelongsTo
    {
        return $this->belongsTo(Competitor::class);
    }

    public function priceHistory(): HasMany
    {
        return $this->hasMany(CompetitorPriceHistory::class, 'product_id');
    }

    /**
     * Get price change percentage from last record
     */
    public function getPriceChangePercentAttribute(): ?float
    {
        $lastPrice = $this->priceHistory()
            ->where('recorded_date', '<', today())
            ->latest('recorded_date')
            ->first();

        if (!$lastPrice || !$lastPrice->price || !$this->current_price) {
            return null;
        }

        return (($this->current_price - $lastPrice->price) / $lastPrice->price) * 100;
    }

    /**
     * Record current price to history
     */
    public function recordPrice(): CompetitorPriceHistory
    {
        return $this->priceHistory()->updateOrCreate(
            ['recorded_date' => today()],
            [
                'price' => $this->current_price,
                'original_price' => $this->original_price,
                'discount_percent' => $this->discount_percent,
                'is_on_sale' => $this->is_on_sale,
                'stock_status' => $this->stock_status,
                'currency' => $this->currency,
            ]
        );
    }

    /**
     * Scope for tracked products
     */
    public function scopeTracked($query)
    {
        return $query->where('is_tracked', true);
    }

    /**
     * Scope for products on sale
     */
    public function scopeOnSale($query)
    {
        return $query->where('is_on_sale', true);
    }

    /**
     * Scope for category
     */
    public function scopeCategory($query, string $category)
    {
        return $query->where('category', $category);
    }
}
