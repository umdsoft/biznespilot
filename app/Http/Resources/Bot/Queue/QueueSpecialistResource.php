<?php

namespace App\Http\Resources\Bot\Queue;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class QueueSpecialistResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'phone' => $this->phone,
            'avatar_url' => $this->avatar_url,
            'specialization' => $this->specialization,
            'bio' => $this->bio,
            'rating_avg' => $this->rating_avg ? (float) $this->rating_avg : null,
            'rating_count' => $this->rating_count,
            'is_active' => $this->is_active,
            'services' => QueueServiceResource::collection($this->whenLoaded('services')),
        ];
    }
}
