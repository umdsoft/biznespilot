<?php

namespace App\Http\Controllers\Api\MiniApp;

use App\Http\Controllers\Controller;
use App\Http\Resources\Store\CartResource;
use App\Models\Store\StoreCartItem;
use App\Models\Store\StoreProduct;
use App\Models\Store\StoreProductVariant;
use App\Models\Store\TelegramStore;
use App\Services\Store\StoreCartService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Mini App Cart Controller.
 *
 * Manages shopping cart: get, add, update, remove items, and promo codes.
 * All endpoints require MiniAppAuth middleware (Telegram initData).
 */
class CartController extends Controller
{
    public function __construct(
        protected StoreCartService $cartService
    ) {}

    /**
     * GET /cart — Get current cart.
     */
    public function index(Request $request, TelegramStore $store): JsonResponse
    {
        $customer = $request->attributes->get('store_customer');

        $cart = $this->cartService->getOrCreateCart($store, $customer);
        $cart->load('items.product.primaryImage', 'items.variant');

        return response()->json([
            'success' => true,
            'data' => new CartResource($cart),
        ]);
    }

    /**
     * POST /cart — Add item to cart.
     *
     * Body: { product_id, variant_id?, quantity? }
     */
    public function addItem(Request $request, TelegramStore $store): JsonResponse
    {
        $validated = $request->validate([
            'product_id' => 'required|uuid',
            'variant_id' => 'nullable|uuid',
            'quantity' => 'nullable|integer|min:1|max:99',
        ]);

        $customer = $request->attributes->get('store_customer');

        // Find product and verify it belongs to this store
        $product = StoreProduct::where('id', $validated['product_id'])
            ->where('store_id', $store->id)
            ->active()
            ->first();

        if (! $product) {
            return response()->json([
                'success' => false,
                'message' => 'Mahsulot topilmadi',
            ], 404);
        }

        // Check stock
        if (! $product->isInStock()) {
            return response()->json([
                'success' => false,
                'message' => 'Mahsulot omborda tugagan',
            ], 422);
        }

        // Resolve variant if provided
        $variant = null;
        if (! empty($validated['variant_id'])) {
            $variant = StoreProductVariant::where('id', $validated['variant_id'])
                ->where('product_id', $product->id)
                ->where('is_active', true)
                ->first();

            if (! $variant) {
                return response()->json([
                    'success' => false,
                    'message' => 'Mahsulot varianti topilmadi',
                ], 404);
            }

            if (! $variant->isInStock()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Tanlangan variant omborda tugagan',
                ], 422);
            }
        }

        $quantity = $validated['quantity'] ?? 1;

        $cart = $this->cartService->getOrCreateCart($store, $customer);
        $this->cartService->addItem($cart, $product, $quantity, $variant);

        // Reload cart with relationships
        $cart->load('items.product.primaryImage', 'items.variant');

        return response()->json([
            'success' => true,
            'message' => 'Mahsulot savatga qo\'shildi',
            'data' => new CartResource($cart),
        ]);
    }

    /**
     * PUT /cart/{item} — Update item quantity.
     *
     * Body: { quantity }
     */
    public function updateItem(Request $request, TelegramStore $store, string $item): JsonResponse
    {
        $validated = $request->validate([
            'quantity' => 'required|integer|min:0|max:99',
        ]);

        $customer = $request->attributes->get('store_customer');

        // Get cart and find item
        $cart = $this->cartService->getOrCreateCart($store, $customer);
        $cartItem = StoreCartItem::where('id', $item)
            ->where('cart_id', $cart->id)
            ->first();

        if (! $cartItem) {
            return response()->json([
                'success' => false,
                'message' => 'Savat elementi topilmadi',
            ], 404);
        }

        $this->cartService->updateItemQuantity($cartItem, $validated['quantity']);

        // Reload cart
        $cart->load('items.product.primaryImage', 'items.variant');

        return response()->json([
            'success' => true,
            'message' => $validated['quantity'] > 0 ? 'Miqdor yangilandi' : 'Mahsulot savatdan o\'chirildi',
            'data' => new CartResource($cart),
        ]);
    }

    /**
     * DELETE /cart/{item} — Remove item from cart.
     */
    public function removeItem(Request $request, TelegramStore $store, string $item): JsonResponse
    {
        $customer = $request->attributes->get('store_customer');

        $cart = $this->cartService->getOrCreateCart($store, $customer);
        $cartItem = StoreCartItem::where('id', $item)
            ->where('cart_id', $cart->id)
            ->first();

        if (! $cartItem) {
            return response()->json([
                'success' => false,
                'message' => 'Savat elementi topilmadi',
            ], 404);
        }

        $this->cartService->removeItem($cartItem);

        // Reload cart
        $cart->load('items.product.primaryImage', 'items.variant');

        return response()->json([
            'success' => true,
            'message' => 'Mahsulot savatdan o\'chirildi',
            'data' => new CartResource($cart),
        ]);
    }

    /**
     * POST /cart/sync — Sync frontend (localStorage) cart to server.
     *
     * Replaces server cart with frontend items.
     * Body: { items: [{ product_id, variant_id?, quantity }] }
     */
    public function sync(Request $request, TelegramStore $store): JsonResponse
    {
        $validated = $request->validate([
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|uuid',
            'items.*.variant_id' => 'nullable|uuid',
            'items.*.quantity' => 'required|integer|min:1|max:99',
            'items.*.selections' => 'nullable|array',
            'items.*.selections.*.modifier_id' => 'required_with:items.*.selections|uuid',
            'items.*.selections.*.modifier_name' => 'required_with:items.*.selections|string|max:100',
            'items.*.selections.*.option_id' => 'required_with:items.*.selections|uuid',
            'items.*.selections.*.option_name' => 'required_with:items.*.selections|string|max:100',
            'items.*.selections.*.price' => 'required_with:items.*.selections|numeric|min:0',
        ]);

        $customer = $request->attributes->get('store_customer');
        $cart = $this->cartService->getOrCreateCart($store, $customer);

        // Clear existing cart items
        $cart->items()->delete();

        $skipped = [];

        foreach ($validated['items'] as $itemData) {
            $product = StoreProduct::where('id', $itemData['product_id'])
                ->where('store_id', $store->id)
                ->active()
                ->first();

            if (! $product || ! $product->isInStock()) {
                $skipped[] = $itemData['product_id'];
                continue;
            }

            $variant = null;
            if (! empty($itemData['variant_id'])) {
                $variant = StoreProductVariant::where('id', $itemData['variant_id'])
                    ->where('product_id', $product->id)
                    ->where('is_active', true)
                    ->first();

                if (! $variant || ! $variant->isInStock()) {
                    $skipped[] = $itemData['product_id'];
                    continue;
                }
            }

            $selections = $itemData['selections'] ?? null;
            $this->cartService->addItem($cart, $product, $itemData['quantity'], $variant, $selections);
        }

        $cart->load('items.product.primaryImage', 'items.variant');

        return response()->json([
            'success' => true,
            'data' => new CartResource($cart),
            'skipped' => $skipped,
        ]);
    }

    /**
     * POST /cart/promo — Apply promo code.
     *
     * Body: { code }
     */
    public function applyPromo(Request $request, TelegramStore $store): JsonResponse
    {
        $validated = $request->validate([
            'code' => 'required|string|max:50',
        ]);

        $customer = $request->attributes->get('store_customer');
        $cart = $this->cartService->getOrCreateCart($store, $customer);

        if ($cart->items->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'Savat bo\'sh',
            ], 422);
        }

        $result = $this->cartService->applyPromoCode($cart, $validated['code']);

        if (! $result['success']) {
            return response()->json([
                'success' => false,
                'message' => $result['error'],
            ], 422);
        }

        return response()->json([
            'success' => true,
            'message' => 'Promo kod qo\'llanildi',
            'data' => [
                'promo_code' => $result['promo']->code,
                'discount_type' => $result['promo']->type,
                'discount_value' => (float) $result['promo']->value,
                'discount_amount' => (float) $result['discount'],
                'subtotal' => (float) $result['subtotal'],
                'total' => (float) $result['total'],
            ],
        ]);
    }
}
