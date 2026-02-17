<?php

namespace App\Models;

use App\Traits\BelongsToBusiness;
use App\Traits\HasUuid;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Sale model for tracking sales and conversions
 * Used by KPICalculator for CAC, ROAS, ROI calculations
 * Integrated with Marketing Attribution system
 */
class Sale extends Model
{
    use BelongsToBusiness, HasUuid, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'business_id',
        'order_id',
        'product_id',
        'customer_id',
        'lead_id',
        'campaign_id',
        'marketing_channel_id',
        'attribution_data',
        'attribution_source_type',
        'acquisition_cost',
        'attributed_spend',
        'amount',
        'cost',
        'profit',
        'currency',
        'sale_date',
        'closed_at',
        'closed_by',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'amount' => 'decimal:2',
        'cost' => 'decimal:2',
        'profit' => 'decimal:2',
        'sale_date' => 'date',
        'closed_at' => 'datetime',
        'attribution_data' => 'array',
        'acquisition_cost' => 'decimal:2',
        'attributed_spend' => 'decimal:2',
    ];

    // ==========================================
    // RELATIONSHIPS
    // ==========================================

    /**
     * Get the customer associated with the sale.
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    /**
     * Get the order associated with the sale.
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * Get the product associated with the sale.
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Get the marketing channel associated with the sale.
     */
    public function marketingChannel(): BelongsTo
    {
        return $this->belongsTo(MarketingChannel::class);
    }

    /**
     * Get the lead that was converted to this sale.
     */
    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class);
    }

    /**
     * Get the campaign that generated this sale.
     */
    public function campaign(): BelongsTo
    {
        return $this->belongsTo(Campaign::class);
    }

    /**
     * Get the user who closed this sale.
     */
    public function closedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'closed_by');
    }

    // ==========================================
    // SCOPES
    // ==========================================

    /**
     * Scope: Filter by campaign.
     */
    public function scopeFromCampaign(Builder $query, $campaignId): Builder
    {
        return $query->where('campaign_id', $campaignId);
    }

    /**
     * Scope: Filter by marketing channel.
     */
    public function scopeFromChannel(Builder $query, $channelId): Builder
    {
        return $query->where('marketing_channel_id', $channelId);
    }

    /**
     * Scope: Sales with attribution data.
     */
    public function scopeWithAttribution(Builder $query): Builder
    {
        return $query->whereNotNull('lead_id');
    }

    /**
     * Scope: Sales without attribution.
     */
    public function scopeWithoutAttribution(Builder $query): Builder
    {
        return $query->whereNull('lead_id');
    }

    /**
     * Scope: Filter by date range.
     */
    public function scopeCreatedBetween(Builder $query, Carbon $from, Carbon $to): Builder
    {
        return $query->whereBetween('created_at', [$from, $to]);
    }

    /**
     * Scope: Filter by closed date range.
     */
    public function scopeClosedBetween(Builder $query, Carbon $from, Carbon $to): Builder
    {
        return $query->whereBetween('closed_at', [$from, $to]);
    }

    // ==========================================
    // HELPER METHODS
    // ==========================================

    /**
     * Check if sale has marketing attribution.
     */
    public function hasAttribution(): bool
    {
        return $this->lead_id !== null || $this->campaign_id !== null;
    }

    /**
     * Get the attribution source name.
     */
    public function getAttributionSource(): ?string
    {
        // First check attribution_data for UTM source
        if ($this->attribution_data && isset($this->attribution_data['utm']['utm_source'])) {
            return $this->attribution_data['utm']['utm_source'];
        }

        // Then check marketing channel
        if ($this->marketingChannel) {
            return $this->marketingChannel->name;
        }

        // Then check lead source
        if ($this->lead && $this->lead->source) {
            return $this->lead->source->name;
        }

        return null;
    }

    /**
     * Get attribution channel type.
     */
    public function getAttributionChannelType(): ?string
    {
        if ($this->marketingChannel) {
            return $this->marketingChannel->type;
        }

        if ($this->attribution_data && isset($this->attribution_data['channel']['type'])) {
            return $this->attribution_data['channel']['type'];
        }

        return null;
    }

    /**
     * Calculate profit if not set.
     */
    public function calculateProfit(): float
    {
        if ($this->profit !== null) {
            return (float) $this->profit;
        }

        return (float) $this->amount - (float) ($this->cost ?? 0);
    }
}
