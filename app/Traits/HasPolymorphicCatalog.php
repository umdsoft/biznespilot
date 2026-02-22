<?php

namespace App\Traits;

use App\Models\Store\StoreCartItem;
use App\Models\Store\StoreOrderItem;
use App\Models\Store\StoreReview;

/**
 * Barcha catalog modellar uchun umumiy polimorfik relationships.
 * StoreProduct, StoreService, StoreMenuItem, etc. da ishlatiladi.
 */
trait HasPolymorphicCatalog
{
    public function catalogOrderItems()
    {
        return $this->morphMany(StoreOrderItem::class, 'item');
    }

    public function catalogCartItems()
    {
        return $this->morphMany(StoreCartItem::class, 'item');
    }

    public function catalogReviews()
    {
        return $this->morphMany(StoreReview::class, 'reviewable');
    }

    public function approvedCatalogReviews()
    {
        return $this->catalogReviews()->where('is_approved', true);
    }

    public function getCatalogAverageRating(): float
    {
        return (float) ($this->approvedCatalogReviews()->avg('rating') ?? 0);
    }

    public function getCatalogReviewsCount(): int
    {
        return $this->approvedCatalogReviews()->count();
    }
}
