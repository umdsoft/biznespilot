<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LeadSource extends Model
{
    use HasFactory;

    protected $fillable = [
        'business_id',
        'code',
        'name',
        'category',
        'icon',
        'color',
        'is_paid',
        'default_cost',
        'is_trackable',
        'sort_order',
        'is_active',
    ];

    protected $casts = [
        'is_paid' => 'boolean',
        'default_cost' => 'decimal:2',
        'is_trackable' => 'boolean',
        'is_active' => 'boolean',
    ];

    /**
     * Get the business that owns the lead source
     */
    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    /**
     * Get daily source details
     */
    public function dailyDetails(): HasMany
    {
        return $this->hasMany(KpiDailySourceDetail::class);
    }

    /**
     * Scope for active sources
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope for global sources (business_id is null)
     */
    public function scopeGlobal($query)
    {
        return $query->whereNull('business_id');
    }

    /**
     * Scope for specific business or global
     */
    public function scopeForBusiness($query, $businessId)
    {
        return $query->where(function ($q) use ($businessId) {
            $q->whereNull('business_id')
                ->orWhere('business_id', $businessId);
        });
    }

    /**
     * Scope by category
     */
    public function scopeCategory($query, string $category)
    {
        return $query->where('category', $category);
    }

    /**
     * Scope for digital sources
     */
    public function scopeDigital($query)
    {
        return $query->where('category', 'digital');
    }

    /**
     * Scope for offline sources
     */
    public function scopeOffline($query)
    {
        return $query->where('category', 'offline');
    }

    /**
     * Scope for referral sources
     */
    public function scopeReferral($query)
    {
        return $query->where('category', 'referral');
    }

    /**
     * Scope for paid sources
     */
    public function scopePaid($query)
    {
        return $query->where('is_paid', true);
    }

    /**
     * Get category label in Uzbek
     */
    public function getCategoryLabelAttribute(): string
    {
        $labels = [
            'digital' => 'Digital',
            'offline' => 'Offline',
            'referral' => 'Tavsiya',
            'organic' => 'Organik',
        ];

        return $labels[$this->category] ?? $this->category;
    }
}
