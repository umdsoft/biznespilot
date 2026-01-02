<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class KpiPlan extends Model
{
    use HasFactory;

    protected $fillable = [
        'business_id',
        'year',
        'month',
        'start_date',
        'end_date',
        'working_days',
        'new_sales',
        'avg_check',
        'repeat_sales',
        'total_customers',
        'total_revenue',
        'ad_costs',
        'gross_margin',
        'gross_margin_percent',
        'roi',
        'roas',
        'cac',
        'clv',
        'ltv_cac_ratio',
        'total_leads',
        'lead_cost',
        'conversion_rate',
        'ctr',
        'churn_rate',
        'daily_breakdown',
        'weekly_breakdown',
        'calculation_method',
        'status',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'avg_check' => 'decimal:2',
        'total_revenue' => 'decimal:2',
        'ad_costs' => 'decimal:2',
        'gross_margin' => 'decimal:2',
        'gross_margin_percent' => 'decimal:2',
        'roi' => 'decimal:2',
        'roas' => 'decimal:2',
        'cac' => 'decimal:2',
        'clv' => 'decimal:2',
        'ltv_cac_ratio' => 'decimal:2',
        'lead_cost' => 'decimal:2',
        'conversion_rate' => 'decimal:2',
        'ctr' => 'decimal:2',
        'churn_rate' => 'decimal:2',
        'daily_breakdown' => 'array',
        'weekly_breakdown' => 'array',
    ];

    /**
     * Get the business that owns the KPI plan
     */
    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    /**
     * Scope for active plans
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Scope for current month
     */
    public function scopeCurrentMonth($query)
    {
        return $query->where('year', now()->year)
                     ->where('month', now()->month);
    }

    /**
     * Get month name in Uzbek
     */
    public function getMonthNameAttribute(): string
    {
        $months = [
            1 => 'Yanvar',
            2 => 'Fevral',
            3 => 'Mart',
            4 => 'Aprel',
            5 => 'May',
            6 => 'Iyun',
            7 => 'Iyul',
            8 => 'Avgust',
            9 => 'Sentabr',
            10 => 'Oktabr',
            11 => 'Noyabr',
            12 => 'Dekabr',
        ];

        return $months[$this->month] ?? '';
    }

    /**
     * Get formatted period string
     */
    public function getPeriodStringAttribute(): string
    {
        return $this->month_name . ' ' . $this->year;
    }
}
