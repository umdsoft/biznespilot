<?php

namespace App\Http\Resources\Store;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Order resource with items, payment details, status history, and timestamps.
 */
class OrderResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'order_number' => $this->order_number,
            'status' => $this->status,
            'status_label' => $this->getStatusLabel(),
            'is_active' => $this->isActive(),
            'is_cancellable' => $this->isCancellable(),

            // Amounts
            'subtotal' => (float) $this->subtotal,
            'delivery_fee' => (float) $this->delivery_fee,
            'discount_amount' => (float) $this->discount_amount,
            'total' => (float) $this->total,
            'promo_code' => $this->promo_code,

            // Payment
            'payment_method' => $this->payment_method,
            'payment_status' => $this->payment_status,
            'is_paid' => $this->isPaid(),

            // Delivery
            'delivery_address' => $this->delivery_address,
            'notes' => $this->notes,

            // Items
            'items' => $this->when(
                $this->relationLoaded('items'),
                fn () => $this->items->map(fn ($item) => [
                    'id' => $item->id,
                    'product_id' => $item->product_id,
                    'product_name' => $item->product_name,
                    'variant_name' => $item->variant_name,
                    'price' => (float) $item->price,
                    'quantity' => $item->quantity,
                    'total' => (float) $item->total,
                    'product_image' => $item->relationLoaded('product') && $item->product
                        ? ($item->product->relationLoaded('primaryImage')
                            ? $item->product->primaryImage?->image_url
                            : null)
                        : null,
                ])
            ),
            'items_count' => $this->when(
                $this->relationLoaded('items'),
                fn () => $this->items->sum('quantity')
            ),

            // Status history
            'status_history' => $this->when(
                $this->relationLoaded('statusHistory'),
                fn () => $this->statusHistory->map(fn ($h) => [
                    'from_status' => $h->from_status,
                    'to_status' => $h->to_status,
                    'comment' => $h->comment,
                    'created_at' => $h->created_at->toISOString(),
                ])
            ),

            // Payment transaction
            'payment_transaction' => $this->when(
                $this->relationLoaded('paymentTransaction') && $this->paymentTransaction,
                fn () => [
                    'provider' => $this->paymentTransaction->provider,
                    'status' => $this->paymentTransaction->status,
                    'amount' => (float) $this->paymentTransaction->amount,
                    'paid_at' => $this->paymentTransaction->paid_at?->toISOString(),
                ]
            ),

            // Timestamps
            'paid_at' => $this->paid_at?->toISOString(),
            'confirmed_at' => $this->confirmed_at?->toISOString(),
            'shipped_at' => $this->shipped_at?->toISOString(),
            'delivered_at' => $this->delivered_at?->toISOString(),
            'cancelled_at' => $this->cancelled_at?->toISOString(),
            'created_at' => $this->created_at->toISOString(),
            'updated_at' => $this->updated_at->toISOString(),
        ];
    }
}
