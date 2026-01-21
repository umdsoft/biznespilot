<?php

namespace App\Models;

use App\Traits\BelongsToBusiness;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MarketingBudget extends Model
{
    use HasUuids, BelongsToBusiness;

    protected $fillable = [
        'business_id',
        'channel_id',
        'year',
        'month',
        'budget_limit',
        'spent_amount',
        'remaining',
        'is_over_budget',
        'notes',
    ];

    protected $casts = [
        'budget_limit' => 'decimal:2',
        'spent_amount' => 'decimal:2',
        'remaining' => 'decimal:2',
        'is_over_budget' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::saving(function ($budget) {
            $budget->remaining = $budget->budget_limit - $budget->spent_amount;
            $budget->is_over_budget = $budget->spent_amount > $budget->budget_limit;
        });
    }

    // Relationships
    public function channel(): BelongsTo
    {
        return $this->belongsTo(MarketingChannel::class, 'channel_id');
    }

    // Get usage percentage
    public function getUsagePercentAttribute(): float
    {
        if ($this->budget_limit == 0) return 0;
        return round(($this->spent_amount / $this->budget_limit) * 100, 2);
    }

    // Get status color
    public function getStatusColorAttribute(): string
    {
        $usage = $this->usage_percent;

        if ($usage >= 100) return 'red';
        if ($usage >= 80) return 'orange';
        if ($usage >= 50) return 'yellow';
        return 'green';
    }

    // Get month name
    public function getMonthNameAttribute(): string
    {
        $months = [
            1 => 'Yanvar', 2 => 'Fevral', 3 => 'Mart',
            4 => 'Aprel', 5 => 'May', 6 => 'Iyun',
            7 => 'Iyul', 8 => 'Avgust', 9 => 'Sentabr',
            10 => 'Oktabr', 11 => 'Noyabr', 12 => 'Dekabr'
        ];

        return $months[$this->month] ?? '';
    }

    // Add spending
    public function addSpending(float $amount): void
    {
        $this->spent_amount += $amount;
        $this->save();
    }

    // Scopes
    public function scopeForYear($query, int $year)
    {
        return $query->where('year', $year);
    }

    public function scopeForMonth($query, int $month)
    {
        return $query->where('month', $month);
    }

    public function scopeForChannel($query, string $channelId)
    {
        return $query->where('channel_id', $channelId);
    }

    public function scopeOverBudget($query)
    {
        return $query->where('is_over_budget', true);
    }

    public function scopeCurrentMonth($query)
    {
        return $query->where('year', now()->year)
                     ->where('month', now()->month);
    }
}
