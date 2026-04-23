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
            // Callers MUST use withCount or loadCount — the lazy fallback was
            // silently firing one count() per category (N+1). Use:
            //   $store->categories()->withCount(['products as active_products_count'
            //     => fn ($q) => $q->where('is_active', true)])
            'products_count' => $this->active_products_count ?? $this->products_count ?? 0,
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
