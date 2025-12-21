<?php

namespace App\Models;

use App\Traits\BelongsToBusiness;
use Illuminate\Database\Eloquent\Model;

class BusinessMaturityAssessment extends Model
{
    use BelongsToBusiness;

    protected $fillable = [
        'business_id',
        'has_website',
        'has_instagram',
        'has_telegram',
        'has_crm',
        'has_paid_ads',
        'has_email_marketing',
        'has_analytics',
        'has_defined_target_audience',
        'has_documented_process',
        'monthly_revenue_range',
        'monthly_marketing_budget_range',
        'team_marketing_size',
        'team_sales_size',
        'main_challenges',
        'main_goals',
        'maturity_score',
        'maturity_level',
        'assessed_at',
    ];

    protected $casts = [
        'has_website' => 'boolean',
        'has_instagram' => 'boolean',
        'has_telegram' => 'boolean',
        'has_crm' => 'boolean',
        'has_paid_ads' => 'boolean',
        'has_email_marketing' => 'boolean',
        'has_analytics' => 'boolean',
        'has_defined_target_audience' => 'boolean',
        'has_documented_process' => 'boolean',
        'main_challenges' => 'array',
        'main_goals' => 'array',
        'assessed_at' => 'datetime',
    ];

    // Constants
    public const REVENUE_RANGES = [
        'none' => 'Hali yo\'q',
        'under_10m' => '10 million so\'mdan kam',
        '10m_50m' => '10-50 million so\'m',
        '50m_200m' => '50-200 million so\'m',
        '200m_500m' => '200-500 million so\'m',
        '500m_1b' => '500 million - 1 milliard so\'m',
        'over_1b' => '1 milliard so\'mdan ko\'p',
    ];

    public const BUDGET_RANGES = [
        'none' => 'Hali yo\'q',
        'under_1m' => '1 million so\'mdan kam',
        '1m_5m' => '1-5 million so\'m',
        '5m_20m' => '5-20 million so\'m',
        '20m_50m' => '20-50 million so\'m',
        'over_50m' => '50 million so\'mdan ko\'p',
    ];

    public const CHALLENGE_OPTIONS = [
        'no_leads' => 'Lidlar yetarli emas',
        'low_conversion' => 'Konversiya past',
        'no_retention' => 'Mijozlar qaytmayapti',
        'no_brand_awareness' => 'Brend tanilmagan',
        'no_clear_target' => 'Maqsadli auditoriya noaniq',
        'no_marketing_budget' => 'Marketing byudjeti yo\'q',
        'no_sales_process' => 'Sotish jarayoni yo\'q',
        'high_competition' => 'Raqobat yuqori',
        'other' => 'Boshqa',
    ];

    public const GOAL_OPTIONS = [
        'increase_revenue' => 'Daromadni oshirish',
        'increase_leads' => 'Lidlar sonini oshirish',
        'improve_conversion' => 'Konversiyani yaxshilash',
        'build_brand' => 'Brend qurish',
        'automate_marketing' => 'Marketingni avtomatlashtirish',
        'improve_retention' => 'Mijozlarni saqlab qolish',
        'enter_new_market' => 'Yangi bozorga kirish',
        'other' => 'Boshqa',
    ];

    // Scopes
    public function scopeAssessed($query)
    {
        return $query->whereNotNull('assessed_at');
    }

    public function scopeByLevel($query, string $level)
    {
        return $query->where('maturity_level', $level);
    }

    // Helpers
    public function getInfrastructureScore(): int
    {
        $score = 0;
        if ($this->has_website) $score += 10;
        if ($this->has_instagram) $score += 10;
        if ($this->has_telegram) $score += 10;
        if ($this->has_crm) $score += 15;
        if ($this->has_paid_ads) $score += 10;
        if ($this->has_email_marketing) $score += 10;
        if ($this->has_analytics) $score += 10;

        return $score; // Max 75
    }

    public function getProcessScore(): int
    {
        $score = 0;
        if ($this->has_defined_target_audience) $score += 15;
        if ($this->has_documented_process) $score += 10;

        return $score; // Max 25
    }

    public function getRevenueScore(): int
    {
        $scores = [
            'none' => 0,
            'under_10m' => 10,
            '10m_50m' => 30,
            '50m_200m' => 50,
            '200m_500m' => 70,
            '500m_1b' => 90,
            'over_1b' => 100,
        ];

        return $scores[$this->monthly_revenue_range] ?? 0;
    }

    public function isAssessed(): bool
    {
        return !is_null($this->assessed_at);
    }
}
