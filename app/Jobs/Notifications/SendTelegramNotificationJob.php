<?php

namespace App\Jobs\Notifications;

use App\Models\Business;
use App\Models\NotificationDelivery;
use App\Models\TelegramBot;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SendTelegramNotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $backoff = 30;
    public int $timeout = 30;

    public function __construct(
        public NotificationDelivery $delivery
    ) {}

    public function handle(): void
    {
        $delivery = $this->delivery;

        if (!$delivery->isPending()) {
            return;
        }

        $chatId = $delivery->metadata['telegram_chat_id'] ?? null;
        if (!$chatId) {
            $delivery->markAsFailed('Telegram chat ID topilmadi');
            return;
        }

        // Get business Telegram bot
        $bot = TelegramBot::where('business_id', $delivery->business_id)
            ->where('is_active', true)
            ->first();

        if (!$bot || !$bot->bot_token) {
            $delivery->markAsFailed('Aktiv Telegram bot topilmadi');
            return;
        }

        try {
            $result = $this->sendMessage($bot->bot_token, $chatId, $delivery);

            if ($result['success']) {
                $delivery->markAsSent($result['message_id'] ?? null);
                Log::info('Telegram notification sent', [
                    'delivery_id' => $delivery->id,
                    'chat_id' => $chatId,
                ]);
            } else {
                $delivery->markAsFailed($result['error'] ?? 'Unknown error');
            }
        } catch (\Exception $e) {
            Log::error('Telegram notification failed', [
                'delivery_id' => $delivery->id,
                'error' => $e->getMessage(),
            ]);

            if ($this->attempts() >= $this->tries) {
                $delivery->markAsFailed($e->getMessage());
            }

            throw $e;
        }
    }

    protected function sendMessage(string $botToken, string $chatId, NotificationDelivery $delivery): array
    {
        $url = "https://api.telegram.org/bot{$botToken}/sendMessage";

        // Format message
        $text = $this->formatMessage($delivery);

        $payload = [
            'chat_id' => $chatId,
            'text' => $text,
            'parse_mode' => 'HTML',
            'disable_web_page_preview' => true,
        ];

        // Add action button if available
        $actionUrl = $delivery->metadata['action_url'] ?? null;
        $actionText = $delivery->metadata['action_text'] ?? null;
        if ($actionUrl) {
            $payload['reply_markup'] = [
                'inline_keyboard' => [[
                    [
                        'text' => $actionText ?? "Ko'rish",
                        'url' => $actionUrl,
                    ]
                ]]
            ];
        }

        $response = Http::timeout(20)->post($url, $payload);

        if ($response->successful()) {
            $data = $response->json();
            if ($data['ok'] ?? false) {
                return [
                    'success' => true,
                    'message_id' => $data['result']['message_id'] ?? null,
                ];
            }
        }

        return [
            'success' => false,
            'error' => $response->json()['description'] ?? 'Telegram API error',
        ];
    }

    protected function formatMessage(NotificationDelivery $delivery): string
    {
        $emoji = match ($delivery->type) {
            'alert' => '⚠️',
            'kpi' => '📊',
            'task' => '✅',
            'lead' => '👤',
            'report' => '📈',
            'celebration' => '🎉',
            'insight' => '💡',
            default => '📢',
        };

        $typeLabel = match ($delivery->type) {
            'alert' => 'Ogohlantirish',
            'kpi' => 'KPI',
            'task' => 'Vazifa',
            'lead' => 'Yangi Lid',
            'report' => 'Hisobot',
            'celebration' => 'Tabriklaymiz!',
            'insight' => 'Insight',
            default => 'Bildirishnoma',
        };

        $lines = [
            "{$emoji} <b>{$typeLabel}</b>",
            "",
            "<b>{$delivery->title}</b>",
            "",
            $delivery->message,
        ];

        // Add extra data if available
        $extraData = $delivery->metadata['extra_data'] ?? [];
        if (!empty($extraData)) {
            $lines[] = "";
            $lines[] = "───────────────";
            foreach ($extraData as $key => $value) {
                $lines[] = "{$key}: <b>{$value}</b>";
            }
        }

        return implode("\n", $lines);
    }
}
