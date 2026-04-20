<?php

namespace App\Services\Telegram;

use App\Models\TelegramBot;
use App\Models\TelegramBusinessConnection;
use Illuminate\Support\Facades\Log;

/**
 * Handles `business_connection` webhook updates — when a Telegram Premium user
 * connects or disconnects our bot to their business account.
 *
 * Payload shape (Bot API 7.2+):
 * {
 *   "id": "<connection_id>",
 *   "user": { "id": 123, "first_name": "...", "username": "..." },
 *   "user_chat_id": 123,
 *   "date": 1234567890,
 *   "can_reply": true,          // (pre-API 9.0)
 *   "rights": { "can_reply": true, ... }, // (API 9.0+)
 *   "is_enabled": true
 * }
 */
class BusinessConnectionService
{
    public function handleConnectionUpdate(TelegramBot $bot, array $payload): void
    {
        $connectionId = $payload['id'] ?? null;
        if (! $connectionId) {
            Log::warning('BusinessConnection update without id', ['bot_id' => $bot->id]);

            return;
        }

        $isEnabled = (bool) ($payload['is_enabled'] ?? false);
        $user = $payload['user'] ?? [];
        $rights = $payload['rights'] ?? null;
        $canReply = $rights['can_reply'] ?? $payload['can_reply'] ?? false;

        $connection = TelegramBusinessConnection::updateOrCreate(
            ['connection_id' => $connectionId],
            [
                'business_id' => $bot->business_id,
                'telegram_bot_id' => $bot->id,
                'telegram_user_id' => $user['id'] ?? 0,
                'owner_first_name' => $user['first_name'] ?? null,
                'owner_last_name' => $user['last_name'] ?? null,
                'owner_username' => $user['username'] ?? null,
                'user_chat_id' => $payload['user_chat_id'] ?? null,
                'can_reply' => (bool) $canReply,
                'rights' => $rights,
                'is_enabled' => $isEnabled,
                'connected_at' => now(),
                'disconnected_at' => $isEnabled ? null : now(),
                'last_activity_at' => now(),
            ]
        );

        Log::info('Business connection '.($isEnabled ? 'established' : 'revoked'), [
            'connection_id' => $connectionId,
            'bot_id' => $bot->id,
            'business_id' => $bot->business_id,
            'can_reply' => $canReply,
        ]);

        // Send greeting DM to the owner on first connection
        if ($isEnabled && $connection->wasRecentlyCreated && $connection->user_chat_id) {
            $this->sendOwnerWelcomeMessage($bot, $connection);
        }
    }

    protected function sendOwnerWelcomeMessage(TelegramBot $bot, TelegramBusinessConnection $connection): void
    {
        try {
            $api = new TelegramApiService($bot);
            $api->sendMessage(
                $connection->user_chat_id,
                "✅ *BiznesPilot AI* biznes akkauntingizga ulandi!\n\n"
                ."Endi mijozlaringizga AI javob bera oladi sizning nomingizdan.\n\n"
                ."🤖 Sozlamalar: biznespilot.uz/business/telegram-funnels\n"
                .'💡 AI rejimi: *Aralash* (siz tasdiqlagandan keyin yuboradi)',
                ['parse_mode' => 'Markdown']
            );
        } catch (\Throwable $e) {
            Log::warning('Failed to send owner welcome message', [
                'error' => $e->getMessage(),
                'connection_id' => $connection->connection_id,
            ]);
        }
    }
}
