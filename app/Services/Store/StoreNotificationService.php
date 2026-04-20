<?php

namespace App\Services\Store;

use App\Models\Store\StoreOrder;
use App\Models\TelegramBot;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class StoreNotificationService
{
    /**
     * Notify store admin about new order
     */
    public function notifyNewOrder(StoreOrder $order): void
    {
        $store = $order->store;
        $bot = $store->telegramBot;

        if (! $bot) {
            return;
        }

        $customer = $order->customer;
        $itemsText = $order->items->map(fn ($item) => "  • {$item->product_name} × {$item->quantity} = " . number_format($item->total, 0, '', ' ') . " so'm")->implode("\n");

        $message = "🛒 *Yangi buyurtma!*\n\n"
            . "📋 Buyurtma: #{$order->order_number}\n"
            . "👤 Mijoz: {$customer->getDisplayName()}\n"
            . ($customer->phone ? "📞 Tel: {$customer->phone}\n" : '')
            . "\n📦 Mahsulotlar:\n{$itemsText}\n"
            . "\n💰 *Jami: " . number_format($order->total, 0, '', ' ') . " so'm*"
            . ($order->payment_method ? "\n💳 To'lov: {$order->payment_method}" : '');

        // Send to business owner's Telegram (if configured)
        $adminChatId = $store->getSetting('admin_chat_id');

        if ($adminChatId) {
            $this->sendMessage($bot, $adminChatId, $message);
        }
    }

    /**
     * Notify customer about order status change
     */
    public function notifyOrderStatusChange(StoreOrder $order, string $newStatus): void
    {
        $store = $order->store;
        $bot = $store->telegramBot;
        $customer = $order->customer;

        if (! $bot || ! $customer->telegram_user_id) {
            return;
        }

        $telegramUser = $customer->telegramUser;
        if (! $telegramUser) {
            return;
        }

        $statusEmoji = match ($newStatus) {
            StoreOrder::STATUS_CONFIRMED => '✅',
            StoreOrder::STATUS_PROCESSING => '⚙️',
            StoreOrder::STATUS_SHIPPED => '🚚',
            StoreOrder::STATUS_DELIVERED => '📦',
            StoreOrder::STATUS_CANCELLED => '❌',
            StoreOrder::STATUS_REFUNDED => '💰',
            default => 'ℹ️',
        };

        $order->loadMissing('items');

        $itemsText = $order->items->map(
            fn ($item) => "  • {$item->product_name} × {$item->quantity}"
        )->implode("\n");

        $message = "{$statusEmoji} *Buyurtma yangilandi*\n\n"
            . "📋 Buyurtma: #{$order->order_number}\n"
            . "📊 Status: {$order->getStatusLabel()}\n"
            . "\n📦 Mahsulotlar:\n{$itemsText}\n"
            . "\n💰 *Jami: " . number_format($order->total, 0, '', ' ') . " so'm*";

        $this->sendMessage($bot, $telegramUser->telegram_id, $message);
    }

    /**
     * Notify about payment received
     */
    public function notifyPaymentReceived(StoreOrder $order): void
    {
        $store = $order->store;
        $bot = $store->telegramBot;

        if (! $bot) {
            return;
        }

        $adminChatId = $store->getSetting('admin_chat_id');

        if ($adminChatId) {
            $message = "💰 *To'lov qabul qilindi!*\n\n"
                . "📋 Buyurtma: #{$order->order_number}\n"
                . "💳 Usul: {$order->payment_method}\n"
                . "💵 Summa: " . number_format($order->total, 0, '', ' ') . " so'm";

            $this->sendMessage($bot, $adminChatId, $message);
        }
    }

    /**
     * Send message via Telegram Bot API
     */
    protected function sendMessage(TelegramBot $bot, string $chatId, string $text): bool
    {
        try {
            $token = $bot->bot_token;
            if (! $token) {
                return false;
            }

            $response = Http::withOptions(['verify' => false])->timeout(30)->post("https://api.telegram.org/bot{$token}/sendMessage", [
                'chat_id' => $chatId,
                'text' => $text,
                'parse_mode' => 'Markdown',
            ]);

            return $response->successful();
        } catch (\Exception $e) {
            Log::warning('Store notification failed', [
                'chat_id' => $chatId,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }
}
