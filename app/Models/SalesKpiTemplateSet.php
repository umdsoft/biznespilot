<?php

namespace App\Models;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SalesKpiTemplateSet extends Model
{
    use HasFactory, HasUuid, SoftDeletes;

    /**
     * Sanoat turlari
     */
    public const INDUSTRIES = [
        'it_services' => 'IT va Xizmatlar',
        'retail' => 'Chakana savdo',
        'wholesale' => 'Ulgurji savdo',
        'education' => 'Ta\'lim',
        'real_estate' => 'Ko\'chmas mulk',
        'finance' => 'Moliya',
        'healthcare' => 'Sog\'liqni saqlash',
        'manufacturing' => 'Ishlab chiqarish',
        'hospitality' => 'Mehmonxona/Restoran',
        'other' => 'Boshqa',
    ];

    protected $fillable = [
        'code',
        'name',
        'description',
        'industry',
        'icon',
        'kpi_settings',
        'bonus_settings',
        'penalty_rules',
        'achievement_definitions',
        'recommended_targets',
        'onboarding_tips',
        'is_active',
        'is_featured',
        'usage_count',
        'sort_order',
    ];

    protected $casts = [
        'kpi_settings' => 'array',
        'bonus_settings' => 'array',
        'penalty_rules' => 'array',
        'achievement_definitions' => 'array',
        'recommended_targets' => 'array',
        'onboarding_tips' => 'array',
        'is_active' => 'boolean',
        'is_featured' => 'boolean',
        'usage_count' => 'integer',
    ];

    /**
     * Faol shablonlar
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * Tavsiya etilgan
     */
    public function scopeFeatured(Builder $query): Builder
    {
        return $query->where('is_featured', true);
    }

    /**
     * Sanoat bo'yicha
     */
    public function scopeForIndustry(Builder $query, string $industry): Builder
    {
        return $query->where('industry', $industry);
    }

    /**
     * Tartiblangan
     */
    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order')->orderBy('name');
    }

    /**
     * Sanoat nomini olish
     */
    public function getIndustryLabelAttribute(): string
    {
        return self::INDUSTRIES[$this->industry] ?? $this->industry;
    }

    /**
     * Ishlatilgan sonini oshirish
     */
    public function incrementUsage(): void
    {
        $this->increment('usage_count');
    }

    /**
     * KPI sozlamalari sonini olish
     */
    public function getKpiCountAttribute(): int
    {
        return count($this->kpi_settings ?? []);
    }

    /**
     * Bonus sozlamalari sonini olish
     */
    public function getBonusCountAttribute(): int
    {
        return count($this->bonus_settings ?? []);
    }

    /**
     * Jarima qoidalari sonini olish
     */
    public function getPenaltyRulesCountAttribute(): int
    {
        return count($this->penalty_rules ?? []);
    }
}
