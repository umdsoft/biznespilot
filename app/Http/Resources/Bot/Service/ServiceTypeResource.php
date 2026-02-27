<?php

namespace App\Http\Resources\Bot\Service;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ServiceTypeResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'price_from' => (float) $this->price_from,
            'price_to' => $this->price_to ? (float) $this->price_to : null,
            'estimated_duration' => $this->estimated_duration,
            'warranty_days' => $this->warranty_days,
            'is_active' => $this->is_active,
            'category' => $this->whenLoaded('category', fn () => [
                'id' => $this->category->id,
                'name' => $this->category->name,
            ]),
        ];
    }
}
