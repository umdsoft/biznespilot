<?php

namespace App\Models\Traits;

use App\Models\Business;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Provides common business relationship functionality
 * Reduces code duplication across models that belong to a business
 */
trait BelongsToBusiness
{
    /**
     * Get the business that owns this model.
     */
    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    /**
     * Scope a query to a specific business.
     */
    public function scopeForBusiness(Builder $query, $businessId): Builder
    {
        if ($businessId instanceof Business) {
            $businessId = $businessId->id;
        }

        return $query->where('business_id', $businessId);
    }

    /**
     * Scope a query to the current business from session.
     */
    public function scopeCurrentBusiness(Builder $query): Builder
    {
        $businessId = session('current_business_id');

        if (!$businessId) {
            return $query->whereRaw('1 = 0'); // Return empty result if no business in session
        }

        return $query->where('business_id', $businessId);
    }

    /**
     * Check if this model belongs to the given business.
     */
    public function belongsToBusinessId($businessId): bool
    {
        if ($businessId instanceof Business) {
            $businessId = $businessId->id;
        }

        return $this->business_id === $businessId;
    }

    /**
     * Check if this model belongs to the current session business.
     */
    public function belongsToCurrentBusiness(): bool
    {
        $currentBusinessId = session('current_business_id');
        return $currentBusinessId && $this->business_id === $currentBusinessId;
    }
}
