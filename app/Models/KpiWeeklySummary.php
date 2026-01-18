<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class KpiWeeklySummary extends Model
{
    use HasFactory;

    protected $fillable = [
        'business_id',
        'year',
        'week_number',
        'start_date',
        'end_date',
        'days_with_data',
        // Leads
        'leads_total',
        'leads_digital',
        'leads_offline',
        'leads_referral',
        'leads_organic',
        // Spend
        'spend_total',
        'spend_digital',
        'spend_offline',
        // Sales
        'sales_total',
        'sales_new',
        'sales_repeat',
        // Revenue
        'revenue_total',
        'revenue_new',
        'revenue_repeat',
        // Metrics
        'avg_check',
        'conversion_rate',
        'cpl',
        'cac',
        'roi',
        'roas',
        // Plan comparison
        'plan_leads',
        'plan_sales',
        'plan_revenue',
        'plan_spend',
        'leads_achievement',
        'sales_achievement',
        'revenue_achievement',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'spend_total' => 'decimal:2',
        'spend_digital' => 'decimal:2',
        'spend_offline' => 'decimal:2',
        'revenue_total' => 'decimal:2',
        'revenue_new' => 'decimal:2',
        'revenue_repeat' => 'decimal:2',
        'avg_check' => 'decimal:2',
        'conversion_rate' => 'decimal:2',
        'cpl' => 'decimal:2',
        'cac' => 'decimal:2',
        'roi' => 'decimal:2',
        'roas' => 'decimal:2',
        'plan_revenue' => 'decimal:2',
        'plan_spend' => 'decimal:2',
        'leads_achievement' => 'decimal:2',
        'sales_achievement' => 'decimal:2',
        'revenue_achievement' => 'decimal:2',
    ];

    /**
     * Get the business
     */
    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    /**
     * Scope for specific year
     */
    public function scopeYear($query, int $year)
    {
        return $query->where('year', $year);
    }

    /**
     * Scope for current year
     */
    public function scopeCurrentYear($query)
    {
        return $query->where('year', now()->year);
    }

    /**
     * Get week label
     */
    public function getWeekLabelAttribute(): string
    {
        return $this->week_number.'-hafta';
    }

    /**
     * Get date range label
     */
    public function getDateRangeLabelAttribute(): string
    {
        return $this->start_date->format('d.m').' - '.$this->end_date->format('d.m');
    }

    /**
     * Calculate metrics from totals
     */
    public function calculateMetrics(): void
    {
        // Average check
        if ($this->sales_total > 0) {
            $this->avg_check = $this->revenue_total / $this->sales_total;
        }

        // Conversion rate
        if ($this->leads_total > 0) {
            $this->conversion_rate = ($this->sales_total / $this->leads_total) * 100;
        }

        // CPL
        if ($this->leads_total > 0 && $this->spend_total > 0) {
            $this->cpl = $this->spend_total / $this->leads_total;
        }

        // CAC
        if ($this->sales_new > 0 && $this->spend_total > 0) {
            $this->cac = $this->spend_total / $this->sales_new;
        }

        // ROI
        if ($this->spend_total > 0) {
            $this->roi = (($this->revenue_total - $this->spend_total) / $this->spend_total) * 100;
        }

        // ROAS
        if ($this->spend_total > 0) {
            $this->roas = $this->revenue_total / $this->spend_total;
        }

        // Achievements
        if ($this->plan_leads > 0) {
            $this->leads_achievement = ($this->leads_total / $this->plan_leads) * 100;
        }
        if ($this->plan_sales > 0) {
            $this->sales_achievement = ($this->sales_total / $this->plan_sales) * 100;
        }
        if ($this->plan_revenue > 0) {
            $this->revenue_achievement = ($this->revenue_total / $this->plan_revenue) * 100;
        }
    }
}
