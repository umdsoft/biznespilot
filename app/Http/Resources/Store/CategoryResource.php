<?php

namespace App\Http\Resources\Store;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Store category resource with nested children and product counts.
 */
class CategoryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'image_url' => $this->image_url,
            'sort_order' => $this->sort_order,
            'products_count' => $this->when(
                $this->relationLoaded('products'),
                fn () => $this->products->where('is_active', true)->count(),
                fn () => $this->products()->where('is_active', true)->count()
            ),
            'children' => $this->when(
                $this->relationLoaded('children'),
                fn () => CategoryResource::collection(
                    $this->children->where('is_active', true)->sortBy('sort_order')
                )
            ),
            'parent_id' => $this->parent_id,
        ];
    }
}
