<?php

declare(strict_types=1);

namespace App\Services\Telegram;

use App\Models\TelegramChannel;
use App\Models\TelegramChannelDailyStat;
use App\Models\TelegramChannelPost;
use App\Models\TelegramChannelPostSnapshot;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * TelegramChannelAnalyticsService
 *
 * Core business logic for tracking Telegram channel statistics through the
 * BiznesPilot System Bot. Handles:
 *   - Bot promotion/demotion lifecycle (my_chat_member events)
 *   - Channel post recording (channel_post events)
 *   - Periodic stats sync (views/reactions refresh)
 *   - Daily rollup snapshot
 *   - Digest message generation
 */
class TelegramChannelAnalyticsService
{
    public function __construct(
        protected SystemBotService $bot,
    ) {}

    // =================================================================
    // LIFECYCLE: Bot promoted / demoted in a channel
    // =================================================================

    /**
     * Handle my_chat_member update when System Bot is added as admin
     * to a channel (or role changes within a channel).
     *
     * The user who promoted the bot is in `from`. We link the channel
     * to that user's current business if the user is registered in BiznesPilot.
     */
    public function onMyChatMemberUpdate(array $update): ?TelegramChannel
    {
        $chat = $update['chat'] ?? [];
        $newMember = $update['new_chat_member'] ?? [];
        $oldMember = $update['old_chat_member'] ?? [];
        $from = $update['from'] ?? [];

        $chatId = $chat['id'] ?? null;
        $chatType = $chat['type'] ?? null;

        if (!$chatId || !in_array($chatType, ['channel', 'supergroup'], true)) {
            return null;
        }

        $newStatus = $newMember['status'] ?? 'left';
        $oldStatus = $oldMember['status'] ?? 'left';

        Log::info('TelegramChannelAnalytics: my_chat_member update', [
            'chat_id' => $chatId,
            'chat_type' => $chatType,
            'old_status' => $oldStatus,
            'new_status' => $newStatus,
            'promoted_by' => $from['id'] ?? null,
        ]);

        // Bot was removed/demoted
        if (in_array($newStatus, ['left', 'kicked', 'member', 'restricted'], true)) {
            return $this->handleBotDemoted((int) $chatId, $newStatus);
        }

        // Bot became administrator
        if ($newStatus === 'administrator') {
            return $this->handleBotPromoted(
                chatId: (int) $chatId,
                chatType: (string) $chatType,
                promotedBy: $from,
                adminRights: $this->extractAdminRights($newMember),
            );
        }

        return null;
    }

