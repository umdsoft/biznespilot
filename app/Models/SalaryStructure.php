<?php

namespace App\Models;

use App\Traits\BelongsToBusiness;
use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SalaryStructure extends Model
{
    use BelongsToBusiness, HasUuid;

    protected $fillable = [
        'business_id',
        'user_id',
        'base_salary',
        'currency',
        'payment_frequency',
        'effective_from',
        'effective_until',
        'is_active',
        'allowances',
        'deductions',
    ];

    protected $casts = [
        'base_salary' => 'decimal:2',
        'effective_from' => 'date',
        'effective_until' => 'date',
        'is_active' => 'boolean',
        'allowances' => 'array',
        'deductions' => 'array',
    ];

    // ==================== Relationships ====================

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function payslips(): HasMany
    {
        return $this->hasMany(Payslip::class);
    }

    // ==================== Accessors ====================

    public function getPaymentFrequencyLabelAttribute(): string
    {
        $labels = [
            'monthly' => 'Oylik',
            'bi-weekly' => '2 Haftalik',
            'weekly' => 'Haftalik',
        ];

        return $labels[$this->payment_frequency] ?? $this->payment_frequency;
    }

    public function getTotalAllowancesAttribute(): float
    {
        if (!$this->allowances) {
            return 0;
        }

        return collect($this->allowances)->sum('amount');
    }

    public function getTotalDeductionsAttribute(): float
    {
        if (!$this->deductions) {
            return 0;
        }

        return collect($this->deductions)->sum('amount');
    }

    public function getGrossSalaryAttribute(): float
    {
        return $this->base_salary + $this->total_allowances;
    }

    public function getNetSalaryAttribute(): float
    {
        return $this->gross_salary - $this->total_deductions;
    }

    // ==================== Scopes ====================

    public function scopeActive($query)
    {
        return $query->where('is_active', true)
            ->where('effective_from', '<=', now())
            ->where(function ($q) {
                $q->whereNull('effective_until')
                    ->orWhere('effective_until', '>=', now());
            });
    }

    public function scopeForUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }
}
