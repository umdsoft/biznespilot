<?php

namespace App\Http\Resources\Bot\Service;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ServiceRequestListResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'request_number' => $this->request_number,
            'customer_name' => $this->customer_name,
            'status' => $this->status,
            'category_name' => $this->whenLoaded('category', fn () => $this->category?->name),
            'total_cost' => $this->total_cost ? (float) $this->total_cost : null,
            'rating' => $this->rating,
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