    /**
     * Register a new tracked channel when bot is promoted to admin.
     */
    protected function handleBotPromoted(
        int $chatId,
        string $chatType,
        array $promotedBy,
        array $adminRights,
    ): ?TelegramChannel {
        // Find BiznesPilot user by their Telegram ID
        $promoterChatId = $promotedBy['id'] ?? null;
        if (!$promoterChatId) {
            Log::warning('TelegramChannelAnalytics: bot promoted but no from.id');
            return null;
        }

        $user = User::where('telegram_chat_id', (string) $promoterChatId)->first();

        if (!$user) {
            // Promoter is not a linked BiznesPilot user → send guidance if possible
            $this->bot->sendMessage(
                (string) $promoterChatId,
                "⚠️ <b>Siz BiznesPilot foydalanuvchisi sifatida topilmadingiz</b>\n\n"
                . "Avval biznespilot.uz da ro'yxatdan o'ting va Telegram hisobingizni ulang,\n"
                . "keyin botni kanalingizga qaytadan admin qilib qo'shing."
            );
            // Bot chiqib ketadi — biz kanalni kuzatmaymiz
            $this->bot->leaveChat($chatId);
            return null;
        }

        $business = $user->currentBusiness ?? $user->businesses()->first();
        if (!$business) {
            $this->bot->sendToUser(
                $user,
                "⚠️ Sizda faol biznes yo'q. Kanal kuzatish uchun avval biznes yarating."
            );
            $this->bot->leaveChat($chatId);
            return null;
        }

        $existing = TelegramChannel::where('telegram_chat_id', $chatId)->first();

        // Tenant isolation guard: a channel already owned by another business
        // cannot be silently reassigned. The bot must leave to prevent "stealing".
        if ($existing && $existing->business_id !== $business->id) {
            Log::warning('TelegramChannelAnalytics: tenant isolation violation — channel belongs to different business', [
                'chat_id' => $chatId,
                'existing_business_id' => $existing->business_id,
                'promoter_business_id' => $business->id,
                'promoter_user_id' => $user->id,
            ]);

            $this->bot->sendToUser(
                $user,
                "⚠️ <b>Bu kanal allaqachon boshqa BiznesPilot hisobida ro'yxatga olingan.</b>\n\n"
                . "Agar bu xato bo'lsa, avvalgi hisobdan kanalni uzdiring, keyin qayta ulang."
            );

            $this->bot->leaveChat($chatId);
            return null;
        }

        // Pull fresh chat info
        $chatInfo = $this->bot->getChat($chatId) ?? [];
        $memberCount = $this->bot->getChatMemberCount($chatId) ?? 0;
        $admins = $this->bot->getChatAdministrators($chatId) ?? [];

        $payload = [
            'business_id' => $business->id,
            'connected_by_user_id' => $user->id,
            'telegram_chat_id' => $chatId,
            'chat_username' => $chatInfo['username'] ?? null,
            'title' => $chatInfo['title'] ?? ('Channel #'.$chatId),
            'description' => $chatInfo['description'] ?? null,
            'invite_link' => $chatInfo['invite_link'] ?? null,
            'type' => $chatType,
            'subscriber_count' => $memberCount,
            'admin_count' => count($admins),
            'admin_status' => TelegramChannel::STATUS_ADMIN,
            'admin_rights' => $adminRights,
            'is_active' => true,
            'last_synced_at' => now(),
        ];

        if ($existing) {
            $existing->update(array_merge($payload, [
                'connected_at' => $existing->connected_at ?? now(),
                'disconnected_at' => null,
            ]));
            $channel = $existing;
        } else {
            $channel = TelegramChannel::create(array_merge($payload, [
                'connected_at' => now(),
            ]));
        }

        // Confirm to the user via DM on System Bot
        $this->sendChannelConnectedNotification($user, $channel);

        return $channel;
    }

    /**
     * Mark a channel as no longer tracked (bot demoted/removed).
     */
    protected function handleBotDemoted(int $chatId, string $newStatus): ?TelegramChannel
    {
        $channel = TelegramChannel::where('telegram_chat_id', $chatId)->first();

        if (!$channel) {
            return null;
        }

        $channel->markDisconnected($newStatus);

        // Notify the connected user
        if ($channel->connectedByUser) {
            $this->bot->sendToUser(
                $channel->connectedByUser,
                "⚠️ <b>Kanal kuzatuvi to'xtadi</b>\n\n"
                . "📢 «{$channel->title}»\n\n"
                . "Men endi bu kanalda admin emasman. Statistik ma'lumotlar "
                . "yig'ilmaydi.\n\n"
                . "Qayta yoqish uchun: kanalga botni qaytadan admin qiling."
            );
        }

        return $channel;
    }

    protected function extractAdminRights(array $memberObject): array
    {
        // Telegram ChatMemberAdministrator object — capture boolean rights
        $rightFields = [
            'can_manage_chat', 'can_delete_messages', 'can_manage_video_chats',
            'can_restrict_members', 'can_promote_members', 'can_change_info',
            'can_invite_users', 'can_post_messages', 'can_edit_messages',
            'can_pin_messages', 'can_post_stories', 'can_edit_stories',
            'can_delete_stories', 'can_manage_topics',
        ];

        $rights = [];
        foreach ($rightFields as $field) {
            if (array_key_exists($field, $memberObject)) {
                $rights[$field] = (bool) $memberObject[$field];
            }
        }
        return $rights;
    }

