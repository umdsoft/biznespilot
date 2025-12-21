<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StepResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'code' => $this->code,
            'phase' => $this->phase,
            'category' => $this->category,
            'order' => $this->order,
            'name' => [
                'uz' => $this->name_uz,
                'ru' => $this->name_ru,
                'en' => $this->name_en,
            ],
            'description' => [
                'uz' => $this->description_uz,
                'ru' => $this->description_ru,
                'en' => $this->description_en,
            ],
            'icon' => $this->icon,
            'is_required' => $this->is_required,
            'estimated_time_minutes' => $this->estimated_time_minutes,
            'required_fields' => $this->required_fields,
            'dependencies' => $this->getDependencies(),
            'is_active' => $this->is_active,
        ];
    }
}
