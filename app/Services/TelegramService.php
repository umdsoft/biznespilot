<?php

namespace App\Services;

use App\Models\MarketingChannel;
use App\Models\TelegramMetric;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TelegramService
{
    /**
     * Telegram Bot API base URL
     */
    private const API_BASE_URL = 'https://api.telegram.org/bot';

    /**
     * Fetch and store Telegram metrics for a channel
     */
    public function syncMetrics(MarketingChannel $channel, ?Carbon $date = null): ?TelegramMetric
    {
        if ($channel->type !== 'telegram') {
            Log::error('Channel is not Telegram type', ['channel_id' => $channel->id]);

            return null;
        }

        if (! $channel->access_token) {
            Log::error('Telegram channel missing bot token', ['channel_id' => $channel->id]);

            return null;
        }

        $date = $date ?? Carbon::today();

        try {
            // Fetch channel/group info
            $chatInfo = $this->fetchChatInfo($channel->access_token, $channel->platform_account_id);

            // Fetch message statistics
            $messageStats = $this->fetchMessageStats($channel->access_token, $channel->platform_account_id, $date);

            // Fetch bot statistics (if applicable)
            $botStats = $this->fetchBotStats($channel->access_token, $date);

            // Get previous metric to calculate growth
            $previousMetric = TelegramMetric::where('marketing_channel_id', $channel->id)
                ->where('metric_date', '<', $date)
                ->orderBy('metric_date', 'desc')
                ->first();

            $previousMembersCount = $previousMetric->members_count ?? $chatInfo['members_count'];

            // Combine and store metrics
            $metric = TelegramMetric::updateOrCreate(
                [
                    'marketing_channel_id' => $channel->id,
                    'metric_date' => $date,
                ],
                [
                    'members_count' => $chatInfo['members_count'] ?? 0,
                    'new_members' => $messageStats['new_members'] ?? 0,
                    'left_members' => $messageStats['left_members'] ?? 0,
                    'posts_count' => $messageStats['posts_count'] ?? 0,
                    'total_views' => $messageStats['total_views'] ?? 0,
                    'average_views' => $messageStats['average_views'] ?? 0,
                    'reactions' => $messageStats['reactions'] ?? 0,
                    'comments' => $messageStats['comments'] ?? 0,
                    'forwards' => $messageStats['forwards'] ?? 0,
                    'shares' => $messageStats['shares'] ?? 0,
                    'bot_messages_sent' => $botStats['messages_sent'] ?? 0,
                    'bot_messages_received' => $botStats['messages_received'] ?? 0,
                    'bot_commands_used' => $botStats['commands_used'] ?? 0,
                    'bot_active_users' => $botStats['active_users'] ?? 0,
                    'link_clicks' => $messageStats['link_clicks'] ?? 0,
                ]
            );

            // Calculate engagement rate and growth rate
            $engagementRate = $metric->calculateEngagementRate();
            $growthRate = $metric->calculateGrowthRate();

            $metric->update([
                'engagement_rate' => $engagementRate,
                'growth_rate' => $growthRate,
            ]);

            Log::info('Telegram metrics synced successfully', [
                'channel_id' => $channel->id,
                'date' => $date->toDateString(),
            ]);

            return $metric;

        } catch (\Exception $e) {
            Log::error('Failed to sync Telegram metrics', [
                'channel_id' => $channel->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return null;
        }
    }

    /**
     * Fetch chat/channel information
     */
    private function fetchChatInfo(string $botToken, string $chatId): array
    {
        try {
            $response = Http::get(self::API_BASE_URL."{$botToken}/getChat", [
                'chat_id' => $chatId,
            ]);

            if (! $response->successful()) {
                Log::error('Telegram getChat request failed', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);

                return [];
            }

            $chatData = $response->json()['result'] ?? [];

            // Get member count
            $membersCount = 0;
            if (isset($chatData['type']) && in_array($chatData['type'], ['channel', 'supergroup'])) {
                $countResponse = Http::get(self::API_BASE_URL."{$botToken}/getChatMemberCount", [
                    'chat_id' => $chatId,
                ]);

                if ($countResponse->successful()) {
                    $membersCount = $countResponse->json()['result'] ?? 0;
                }
            }

            return [
                'members_count' => $membersCount,
                'title' => $chatData['title'] ?? '',
                'username' => $chatData['username'] ?? '',
                'type' => $chatData['type'] ?? '',
            ];

        } catch (\Exception $e) {
            Log::error('Failed to fetch Telegram chat info', [
                'error' => $e->getMessage(),
            ]);

            return [];
        }
    }

    /**
     * Fetch message statistics for a specific date
     */
    private function fetchMessageStats(string $botToken, string $chatId, Carbon $date): array
    {
        try {
            $stats = [
                'new_members' => 0,
                'left_members' => 0,
                'posts_count' => 0,
                'total_views' => 0,
                'average_views' => 0,
                'reactions' => 0,
                'comments' => 0,
                'forwards' => 0,
                'shares' => 0,
                'link_clicks' => 0,
            ];

            // Note: Telegram Bot API has limitations for fetching historical messages
            // This implementation uses a stored message tracking system
            // In production, you should implement a webhook to track messages in real-time

            // Get updates (this is limited and should be replaced with webhook in production)
            $response = Http::get(self::API_BASE_URL."{$botToken}/getUpdates", [
                'offset' => -100,
                'limit' => 100,
            ]);

            if (! $response->successful()) {
                return $stats;
            }

            $updates = $response->json()['result'] ?? [];

            foreach ($updates as $update) {
                if (! isset($update['message'])) {
                    continue;
                }

                $message = $update['message'];
                $messageDate = isset($message['date']) ? Carbon::createFromTimestamp($message['date']) : null;

                // Only process messages from the specified date
                if (! $messageDate || $messageDate->toDateString() !== $date->toDateString()) {
                    continue;
                }

                // Check for new members
                if (isset($message['new_chat_members'])) {
                    $stats['new_members'] += count($message['new_chat_members']);
                }

                // Check for left members
                if (isset($message['left_chat_member'])) {
                    $stats['left_members']++;
                }

                // Count posts from the channel
                if (isset($message['sender_chat']) && $message['sender_chat']['id'] == $chatId) {
                    $stats['posts_count']++;
                }

                // Count forwards
                if (isset($message['forward_date'])) {
                    $stats['forwards']++;
                }
            }

            // For view counts, we need to use getChannelPosts (if available via TDLib or custom implementation)
            // Standard Bot API doesn't provide view counts, so this would need to be implemented
            // via Telegram's TDLib or a custom tracking solution

            if ($stats['posts_count'] > 0) {
                $stats['average_views'] = (int) ($stats['total_views'] / $stats['posts_count']);
            }

            return $stats;

        } catch (\Exception $e) {
            Log::error('Failed to fetch Telegram message stats', [
                'error' => $e->getMessage(),
            ]);

            return [
                'new_members' => 0,
                'left_members' => 0,
                'posts_count' => 0,
                'total_views' => 0,
                'average_views' => 0,
                'reactions' => 0,
                'comments' => 0,
                'forwards' => 0,
                'shares' => 0,
                'link_clicks' => 0,
            ];
        }
    }

    /**
     * Fetch bot statistics
     */
    private function fetchBotStats(string $botToken, Carbon $date): array
    {
        try {
            // Bot statistics would typically come from a database where you track
            // incoming and outgoing messages, commands, etc.
            // This is a placeholder that should be connected to your bot message tracking system

            $stats = [
                'messages_sent' => 0,
                'messages_received' => 0,
                'commands_used' => 0,
                'active_users' => 0,
            ];

            // In production, query your bot_messages table or similar
            // Example:
            // $stats['messages_sent'] = BotMessage::where('date', $date)->where('direction', 'outgoing')->count();
            // $stats['messages_received'] = BotMessage::where('date', $date)->where('direction', 'incoming')->count();
            // $stats['commands_used'] = BotCommand::where('date', $date)->count();
            // $stats['active_users'] = BotMessage::where('date', $date)->distinct('user_id')->count();

            return $stats;

        } catch (\Exception $e) {
            Log::error('Failed to fetch Telegram bot stats', [
                'error' => $e->getMessage(),
            ]);

            return [
                'messages_sent' => 0,
                'messages_received' => 0,
                'commands_used' => 0,
                'active_users' => 0,
            ];
        }
    }

    /**
     * Get bot information
     */
    public function getBotInfo(string $botToken): ?array
    {
        try {
            $response = Http::get(self::API_BASE_URL."{$botToken}/getMe");

            if (! $response->successful()) {
                return null;
            }

            return $response->json()['result'] ?? null;

        } catch (\Exception $e) {
            Log::error('Failed to get Telegram bot info', [
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Validate bot token
     */
    public function validateBotToken(string $botToken): bool
    {
        $botInfo = $this->getBotInfo($botToken);

        return $botInfo !== null;
    }

    /**
     * Set webhook for real-time updates
     */
    public function setWebhook(string $botToken, string $webhookUrl): bool
    {
        try {
            $response = Http::post(self::API_BASE_URL."{$botToken}/setWebhook", [
                'url' => $webhookUrl,
                'allowed_updates' => [
                    'message',
                    'channel_post',
                    'chat_member',
                    'my_chat_member',
                ],
            ]);

            if (! $response->successful()) {
                Log::error('Failed to set Telegram webhook', [
                    'response' => $response->body(),
                ]);

                return false;
            }

            Log::info('Telegram webhook set successfully', [
                'url' => $webhookUrl,
            ]);

            return true;

        } catch (\Exception $e) {
            Log::error('Failed to set Telegram webhook', [
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Delete webhook
     */
    public function deleteWebhook(string $botToken): bool
    {
        try {
            $response = Http::post(self::API_BASE_URL."{$botToken}/deleteWebhook");

            return $response->successful();

        } catch (\Exception $e) {
            Log::error('Failed to delete Telegram webhook', [
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Sync metrics for date range
     *
     * @return int Number of days synced
     */
    public function syncMetricsRange(MarketingChannel $channel, Carbon $startDate, Carbon $endDate): int
    {
        $synced = 0;
        $currentDate = $startDate->copy();

        while ($currentDate->lte($endDate)) {
            $metric = $this->syncMetrics($channel, $currentDate);
            if ($metric) {
                $synced++;
            }
            $currentDate->addDay();
        }

        return $synced;
    }

    /**
     * Send message to channel/chat
     */
    public function sendMessage(string $botToken, string $chatId, string $text, array $options = []): bool
    {
        try {
            $params = array_merge([
                'chat_id' => $chatId,
                'text' => $text,
                'parse_mode' => 'HTML',
            ], $options);

            $response = Http::post(self::API_BASE_URL."{$botToken}/sendMessage", $params);

            if (! $response->successful()) {
                Log::error('Failed to send Telegram message', [
                    'response' => $response->body(),
                ]);

                return false;
            }

            return true;

        } catch (\Exception $e) {
            Log::error('Failed to send Telegram message', [
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }
}
