<?php

namespace App\Services\Telegram;

use App\Models\TelegramBot;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TelegramApiService
{
    protected string $baseUrl = 'https://api.telegram.org/bot';

    protected TelegramBot $bot;

    protected string $token;

    public function __construct(TelegramBot $bot)
    {
        $this->bot = $bot;
        $this->token = $bot->bot_token;
    }

    /**
     * Make API request to Telegram
     */
    protected function request(string $method, array $data = []): array
    {
        $url = "{$this->baseUrl}{$this->token}/{$method}";

        try {
            $response = Http::timeout(30)->post($url, $data);

            $result = $response->json();

            if (! $response->successful() || ! ($result['ok'] ?? false)) {
                Log::error('Telegram API error', [
                    'method' => $method,
                    'error_code' => $result['error_code'] ?? null,
                    'description' => $result['description'] ?? 'Unknown error',
                    'bot_id' => $this->bot->id,
                ]);

                return [
                    'success' => false,
                    'error_code' => $result['error_code'] ?? 0,
                    'description' => $result['description'] ?? 'Unknown error',
                ];
            }

            return [
                'success' => true,
                'result' => $result['result'],
            ];

        } catch (\Exception $e) {
            Log::error('Telegram API exception', [
                'method' => $method,
                'error' => $e->getMessage(),
                'bot_id' => $this->bot->id,
            ]);

            return [
                'success' => false,
                'error_code' => 0,
                'description' => $e->getMessage(),
            ];
        }
    }

    /**
     * Get bot info (getMe)
     */
    public function getMe(): array
    {
        return $this->request('getMe');
    }

    /**
     * Set webhook
     */
    public function setWebhook(string $url, ?string $secretToken = null): array
    {
        $data = [
            'url' => $url,
            'allowed_updates' => ['message', 'callback_query', 'my_chat_member'],
            'drop_pending_updates' => false,
        ];

        if ($secretToken) {
            $data['secret_token'] = $secretToken;
        }

        return $this->request('setWebhook', $data);
    }

    /**
     * Delete webhook
     */
    public function deleteWebhook(bool $dropPendingUpdates = false): array
    {
        return $this->request('deleteWebhook', [
            'drop_pending_updates' => $dropPendingUpdates,
        ]);
    }

    /**
     * Get webhook info
     */
    public function getWebhookInfo(): array
    {
        return $this->request('getWebhookInfo');
    }

    /**
     * Send message
     */
    public function sendMessage(
        int|string $chatId,
        string $text,
        ?array $keyboard = null,
        string $parseMode = 'HTML',
        bool $disableWebPagePreview = false,
        ?int $replyToMessageId = null
    ): array {
        $data = [
            'chat_id' => $chatId,
            'text' => $text,
            'parse_mode' => $parseMode,
            'disable_web_page_preview' => $disableWebPagePreview,
        ];

        if ($replyToMessageId) {
            $data['reply_to_message_id'] = $replyToMessageId;
        }

        if ($keyboard) {
            $data['reply_markup'] = json_encode($keyboard);
        }

        return $this->request('sendMessage', $data);
    }

    /**
     * Send photo
     */
    public function sendPhoto(
        int|string $chatId,
        string $photo,
        ?string $caption = null,
        ?array $keyboard = null,
        string $parseMode = 'HTML'
    ): array {
        $data = [
            'chat_id' => $chatId,
            'photo' => $photo,
            'parse_mode' => $parseMode,
        ];

        if ($caption) {
            $data['caption'] = $caption;
        }

        if ($keyboard) {
            $data['reply_markup'] = json_encode($keyboard);
        }

        return $this->request('sendPhoto', $data);
    }

    /**
     * Send video
     */
    public function sendVideo(
        int|string $chatId,
        string $video,
        ?string $caption = null,
        ?array $keyboard = null,
        string $parseMode = 'HTML'
    ): array {
        $data = [
            'chat_id' => $chatId,
            'video' => $video,
            'parse_mode' => $parseMode,
        ];

        if ($caption) {
            $data['caption'] = $caption;
        }

        if ($keyboard) {
            $data['reply_markup'] = json_encode($keyboard);
        }

        return $this->request('sendVideo', $data);
    }

    /**
     * Send document
     */
    public function sendDocument(
        int|string $chatId,
        string $document,
        ?string $caption = null,
        ?array $keyboard = null,
        string $parseMode = 'HTML'
    ): array {
        $data = [
            'chat_id' => $chatId,
            'document' => $document,
            'parse_mode' => $parseMode,
        ];

        if ($caption) {
            $data['caption'] = $caption;
        }

        if ($keyboard) {
            $data['reply_markup'] = json_encode($keyboard);
        }

        return $this->request('sendDocument', $data);
    }

    /**
     * Send voice message
     */
    public function sendVoice(
        int|string $chatId,
        string $voice,
        ?string $caption = null,
        ?array $keyboard = null,
        string $parseMode = 'HTML'
    ): array {
        $data = [
            'chat_id' => $chatId,
            'voice' => $voice,
            'parse_mode' => $parseMode,
        ];

        if ($caption) {
            $data['caption'] = $caption;
        }

        if ($keyboard) {
            $data['reply_markup'] = json_encode($keyboard);
        }

        return $this->request('sendVoice', $data);
    }

    /**
     * Send video note (round video)
     */
    public function sendVideoNote(
        int|string $chatId,
        string $videoNote,
        ?int $duration = null,
        ?array $keyboard = null
    ): array {
        $data = [
            'chat_id' => $chatId,
            'video_note' => $videoNote,
        ];

        if ($duration) {
            $data['duration'] = $duration;
        }

        if ($keyboard) {
            $data['reply_markup'] = json_encode($keyboard);
        }

        return $this->request('sendVideoNote', $data);
    }

    /**
     * Send location
     */
    public function sendLocation(
        int|string $chatId,
        float $latitude,
        float $longitude,
        ?array $keyboard = null
    ): array {
        $data = [
            'chat_id' => $chatId,
            'latitude' => $latitude,
            'longitude' => $longitude,
        ];

        if ($keyboard) {
            $data['reply_markup'] = json_encode($keyboard);
        }

        return $this->request('sendLocation', $data);
    }

    /**
     * Send contact
     */
    public function sendContact(
        int|string $chatId,
        string $phoneNumber,
        string $firstName,
        ?string $lastName = null,
        ?array $keyboard = null
    ): array {
        $data = [
            'chat_id' => $chatId,
            'phone_number' => $phoneNumber,
            'first_name' => $firstName,
        ];

        if ($lastName) {
            $data['last_name'] = $lastName;
        }

        if ($keyboard) {
            $data['reply_markup'] = json_encode($keyboard);
        }

        return $this->request('sendContact', $data);
    }

    /**
     * Edit message text
     */
    public function editMessageText(
        int|string $chatId,
        int $messageId,
        string $text,
        ?array $keyboard = null,
        string $parseMode = 'HTML'
    ): array {
        $data = [
            'chat_id' => $chatId,
            'message_id' => $messageId,
            'text' => $text,
            'parse_mode' => $parseMode,
        ];

        if ($keyboard) {
            $data['reply_markup'] = json_encode($keyboard);
        }

        return $this->request('editMessageText', $data);
    }

    /**
     * Edit message reply markup (keyboard)
     */
    public function editMessageReplyMarkup(
        int|string $chatId,
        int $messageId,
        ?array $keyboard = null
    ): array {
        $data = [
            'chat_id' => $chatId,
            'message_id' => $messageId,
        ];

        if ($keyboard) {
            $data['reply_markup'] = json_encode($keyboard);
        }

        return $this->request('editMessageReplyMarkup', $data);
    }

    /**
     * Delete message
     */
    public function deleteMessage(int|string $chatId, int $messageId): array
    {
        return $this->request('deleteMessage', [
            'chat_id' => $chatId,
            'message_id' => $messageId,
        ]);
    }

    /**
     * Answer callback query
     */
    public function answerCallbackQuery(
        string $callbackQueryId,
        ?string $text = null,
        bool $showAlert = false
    ): array {
        $data = [
            'callback_query_id' => $callbackQueryId,
            'show_alert' => $showAlert,
        ];

        if ($text) {
            $data['text'] = $text;
        }

        return $this->request('answerCallbackQuery', $data);
    }

    /**
     * Send chat action (typing, upload_photo, etc.)
     */
    public function sendChatAction(int|string $chatId, string $action = 'typing'): array
    {
        return $this->request('sendChatAction', [
            'chat_id' => $chatId,
            'action' => $action,
        ]);
    }

    /**
     * Get chat member
     */
    public function getChatMember(int|string $chatId, int $userId): array
    {
        return $this->request('getChatMember', [
            'chat_id' => $chatId,
            'user_id' => $userId,
        ]);
    }

    /**
     * Build inline keyboard
     */
    public static function buildInlineKeyboard(array $buttons): array
    {
        $keyboard = [];

        foreach ($buttons as $row) {
            $keyboardRow = [];
            foreach ($row as $button) {
                $btn = ['text' => $button['text']];

                if (isset($button['callback_data'])) {
                    $btn['callback_data'] = $button['callback_data'];
                } elseif (isset($button['url'])) {
                    $btn['url'] = $button['url'];
                }

                $keyboardRow[] = $btn;
            }
            $keyboard[] = $keyboardRow;
        }

        return ['inline_keyboard' => $keyboard];
    }

    /**
     * Build reply keyboard
     */
    public static function buildReplyKeyboard(
        array $buttons,
        bool $resizeKeyboard = true,
        bool $oneTimeKeyboard = false,
        ?string $inputFieldPlaceholder = null
    ): array {
        $keyboard = [];

        foreach ($buttons as $row) {
            $keyboardRow = [];
            foreach ($row as $button) {
                if (is_string($button)) {
                    $keyboardRow[] = ['text' => $button];
                } else {
                    $keyboardRow[] = $button;
                }
            }
            $keyboard[] = $keyboardRow;
        }

        $result = [
            'keyboard' => $keyboard,
            'resize_keyboard' => $resizeKeyboard,
            'one_time_keyboard' => $oneTimeKeyboard,
        ];

        if ($inputFieldPlaceholder) {
            $result['input_field_placeholder'] = $inputFieldPlaceholder;
        }

        return $result;
    }

    /**
     * Build remove keyboard
     */
    public static function buildRemoveKeyboard(): array
    {
        return ['remove_keyboard' => true];
    }

    /**
     * Build request contact button
     */
    public static function buildContactButton(string $text): array
    {
        return [
            'text' => $text,
            'request_contact' => true,
        ];
    }

    /**
     * Build request location button
     */
    public static function buildLocationButton(string $text): array
    {
        return [
            'text' => $text,
            'request_location' => true,
        ];
    }

    /**
     * Check if error is "blocked by user"
     */
    public static function isBlockedByUser(array $response): bool
    {
        $errorCode = $response['error_code'] ?? 0;
        $description = $response['description'] ?? '';

        return $errorCode === 403 && (
            str_contains($description, 'bot was blocked') ||
            str_contains($description, 'user is deactivated') ||
            str_contains($description, 'chat not found')
        );
    }

    /**
     * Check if error is rate limit
     */
    public static function isRateLimited(array $response): bool
    {
        return ($response['error_code'] ?? 0) === 429;
    }

    /**
     * Get retry after seconds from rate limit response
     */
    public static function getRetryAfter(array $response): int
    {
        if (preg_match('/retry after (\d+)/', $response['description'] ?? '', $matches)) {
            return (int) $matches[1];
        }

        return 5;
    }
}
