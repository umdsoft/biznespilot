<?php

namespace App\Http\Resources\Store;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Compact menu item resource for listing pages.
 */
class MenuItemListResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'price' => (float) $this->price,
            'image' => $this->image_url,
            'preparation_time_minutes' => $this->preparation_time_minutes,
            'calories' => $this->calories,
            'dietary_tags' => $this->dietary_tags,
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
