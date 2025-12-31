<?php

namespace App\Models;

use App\Traits\BelongsToBusiness;
use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Customer extends Model
{
    use BelongsToBusiness, SoftDeletes, HasUuid;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'business_id',
        'lead_id',
        'dream_buyer_id',
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
        'orders_count',
        'average_order_value',
        'first_purchase_at',
        'last_purchase_at',
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
        'average_order_value' => 'decimal:2',
        'orders_count' => 'integer',
        'first_purchase_at' => 'datetime',
        'last_purchase_at' => 'datetime',
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
}