    // =================================================================
    // CHANNEL POSTS: channel_post webhook event
    // =================================================================

    /**
     * Record a new channel post into our database.
     */
    public function recordChannelPost(array $message): ?TelegramChannelPost
    {
        $chat = $message['chat'] ?? [];
        $chatId = $chat['id'] ?? null;
        $messageId = $message['message_id'] ?? null;

        if (!$chatId || !$messageId) {
            return null;
        }

        $channel = TelegramChannel::where('telegram_chat_id', $chatId)->first();
        if (!$channel || !$channel->isTracked()) {
            return null;
        }

        $post = TelegramChannelPost::firstOrNew([
            'telegram_channel_id' => $channel->id,
            'message_id' => $messageId,
        ]);

        if (!$post->exists) {
            $post->fill([
                'posted_at' => isset($message['date'])
                    ? Carbon::createFromTimestamp((int) $message['date'])
                    : now(),
                'content_type' => $this->detectContentType($message),
                'media_count' => $this->countMedia($message),
                'text_preview' => $this->extractTextPreview($message),
                'views' => $message['views'] ?? 0,
                'reactions_count' => 0,
                'forwards_count' => $message['forward_signature'] ?? 0,
                'raw_payload' => $message,
            ])->save();
        } else {
            // Edited post — refresh preview if text changed
            $post->update([
                'text_preview' => $this->extractTextPreview($message),
                'raw_payload' => $message,
            ]);
        }

        return $post;
    }

    protected function detectContentType(array $message): string
    {
        return match (true) {
            isset($message['photo']) => TelegramChannelPost::TYPE_PHOTO,
            isset($message['video']) => TelegramChannelPost::TYPE_VIDEO,
            isset($message['animation']) => TelegramChannelPost::TYPE_ANIMATION,
            isset($message['audio']) => TelegramChannelPost::TYPE_AUDIO,
            isset($message['voice']) => TelegramChannelPost::TYPE_VOICE,
            isset($message['document']) => TelegramChannelPost::TYPE_DOCUMENT,
            isset($message['poll']) => TelegramChannelPost::TYPE_POLL,
            isset($message['location']) => TelegramChannelPost::TYPE_LOCATION,
            isset($message['text']) || isset($message['caption']) => TelegramChannelPost::TYPE_TEXT,
            default => TelegramChannelPost::TYPE_OTHER,
        };
    }

    protected function countMedia(array $message): int
    {
        $count = 0;
        foreach (['photo', 'video', 'animation', 'document', 'audio', 'voice'] as $k) {
            if (isset($message[$k])) {
                $count++;
            }
        }
        return $count;
    }

    protected function extractTextPreview(array $message): ?string
    {
        $text = $message['text'] ?? $message['caption'] ?? null;
        if (!$text) {
            return null;
        }
        return mb_substr($text, 0, 280);
    }

    // =================================================================
    // REACTIONS: message_reaction_count webhook event
    // =================================================================

    public function recordReactionCount(array $update): void
    {
        $chat = $update['chat'] ?? [];
        $messageId = $update['message_id'] ?? null;
        $reactions = $update['reactions'] ?? [];

        if (!($chat['id'] ?? null) || !$messageId) {
            return;
        }

        $channel = TelegramChannel::where('telegram_chat_id', $chat['id'])->first();
        if (!$channel) {
            return;
        }

        $post = TelegramChannelPost::where('telegram_channel_id', $channel->id)
            ->where('message_id', $messageId)
            ->first();

        if (!$post) {
            return;
        }

        $total = 0;
        foreach ($reactions as $r) {
            $total += (int) ($r['total_count'] ?? 0);
        }

        $post->update(['reactions_count' => $total]);
    }

    // =================================================================
    // MEMBERSHIP: chat_member webhook event (join/leave)
    // =================================================================

