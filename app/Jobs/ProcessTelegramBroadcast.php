<?php

namespace App\Jobs;

use App\Models\TelegramBroadcast;
use App\Models\TelegramBot;
use App\Models\TelegramUser;
use App\Services\Telegram\TelegramApiService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessTelegramBroadcast implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $backoff = 60;
    public int $timeout = 3600; // 1 hour max

    protected string $broadcastId;

    public function __construct(string $broadcastId)
    {
        $this->broadcastId = $broadcastId;
    }

    public function handle(): void
    {
        $broadcast = TelegramBroadcast::find($this->broadcastId);

        if (!$broadcast) {
            Log::warning('ProcessTelegramBroadcast: Broadcast not found', [
                'broadcast_id' => $this->broadcastId,
            ]);
            return;
        }

        // Check if broadcast is still in sending state
        if (!in_array($broadcast->status, ['sending'])) {
            Log::info('ProcessTelegramBroadcast: Broadcast not in sending state', [
                'broadcast_id' => $this->broadcastId,
                'status' => $broadcast->status,
            ]);
            return;
        }

        $bot = TelegramBot::find($broadcast->telegram_bot_id);
        if (!$bot) {
            Log::error('ProcessTelegramBroadcast: Bot not found', [
                'broadcast_id' => $this->broadcastId,
                'bot_id' => $broadcast->telegram_bot_id,
            ]);
            $broadcast->update(['status' => 'failed']);
            return;
        }

        $api = new TelegramApiService($bot);

        Log::info('ProcessTelegramBroadcast: Starting', [
            'broadcast_id' => $broadcast->id,
            'broadcast_name' => $broadcast->name,
            'total_recipients' => $broadcast->total_recipients,
        ]);

        // Get recipients query
        $recipientsQuery = TelegramUser::where('telegram_bot_id', $bot->id)
            ->where('is_blocked', false);

        // Apply filters
        $filter = $broadcast->target_filter ?? [];

        if (!empty($filter['tags'])) {
            $recipientsQuery->where(function ($q) use ($filter) {
                foreach ($filter['tags'] as $tag) {
                    $q->orWhereJsonContains('tags', $tag);
                }
            });
        }

        if (!empty($filter['active_after'])) {
            $recipientsQuery->where('last_active_at', '>=', $filter['active_after']);
        }

        // Process in chunks to handle large audiences
        $processedCount = $broadcast->sent_count;

        $recipientsQuery->orderBy('id')
            ->skip($processedCount)
            ->chunk(100, function ($users) use ($broadcast, $api, &$processedCount) {
                foreach ($users as $user) {
                    // Refresh broadcast to check if paused/cancelled
                    $broadcast->refresh();

                    if ($broadcast->status === 'paused') {
                        Log::info('ProcessTelegramBroadcast: Paused', [
                            'broadcast_id' => $broadcast->id,
                            'processed' => $processedCount,
                        ]);
                        return false; // Stop processing
                    }

                    if ($broadcast->status === 'cancelled') {
                        Log::info('ProcessTelegramBroadcast: Cancelled', [
                            'broadcast_id' => $broadcast->id,
                            'processed' => $processedCount,
                        ]);
                        return false; // Stop processing
                    }

                    // Send message to user
                    $result = $this->sendBroadcastMessage($api, $user, $broadcast);

                    // Update counters
                    $broadcast->incrementSent();
                    $processedCount++;

                    if ($result['success']) {
                        $broadcast->incrementDelivered();
                    } else {
                        // Check if user blocked the bot
                        if ($this->isBlockedError($result)) {
                            $broadcast->incrementBlocked();
                            $user->markBlocked();
                        } else {
                            $broadcast->incrementFailed();
                        }
                    }

                    // Rate limiting: Telegram allows ~30 messages/second
                    // Using 50ms delay = max 20 messages/second (safe margin)
                    usleep(50000);
                }

                return true; // Continue processing
            });

        // Check final status
        $broadcast->refresh();

        if ($broadcast->status === 'sending') {
            // All messages sent, mark as completed
            $broadcast->complete();

            Log::info('ProcessTelegramBroadcast: Completed', [
                'broadcast_id' => $broadcast->id,
                'sent' => $broadcast->sent_count,
                'delivered' => $broadcast->delivered_count,
                'failed' => $broadcast->failed_count,
                'blocked' => $broadcast->blocked_count,
            ]);
        }
    }

    /**
     * Send broadcast message to a user
     */
    protected function sendBroadcastMessage(
        TelegramApiService $api,
        TelegramUser $user,
        TelegramBroadcast $broadcast
    ): array {
        $content = $broadcast->content;
        $keyboard = $broadcast->keyboard;
        $chatId = $user->telegram_id;

        $type = $content['type'] ?? 'text';
        $text = $content['text'] ?? '';
        $caption = $content['caption'] ?? $text;
        $fileId = $content['file_id'] ?? null;

        try {
            switch ($type) {
                case 'photo':
                    return $api->sendPhoto($chatId, $fileId, $caption, $keyboard);

                case 'video':
                    return $api->sendVideo($chatId, $fileId, $caption, $keyboard);

                case 'document':
                    return $api->sendDocument($chatId, $fileId, $caption, $keyboard);

                case 'text':
                default:
                    return $api->sendMessage($chatId, $text, $keyboard);
            }
        } catch (\Exception $e) {
            Log::error('ProcessTelegramBroadcast: Send error', [
                'broadcast_id' => $broadcast->id,
                'user_id' => $user->id,
                'telegram_id' => $chatId,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error_code' => 0,
                'description' => $e->getMessage(),
            ];
        }
    }

    /**
     * Check if the error indicates user blocked the bot
     */
    protected function isBlockedError(array $result): bool
    {
        $errorCode = $result['error_code'] ?? 0;
        $description = $result['description'] ?? '';

        // Error code 403 = Forbidden (user blocked the bot or chat not found)
        if ($errorCode === 403) {
            return true;
        }

        // Also check common descriptions
        $blockedDescriptions = [
            'Forbidden',
            'bot was blocked',
            'user is deactivated',
            'chat not found',
        ];

        foreach ($blockedDescriptions as $blocked) {
            if (stripos($description, $blocked) !== false) {
                return true;
            }
        }

        return false;
    }

    /**
     * Handle job failure
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('ProcessTelegramBroadcast: Job failed', [
            'broadcast_id' => $this->broadcastId,
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString(),
        ]);

        $broadcast = TelegramBroadcast::find($this->broadcastId);
        if ($broadcast && $broadcast->status === 'sending') {
            $broadcast->update(['status' => 'failed']);
        }
    }
}
