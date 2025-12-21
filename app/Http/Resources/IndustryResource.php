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
            'code' => $this->code,
            'name' => [
                'uz' => $this->name_uz,
                'ru' => $this->name_ru,
                'en' => $this->name_en,
            ],
            'icon' => $this->icon,
            'parent_id' => $this->parent_id,
            'is_active' => $this->is_active,
            'order' => $this->order,
            'children' => IndustryResource::collection($this->whenLoaded('children')),
        ];
    }
}
