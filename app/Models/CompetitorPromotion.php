<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CompetitorPromotion extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'competitor_id',
        'title',
        'description',
        'promo_type',
        'discount_value',
        'discount_type',
        'promo_code',
        'start_date',
        'end_date',
        'is_active',
        'detected_from',
        'source_url',
        'affected_categories',
        'affected_products',
    ];

    protected $casts = [
        'discount_value' => 'decimal:2',
        'start_date' => 'date',
        'end_date' => 'date',
        'is_active' => 'boolean',
        'affected_categories' => 'array',
        'affected_products' => 'array',
    ];

    public function competitor(): BelongsTo
    {
        return $this->belongsTo(Competitor::class);
    }

    /**
     * Check if promotion is currently active
     */
    public function isCurrentlyActive(): bool
    {
        if (! $this->is_active) {
            return false;
        }

        $today = now()->startOfDay();

        if ($this->start_date && $this->start_date > $today) {
            return false;
        }
        if ($this->end_date && $this->end_date < $today) {
            return false;
        }

        return true;
    }

    /**
     * Get discount display text
     */
    public function getDiscountDisplayAttribute(): ?string
    {
        if (! $this->discount_value) {
            return null;
        }

        if ($this->discount_type === 'percent') {
            return "-{$this->discount_value}%";
        }

        return "-{$this->discount_value} {$this->currency}";
    }

    /**
     * Scope for active promotions
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true)
            ->where(function ($q) {
                $q->whereNull('end_date')
                    ->orWhere('end_date', '>=', today());
            });
    }

    /**
     * Scope for promo type
     */
    public function scopeType($query, string $type)
    {
        return $query->where('promo_type', $type);
    }
}
