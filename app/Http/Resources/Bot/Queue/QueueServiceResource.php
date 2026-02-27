<?php

namespace App\Http\Resources\Bot\Queue;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class QueueServiceResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'description' => $this->description,
            'icon' => $this->icon,
            'duration_min' => $this->duration_min,
            'duration_max' => $this->duration_max,
            'price' => $this->price ? (float) $this->price : null,
            'is_active' => $this->is_active,
            'sort_order' => $this->sort_order,
        ];
    }
}