    public function recordMembershipChange(array $update): void
    {
        $chat = $update['chat'] ?? [];
        $oldStatus = $update['old_chat_member']['status'] ?? 'left';
        $newStatus = $update['new_chat_member']['status'] ?? 'left';

        if (!($chat['id'] ?? null)) {
            return;
        }

        $channel = TelegramChannel::where('telegram_chat_id', $chat['id'])->first();
        if (!$channel) {
            return;
        }

        $today = now()->toDateString();
        $stat = TelegramChannelDailyStat::firstOrNew([
            'telegram_channel_id' => $channel->id,
            'stat_date' => $today,
        ]);

        $joined = ['left', 'kicked', 'restricted'];
        $active = ['member', 'administrator', 'creator'];

        if (in_array($oldStatus, $joined, true) && in_array($newStatus, $active, true)) {
            $stat->new_subscribers = (int) $stat->new_subscribers + 1;
        } elseif (in_array($oldStatus, $active, true) && in_array($newStatus, $joined, true)) {
            $stat->left_subscribers = (int) $stat->left_subscribers + 1;
        }

        $stat->net_growth = (int) $stat->new_subscribers - (int) $stat->left_subscribers;
        $stat->save();
    }

    // =================================================================
    // SYNC: Called from SyncTelegramChannelStatsJob
    // =================================================================

    /**
     * Refresh channel core stats (subscriber count, chat info).
     *
     * On transient failure (e.g. Telegram API timeout) we DO NOT mark the
     * channel as disconnected — that's the responsibility of the `my_chat_member`
     * webhook which gives us authoritative status updates. We simply skip this
     * sync cycle and try again in 30 minutes.
     */
    public function syncChannelCore(TelegramChannel $channel): void
    {
        $chatId = $channel->telegram_chat_id;
        $chatInfo = $this->bot->getChat($chatId);

        if (!$chatInfo) {
            Log::info('TelegramChannelAnalytics: syncChannelCore skipped (getChat returned null)', [
                'channel_id' => $channel->id,
                'telegram_chat_id' => $chatId,
            ]);
            return;
        }

        $memberCount = $this->bot->getChatMemberCount($chatId) ?? $channel->subscriber_count;

        $channel->update([
            'chat_username' => $chatInfo['username'] ?? $channel->chat_username,
            'title' => $chatInfo['title'] ?? $channel->title,
            'description' => $chatInfo['description'] ?? $channel->description,
            'invite_link' => $chatInfo['invite_link'] ?? $channel->invite_link,
            'subscriber_count' => $memberCount,
            'last_synced_at' => now(),
        ]);
    }

    /**
     * Refresh views + reactions for recent posts (last 7 days).
     *
     * NOTE: Bot API'da `Message.views` faqat yangi update'lar orqali
     * yetkaziladi. Eski postlarga qayta `getMessage` chaqiruvi yo'q —
     * shuning uchun biz `channel_post` update kelganida faqat yangilarni
     * olamiz. Bu metod faqat DB'dagi postlar uchun snapshot yozadi.
     */
    public function snapshotRecentPosts(TelegramChannel $channel): int
    {
        $posts = TelegramChannelPost::where('telegram_channel_id', $channel->id)
            ->where('posted_at', '>=', now()->subDays(7))
            ->get();

        $count = 0;
        $now = now();

        foreach ($posts as $post) {
            // Write a snapshot row for delta analysis
            TelegramChannelPostSnapshot::create([
                'telegram_channel_post_id' => $post->id,
                'snapshot_at' => $now,
                'views' => $post->views,
                'reactions_count' => $post->reactions_count,
                'forwards_count' => $post->forwards_count,
            ]);

            // Compute 24h delta from oldest snapshot within last 24h
            $earlier = TelegramChannelPostSnapshot::where('telegram_channel_post_id', $post->id)
                ->where('snapshot_at', '<=', $now->copy()->subHours(24))
                ->orderByDesc('snapshot_at')
                ->first();

            if ($earlier) {
                $post->update([
                    'views_delta_24h' => max(0, $post->views - (int) $earlier->views),
                    'reactions_delta_24h' => max(0, $post->reactions_count - (int) $earlier->reactions_count),
                    'last_snapshot_at' => $now,
                ]);
            } else {
                $post->update(['last_snapshot_at' => $now]);
            }

            $count++;
        }

        return $count;
    }

