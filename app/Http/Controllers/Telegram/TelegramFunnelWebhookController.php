<?php

namespace App\Http\Controllers\Telegram;

use App\Http\Controllers\Controller;
use App\Models\TelegramBot;
use App\Models\TelegramUser;
use App\Models\TelegramDailyStat;
use App\Services\Telegram\FunnelEngineService;
use App\Services\Telegram\TelegramApiService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class TelegramFunnelWebhookController extends Controller
{
    /**
     * Handle incoming Telegram webhook for Funnel Builder bots
     */
    public function handle(Request $request, string $botId): JsonResponse
    {
        // Log at the very beginning - before any processing
        Log::info('=== WEBHOOK REQUEST RECEIVED ===', [
            'bot_id' => $botId,
            'method' => $request->method(),
            'ip' => $request->ip(),
            'url' => $request->fullUrl(),
            'headers' => $request->headers->all(),
            'body' => $request->all(),
        ]);

        try {
            // Find bot
            $bot = TelegramBot::where('id', $botId)
                ->where('is_active', true)
                ->first();

            if (!$bot) {
                Log::warning('Telegram funnel webhook: bot not found or inactive', [
                    'bot_id' => $botId,
                ]);
                return response()->json(['ok' => false, 'error' => 'Bot not found'], 404);
            }

            // Verify secret token
            if (!$this->verifySecretToken($request, $bot)) {
                Log::warning('Telegram funnel webhook: secret token verification failed', [
                    'bot_id' => $botId,
                    'ip' => $request->ip(),
                ]);
                return response()->json(['ok' => false, 'error' => 'Unauthorized'], 403);
            }

            // Get update data
            $update = $request->all();

            Log::info('Telegram funnel webhook received', [
                'bot_id' => $botId,
                'bot_username' => $bot->bot_username,
                'update_id' => $update['update_id'] ?? null,
                'has_message' => isset($update['message']),
                'has_callback' => isset($update['callback_query']),
                'message_text' => $update['message']['text'] ?? null,
            ]);

            // Process based on update type
            if (isset($update['message'])) {
                $this->processMessage($bot, $update['message']);
            } elseif (isset($update['callback_query'])) {
                $this->processCallbackQuery($bot, $update['callback_query']);
            } elseif (isset($update['my_chat_member'])) {
                $this->processChatMemberUpdate($bot, $update['my_chat_member']);
            }

            return response()->json(['ok' => true]);

        } catch (\Exception $e) {
            Log::error('Telegram funnel webhook error', [
                'bot_id' => $botId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            // Always return 200 to prevent Telegram from retrying
            return response()->json(['ok' => false]);
        }
    }

    /**
     * Process message update
     */
    protected function processMessage(TelegramBot $bot, array $message): void
    {
        $from = $message['from'] ?? null;

        Log::info('Webhook: Processing message', [
            'bot_id' => $bot->id,
            'from_id' => $from['id'] ?? null,
            'chat_id' => $message['chat']['id'] ?? null,
            'text' => $message['text'] ?? null,
        ]);

        if (!$from || ($from['is_bot'] ?? false)) {
            Log::info('Webhook: Skipping - no from or is_bot');
            return;
        }

        // Get or create user
        $user = $this->getOrCreateUser($bot, $from, $message['chat']['id']);

        Log::info('Webhook: User found/created', [
            'user_id' => $user->id,
            'telegram_id' => $user->telegram_id,
        ]);

        // Mark user as active
        $user->markActive();

        // Process through funnel engine
        $engine = new FunnelEngineService($bot, $user);
        $engine->processMessage($message);
    }

    /**
     * Process callback query update
     */
    protected function processCallbackQuery(TelegramBot $bot, array $callbackQuery): void
    {
        $from = $callbackQuery['from'] ?? null;

        if (!$from || ($from['is_bot'] ?? false)) {
            return;
        }

        $chatId = $callbackQuery['message']['chat']['id'] ?? null;

        if (!$chatId) {
            return;
        }

        // Get or create user
        $user = $this->getOrCreateUser($bot, $from, $chatId);

        // Mark user as active
        $user->markActive();

        // Process through funnel engine
        $engine = new FunnelEngineService($bot, $user);
        $engine->processCallback($callbackQuery);
    }

    /**
     * Process chat member status update (block/unblock)
     */
    protected function processChatMemberUpdate(TelegramBot $bot, array $update): void
    {
        $from = $update['from'] ?? null;
        $newStatus = $update['new_chat_member']['status'] ?? null;

        if (!$from || !$newStatus) {
            return;
        }

        $user = TelegramUser::where('telegram_bot_id', $bot->id)
            ->where('telegram_id', $from['id'])
            ->first();

        if (!$user) {
            return;
        }

        $stat = TelegramDailyStat::getOrCreateForToday($bot);

        // Check if user blocked or unblocked the bot
        if ($newStatus === 'kicked') {
            // User blocked the bot
            $user->markBlocked();
            $stat->incrementBlockedUsers();

            Log::info('User blocked bot', [
                'bot_id' => $bot->id,
                'user_id' => $user->id,
                'telegram_id' => $from['id'],
            ]);
        } elseif ($newStatus === 'member') {
            // User unblocked or started the bot
            if ($user->is_blocked) {
                $user->markUnblocked();
                $stat->incrementUnblockedUsers();

                Log::info('User unblocked bot', [
                    'bot_id' => $bot->id,
                    'user_id' => $user->id,
                    'telegram_id' => $from['id'],
                ]);
            }
        }
    }

    /**
     * Get or create Telegram user
     */
    protected function getOrCreateUser(TelegramBot $bot, array $from, int $chatId): TelegramUser
    {
        $user = TelegramUser::where('telegram_bot_id', $bot->id)
            ->where('telegram_id', $from['id'])
            ->first();

        if ($user) {
            // Update user info if changed
            $updateData = [];

            if (($from['first_name'] ?? null) !== $user->first_name) {
                $updateData['first_name'] = $from['first_name'] ?? null;
            }
            if (($from['last_name'] ?? null) !== $user->last_name) {
                $updateData['last_name'] = $from['last_name'] ?? null;
            }
            if (($from['username'] ?? null) !== $user->username) {
                $updateData['username'] = $from['username'] ?? null;
            }
            if (($from['language_code'] ?? null) !== $user->language_code) {
                $updateData['language_code'] = $from['language_code'] ?? null;
            }

            if (!empty($updateData)) {
                $user->update($updateData);
            }

            return $user;
        }

        // Create new user
        $user = TelegramUser::create([
            'business_id' => $bot->business_id,
            'telegram_bot_id' => $bot->id,
            'telegram_id' => $from['id'],
            'username' => $from['username'] ?? null,
            'first_name' => $from['first_name'] ?? null,
            'last_name' => $from['last_name'] ?? null,
            'language_code' => $from['language_code'] ?? null,
            'is_bot' => $from['is_bot'] ?? false,
        ]);

        // Increment new users stat
        $stat = TelegramDailyStat::getOrCreateForToday($bot);
        $stat->incrementNewUsers();

        Log::info('New Telegram user created', [
            'bot_id' => $bot->id,
            'user_id' => $user->id,
            'telegram_id' => $from['id'],
        ]);

        return $user;
    }

    /**
     * Verify webhook secret token
     */
    protected function verifySecretToken(Request $request, TelegramBot $bot): bool
    {
        $secretToken = $request->header('X-Telegram-Bot-Api-Secret-Token');

        // If no secret token in request
        if (!$secretToken) {
            // Allow if bot doesn't have secret configured (backwards compatibility)
            if (!$bot->webhook_secret) {
                return true;
            }
            return false;
        }

        // Verify against bot's webhook secret
        if (!$bot->webhook_secret) {
            return false;
        }

        return hash_equals($bot->webhook_secret, $secretToken);
    }

    /**
     * Setup webhook for a bot
     */
    public function setup(Request $request, string $botId): JsonResponse
    {
        try {
            $bot = TelegramBot::findOrFail($botId);

            // Generate webhook URL
            $webhookUrl = route('telegram.funnel.webhook', ['botId' => $bot->id]);

            // Generate secret token if not exists
            if (!$bot->webhook_secret) {
                $bot->webhook_secret = bin2hex(random_bytes(32));
                $bot->save();
            }

            // Set webhook via Telegram API
            $api = new TelegramApiService($bot);
            $result = $api->setWebhook($webhookUrl, $bot->webhook_secret);

            if ($result['success']) {
                $bot->update([
                    'webhook_url' => $webhookUrl,
                    'is_verified' => true,
                    'verified_at' => now(),
                ]);

                return response()->json([
                    'success' => true,
                    'webhook_url' => $webhookUrl,
                    'message' => 'Webhook muvaffaqiyatli o\'rnatildi',
                ]);
            }

            return response()->json([
                'success' => false,
                'error' => $result['description'] ?? 'Webhook o\'rnatishda xatolik',
            ], 400);

        } catch (\Exception $e) {
            Log::error('Webhook setup error', [
                'bot_id' => $botId,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Delete webhook for a bot
     */
    public function delete(Request $request, string $botId): JsonResponse
    {
        try {
            $bot = TelegramBot::findOrFail($botId);

            $api = new TelegramApiService($bot);
            $result = $api->deleteWebhook();

            if ($result['success']) {
                $bot->update([
                    'webhook_url' => null,
                ]);

                return response()->json([
                    'success' => true,
                    'message' => 'Webhook o\'chirildi',
                ]);
            }

            return response()->json([
                'success' => false,
                'error' => $result['description'] ?? 'Webhook o\'chirishda xatolik',
            ], 400);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get webhook info
     */
    public function info(Request $request, string $botId): JsonResponse
    {
        try {
            $bot = TelegramBot::findOrFail($botId);

            $api = new TelegramApiService($bot);
            $result = $api->getWebhookInfo();

            if ($result['success']) {
                return response()->json([
                    'success' => true,
                    'info' => $result['result'],
                ]);
            }

            return response()->json([
                'success' => false,
                'error' => $result['description'] ?? 'Ma\'lumot olishda xatolik',
            ], 400);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
