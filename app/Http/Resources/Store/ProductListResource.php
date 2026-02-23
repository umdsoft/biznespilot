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
        $imageUrl = $this->when(
            $this->relationLoaded('primaryImage'),
            fn () => $this->primaryImage?->image_url
        );

        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            // ProductCard format: price = original, sale_price = discounted
            'price' => $this->compare_price ? (float) $this->compare_price : (float) $this->price,
            'sale_price' => $this->compare_price ? (float) $this->price : null,
            'compare_price' => $this->compare_price ? (float) $this->compare_price : null,
            'discount_percent' => $this->getDiscountPercent(),
            'has_discount' => $this->hasDiscount(),
            'image' => $imageUrl,
            'primary_image' => $imageUrl,
            'category_name' => $this->when(
                $this->relationLoaded('category'),
                fn () => $this->category?->name
            ),
            'category_id' => $this->category_id,
            'stock' => $this->track_stock ? $this->stock_quantity : 99,
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
