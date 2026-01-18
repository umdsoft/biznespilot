<?php

namespace App\Models;

use App\Traits\BelongsToBusiness;
use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;

class BusinessMaturityAssessment extends Model
{
    use BelongsToBusiness, HasUuid;

    protected $fillable = [
        'business_id',
        // Revenue
        'monthly_revenue_range',
        'monthly_marketing_budget_range',
        // Challenges
        'main_challenges',
        // Infrastructure
        'has_website',
        'has_crm',
        'uses_analytics',
        'has_automation',
        'current_tools',
        // Processes
        'has_documented_processes',
        'has_sales_process',
        'has_support_process',
        'has_marketing_process',
        // Marketing
        'marketing_channels',
        'has_marketing_budget',
        'tracks_marketing_metrics',
        'has_dedicated_marketing',
        // Goals
        'primary_goals',
        'growth_target',
        // Scores (calculated)
        'overall_score',
        'maturity_level',
        'category_scores',
        'answers',
        'recommendations',
        'assessed_at',
    ];

    protected $casts = [
        // Infrastructure
        'has_website' => 'boolean',
        'has_crm' => 'boolean',
        'uses_analytics' => 'boolean',
        'has_automation' => 'boolean',
        'current_tools' => 'array',
        // Processes
        'has_documented_processes' => 'boolean',
        'has_sales_process' => 'boolean',
        'has_support_process' => 'boolean',
        'has_marketing_process' => 'boolean',
        // Marketing
        'marketing_channels' => 'array',
        'has_marketing_budget' => 'boolean',
        'tracks_marketing_metrics' => 'boolean',
        'has_dedicated_marketing' => 'boolean',
        // Goals & Challenges
        'main_challenges' => 'array',
        'primary_goals' => 'array',
        // Scores
        'category_scores' => 'array',
        'answers' => 'array',
        'recommendations' => 'array',
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
        if ($this->has_website) {
            $score += 20;
        }
        if ($this->has_crm) {
            $score += 25;
        }
        if ($this->uses_analytics) {
            $score += 20;
        }
        if ($this->has_automation) {
            $score += 20;
        }
        if (! empty($this->current_tools)) {
            $score += min(count($this->current_tools) * 5, 15);
        }

        return $score; // Max 100
    }

    public function getProcessScore(): int
    {
        $score = 0;
        if ($this->has_documented_processes) {
            $score += 25;
        }
        if ($this->has_sales_process) {
            $score += 25;
        }
        if ($this->has_support_process) {
            $score += 25;
        }
        if ($this->has_marketing_process) {
            $score += 25;
        }

        return $score; // Max 100
    }

    public function getMarketingScore(): int
    {
        $score = 0;
        if (! empty($this->marketing_channels)) {
            $score += min(count($this->marketing_channels) * 10, 30);
        }
        if ($this->has_marketing_budget) {
            $score += 25;
        }
        if ($this->tracks_marketing_metrics) {
            $score += 25;
        }
        if ($this->has_dedicated_marketing) {
            $score += 20;
        }

        return $score; // Max 100
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
        return ! is_null($this->assessed_at);
    }
}
