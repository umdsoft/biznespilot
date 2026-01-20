<?php

namespace App\Models;

use App\Traits\BelongsToBusiness;
use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class SalesBonusSetting extends Model
{
    use BelongsToBusiness, HasFactory, HasUuid, SoftDeletes;

    /**
     * Bonus turlari
     */
    public const BONUS_TYPES = [
        'fixed' => 'Belgilangan summa',
        'revenue_percentage' => 'Daromad foizi',
        'kpi_based' => 'KPI asosida',
        'tiered' => 'Bosqichli (Tiered)',
    ];

    /**
     * Hisoblash davrlari
     */
    public const CALCULATION_PERIODS = [
        'monthly' => 'Oylik',
        'quarterly' => 'Choraklik',
    ];

    /**
     * Standart tier lar
     */
    public const DEFAULT_TIERS = [
        ['min' => 80, 'max' => 99, 'multiplier' => 1.0, 'name' => 'Standard'],
        ['min' => 100, 'max' => 119, 'multiplier' => 1.2, 'name' => 'Excellent'],
        ['min' => 120, 'max' => null, 'multiplier' => 1.5, 'name' => 'Accelerator'],
    ];

    protected $fillable = [
        'business_id',
        'name',
        'description',
        'bonus_type',
        'base_amount',
        'percentage_of',
        'percentage_rate',
        'tiers',
        'min_kpi_score',
        'min_working_days',
        'calculation_period',
        'applies_to_roles',
        'requires_approval',
        'auto_calculate',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'base_amount' => 'decimal:2',
        'percentage_rate' => 'decimal:2',
        'tiers' => 'array',
        'min_kpi_score' => 'integer',
        'min_working_days' => 'integer',
        'applies_to_roles' => 'array',
        'requires_approval' => 'boolean',
        'auto_calculate' => 'boolean',
        'is_active' => 'boolean',
    ];

    /**
     * Hisoblangan bonuslar
     */
    public function calculations(): HasMany
    {
        return $this->hasMany(SalesBonusCalculation::class, 'bonus_setting_id');
    }

    /**
     * Faol
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * Avtomatik hisoblash
     */
    public function scopeAutoCalculate(Builder $query): Builder
    {
        return $query->where('auto_calculate', true);
    }

    /**
     * Davr bo'yicha
     */
    public function scopeForPeriod(Builder $query, string $period): Builder
    {
        return $query->where('calculation_period', $period);
    }

    /**
     * Tartiblangan ro'yxat
     */
    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order')->orderBy('created_at');
    }

    /**
     * Bonus turi labelini olish
     */
    public function getTypeLabelAttribute(): string
    {
        return self::BONUS_TYPES[$this->bonus_type] ?? $this->bonus_type;
    }

    /**
     * Tier ni ball bo'yicha topish
     */
    public function getTierByScore(int $score): ?array
    {
        if (! $this->tiers) {
            return null;
        }

        foreach ($this->tiers as $tier) {
            $min = $tier['min'] ?? 0;
            $max = $tier['max'] ?? PHP_INT_MAX;

            if ($score >= $min && $score <= $max) {
                return $tier;
            }
        }

        return null;
    }

    /**
     * Multiplier ni ball bo'yicha olish
     */
    public function getMultiplierByScore(int $score): float
    {
        $tier = $this->getTierByScore($score);

        return $tier['multiplier'] ?? 1.0;
    }

    /**
     * Foydalanuvchi mos keladimi
     */
    public function appliesTo(string $role): bool
    {
        if (! $this->applies_to_roles) {
            return true; // Hamma uchun
        }

        return in_array($role, $this->applies_to_roles);
    }

    /**
     * Minimal talablarni tekshirish
     */
    public function checkQualification(int $kpiScore, int $workingDays): array
    {
        $qualified = true;
        $reasons = [];

        if ($kpiScore < $this->min_kpi_score) {
            $qualified = false;
            $reasons[] = "KPI ball ({$kpiScore}) minimal talabdan ({$this->min_kpi_score}) past";
        }

        if ($workingDays < $this->min_working_days) {
            $qualified = false;
            $reasons[] = "Ish kunlari ({$workingDays}) minimal talabdan ({$this->min_working_days}) kam";
        }

        return [
            'qualified' => $qualified,
            'reasons' => $reasons,
            'disqualification_reason' => $qualified ? null : implode('; ', $reasons),
        ];
    }

    /**
     * Bonus summasini hisoblash
     */
    public function calculateBonus(int $kpiScore, float $revenue = 0): array
    {
        $baseAmount = 0;
        $multiplier = 1.0;
        $appliedTier = null;

        switch ($this->bonus_type) {
            case 'fixed':
                $baseAmount = $this->base_amount;
                break;

            case 'revenue_percentage':
                $baseAmount = $revenue * ($this->percentage_rate / 100);
                break;

            case 'kpi_based':
                $baseAmount = $this->base_amount;
                break;

            case 'tiered':
                $baseAmount = $this->base_amount;
                $tier = $this->getTierByScore($kpiScore);
                if ($tier) {
                    $multiplier = $tier['multiplier'];
                    $appliedTier = $tier['name'] ?? "KPI {$kpiScore}%";
                }
                break;
        }

        $finalAmount = $baseAmount * $multiplier;

        return [
            'base_amount' => round($baseAmount, 0),
            'multiplier' => $multiplier,
            'applied_tier' => $appliedTier,
            'final_amount' => round($finalAmount, 0),
        ];
    }
}
