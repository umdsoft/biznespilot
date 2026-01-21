<?php

namespace App\Models;

use App\Traits\BelongsToBusiness;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Rejection Reasons - Track why deals are lost
 * This data feeds back to Marketing for improvement
 */
class RejectionReason extends Model
{
    use HasUuids, BelongsToBusiness;

    protected $fillable = [
        'business_id',
        'name',
        'category', // price, competition, timing, quality, other
        'description',
        'count',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    // Relationships
    public function lostDeals(): HasMany
    {
        return $this->hasMany(LostDeal::class);
    }

    // Get category label
    public function getCategoryLabelAttribute(): string
    {
        return match($this->category) {
            'price' => 'Narx',
            'competition' => 'Raqobatchi',
            'timing' => 'Vaqt',
            'quality' => 'Sifat',
            'other' => 'Boshqa',
            default => $this->category ?? 'Noma\'lum',
        };
    }

    // Get category color
    public function getCategoryColorAttribute(): string
    {
        return match($this->category) {
            'price' => 'orange',
            'competition' => 'red',
            'timing' => 'blue',
            'quality' => 'yellow',
            'other' => 'gray',
            default => 'gray',
        };
    }

    // Increment usage count
    public function incrementCount(): void
    {
        $this->increment('count');
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByCategory($query, string $category)
    {
        return $query->where('category', $category);
    }

    public function scopeMostUsed($query, int $limit = 10)
    {
        return $query->orderByDesc('count')->limit($limit);
    }
}
