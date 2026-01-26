<?php

namespace App\Models;

use App\Traits\BelongsToBusiness;
use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Order Model - Buyurtmalar.
 *
 * Marketing Attribution: Har bir buyurtma Customer -> Lead zanjiri orqali
 * marketing kanaliga bog'langan. Bu "Black Box" konsepsiyasi uchun muhim.
 *
 * @property string $id
 * @property string $business_id
 * @property string $customer_id
 * @property string|null $campaign_id
 * @property string|null $marketing_channel_id
 * @property string|null $lead_id
 * @property string|null $utm_source
 * @property string|null $utm_medium
 * @property string|null $utm_campaign
 * @property string|null $utm_content
 * @property string|null $utm_term
 * @property string|null $attribution_source_type
 * @property array|null $attribution_data
 * @property float $attributed_acquisition_cost
 */
class Order extends Model
{
    use BelongsToBusiness, HasUuid, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'business_id',
        'customer_id',
        // Marketing Attribution
        'campaign_id',
        'marketing_channel_id',
        'lead_id',
        'utm_source',
        'utm_medium',
        'utm_campaign',
        'utm_content',
        'utm_term',
        'attribution_source_type',
        'attribution_data',
        'attributed_acquisition_cost',
        // Order info
        'order_number',
        'subtotal',
        'tax',
        'discount',
        'shipping',
        'total_amount',
        'currency',
        'status',
        'payment_status',
        'payment_method',
        'shipping_address',
        'billing_address',
        'notes',
        'ordered_at',
        'paid_at',
        'shipped_at',
        'delivered_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'subtotal' => 'decimal:2',
        'tax' => 'decimal:2',
        'discount' => 'decimal:2',
        'shipping' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'attributed_acquisition_cost' => 'decimal:2',
        'attribution_data' => 'array',
        'ordered_at' => 'datetime',
        'paid_at' => 'datetime',
        'shipped_at' => 'datetime',
        'delivered_at' => 'datetime',
    ];

    // ==========================================
    // RELATIONSHIPS
    // ==========================================

    /**
     * Get the customer that owns the order.
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    /**
     * Get the order items for the order.
     */
    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    /**
     * Get the campaign for this order (Marketing Attribution).
     */
    public function campaign(): BelongsTo
    {
        return $this->belongsTo(Campaign::class);
    }

    /**
     * Get the marketing channel for this order (Marketing Attribution).
     */
    public function marketingChannel(): BelongsTo
    {
        return $this->belongsTo(MarketingChannel::class);
    }

    /**
     * Get the original lead for this order.
     */
    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class);
    }

    // ==========================================
    // ATTRIBUTION METHODS
    // ==========================================

    /**
     * Check if order has marketing attribution.
     */
    public function hasAttribution(): bool
    {
        return $this->campaign_id !== null
            || $this->marketing_channel_id !== null
            || $this->utm_source !== null;
    }

    /**
     * Get UTM parameters as array.
     */
    public function getUtmArray(): array
    {
        return [
            'utm_source' => $this->utm_source,
            'utm_medium' => $this->utm_medium,
            'utm_campaign' => $this->utm_campaign,
            'utm_content' => $this->utm_content,
            'utm_term' => $this->utm_term,
        ];
    }

    /**
     * Get full attribution summary.
     */
    public function getAttributionSummary(): array
    {
        // Agar cached attribution_data mavjud bo'lsa
        if ($this->attribution_data) {
            return $this->attribution_data;
        }

        return [
            'campaign_id' => $this->campaign_id,
            'campaign_name' => $this->campaign?->name,
            'channel_id' => $this->marketing_channel_id,
            'channel_name' => $this->marketingChannel?->name,
            'lead_id' => $this->lead_id,
            'source_type' => $this->attribution_source_type,
            'utm' => $this->getUtmArray(),
            'acquisition_cost' => $this->attributed_acquisition_cost,
        ];
    }

    /**
     * Inherit attribution from Customer -> Lead chain.
     * Bu metod OrderObserver tomonidan avtomatik chaqiriladi.
     */
    public function inheritAttributionFromCustomer(): void
    {
        $customer = $this->customer;
        if (!$customer) {
            return;
        }

        // Avval Customer dan olishga harakat qilish
        if ($customer->first_campaign_id || $customer->first_acquisition_channel_id) {
            $this->campaign_id = $this->campaign_id ?? $customer->first_campaign_id;
            $this->marketing_channel_id = $this->marketing_channel_id ?? $customer->first_acquisition_channel_id;
            $this->attribution_source_type = $this->attribution_source_type ?? $customer->first_acquisition_source_type;
            $this->attributed_acquisition_cost = $customer->total_acquisition_cost ?? 0;
        }

        // Keyin Lead dan qo'shimcha ma'lumotlarni olish
        $lead = $customer->lead;
        if ($lead) {
            $this->lead_id = $lead->id;
            $this->campaign_id = $this->campaign_id ?? $lead->campaign_id;
            $this->marketing_channel_id = $this->marketing_channel_id ?? $lead->marketing_channel_id;
            $this->utm_source = $this->utm_source ?? $lead->utm_source;
            $this->utm_medium = $this->utm_medium ?? $lead->utm_medium;
            $this->utm_campaign = $this->utm_campaign ?? $lead->utm_campaign;
            $this->utm_content = $this->utm_content ?? $lead->utm_content;
            $this->utm_term = $this->utm_term ?? $lead->utm_term;
            $this->attribution_source_type = $this->attribution_source_type ?? $lead->acquisition_source_type;

            // Full attribution data ni saqlash
            $this->attribution_data = $lead->getAttributionSummary();
        }
    }

    // ==========================================
    // SCOPES
    // ==========================================

    /**
     * Scope: Filter by campaign.
     */
    public function scopeFromCampaign(Builder $query, string $campaignId): Builder
    {
        return $query->where('campaign_id', $campaignId);
    }

    /**
     * Scope: Filter by marketing channel.
     */
    public function scopeFromChannel(Builder $query, string $channelId): Builder
    {
        return $query->where('marketing_channel_id', $channelId);
    }

    /**
     * Scope: Orders with attribution.
     */
    public function scopeWithAttribution(Builder $query): Builder
    {
        return $query->where(function ($q) {
            $q->whereNotNull('campaign_id')
              ->orWhereNotNull('marketing_channel_id')
              ->orWhereNotNull('utm_source');
        });
    }

    /**
     * Scope: Orders without attribution (muammo - tracking kerak).
     */
    public function scopeWithoutAttribution(Builder $query): Builder
    {
        return $query->whereNull('campaign_id')
            ->whereNull('marketing_channel_id')
            ->whereNull('utm_source');
    }
}
