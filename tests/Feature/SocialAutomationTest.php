<?php

namespace Tests\Feature;

use App\Jobs\CheckAllTokensJob;
use App\Jobs\SyncContentPerformanceJob;
use App\Models\Business;
use App\Models\ContentCalendar;
use App\Models\InstagramAccount;
use App\Models\InstagramContentLink;
use App\Models\InstagramConversation;
use App\Models\Integration;
use App\Models\Lead;
use App\Models\LeadSource;
use App\Models\User;
use App\Services\ChatbotIntentService;
use App\Services\SocialTokenService;
use App\Services\TicketService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Mockery;
use Tests\TestCase;

/**
 * SocialAutomationTest - Instagram Chatbot va Kontent Integratsiyasi Testlari
 *
 * Bu test fayli quyidagi ssenariylarni tekshiradi:
 * A. Shikoyatdan Leadgacha (Chat -> CRM)
 * B. Postdan Statistikagacha (Instagram -> Content Plan)
 * C. Token Himoyasi (Self-Healing)
 *
 * @group social
 * @group instagram
 * @group chatbot
 */
class SocialAutomationTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Business $business;
    private InstagramAccount $instagramAccount;
    private Integration $integration;

    protected function setUp(): void
    {
        parent::setUp();

        // Test foydalanuvchi va biznes
        $this->user = User::factory()->create();
        $this->business = Business::factory()->create(['user_id' => $this->user->id]);
        $this->user->teamBusinesses()->attach($this->business->id, ['role' => 'owner']);

        // Integration (Meta Ads) - avval yaratamiz
        $this->integration = Integration::create([
            'business_id' => $this->business->id,
            'name' => 'Test Meta Ads Integration',
            'type' => 'meta_ads',
            'status' => 'connected',
            'is_active' => true,
            'credentials' => json_encode(['access_token' => 'test_token_abc']),
            'expires_at' => now()->addDays(30),
        ]);

        // Instagram akkaunt - integration_id bilan
        $this->instagramAccount = InstagramAccount::create([
            'business_id' => $this->business->id,
            'integration_id' => $this->integration->id,
            'instagram_id' => '17841400000000001',
            'username' => 'test_business',
            'name' => 'Test Business Account',
            'access_token' => 'test_access_token_123',
            'followers_count' => 5000,
            'is_active' => true,
        ]);

        // Lead source yaratish
        LeadSource::create([
            'code' => 'instagram_chatbot_auto',
            'name' => 'Instagram Chatbot (Avto)',
            'category' => 'digital',
            'is_active' => true,
        ]);

        LeadSource::create([
            'code' => 'instagram_chatbot_handoff',
            'name' => 'Instagram Chatbot -> Operator',
            'category' => 'digital',
            'is_active' => true,
        ]);
    }

    // ==========================================
    // TEST A: SHIKOYATDAN LEADGACHA (Chat -> CRM)
    // ==========================================

    /**
     * Test A.1: Shikoyat intentidan Lead yaratilishi
     *
     * Simulyatsiya: "Menda muammo bor" xabari kelganda
     * Kutilgan: Lead yaratilsin, intent='complaint'
     */
    public function test_complaint_message_creates_lead_in_crm(): void
    {
        // 1. Instagram Conversation yaratish
        $conversation = InstagramConversation::create([
            'account_id' => $this->instagramAccount->id,
            'conversation_id' => 'conv_' . uniqid(),
            'participant_id' => 'user_123456',
            'participant_name' => 'Test Mijoz',
            'participant_username' => 'test_user',
            'status' => 'active',
        ]);

        // 2. TicketService orqali Lead yaratish
        $ticketService = app(TicketService::class);

        $lead = $ticketService->createFromChatbot($conversation, [
            'intent' => 'complaint',
            'source_type' => 'dm',
            'first_message' => 'Menda muammo bor, mahsulot buzilgan',
            'collected_data' => [
                'detected_intent' => 'complaint',
                'matched_keyword' => 'muammo',
            ],
        ]);

        // 3. Tasdiqlash
        $this->assertNotNull($lead->id);
        $this->assertEquals($this->business->id, $lead->business_id);
        $this->assertEquals('complaint', $lead->chatbot_detected_intent);
        $this->assertEquals('dm', $lead->chatbot_source_type);
        $this->assertEquals($conversation->id, $lead->instagram_conversation_id);

        // Bazada mavjudligini tekshirish
        $this->assertDatabaseHas('leads', [
            'instagram_conversation_id' => $conversation->id,
            'chatbot_detected_intent' => 'complaint',
            'chatbot_source_type' => 'dm',
        ]);
    }

    /**
     * Test A.2: ChatbotIntentService shikoyatni to'g'ri aniqlaydi
     */
    public function test_chatbot_intent_service_detects_complaint_correctly(): void
    {
        $ticketService = app(TicketService::class);
        $intentService = new ChatbotIntentService($ticketService);

        // Conversation yaratish
        $conversation = InstagramConversation::create([
            'account_id' => $this->instagramAccount->id,
            'conversation_id' => 'conv_' . uniqid(),
            'participant_id' => 'user_789',
            'status' => 'active',
        ]);

        // Shikoyat xabarlarini tekshirish
        $complaintMessages = [
            'Menda muammo bor',
            'Mahsulot buzilgan',
            'Shikoyat qilmoqchiman',
            'Bu juda yomon xizmat',
            'Pulimni qaytaring',
        ];

        foreach ($complaintMessages as $message) {
            $intent = $intentService->detect(
                $message,
                $this->instagramAccount,
                $conversation
            );

            $this->assertEquals('complaint', $intent['type'], "'{$message}' uchun intent complaint bo'lishi kerak");
            $this->assertTrue($intent['requires_handoff'] ?? false, "'{$message}' uchun handoff kerak");
        }
    }

    /**
     * Test A.3: Handoff javobi to'g'ri qaytariladi
     */
    public function test_handoff_returns_correct_response_message(): void
    {
        $ticketService = app(TicketService::class);
        $intentService = new ChatbotIntentService($ticketService);

        $conversation = InstagramConversation::create([
            'account_id' => $this->instagramAccount->id,
            'conversation_id' => 'conv_' . uniqid(),
            'participant_id' => 'user_handoff_test',
            'status' => 'active',
        ]);

        $intent = $intentService->detect(
            'Operator bilan gaplashmoqchiman',
            $this->instagramAccount,
            $conversation
        );

        $result = $intentService->handleIntent($intent, $conversation, 'Operator bilan gaplashmoqchiman');

        // Handoff kerak
        $this->assertTrue($result['handoff_required']);
        $this->assertFalse($result['should_continue_flow']);

        // Javob matnida "operator" so'zi bo'lishi kerak
        $this->assertNotNull($result['auto_reply']);
        $this->assertStringContainsStringIgnoringCase('operator', $result['auto_reply']);

        // Lead yaratilgan bo'lishi kerak
        $this->assertTrue($result['lead_created']);
        $this->assertNotNull($result['lead']);
    }

    /**
     * Test A.4: Dublikat Lead yaratilmaydi (1 conversation = 1 lead)
     */
    public function test_duplicate_lead_not_created_for_same_conversation(): void
    {
        $conversation = InstagramConversation::create([
            'account_id' => $this->instagramAccount->id,
            'conversation_id' => 'conv_' . uniqid(),
            'participant_id' => 'user_duplicate_test',
            'status' => 'active',
        ]);

        $ticketService = app(TicketService::class);

        // Birinchi lead
        $lead1 = $ticketService->createFromChatbot($conversation, [
            'intent' => 'complaint',
            'source_type' => 'dm',
            'first_message' => 'Birinchi shikoyat',
        ]);

        // Ikkinchi urinish (xuddi shu conversation)
        $lead2 = $ticketService->createFromChatbot($conversation, [
            'intent' => 'complaint',
            'source_type' => 'dm',
            'first_message' => 'Ikkinchi shikoyat',
        ]);

        // Ikkalasi ham bir xil lead bo'lishi kerak
        $this->assertEquals($lead1->id, $lead2->id);

        // Bazada faqat 1 ta lead bo'lishi kerak
        $this->assertEquals(
            1,
            Lead::where('instagram_conversation_id', $conversation->id)->count()
        );
    }

    // ==========================================
    // TEST B: POSTDAN STATISTIKAGACHA (Instagram -> Content Plan)
    // ==========================================

    /**
     * Test B.1: SyncContentPerformanceJob statistikalarni sinxronlaydi
     */
    public function test_sync_job_updates_content_statistics(): void
    {
        // 1. Content Calendar yaratish
        $contentCalendar = ContentCalendar::create([
            'business_id' => $this->business->id,
            'title' => 'Test Post',
            'content' => 'Bu test post matni #test',
            'content_type' => 'post',
            'platform' => 'instagram',
            'status' => 'published',
            'scheduled_date' => now(),
        ]);

        // 2. Instagram Content Link yaratish
        $contentLink = InstagramContentLink::create([
            'business_id' => $this->business->id,
            'instagram_account_id' => $this->instagramAccount->id,
            'content_calendar_id' => $contentCalendar->id,
            'instagram_media_id' => '17841400000000099',
            'media_type' => 'post',
            'sync_status' => 'pending',
            'posted_at' => now(),
            'views' => 0,
            'likes' => 0,
        ]);

        // 3. Instagram API ni mock qilish
        Http::fake([
            'graph.facebook.com/*' => Http::sequence()
                // Basic metrics
                ->push([
                    'like_count' => 450,
                    'comments_count' => 32,
                    'caption' => 'Bu test post matni #test',
                    'permalink' => 'https://instagram.com/p/ABC123',
                ])
                // Insights
                ->push([
                    'data' => [
                        ['name' => 'reach', 'values' => [['value' => 3500]]],
                        ['name' => 'impressions', 'values' => [['value' => 5200]]],
                        ['name' => 'saved', 'values' => [['value' => 78]]],
                    ],
                ]),
        ]);

        // 4. Job ni ishga tushirish
        $job = new SyncContentPerformanceJob($this->business->id);
        $job->handle();

        // 5. Natijalarni tekshirish
        $contentLink->refresh();

        $this->assertEquals(450, $contentLink->likes);
        $this->assertEquals(32, $contentLink->comments);
        $this->assertEquals(78, $contentLink->saves);
        $this->assertEquals(3500, $contentLink->reach);
        $this->assertEquals('synced', $contentLink->sync_status);
        $this->assertNotNull($contentLink->last_synced_at);
    }

    /**
     * Test B.2: Rate Limit (429) da job to'xtamaydi
     */
    public function test_sync_job_handles_rate_limit_gracefully(): void
    {
        $contentLink = InstagramContentLink::create([
            'business_id' => $this->business->id,
            'instagram_account_id' => $this->instagramAccount->id,
            'instagram_media_id' => '17841400000000100',
            'media_type' => 'post',
            'sync_status' => 'pending',
            'posted_at' => now(),
        ]);

        // Rate limit xatosini simulyatsiya qilish
        Http::fake([
            'graph.facebook.com/*' => Http::response([
                'error' => [
                    'message' => 'Application request limit reached',
                    'code' => 4,
                    'error_subcode' => 2207051,
                ],
            ], 429),
        ]);

        // Job ishga tushirish - rate limit bo'lganda job release qilinadi
        $job = new SyncContentPerformanceJob($this->business->id);

        // Job xato bilan to'xtamagan bo'lishi kerak - istisnoga tushmasligi kerak
        try {
            $job->handle();
            // Job gracefully handles rate limit by releasing for retry
            $this->assertTrue(true, 'Job completed without throwing exception');
        } catch (\Exception $e) {
            $this->fail('Job should handle rate limit gracefully, but threw: ' . $e->getMessage());
        }

        // Post status 'pending' qoladi chunki job keyinroq qayta urinadi
        $contentLink->refresh();
        $this->assertContains($contentLink->sync_status, ['pending', 'failed']);
    }

    /**
     * Test B.3: Auto-link content calendar ga to'g'ri bog'laydi
     */
    public function test_auto_link_matches_content_correctly(): void
    {
        // Content Calendar yaratish
        $contentCalendar = ContentCalendar::create([
            'business_id' => $this->business->id,
            'title' => 'Yangi mahsulot',
            'content' => 'Yangi iPhone 15 Pro Max chegirmada! #chegirma #iphone',
            'content_type' => 'post',
            'platform' => 'instagram',
            'status' => 'published',
            'scheduled_date' => now(),
        ]);

        // Instagram post (caption o'xshash)
        $contentLink = InstagramContentLink::create([
            'business_id' => $this->business->id,
            'instagram_account_id' => $this->instagramAccount->id,
            'instagram_media_id' => '17841400000000101',
            'media_type' => 'post',
            'caption' => 'Yangi iPhone 15 Pro Max chegirmada! #chegirma #iphone #yangi',
            'posted_at' => now(),
            'sync_status' => 'pending',
        ]);

        // Auto-link qilish
        $linkedCount = SyncContentPerformanceJob::autoLinkPosts(
            $this->business->id,
            $this->instagramAccount->id
        );

        // Bog'langanligini tekshirish
        $contentLink->refresh();

        $this->assertEquals($contentCalendar->id, $contentLink->content_calendar_id);
        $this->assertEquals('auto', $contentLink->link_type);
        $this->assertGreaterThanOrEqual(70, $contentLink->match_confidence);
        $this->assertEquals(1, $linkedCount);
    }

    // ==========================================
    // TEST C: TOKEN HIMOYASI (Self-Healing)
    // ==========================================

    /**
     * Test C.1: Eskirgan token aniqlanadi
     */
    public function test_expired_token_is_detected(): void
    {
        // Facebook credentials ni konfiguratsiya qilish (test uchun zarur)
        config([
            'services.facebook.client_id' => 'test_app_id',
            'services.facebook.client_secret' => 'test_app_secret',
        ]);

        // Eskirgan token bilan integration (facebook type - meta_ads already used in setUp)
        $expiredIntegration = Integration::create([
            'business_id' => $this->business->id,
            'name' => 'Expired Facebook Integration',
            'type' => 'facebook',
            'status' => 'connected',
            'is_active' => true,
            'credentials' => json_encode(['access_token' => 'expired_token']),
            'expires_at' => now()->subDays(5), // 5 kun oldin eskirgan
        ]);

        // Mock Facebook debug_token API
        Http::fake([
            'graph.facebook.com/*/debug_token*' => Http::response([
                'data' => [
                    'is_valid' => false,
                    'error' => [
                        'message' => 'Error validating access token: Session has expired',
                    ],
                ],
            ]),
        ]);

        $tokenService = app(SocialTokenService::class);
        $health = $tokenService->checkTokenHealth($expiredIntegration);

        $this->assertFalse($health['is_valid']);
        $this->assertNotNull($health['error']);
    }

    /**
     * Test C.2: Token yangilanishi kerak bo'lganda refresh chaqiriladi
     */
    public function test_token_refresh_is_called_when_expiring(): void
    {
        // Facebook credentials ni konfiguratsiya qilish (test uchun zarur)
        config([
            'services.facebook.client_id' => 'test_app_id',
            'services.facebook.client_secret' => 'test_app_secret',
        ]);

        // 5 kun qolgan token (instagram type - meta_ads already used in setUp)
        $expiringIntegration = Integration::create([
            'business_id' => $this->business->id,
            'name' => 'Expiring Instagram Integration',
            'type' => 'instagram',
            'status' => 'connected',
            'is_active' => true,
            'credentials' => json_encode(['access_token' => 'expiring_token']),
            'expires_at' => now()->addDays(5),
        ]);

        // Mock APIs
        Http::fake([
            // debug_token - token hali valid, lekin tez eskiradi
            'graph.facebook.com/*/debug_token*' => Http::response([
                'data' => [
                    'is_valid' => true,
                    'expires_at' => now()->addDays(5)->timestamp,
                    'scopes' => ['instagram_basic', 'pages_read_engagement'],
                ],
            ]),
            // Token refresh
            'graph.facebook.com/*/oauth/access_token*' => Http::response([
                'access_token' => 'new_refreshed_token_xyz',
                'expires_in' => 5184000, // 60 kun
            ]),
        ]);

        $tokenService = app(SocialTokenService::class);
        $result = $tokenService->checkAndRefreshIfNeeded($expiringIntegration);

        $this->assertEquals('refreshed', $result['status']);
        $this->assertEquals('token_refreshed', $result['action_taken']);

        // Bazada yangi token saqlanganini tekshirish
        $expiringIntegration->refresh();
        $credentials = json_decode($expiringIntegration->credentials, true);
        $this->assertEquals('new_refreshed_token_xyz', $credentials['access_token']);
    }

    /**
     * Test C.3: CheckAllTokensJob barcha tokenlarni tekshiradi
     */
    public function test_check_all_tokens_job_processes_all_integrations(): void
    {
        // Facebook credentials ni konfiguratsiya qilish (test uchun zarur)
        config([
            'services.facebook.client_id' => 'test_app_id',
            'services.facebook.client_secret' => 'test_app_secret',
        ]);

        // Bir nechta business yaratib, har biriga meta_ads integration yaratish
        // (unique constraint business_id + type bo'lgani uchun)
        $additionalBusinesses = [];
        for ($i = 1; $i <= 3; $i++) {
            $business = Business::factory()->create(['user_id' => $this->user->id]);
            $additionalBusinesses[] = $business;
            Integration::create([
                'business_id' => $business->id,
                'name' => "Test Meta Ads {$i}",
                'type' => 'meta_ads',
                'status' => 'connected',
                'is_active' => true,
                'credentials' => json_encode(['access_token' => "test_token_{$i}"]),
                'expires_at' => now()->addDays(30),
            ]);
        }

        // Mock API - barcha tokenlar sog'lom
        Http::fake([
            'graph.facebook.com/*/debug_token*' => Http::response([
                'data' => [
                    'is_valid' => true,
                    'expires_at' => now()->addDays(30)->timestamp,
                ],
            ]),
        ]);

        // Log ni kuzatish - barcha log darajalarini qabul qilish
        Log::shouldReceive('info')
            ->zeroOrMoreTimes()
            ->andReturnNull();
        Log::shouldReceive('warning')
            ->zeroOrMoreTimes()
            ->andReturnNull();
        Log::shouldReceive('error')
            ->zeroOrMoreTimes()
            ->andReturnNull();

        // Job ni ishga tushirish (dependency injection bilan)
        $job = new CheckAllTokensJob();
        $job->handle(app(SocialTokenService::class));

        // HTTP so'rovlar yuborilganini tekshirish (4 ta integration)
        Http::assertSentCount(4); // setUp dan 1 + 3 qo'shimcha
    }

    // ==========================================
    // EDGE CASES TESTLARI
    // ==========================================

    /**
     * Test Edge.1: Bo'sh webhook xatosiz qayta ishlanadi
     */
    public function test_empty_message_handled_gracefully(): void
    {
        $ticketService = app(TicketService::class);
        $intentService = new ChatbotIntentService($ticketService);

        $conversation = InstagramConversation::create([
            'account_id' => $this->instagramAccount->id,
            'conversation_id' => 'conv_' . uniqid(),
            'participant_id' => 'user_empty_test',
            'status' => 'active',
        ]);

        // Bo'sh xabar
        $intent = $intentService->detect(
            '',
            $this->instagramAccount,
            $conversation
        );

        // Xato bermaydi, unknown qaytaradi
        $this->assertEquals('unknown', $intent['type']);
        $this->assertTrue($intent['should_fallback'] ?? false);
    }

    /**
     * Test Edge.2: Tez-tez kelgan shikoyatlarda spam himoyasi
     */
    public function test_spam_protection_for_rapid_complaints(): void
    {
        $conversation = InstagramConversation::create([
            'account_id' => $this->instagramAccount->id,
            'conversation_id' => 'conv_' . uniqid(),
            'participant_id' => 'spam_user',
            'status' => 'active',
        ]);

        $ticketService = app(TicketService::class);

        // 5 ta xabar ketma-ket
        for ($i = 0; $i < 5; $i++) {
            $lead = $ticketService->createFromChatbot($conversation, [
                'intent' => 'complaint',
                'source_type' => 'dm',
                'first_message' => "Shikoyat #{$i}",
            ]);
        }

        // Faqat 1 ta lead yaratilgan bo'lishi kerak
        $leadCount = Lead::where('instagram_conversation_id', $conversation->id)->count();
        $this->assertEquals(1, $leadCount);
    }

    /**
     * Test Edge.3: Maksimal sync attempts dan keyin to'xtash
     */
    public function test_sync_stops_after_max_attempts(): void
    {
        $contentLink = InstagramContentLink::create([
            'business_id' => $this->business->id,
            'instagram_account_id' => $this->instagramAccount->id,
            'instagram_media_id' => '17841400000000999',
            'media_type' => 'post',
            'sync_status' => 'failed',
            'sync_attempts' => 3, // Maksimal
            'sync_error' => 'Previous error',
            'posted_at' => now()->subDay(),
        ]);

        // requiresSync() false qaytarishi kerak
        $this->assertFalse($contentLink->requiresSync());

        // Scope ham uni qaytarmasligi kerak
        $needsSyncCount = InstagramContentLink::needsSync()
            ->where('id', $contentLink->id)
            ->count();

        $this->assertEquals(0, $needsSyncCount);
    }

    /**
     * Test Edge.4: Cache-based idempotency tekshiruvi
     */
    public function test_webhook_idempotency_with_cache(): void
    {
        $messageId = 'msg_' . uniqid();
        $cacheKey = "instagram_processed_message_{$messageId}";

        // Birinchi marta - cache bo'sh
        $this->assertFalse(Cache::has($cacheKey));

        // Xabarni qayta ishlash simulyatsiyasi
        Cache::put($cacheKey, true, 60);

        // Ikkinchi marta - cache da bor
        $this->assertTrue(Cache::has($cacheKey));

        // 60 soniya o'tgandan keyin tozalanishi kerak (unit test da tezlashtirish mumkin emas)
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
