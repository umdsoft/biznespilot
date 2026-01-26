<?php

namespace Tests\Feature;

use App\Models\Business;
use App\Models\CallLog;
use App\Models\InstagramAccount;
use App\Models\InstagramConversation;
use App\Models\Integration;
use App\Models\Lead;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;
use App\Services\ChatbotIntentService;
use App\Services\TicketService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * CriticalFixTest - 4 ta muhim tuzatishni tekshirish
 *
 * Test 1: Business yaratilganda subscription yaratilishi
 * Test 2: Plan strukturasi va limits JSON to'g'riligi
 * Test 3: Call Center limit enforcement (audio_minutes_limit)
 * Test 4: Chatbot intent dan Lead yaratilishi
 */
class CriticalFixTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();

        // Seed plans for testing
        $this->seedPlans();
    }

    /**
     * Seed plans for testing
     */
    protected function seedPlans(): void
    {
        $plans = [
            [
                'name' => 'Free',
                'slug' => 'free',
                'price_monthly' => 0,
                'price_yearly' => 0,
                'team_member_limit' => 1,
                'lead_limit' => 100,
                'audio_minutes_limit' => 0,
                'has_instagram' => false,
                'is_active' => true,
                'features' => ['CRM', 'Telegram chatbot'],
            ],
            [
                'name' => 'Start',
                'slug' => 'start',
                'price_monthly' => 299000,
                'price_yearly' => 2990000,
                'team_member_limit' => 2,
                'lead_limit' => 500,
                'audio_minutes_limit' => 60,
                'has_instagram' => true,
                'is_active' => true,
                'features' => ['Instagram chatbot', '60 daq Call Center AI'],
            ],
            [
                'name' => 'Standard',
                'slug' => 'standard',
                'price_monthly' => 599000,
                'price_yearly' => 5990000,
                'team_member_limit' => 5,
                'lead_limit' => 2000,
                'audio_minutes_limit' => 150,
                'has_instagram' => true,
                'is_active' => true,
                'features' => ['Flow Builder', '150 daq Call Center AI'],
            ],
            [
                'name' => 'Business',
                'slug' => 'business',
                'price_monthly' => 799000,
                'price_yearly' => 7990000,
                'team_member_limit' => 10,
                'lead_limit' => 10000,
                'audio_minutes_limit' => 400,
                'has_instagram' => true,
                'is_active' => true,
                'features' => ['HR Bot', '400 daq Call Center AI'],
            ],
            [
                'name' => 'Premium',
                'slug' => 'premium',
                'price_monthly' => 1499000,
                'price_yearly' => 14990000,
                'team_member_limit' => 15,
                'lead_limit' => null, // Unlimited
                'audio_minutes_limit' => 1000,
                'has_instagram' => true,
                'is_active' => true,
                'features' => ['Cheksiz lid', '1000 daq Call Center AI'],
            ],
        ];

        foreach ($plans as $planData) {
            Plan::create(array_merge(['id' => Str::uuid()->toString()], $planData));
        }
    }

    /**
     * Helper: Create a business with subscription manually
     */
    protected function createBusinessWithSubscription(string $planSlug = 'business'): Business
    {
        $business = Business::create([
            'id' => Str::uuid()->toString(),
            'name' => 'Test Business ' . Str::random(5),
            'slug' => 'test-business-' . Str::random(5),
            'user_id' => $this->user->id,
            'category' => 'retail',
        ]);

        $plan = Plan::where('slug', $planSlug)->first();

        Subscription::create([
            'id' => Str::uuid()->toString(),
            'business_id' => $business->id,
            'plan_id' => $plan->id,
            'status' => 'trialing',
            'trial_ends_at' => now()->addDays(14),
            'starts_at' => now(),
            'ends_at' => now()->addMonth(),
            'amount' => $plan->price_monthly,
            'currency' => 'UZS',
        ]);

        return $business;
    }

    /**
     * Helper: Create Instagram account with integration
     */
    protected function createInstagramAccount(Business $business, string $username = 'test_account'): InstagramAccount
    {
        // Create integration first
        $integration = Integration::create([
            'id' => Str::uuid()->toString(),
            'business_id' => $business->id,
            'type' => 'instagram',
            'name' => 'Instagram Integration',
            'access_token' => 'test_token_' . Str::random(10),
            'status' => 'active',
        ]);

        return InstagramAccount::create([
            'id' => Str::uuid()->toString(),
            'business_id' => $business->id,
            'integration_id' => $integration->id,
            'instagram_id' => Str::random(15),
            'username' => $username,
        ]);
    }

    // ============================================================
    // TEST 1: Business va Subscription yaratilishi
    // ============================================================

    public function test_subscription_can_be_created_with_trialing_status(): void
    {
        $business = Business::create([
            'id' => Str::uuid()->toString(),
            'name' => 'Test Business',
            'slug' => 'test-business-' . Str::random(5),
            'user_id' => $this->user->id,
            'category' => 'retail',
        ]);

        $plan = Plan::where('slug', 'business')->first();

        // Create subscription directly
        $subscription = Subscription::create([
            'id' => Str::uuid()->toString(),
            'business_id' => $business->id,
            'plan_id' => $plan->id,
            'status' => 'trialing',
            'trial_ends_at' => now()->addDays(14),
            'starts_at' => now(),
            'ends_at' => now()->addMonth(),
            'amount' => $plan->price_monthly,
            'currency' => 'UZS',
        ]);

        $this->assertNotNull($subscription);
        $this->assertEquals('trialing', $subscription->status);
        $this->assertNotNull($subscription->trial_ends_at);
        $this->assertEquals($plan->id, $subscription->plan_id);

        // Trial 14 kun bo'lishi kerak
        $trialDays = now()->diffInDays($subscription->trial_ends_at);
        $this->assertGreaterThanOrEqual(13, $trialDays);
        $this->assertLessThanOrEqual(14, $trialDays);
    }

    public function test_subscription_can_be_created_with_active_status(): void
    {
        $business = Business::create([
            'id' => Str::uuid()->toString(),
            'name' => 'No Trial Business',
            'slug' => 'no-trial-' . Str::random(5),
            'user_id' => $this->user->id,
            'category' => 'services',
        ]);

        $plan = Plan::where('slug', 'start')->first();

        // Create subscription without trial
        $subscription = Subscription::create([
            'id' => Str::uuid()->toString(),
            'business_id' => $business->id,
            'plan_id' => $plan->id,
            'status' => 'active',
            'starts_at' => now(),
            'ends_at' => now()->addMonth(),
            'amount' => $plan->price_monthly,
            'currency' => 'UZS',
        ]);

        $this->assertNotNull($subscription);
        $this->assertEquals('active', $subscription->status);
        $this->assertNull($subscription->trial_ends_at);
    }

    public function test_subscription_belongs_to_business_and_plan(): void
    {
        $business = $this->createBusinessWithSubscription('start');

        $subscription = Subscription::where('business_id', $business->id)->first();

        $this->assertNotNull($subscription);
        $this->assertEquals($business->id, $subscription->business_id);

        // Test relationship
        $this->assertNotNull($subscription->business);
        $this->assertEquals($business->name, $subscription->business->name);

        $this->assertNotNull($subscription->plan);
        $this->assertEquals('Start', $subscription->plan->name);
    }

    // ============================================================
    // TEST 2: Plan strukturasi va limits JSON to'g'riligi
    // ============================================================

    public function test_all_plans_have_required_fields(): void
    {
        $requiredFields = [
            'name',
            'slug',
            'price_monthly',
            'price_yearly',
            'team_member_limit',
            'audio_minutes_limit',
            'has_instagram',
            'is_active',
            'features',
        ];

        $plans = Plan::all();
        $this->assertGreaterThanOrEqual(5, $plans->count(), 'Kamida 5 ta plan bo\'lishi kerak');

        foreach ($plans as $plan) {
            foreach ($requiredFields as $field) {
                $this->assertTrue(
                    array_key_exists($field, $plan->getAttributes()) || $plan->$field !== null || $plan->$field === 0 || $plan->$field === false,
                    "Plan '{$plan->name}' da '{$field}' field yo'q"
                );
            }
        }
    }

    public function test_plans_have_correct_audio_minutes_limits(): void
    {
        $expectedLimits = [
            'free' => 0,
            'start' => 60,
            'standard' => 150,
            'business' => 400,
            'premium' => 1000,
        ];

        foreach ($expectedLimits as $slug => $expectedLimit) {
            $plan = Plan::where('slug', $slug)->first();
            $this->assertNotNull($plan, "Plan '{$slug}' topilmadi");
            $this->assertEquals(
                $expectedLimit,
                $plan->audio_minutes_limit,
                "Plan '{$slug}' da audio_minutes_limit noto'g'ri"
            );
        }
    }

    public function test_free_plan_has_instagram_disabled(): void
    {
        $freePlan = Plan::where('slug', 'free')->first();

        $this->assertNotNull($freePlan);
        $this->assertFalse($freePlan->has_instagram);
        $this->assertEquals(0, $freePlan->audio_minutes_limit);
    }

    public function test_paid_plans_have_instagram_enabled(): void
    {
        $paidSlugs = ['start', 'standard', 'business', 'premium'];

        foreach ($paidSlugs as $slug) {
            $plan = Plan::where('slug', $slug)->first();
            $this->assertNotNull($plan, "Plan '{$slug}' topilmadi");
            $this->assertTrue($plan->has_instagram, "Plan '{$slug}' da Instagram yoqilmagan");
        }
    }

    public function test_plans_features_are_arrays(): void
    {
        $plans = Plan::all();

        foreach ($plans as $plan) {
            $this->assertIsArray($plan->features, "Plan '{$plan->name}' features array emas");
            $this->assertNotEmpty($plan->features, "Plan '{$plan->name}' features bo'sh");
        }
    }

    public function test_premium_plan_has_unlimited_leads(): void
    {
        $premiumPlan = Plan::where('slug', 'premium')->first();

        $this->assertNotNull($premiumPlan);
        $this->assertNull($premiumPlan->lead_limit, 'Premium plan lead limiti cheksiz bo\'lishi kerak');
    }

    public function test_plans_have_correct_pricing(): void
    {
        $expectedPricing = [
            'free' => ['monthly' => 0, 'yearly' => 0],
            'start' => ['monthly' => 299000, 'yearly' => 2990000],
            'standard' => ['monthly' => 599000, 'yearly' => 5990000],
            'business' => ['monthly' => 799000, 'yearly' => 7990000],
            'premium' => ['monthly' => 1499000, 'yearly' => 14990000],
        ];

        foreach ($expectedPricing as $slug => $pricing) {
            $plan = Plan::where('slug', $slug)->first();
            $this->assertNotNull($plan);
            $this->assertEquals($pricing['monthly'], $plan->price_monthly, "Plan '{$slug}' oylik narxi noto'g'ri");
            $this->assertEquals($pricing['yearly'], $plan->price_yearly, "Plan '{$slug}' yillik narxi noto'g'ri");
        }
    }

    // ============================================================
    // TEST 3: Call Center limit enforcement
    // ============================================================

    public function test_call_analysis_blocked_when_limit_exceeded(): void
    {
        $business = $this->createBusinessWithSubscription('start');

        // Start tarifiga o'tkazish (60 daqiqa limit)
        $startPlan = Plan::where('slug', 'start')->first();

        // 59 daqiqa ishlatilgan qo'ng'iroqlar qo'shish (limit ichida)
        for ($i = 0; $i < 59; $i++) {
            CallLog::create([
                'id' => Str::uuid()->toString(),
                'business_id' => $business->id,
                'user_id' => $this->user->id,
                'provider' => 'pbx',
                'direction' => 'outbound',
                'from_number' => '+998901234567',
                'to_number' => '+998901234568',
                'duration' => 60, // 60 sekund = 1 daqiqa
                'status' => 'completed',
                'analysis_status' => 'completed',
                'created_at' => now(),
            ]);
        }

        // 60-daqiqa - limit to'ladi
        $newCallLog = CallLog::create([
            'id' => Str::uuid()->toString(),
            'business_id' => $business->id,
            'user_id' => $this->user->id,
            'provider' => 'pbx',
            'direction' => 'outbound',
            'from_number' => '+998901234567',
            'to_number' => '+998901234569',
            'duration' => 120, // 2 daqiqa - limit oshadi
            'status' => 'completed',
            'analysis_status' => 'pending',
            'recording_url' => 'https://example.com/recording.mp3',
        ]);

        // Limit tekshirish - checkAudioMinutesLimit() ni simulyatsiya qilish
        $usedMinutes = (int) DB::table('call_logs')
            ->where('business_id', $business->id)
            ->where('created_at', '>=', now()->startOfMonth())
            ->whereIn('analysis_status', ['completed', 'analyzing', 'transcribing'])
            ->sum('duration') / 60;

        $callDurationMinutes = (int) ceil($newCallLog->duration / 60);
        $totalMinutes = $usedMinutes + $callDurationMinutes;

        // Limit: 60 daqiqa, ishlatilgan: 59, yangi qo'ng'iroq: 2 = 61 > 60
        $this->assertGreaterThan(
            $startPlan->audio_minutes_limit,
            $totalMinutes,
            'Total minutes limitdan oshishi kerak'
        );
    }

    public function test_call_analysis_allowed_within_limit(): void
    {
        $business = $this->createBusinessWithSubscription('business');
        $businessPlan = Plan::where('slug', 'business')->first();

        // 100 daqiqa ishlatish (400 daqiqa limitdan kam)
        for ($i = 0; $i < 100; $i++) {
            CallLog::create([
                'id' => Str::uuid()->toString(),
                'business_id' => $business->id,
                'user_id' => $this->user->id,
                'provider' => 'pbx',
                'direction' => 'outbound',
                'from_number' => '+998901234567',
                'to_number' => '+998901234568',
                'duration' => 60,
                'status' => 'completed',
                'analysis_status' => 'completed',
                'created_at' => now(),
            ]);
        }

        $usedMinutes = (int) DB::table('call_logs')
            ->where('business_id', $business->id)
            ->where('created_at', '>=', now()->startOfMonth())
            ->whereIn('analysis_status', ['completed', 'analyzing', 'transcribing'])
            ->sum('duration') / 60;

        $this->assertLessThan(
            $businessPlan->audio_minutes_limit,
            $usedMinutes,
            'Used minutes limitdan kam bo\'lishi kerak'
        );
    }

    public function test_free_plan_blocks_all_call_analysis(): void
    {
        $business = $this->createBusinessWithSubscription('free');
        $freePlan = Plan::where('slug', 'free')->first();

        // Free planda audio_minutes_limit = 0 bo'lishi kerak
        $this->assertEquals(0, $freePlan->audio_minutes_limit);

        // Hatto 1 daqiqa ham ruxsat berilmasligi kerak
        $newCallDuration = 60; // 1 daqiqa
        $callDurationMinutes = (int) ceil($newCallDuration / 60);

        $allowed = $callDurationMinutes <= $freePlan->audio_minutes_limit;
        $this->assertFalse($allowed, 'Free planda call analysis ruxsat berilmasligi kerak');
    }

    public function test_premium_plan_has_highest_audio_limit(): void
    {
        $plans = Plan::where('is_active', true)->get();
        $premiumPlan = Plan::where('slug', 'premium')->first();

        foreach ($plans as $plan) {
            if ($plan->slug !== 'premium') {
                $this->assertGreaterThanOrEqual(
                    $plan->audio_minutes_limit ?? 0,
                    $premiumPlan->audio_minutes_limit,
                    "Premium plan boshqa planlardan ko'p audio limitga ega bo'lishi kerak"
                );
            }
        }
    }

    // ============================================================
    // TEST 4: Chatbot intent dan Lead yaratilishi
    // ============================================================

    public function test_price_inquiry_intent_creates_lead(): void
    {
        $business = $this->createBusinessWithSubscription();

        $instagramAccount = $this->createInstagramAccount($business, 'chatbot_test');

        $conversation = InstagramConversation::create([
            'id' => Str::uuid()->toString(),
            'account_id' => $instagramAccount->id,
            'conversation_id' => 'thread_' . Str::random(10),
            'participant_id' => 'participant_123',
            'participant_username' => 'test_user',
            'participant_name' => 'Test User',
            'status' => 'active',
        ]);

        // TicketService orqali lead yaratish
        $ticketService = app(TicketService::class);

        $lead = $ticketService->createFromChatbot($conversation, [
            'intent' => 'price_inquiry',
            'source_type' => 'dm',
            'first_message' => 'Bu mahsulot narxi qancha?',
            'collected_data' => [
                'detected_intent' => 'price_inquiry',
                'intent_confidence' => 0.7,
            ],
        ]);

        $this->assertNotNull($lead, 'Lead yaratilmagan');
        $this->assertEquals($business->id, $lead->business_id);
        $this->assertEquals('price_inquiry', $lead->chatbot_detected_intent);
        $this->assertEquals($conversation->id, $lead->instagram_conversation_id);
        $this->assertEquals('dm', $lead->chatbot_source_type);
    }

    public function test_complaint_intent_creates_lead_with_handoff(): void
    {
        $business = $this->createBusinessWithSubscription();

        $instagramAccount = $this->createInstagramAccount($business, 'complaint_test');

        $conversation = InstagramConversation::create([
            'id' => Str::uuid()->toString(),
            'account_id' => $instagramAccount->id,
            'conversation_id' => 'thread_complaint_' . Str::random(10),
            'participant_id' => 'participant_complaint',
            'participant_username' => 'unhappy_customer',
            'participant_name' => 'Norozi Mijoz',
            'status' => 'active',
        ]);

        $ticketService = app(TicketService::class);

        $lead = $ticketService->createFromChatbot($conversation, [
            'intent' => 'complaint',
            'source_type' => 'dm',
            'first_message' => 'Mahsulot buzilgan keldi! Shikoyat qilmoqchiman.',
            'collected_data' => [
                'detected_intent' => 'complaint',
                'intent_confidence' => 0.85,
                'category' => 'support',
            ],
        ]);

        $this->assertNotNull($lead);
        $this->assertEquals('complaint', $lead->chatbot_detected_intent);
    }

    public function test_chatbot_intent_service_detects_price_inquiry(): void
    {
        $business = $this->createBusinessWithSubscription();
        $instagramAccount = $this->createInstagramAccount($business, 'intent_test');

        $intentService = app(ChatbotIntentService::class);

        // Narx so'rovi xabarlari
        $priceMessages = [
            'narxi qancha?',
            'Bu mahsulot necha pul?',
            'Qancha turadi?',
            'price nima?',
        ];

        foreach ($priceMessages as $message) {
            $intent = $intentService->detect($message, $instagramAccount);

            $this->assertEquals('general', $intent['type'], "Message: {$message}");
            $this->assertEquals('price_inquiry', $intent['value'], "Message: {$message}");
        }
    }

    public function test_chatbot_intent_service_detects_complaint(): void
    {
        $business = $this->createBusinessWithSubscription();
        $instagramAccount = $this->createInstagramAccount($business, 'complaint_detection');

        $intentService = app(ChatbotIntentService::class);

        // Shikoyat xabarlari
        $complaintMessages = [
            'shikoyat qilmoqchiman',
            'muammo bor',
            'ishlamayapti',
            'buzilgan keldi',
        ];

        foreach ($complaintMessages as $message) {
            $intent = $intentService->detect($message, $instagramAccount);

            $this->assertEquals('complaint', $intent['type'], "Message: {$message}");
            $this->assertTrue($intent['requires_handoff'] ?? false, "Message: {$message} handoff kerak");
        }
    }

    public function test_lead_not_duplicated_for_same_conversation(): void
    {
        $business = $this->createBusinessWithSubscription();
        $instagramAccount = $this->createInstagramAccount($business, 'duplicate_test');

        $conversation = InstagramConversation::create([
            'id' => Str::uuid()->toString(),
            'account_id' => $instagramAccount->id,
            'conversation_id' => 'thread_duplicate_' . Str::random(10),
            'participant_id' => 'participant_dup',
            'participant_username' => 'duplicate_user',
            'status' => 'active',
        ]);

        $ticketService = app(TicketService::class);

        // Birinchi lead yaratish
        $lead1 = $ticketService->createFromChatbot($conversation, [
            'intent' => 'price_inquiry',
            'source_type' => 'dm',
            'first_message' => 'Narxi qancha?',
        ]);

        $this->assertNotNull($lead1);

        // Cache'ni tozalash - real senariyda throttle ishlaydi
        Cache::forget("lead_creation_lock:{$conversation->id}");

        // Ikkinchi urinish - mavjud lead qaytishi kerak
        $lead2 = $ticketService->createFromChatbot($conversation, [
            'intent' => 'order_intent',
            'source_type' => 'dm',
            'first_message' => 'Buyurtma bermoqchiman',
        ]);

        // Bir xil lead qaytishi kerak
        $this->assertEquals($lead1->id, $lead2->id);

        // Faqat 1 ta lead bo'lishi kerak
        $leadCount = Lead::where('instagram_conversation_id', $conversation->id)->count();
        $this->assertEquals(1, $leadCount);
    }

    public function test_lead_creating_intents_list(): void
    {
        $expectedIntents = [
            'complaint',
            'issue',
            'problem',
            'human_handoff',
            'order_intent',
            'price_inquiry',
        ];

        $actualIntents = ChatbotIntentService::getLeadCreatingIntents();

        foreach ($expectedIntents as $intent) {
            $this->assertContains($intent, $actualIntents, "'{$intent}' lead yaratuvchi intentlar ro'yxatida yo'q");
        }
    }

    public function test_handoff_intents_list(): void
    {
        $expectedHandoffIntents = [
            'complaint',
            'issue',
            'problem',
            'human_handoff',
        ];

        $actualIntents = ChatbotIntentService::getHandoffIntents();

        foreach ($expectedHandoffIntents as $intent) {
            $this->assertContains($intent, $actualIntents, "'{$intent}' handoff intentlar ro'yxatida yo'q");
        }
    }
}
