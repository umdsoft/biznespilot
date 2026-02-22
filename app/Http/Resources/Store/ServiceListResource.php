<?php

namespace App\Http\Resources\Store;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Compact service resource for listing pages.
 */
class ServiceListResource extends JsonResource
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
            'image' => $this->image_url,
            'duration_minutes' => $this->duration_minutes,
            'requires_staff' => $this->requires_staff,
            'category_name' => $this->when(
                $this->relationLoaded('category'),
                fn () => $this->category?->name
            ),
            'category_id' => $this->category_id,
            'is_active' => $this->is_active,
            'is_featured' => $this->is_featured,
        ];
    }
}
