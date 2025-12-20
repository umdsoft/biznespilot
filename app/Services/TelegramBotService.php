<?php

namespace App\Services;

use App\Models\Business;
use App\Models\ChatbotConfig;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TelegramBotService
{
    protected ChatbotService $chatbotService;

    public function __construct(ChatbotService $chatbotService)
    {
        $this->chatbotService = $chatbotService;
    }

    /**
     * Handle incoming Telegram webhook
     */
    public function handleWebhook(array $update, Business $business): array
    {
        try {
            // Extract message data
            $message = $update['message'] ?? null;

            if (!$message) {
                return ['success' => false, 'message' => 'No message in update'];
            }

            $chatId = $message['chat']['id'];
            $username = $message['from']['username'] ?? null;
            $text = $message['text'] ?? '';

            if (empty($text)) {
                return ['success' => false, 'message' => 'Empty message'];
            }

            // Process message through chatbot service
            $response = $this->chatbotService->processMessage(
                business: $business,
                channel: 'telegram',
                channelUserId: (string) $chatId,
                messageContent: $text,
                channelUsername: $username,
                metadata: [
                    'message_id' => $message['message_id'],
                    'from' => $message['from'],
                    'chat' => $message['chat'],
                ]
            );

            if ($response['success']) {
                // Send response back to user
                $this->sendMessage(
                    $business,
                    $chatId,
                    $response['response'],
                    $response['attachments'] ?? []
                );
            }

            return $response;

        } catch (\Exception $e) {
            Log::error('Telegram webhook error', [
                'business_id' => $business->id,
                'error' => $e->getMessage(),
                'update' => $update,
            ]);

            return [
                'success' => false,
                'message' => 'Webhook processing error',
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Send message to Telegram user
     */
    public function sendMessage(
        Business $business,
        string $chatId,
        string $text,
        array $attachments = []
    ): bool {
        $config = ChatbotConfig::where('business_id', $business->id)->first();

        if (!$config || !$config->telegram_enabled || !$config->telegram_bot_token) {
            return false;
        }

        $url = "https://api.telegram.org/bot{$config->telegram_bot_token}/sendMessage";

        $payload = [
            'chat_id' => $chatId,
            'text' => $text,
            'parse_mode' => 'HTML',
        ];

        // Add inline keyboard if attachments (buttons) present
        if (!empty($attachments)) {
            $payload['reply_markup'] = $this->buildInlineKeyboard($attachments);
        }

        try {
            $response = Http::post($url, $payload);

            if ($response->successful()) {
                return true;
            }

            Log::error('Telegram send message failed', [
                'response' => $response->json(),
                'chat_id' => $chatId,
            ]);

            return false;

        } catch (\Exception $e) {
            Log::error('Telegram API error', [
                'error' => $e->getMessage(),
                'chat_id' => $chatId,
            ]);

            return false;
        }
    }

    /**
     * Build inline keyboard from attachments
     */
    private function buildInlineKeyboard(array $buttons): array
    {
        $keyboard = [];

        foreach ($buttons as $button) {
            $keyboard[] = [[
                'text' => $button['text'] ?? $button['label'],
                'callback_data' => $button['callback'] ?? $button['value'],
            ]];
        }

        return ['inline_keyboard' => $keyboard];
    }

    /**
     * Set webhook URL
     */
    public function setWebhook(string $botToken, string $webhookUrl): array
    {
        $url = "https://api.telegram.org/bot{$botToken}/setWebhook";

        try {
            $response = Http::post($url, [
                'url' => $webhookUrl,
                'allowed_updates' => ['message', 'callback_query'],
            ]);

            return [
                'success' => $response->successful(),
                'data' => $response->json(),
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Get webhook info
     */
    public function getWebhookInfo(string $botToken): array
    {
        $url = "https://api.telegram.org/bot{$botToken}/getWebhookInfo";

        try {
            $response = Http::get($url);

            return [
                'success' => $response->successful(),
                'data' => $response->json(),
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Get bot info
     */
    public function getBotInfo(string $botToken): array
    {
        $url = "https://api.telegram.org/bot{$botToken}/getMe";

        try {
            $response = Http::get($url);

            if ($response->successful()) {
                $data = $response->json();

                return [
                    'success' => true,
                    'bot' => $data['result'] ?? null,
                ];
            }

            return [
                'success' => false,
                'error' => 'Failed to get bot info',
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Send typing action
     */
    public function sendTypingAction(string $botToken, string $chatId): bool
    {
        $url = "https://api.telegram.org/bot{$botToken}/sendChatAction";

        try {
            $response = Http::post($url, [
                'chat_id' => $chatId,
                'action' => 'typing',
            ]);

            return $response->successful();

        } catch (\Exception $e) {
            return false;
        }
    }
}
