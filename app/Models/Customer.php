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
        'name',
        'email',
        'phone',
        'company',
        'address',
        'city',
        'country',
        'status',
        'ltv',
        'total_spent',
        'total_orders',
        'acquisition_date',
        'acquisition_source',
        'data',
        'notes',
        'last_purchase_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'ltv' => 'decimal:2',
        'total_spent' => 'decimal:2',
        'acquisition_date' => 'date',
        'data' => 'array',
        'last_purchase_at' => 'datetime',
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
