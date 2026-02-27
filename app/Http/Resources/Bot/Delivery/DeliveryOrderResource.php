<?php

namespace App\Http\Resources\Bot\Delivery;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DeliveryOrderResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'order_number' => $this->order_number,
            'customer_name' => $this->customer_name,
            'customer_phone' => $this->customer_phone,
            'status' => $this->status,
            'delivery_type' => $this->delivery_type,
            'delivery_address' => $this->delivery_address,
            'delivery_landmark' => $this->delivery_landmark,
            'delivery_lat' => $this->delivery_lat ? (float) $this->delivery_lat : null,
            'delivery_lng' => $this->delivery_lng ? (float) $this->delivery_lng : null,
            'scheduled_at' => $this->scheduled_at?->toIso8601String(),
            'estimated_delivery' => $this->estimated_delivery,
            'subtotal' => (float) $this->subtotal,
            'delivery_fee' => (float) $this->delivery_fee,
            'service_fee' => (float) $this->service_fee,
            'discount_amount' => (float) $this->discount_amount,
            'total' => (float) $this->total,
            'payment_method' => $this->payment_method,
            'payment_status' => $this->payment_status,
            'coupon_code' => $this->coupon_code,
            'notes' => $this->notes,
            'courier' => $this->courier_name ? [
                'name' => $this->courier_name,
                'phone' => $this->courier_phone,
            ] : null,
            'cancel_reason' => $this->cancel_reason,
            'items' => $this->whenLoaded('items', fn () => $this->items->map(fn ($item) => [
                'id' => $item->id,
                'item_name' => $item->item_name,
                'variant_name' => $item->variant_name,
                'quantity' => $item->quantity,
                'unit_price' => (float) $item->unit_price,
                'addons' => $item->addons,
                'addons_total' => (float) $item->addons_total,
                'subtotal' => (float) $item->subtotal,
                'special_instructions' => $item->special_instructions,
            ])),
            'confirmed_at' => $this->confirmed_at?->toIso8601String(),
            'preparing_at' => $this->preparing_at?->toIso8601String(),
            'ready_at' => $this->ready_at?->toIso8601String(),
            'delivering_at' => $this->delivering_at?->toIso8601String(),
            'delivered_at' => $this->delivered_at?->toIso8601String(),
            'cancelled_at' => $this->cancelled_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
