<?php

namespace App\Models;

use App\Traits\BelongsToBusiness;
use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MetaCampaign extends Model
{
    use BelongsToBusiness, HasUuid;

    protected $fillable = [
        'ad_account_id',
        'business_id',
        'meta_campaign_id',
        'name',
        'objective',
        'status',
        'effective_status',
        'daily_budget',
        'lifetime_budget',
        'budget_remaining',
        'start_time',
        'stop_time',
        'created_time',
        'metadata',
        // Aggregated metrics
        'total_spend',
        'total_impressions',
        'total_reach',
        'total_clicks',
        'total_conversions',
        'total_leads',
        'total_purchases',
        'total_messages',
        'total_link_clicks',
        'total_video_views',
        'total_add_to_cart',
        'avg_cpc',
        'avg_cpm',
        'avg_ctr',
        'cost_per_lead',
        'cost_per_purchase',
        'cost_per_message',
        // Sync info
        'last_synced_at',
        'sync_status',
    ];

    protected $casts = [
        'daily_budget' => 'decimal:2',
        'lifetime_budget' => 'decimal:2',
        'total_spend' => 'decimal:2',
        'avg_cpc' => 'decimal:4',
        'avg_cpm' => 'decimal:4',
        'avg_ctr' => 'decimal:4',
        'cost_per_lead' => 'decimal:4',
        'cost_per_purchase' => 'decimal:4',
        'cost_per_message' => 'decimal:4',
        'total_impressions' => 'integer',
        'total_reach' => 'integer',
        'total_clicks' => 'integer',
        'total_conversions' => 'integer',
        'total_leads' => 'integer',
        'total_purchases' => 'integer',
        'total_messages' => 'integer',
        'total_link_clicks' => 'integer',
        'total_video_views' => 'integer',
        'total_add_to_cart' => 'integer',
        'metadata' => 'array',
        'start_time' => 'datetime',
        'stop_time' => 'datetime',
        'created_time' => 'datetime',
        'last_synced_at' => 'datetime',
    ];

    public function adAccount(): BelongsTo
    {
        return $this->belongsTo(MetaAdAccount::class, 'ad_account_id');
    }

    public function adSets(): HasMany
    {
        return $this->hasMany(MetaAdSet::class, 'campaign_id');
    }

    public function insights(): HasMany
    {
        return $this->hasMany(MetaCampaignInsight::class, 'campaign_id');
    }

    public function scopeActive($query)
    {
        return $query->where('effective_status', 'ACTIVE');
    }

    public function scopeByObjective($query, string $objective)
    {
        return $query->where('objective', $objective);
    }

    public function scopeByStatus($query, $status)
    {
        if (is_array($status)) {
            return $query->whereIn('effective_status', $status);
        }
        return $query->where('effective_status', $status);
    }

    public function getObjectiveLabelAttribute(): string
    {
        return match ($this->objective) {
            'OUTCOME_AWARENESS' => 'Xabardorlik',
            'OUTCOME_TRAFFIC' => 'Trafik',
            'OUTCOME_ENGAGEMENT' => 'Engagement',
            'OUTCOME_LEADS' => 'Lidlar',
            'OUTCOME_APP_PROMOTION' => 'App Promotion',
            'OUTCOME_SALES' => 'Sotuvlar',
            'LINK_CLICKS' => 'Link Clicks',
            'POST_ENGAGEMENT' => 'Post Engagement',
            'PAGE_LIKES' => 'Page Likes',
            'CONVERSIONS' => 'Konversiyalar',
            'MESSAGES' => 'Xabarlar',
            'VIDEO_VIEWS' => 'Video Views',
            default => ucfirst(strtolower(str_replace('_', ' ', $this->objective ?? 'Unknown'))),
        };
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->effective_status) {
            'ACTIVE' => 'Faol',
            'PAUSED' => 'Pauza',
            'DELETED' => 'O\'chirilgan',
            'ARCHIVED' => 'Arxivlangan',
            'IN_PROCESS' => 'Jarayonda',
            'WITH_ISSUES' => 'Muammoli',
            'CAMPAIGN_PAUSED' => 'Kampaniya pauzada',
            'ADSET_PAUSED' => 'AdSet pauzada',
            'PENDING_REVIEW' => 'Ko\'rib chiqilmoqda',
            'DISAPPROVED' => 'Rad etilgan',
            default => $this->effective_status ?? 'Noma\'lum',
        };
    }

    public function getStatusColorAttribute(): string
    {
        return match ($this->effective_status) {
            'ACTIVE' => 'green',
            'PAUSED', 'CAMPAIGN_PAUSED', 'ADSET_PAUSED' => 'yellow',
            'DELETED' => 'red',
            'ARCHIVED' => 'gray',
            'WITH_ISSUES', 'DISAPPROVED' => 'red',
            'PENDING_REVIEW', 'IN_PROCESS' => 'blue',
            default => 'gray',
        };
    }

    /**
     * Update aggregated metrics from insights
     */
    public function updateAggregates(): void
    {
        $aggregates = $this->insights()
            ->selectRaw('
                SUM(spend) as total_spend,
                SUM(impressions) as total_impressions,
                SUM(reach) as total_reach,
                SUM(clicks) as total_clicks,
                SUM(conversions) as total_conversions,
                SUM(leads) as total_leads,
                SUM(purchases) as total_purchases,
                SUM(messages) as total_messages,
                SUM(link_clicks) as total_link_clicks,
                SUM(video_views) as total_video_views,
                SUM(add_to_cart) as total_add_to_cart,
                AVG(cpc) as avg_cpc,
                AVG(cpm) as avg_cpm,
                CASE WHEN SUM(impressions) > 0 THEN (SUM(clicks) / SUM(impressions)) * 100 ELSE 0 END as avg_ctr
            ')
            ->first();

        $totalMessages = (int) ($aggregates->total_messages ?? 0);

        $totalSpend = (float) ($aggregates->total_spend ?? 0);
        $totalLeads = (int) ($aggregates->total_leads ?? 0);
        $totalPurchases = (int) ($aggregates->total_purchases ?? 0);

        $this->update([
            'total_spend' => $totalSpend,
            'total_impressions' => $aggregates->total_impressions ?? 0,
            'total_reach' => $aggregates->total_reach ?? 0,
            'total_clicks' => $aggregates->total_clicks ?? 0,
            'total_conversions' => $aggregates->total_conversions ?? 0,
            'total_leads' => $totalLeads,
            'total_purchases' => $totalPurchases,
            'total_messages' => $totalMessages,
            'total_link_clicks' => $aggregates->total_link_clicks ?? 0,
            'total_video_views' => $aggregates->total_video_views ?? 0,
            'total_add_to_cart' => $aggregates->total_add_to_cart ?? 0,
            'avg_cpc' => $aggregates->avg_cpc ?? 0,
            'avg_cpm' => $aggregates->avg_cpm ?? 0,
            'avg_ctr' => $aggregates->avg_ctr ?? 0,
            'cost_per_lead' => $totalLeads > 0 ? $totalSpend / $totalLeads : 0,
            'cost_per_purchase' => $totalPurchases > 0 ? $totalSpend / $totalPurchases : 0,
            'cost_per_message' => $totalMessages > 0 ? $totalSpend / $totalMessages : 0,
        ]);
    }

    /**
     * Get the primary result value based on campaign objective
     */
    public function getPrimaryResult(): array
    {
        return match ($this->objective) {
            'OUTCOME_LEADS', 'LEAD_GENERATION' => [
                'value' => $this->total_leads,
                'label' => 'Lid',
                'cost' => $this->cost_per_lead,
            ],
            'OUTCOME_SALES', 'CONVERSIONS', 'PRODUCT_CATALOG_SALES' => [
                'value' => $this->total_purchases ?: $this->total_conversions,
                'label' => 'Sotish',
                'cost' => $this->cost_per_purchase,
            ],
            'MESSAGES' => [
                'value' => $this->total_messages,
                'label' => 'Xabar',
                'cost' => $this->cost_per_message,
            ],
            'OUTCOME_TRAFFIC', 'LINK_CLICKS' => [
                'value' => $this->total_link_clicks,
                'label' => 'Klik',
                'cost' => $this->total_link_clicks > 0 ? $this->total_spend / $this->total_link_clicks : 0,
            ],
            'VIDEO_VIEWS' => [
                'value' => $this->total_video_views,
                'label' => 'Ko\'rish',
                'cost' => $this->total_video_views > 0 ? $this->total_spend / $this->total_video_views : 0,
            ],
            'OUTCOME_ENGAGEMENT', 'POST_ENGAGEMENT', 'PAGE_LIKES' => [
                'value' => $this->total_clicks,
                'label' => 'Engagement',
                'cost' => $this->avg_cpc,
            ],
            default => [
                'value' => $this->total_conversions ?: $this->total_clicks,
                'label' => $this->total_conversions > 0 ? 'Konversiya' : 'Klik',
                'cost' => $this->total_conversions > 0
                    ? ($this->total_spend / $this->total_conversions)
                    : $this->avg_cpc,
            ],
        };
    }
}
