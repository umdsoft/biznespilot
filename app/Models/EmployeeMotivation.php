<?php

namespace App\Models;

use App\Traits\BelongsToBusiness;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EmployeeMotivation extends Model
{
    use HasUuids, BelongsToBusiness;

    protected $fillable = [
        'business_id',
        'user_id',
        'motivation_scheme_id',
        'personal_fixed_salary',
        'personal_soft_salary',
        'personal_adjustments',
        'effective_from',
        'effective_to',
        'is_active',
    ];

    protected $casts = [
        'personal_fixed_salary' => 'decimal:2',
        'personal_soft_salary' => 'decimal:2',
        'personal_adjustments' => 'array',
        'effective_from' => 'date',
        'effective_to' => 'date',
        'is_active' => 'boolean',
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

    public function calculations(): HasMany
    {
        return $this->hasMany(MotivationCalculation::class);
    }

    // Get effective fixed salary (personal or from scheme)
    public function getEffectiveFixedSalaryAttribute(): float
    {
        if ($this->personal_fixed_salary) {
            return $this->personal_fixed_salary;
        }

        return $this->motivationScheme?->total_fixed_salary ?? 0;
    }

    // Get effective soft salary (personal or from scheme)
    public function getEffectiveSoftSalaryAttribute(): float
    {
        if ($this->personal_soft_salary) {
            return $this->personal_soft_salary;
        }

        return $this->motivationScheme?->total_soft_salary ?? 0;
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeCurrentlyValid($query)
    {
        return $query->where('effective_from', '<=', now())
                     ->where(function($q) {
                         $q->whereNull('effective_to')
                           ->orWhere('effective_to', '>=', now());
                     });
    }

    public function scopeForUser($query, string $userId)
    {
        return $query->where('user_id', $userId);
    }
}
