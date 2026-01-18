<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SalesChannel extends Model
{
    use HasFactory;

    protected $fillable = [
        'business_id',
        'code',
        'name',
        'type',
        'icon',
        'commission_percent',
        'sort_order',
        'is_active',
    ];

    protected $casts = [
        'commission_percent' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    /**
     * Get the business that owns the channel
     */
    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    /**
     * Scope for active channels
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope for global channels
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
     * Get type label in Uzbek
     */
    public function getTypeLabelAttribute(): string
    {
        $labels = [
            'retail' => 'Chakana',
            'online' => 'Online',
            'wholesale' => 'Ulgurji',
            'agent' => 'Agent',
            'b2b' => 'B2B',
        ];

        return $labels[$this->type] ?? $this->type;
    }
}
