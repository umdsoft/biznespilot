<?php

namespace App\Models;

use App\Traits\BelongsToBusiness;
use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Customer extends Model
{
    use BelongsToBusiness, HasFactory, HasUuid, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'business_id',
        'lead_id',
        'dream_buyer_id',
        // Acquisition tracking
        'first_acquisition_source',
        'first_acquisition_source_type',
        'first_acquisition_channel_id',
        'first_campaign_id',
        'total_acquisition_cost',
        // Customer info
        'name',
        'email',
        'phone',
        'company',
        'address',
        'city',
        'region',
        'status',
        'type',
        'total_spent',
        'lifetime_value',
        'orders_count',
        'average_order_value',
        'first_purchase_at',
        'last_purchase_at',
        // Churn tracking
        'churn_risk_score',
        'churn_risk_level',
        'last_activity_at',
        'churned_at',
        'churn_reason',
        'days_since_last_purchase',
        'purchase_frequency_days',
        // Meta
        'notes',
        'tags',
        'custom_fields',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'total_spent' => 'decimal:2',
        'lifetime_value' => 'decimal:2',
        'total_acquisition_cost' => 'decimal:2',
        'average_order_value' => 'decimal:2',
        'churn_risk_score' => 'decimal:2',
        'orders_count' => 'integer',
        'days_since_last_purchase' => 'integer',
        'purchase_frequency_days' => 'integer',
        'first_purchase_at' => 'datetime',
        'last_purchase_at' => 'datetime',
        'last_activity_at' => 'datetime',
        'churned_at' => 'datetime',
        'tags' => 'array',
        'custom_fields' => 'array',
    ];

    /**
     * Get the lead that was converted to this customer.
     */
    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class);
    }

    /**
     * Get the orders for the customer.
     */
    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    /**
     * Get the first acquisition channel.
     */
    public function firstAcquisitionChannel(): BelongsTo
    {
        return $this->belongsTo(MarketingChannel::class, 'first_acquisition_channel_id');
    }

    /**
     * Get the first campaign that acquired this customer.
     */
    public function firstCampaign(): BelongsTo
    {
        return $this->belongsTo(Campaign::class, 'first_campaign_id');
    }

    /**
     * Scope: Churn xavfi bo'yicha
     */
    public function scopeChurnRisk($query, string $level)
    {
        return $query->where('churn_risk_level', $level);
    }

    /**
     * Scope: High va Critical churn xavfi
     */
    public function scopeHighChurnRisk($query)
    {
        return $query->whereIn('churn_risk_level', ['high', 'critical']);
    }

    /**
     * Scope: Churn bo'lganlar
     */
    public function scopeChurned($query)
    {
        return $query->whereNotNull('churned_at');
    }

    /**
     * Scope: Faol (churn bo'lmagan)
     */
    public function scopeActive($query)
    {
        return $query->whereNull('churned_at');
    }

    /**
     * Scope: Source type bo'yicha
     */
    public function scopeBySourceType($query, string $sourceType)
    {
        return $query->where('first_acquisition_source_type', $sourceType);
    }

    /**
     * CLV/CAC ratio hisoblash
     */
    public function getLtvCacRatioAttribute(): ?float
    {
        if (!$this->lifetime_value || !$this->total_acquisition_cost || $this->total_acquisition_cost == 0) {
            return null;
        }

        return round($this->lifetime_value / $this->total_acquisition_cost, 2);
    }

    /**
     * Churn xavfi haqida ma'lumot
     */
    public function getChurnRiskInfoAttribute(): array
    {
        $colors = [
            'low' => ['bg' => 'bg-green-100', 'text' => 'text-green-800', 'label' => 'Past'],
            'medium' => ['bg' => 'bg-yellow-100', 'text' => 'text-yellow-800', 'label' => 'O\'rtacha'],
            'high' => ['bg' => 'bg-orange-100', 'text' => 'text-orange-800', 'label' => 'Yuqori'],
            'critical' => ['bg' => 'bg-red-100', 'text' => 'text-red-800', 'label' => 'Kritik'],
        ];

        return $colors[$this->churn_risk_level] ?? $colors['low'];
    }
}
