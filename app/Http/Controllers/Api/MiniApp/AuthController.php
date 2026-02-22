<?php

namespace App\Http\Controllers\Api\MiniApp;

use App\Http\Controllers\Controller;
use App\Models\Store\TelegramStore;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Mini App Authentication Controller.
 *
 * For Telegram Mini Apps, we validate initData on every request via middleware.
 * This endpoint simply returns the authenticated customer's info.
 */
class AuthController extends Controller
{
    /**
     * POST /auth — Return authenticated customer info.
     *
     * The MiniAppAuth middleware has already validated the initData
     * and resolved the StoreCustomer.
     */
    public function authenticate(Request $request, TelegramStore $store): JsonResponse
    {
        $customer = $request->attributes->get('store_customer');
        $telegramUserData = $request->attributes->get('telegram_user_data');

        if (! $customer) {
            return response()->json([
                'success' => false,
                'message' => 'Mijoz topilmadi',
            ], 401);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'customer' => [
                    'id' => $customer->id,
                    'name' => $customer->getDisplayName(),
                    'phone' => $customer->phone,
                    'address' => $customer->address,
                    'orders_count' => $customer->orders_count,
                    'total_spent' => (float) $customer->total_spent,
                    'last_order_at' => $customer->last_order_at?->toISOString(),
                ],
                'telegram_user' => [
                    'id' => $telegramUserData['id'] ?? null,
                    'first_name' => $telegramUserData['first_name'] ?? null,
                    'last_name' => $telegramUserData['last_name'] ?? null,
                    'username' => $telegramUserData['username'] ?? null,
                    'language_code' => $telegramUserData['language_code'] ?? null,
                    'photo_url' => $telegramUserData['photo_url'] ?? null,
                ],
                'store' => [
                    'id' => $store->id,
                    'name' => $store->name,
                    'slug' => $store->slug,
                ],
            ],
        ]);
    }
}
