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
        'avg_cpc',
        'avg_cpm',
        'avg_ctr',
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
        'total_impressions' => 'integer',
        'total_reach' => 'integer',
        'total_clicks' => 'integer',
        'total_conversions' => 'integer',
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
                AVG(cpc) as avg_cpc,
                AVG(cpm) as avg_cpm,
                CASE WHEN SUM(impressions) > 0 THEN (SUM(clicks) / SUM(impressions)) * 100 ELSE 0 END as avg_ctr
            ')
            ->first();

        $this->update([
            'total_spend' => $aggregates->total_spend ?? 0,
            'total_impressions' => $aggregates->total_impressions ?? 0,
            'total_reach' => $aggregates->total_reach ?? 0,
            'total_clicks' => $aggregates->total_clicks ?? 0,
            'total_conversions' => $aggregates->total_conversions ?? 0,
            'avg_cpc' => $aggregates->avg_cpc ?? 0,
            'avg_cpm' => $aggregates->avg_cpm ?? 0,
            'avg_ctr' => $aggregates->avg_ctr ?? 0,
        ]);
    }
}
