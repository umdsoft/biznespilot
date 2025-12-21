<?php

namespace App\Models;

use App\Traits\BelongsToBusiness;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class KpiDailySnapshot extends Model
{
    use HasUuids, BelongsToBusiness;

    protected $fillable = [
        'business_id',
        'snapshot_date',
        // Revenue
        'revenue_total',
        'revenue_new',
        'revenue_recurring',
        'orders_count',
        'aov',
        // Leads
        'leads_total',
        'leads_qualified',
        'leads_converted',
        'lead_response_time_avg',
        // Marketing
        'reach_total',
        'impressions_total',
        'engagement_total',
        'engagement_rate',
        'followers_total',
        'followers_change',
        // Advertising
        'ad_spend',
        'ad_impressions',
        'ad_clicks',
        'ad_ctr',
        'ad_cpc',
        'ad_leads',
        'ad_cpl',
        'ad_conversions',
        'ad_roas',
        // Sales
        'cac',
        'clv',
        'ltv_cac_ratio',
        'conversion_rate',
        // Content
        'posts_count',
        'posts_engagement_avg',
        'best_post_id',
        'best_post_engagement',
        // Funnel
        'funnel_awareness',
        'funnel_interest',
        'funnel_consideration',
        'funnel_intent',
        'funnel_purchase',
        // Scores
        'health_score',
        'marketing_score',
        'sales_score',
        'content_score',
    ];

    protected $casts = [
        'snapshot_date' => 'date',
        'engagement_rate' => 'decimal:2',
        'ad_ctr' => 'decimal:2',
        'ad_roas' => 'decimal:2',
        'ltv_cac_ratio' => 'decimal:2',
        'conversion_rate' => 'decimal:2',
        'posts_engagement_avg' => 'decimal:2',
    ];

    public function business()
    {
        return $this->belongsTo(Business::class);
    }

    public function scopeForDate($query, $date)
    {
        return $query->where('snapshot_date', $date);
    }

    public function scopeForRange($query, $from, $to)
    {
        return $query->whereBetween('snapshot_date', [$from, $to]);
    }

    public function scopeLatest($query)
    {
        return $query->orderBy('snapshot_date', 'desc');
    }

    public function getRevenueChange($previousSnapshot)
    {
        if (!$previousSnapshot || $previousSnapshot->revenue_total == 0) {
            return null;
        }
        return (($this->revenue_total - $previousSnapshot->revenue_total) / $previousSnapshot->revenue_total) * 100;
    }

    public function getLeadsChange($previousSnapshot)
    {
        if (!$previousSnapshot || $previousSnapshot->leads_total == 0) {
            return null;
        }
        return (($this->leads_total - $previousSnapshot->leads_total) / $previousSnapshot->leads_total) * 100;
    }

    public function toKpiArray()
    {
        return [
            'revenue' => [
                'total' => $this->revenue_total,
                'new' => $this->revenue_new,
                'recurring' => $this->revenue_recurring,
                'orders' => $this->orders_count,
                'aov' => $this->aov,
            ],
            'leads' => [
                'total' => $this->leads_total,
                'qualified' => $this->leads_qualified,
                'converted' => $this->leads_converted,
                'response_time' => $this->lead_response_time_avg,
            ],
            'marketing' => [
                'reach' => $this->reach_total,
                'impressions' => $this->impressions_total,
                'engagement' => $this->engagement_total,
                'engagement_rate' => $this->engagement_rate,
                'followers' => $this->followers_total,
                'followers_change' => $this->followers_change,
            ],
            'advertising' => [
                'spend' => $this->ad_spend,
                'impressions' => $this->ad_impressions,
                'clicks' => $this->ad_clicks,
                'ctr' => $this->ad_ctr,
                'cpc' => $this->ad_cpc,
                'leads' => $this->ad_leads,
                'cpl' => $this->ad_cpl,
                'conversions' => $this->ad_conversions,
                'roas' => $this->ad_roas,
            ],
            'sales' => [
                'cac' => $this->cac,
                'clv' => $this->clv,
                'ltv_cac_ratio' => $this->ltv_cac_ratio,
                'conversion_rate' => $this->conversion_rate,
            ],
            'content' => [
                'posts' => $this->posts_count,
                'avg_engagement' => $this->posts_engagement_avg,
                'best_post' => $this->best_post_id,
                'best_engagement' => $this->best_post_engagement,
            ],
            'funnel' => [
                'awareness' => $this->funnel_awareness,
                'interest' => $this->funnel_interest,
                'consideration' => $this->funnel_consideration,
                'intent' => $this->funnel_intent,
                'purchase' => $this->funnel_purchase,
            ],
            'scores' => [
                'health' => $this->health_score,
                'marketing' => $this->marketing_score,
                'sales' => $this->sales_score,
                'content' => $this->content_score,
            ],
        ];
    }
}
