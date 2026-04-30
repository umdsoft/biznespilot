<?php

declare(strict_types=1);

namespace App\Services\Telegram;

use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * SystemBotService - BiznesPilot System Bot xizmati
 *
 * "Dual Bot Strategy":
 * - Tenant Bot: Bizneslar o'z mijozlari bilan aloqa qilish uchun (Flow Builder)
 * - System Bot: BiznesPilot adminlarga xabar yuborish uchun (Daily Brief, Alerts)
 *
 * Bu service faqat System Bot uchun ishlaydi.
 */
class SystemBotService
{
    private const API_BASE_URL = 'https://api.telegram.org/bot';

    private ?string $botToken;

    private ?string $botUsername;

    public function __construct()
    {
        $this->botToken = config('services.telegram.system_bot_token');
        $this->botUsername = config('services.telegram.system_bot_username');
    }

    /**
     * Check if System Bot is configured.
     */
    public function isConfigured(): bool
    {
        return !empty($this->botToken);
    }

    /**
     * Bot token getter — Publisher servislari uchun.
     */
    public function getToken(): ?string
    {
        return $this->botToken;
    }

    public function getUsername(): ?string
    {
        return $this->botUsername;
    }

    /**
     * Generate deep link for user authentication.
     *
     * Foydalanuvchi bu link orqali botni ochganda,
     * /start {token} buyrug'i yuboriladi.
     */
    public function generateAuthLink(User $user): string
    {
        // Generate unique token
        $token = Str::random(32);

        // Save token to user
        $user->update([
            'telegram_auth_token' => $token,
        ]);

        // Generate deep link
        return "https://t.me/{$this->botUsername}?start={$token}";
    }

    /**
     * Handle /start command from webhook.
     *
     * @return array{success: bool, message: string}
     */
    public function handleStartCommand(string $chatId, ?string $token = null): array
    {
        // If no token, just send welcome
        if (empty($token)) {
            $this->sendMessage($chatId, $this->getWelcomeMessage());

            return [
                'success' => true,
                'message' => 'Welcome message sent',
            ];
        }

        // Find user by token
        $user = User::where('telegram_auth_token', $token)->first();

        if (!$user) {
            $this->sendMessage($chatId, $this->getInvalidTokenMessage());

            return [
                'success' => false,
                'message' => 'Invalid token',
            ];
        }

        // Link user's Telegram account
        $user->update([
            'telegram_chat_id' => $chatId,
            'telegram_auth_token' => null, // Clear token after use
            'telegram_linked_at' => now(),
        ]);

        // Clear user cache to update UI immediately
        \App\Http\Middleware\HandleInertiaRequests::clearUserCache($user->id);

        // Send success message with main menu (Jarvis Onboarding)
        $this->sendMessage($chatId, $this->getLinkedSuccessMessage($user));

        // Show main menu
        $this->sendMainMenu($chatId, $user);

        Log::info('SystemBot: User linked Telegram account', [
            'user_id' => $user->id,
            'chat_id' => $chatId,
        ]);

        return [
            'success' => true,
            'message' => 'User linked successfully',
            'user' => $user,
        ];
    }

