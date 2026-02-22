<?php

namespace App\Http\Resources\Store;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Cart resource with items, product info, subtotal, and items count.
 */
class CartResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $items = $this->whenLoaded('items', fn () => $this->items, collect());

        return [
            'id' => $this->id,
            'items' => $items->map(fn ($item) => [
                'id' => $item->id,
                'product_id' => $item->product_id,
                'variant_id' => $item->variant_id,
                'quantity' => $item->quantity,
                'price' => (float) $item->price,
                'total' => (float) $item->getTotal(),
                'product' => $item->relationLoaded('product') ? [
                    'id' => $item->product->id,
                    'name' => $item->product->name,
                    'slug' => $item->product->slug,
                    'price' => (float) $item->product->price,
                    'in_stock' => $item->product->isInStock(),
                    'primary_image' => $item->product->relationLoaded('primaryImage')
                        ? $item->product->primaryImage?->image_url
                        : null,
                ] : null,
                'variant' => $item->relationLoaded('variant') && $item->variant ? [
                    'id' => $item->variant->id,
                    'name' => $item->variant->name,
                    'price' => (float) $item->variant->price,
                    'attributes' => $item->variant->attributes,
                ] : null,
            ]),
            'items_count' => $this->getItemsCount(),
            'subtotal' => (float) $this->getSubtotal(),
            'expires_at' => $this->expires_at?->toISOString(),
        ];
    }
}
