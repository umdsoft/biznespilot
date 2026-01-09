<?php

namespace App\Models;

use App\Traits\BelongsToBusiness;
use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SmsDailyStat extends Model
{
    use BelongsToBusiness, HasUuid;

    protected $fillable = [
        'business_id',
        'stat_date',
        'total_sent',
        'delivered',
        'failed',
        'pending',
        'parts_used',
    ];

    protected $casts = [
        'stat_date' => 'date',
    ];

    /**
     * Get the business that owns this stat
     */
    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    /**
     * Calculate delivery rate percentage
     */
    public function getDeliveryRateAttribute(): float
    {
        if ($this->total_sent === 0) {
            return 0;
        }

        return round(($this->delivered / $this->total_sent) * 100, 1);
    }

    /**
     * Calculate failure rate percentage
     */
    public function getFailureRateAttribute(): float
    {
        if ($this->total_sent === 0) {
            return 0;
        }

        return round(($this->failed / $this->total_sent) * 100, 1);
    }

    /**
     * Scope: Get stats for a specific date range
     */
    public function scopeDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('stat_date', [$startDate, $endDate]);
    }

    /**
     * Scope: Get today's stats
     */
    public function scopeToday($query)
    {
        return $query->where('stat_date', today());
    }

    /**
     * Scope: Get this month's stats
     */
    public function scopeThisMonth($query)
    {
        return $query->whereMonth('stat_date', now()->month)
                     ->whereYear('stat_date', now()->year);
    }

    /**
     * Increment sent counter
     */
    public function incrementSent(int $partsCount = 1): void
    {
        $this->increment('total_sent');
        $this->increment('parts_used', $partsCount);
        $this->increment('pending');
    }

    /**
     * Mark a message as delivered (update stats)
     */
    public function markDelivered(): void
    {
        $this->decrement('pending');
        $this->increment('delivered');
    }

    /**
     * Mark a message as failed (update stats)
     */
    public function markFailed(): void
    {
        $this->decrement('pending');
        $this->increment('failed');
    }
}
