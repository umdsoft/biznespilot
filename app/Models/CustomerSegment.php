<?php

namespace App\Models;

use App\Traits\BelongsToBusiness;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

/**
 * Customer Segments for targeted marketing
 */
class CustomerSegment extends Model
{
    use HasUuids, BelongsToBusiness;

    protected $fillable = [
        'business_id',
        'name',
        'description',
        // Demographics
        'age_range',
        'income_level',
        'location',
        // Behavior
        'preferred_channel',
        'purchase_motivation',
        'average_order_value',
        'purchase_frequency',
        // Contribution
        'revenue_share_percent',
        'customer_count',
        // Display
        'color',
        'is_active',
    ];

    protected $casts = [
        'average_order_value' => 'decimal:2',
        'revenue_share_percent' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    // Get income level label
    public function getIncomeLevelLabelAttribute(): string
    {
        return match($this->income_level) {
            'low' => 'Past',
            'medium' => 'O\'rta',
            'high' => 'Yuqori',
            default => $this->income_level ?? 'Noma\'lum',
        };
    }

    // Get purchase motivation label
    public function getPurchaseMotivationLabelAttribute(): string
    {
        return match($this->purchase_motivation) {
            'quality' => 'Sifat',
            'price' => 'Narx',
            'status' => 'Status',
            'convenience' => 'Qulaylik',
            default => $this->purchase_motivation ?? 'Noma\'lum',
        };
    }

    // Get purchase frequency label
    public function getPurchaseFrequencyLabelAttribute(): string
    {
        $freq = $this->purchase_frequency;

        if ($freq === null) return 'Noma\'lum';
        if ($freq >= 12) return 'Oylik+';
        if ($freq >= 4) return 'Choraklik';
        if ($freq >= 2) return 'Yarim yillik';
        if ($freq >= 1) return 'Yillik';
        return 'Kam';
    }

    // Calculate total revenue contribution
    public function getEstimatedRevenueAttribute(): float
    {
        return $this->average_order_value * $this->purchase_frequency * $this->customer_count;
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeHighValue($query)
    {
        return $query->where('revenue_share_percent', '>=', 20);
    }

    public function scopeByMotivation($query, string $motivation)
    {
        return $query->where('purchase_motivation', $motivation);
    }

    public function scopeByChannel($query, string $channel)
    {
        return $query->where('preferred_channel', $channel);
    }
}
