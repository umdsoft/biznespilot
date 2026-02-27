<?php

namespace App\Http\Resources\Bot\Service;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ServiceRequestResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'request_number' => $this->request_number,
            'customer_name' => $this->customer_name,
            'customer_phone' => $this->customer_phone,
            'category' => $this->whenLoaded('category', fn () => [
                'id' => $this->category->id,
                'name' => $this->category->name,
                'icon' => $this->category->icon,
            ]),
            'service_type' => $this->whenLoaded('serviceType', fn () => [
                'id' => $this->serviceType->id,
                'name' => $this->serviceType->name,
                'price_from' => (float) $this->serviceType->price_from,
                'warranty_days' => $this->serviceType->warranty_days,
            ]),
            'master' => $this->whenLoaded('master', fn () => $this->master ? [
                'id' => $this->master->id,
                'name' => $this->master->name,
                'phone' => $this->master->phone,
                'avatar_url' => $this->master->avatar_url,
                'rating_avg' => (float) $this->master->rating_avg,
            ] : null),
            'status' => $this->status,
            'description' => $this->description,
            'images' => $this->images,
            'address' => $this->address,
            'landmark' => $this->landmark,
            'lat' => $this->lat ? (float) $this->lat : null,
            'lng' => $this->lng ? (float) $this->lng : null,
            'preferred_date' => $this->preferred_date?->toDateString(),
            'preferred_time_slot' => $this->preferred_time_slot,
            'diagnosis_notes' => $this->diagnosis_notes,
            'work_description' => $this->work_description,
            'parts_used' => $this->parts_used,
            'labor_cost' => $this->labor_cost ? (float) $this->labor_cost : null,
            'parts_cost' => $this->parts_cost ? (float) $this->parts_cost : null,
            'total_cost' => $this->total_cost ? (float) $this->total_cost : null,
            'cost_approved' => $this->cost_approved,
            'payment_method' => $this->payment_method,
            'payment_status' => $this->payment_status,
            'warranty_until' => $this->warranty_until?->toDateString(),
            'rating' => $this->rating,
            'review' => $this->review,
            'assigned_at' => $this->assigned_at?->toIso8601String(),
            'en_route_at' => $this->en_route_at?->toIso8601String(),
            'arrived_at' => $this->arrived_at?->toIso8601String(),
            'diagnosing_at' => $this->diagnosing_at?->toIso8601String(),
            'in_progress_at' => $this->in_progress_at?->toIso8601String(),
            'completed_at' => $this->completed_at?->toIso8601String(),
            'cancelled_at' => $this->cancelled_at?->toIso8601String(),
            'cancel_reason' => $this->cancel_reason,
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