    // =================================================================
    // DAILY ROLLUP: called at 23:59 for each channel
    // =================================================================

    /**
     * Roll up today's metrics into telegram_channel_daily_stats.
     */
    public function rollupDailyStats(TelegramChannel $channel, ?Carbon $date = null): TelegramChannelDailyStat
    {
        $date = $date ?? now()->startOfDay();

        $posts = TelegramChannelPost::where('telegram_channel_id', $channel->id)
            ->whereDate('posted_at', $date)
            ->get();

        $totalViews = (int) $posts->sum('views');
        $totalReactions = (int) $posts->sum('reactions_count');
        $totalForwards = (int) $posts->sum('forwards_count');
        $totalReplies = (int) $posts->sum('replies_count');
        $postsCount = $posts->count();
        $avgViews = $postsCount > 0 ? intval($totalViews / $postsCount) : 0;

        $topPost = $posts->sortByDesc('views')->first();

        $engagementRate = $totalViews > 0
            ? round((($totalReactions + $totalForwards + $totalReplies) / $totalViews) * 100, 2)
            : 0.0;

        $stat = TelegramChannelDailyStat::firstOrNew([
            'telegram_channel_id' => $channel->id,
            'stat_date' => $date->toDateString(),
        ]);

        // Preserve already-accumulated subscriber deltas from chat_member events
        $stat->fill([
            'subscriber_count' => $channel->subscriber_count,
            'posts_count' => $postsCount,
            'total_views' => $totalViews,
            'average_views' => $avgViews,
            'total_reactions' => $totalReactions,
            'total_forwards' => $totalForwards,
            'total_replies' => $totalReplies,
            'engagement_rate' => $engagementRate,
            'top_post_id' => $topPost?->id,
        ]);

        // Growth rate vs previous day
        $prev = TelegramChannelDailyStat::where('telegram_channel_id', $channel->id)
            ->where('stat_date', '<', $date->toDateString())
            ->orderByDesc('stat_date')
            ->first();

        if ($prev && $prev->subscriber_count > 0) {
            $growthRate = round(
                (($channel->subscriber_count - $prev->subscriber_count) / $prev->subscriber_count) * 100,
                2
            );
            $stat->growth_rate = $growthRate;
        }

        $stat->net_growth = (int) $stat->new_subscribers - (int) $stat->left_subscribers;
        $stat->save();

        return $stat;
    }

    // =================================================================
    // DIGEST: Build & send daily digest to connected user
    // =================================================================

