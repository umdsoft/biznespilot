<?php

namespace App\Models;

use App\Traits\BelongsToBusiness;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Key Task Map (Карта ключевых задач) based on book methodology
 * - Monthly/Quarterly key tasks with weights
 * - Minimum completion threshold for any bonus
 * - Progressive bonus calculation
 */
class KeyTaskMap extends Model
{
    use HasUuids, BelongsToBusiness;

    protected $fillable = [
        'business_id',
        'user_id',
        'motivation_scheme_id',
        'title',
        'period_type',
        'period_start',
        'period_end',
        'total_bonus_fund',
        'earned_bonus',
        'min_completion_percent',
        'full_bonus_percent',
        'status',
    ];

    protected $casts = [
        'period_start' => 'date',
        'period_end' => 'date',
        'total_bonus_fund' => 'decimal:2',
        'earned_bonus' => 'decimal:2',
    ];

    // Relationships
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function motivationScheme(): BelongsTo
    {
        return $this->belongsTo(MotivationScheme::class);
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(KeyTask::class)->orderBy('due_date');
    }

    public function completedTasks(): HasMany
    {
        return $this->hasMany(KeyTask::class)->where('status', 'completed');
    }

    public function pendingTasks(): HasMany
    {
        return $this->hasMany(KeyTask::class)->where('status', 'pending');
    }

    // Calculate completion percentage
    public function getCompletionPercentAttribute(): float
    {
        $totalWeight = $this->tasks()->sum('weight');
        if ($totalWeight == 0) return 0;

        $completedWeight = $this->completedTasks()->sum('weight');
        return round(($completedWeight / $totalWeight) * 100, 2);
    }

    // Calculate earned bonus based on completion
    public function calculateEarnedBonus(): float
    {
        $completion = $this->completion_percent;

        // If below minimum threshold, no bonus
        if ($completion < $this->min_completion_percent) {
            return 0;
        }

        // Calculate proportional bonus
        return round($this->total_bonus_fund * ($completion / 100), 2);
    }

    // Update earned bonus
    public function updateEarnedBonus(): void
    {
        $this->earned_bonus = $this->calculateEarnedBonus();
        $this->save();
    }

    // Get status color
    public function getStatusColorAttribute(): string
    {
        return match($this->status) {
            'draft' => 'gray',
            'active' => 'blue',
            'completed' => 'green',
            'cancelled' => 'red',
            default => 'gray',
        };
    }

    // Get completion status label
    public function getCompletionStatusAttribute(): string
    {
        $completion = $this->completion_percent;

        if ($completion >= $this->full_bonus_percent) {
            return 'A\'lo';
        }

        if ($completion >= $this->min_completion_percent) {
            return 'Qoniqarli';
        }

        return 'Yetarli emas';
    }

    // Get remaining days
    public function getRemainingDaysAttribute(): int
    {
        return max(0, now()->diffInDays($this->period_end, false));
    }

    // Check if overdue
    public function getIsOverdueAttribute(): bool
    {
        return now()->gt($this->period_end) && $this->status === 'active';
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeForUser($query, string $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeCurrentPeriod($query)
    {
        return $query->where('period_start', '<=', now())
                     ->where('period_end', '>=', now());
    }

    public function scopeMonthly($query)
    {
        return $query->where('period_type', 'monthly');
    }

    public function scopeQuarterly($query)
    {
        return $query->where('period_type', 'quarterly');
    }
}
