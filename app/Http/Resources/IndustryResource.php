<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class IndustryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'slug' => $this->slug,
            'name_uz' => $this->name_uz,
            'name_en' => $this->name_en,
            'name' => [
                'uz' => $this->name_uz,
                'en' => $this->name_en,
            ],
            'icon' => $this->icon,
            'parent_id' => $this->parent_id,
            'is_active' => $this->is_active,
            'sort_order' => $this->sort_order,
            'children' => IndustryResource::collection($this->whenLoaded('children')),
        ];
    }
}
