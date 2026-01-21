<?php

namespace App\Models;

use App\Traits\BelongsToBusiness;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Lead Flow Tracking - Marketing -> Sales pipeline
 * Tracks lead quality and conversion from marketing to sales
 */
class LeadFlowTracking extends Model
{
    use HasUuids, BelongsToBusiness;

    protected $table = 'lead_flow_tracking';

    protected $fillable = [
        'business_id',
        'marketing_channel_id',
        'marketing_campaign_id',
        'tracking_date',
        // Leads from marketing
        'leads_generated',
        // Sales acceptance
        'leads_accepted',
        'leads_rejected',
        // Conversion
        'leads_converted',
        'conversion_rate',
        'revenue_generated',
        // Quality feedback
        'lead_quality_score',
        'rejection_reasons_summary',
    ];

    protected $casts = [
        'tracking_date' => 'date',
        'conversion_rate' => 'decimal:2',
        'revenue_generated' => 'decimal:2',
        'lead_quality_score' => 'decimal:2',
        'rejection_reasons_summary' => 'array',
    ];

    protected static function booted(): void
    {
        static::saving(function ($tracking) {
            // Calculate conversion rate
            if ($tracking->leads_accepted > 0) {
                $tracking->conversion_rate = round(
                    ($tracking->leads_converted / $tracking->leads_accepted) * 100,
                    2
                );
            }
        });
    }

    // Relationships
    public function marketingChannel(): BelongsTo
    {
        return $this->belongsTo(MarketingChannel::class);
    }

    public function marketingCampaign(): BelongsTo
    {
        return $this->belongsTo(MarketingCampaign::class);
    }

    // Get acceptance rate
    public function getAcceptanceRateAttribute(): float
    {
        if ($this->leads_generated == 0) return 0;
        return round(($this->leads_accepted / $this->leads_generated) * 100, 2);
    }

    // Get rejection rate
    public function getRejectionRateAttribute(): float
    {
        if ($this->leads_generated == 0) return 0;
        return round(($this->leads_rejected / $this->leads_generated) * 100, 2);
    }

    // Get average revenue per converted lead
    public function getRevenuePerLeadAttribute(): float
    {
        if ($this->leads_converted == 0) return 0;
        return round($this->revenue_generated / $this->leads_converted, 2);
    }

    // Get quality score label
    public function getQualityScoreLabelAttribute(): string
    {
        $score = $this->lead_quality_score;

        if ($score >= 4.5) return 'A\'lo';
        if ($score >= 3.5) return 'Yaxshi';
        if ($score >= 2.5) return 'O\'rta';
        if ($score >= 1.5) return 'Past';
        return 'Juda past';
    }

    // Get quality score color
    public function getQualityScoreColorAttribute(): string
    {
        $score = $this->lead_quality_score;

        if ($score >= 4.5) return 'green';
        if ($score >= 3.5) return 'blue';
        if ($score >= 2.5) return 'yellow';
        if ($score >= 1.5) return 'orange';
        return 'red';
    }

    // Scopes
    public function scopeForChannel($query, string $channelId)
    {
        return $query->where('marketing_channel_id', $channelId);
    }

    public function scopeForCampaign($query, string $campaignId)
    {
        return $query->where('marketing_campaign_id', $campaignId);
    }

    public function scopeForDate($query, $date)
    {
        return $query->whereDate('tracking_date', $date);
    }

    public function scopeForPeriod($query, $startDate, $endDate)
    {
        return $query->whereBetween('tracking_date', [$startDate, $endDate]);
    }

    public function scopeHighQuality($query, float $minScore = 4.0)
    {
        return $query->where('lead_quality_score', '>=', $minScore);
    }

    public function scopeLowQuality($query, float $maxScore = 2.5)
    {
        return $query->where('lead_quality_score', '<=', $maxScore);
    }
}
