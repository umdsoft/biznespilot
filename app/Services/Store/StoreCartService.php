<?php

namespace App\Services\Store;

use App\Models\Store\StoreCart;
use App\Models\Store\StoreCartItem;
use App\Models\Store\StoreCustomer;
use App\Models\Store\StoreProduct;
use App\Models\Store\StoreProductVariant;
use App\Models\Store\StorePromoCode;
use App\Models\Store\TelegramStore;
use Illuminate\Support\Facades\DB;

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
     * Add item to cart.
     *
     * RACE-SAFE: lockForUpdate transaction orqali ikki parallel "+1" so'rov
     * lost update qilmaydi (ilgari: ikkala request quantity=1 ko'rib, ikkalasi
     * 2 ga qo'yardi — natija 2 emas 3 bo'lishi kerak edi).
     *
     * Modifier selections (taom modifierlari) JSON solishtirish key-order
     * ga sezgir bo'lardi (same modifier set, different key order = duplicate
     * row). Endi selections ksort qilib normalize qilamiz.
     */
    public function addItem(StoreCart $cart, StoreProduct $product, int $quantity = 1, ?StoreProductVariant $variant = null, ?array $selections = null): StoreCartItem
    {
        $price = $variant ? $variant->price : $product->price;
        $normalizedSelections = $this->normalizeSelections($selections);

        return DB::transaction(function () use ($cart, $product, $variant, $quantity, $price, $normalizedSelections) {
            // Same cart'ning items qatoriga LOCK qo'yamiz — boshqa parallel
            // request shu cart'da o'zgartira olmaydi.
            $query = $cart->items()
                ->where('product_id', $product->id)
                ->where('variant_id', $variant?->id);

            if ($normalizedSelections !== null) {
                $query->where('selections', json_encode($normalizedSelections));
            } else {
                $query->where(function ($q) {
                    $q->whereNull('selections')->orWhere('selections', '[]');
                });
            }

            $existingItem = $query->lockForUpdate()->first();

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
                'selections' => $normalizedSelections,
            ]);
        });
    }

    /**
     * Modifier selections'ni normalize qilish — key tartibidan qat'iy nazar
     * bir xil modifier to'plami bir xil JSON beradi.
     */
    private function normalizeSelections(?array $selections): ?array
    {
        if (empty($selections)) {
            return null;
        }
        // Recursive ksort — barcha darajalarda kalit tartibini bir xil qilish
        $sorted = $selections;
        $sort = function (&$arr) use (&$sort) {
            if (! is_array($arr)) return;
            ksort($arr);
            foreach ($arr as &$v) {
                if (is_array($v)) $sort($v);
            }
        };
        $sort($sorted);
        return $sorted;
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
     * Apply promo code — read-only preview (cart UI).
     *
     * Does NOT increment usage. Use claimPromoCode() inside the checkout
     * transaction to atomically reserve a slot against max_uses.
     */
    public function applyPromoCode(StoreCart $cart, string $code): array
    {
        $store = $cart->store;
        $promo = StorePromoCode::where('store_id', $store->id)
            ->where('code', strtoupper(trim($code)))
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
     * Atomically claim one promo code usage slot under a row lock.
     *
     * Race-safe: two concurrent checkouts for the last max_uses slot cannot
     * both succeed — the loser gets `null` and the caller skips the discount.
     *
     * Returns the claimed discount + promo, or null if claim failed.
     */
    public function claimPromoCode(TelegramStore $store, string $code, float $subtotal): ?array
    {
        return DB::transaction(function () use ($store, $code, $subtotal) {
            $promo = StorePromoCode::where('store_id', $store->id)
                ->where('code', strtoupper(trim($code)))
                ->lockForUpdate()
                ->first();

            if (! $promo || ! $promo->isValid()) {
                return null;
            }

            $discount = $promo->calculateDiscount($subtotal);
            if ($discount <= 0) {
                return null;
            }

            // Atomic slot claim (under lock, so max_uses is authoritative here)
            $promo->increment('used_count');

            return [
                'promo' => $promo,
                'discount' => $discount,
                'code' => $promo->code,
            ];
        });
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
