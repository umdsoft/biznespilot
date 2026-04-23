<?php

namespace App\Http\Controllers\Api\MiniApp;

use App\Http\Controllers\Controller;
use App\Http\Resources\Store\OrderResource;
use App\Models\Store\StoreDeliveryZone;
use App\Models\Store\TelegramStore;
use App\Services\Store\StoreCartService;
use App\Services\Store\StoreOrderService;
use App\Services\Store\StorePaymentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Mini App Checkout Controller.
 *
 * Handles order creation from cart and delivery zone listing.
 */
class CheckoutController extends Controller
{
    public function __construct(
        protected StoreOrderService $orderService,
        protected StoreCartService $cartService,
        protected StorePaymentService $paymentService
    ) {}

    /**
     * POST /checkout — Create order from cart.
     *
     * Body: {
     *   delivery_address: { street, city, apartment?, comment? },
     *   payment_method: "cash" | "payme" | "click",
     *   notes: string?,
     *   delivery_zone_id: uuid?,
     *   promo_code: string?
     * }
     */
    public function checkout(Request $request, TelegramStore $store): JsonResponse
    {
        $validated = $request->validate([
            'customer_name' => 'nullable|string|max:100',
            'customer_phone' => 'nullable|string|max:30',
            'delivery_address' => 'required|array',
            'delivery_address.street' => 'required|string|max:500',
            'delivery_address.city' => 'nullable|string|max:100',
            'delivery_address.district' => 'nullable|string|max:100',
            'delivery_address.apartment' => 'nullable|string|max:100',
            'delivery_address.entrance' => 'nullable|string|max:20',
            'delivery_address.floor' => 'nullable|string|max:20',
            'delivery_address.comment' => 'nullable|string|max:500',
            'delivery_address.latitude' => 'nullable|numeric|between:-90,90',
            'delivery_address.longitude' => 'nullable|numeric|between:-180,180',
            'delivery_type' => 'nullable|string|in:delivery,pickup',
            'payment_method' => 'required|in:cash,card,payme,click',
            'notes' => 'nullable|string|max:1000',
            'delivery_zone_id' => 'nullable|uuid',
            'promo_code' => 'nullable|string|max:50',
        ]);

        $customer = $request->attributes->get('store_customer');

        // Update customer name/phone if provided
        $customerUpdate = [];
        if (! empty($validated['customer_name']) && ! $customer->name) {
            $customerUpdate['name'] = $validated['customer_name'];
        }
        if (! empty($validated['customer_phone'])) {
            $customerUpdate['phone'] = $validated['customer_phone'];
        }
        if ($customerUpdate) {
            $customer->update($customerUpdate);
        }

        // Get customer's cart with items
        $cart = $this->cartService->getOrCreateCart($store, $customer);
        $cart->load('items.product.primaryImage', 'items.variant');

        // Validate cart is not empty
        if ($cart->items->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'Savat bo\'sh. Buyurtma berish uchun mahsulot qo\'shing.',
            ], 422);
        }

        // Check minimum order amount
        $minOrderAmount = $store->getSetting('min_order_amount', 0);
        $subtotal = $cart->getSubtotal();

        if ($minOrderAmount > 0 && $subtotal < $minOrderAmount) {
            $formattedMin = number_format($minOrderAmount, 0, '', ' ');

            return response()->json([
                'success' => false,
                'message' => "Minimal buyurtma summasi: {$formattedMin} so'm",
            ], 422);
        }

        // Validate all items are in stock
        foreach ($cart->items as $item) {
            if (! $item->product || ! $item->product->is_active) {
                return response()->json([
                    'success' => false,
                    'message' => "Mahsulot \"{$item->product?->name}\" mavjud emas",
                ], 422);
            }

            if (! $item->product->isInStock()) {
                return response()->json([
                    'success' => false,
                    'message' => "Mahsulot \"{$item->product->name}\" omborda tugagan",
                ], 422);
            }

            if ($item->variant && ! $item->variant->isInStock()) {
                return response()->json([
                    'success' => false,
                    'message' => "\"{$item->product->name}\" ({$item->variant->name}) varianti omborda tugagan",
                ], 422);
            }
        }

        // Calculate delivery fee from delivery zone
        $deliveryFee = 0;
        if (! empty($validated['delivery_zone_id'])) {
            $deliveryZone = StoreDeliveryZone::where('id', $validated['delivery_zone_id'])
                ->where('store_id', $store->id)
                ->active()
                ->first();

            if ($deliveryZone) {
                $deliveryFee = (float) $deliveryZone->delivery_fee;

                // Check minimum order for this zone
                if ($deliveryZone->min_order_amount && $subtotal < $deliveryZone->min_order_amount) {
                    $formattedMin = number_format($deliveryZone->min_order_amount, 0, '', ' ');

                    return response()->json([
                        'success' => false,
                        'message' => "Bu hudud uchun minimal buyurtma: {$formattedMin} so'm",
                    ], 422);
                }
            }
        }

        // Apply promo code — atomically claim a usage slot under lock
        $discountAmount = 0;
        $promoCode = null;
        if (! empty($validated['promo_code'])) {
            $claim = $this->cartService->claimPromoCode(
                $store,
                $validated['promo_code'],
                (float) $subtotal
            );

            if ($claim) {
                $discountAmount = $claim['discount'];
                $promoCode = $claim['code'];
            }
        }

        // Prepare order items from cart (include modifier selections in metadata)
        $orderItems = $cart->items->map(function ($item) {
            $data = [
                'product_id' => $item->product_id,
                'variant_id' => $item->variant_id,
                'product_name' => $item->product->name,
                'variant_name' => $item->variant?->name,
                'price' => $item->price,
                'quantity' => $item->quantity,
                'product' => $item->product,
                'variant' => $item->variant,
            ];

            // Pass modifier selections for order item metadata
            if (! empty($item->selections)) {
                $modifierTotal = collect($item->selections)->sum('price');
                $data['price'] = $item->price + $modifierTotal;
                $data['item_metadata'] = ['modifiers' => $item->selections];
            }

            return $data;
        })->toArray();

        // Create the order
        try {
            $order = $this->orderService->createOrder($store, $customer, $orderItems, [
                'delivery_address' => $validated['delivery_address'],
                'delivery_type' => $validated['delivery_type'] ?? 'delivery',
                'payment_method' => $validated['payment_method'],
                'notes' => $validated['notes'] ?? null,
                'delivery_fee' => $deliveryFee,
                'discount_amount' => $discountAmount,
                'promo_code' => $promoCode,
            ]);

            // Clear the cart after successful order creation
            $cart->clear();

            // Update customer address if not set
            if (! $customer->address) {
                $customer->update(['address' => $validated['delivery_address']]);
            }

            // Generate payment URL for online payment methods
            $paymentUrl = null;
            if (in_array($validated['payment_method'], ['payme', 'click'])) {
                $paymentResult = $this->paymentService->createPaymentUrl($order, $validated['payment_method']);

                if ($paymentResult['success']) {
                    $paymentUrl = $paymentResult['payment_url'];
                } else {
                    Log::warning('Failed to generate payment URL', [
                        'order_id' => $order->id,
                        'provider' => $validated['payment_method'],
                        'error' => $paymentResult['error'],
                    ]);
                }
            }

            $order->load(['items.product.primaryImage', 'statusHistory']);

            return response()->json([
                'success' => true,
                'message' => 'Buyurtma muvaffaqiyatli yaratildi',
                'data' => [
                    'order' => new OrderResource($order),
                    'payment_url' => $paymentUrl,
                ],
            ], 201);

        } catch (\App\Exceptions\Store\OutOfStockException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        } catch (\Exception $e) {
            Log::error('Checkout failed', [
                'store_id' => $store->id,
                'customer_id' => $customer->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Buyurtma yaratishda xatolik yuz berdi. Qaytadan urinib ko\'ring.',
            ], 500);
        }
    }

    /**
     * GET /delivery-zones — List active delivery zones for the store.
     *
     * This is a PUBLIC endpoint (no auth needed).
     */
    public function deliveryZones(TelegramStore $store): JsonResponse
    {
        $zones = $store->deliveryZones()
            ->active()
            ->orderBy('name')
            ->get()
            ->map(fn ($zone) => [
                'id' => $zone->id,
                'name' => $zone->name,
                'delivery_fee' => (float) $zone->delivery_fee,
                'min_order_amount' => $zone->min_order_amount ? (float) $zone->min_order_amount : null,
                'estimated_time' => $zone->estimated_time,
            ]);

        return response()->json([
            'success' => true,
            'data' => $zones,
        ]);
    }
}
