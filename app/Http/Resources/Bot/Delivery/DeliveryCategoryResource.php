<?php

namespace App\Http\Resources\Bot\Delivery;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DeliveryCategoryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'icon' => $this->icon,
            'image_url' => $this->image_url,
            'sort_order' => $this->sort_order,
            'is_active' => $this->is_active,
            'items_count' => $this->whenCounted('menuItems', $this->menuItems_count ?? null),
            'children' => self::collection($this->whenLoaded('children')),
            'menu_items' => DeliveryMenuResource::collection($this->whenLoaded('menuItems')),
        ];
    }
}
