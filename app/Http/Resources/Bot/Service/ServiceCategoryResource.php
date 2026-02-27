<?php

namespace App\Http\Resources\Bot\Service;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ServiceCategoryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'icon' => $this->icon,
            'image_url' => $this->image_url,
            'is_active' => $this->is_active,
            'sort_order' => $this->sort_order,
            'service_types' => ServiceTypeResource::collection($this->whenLoaded('serviceTypes')),
            'masters_count' => $this->whenCounted('masters', $this->masters_count ?? null),
        ];
    }
}
