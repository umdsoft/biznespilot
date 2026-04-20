<?php

namespace App\Services\Store;

use App\Models\Business;
use App\Models\Store\TelegramStore;
use App\Models\TelegramBot;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class StoreSetupService
{
    /**
     * Create a new store for a business
     */
    public function createStore(Business $business, array $data): TelegramStore
    {
        $slug = Str::slug($data['name']) . '-' . Str::random(6);

        return TelegramStore::create([
            'business_id' => $business->id,
            'name' => $data['name'],
            'slug' => $slug,
            'description' => $data['description'] ?? null,
            'phone' => $data['phone'] ?? $business->phone,
            'address' => $data['address'] ?? null,
            'currency' => $data['currency'] ?? 'UZS',
            'store_type' => $data['store_type'] ?? 'ecommerce',
            'enabled_features' => $data['enabled_features'] ?? [],
            'settings' => $data['settings'] ?? [],
            'theme' => config('store.default_theme'),
        ]);
    }

    /**
     * Connect a Telegram bot to the store
     */
    public function connectBot(TelegramStore $store, string $botToken): array
    {
        // Verify bot token with Telegram API
        $response = Http::withOptions(['verify' => false, 'connect_timeout' => 15])->timeout(30)->get("https://api.telegram.org/bot{$botToken}/getMe");

        if (! $response->successful()) {
            return ['success' => false, 'error' => 'Bot tokeni noto\'g\'ri. Telegram BotFather dan to\'g\'ri tokenni oling.'];
        }

        $botData = $response->json('result');

        // Check if bot already exists for this business
        $existingBot = TelegramBot::where('business_id', $store->business_id)
            ->where('bot_username', $botData['username'])
            ->first();

        if ($existingBot) {
            $bot = $existingBot;
        } else {
            $bot = TelegramBot::create([
                'business_id' => $store->business_id,
                'bot_token' => $botToken,
                'bot_username' => $botData['username'],
                'bot_first_name' => $botData['first_name'],
                'is_active' => true,
                'is_verified' => true,
                'verified_at' => now(),
            ]);
        }

        // Set up webhook (use funnel endpoint — single handler for all bot types)
        $baseUrl = config('app.url');
        $webhookUrl = "{$baseUrl}/webhooks/telegram-funnel/{$bot->id}";
        $webhookSecret = $bot->webhook_secret ?: bin2hex(random_bytes(32));

        $webhookResponse = Http::withOptions(['verify' => false, 'connect_timeout' => 15])->timeout(30)->post("https://api.telegram.org/bot{$botToken}/setWebhook", [
            'url' => $webhookUrl,
            'secret_token' => $webhookSecret,
            'allowed_updates' => ['message', 'callback_query', 'my_chat_member'],
        ]);

        if ($webhookResponse->successful()) {
            $bot->update([
                'webhook_url' => $webhookUrl,
                'webhook_secret' => $webhookSecret,
            ]);
        }

        // Set WebApp menu button
        $miniAppUrl = "{$baseUrl}/miniapp/{$store->slug}";
        Http::withOptions(['verify' => false, 'connect_timeout' => 15])->timeout(30)->post("https://api.telegram.org/bot{$botToken}/setChatMenuButton", [
            'menu_button' => [
                'type' => 'web_app',
                'text' => 'Do\'kon',
                'web_app' => ['url' => $miniAppUrl],
            ],
        ]);

        $store->update(['telegram_bot_id' => $bot->id]);

        Log::info('Store bot connected', [
            'store_id' => $store->id,
            'bot_username' => $botData['username'],
        ]);

        return [
            'success' => true,
            'bot' => $bot,
            'webhook_set' => $webhookResponse->successful(),
        ];
    }

    /**
     * Set up an existing bot for store (webhook + menu button)
     */
    public function setupBotForStore(TelegramStore $store, TelegramBot $bot): void
    {
        $botToken = $bot->bot_token;

        if (! $botToken) {
            return;
        }

        $baseUrl = config('app.url');

        // Set up webhook if not already set
        if (! $bot->webhook_url) {
            $webhookUrl = "{$baseUrl}/webhooks/telegram-funnel/{$bot->id}";
            $webhookSecret = $bot->webhook_secret ?: bin2hex(random_bytes(32));

            $webhookResponse = Http::withOptions(['verify' => false, 'connect_timeout' => 15])->timeout(30)->post("https://api.telegram.org/bot{$botToken}/setWebhook", [
                'url' => $webhookUrl,
                'secret_token' => $webhookSecret,
                'allowed_updates' => ['message', 'callback_query', 'my_chat_member'],
            ]);

            if ($webhookResponse->successful()) {
                $bot->update([
                    'webhook_url' => $webhookUrl,
                    'webhook_secret' => $webhookSecret,
                ]);
            }
        }

        // Set WebApp menu button
        $miniAppUrl = "{$baseUrl}/miniapp/{$store->slug}";
        Http::withOptions(['verify' => false, 'connect_timeout' => 15])->timeout(30)->post("https://api.telegram.org/bot{$botToken}/setChatMenuButton", [
            'menu_button' => [
                'type' => 'web_app',
                'text' => 'Do\'kon',
                'web_app' => ['url' => $miniAppUrl],
            ],
        ]);

        Log::info('Existing bot connected to store', [
            'store_id' => $store->id,
            'bot_id' => $bot->id,
            'bot_username' => $bot->bot_username,
        ]);
    }

    /**
     * Activate the store
     */
    public function activateStore(TelegramStore $store): bool
    {
        if (! $store->telegram_bot_id) {
            return false;
        }

        $store->update(['is_active' => true]);

        return true;
    }

    /**
     * Deactivate the store
     */
    public function deactivateStore(TelegramStore $store): void
    {
        $store->update(['is_active' => false]);
    }
}
