<?php

namespace App\Http\Middleware;

use App\Models\Store\StoreCustomer;
use App\Models\Store\TelegramStore;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

/**
 * Telegram WebApp initData HMAC-SHA256 validation middleware.
 *
 * Validates the Telegram Mini App initData signature and resolves
 * the StoreCustomer from the embedded user data.
 *
 * @see https://core.telegram.org/bots/webapps#validating-data-received-via-the-mini-app
 */
class MiniAppAuth
{
    /**
     * Maximum age of initData in seconds (24 hours).
     * Mini App do'kon uchun user uzoq vaqt browse qilishi mumkin.
     */
    protected const MAX_AGE_SECONDS = 86400;

    public function handle(Request $request, Closure $next): Response
    {
        // Resolve store from route model binding
        $store = $request->route('store');

        if (! $store instanceof TelegramStore) {
            return $this->unauthorized('Store not found');
        }

        if (! $store->is_active) {
            return $this->unauthorized('Store is not active');
        }

        // Extract initData from Authorization header: "tma {initData}"
        $authHeader = $request->header('Authorization', '');
        if (! str_starts_with($authHeader, 'tma ')) {
            return $this->unauthorized('Missing or invalid Authorization header');
        }

        $initData = substr($authHeader, 4);

        if (empty($initData)) {
            return $this->unauthorized('Empty initData');
        }

        // Get bot token from the store's telegram bot
        $telegramBot = $store->telegramBot;

        if (! $telegramBot || ! $telegramBot->bot_token) {
            Log::warning('MiniAppAuth: Store has no linked telegram bot', [
                'store_id' => $store->id,
            ]);

            return $this->unauthorized('Store configuration error');
        }

        $botToken = $telegramBot->bot_token;

        // Validate initData signature
        $userData = $this->validateInitData($initData, $botToken);

        if ($userData === null) {
            return $this->unauthorized('Invalid initData signature');
        }

        // Find or create StoreCustomer from Telegram user data
        $customer = $this->resolveCustomer($store, $userData);

        // Set request attributes for downstream controllers
        $request->attributes->set('store_customer', $customer);
        $request->attributes->set('telegram_user_data', $userData);

        return $next($request);
    }

    /**
     * Validate Telegram initData using HMAC-SHA256.
     *
     * Steps:
     * 1. Parse initData as URL-encoded string
     * 2. Extract the "hash" field
     * 3. Sort remaining fields alphabetically by key
     * 4. Create data_check_string by joining "key=value" with \n
     * 5. secret_key = HMAC-SHA256("WebAppData", bot_token)
     * 6. Validate: HMAC-SHA256(secret_key, data_check_string) === hash
     */
    protected function validateInitData(string $initData, string $botToken): ?array
    {
        // Parse URL-encoded initData
        parse_str($initData, $params);

        if (empty($params) || ! isset($params['hash'])) {
            return null;
        }

        $receivedHash = $params['hash'];
        unset($params['hash']);

        // Check auth_date freshness (prevent replay attacks)
        if (isset($params['auth_date'])) {
            $authDate = (int) $params['auth_date'];
            $now = time();

            if (($now - $authDate) > self::MAX_AGE_SECONDS) {
                Log::debug('MiniAppAuth: initData expired', [
                    'auth_date' => $authDate,
                    'now' => $now,
                    'age' => $now - $authDate,
                ]);

                return null;
            }
        }

        // Sort remaining fields alphabetically by key
        ksort($params);

        // Create data_check_string: join "key=value" pairs with newline
        $dataCheckParts = [];
        foreach ($params as $key => $value) {
            $dataCheckParts[] = "{$key}={$value}";
        }
        $dataCheckString = implode("\n", $dataCheckParts);

        // Generate secret key: HMAC-SHA256("WebAppData", bot_token)
        $secretKey = hash_hmac('sha256', $botToken, 'WebAppData', true);

        // Generate hash: HMAC-SHA256(secret_key, data_check_string)
        $calculatedHash = hash_hmac('sha256', $dataCheckString, $secretKey);

        // Validate hash (timing-safe comparison)
        if (! hash_equals($calculatedHash, $receivedHash)) {
            Log::debug('MiniAppAuth: Hash mismatch', [
                'calculated' => $calculatedHash,
                'received' => $receivedHash,
            ]);

            return null;
        }

        // Extract user data from the "user" param (JSON-encoded)
        $userData = [];
        if (isset($params['user'])) {
            $userData = json_decode($params['user'], true) ?? [];
        }

        // Add query_id and auth_date for reference
        $userData['_query_id'] = $params['query_id'] ?? null;
        $userData['_auth_date'] = $params['auth_date'] ?? null;
        $userData['_start_param'] = $params['start_param'] ?? null;

        return $userData;
    }

    /**
     * Find or create a StoreCustomer from Telegram user data.
     */
    protected function resolveCustomer(TelegramStore $store, array $userData): StoreCustomer
    {
        $telegramId = $userData['id'] ?? null;

        if (! $telegramId) {
            // Edge case: create a guest customer
            return StoreCustomer::firstOrCreate([
                'store_id' => $store->id,
                'telegram_user_id' => null,
                'name' => 'Guest',
            ]);
        }

        // Find TelegramUser by telegram_id for this business
        $telegramUser = \App\Models\TelegramUser::where('telegram_id', $telegramId)
            ->where('business_id', $store->business_id)
            ->first();

        // Build customer name from Telegram data
        $firstName = $userData['first_name'] ?? '';
        $lastName = $userData['last_name'] ?? '';
        $fullName = trim("{$firstName} {$lastName}") ?: null;

        // Find or create StoreCustomer
        $customer = StoreCustomer::firstOrCreate(
            [
                'store_id' => $store->id,
                'telegram_user_id' => $telegramUser?->id,
            ],
            [
                'name' => $fullName,
            ]
        );

        // Update customer name if it was null and we now have data
        if (! $customer->name && $fullName) {
            $customer->update(['name' => $fullName]);
        }

        // If no TelegramUser record exists yet, try linking by telegram_id
        if (! $telegramUser && $telegramId) {
            // Check if there's a TelegramUser in ANY bot for this business
            $telegramUser = \App\Models\TelegramUser::where('telegram_id', $telegramId)
                ->where('business_id', $store->business_id)
                ->first();

            if ($telegramUser && ! $customer->telegram_user_id) {
                $customer->update(['telegram_user_id' => $telegramUser->id]);
            }
        }

        return $customer;
    }

    /**
     * Return unauthorized JSON response.
     */
    protected function unauthorized(string $message): Response
    {
        return response()->json([
            'success' => false,
            'message' => $message,
        ], 401);
    }
}
