<?php

namespace App\Services\Telegram;

use App\Models\TelegramBot;
use App\Models\TelegramBusinessConnection;
use App\Models\TelegramConversation;
use App\Models\TelegramMessage;
use App\Models\TelegramUser;
use App\Services\AI\AIService;
use Illuminate\Support\Facades\Log;

/**
 * Handles `business_message`, `edited_business_message` and `deleted_business_messages`
 * webhook updates. These arrive when a customer writes to the business owner's
 * personal Telegram chat (NOT to the bot). The bot then replies on the owner's behalf.
 *
 * Flow:
 *   customer -> owner's DM -> Telegram -> our webhook (business_message)
 *   -> log message -> generate AI reply (owner's persona)
 *   -> send reply via bot API with business_connection_id
 *   -> customer sees reply from "owner" (not bot)
 */
class BusinessChatHandlerService
{
    public function __construct(
        protected AIService $ai,
    ) {}

    /**
     * Handle incoming business_message update (customer wrote to owner).
     */
    public function handleIncomingMessage(TelegramBot $bot, array $message): void
    {
        $connectionId = $message['business_connection_id'] ?? null;
        if (! $connectionId) {
            return;
        }

        $connection = TelegramBusinessConnection::where('connection_id', $connectionId)->first();
        if (! $connection) {
            // Auto-create connection on first message if business_connection event was missed
            Log::info('Business message received for unknown connection — fetching from Telegram', [
                'connection_id' => $connectionId,
                'bot_id' => $bot->id,
            ]);

            $api = new TelegramApiService($bot);
            $info = $api->getBusinessConnection($connectionId);

            if (! ($info['success'] ?? false)) {
                Log::warning('Failed to fetch business connection from Telegram', [
                    'connection_id' => $connectionId,
                    'error' => $info['description'] ?? 'unknown',
                ]);

                return;
            }

            $bc = $info['result'];
            $rights = $bc['rights'] ?? null;
            $user = $bc['user'] ?? [];

            $connection = TelegramBusinessConnection::create([
                'business_id' => $bot->business_id,
                'telegram_bot_id' => $bot->id,
                'connection_id' => $connectionId,
                'telegram_user_id' => $user['id'] ?? 0,
                'owner_first_name' => $user['first_name'] ?? null,
                'owner_last_name' => $user['last_name'] ?? null,
                'owner_username' => $user['username'] ?? null,
                'user_chat_id' => $bc['user_chat_id'] ?? null,
                'can_reply' => (bool) ($rights['can_reply'] ?? $bc['can_reply'] ?? true),
                'rights' => $rights,
                'is_enabled' => (bool) ($bc['is_enabled'] ?? true),
                'connected_at' => now(),
                'last_activity_at' => now(),
            ]);

            Log::info('Business connection auto-created from first message', [
                'connection_id' => $connectionId,
                'owner' => $connection->owner_username,
            ]);
        }

        $fromUser = $message['from'] ?? [];
        $chatId = $message['chat']['id'] ?? null;

        // Do not respond to the owner's own messages (loop prevention)
        if (($fromUser['id'] ?? null) === $connection->telegram_user_id) {
            return;
        }

        if (! $chatId) {
            return;
        }

        // Upsert the customer as a TelegramUser (reuse existing schema)
        $customer = $this->getOrCreateCustomer($bot, $connection, $fromUser, $chatId);

        // Get or create conversation in BUSINESS mode
        $conversation = $this->getOrCreateConversation($bot, $connection, $customer);

        // Log incoming message
        $telegramMessage = $this->logIncomingMessage($conversation, $connection, $message);

        $connection->update(['last_activity_at' => now()]);

        // Generate AI reply if enabled
        if ($connection->shouldAIReply() && $connection->isWithinWorkingHours()) {
            $this->generateAndSendAIReply($bot, $connection, $conversation, $customer, $message);
        } elseif (! $connection->isWithinWorkingHours()) {
            $this->sendAwayMessage($bot, $connection, $chatId);
        }
    }

    /**
     * Handle edited_business_message — update our message log.
     */
    public function handleEditedMessage(TelegramBot $bot, array $message): void
    {
        $messageId = $message['message_id'] ?? null;
        $chatId = $message['chat']['id'] ?? null;

        if (! $messageId || ! $chatId) {
            return;
        }

        TelegramMessage::where('telegram_message_id', $messageId)
            ->where('telegram_chat_id', $chatId)
            ->update([
                'content' => json_encode(['text' => $message['text'] ?? null, 'edited' => true]),
            ]);
    }

