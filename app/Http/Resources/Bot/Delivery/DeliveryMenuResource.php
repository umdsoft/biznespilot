<?php

namespace App\Http\Resources\Bot\Delivery;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DeliveryMenuResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'image_url' => $this->image_url,
            'base_price' => (float) $this->base_price,
            'sale_price' => $this->sale_price ? (float) $this->sale_price : null,
            'effective_price' => (float) $this->effective_price,
            'has_discount' => $this->hasDiscount(),
            'preparation_time' => $this->preparation_time,
            'rating_avg' => (float) $this->rating_avg,
            'rating_count' => $this->rating_count,
            'is_popular' => $this->is_popular,
            'is_available' => $this->is_available,
            'category' => $this->whenLoaded('category', fn () => [
                'id' => $this->category->id,
                'name' => $this->category->name,
            ]),
        ];
    }
}
