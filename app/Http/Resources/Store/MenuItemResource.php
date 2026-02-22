<?php

namespace App\Http\Resources\Store;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Full menu item resource with modifiers, allergens, and dietary info.
 */
class MenuItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'description' => $this->description,
            'price' => (float) $this->price,
            'image' => $this->image_url,
            'preparation_time_minutes' => $this->preparation_time_minutes,
            'calories' => $this->calories,
            'portion_size' => $this->portion_size,
            'allergens' => $this->allergens,
            'dietary_tags' => $this->dietary_tags,
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

            // Modifiers
            'modifiers' => $this->when(
                $this->relationLoaded('modifiers'),
                fn () => $this->modifiers->map(fn ($mod) => [
                    'id' => $mod->id,
                    'name' => $mod->name,
                    'price' => (float) $mod->price,
                    'is_required' => $mod->is_required,
                    'sort_order' => $mod->sort_order,
                ])
            ),

            'catalog_type' => 'menu_item',
            'attributes' => $this->getCatalogAttributes(),
            'metadata' => $this->metadata,
            'created_at' => $this->created_at->toISOString(),
        ];
    }
}