    /**
     * Handle deleted_business_messages — mark messages as deleted.
     */
    public function handleDeletedMessages(TelegramBot $bot, array $payload): void
    {
        $chat = $payload['chat']['id'] ?? null;
        $ids = $payload['message_ids'] ?? [];

        if (! $chat || empty($ids)) {
            return;
        }

        TelegramMessage::whereIn('telegram_message_id', $ids)
            ->where('telegram_chat_id', $chat)
            ->delete();
    }

    // ==================== Helpers ====================

    protected function getOrCreateCustomer(
        TelegramBot $bot,
        TelegramBusinessConnection $connection,
        array $fromUser,
        int $chatId,
    ): TelegramUser {
        return TelegramUser::updateOrCreate(
            [
                'telegram_bot_id' => $bot->id,
                'telegram_id' => $fromUser['id'] ?? $chatId,
            ],
            [
                'business_id' => $bot->business_id,
                'chat_id' => $chatId,
                'first_name' => $fromUser['first_name'] ?? null,
                'last_name' => $fromUser['last_name'] ?? null,
                'username' => $fromUser['username'] ?? null,
                'language_code' => $fromUser['language_code'] ?? 'uz',
                'last_interaction_at' => now(),
            ]
        );
    }

    protected function getOrCreateConversation(
        TelegramBot $bot,
        TelegramBusinessConnection $connection,
        TelegramUser $customer,
    ): TelegramConversation {
        return TelegramConversation::firstOrCreate(
            [
                'telegram_user_id' => $customer->id,
                'telegram_bot_id' => $bot->id,
                'business_connection_id' => $connection->connection_id,
            ],
            [
                'business_id' => $bot->business_id,
                'mode' => TelegramConversation::MODE_BUSINESS,
                'status' => 'active',
                'started_at' => now(),
                'last_message_at' => now(),
            ]
        );
    }

    protected function logIncomingMessage(
        TelegramConversation $conversation,
        TelegramBusinessConnection $connection,
        array $message,
    ): TelegramMessage {
        $conversation->update(['last_message_at' => now()]);

        return TelegramMessage::create([
            'conversation_id' => $conversation->id,
            'telegram_message_id' => $message['message_id'] ?? null,
            'telegram_chat_id' => $message['chat']['id'] ?? null,
            'business_connection_id' => $connection->connection_id,
            'direction' => 'incoming',
            'sender_type' => TelegramMessage::SENDER_USER,
            'content_type' => $this->detectContentType($message),
            'content' => ['text' => $message['text'] ?? null, 'raw' => $message],
        ]);
    }

    protected function detectContentType(array $message): string
    {
        if (isset($message['text'])) {
            return 'text';
        }
        if (isset($message['photo'])) {
            return 'photo';
        }
        if (isset($message['voice'])) {
            return 'voice';
        }
        if (isset($message['video'])) {
            return 'video';
        }
        if (isset($message['document'])) {
            return 'document';
        }
        if (isset($message['location'])) {
            return 'location';
        }
        if (isset($message['contact'])) {
            return 'contact';
        }

        return 'other';
    }

