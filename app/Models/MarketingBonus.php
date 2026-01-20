<?php

namespace App\Models;

use App\Traits\BelongsToBusiness;
use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class MarketingBonus extends Model
{
    use HasUuid, BelongsToBusiness, SoftDeletes;

    protected $fillable = [
        'business_id',
        'user_id',
        'period_start',
        'period_end',
        'period_type',
        'target_id',
        'base_salary',
        'lead_bonus',
        'lead_bonus_percent',
        'cpl_bonus',
        'cpl_bonus_percent',
        'roas_bonus',
        'roas_bonus_percent',
        'accelerator_bonus',
        'accelerator_percent',
        'total_penalties',
        'penalty_details',
        'gross_bonus',
        'net_bonus',
        'total_earnings',
        'performance_snapshot',
        'targets_snapshot',
        'status',
        'approved_by',
        'approved_at',
        'notes',
    ];

    protected $casts = [
        'period_start' => 'date',
        'period_end' => 'date',
        'base_salary' => 'decimal:2',
        'lead_bonus' => 'decimal:2',
        'lead_bonus_percent' => 'decimal:2',
        'cpl_bonus' => 'decimal:2',
        'cpl_bonus_percent' => 'decimal:2',
        'roas_bonus' => 'decimal:2',
        'roas_bonus_percent' => 'decimal:2',
        'accelerator_bonus' => 'decimal:2',
        'accelerator_percent' => 'decimal:2',
        'total_penalties' => 'decimal:2',
        'penalty_details' => 'array',
        'gross_bonus' => 'decimal:2',
        'net_bonus' => 'decimal:2',
        'total_earnings' => 'decimal:2',
        'performance_snapshot' => 'array',
        'targets_snapshot' => 'array',
        'approved_at' => 'datetime',
    ];

    // RELATIONSHIPS

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function target(): BelongsTo
    {
        return $this->belongsTo(MarketingTarget::class, 'target_id');
    }

    public function approvedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function penalties(): HasMany
    {
        return $this->hasMany(MarketingPenalty::class, 'bonus_id');
    }

    // SCOPES

    public function scopeForUser(Builder $query, $userId): Builder
    {
        return $query->where('user_id', $userId);
    }

    public function scopeForPeriod(Builder $query, $periodStart): Builder
    {
        return $query->where('period_start', $periodStart);
    }

    public function scopeMonthly(Builder $query): Builder
    {
        return $query->where('period_type', 'monthly');
    }

    public function scopeApproved(Builder $query): Builder
    {
        return $query->where('status', 'approved');
    }

    public function scopePending(Builder $query): Builder
    {
        return $query->whereIn('status', ['draft', 'calculated']);
    }

    // HELPERS

    public function isApproved(): bool
    {
        return $this->status === 'approved';
    }

    public function isPaid(): bool
    {
        return $this->status === 'paid';
    }

    public function canBeApproved(): bool
    {
        return in_array($this->status, ['draft', 'calculated']);
    }

    public function getBonusPercentOfSalary(): float
    {
        if ($this->base_salary == 0) {
            return 0;
        }

        return round(($this->net_bonus / $this->base_salary) * 100, 2);
    }

    public function approve(?string $approvedBy = null): void
    {
        $this->update([
            'status' => 'approved',
            'approved_by' => $approvedBy ?? auth()->id(),
            'approved_at' => now(),
        ]);
    }

    public function markAsPaid(): void
    {
        $this->update(['status' => 'paid']);
    }
}
