<?php

namespace App\Models;

use App\Models\Billing\BillingTransaction;
use App\Traits\BelongsToBusiness;
use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Subscription extends Model
{
    use BelongsToBusiness, HasUuid, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'business_id',
        'plan_id',
        'status',
        'billing_cycle',
        'starts_at',
        'ends_at',
        'trial_ends_at',
        'cancelled_at',
        'cancellation_reason',
        'amount',
        'currency',
        'auto_renew',
        'payment_provider',
        'last_payment_at',
        'scheduled_plan_id',
        'scheduled_change_at',
        'scheduled_cancellation_at',
        'metadata',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
        'trial_ends_at' => 'datetime',
        'cancelled_at' => 'datetime',
        'last_payment_at' => 'datetime',
        'scheduled_change_at' => 'datetime',
        'scheduled_cancellation_at' => 'datetime',
        'amount' => 'decimal:2',
        'auto_renew' => 'boolean',
        'metadata' => 'array',
    ];

    /**
     * Get the business that owns the subscription.
     */
    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    /**
     * Get the plan for the subscription.
     */
    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }

    /**
     * Get the payments for the subscription.
     */
    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    /**
     * Get the scheduled plan for this subscription.
     */
    public function scheduledPlan(): BelongsTo
    {
        return $this->belongsTo(Plan::class, 'scheduled_plan_id');
    }

    /**
     * Get billing transactions for this subscription.
     */
    public function billingTransactions(): HasMany
    {
        return $this->hasMany(BillingTransaction::class);
    }

    /**
     * Check if the subscription is currently active.
     */
    public function isActive(): bool
    {
        return in_array($this->status, ['active', 'trialing'])
            && $this->ends_at
            && $this->ends_at->isFuture();
    }

    /**
     * Check if the subscription is on a yearly billing cycle.
     */
    public function isYearly(): bool
    {
        return $this->billing_cycle === 'yearly';
    }
}
