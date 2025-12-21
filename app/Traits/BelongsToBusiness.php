<?php

namespace App\Traits;

use App\Models\Business;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

trait BelongsToBusiness
{
    /**
     * Boot the trait
     */
    protected static function bootBelongsToBusiness(): void
    {
        // Auto-assign business_id when creating
        static::creating(function ($model) {
            if (!$model->business_id && session()->has('current_business_id')) {
                $model->business_id = session('current_business_id');
            }
        });

        // Add global scope to filter by business
        static::addGlobalScope('business', function (Builder $builder) {
            if (session()->has('current_business_id')) {
                $builder->where($builder->getModel()->getTable() . '.business_id', session('current_business_id'));
            }
        });
    }

    /**
     * Get the business that owns the model
     */
    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    /**
     * Scope a query to only include models for a specific business
     */
    public function scopeForBusiness(Builder $query, string $businessId): Builder
    {
        return $query->withoutGlobalScope('business')->where('business_id', $businessId);
    }

    /**
     * Scope a query to include all businesses (bypass global scope)
     */
    public function scopeAllBusinesses(Builder $query): Builder
    {
        return $query->withoutGlobalScope('business');
    }
}