    public function buildDigestMessage(TelegramChannel $channel, ?Carbon $date = null): string
    {
        $date = $date ?? now()->subDay()->startOfDay();

        $stat = TelegramChannelDailyStat::where('telegram_channel_id', $channel->id)
            ->where('stat_date', $date->toDateString())
            ->first();

        $headline = "📊 <b>Kanal hisoboti</b> — {$date->format('d.m.Y')}\n"
            . "━━━━━━━━━━━━━━━━━━━━\n\n"
            . "📢 <b>{$channel->title}</b>";

        if ($channel->chat_username) {
            $headline .= " (@{$channel->chat_username})";
        }
        $headline .= "\n\n";

        if (!$stat) {
            return $headline
                . "ℹ️ Bu kun uchun ma'lumot yo'q.\n\n"
                . "Kanalga post chiqaring va ertaga to'liq hisobot olasiz.";
        }

        $lines = [];

        // Subscribers
        $growthIcon = $stat->net_growth >= 0 ? '📈' : '📉';
        $lines[] = "👥 <b>Obunachilar:</b> " . number_format($stat->subscriber_count, 0, '.', ' ');
        $lines[] = "   +{$stat->new_subscribers} / -{$stat->left_subscribers} (sof: "
            . ($stat->net_growth >= 0 ? '+' : '')
            . "{$stat->net_growth})";
        if ($stat->growth_rate != 0) {
            $lines[] = "   {$growthIcon} O'sish: "
                . ($stat->growth_rate >= 0 ? '+' : '')
                . "{$stat->growth_rate}%";
        }

        // Posts
        $lines[] = "";
        $lines[] = "📝 <b>Postlar:</b> {$stat->posts_count} ta";

        if ($stat->posts_count > 0) {
            $lines[] = "👁 Ko'rishlar: " . number_format($stat->total_views, 0, '.', ' ');
            $lines[] = "📊 O'rtacha: " . number_format($stat->average_views, 0, '.', ' ') . "/post";
            $lines[] = "❤️ Reaksiyalar: " . number_format($stat->total_reactions, 0, '.', ' ');
            if ($stat->total_forwards > 0) {
                $lines[] = "↗️ Forwardlar: " . number_format($stat->total_forwards, 0, '.', ' ');
            }
            $lines[] = "⚡ Engagement: {$stat->engagement_rate}%";
        }

        // Top post
        $topPost = $stat->topPost;
        if ($topPost) {
            $preview = $topPost->text_preview
                ? mb_substr($topPost->text_preview, 0, 80)
                : '(media post)';
            $lines[] = "";
            $lines[] = "🔥 <b>Eng yaxshi post:</b>";
            $lines[] = "   «{$preview}»";
            $lines[] = "   👁 " . number_format($topPost->views, 0, '.', ' ')
                . "  ❤️ " . number_format($topPost->reactions_count, 0, '.', ' ');

            $link = $topPost->telegramLink();
            if ($link) {
                $lines[] = "   🔗 <a href=\"{$link}\">Ochish</a>";
            }
        }

        $lines[] = "";
        $lines[] = "━━━━━━━━━━━━━━━━━━━━";
        $lines[] = "📊 Batafsil: biznespilot.uz/telegram/channels";

        return $headline . implode("\n", $lines);
    }

    public function sendDigestToOwner(TelegramChannel $channel, ?Carbon $date = null): bool
    {
        $user = $channel->connectedByUser;
        if (!$user || !$user->telegram_chat_id) {
            Log::info('TelegramChannelAnalytics: digest skipped — no linked user', [
                'channel_id' => $channel->id,
            ]);
            return false;
        }

        $message = $this->buildDigestMessage($channel, $date);

        return $this->bot->sendToUser($user, $message);
    }

    // =================================================================
    // NOTIFICATIONS
    // =================================================================

    protected function sendChannelConnectedNotification(User $user, TelegramChannel $channel): void
    {
        $title = $channel->title;
        $username = $channel->chat_username ? " (@{$channel->chat_username})" : '';

        $message = "✅ <b>Kanal ulandi!</b>\n\n"
            . "📢 <b>{$title}</b>{$username}\n"
            . "👥 Obunachilar: " . number_format($channel->subscriber_count, 0, '.', ' ') . "\n\n"
            . "━━━━━━━━━━━━━━━━━━━━\n\n"
            . "🎯 <b>Endi men nima qilaman:</b>\n\n"
            . "📊 Har kuni ertalab 08:00 da statistika jo'nataman\n"
            . "📝 Har bir post ko'rishlari, reaksiyalari — real-time\n"
            . "📈 Obunachilar o'sishi/kamayishi — kunlik hisobot\n"
            . "🔥 Top post — eng yaxshi kontent\n\n"
            . "━━━━━━━━━━━━━━━━━━━━\n\n"
            . "💡 <i>Maslahat: yangi post chiqing va ertaga to'liq hisobot oling!</i>";

        $this->bot->sendToUser($user, $message);
    }
}
