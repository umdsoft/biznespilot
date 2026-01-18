<?php

namespace App\Models;

use App\Traits\BelongsToBusiness;
use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Sale model for tracking sales and conversions
 * Used by KPICalculator for CAC, ROAS, ROI calculations
 */
class Sale extends Model
{
    use BelongsToBusiness, HasUuid, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     * Matches the actual database schema from create_crm_tables migration
     *
     * @var array<string>
     */
    protected $fillable = [
        'business_id',
        'order_id',
        'product_id',
        'customer_id',
        'marketing_channel_id',
        'amount',
        'cost',
        'profit',
        'currency',
        'sale_date',
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
    ];

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
}
