<?php

namespace App\Models;

use App\Traits\BelongsToBusiness;
use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MetaCampaignInsight extends Model
{
    use BelongsToBusiness, HasUuid;

    protected $fillable = [
        'campaign_id',
        'business_id',
        'date',
        'spend',
        'impressions',
        'reach',
        'clicks',
        'cpc',
        'cpm',
        'ctr',
        'frequency',
        'conversions',
        'leads',
        'purchases',
        'add_to_cart',
        'link_clicks',
        'video_views',
        'cost_per_conversion',
        'cost_per_lead',
        'actions',
        'action_values',
    ];

    protected $casts = [
        'date' => 'date',
        'spend' => 'decimal:2',
        'cpc' => 'decimal:4',
        'cpm' => 'decimal:4',
        'ctr' => 'decimal:4',
        'frequency' => 'decimal:2',
        'cost_per_conversion' => 'decimal:2',
        'cost_per_lead' => 'decimal:2',
        'impressions' => 'integer',
        'reach' => 'integer',
        'clicks' => 'integer',
        'conversions' => 'integer',
        'leads' => 'integer',
        'purchases' => 'integer',
        'add_to_cart' => 'integer',
        'link_clicks' => 'integer',
        'video_views' => 'integer',
        'actions' => 'array',
        'action_values' => 'array',
    ];

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(MetaCampaign::class, 'campaign_id');
    }

    public function scopeForDateRange($query, string $startDate, string $endDate)
    {
        return $query->whereBetween('date', [$startDate, $endDate]);
    }

    public function scopeForCampaign($query, string $campaignId)
    {
        return $query->where('campaign_id', $campaignId);
    }

    /**
     * Get action value by type from actions array
     */
    public function getActionValue(string $actionType): int
    {
        if (!$this->actions) {
            return 0;
        }

        foreach ($this->actions as $action) {
            if (($action['action_type'] ?? '') === $actionType) {
                return (int) ($action['value'] ?? 0);
            }
        }

        return 0;
    }
}
