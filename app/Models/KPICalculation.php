<?php

namespace App\Models;

use App\Traits\BelongsToBusiness;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class KPICalculation extends Model
{
    use BelongsToBusiness;

    protected $fillable = [
        'business_id',
        'diagnostic_id',
        'calculation_date',
        'period_type',
        'period_start',
        'period_end',
        // Marketing KPIs
        'total_reach',
        'total_impressions',
        'total_engagement',
        'engagement_rate',
        'follower_growth_rate',
        'content_posts_count',
        'avg_engagement_per_post',
        // Advertising KPIs
        'total_ad_spend',
        'total_clicks',
        'total_impressions_ads',
        'cpc',
        'cpm',
        'ctr',
        'leads_from_ads',
        'cpl',
        'roas',
        // Sales KPIs
        'total_leads',
        'qualified_leads',
        'new_customers',
        'total_revenue',
        'cac',
        'clv',
        'ltv_cac_ratio',
        'conversion_rate',
        'avg_deal_size',
        'sales_cycle_days',
        // Funnel KPIs
        'funnel_awareness',
        'funnel_interest',
        'funnel_consideration',
        'funnel_intent',
        'funnel_purchase',
        'funnel_conversion_rate',
        // Retention KPIs
        'active_customers',
        'churned_customers',
        'churn_rate',
        'repeat_purchase_rate',
        'nps_score',
        // Benchmark comparison
        'benchmark_comparison',
    ];

    protected $casts = [
        'calculation_date' => 'date',
        'period_start' => 'date',
        'period_end' => 'date',
        'engagement_rate' => 'decimal:2',
        'follower_growth_rate' => 'decimal:2',
        'avg_engagement_per_post' => 'decimal:2',
        'cpc' => 'decimal:2',
        'cpm' => 'decimal:2',
        'ctr' => 'decimal:2',
        'cpl' => 'decimal:2',
        'roas' => 'decimal:2',
        'cac' => 'decimal:2',
        'clv' => 'decimal:2',
        'ltv_cac_ratio' => 'decimal:2',
        'conversion_rate' => 'decimal:2',
        'funnel_conversion_rate' => 'decimal:2',
        'churn_rate' => 'decimal:2',
        'repeat_purchase_rate' => 'decimal:2',
        'benchmark_comparison' => 'array',
    ];

    // Constants
    public const PERIOD_TYPES = [
        'daily' => 'Kunlik',
        'weekly' => 'Haftalik',
        'monthly' => 'Oylik',
        'quarterly' => 'Choraklik',
    ];

    // Relationships
    public function diagnostic(): BelongsTo
    {
        return $this->belongsTo(AIDiagnostic::class, 'diagnostic_id');
    }

    // Scopes
    public function scopeMonthly($query)
    {
        return $query->where('period_type', 'monthly');
    }

    public function scopeWeekly($query)
    {
        return $query->where('period_type', 'weekly');
    }

    public function scopeLatest($query)
    {
        return $query->orderBy('calculation_date', 'desc');
    }

    // Helpers
    public function getMarketingKPIs(): array
    {
        return [
            'total_reach' => $this->total_reach,
            'total_impressions' => $this->total_impressions,
            'total_engagement' => $this->total_engagement,
            'engagement_rate' => $this->engagement_rate,
            'follower_growth_rate' => $this->follower_growth_rate,
            'content_posts_count' => $this->content_posts_count,
            'avg_engagement_per_post' => $this->avg_engagement_per_post,
        ];
    }

    public function getAdvertisingKPIs(): array
    {
        return [
            'total_ad_spend' => $this->total_ad_spend,
            'total_clicks' => $this->total_clicks,
            'total_impressions_ads' => $this->total_impressions_ads,
            'cpc' => $this->cpc,
            'cpm' => $this->cpm,
            'ctr' => $this->ctr,
            'leads_from_ads' => $this->leads_from_ads,
            'cpl' => $this->cpl,
            'roas' => $this->roas,
        ];
    }

    public function getSalesKPIs(): array
    {
        return [
            'total_leads' => $this->total_leads,
            'qualified_leads' => $this->qualified_leads,
            'new_customers' => $this->new_customers,
            'total_revenue' => $this->total_revenue,
            'cac' => $this->cac,
            'clv' => $this->clv,
            'ltv_cac_ratio' => $this->ltv_cac_ratio,
            'conversion_rate' => $this->conversion_rate,
            'avg_deal_size' => $this->avg_deal_size,
            'sales_cycle_days' => $this->sales_cycle_days,
        ];
    }

    public function getFunnelKPIs(): array
    {
        return [
            'funnel_awareness' => $this->funnel_awareness,
            'funnel_interest' => $this->funnel_interest,
            'funnel_consideration' => $this->funnel_consideration,
            'funnel_intent' => $this->funnel_intent,
            'funnel_purchase' => $this->funnel_purchase,
            'funnel_conversion_rate' => $this->funnel_conversion_rate,
        ];
    }

    public function getRetentionKPIs(): array
    {
        return [
            'active_customers' => $this->active_customers,
            'churned_customers' => $this->churned_customers,
            'churn_rate' => $this->churn_rate,
            'repeat_purchase_rate' => $this->repeat_purchase_rate,
            'nps_score' => $this->nps_score,
        ];
    }

    public function getAllKPIs(): array
    {
        return array_merge(
            $this->getMarketingKPIs(),
            $this->getAdvertisingKPIs(),
            $this->getSalesKPIs(),
            $this->getFunnelKPIs(),
            $this->getRetentionKPIs()
        );
    }

    public function getPeriodLabel(): string
    {
        return self::PERIOD_TYPES[$this->period_type] ?? $this->period_type;
    }

    public function getPeriodRange(): string
    {
        return $this->period_start->format('d.m.Y') . ' - ' . $this->period_end->format('d.m.Y');
    }
}