    protected function generateAndSendAIReply(
        TelegramBot $bot,
        TelegramBusinessConnection $connection,
        TelegramConversation $conversation,
        TelegramUser $customer,
        array $message,
    ): void {
        $userText = $message['text'] ?? null;
        if (! $userText) {
            return; // Non-text for now
        }

        try {
            $api = new TelegramApiService($bot);

            // Show typing indicator (sent via business connection)
            $api->forBusinessConnection($connection->connection_id)
                ->sendChatAction($message['chat']['id'], 'typing');

            // Build system prompt with persona
            $systemPrompt = $this->buildSystemPrompt($connection, $bot->business);

            // Fetch recent conversation history for context
            $history = $conversation->messages()
                ->orderBy('created_at', 'desc')
                ->limit(10)
                ->get()
                ->reverse()
                ->map(fn ($m) => [
                    'role' => $m->sender_type === TelegramMessage::SENDER_USER ? 'user' : 'assistant',
                    'content' => $m->content['text'] ?? '',
                ])
                ->filter(fn ($m) => ! empty($m['content']))
                ->values()
                ->toArray();

            // Append current user message
            $messages = array_merge($history, [['role' => 'user', 'content' => $userText]]);

            // Generate AI response via existing AIService (channel-agnostic)
            $response = $this->ai->chat(
                messages: $messages,
                systemPrompt: $systemPrompt,
                preferredModel: 'haiku',
                maxTokens: 400,
                businessId: $bot->business_id,
                agentType: 'business_chat',
            );

            if (! $response->success || empty($response->content)) {
                Log::warning('AI returned empty response for business chat', [
                    'connection_id' => $connection->connection_id,
                ]);

                return;
            }

            $reply = $response->content;

            // Auto-send only in AUTO mode; HYBRID waits for owner confirmation
            if ($connection->ai_mode === TelegramBusinessConnection::AI_MODE_AUTO) {
                $sendResult = $api->forBusinessConnection($connection->connection_id)
                    ->sendMessage($message['chat']['id'], $reply);

                if ($sendResult['success'] ?? false) {
                    $this->logOutgoingMessage($conversation, $connection, $reply, $sendResult['result'] ?? []);
                }
            } else {
                // HYBRID/MANUAL — notify owner with suggested reply
                $this->notifyOwnerWithSuggestion($bot, $connection, $customer, $userText, $reply);
            }
        } catch (\Throwable $e) {
            Log::error('Business chat AI reply failed', [
                'error' => $e->getMessage(),
                'connection_id' => $connection->connection_id,
            ]);
        }
    }

    protected function buildSystemPrompt(TelegramBusinessConnection $connection, $business): string
    {
        $businessName = $business->name ?? 'biznes';
        $industry = $business->industry ?? '';
        $ownerName = $connection->owner_first_name ?? 'egasiz';

        $persona = $connection->persona_prompt ?? "Sen {$ownerName}, {$businessName} egasisan. "
            ."Mijozlar bilan do'stona va professional muloqot qilasan. "
            .'Qisqa va aniq javob berasan. O\'zbek tilida gaplashasan.';

        $context = "Biznes: {$businessName}".($industry ? " ({$industry})" : '');

        return "{$persona}\n\n{$context}\n\n"
            ."MUHIM QOIDALAR:\n"
            ."- Hech qachon \"Men AI/botman\" dema\n"
            ."- Sen aslida biznes egasi — shunday javob ber\n"
            ."- Narx va aniq savollar uchun \"Bir daqiqa, tekshirib aytaman\" de\n"
            .'- Maksimum 2-3 jumla';
    }

    protected function logOutgoingMessage(
        TelegramConversation $conversation,
        TelegramBusinessConnection $connection,
        string $text,
        array $result,
    ): void {
        TelegramMessage::create([
            'conversation_id' => $conversation->id,
            'telegram_message_id' => $result['message_id'] ?? null,
            'telegram_chat_id' => $result['chat']['id'] ?? null,
            'business_connection_id' => $connection->connection_id,
            'direction' => 'outgoing',
            'sender_type' => TelegramMessage::SENDER_BUSINESS_OWNER,
            'content_type' => 'text',
            'content' => ['text' => $text],
        ]);

        $conversation->update(['last_message_at' => now()]);
    }

    protected function sendAwayMessage(TelegramBot $bot, TelegramBusinessConnection $connection, int $chatId): void
    {
        $awayMsg = $connection->getSetting('away_message');
        if (! $awayMsg) {
            return;
        }

        $api = new TelegramApiService($bot);
        $api->forBusinessConnection($connection->connection_id)
            ->sendMessage($chatId, $awayMsg);
    }

    protected function notifyOwnerWithSuggestion(
        TelegramBot $bot,
        TelegramBusinessConnection $connection,
        TelegramUser $customer,
        string $customerMsg,
        string $aiSuggestion,
    ): void {
        if (! $connection->user_chat_id) {
            return;
        }

        $customerName = trim(($customer->first_name ?? '').' '.($customer->last_name ?? '')) ?: 'Mijoz';

        $text = "💬 *Yangi mijoz xabari*\n\n"
            ."👤 {$customerName} ".($customer->username ? "(@{$customer->username})" : '')."\n"
            ."📨 _{$customerMsg}_\n\n"
            ."🤖 *AI tavsiya etadi:*\n{$aiSuggestion}";

        $api = new TelegramApiService($bot);
        $api->sendMessage($connection->user_chat_id, $text, ['parse_mode' => 'Markdown']);
    }
}
