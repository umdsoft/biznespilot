<?php

declare(strict_types=1);

namespace Tests\Feature\Telegram;

use App\Jobs\Telegram\RollupTelegramChannelDailyStatsJob;
use App\Jobs\Telegram\SendTelegramChannelDigestJob;
use App\Jobs\Telegram\SyncTelegramChannelStatsJob;
use App\Models\Business;
use App\Models\TelegramChannel;
use App\Models\TelegramChannelDailyStat;
use App\Models\TelegramChannelPost;
use App\Models\TelegramChannelPostSnapshot;
use App\Models\User;
use App\Services\Telegram\SystemBotService;
use App\Services\Telegram\TelegramChannelAnalyticsService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Comprehensive test suite for Telegram Channel Analytics.
 *
 * Covers:
 *   - Webhook event processing (my_chat_member, channel_post, chat_member, message_reaction_count)
 *   - Service layer logic (rollupDailyStats, buildDigestMessage, syncChannelCore)
 *   - Jobs (sync, rollup, digest)
 *   - HTTP endpoints (connect-link, list, disconnect)
 *   - Tenant isolation & security boundaries
 */
class TelegramChannelAnalyticsTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected Business $business;

    protected const BOT_TOKEN = 'FAKE_TOKEN_123:ABC';

    protected const BOT_USERNAME = 'TestBiznesPilotBot';

    protected function setUp(): void
    {
        parent::setUp();

        // Configure System Bot token/username for the tests
        Config::set('services.telegram.system_bot_token', self::BOT_TOKEN);
        Config::set('services.telegram.system_bot_username', self::BOT_USERNAME);
        Config::set('services.telegram.webhook_secret', null); // disable secret check

        $this->user = User::factory()->create([
            'telegram_chat_id' => '555000111',
            'telegram_linked_at' => now(),
        ]);

        $this->business = Business::factory()->create([
            'user_id' => $this->user->id,
        ]);

        session(['current_business_id' => $this->business->id]);

        // Intercept all Telegram Bot API calls
        Http::fake([
            'api.telegram.org/*getChat' => Http::response([
                'ok' => true,
                'result' => [
                    'id' => -1001234567890,
                    'type' => 'channel',
                    'title' => 'Test Channel',
                    'username' => 'testchannel',
                    'description' => 'Testing',
                ],
            ]),
            'api.telegram.org/*getChatMemberCount' => Http::response([
                'ok' => true,
                'result' => 1250,
            ]),
            'api.telegram.org/*getChatAdministrators' => Http::response([
                'ok' => true,
                'result' => [
                    ['user' => ['id' => 1, 'is_bot' => false, 'first_name' => 'Owner']],
                    ['user' => ['id' => 2, 'is_bot' => true, 'first_name' => 'Bot']],
                ],
            ]),
            'api.telegram.org/*sendMessage' => Http::response([
                'ok' => true,
                'result' => ['message_id' => 100, 'date' => time()],
            ]),
            'api.telegram.org/*leaveChat' => Http::response([
                'ok' => true,
                'result' => true,
            ]),
            // Default fallback
            'api.telegram.org/*' => Http::response(['ok' => true, 'result' => null]),
        ]);
    }

    // =================================================================
    // my_chat_member — BOT PROMOTED TO ADMIN
    // =================================================================

    public function test_bot_promoted_by_linked_user_creates_tracked_channel(): void
    {
        $update = $this->myChatMemberUpdate(
            chatId: -1001234567890,
            chatType: 'channel',
            chatTitle: 'Test Channel',
            oldStatus: 'left',
            newStatus: 'administrator',
            promotedById: (int) $this->user->telegram_chat_id,
            rights: [
                'can_manage_chat' => true,
                'can_post_messages' => false,
                'can_delete_messages' => false,
            ],
        );

        $response = $this->postJson('/api/webhooks/system-bot', $update);

        $response->assertOk()->assertJson(['ok' => true]);

        $this->assertDatabaseHas('telegram_channels', [
            'telegram_chat_id' => -1001234567890,
            'business_id' => $this->business->id,
            'connected_by_user_id' => $this->user->id,
            'admin_status' => 'administrator',
            'is_active' => true,
        ]);

        $channel = TelegramChannel::where('telegram_chat_id', -1001234567890)->first();
        $this->assertSame('Test Channel', $channel->title);
        $this->assertSame('testchannel', $channel->chat_username);
        $this->assertSame(1250, $channel->subscriber_count);
        $this->assertEqualsCanonicalizing(
            ['can_manage_chat' => true, 'can_post_messages' => false, 'can_delete_messages' => false],
            $channel->admin_rights,
        );
        $this->assertNotNull($channel->connected_at);
    }

    public function test_bot_promoted_by_unlinked_user_is_rejected_and_bot_leaves_chat(): void
    {
        $update = $this->myChatMemberUpdate(
            chatId: -1009999999,
            chatType: 'channel',
            chatTitle: 'Ghost Channel',
            oldStatus: 'left',
            newStatus: 'administrator',
            promotedById: 999888777, // not linked
        );

        $response = $this->postJson('/api/webhooks/system-bot', $update);

        $response->assertOk();

        // Channel should NOT be created
        $this->assertDatabaseMissing('telegram_channels', [
            'telegram_chat_id' => -1009999999,
        ]);

        // Bot should have called leaveChat
        Http::assertSent(fn ($r) => str_contains($r->url(), '/leaveChat'));
    }

    public function test_bot_promoted_in_group_chat_type_is_ignored(): void
    {
        $update = $this->myChatMemberUpdate(
            chatId: -10099,
            chatType: 'group', // not channel/supergroup
            chatTitle: 'Private Group',
            oldStatus: 'left',
            newStatus: 'administrator',
            promotedById: (int) $this->user->telegram_chat_id,
        );

        $this->postJson('/api/webhooks/system-bot', $update)->assertOk();

        $this->assertDatabaseMissing('telegram_channels', [
            'telegram_chat_id' => -10099,
        ]);
    }

    public function test_bot_demoted_marks_channel_inactive(): void
    {
        $channel = TelegramChannel::create([
            'business_id' => $this->business->id,
            'connected_by_user_id' => $this->user->id,
            'telegram_chat_id' => -100777,
            'title' => 'Active Ch',
            'type' => 'channel',
            'subscriber_count' => 500,
            'admin_status' => TelegramChannel::STATUS_ADMIN,
            'is_active' => true,
            'connected_at' => now(),
        ]);

        $update = $this->myChatMemberUpdate(
            chatId: -100777,
            chatType: 'channel',
            chatTitle: 'Active Ch',
            oldStatus: 'administrator',
            newStatus: 'kicked',
            promotedById: (int) $this->user->telegram_chat_id,
        );

        $this->postJson('/api/webhooks/system-bot', $update)->assertOk();

        $channel->refresh();
        $this->assertFalse($channel->is_active);
        $this->assertSame('kicked', $channel->admin_status);
        $this->assertNotNull($channel->disconnected_at);
    }

    public function test_channel_already_owned_by_another_business_cannot_be_stolen(): void
    {
        // Business A owns the channel
        $businessA = Business::factory()->create();
        $channel = TelegramChannel::create([
            'business_id' => $businessA->id,
            'connected_by_user_id' => $this->user->id,
            'telegram_chat_id' => -100_55555,
            'title' => 'A Channel',
            'type' => 'channel',
            'subscriber_count' => 999,
            'admin_status' => TelegramChannel::STATUS_ADMIN,
            'is_active' => true,
            'connected_at' => now()->subDay(),
        ]);

        // User from business B promotes bot in that same channel
        $update = $this->myChatMemberUpdate(
            chatId: -100_55555,
            chatType: 'channel',
            chatTitle: 'A Channel',
            oldStatus: 'left',
            newStatus: 'administrator',
            promotedById: (int) $this->user->telegram_chat_id, // $this->user is in $this->business (Business B)
        );

        $this->postJson('/api/webhooks/system-bot', $update)->assertOk();

        // Channel must still belong to Business A — NOT stolen
        $channel->refresh();
        $this->assertSame($businessA->id, $channel->business_id);
        $this->assertSame(999, $channel->subscriber_count); // not overwritten

        // Bot must call leaveChat to bail out
        Http::assertSent(fn ($r) => str_contains($r->url(), '/leaveChat'));
    }

    public function test_sync_core_skips_silently_on_api_failure_does_not_mark_disconnected(): void
    {
        $channel = $this->makeTrackedChannel(chatId: -100_66777);

        // Override Http fake — return a failing response
        Http::fake([
            'api.telegram.org/*getChat' => Http::response(['ok' => false, 'description' => 'Timeout'], 500),
            'api.telegram.org/*' => Http::response(['ok' => true, 'result' => true]),
        ]);

        app(TelegramChannelAnalyticsService::class)->syncChannelCore($channel);

        $channel->refresh();
        // Must remain active — transient error should not disconnect
        $this->assertTrue($channel->is_active);
        $this->assertSame('administrator', $channel->admin_status);
    }

    public function test_bot_re_promoted_on_existing_channel_reactivates_it(): void
    {
        $channel = TelegramChannel::create([
            'business_id' => $this->business->id,
            'connected_by_user_id' => $this->user->id,
            'telegram_chat_id' => -1001234567890,
            'title' => 'Test Channel',
            'type' => 'channel',
            'subscriber_count' => 0,
            'admin_status' => TelegramChannel::STATUS_LEFT,
            'is_active' => false,
            'disconnected_at' => now()->subDay(),
            'connected_at' => now()->subWeek(),
        ]);

        $originalConnectedAt = $channel->connected_at->toIso8601String();

        $update = $this->myChatMemberUpdate(
            chatId: -1001234567890,
            chatType: 'channel',
            chatTitle: 'Test Channel',
            oldStatus: 'left',
            newStatus: 'administrator',
            promotedById: (int) $this->user->telegram_chat_id,
        );

        $this->postJson('/api/webhooks/system-bot', $update)->assertOk();

        $channel->refresh();
        $this->assertTrue($channel->is_active);
        $this->assertNull($channel->disconnected_at);
        $this->assertSame('administrator', $channel->admin_status);
        // connected_at should be preserved (not reset)
        $this->assertSame($originalConnectedAt, $channel->connected_at->toIso8601String());
    }

    // =================================================================
    // channel_post — POST TRACKING
    // =================================================================

    public function test_channel_post_creates_post_record_for_tracked_channel(): void
    {
        $channel = $this->makeTrackedChannel(chatId: -1001111);

        $update = [
            'update_id' => 100,
            'channel_post' => [
                'message_id' => 42,
                'date' => now()->timestamp,
                'chat' => [
                    'id' => -1001111,
                    'type' => 'channel',
                    'title' => $channel->title,
                ],
                'text' => 'New product launched today! Check it out.',
                'views' => 250,
            ],
        ];

        $this->postJson('/api/webhooks/system-bot', $update)->assertOk();

        $this->assertDatabaseHas('telegram_channel_posts', [
            'telegram_channel_id' => $channel->id,
            'message_id' => 42,
            'views' => 250,
            'content_type' => 'text',
        ]);

        $post = TelegramChannelPost::where('message_id', 42)->first();
        $this->assertStringContainsString('New product launched', $post->text_preview);
    }

    public function test_channel_post_with_photo_detects_content_type(): void
    {
        $channel = $this->makeTrackedChannel(chatId: -1002222);

        $update = [
            'update_id' => 101,
            'channel_post' => [
                'message_id' => 10,
                'date' => now()->timestamp,
                'chat' => ['id' => -1002222, 'type' => 'channel'],
                'photo' => [
                    ['file_id' => 'abc1', 'width' => 640, 'height' => 480],
                    ['file_id' => 'abc2', 'width' => 1280, 'height' => 960],
                ],
                'caption' => 'Product photo',
            ],
        ];

        $this->postJson('/api/webhooks/system-bot', $update)->assertOk();

        $post = TelegramChannelPost::where('telegram_channel_id', $channel->id)
            ->where('message_id', 10)
            ->first();
        $this->assertNotNull($post);
        $this->assertSame('photo', $post->content_type);
        $this->assertSame('Product photo', $post->text_preview);
    }

    public function test_channel_post_on_inactive_channel_is_ignored(): void
    {
        $channel = TelegramChannel::create([
            'business_id' => $this->business->id,
            'connected_by_user_id' => $this->user->id,
            'telegram_chat_id' => -1003333,
            'title' => 'Disabled',
            'type' => 'channel',
            'subscriber_count' => 100,
            'admin_status' => TelegramChannel::STATUS_LEFT,
            'is_active' => false,
        ]);

        $update = [
            'update_id' => 102,
            'channel_post' => [
                'message_id' => 99,
                'date' => now()->timestamp,
                'chat' => ['id' => -1003333, 'type' => 'channel'],
                'text' => 'Post for inactive channel',
            ],
        ];

        $this->postJson('/api/webhooks/system-bot', $update)->assertOk();

        $this->assertDatabaseMissing('telegram_channel_posts', [
            'telegram_channel_id' => $channel->id,
            'message_id' => 99,
        ]);
    }

    public function test_edited_channel_post_updates_existing_post_without_duplicate(): void
    {
        $channel = $this->makeTrackedChannel(chatId: -1004444);

        // Step 1: initial post
        $this->postJson('/api/webhooks/system-bot', [
            'update_id' => 201,
            'channel_post' => [
                'message_id' => 77,
                'date' => now()->timestamp,
                'chat' => ['id' => -1004444, 'type' => 'channel'],
                'text' => 'Original text',
            ],
        ])->assertOk();

        // Step 2: edit
        $this->postJson('/api/webhooks/system-bot', [
            'update_id' => 202,
            'edited_channel_post' => [
                'message_id' => 77,
                'date' => now()->timestamp,
                'chat' => ['id' => -1004444, 'type' => 'channel'],
                'text' => 'Edited text — much better!',
            ],
        ])->assertOk();

        $this->assertSame(1, TelegramChannelPost::where('telegram_channel_id', $channel->id)->count());

        $post = TelegramChannelPost::where('message_id', 77)->first();
        $this->assertSame('Edited text — much better!', $post->text_preview);
    }

    // =================================================================
    // message_reaction_count — REACTIONS
    // =================================================================

    public function test_message_reaction_count_updates_post_reactions(): void
    {
        $channel = $this->makeTrackedChannel(chatId: -1005555);

        $post = TelegramChannelPost::create([
            'telegram_channel_id' => $channel->id,
            'message_id' => 1,
            'posted_at' => now(),
            'content_type' => 'text',
            'views' => 100,
            'reactions_count' => 0,
        ]);

        $update = [
            'update_id' => 300,
            'message_reaction_count' => [
                'chat' => ['id' => -1005555, 'type' => 'channel'],
                'message_id' => 1,
                'date' => now()->timestamp,
                'reactions' => [
                    ['type' => ['type' => 'emoji', 'emoji' => '❤️'], 'total_count' => 12],
                    ['type' => ['type' => 'emoji', 'emoji' => '🔥'], 'total_count' => 5],
                ],
            ],
        ];

        $this->postJson('/api/webhooks/system-bot', $update)->assertOk();

        $post->refresh();
        $this->assertSame(17, $post->reactions_count);
    }

    public function test_reaction_count_for_unknown_post_does_nothing(): void
    {
        $channel = $this->makeTrackedChannel(chatId: -1006666);

        $update = [
            'update_id' => 301,
            'message_reaction_count' => [
                'chat' => ['id' => -1006666, 'type' => 'channel'],
                'message_id' => 99999,
                'date' => now()->timestamp,
                'reactions' => [
                    ['type' => ['type' => 'emoji', 'emoji' => '❤️'], 'total_count' => 1],
                ],
            ],
        ];

        // Should not error
        $this->postJson('/api/webhooks/system-bot', $update)->assertOk();
    }

    // =================================================================
    // chat_member — MEMBERSHIP CHANGES
    // =================================================================

    public function test_chat_member_join_increments_new_subscribers(): void
    {
        $channel = $this->makeTrackedChannel(chatId: -1007777);

        $update = [
            'update_id' => 400,
            'chat_member' => [
                'chat' => ['id' => -1007777, 'type' => 'channel'],
                'from' => ['id' => 111, 'is_bot' => false, 'first_name' => 'A'],
                'date' => now()->timestamp,
                'old_chat_member' => ['user' => ['id' => 111, 'is_bot' => false], 'status' => 'left'],
                'new_chat_member' => ['user' => ['id' => 111, 'is_bot' => false], 'status' => 'member'],
            ],
        ];

        $this->postJson('/api/webhooks/system-bot', $update)->assertOk();

        $stat = TelegramChannelDailyStat::where('telegram_channel_id', $channel->id)
            ->where('stat_date', now()->toDateString())
            ->first();

        $this->assertNotNull($stat);
        $this->assertSame(1, $stat->new_subscribers);
        $this->assertSame(0, $stat->left_subscribers);
        $this->assertSame(1, $stat->net_growth);
    }

    public function test_chat_member_leave_increments_left_subscribers(): void
    {
        $channel = $this->makeTrackedChannel(chatId: -1008888);

        $this->postJson('/api/webhooks/system-bot', [
            'update_id' => 401,
            'chat_member' => [
                'chat' => ['id' => -1008888, 'type' => 'channel'],
                'from' => ['id' => 222],
                'date' => now()->timestamp,
                'old_chat_member' => ['user' => ['id' => 222], 'status' => 'member'],
                'new_chat_member' => ['user' => ['id' => 222], 'status' => 'left'],
            ],
        ])->assertOk();

        $stat = TelegramChannelDailyStat::where('telegram_channel_id', $channel->id)
            ->where('stat_date', now()->toDateString())
            ->first();

        $this->assertSame(1, $stat->left_subscribers);
        $this->assertSame(-1, $stat->net_growth);
    }

    // =================================================================
    // SERVICE LAYER: rollupDailyStats, buildDigest
    // =================================================================

    public function test_rollup_daily_stats_calculates_engagement_and_picks_top_post(): void
    {
        $channel = $this->makeTrackedChannel(chatId: -1011111, subscriberCount: 5000);

        $today = now()->startOfDay();

        $post1 = TelegramChannelPost::create([
            'telegram_channel_id' => $channel->id,
            'message_id' => 1,
            'posted_at' => $today->copy()->addHours(8),
            'content_type' => 'text',
            'views' => 1000,
            'reactions_count' => 50,
            'forwards_count' => 10,
        ]);
        $post2 = TelegramChannelPost::create([
            'telegram_channel_id' => $channel->id,
            'message_id' => 2,
            'posted_at' => $today->copy()->addHours(14),
            'content_type' => 'photo',
            'views' => 3000, // TOP
            'reactions_count' => 200,
            'forwards_count' => 40,
        ]);

        $service = app(TelegramChannelAnalyticsService::class);
        $stat = $service->rollupDailyStats($channel, $today);

        $this->assertSame(2, $stat->posts_count);
        $this->assertSame(4000, $stat->total_views);
        $this->assertSame(2000, $stat->average_views);
        $this->assertSame(250, $stat->total_reactions);
        $this->assertSame(50, $stat->total_forwards);
        $this->assertSame($post2->id, $stat->top_post_id);
        // engagement = (250 + 50) / 4000 * 100 = 7.50
        $this->assertEquals(7.50, (float) $stat->engagement_rate);
    }

    public function test_rollup_computes_growth_rate_vs_previous_day(): void
    {
        $channel = $this->makeTrackedChannel(chatId: -1012222, subscriberCount: 1100);

        TelegramChannelDailyStat::create([
            'telegram_channel_id' => $channel->id,
            'stat_date' => now()->subDay()->toDateString(),
            'subscriber_count' => 1000,
        ]);

        $stat = app(TelegramChannelAnalyticsService::class)
            ->rollupDailyStats($channel, now()->startOfDay());

        // (1100 - 1000) / 1000 * 100 = 10.00
        $this->assertEquals(10.00, (float) $stat->growth_rate);
    }

    public function test_digest_message_has_headline_and_sends_to_owner(): void
    {
        $channel = $this->makeTrackedChannel(chatId: -1013333, subscriberCount: 2500);

        TelegramChannelDailyStat::create([
            'telegram_channel_id' => $channel->id,
            'stat_date' => now()->subDay()->toDateString(),
            'subscriber_count' => 2500,
            'new_subscribers' => 50,
            'left_subscribers' => 5,
            'net_growth' => 45,
            'posts_count' => 3,
            'total_views' => 9000,
            'average_views' => 3000,
            'total_reactions' => 300,
            'engagement_rate' => 3.33,
        ]);

        $service = app(TelegramChannelAnalyticsService::class);
        $message = $service->buildDigestMessage($channel, now()->subDay()->startOfDay());

        $this->assertStringContainsString('Kanal hisoboti', $message);
        $this->assertStringContainsString($channel->title, $message);
        $this->assertStringContainsString('2 500', $message);
        $this->assertStringContainsString('+50', $message);

        $ok = $service->sendDigestToOwner($channel, now()->subDay()->startOfDay());
        $this->assertTrue($ok);

        Http::assertSent(fn ($r) => str_contains($r->url(), '/sendMessage'));
    }

    public function test_digest_skipped_when_no_stats_exist(): void
    {
        $channel = $this->makeTrackedChannel(chatId: -1014444);

        $service = app(TelegramChannelAnalyticsService::class);
        $message = $service->buildDigestMessage($channel, now()->subDay()->startOfDay());

        $this->assertStringContainsString("ma'lumot yo'q", $message);
    }

    // =================================================================
    // SystemBotService — DEEP LINK
    // =================================================================

    public function test_generate_channel_deep_link_returns_correct_url(): void
    {
        $bot = app(SystemBotService::class);
        $link = $bot->generateChannelDeepLink();

        $this->assertSame(
            'https://t.me/'.self::BOT_USERNAME.'?startchannel=true&admin=manage_chat',
            $link
        );
    }

    public function test_generate_channel_deep_link_null_when_not_configured(): void
    {
        Config::set('services.telegram.system_bot_username', null);

        $bot = new SystemBotService();
        $this->assertNull($bot->generateChannelDeepLink());
    }

    // =================================================================
    // HTTP ENDPOINTS
    // =================================================================

    public function test_endpoint_returns_deep_link_for_linked_user(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson('/business/settings/telegram/channels/connect-link');

        $response->assertOk()
            ->assertJsonStructure(['success', 'link', 'bot_username', 'instructions']);

        $this->assertStringContainsString('?startchannel=true', $response->json('link'));
    }

    public function test_endpoint_rejects_unlinked_user(): void
    {
        $unlinked = User::factory()->create([
            'telegram_chat_id' => null,
            'telegram_linked_at' => null,
        ]);

        $response = $this->actingAs($unlinked)
            ->postJson('/business/settings/telegram/channels/connect-link');

        $response->assertStatus(400)
            ->assertJson(['requires_telegram_link' => true]);
    }

    public function test_list_channels_scoped_to_current_business(): void
    {
        $otherBiz = Business::factory()->create(['user_id' => $this->user->id]);

        TelegramChannel::create([
            'business_id' => $this->business->id,
            'connected_by_user_id' => $this->user->id,
            'telegram_chat_id' => -100_001,
            'title' => 'Mine',
            'type' => 'channel',
            'subscriber_count' => 100,
            'admin_status' => 'administrator',
            'is_active' => true,
        ]);
        TelegramChannel::create([
            'business_id' => $otherBiz->id,
            'connected_by_user_id' => $this->user->id,
            'telegram_chat_id' => -100_002,
            'title' => 'Other',
            'type' => 'channel',
            'subscriber_count' => 99,
            'admin_status' => 'administrator',
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->user)
            ->getJson('/business/settings/telegram/channels');

        $response->assertOk();
        $channels = $response->json('channels');
        $this->assertCount(1, $channels);
        $this->assertSame('Mine', $channels[0]['title']);
    }

    public function test_disconnect_channel_requires_matching_business(): void
    {
        $foreignBiz = Business::factory()->create();

        $channel = TelegramChannel::create([
            'business_id' => $foreignBiz->id,
            'connected_by_user_id' => $this->user->id,
            'telegram_chat_id' => -100_9999,
            'title' => 'Foreign',
            'type' => 'channel',
            'subscriber_count' => 10,
            'admin_status' => 'administrator',
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->user)
            ->deleteJson("/business/settings/telegram/channels/{$channel->id}");

        $response->assertForbidden();

        $channel->refresh();
        $this->assertTrue($channel->is_active);
    }

    public function test_disconnect_channel_succeeds_for_owned_channel(): void
    {
        $channel = $this->makeTrackedChannel(chatId: -100_1000);

        $response = $this->actingAs($this->user)
            ->deleteJson("/business/settings/telegram/channels/{$channel->id}");

        $response->assertOk();

        $channel->refresh();
        $this->assertFalse($channel->is_active);
    }

    // =================================================================
    // JOBS
    // =================================================================

    public function test_sync_job_runs_only_on_active_channels(): void
    {
        $active = $this->makeTrackedChannel(chatId: -100_5001);
        $inactive = TelegramChannel::create([
            'business_id' => $this->business->id,
            'connected_by_user_id' => $this->user->id,
            'telegram_chat_id' => -100_5002,
            'title' => 'Dead',
            'type' => 'channel',
            'subscriber_count' => 0,
            'admin_status' => 'left',
            'is_active' => false,
        ]);

        $job = new SyncTelegramChannelStatsJob();
        $job->handle(app(TelegramChannelAnalyticsService::class));

        $active->refresh();
        $this->assertNotNull($active->last_synced_at);

        $inactive->refresh();
        $this->assertNull($inactive->last_synced_at);
    }

    public function test_rollup_job_writes_daily_stats_for_every_active_channel(): void
    {
        $ch1 = $this->makeTrackedChannel(chatId: -100_6001);
        $ch2 = $this->makeTrackedChannel(chatId: -100_6002);

        (new RollupTelegramChannelDailyStatsJob(now()->toDateString()))
            ->handle(app(TelegramChannelAnalyticsService::class));

        $this->assertDatabaseHas('telegram_channel_daily_stats', [
            'telegram_channel_id' => $ch1->id,
            'stat_date' => now()->toDateString(),
        ]);
        $this->assertDatabaseHas('telegram_channel_daily_stats', [
            'telegram_channel_id' => $ch2->id,
            'stat_date' => now()->toDateString(),
        ]);
    }

    public function test_digest_job_sends_message_via_http(): void
    {
        Http::fake([
            'api.telegram.org/*' => Http::response(['ok' => true, 'result' => []]),
        ]);

        $channel = $this->makeTrackedChannel(chatId: -100_7001);

        TelegramChannelDailyStat::create([
            'telegram_channel_id' => $channel->id,
            'stat_date' => now()->subDay()->toDateString(),
            'subscriber_count' => 100,
            'new_subscribers' => 10,
            'left_subscribers' => 2,
            'net_growth' => 8,
            'posts_count' => 2,
            'total_views' => 500,
            'average_views' => 250,
            'total_reactions' => 30,
            'engagement_rate' => 6.00,
        ]);

        (new SendTelegramChannelDigestJob(now()->subDay()->toDateString()))
            ->handle(app(TelegramChannelAnalyticsService::class));

        Http::assertSent(fn ($r) => str_contains($r->url(), '/sendMessage')
            && str_contains((string) $r->body(), 'Kanal hisoboti'));
    }

    // =================================================================
    // SNAPSHOT LOGIC
    // =================================================================

    public function test_snapshot_recent_posts_creates_snapshot_rows_and_computes_delta(): void
    {
        $channel = $this->makeTrackedChannel(chatId: -100_8001);

        $post = TelegramChannelPost::create([
            'telegram_channel_id' => $channel->id,
            'message_id' => 5,
            'posted_at' => now()->subHours(48),
            'content_type' => 'text',
            'views' => 500,
            'reactions_count' => 20,
        ]);

        // Create an earlier snapshot 25h ago
        TelegramChannelPostSnapshot::create([
            'telegram_channel_post_id' => $post->id,
            'snapshot_at' => now()->subHours(25),
            'views' => 400,
            'reactions_count' => 15,
            'forwards_count' => 0,
        ]);

        app(TelegramChannelAnalyticsService::class)->snapshotRecentPosts($channel);

        $post->refresh();
        // Delta = current 500 - earlier 400 = 100
        $this->assertSame(100, $post->views_delta_24h);
        $this->assertSame(5, $post->reactions_delta_24h);

        // A new snapshot row should exist
        $this->assertSame(2, $post->snapshots()->count());
    }

    // =================================================================
    // HELPERS
    // =================================================================

    protected function makeTrackedChannel(int $chatId, int $subscriberCount = 100): TelegramChannel
    {
        return TelegramChannel::create([
            'business_id' => $this->business->id,
            'connected_by_user_id' => $this->user->id,
            'telegram_chat_id' => $chatId,
            'title' => "Ch {$chatId}",
            'chat_username' => 'ch_'.abs($chatId),
            'type' => 'channel',
            'subscriber_count' => $subscriberCount,
            'admin_status' => TelegramChannel::STATUS_ADMIN,
            'is_active' => true,
            'connected_at' => now(),
        ]);
    }

    protected function myChatMemberUpdate(
        int $chatId,
        string $chatType,
        string $chatTitle,
        string $oldStatus,
        string $newStatus,
        int $promotedById,
        array $rights = [],
    ): array {
        $newMember = [
            'user' => ['id' => 1234567, 'is_bot' => true, 'first_name' => 'Bot', 'username' => self::BOT_USERNAME],
            'status' => $newStatus,
        ];
        foreach ($rights as $k => $v) {
            $newMember[$k] = $v;
        }

        return [
            'update_id' => random_int(1, 999999),
            'my_chat_member' => [
                'chat' => [
                    'id' => $chatId,
                    'type' => $chatType,
                    'title' => $chatTitle,
                ],
                'from' => [
                    'id' => $promotedById,
                    'is_bot' => false,
                    'first_name' => 'Promoter',
                ],
                'date' => now()->timestamp,
                'old_chat_member' => [
                    'user' => ['id' => 1234567, 'is_bot' => true, 'first_name' => 'Bot'],
                    'status' => $oldStatus,
                ],
                'new_chat_member' => $newMember,
            ],
        ];
    }
}
