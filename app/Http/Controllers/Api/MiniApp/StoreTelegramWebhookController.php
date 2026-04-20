<?php

namespace App\Http\Controllers\Api\MiniApp;

use App\Http\Controllers\Controller;
use App\Models\Store\StoreCustomer;
use App\Models\Store\StoreOrder;
use App\Models\Store\TelegramStore;
use App\Models\TelegramUser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class StoreTelegramWebhookController extends Controller
{
    /**
     * Handle Telegram webhook for store bot
     */
    public function handle(Request $request, string $store)
    {
        $telegramStore = TelegramStore::find($store);

        if (! $telegramStore || ! $telegramStore->is_active) {
            return response()->json(['ok' => true]);
        }

        $bot = $telegramStore->telegramBot;
        if (! $bot) {
            return response()->json(['ok' => true]);
        }

        // Verify secret token
        $secretToken = $request->header('X-Telegram-Bot-Api-Secret-Token');
        if ($bot->webhook_secret && ! hash_equals($bot->webhook_secret, $secretToken ?? '')) {
            return response()->json(['ok' => false], 403);
        }

        $update = $request->all();

        try {
            if (isset($update['message'])) {
                $this->handleMessage($telegramStore, $bot, $update['message']);
            } elseif (isset($update['callback_query'])) {
                $this->handleCallbackQuery($telegramStore, $bot, $update['callback_query']);
            } elseif (isset($update['pre_checkout_query'])) {
                $this->handlePreCheckoutQuery($bot, $update['pre_checkout_query']);
            }
        } catch (\Exception $e) {
            Log::error('Store webhook error', [
                'store_id' => $telegramStore->id,
                'error' => $e->getMessage(),
            ]);
        }

        return response()->json(['ok' => true]);
    }

    protected function handleMessage(TelegramStore $store, $bot, array $message): void
    {
        $chatId = $message['chat']['id'];
        $text = $message['text'] ?? '';
        $from = $message['from'] ?? [];

        // Find or create telegram user
        $this->getOrCreateTelegramUser($store, $bot, $from);

        match (true) {
            str_starts_with($text, '/start') => $this->handleStart($store, $bot, $chatId),
            str_starts_with($text, '/myorders') => $this->handleMyOrders($store, $bot, $chatId, $from),
            str_starts_with($text, '/help') => $this->handleHelp($store, $bot, $chatId),
            default => $this->handleDefault($store, $bot, $chatId),
        };
    }

    protected function handleStart(TelegramStore $store, $bot, string $chatId): void
    {
        $miniAppUrl = $store->getMiniAppUrl();

        $text = "🛍 *{$store->name}* ga xush kelibsiz!\n\n";

        if ($store->description) {
            $text .= "{$store->description}\n\n";
        }

        $text .= "📱 Do'konni ochish uchun quyidagi tugmani bosing:";

        $keyboard = [
            'inline_keyboard' => [
                [['text' => '🛒 Do\'konni ochish', 'web_app' => ['url' => $miniAppUrl]]],
                [['text' => '📦 Buyurtmalarim', 'callback_data' => 'my_orders']],
            ],
        ];

        $this->sendMessage($bot, $chatId, $text, $keyboard);
    }

    protected function handleMyOrders(TelegramStore $store, $bot, string $chatId, array $from): void
    {
        $telegramUser = TelegramUser::where('telegram_bot_id', $bot->id)
            ->where('telegram_id', $from['id'])
            ->first();

        if (! $telegramUser) {
            $this->sendMessage($bot, $chatId, "Siz hali buyurtma bermadingiz.\n\n🛒 Do'konni ochish uchun /start buyrug'ini yuboring.");

            return;
        }

        $customer = StoreCustomer::where('store_id', $store->id)
            ->where('telegram_user_id', $telegramUser->id)
            ->first();

        if (! $customer || $customer->orders_count === 0) {
            $this->sendMessage($bot, $chatId, "Siz hali buyurtma bermadingiz.\n\n🛒 Do'konni ochish uchun /start buyrug'ini yuboring.");

            return;
        }

        $recentOrders = $customer->orders()
            ->latest()
            ->take(5)
            ->get();

        $text = "📦 *Oxirgi buyurtmalaringiz:*\n\n";

        foreach ($recentOrders as $order) {
            $statusEmoji = match ($order->status) {
                StoreOrder::STATUS_PENDING => '⏳',
                StoreOrder::STATUS_CONFIRMED => '✅',
                StoreOrder::STATUS_PROCESSING => '⚙️',
                StoreOrder::STATUS_SHIPPED => '🚚',
                StoreOrder::STATUS_DELIVERED => '📦',
                StoreOrder::STATUS_CANCELLED => '❌',
                default => 'ℹ️',
            };

            $total = number_format($order->total, 0, '', ' ');
            $date = $order->created_at->format('d.m.Y');
            $text .= "{$statusEmoji} #{$order->order_number} — {$total} so'm ({$date})\n";
        }

        $text .= "\n📱 Batafsil ko'rish uchun do'konni oching.";

        $keyboard = [
            'inline_keyboard' => [
                [['text' => '🛒 Do\'konni ochish', 'web_app' => ['url' => $store->getMiniAppUrl()]]],
            ],
        ];

        $this->sendMessage($bot, $chatId, $text, $keyboard);
    }

    protected function handleHelp(TelegramStore $store, $bot, string $chatId): void
    {
        $text = "ℹ️ *Yordam*\n\n"
            . "/start — Do'konni ochish\n"
            . "/myorders — Buyurtmalarim\n"
            . "/help — Yordam\n";

        if ($store->phone) {
            $text .= "\n📞 Bog'lanish: {$store->phone}";
        }

        $this->sendMessage($bot, $chatId, $text);
    }

    protected function handleDefault(TelegramStore $store, $bot, string $chatId): void
    {
        $keyboard = [
            'inline_keyboard' => [
                [['text' => '🛒 Do\'konni ochish', 'web_app' => ['url' => $store->getMiniAppUrl()]]],
            ],
        ];

        $this->sendMessage($bot, $chatId, "🛍 Do'konni ochish uchun quyidagi tugmani bosing:", $keyboard);
    }

    protected function handleCallbackQuery(TelegramStore $store, $bot, array $query): void
    {
        $chatId = $query['message']['chat']['id'];
        $data = $query['callback_data'] ?? '';

        if ($data === 'my_orders') {
            $this->handleMyOrders($store, $bot, $chatId, $query['from']);
        }

        // Answer callback query to remove loading state
        $token = $bot->bot_token;
        Http::withOptions(['verify' => false])->timeout(30)->post("https://api.telegram.org/bot{$token}/answerCallbackQuery", [
            'callback_query_id' => $query['id'],
        ]);
    }

    protected function handlePreCheckoutQuery($bot, array $query): void
    {
        $token = $bot->bot_token;
        Http::withOptions(['verify' => false])->timeout(30)->post("https://api.telegram.org/bot{$token}/answerPreCheckoutQuery", [
            'pre_checkout_query_id' => $query['id'],
            'ok' => true,
        ]);
    }

    protected function getOrCreateTelegramUser(TelegramStore $store, $bot, array $from): TelegramUser
    {
        return TelegramUser::firstOrCreate(
            [
                'telegram_bot_id' => $bot->id,
                'telegram_id' => $from['id'],
            ],
            [
                'business_id' => $store->business_id,
                'username' => $from['username'] ?? null,
                'first_name' => $from['first_name'] ?? null,
                'last_name' => $from['last_name'] ?? null,
                'language_code' => $from['language_code'] ?? 'uz',
                'first_interaction_at' => now(),
                'last_interaction_at' => now(),
            ]
        );
    }

    protected function sendMessage($bot, string $chatId, string $text, ?array $replyMarkup = null): void
    {
        $token = $bot->bot_token;
        if (! $token) {
            return;
        }

        $payload = [
            'chat_id' => $chatId,
            'text' => $text,
            'parse_mode' => 'Markdown',
        ];

        if ($replyMarkup) {
            $payload['reply_markup'] = $replyMarkup;
        }

        Http::withOptions(['verify' => false])->timeout(30)->post("https://api.telegram.org/bot{$token}/sendMessage", $payload);
    }
}
