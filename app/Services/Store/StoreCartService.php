<?php

namespace App\Services\Store;

use App\Models\Store\StoreCart;
use App\Models\Store\StoreCartItem;
use App\Models\Store\StoreCustomer;
use App\Models\Store\StoreProduct;
use App\Models\Store\StoreProductVariant;
use App\Models\Store\StorePromoCode;
use App\Models\Store\TelegramStore;

class StoreCartService
{
    /**
     * Get or create cart for customer/session
     */
    public function getOrCreateCart(TelegramStore $store, ?StoreCustomer $customer = null, ?string $sessionId = null): StoreCart
    {
        $query = StoreCart::where('store_id', $store->id);

        if ($customer) {
            $query->where('customer_id', $customer->id);
        } elseif ($sessionId) {
            $query->where('session_id', $sessionId);
        }

        $cart = $query->first();

        if ($cart && $cart->isExpired()) {
            $cart->clear();
            $cart->update(['expires_at' => now()->addHours(config('store.cart.expires_hours', 72))]);
        }

        if (! $cart) {
            $cart = StoreCart::create([
                'store_id' => $store->id,
                'customer_id' => $customer?->id,
                'session_id' => $sessionId,
                'expires_at' => now()->addHours(config('store.cart.expires_hours', 72)),
            ]);
        }

        return $cart->load('items.product.primaryImage');
    }

    /**
     * Add item to cart
     */
    public function addItem(StoreCart $cart, StoreProduct $product, int $quantity = 1, ?StoreProductVariant $variant = null): StoreCartItem
    {
        $price = $variant ? $variant->price : $product->price;

        // Check if item already exists
        $existingItem = $cart->items()
            ->where('product_id', $product->id)
            ->where('variant_id', $variant?->id)
            ->first();

        if ($existingItem) {
            $existingItem->update([
                'quantity' => $existingItem->quantity + $quantity,
                'price' => $price,
            ]);

            return $existingItem->fresh();
        }

        return StoreCartItem::create([
            'cart_id' => $cart->id,
            'product_id' => $product->id,
            'variant_id' => $variant?->id,
            'quantity' => $quantity,
            'price' => $price,
        ]);
    }

    /**
     * Update item quantity
     */
    public function updateItemQuantity(StoreCartItem $item, int $quantity): ?StoreCartItem
    {
        if ($quantity <= 0) {
            $item->delete();

            return null;
        }

        $item->update(['quantity' => $quantity]);

        return $item->fresh();
    }

    /**
     * Remove item from cart
     */
    public function removeItem(StoreCartItem $item): void
    {
        $item->delete();
    }

    /**
     * Apply promo code
     */
    public function applyPromoCode(StoreCart $cart, string $code): array
    {
        $store = $cart->store;
        $promo = StorePromoCode::where('store_id', $store->id)
            ->where('code', $code)
            ->first();

        if (! $promo) {
            return ['success' => false, 'error' => 'Promo kod topilmadi'];
        }

        if (! $promo->isValid()) {
            return ['success' => false, 'error' => 'Promo kod muddati o\'tgan yoki faol emas'];
        }

        $subtotal = $cart->getSubtotal();
        $discount = $promo->calculateDiscount($subtotal);

        if ($discount <= 0) {
            $minAmount = number_format($promo->min_order_amount, 0, '', ' ');

            return ['success' => false, 'error' => "Minimal buyurtma summasi: {$minAmount} so'm"];
        }

        return [
            'success' => true,
            'promo' => $promo,
            'discount' => $discount,
            'subtotal' => $subtotal,
            'total' => max(0, $subtotal - $discount),
        ];
    }

    /**
     * Get cart summary for checkout
     */
    public function getCartSummary(StoreCart $cart): array
    {
        $items = $cart->items()->with(['product.primaryImage', 'variant'])->get();

        return [
            'items' => $items->map(fn ($item) => [
                'id' => $item->id,
                'product_id' => $item->product_id,
                'variant_id' => $item->variant_id,
                'product_name' => $item->product->name,
                'variant_name' => $item->variant?->name,
                'image_url' => $item->product->primaryImage?->image_url,
                'price' => $item->price,
                'quantity' => $item->quantity,
                'total' => $item->getTotal(),
                'in_stock' => $item->product->isInStock(),
            ]),
            'items_count' => $items->sum('quantity'),
            'subtotal' => $items->sum(fn ($item) => $item->getTotal()),
        ];
    }
}
