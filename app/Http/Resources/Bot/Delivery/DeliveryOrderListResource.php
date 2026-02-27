<?php

namespace App\Http\Resources\Bot\Delivery;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DeliveryOrderListResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'order_number' => $this->order_number,
            'customer_name' => $this->customer_name,
            'status' => $this->status,
            'delivery_type' => $this->delivery_type,
            'total' => (float) $this->total,
            'payment_method' => $this->payment_method,
            'payment_status' => $this->payment_status,
            'items_count' => $this->whenLoaded('items', fn () => $this->items->sum('quantity')),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
