<?php

namespace App\Http\Resources\Bot\Queue;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class QueueBookingResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'booking_number' => $this->booking_number,
            'customer_name' => $this->customer_name,
            'customer_phone' => $this->customer_phone,
            'service' => $this->whenLoaded('service', fn () => [
                'id' => $this->service->id,
                'name' => $this->service->name,
            ]),
            'branch' => $this->whenLoaded('branch', fn () => [
                'id' => $this->branch->id,
                'name' => $this->branch->name,
                'address' => $this->branch->address,
            ]),
            'specialist' => $this->whenLoaded('specialist', fn () => $this->specialist ? [
                'id' => $this->specialist->id,
                'name' => $this->specialist->name,
                'avatar_url' => $this->specialist->avatar_url,
            ] : null),
            'date' => $this->date?->toDateString(),
            'start_time' => $this->start_time,
            'end_time' => $this->end_time,
            'queue_number' => $this->queue_number,
            'status' => $this->status,
            'people_ahead' => $this->people_ahead,
            'estimated_wait' => $this->estimated_wait,
            'price' => $this->price ? (float) $this->price : null,
            'payment_status' => $this->payment_status,
            'payment_method' => $this->payment_method,
            'notes' => $this->notes,
            'rating' => $this->rating,
            'review' => $this->review,
            'confirmed_at' => $this->confirmed_at?->toIso8601String(),
            'started_at' => $this->started_at?->toIso8601String(),
            'completed_at' => $this->completed_at?->toIso8601String(),
            'cancelled_at' => $this->cancelled_at?->toIso8601String(),
            'cancel_reason' => $this->cancel_reason,
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
