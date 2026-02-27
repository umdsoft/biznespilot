<?php

namespace App\Http\Resources\Bot\Queue;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class QueueBranchResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'address' => $this->address,
            'phone' => $this->phone,
            'lat' => $this->lat ? (float) $this->lat : null,
            'lng' => $this->lng ? (float) $this->lng : null,
            'working_hours' => $this->working_hours,
            'slot_duration' => $this->slot_duration,
            'is_active' => $this->is_active,
            'specialists_count' => $this->whenCounted('specialists'),
            'services' => QueueServiceResource::collection($this->whenLoaded('services')),
        ];
    }
}
