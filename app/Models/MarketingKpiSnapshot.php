<?php

namespace App\Models;

use App\Traits\BelongsToBusiness;
use App\Traits\HasUuid;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * MarketingKpiSnapshot - Marketing KPI kunlik/haftalik/oylik snapshotlar
 * CPL, ROAS, ROI, CAC va boshqa metrikalar
 */
class MarketingKpiSnapshot extends Model
{
    use BelongsToBusiness, HasUuid;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'business_id',
        'date',
        'period_type',
        'channel_id',
        'campaign_id',
        'leads_count',
        'mql_count',
        'sql_count',
        'won_count',
        'lost_count',
        'total_spend',
        'total_revenue',
        'cpl',
        'cpmql',
        'cpsql',
        'cac',
        'roas',
        'roi',
        'lead_to_mql_rate',
        'mql_to_sql_rate',
        'sql_to_won_rate',
        'overall_conversion_rate',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'date' => 'date',
        'leads_count' => 'integer',
        'mql_count' => 'integer',
        'sql_count' => 'integer',
        'won_count' => 'integer',
        'lost_count' => 'integer',
        'total_spend' => 'decimal:2',
        'total_revenue' => 'decimal:2',
        'cpl' => 'decimal:2',
        'cpmql' => 'decimal:2',
        'cpsql' => 'decimal:2',
        'cac' => 'decimal:2',
        'roas' => 'decimal:4',
        'roi' => 'decimal:4',
        'lead_to_mql_rate' => 'decimal:2',
        'mql_to_sql_rate' => 'decimal:2',
        'sql_to_won_rate' => 'decimal:2',
        'overall_conversion_rate' => 'decimal:2',
    ];

    // ==========================================
    // RELATIONSHIPS
    // ==========================================

    /**
     * Get the marketing channel.
     */
    public function channel(): BelongsTo
    {
        return $this->belongsTo(MarketingChannel::class, 'channel_id');
    }

    /**
     * Get the campaign.
     */
    public function campaign(): BelongsTo
    {
        return $this->belongsTo(Campaign::class);
    }

    // ==========================================
    // SCOPES
    // ==========================================

    /**
     * Scope: Daily snapshots.
     */
    public function scopeDaily(Builder $query): Builder
    {
        return $query->where('period_type', 'daily');
    }

    /**
     * Scope: Weekly snapshots.
     */
    public function scopeWeekly(Builder $query): Builder
    {
        return $query->where('period_type', 'weekly');
    }

    /**
     * Scope: Monthly snapshots.
     */
    public function scopeMonthly(Builder $query): Builder
    {
        return $query->where('period_type', 'monthly');
    }

    /**
     * Scope: Overall (no channel/campaign filter).
     */
    public function scopeOverall(Builder $query): Builder
    {
        return $query->whereNull('channel_id')->whereNull('campaign_id');
    }

    /**
     * Scope: Filter by channel.
     */
    public function scopeForChannel(Builder $query, $channelId): Builder
    {
        return $query->where('channel_id', $channelId);
    }

    /**
     * Scope: Filter by campaign.
     */
    public function scopeForCampaign(Builder $query, $campaignId): Builder
    {
        return $query->where('campaign_id', $campaignId);
    }

    /**
     * Scope: Filter by date range.
     */
    public function scopeForDateRange(Builder $query, Carbon $from, Carbon $to): Builder
    {
        return $query->whereBetween('date', [$from, $to]);
    }

    /**
     * Scope: Latest snapshot for each group.
     */
    public function scopeLatest(Builder $query): Builder
    {
        return $query->orderByDesc('date');
    }

    // ==========================================
    // HELPER METHODS
    // ==========================================

    /**
     * Check if this is an overall snapshot (not filtered).
     */
    public function isOverall(): bool
    {
        return $this->channel_id === null && $this->campaign_id === null;
    }

    /**
     * Get formatted ROAS (e.g., "2.5x").
     */
    public function getFormattedRoas(): string
    {
        return number_format($this->roas, 2) . 'x';
    }

    /**
     * Get formatted ROI (e.g., "150%").
     */
    public function getFormattedRoi(): string
    {
        return number_format($this->roi, 1) . '%';
    }

    /**
     * Get summary array for API/display.
     */
    public function getSummary(): array
    {
        return [
            'date' => $this->date->format('Y-m-d'),
            'period_type' => $this->period_type,
            'leads' => [
                'total' => $this->leads_count,
                'mql' => $this->mql_count,
                'sql' => $this->sql_count,
                'won' => $this->won_count,
                'lost' => $this->lost_count,
            ],
            'financial' => [
                'spend' => $this->total_spend,
                'revenue' => $this->total_revenue,
                'profit' => $this->total_revenue - $this->total_spend,
            ],
            'kpis' => [
                'cpl' => $this->cpl,
                'cpmql' => $this->cpmql,
                'cac' => $this->cac,
                'roas' => $this->roas,
                'roi' => $this->roi,
            ],
            'conversion_rates' => [
                'lead_to_mql' => $this->lead_to_mql_rate,
                'mql_to_sql' => $this->mql_to_sql_rate,
                'sql_to_won' => $this->sql_to_won_rate,
                'overall' => $this->overall_conversion_rate,
            ],
        ];
    }
}
