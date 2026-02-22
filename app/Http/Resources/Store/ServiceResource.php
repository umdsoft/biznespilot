<?php

namespace App\Http\Resources\Store;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Full service resource with category and all attributes.
 */
class ServiceResource extends JsonResource
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
            'image' => $this->image_url,
            'duration_minutes' => $this->duration_minutes,
            'max_capacity' => $this->max_capacity,
            'requires_staff' => $this->requires_staff,
            'is_active' => $this->is_active,
            'is_featured' => $this->is_featured,
            'sort_order' => $this->sort_order,

            // Category
            'category' => $this->when(
                $this->relationLoaded('category'),
                fn () => $this->category ? [
                    'id' => $this->category->id,
                    'name' => $this->category->name,
                ] : null
            ),

            'catalog_type' => 'service',
            'attributes' => $this->getCatalogAttributes(),
            'metadata' => $this->metadata,
            'created_at' => $this->created_at->toISOString(),
        ];
    }
}
