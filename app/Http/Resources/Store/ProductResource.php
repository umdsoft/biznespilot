<?php

namespace App\Http\Resources\Store;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Full product resource with images, variants, category, and reviews info.
 */
class ProductResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'description' => $this->description,
            'price' => (float) $this->price,
            'compare_price' => $this->compare_price ? (float) $this->compare_price : null,
            'discount_percent' => $this->getDiscountPercent(),
            'has_discount' => $this->hasDiscount(),
            'sku' => $this->sku,
            'in_stock' => $this->isInStock(),
            'stock_quantity' => $this->when($this->track_stock, $this->stock_quantity),
            'track_stock' => $this->track_stock,
            'is_featured' => $this->is_featured,
            'sort_order' => $this->sort_order,

            // Images
            'images' => $this->when(
                $this->relationLoaded('images'),
                fn () => $this->images->map(fn ($img) => [
                    'id' => $img->id,
                    'image_url' => $img->image_url,
                    'is_primary' => $img->is_primary,
                    'sort_order' => $img->sort_order,
                ])
            ),
            'primary_image' => $this->when(
                $this->relationLoaded('primaryImage'),
                fn () => $this->primaryImage?->image_url
            ),

            // Variants
            'variants' => $this->when(
                $this->relationLoaded('activeVariants') || $this->relationLoaded('variants'),
                function () {
                    $variants = $this->relationLoaded('activeVariants')
                        ? $this->activeVariants
                        : $this->variants->where('is_active', true);

                    return $variants->map(fn ($v) => [
                        'id' => $v->id,
                        'name' => $v->name,
                        'sku' => $v->sku,
                        'price' => (float) $v->price,
                        'stock_quantity' => $this->when($this->track_stock, $v->stock_quantity),
                        'in_stock' => $v->isInStock(),
                        'attributes' => $v->attributes,
                    ]);
                }
            ),
            'has_variants' => $this->when(
                $this->relationLoaded('activeVariants') || $this->relationLoaded('variants'),
                function () {
                    $variants = $this->relationLoaded('activeVariants')
                        ? $this->activeVariants
                        : $this->variants->where('is_active', true);

                    return $variants->count() > 0;
                }
            ),

            // Category
            'category' => $this->when(
                $this->relationLoaded('category'),
                fn () => $this->category ? [
                    'id' => $this->category->id,
                    'name' => $this->category->name,
                    'slug' => $this->category->slug,
                ] : null
            ),

            // Reviews
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
            'reviews' => $this->when(
                $this->relationLoaded('approvedReviews') && $request->routeIs('*products.show*'),
                fn () => $this->approvedReviews->take(10)->map(fn ($review) => [
                    'id' => $review->id,
                    'rating' => $review->rating,
                    'comment' => $review->comment,
                    'customer_name' => $review->customer?->getDisplayName() ?? 'Anonim',
                    'created_at' => $review->created_at->toISOString(),
                ])
            ),

            'metadata' => $this->metadata,
            'created_at' => $this->created_at->toISOString(),
        ];
    }
}