    /**
     * Send message to a chat.
     */
    public function sendMessage(
        string $chatId,
        string $text,
        array $options = []
    ): bool {
        if (!$this->isConfigured()) {
            Log::warning('SystemBot: Bot token not configured');

            return false;
        }

        try {
            $params = array_merge([
                'chat_id' => $chatId,
                'text' => $text,
                'parse_mode' => 'HTML',
                'disable_web_page_preview' => true,
            ], $options);

            $response = Http::post(
                self::API_BASE_URL . $this->botToken . '/sendMessage',
                $params
            );

            if (!$response->successful()) {
                Log::error('SystemBot: Failed to send message', [
                    'chat_id' => $chatId,
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);

                return false;
            }

            return true;

        } catch (\Exception $e) {
            Log::error('SystemBot: Exception sending message', [
                'chat_id' => $chatId,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Send message to a user by User model.
     */
    public function sendToUser(User $user, string $text, array $options = []): bool
    {
        if (empty($user->telegram_chat_id)) {
            Log::warning('SystemBot: User has no linked Telegram', [
                'user_id' => $user->id,
            ]);

            return false;
        }

        return $this->sendMessage($user->telegram_chat_id, $text, $options);
    }

    /**
     * Send daily brief to a user.
     */
    public function sendDailyBrief(User $user, string $briefContent): bool
    {
        return $this->sendToUser($user, $this->convertToHtml($briefContent));
    }

    /**
     * Send message with inline keyboard.
     */
    public function sendMessageWithInlineKeyboard(
        string $chatId,
        string $text,
        array $keyboard
    ): bool {
        return $this->sendMessage($chatId, $text, [
            'reply_markup' => json_encode([
                'inline_keyboard' => $keyboard,
            ]),
        ]);
    }

    /**
     * Send main menu with reply keyboard (Jarvis Menu).
     */
    public function sendMainMenu(string $chatId, ?User $user): bool
    {
        $firstName = $user ? explode(' ', $user->name)[0] : 'Boss';

        $text = "⚡️ <b>Buyruqlaringizni kutaman, {$firstName}!</b>";

        $keyboard = [
            [
                ['text' => '📊 Statistika'],
                ['text' => '💰 Kassa'],
            ],
            [
                ['text' => '📝 Vazifa Berish'],
                ['text' => '⚙️ Sozlamalar'],
            ],
        ];

        return $this->sendMessage($chatId, $text, [
            'reply_markup' => json_encode([
                'keyboard' => $keyboard,
                'resize_keyboard' => true,
                'one_time_keyboard' => false,
            ]),
        ]);
    }

    /**
     * Answer callback query (for inline buttons).
     */
    public function answerCallbackQuery(string $callbackQueryId, ?string $text = null): bool
    {
        if (!$this->isConfigured()) {
            return false;
        }

        try {
            $params = ['callback_query_id' => $callbackQueryId];
            if ($text) {
                $params['text'] = $text;
            }

            $response = Http::post(
                self::API_BASE_URL . $this->botToken . '/answerCallbackQuery',
                $params
            );

            return $response->successful();
        } catch (\Exception $e) {
            Log::error('SystemBot: Exception answering callback query', [
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Edit message text (for updating inline keyboard messages).
     */
    public function editMessageText(
        string $chatId,
        int $messageId,
        string $text,
        ?array $keyboard = null
    ): bool {
        if (!$this->isConfigured()) {
            return false;
        }

        try {
            $params = [
                'chat_id' => $chatId,
                'message_id' => $messageId,
                'text' => $text,
                'parse_mode' => 'HTML',
            ];

            if ($keyboard) {
                $params['reply_markup'] = json_encode([
                    'inline_keyboard' => $keyboard,
                ]);
            }

            $response = Http::post(
                self::API_BASE_URL . $this->botToken . '/editMessageText',
                $params
            );

            return $response->successful();
        } catch (\Exception $e) {
            Log::error('SystemBot: Exception editing message', [
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Send payment alert (Dopamine trigger).
     */
    public function sendPaymentAlert(
        User $user,
        float $amount,
        string $provider,
        ?string $clientName = null
    ): bool {
        $currency = "so'm";
        $formattedAmount = number_format($amount, 0, '.', ' ');

        $text = "🤑 <b>+ {$formattedAmount}</b> {$currency} kelib tushdi!\n\n"
            . "💳 Tizim: <b>{$provider}</b>\n";

        if ($clientName) {
            $text .= "👤 Mijoz: {$clientName}\n";
        }

        $text .= "\n💪 <i>Davom eting!</i>";

        return $this->sendToUser($user, $text);
    }

    /**
     * Send stagnant task alert (The Whip).
     */
    public function sendStagnantTaskAlert(
        User $user,
        string $taskId,
        string $taskTitle,
        string $employeeName,
        int $hoursOverdue
    ): bool {
        $text = "🐢 <b>Vazifa o'lib yotibdi!</b>\n\n"
            . "📝 \"{$taskTitle}\"\n"
            . "👨‍💼 {$employeeName}\n"
            . "⏰ {$hoursOverdue} soat kechikkan\n\n"
            . "Xodimga eslatma yuborasizmi?";

        $keyboard = [
            [
                ['text' => '👊 Turtib qo\'yish', 'callback_data' => "nudge_task_{$taskId}"],
                ['text' => '✅ Ignore', 'callback_data' => "ignore_task_{$taskId}"],
            ],
        ];

        return $this->sendMessageWithInlineKeyboard($user->telegram_chat_id, $text, $keyboard);
    }

    /**
     * Send nudge notification to employee.
     */
    public function sendNudgeToEmployee(User $employee, string $taskTitle, string $managerName): bool
    {
        $text = "👊 <b>Eslatma!</b>\n\n"
            . "📝 \"{$taskTitle}\" vazifasi kutilmoqda.\n"
            . "👨‍💼 {$managerName} eslatdi.\n\n"
            . "⏰ Iltimos, tezroq bajaring!";

        return $this->sendToUser($employee, $text);
    }

    /**
     * Send record broken / viral message.
     */
    public function sendRecordBrokenAlert(
        User $user,
        string $recordType,
        float $currentValue,
        float $previousRecord
    ): bool {
        $currency = "so'm";

        $text = "🎉 <b>REKORD YANGILANDI!</b>\n\n"
            . "🏆 {$recordType}\n"
            . "📈 Yangi rekord: <b>" . number_format($currentValue, 0, '.', ' ') . "</b> {$currency}\n"
            . "📊 Oldingi: " . number_format($previousRecord, 0, '.', ' ') . " {$currency}\n\n"
            . "🚀 <i>Davom eting!</i>";

        $keyboard = [
            [
                ['text' => '📢 Do\'stlarga ulashish', 'callback_data' => 'share_record'],
            ],
        ];

        return $this->sendMessageWithInlineKeyboard($user->telegram_chat_id, $text, $keyboard);
    }

    /**
     * Set webhook for the System Bot.
     *
     * allowed_updates is extended to include channel analytics events:
     * - my_chat_member: bot added/removed as admin in a chat
     * - chat_member: channel subscribers join/leave (requires admin + can_manage_chat)
     * - channel_post / edited_channel_post: posts tracking
     * - message_reaction / message_reaction_count: reactions tracking (Bot API 7.0+)
     * - chat_boost / removed_chat_boost: boost tracking
     */
    public function setWebhook(string $webhookUrl): bool
    {
        if (!$this->isConfigured()) {
            return false;
        }

        try {
            $params = [
                'url' => $webhookUrl,
                'allowed_updates' => [
                    'message',
                    'callback_query',
                    'my_chat_member',
                    'chat_member',
                    'channel_post',
                    'edited_channel_post',
                    'message_reaction',
                    'message_reaction_count',
                    'chat_boost',
                    'removed_chat_boost',
                ],
                'drop_pending_updates' => true,
            ];

            // MUHIM: Telegram'ning DNS resolveri ba'zan .uz TLD bilan
            // ishlamaydi ("Failed to resolve host: Temporary failure in name
            // resolution"). Bu setWebhook'ni butunlay buzadi va eski webhook'ni
            // ham o'chirib yuboradi. ip_address parametri Telegram'ga to'g'ridan-
            // to'g'ri shu IP'ga ulanishni aytadi (DNS resolve'ni bypass qiladi).
            $ipAddress = config('services.telegram.webhook_ip_address');
            if ($ipAddress) {
                $params['ip_address'] = $ipAddress;
            }

            // Add secret token if configured
            $secret = config('services.telegram.webhook_secret');
            if ($secret) {
                $params['secret_token'] = $secret;
            }

            $response = Http::post(
                self::API_BASE_URL . $this->botToken . '/setWebhook',
                $params
            );

            if (!$response->successful()) {
                Log::error('SystemBot: Failed to set webhook', [
                    'url' => $webhookUrl,
                    'response' => $response->body(),
                ]);

                return false;
            }

            Log::info('SystemBot: Webhook set successfully', [
                'url' => $webhookUrl,
            ]);

            return true;

        } catch (\Exception $e) {
            Log::error('SystemBot: Exception setting webhook', [
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Delete webhook.
     */
    public function deleteWebhook(): bool
    {
        if (!$this->isConfigured()) {
            return false;
        }

        try {
            $response = Http::post(
                self::API_BASE_URL . $this->botToken . '/deleteWebhook'
            );

            return $response->successful();

        } catch (\Exception $e) {
            Log::error('SystemBot: Exception deleting webhook', [
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Get bot info.
     */
    public function getBotInfo(): ?array
    {
        if (!$this->isConfigured()) {
            return null;
        }

        try {
            $response = Http::get(
                self::API_BASE_URL . $this->botToken . '/getMe'
            );

            if (!$response->successful()) {
                return null;
            }

            return $response->json()['result'] ?? null;

        } catch (\Exception $e) {
            Log::error('SystemBot: Exception getting bot info', [
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Convert Markdown to HTML for Telegram.
     */
    protected function convertToHtml(string $markdown): string
    {
        // Convert *bold* to <b>bold</b>
        $html = preg_replace('/\*([^*]+)\*/', '<b>$1</b>', $markdown);

        // Convert _italic_ to <i>italic</i>
        $html = preg_replace('/_([^_]+)_/', '<i>$1</i>', $html);

        // Convert `code` to <code>code</code>
        $html = preg_replace('/`([^`]+)`/', '<code>$1</code>', $html);

        return $html;
    }

    // ============================================
    // MESSAGE TEMPLATES
    // ============================================

    protected function getWelcomeMessage(): string
    {
        return "🤖 <b>Assalomu alaykum!</b>\n\n"
            . "Men <b>BiznesPilot</b> — biznes egalarining raqamli bosh menejeri.\n\n"
            . "━━━━━━━━━━━━━━━━━━━━\n\n"
            . "💰 Pul kelganda — darhol xabar beraman\n"
            . "🐢 Vazifa kechiksa — xodimni \"turtib qo'yaman\"\n"
            . "📊 Har kuni ertalab — biznes holatini aytaman\n"
            . "📝 Vazifa berish — to'g'ridan-to'g'ri shu yerdan\n\n"
            . "━━━━━━━━━━━━━━━━━━━━\n\n"
            . "🔗 Ulash uchun:\n"
            . "<b>BiznesPilot</b> → Sozlamalar → <b>\"Telegram ulash\"</b>\n\n"
            . "⚡️ <i>Biznesingizni cho'ntagingizdan boshqaring!</i>";
    }

    protected function getInvalidTokenMessage(): string
    {
        return "❌ <b>Xatolik!</b>\n\n"
            . "Havola eskirgan yoki noto'g'ri.\n\n"
            . "Iltimos, BiznesPilot dashboardidan yangi havola oling.";
    }

    protected function getLinkedSuccessMessage(User $user): string
    {
        $firstName = explode(' ', $user->name)[0];

        return "🚀 <b>Xush kelibsiz, {$firstName}!</b>\n\n"
            . "Men sizning <b>raqamli bosh menejeringiz</b>man.\n"
            . "Endi biznesingiz 24/7 nazorat ostida.\n\n"
            . "━━━━━━━━━━━━━━━━━━━━\n\n"
            . "🎯 <b>Men nima qila olaman:</b>\n\n"
            . "💰 <b>Pul keldi</b> — darhol xabar beraman\n"
            . "🐢 <b>Vazifa kechikdi</b> — xodimni \"turtib qo'yaman\"\n"
            . "📊 <b>Kunlik brief</b> — har kuni 07:00 da\n"
            . "📝 <b>Vazifa berish</b> — to'g'ridan-to'g'ri shu yerdan\n\n"
            . "━━━━━━━━━━━━━━━━━━━━\n\n"
            . "🏆 <i>Rekord yangilansami — birinchi bo'lib bilasiz!</i>";
    }

    /**
     * Get unlink confirmation message.
     */
    public function getUnlinkMessage(): string
    {
        return "🔓 <b>Telegram hisobingiz uzildi</b>\n\n"
            . "Siz endi BiznesPilot dan xabar olmaysiz.\n\n"
            . "Qayta ulash uchun dashboardga kiring.";
    }

    // ============================================
    // CHANNEL ANALYTICS API METHODS
    // ============================================

    /**
     * Generate deep link for adding bot as channel admin.
     *
     * Telegram Bot API deep-link format:
     *   https://t.me/<bot_username>?startchannel=true&admin=<rights>
     *
     * User kanalga bot'ni admin qilganda Telegram bizga my_chat_member
     * update yuboradi. Minimal admin-right: can_manage_chat (reactions + members).
     */
    public function generateChannelDeepLink(): ?string
    {
        if (empty($this->botUsername)) {
            return null;
        }

        // Minimal admin rights for analytics:
        //  - manage_chat: receive chat_member + message_reaction updates
        // We explicitly avoid post/edit/delete rights.
        $rights = 'manage_chat';

        return "https://t.me/{$this->botUsername}?startchannel=true&admin={$rights}";
    }

    /**
     * Get bot username (for generating deep links).
     */
    public function getBotUsername(): ?string
    {
        return $this->botUsername;
    }

    /**
     * Get chat info.
     *
     * @return array|null Telegram Chat object
     */
    public function getChat(int|string $chatId): ?array
    {
        return $this->apiRequest('getChat', ['chat_id' => $chatId]);
    }

    /**
     * Get chat member count.
     */
    public function getChatMemberCount(int|string $chatId): ?int
    {
        $result = $this->apiRequest('getChatMemberCount', ['chat_id' => $chatId]);

        return is_int($result) ? $result : null;
    }

    /**
     * Get list of chat administrators.
     *
     * @return array|null Array of ChatMember objects
     */
    public function getChatAdministrators(int|string $chatId): ?array
    {
        return $this->apiRequest('getChatAdministrators', ['chat_id' => $chatId]);
    }

    /**
     * Leave a chat (used when user wants to stop tracking a channel).
     */
    public function leaveChat(int|string $chatId): bool
    {
        $result = $this->apiRequest('leaveChat', ['chat_id' => $chatId]);

        return $result === true;
    }

    /**
     * Generic Bot API request helper.
     *
     * Returns parsed `result` from Telegram response on success, null on failure.
     */
    protected function apiRequest(string $method, array $params = []): mixed
    {
        if (!$this->isConfigured()) {
            Log::warning('SystemBot: Bot token not configured', ['method' => $method]);
            return null;
        }

        try {
            $response = Http::timeout(10)
                ->retry(2, 500)
                ->post(self::API_BASE_URL . $this->botToken . '/' . $method, $params);

            if (!$response->successful()) {
                Log::warning('SystemBot: API request failed', [
                    'method' => $method,
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
                return null;
            }

            $data = $response->json();
            if (!($data['ok'] ?? false)) {
                Log::warning('SystemBot: API returned not-ok', [
                    'method' => $method,
                    'description' => $data['description'] ?? 'unknown',
                ]);
                return null;
            }

            return $data['result'] ?? null;
        } catch (\Throwable $e) {
            Log::error('SystemBot: API request exception', [
                'method' => $method,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }
}
