<?php

namespace App\Http\Resources\Store;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Compact product resource for listing pages.
 *
 * Contains minimal data for product cards: id, name, slug, price,
 * primary image, category name, and stock status.
 */
class ProductListResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'price' => (float) $this->price,
            'compare_price' => $this->compare_price ? (float) $this->compare_price : null,
            'discount_percent' => $this->getDiscountPercent(),
            'has_discount' => $this->hasDiscount(),
            'primary_image' => $this->when(
                $this->relationLoaded('primaryImage'),
                fn () => $this->primaryImage?->image_url
            ),
            'category_name' => $this->when(
                $this->relationLoaded('category'),
                fn () => $this->category?->name
            ),
            'category_id' => $this->category_id,
            'in_stock' => $this->isInStock(),
            'is_featured' => $this->is_featured,
            'rating' => $this->when(
                $this->relationLoaded('approvedReviews'),
                fn () => $this->approvedReviews->count() > 0
                    ? round($this->approvedReviews->avg('rating'), 1)
                    : 0
            ),
            'reviews_count' => $this->when(
                $this->relationLoaded('approvedReviews'),
                fn () => $this->approvedReviews->count()
            ),
        ];
    }
}
