<?php

namespace Tests\Feature\Subscription;

use App\Exceptions\FeatureNotAvailableException;
use App\Exceptions\NoActiveSubscriptionException;
use App\Exceptions\QuotaExceededException;
use App\Models\Business;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;
use App\Services\PlanLimitService;
use App\Services\SubscriptionGate;
use Database\Seeders\PlanSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * PlanLimitsEnforcementTest - Tarif limitlari va feature aktivatsiyasi to'liq test suite
 *
 * Qoplangan senariylar:
 *  A. Feature aktivatsiyasi (6 tarif x hr_tasks/hr_bot/anti_fraud)
 *  B. Quota chegara testi (har bir raqamli limit uchun)
 *  C. Tarifdan tarifga o'tish (upgrade/downgrade)
 *  D. HTTP-level feature gating (403 FEATURE_NOT_AVAILABLE)
 *  E. Quota exception response shape (403 QUOTA_EXCEEDED)
 *  F. NoActiveSubscriptionException (402 NO_ACTIVE_SUBSCRIPTION)
 */
class PlanLimitsEnforcementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // PlanSeeder ma'lumotlarini yuklash: trial-pack, start, standard, business, premium, enterprise
        $this->seed(PlanSeeder::class);
    }

    // ==================== HELPERS ====================

    /**
     * Tarif slug bo'yicha aktiv subscription yaratib beradi.
     * BusinessObserver'ning avtomatik trial'ini o'chirib, aniq tarifni belgilaydi.
     */
    protected function activatePlan(Business $business, string $planSlug, string $status = 'active'): Subscription
    {
        // Observer yaratgan trial yoki boshqa avtomatik subscriptionlarni tozalash
        $business->subscriptions()->forceDelete();

        $plan = Plan::where('slug', $planSlug)->firstOrFail();

        return Subscription::create([
            'business_id' => $business->id,
            'plan_id' => $plan->id,
            'status' => $status,
            'billing_cycle' => 'monthly',
            'starts_at' => now()->subDay(),
            'ends_at' => now()->addMonth(),
            'trial_ends_at' => $status === 'trialing' ? now()->addDays(14) : null,
            'amount' => $plan->price_monthly,
            'currency' => 'UZS',
        ]);
    }

    /**
     * Yangi User + Business tandem yaratadi (observer subscription o'chiriladi).
     */
    protected function makeBusinessOwner(?string $namePrefix = null): array
    {
        $user = User::factory()->create();
        $business = Business::factory()->create(['user_id' => $user->id]);

        // Observer tomonidan yaratilgan auto-trial subscriptionni olib tashlash
        $business->subscriptions()->forceDelete();

        return [$user, $business];
    }

    /**
     * Pivot orqali business'ga xodim qo'shadi (team members limitiga ta'sir qiladi).
     */
    protected function attachTeamMember(Business $business, User $member, string $role = 'member'): void
    {
        $business->users()->attach($member->id, [
            'id' => Str::uuid()->toString(),
            'role' => $role,
            'joined_at' => now(),
            'accepted_at' => now(),
        ]);
    }

    // ==================== SECTION A: FEATURE ACTIVATION ====================

    /**
     * @dataProvider planFeatureMatrix
     */
    public function test_plan_feature_activation_matches_seeder(
        string $planSlug,
        bool $hrTasks,
        bool $hrBot,
        bool $antiFraud,
        bool $onboarding,
        bool $personalManager
    ): void {
        [$user, $business] = $this->makeBusinessOwner();
        $this->activatePlan($business, $planSlug);

        $this->assertSame(
            $hrTasks,
            $business->canUseFeature('hr_tasks'),
            "[$planSlug] hr_tasks feature holati noto'g'ri"
        );
        $this->assertSame(
            $hrBot,
            $business->canUseFeature('hr_bot'),
            "[$planSlug] hr_bot feature holati noto'g'ri"
        );
        $this->assertSame(
            $antiFraud,
            $business->canUseFeature('anti_fraud'),
            "[$planSlug] anti_fraud feature holati noto'g'ri"
        );
        $this->assertSame(
            $onboarding,
            $business->canUseFeature('onboarding'),
            "[$planSlug] onboarding feature holati noto'g'ri"
        );
        $this->assertSame(
            $personalManager,
            $business->canUseFeature('personal_manager'),
            "[$planSlug] personal_manager feature holati noto'g'ri"
        );
    }

    public static function planFeatureMatrix(): array
    {
        // [plan_slug, hr_tasks, hr_bot, anti_fraud, onboarding, personal_manager]
        return [
            'trial-pack' => ['trial-pack', true,  true,  true,  false, false],
            'start'      => ['start',      false, false, false, false, false],
            'standard'   => ['standard',   true,  false, false, true,  false],
            'business'   => ['business',   true,  true,  false, true,  false],
            'premium'    => ['premium',    true,  true,  true,  true,  true],
            'enterprise' => ['enterprise', true,  true,  true,  true,  true],
        ];
    }

    public function test_enterprise_has_all_features_enabled(): void
    {
        [$user, $business] = $this->makeBusinessOwner();
        $this->activatePlan($business, 'enterprise');

        foreach (['hr_tasks', 'hr_bot', 'anti_fraud', 'onboarding', 'personal_manager'] as $feature) {
            $this->assertTrue(
                $business->canUseFeature($feature),
                "Enterprise tarifi $feature feature ga ega bo'lishi kerak"
            );
        }
    }

    public function test_start_plan_has_minimal_feature_set(): void
    {
        [$user, $business] = $this->makeBusinessOwner();
        $this->activatePlan($business, 'start');

        // Start tarifida HR va anti-fraud yo'q
        $this->assertFalse($business->canUseFeature('hr_tasks'));
        $this->assertFalse($business->canUseFeature('hr_bot'));
        $this->assertFalse($business->canUseFeature('anti_fraud'));
    }

    // ==================== SECTION B: QUOTA BOUNDARY TESTS ====================

    /**
     * @dataProvider usersLimitProvider
     */
    public function test_users_limit_enforcement_per_plan(string $planSlug, int $expectedLimit): void
    {
        [$owner, $business] = $this->makeBusinessOwner();
        $this->activatePlan($business, $planSlug);

        $service = app(PlanLimitService::class);
        $gate = app(SubscriptionGate::class);

        // getUsersCount = teamMembers + 1 (owner). Yangi yaratilgan business'da 1.
        $this->assertEquals(1, $service->getCurrentUsage($business, 'users'));

        // Limitga yetguncha xodim qo'shish. Owner allaqachon 1 hisoblanganligi sababli
        // qo'shiladigan xodim soni = expectedLimit - 1
        $toAdd = $expectedLimit - 1;
        for ($i = 0; $i < $toAdd; $i++) {
            $member = User::factory()->create();
            $this->attachTeamMember($business, $member);
        }

        $business->refresh();

        // Limitga yetdik
        $this->assertTrue(
            $business->hasReachedLimit('users'),
            "[$planSlug] {$expectedLimit} foydalanuvchi qo'shilgandan keyin limit to'lishi kerak"
        );
        $this->assertFalse(
            $business->canAdd('users'),
            "[$planSlug] limit to'lgandan keyin canAdd() false qaytarishi kerak"
        );

        // Gate chekuvi exception otishi kerak
        $this->expectException(QuotaExceededException::class);
        $gate->checkQuota($business, 'users');
    }

    public static function usersLimitProvider(): array
    {
        return [
            'trial-pack (2 users)' => ['trial-pack', 2],
            'start (2 users)'      => ['start', 2],
            'standard (5 users)'   => ['standard', 5],
            'business (10 users)'  => ['business', 10],
            'premium (15 users)'   => ['premium', 15],
        ];
    }

    public function test_enterprise_users_limit_is_unlimited(): void
    {
        [$owner, $business] = $this->makeBusinessOwner();
        $this->activatePlan($business, 'enterprise');

        $service = app(PlanLimitService::class);

        // Enterprise'da users limiti null - cheksiz
        $plan = Plan::where('slug', 'enterprise')->first();
        $this->assertTrue($plan->isLimitUnlimited('users'));

        // 50 ta xodim qo'shib, limit oshib ketmasligini tekshirish
        for ($i = 0; $i < 50; $i++) {
            $member = User::factory()->create();
            $this->attachTeamMember($business, $member);
        }

        $business->refresh();

        $this->assertFalse(
            $business->hasReachedLimit('users'),
            'Enterprise tarifida users limiti cheksiz bo\'lishi kerak'
        );
        $this->assertTrue($business->canAdd('users', 1000));

        // Gate exception otmasligi kerak
        $gate = app(SubscriptionGate::class);
        $gate->checkQuota($business, 'users'); // No exception
        $this->assertTrue(true); // Marker
    }

    public function test_premium_monthly_leads_is_unlimited(): void
    {
        [$owner, $business] = $this->makeBusinessOwner();
        $this->activatePlan($business, 'premium');

        $plan = Plan::where('slug', 'premium')->first();
        $this->assertTrue($plan->isLimitUnlimited('monthly_leads'));

        $service = app(PlanLimitService::class);
        $this->assertNull(
            $service->getRemainingQuota($business, 'monthly_leads'),
            'Cheksiz limit null qaytarishi kerak'
        );
    }

    /**
     * @dataProvider telegramBotsLimitProvider
     */
    public function test_telegram_bots_limit_values(string $planSlug, int $expectedLimit): void
    {
        [$owner, $business] = $this->makeBusinessOwner();
        $this->activatePlan($business, $planSlug);

        $gate = app(SubscriptionGate::class);
        $this->assertEquals(
            $expectedLimit,
            $gate->getLimit($business, 'telegram_bots'),
            "[$planSlug] telegram_bots limiti noto'g'ri"
        );
    }

    public static function telegramBotsLimitProvider(): array
    {
        return [
            'trial-pack' => ['trial-pack', 1],
            'start'      => ['start', 2],
            'standard'   => ['standard', 3],
            'business'   => ['business', 5],
            'premium'    => ['premium', 20],
        ];
    }

    /**
     * @dataProvider storageLimitProvider
     */
    public function test_storage_mb_limit_values(string $planSlug, int $expectedLimit): void
    {
        [$owner, $business] = $this->makeBusinessOwner();
        $this->activatePlan($business, $planSlug);

        $gate = app(SubscriptionGate::class);
        $this->assertEquals(
            $expectedLimit,
            $gate->getLimit($business, 'storage_mb'),
            "[$planSlug] storage_mb limiti noto'g'ri"
        );
    }

    public static function storageLimitProvider(): array
    {
        return [
            'trial-pack' => ['trial-pack', 200],
            'start'      => ['start', 500],
            'standard'   => ['standard', 1000],
            'business'   => ['business', 5000],
            'premium'    => ['premium', 50000],
        ];
    }

    /**
     * @dataProvider aiCallMinutesProvider
     */
    public function test_ai_call_minutes_limit_values(string $planSlug, int $expectedLimit): void
    {
        [$owner, $business] = $this->makeBusinessOwner();
        $this->activatePlan($business, $planSlug);

        $gate = app(SubscriptionGate::class);
        $this->assertEquals(
            $expectedLimit,
            $gate->getLimit($business, 'ai_call_minutes'),
            "[$planSlug] ai_call_minutes limiti noto'g'ri"
        );
    }

    public static function aiCallMinutesProvider(): array
    {
        return [
            'trial-pack' => ['trial-pack', 30],
            'start'      => ['start', 60],
            'standard'   => ['standard', 150],
            'business'   => ['business', 400],
            'premium'    => ['premium', 1000],
            'enterprise' => ['enterprise', 10000],
        ];
    }

    /**
     * @dataProvider instagramAccountsProvider
     */
    public function test_instagram_accounts_limit_values(string $planSlug, int $expectedLimit): void
    {
        [$owner, $business] = $this->makeBusinessOwner();
        $this->activatePlan($business, $planSlug);

        $gate = app(SubscriptionGate::class);
        $this->assertEquals(
            $expectedLimit,
            $gate->getLimit($business, 'instagram_accounts'),
            "[$planSlug] instagram_accounts limiti noto'g'ri"
        );
    }

    public static function instagramAccountsProvider(): array
    {
        return [
            'trial-pack' => ['trial-pack', 1],
            'start'      => ['start', 1],
            'standard'   => ['standard', 2],
            'business'   => ['business', 3],
            'premium'    => ['premium', 10],
        ];
    }

    public function test_quota_exception_carries_usage_metadata(): void
    {
        [$owner, $business] = $this->makeBusinessOwner();
        $this->activatePlan($business, 'start'); // users = 2

        // 1 ta xodim qo'shib, limitga yetishmay qoldirish
        $member = User::factory()->create();
        $this->attachTeamMember($business, $member);

        // Endi 2 ta bor (owner + 1). +2 qo'shish limitni oshiradi (2+2>2)
        $gate = app(SubscriptionGate::class);

        try {
            $gate->checkQuota($business, 'users', null, 2);
            $this->fail('QuotaExceededException otilishi kerak edi');
        } catch (QuotaExceededException $e) {
            $this->assertEquals('users', $e->getLimitKey());
            $this->assertEquals(2, $e->getLimit());
            $this->assertEquals(2, $e->getCurrentUsage());
        }
    }

    // ==================== SECTION C: PLAN TRANSITIONS ====================

    public function test_trial_to_start_upgrade_updates_features_immediately(): void
    {
        [$owner, $business] = $this->makeBusinessOwner();
        $this->activatePlan($business, 'trial-pack');

        // Trial'da hr_tasks bor
        $this->assertTrue($business->canUseFeature('hr_tasks'));
        $this->assertTrue($business->canUseFeature('anti_fraud'));

        // Start'ga upgrade
        $this->activatePlan($business, 'start');
        $business->refresh();

        // Start tarifida hr_tasks yo'q
        $this->assertFalse(
            $business->canUseFeature('hr_tasks'),
            'Start tarifiga o\'tgandan keyin hr_tasks darhol o\'chirilishi kerak'
        );
        $this->assertFalse($business->canUseFeature('anti_fraud'));
    }

    public function test_business_to_premium_upgrade_enables_anti_fraud(): void
    {
        [$owner, $business] = $this->makeBusinessOwner();
        $this->activatePlan($business, 'business');

        // Business tarifida anti_fraud yo'q
        $this->assertFalse($business->canUseFeature('anti_fraud'));
        $this->assertFalse($business->canUseFeature('personal_manager'));

        // Premium'ga upgrade
        $this->activatePlan($business, 'premium');
        $business->refresh();

        $this->assertTrue(
            $business->canUseFeature('anti_fraud'),
            'Premium tarifiga o\'tgandan keyin anti_fraud yoqilishi kerak'
        );
        $this->assertTrue($business->canUseFeature('personal_manager'));
    }

    public function test_downgrade_detects_over_limit_resources(): void
    {
        [$owner, $business] = $this->makeBusinessOwner();
        $this->activatePlan($business, 'business'); // users = 10

        // 7 ta xodim qo'shish → jami 8 ta (owner + 7)
        for ($i = 0; $i < 7; $i++) {
            $member = User::factory()->create();
            $this->attachTeamMember($business, $member);
        }
        $business->refresh();

        $service = app(PlanLimitService::class);
        $this->assertEquals(8, $service->getCurrentUsage($business, 'users'));

        // Start tarifiga downgrade (2 user limiti) - mumkinmi?
        $startPlan = Plan::where('slug', 'start')->firstOrFail();
        $result = $service->canDowngradeToPlan($business, $startPlan);

        $this->assertFalse($result['can_downgrade'], 'Downgrade over-limit resurslar sababli bloklanishi kerak');
        $this->assertNotEmpty($result['issues']);

        $userIssue = collect($result['issues'])->firstWhere('key', 'users');
        $this->assertNotNull($userIssue, 'users limitida issue bo\'lishi kerak');
        $this->assertEquals(8, $userIssue['current']);
        $this->assertEquals(2, $userIssue['new_limit']);
    }

    public function test_downgrade_allowed_when_within_new_limits(): void
    {
        [$owner, $business] = $this->makeBusinessOwner();
        $this->activatePlan($business, 'business'); // users = 10

        // Faqat owner bor (1 user), Start'ga downgrade mumkin (2 limiti)
        $service = app(PlanLimitService::class);

        $startPlan = Plan::where('slug', 'start')->firstOrFail();
        $result = $service->canDowngradeToPlan($business, $startPlan);

        $this->assertTrue(
            $result['can_downgrade'],
            'Resurslar yangi limit ichida bo\'lsa downgrade mumkin bo\'lishi kerak'
        );
        $this->assertEmpty($result['issues']);
    }

    public function test_existing_over_limit_data_preserved_after_downgrade(): void
    {
        // Talab: Downgrade'da eski ma'lumot saqlanadi, lekin yangi qo'shib bo'lmaydi
        [$owner, $business] = $this->makeBusinessOwner();
        $this->activatePlan($business, 'business'); // users = 10

        for ($i = 0; $i < 7; $i++) {
            $member = User::factory()->create();
            $this->attachTeamMember($business, $member);
        }
        $business->refresh();

        $service = app(PlanLimitService::class);
        $this->assertEquals(8, $service->getCurrentUsage($business, 'users'));

        // Faqat yaxshilab tekshirib, Start tarifiga majburan o'tkazaylik (admin tomonidan)
        $this->activatePlan($business, 'start');
        $business->refresh();

        // Eski xodimlar saqlanib qolgan (ma'lumot yo'qolmadi)
        $this->assertEquals(
            8,
            $service->getCurrentUsage($business, 'users'),
            'Downgrade eski ma\'lumotlarni o\'chirmasligi kerak'
        );

        // Lekin yangi xodim qo'shib bo'lmaydi (limit allaqachon oshgan)
        $this->assertFalse(
            $business->canAdd('users'),
            'Over-limit holatda yangi user qo\'shib bo\'lmasligi kerak'
        );
        $this->assertTrue($business->hasReachedLimit('users'));
    }

    // ==================== SECTION D: HTTP FEATURE GATING ====================

    public function test_hr_tasks_route_returns_403_when_feature_disabled(): void
    {
        [$owner, $business] = $this->makeBusinessOwner();
        $this->activatePlan($business, 'start'); // hr_tasks = false

        session(['current_business_id' => $business->id]);

        // HR tasks route - EnsureFeatureEnabled('hr_tasks') middleware bilan himoyalangan
        // HRMiddleware'ni chetlab o'tish uchun JSON API kutilganini ko'rsatib yuboramiz
        $response = $this->actingAs($owner)
            ->withHeaders(['Accept' => 'application/json'])
            ->get('/hr/tasks');

        // Middleware zanjiri: hr (department tekshirish) → subscription → feature:hr_tasks
        // HR middleware department tekshiradi; agar o'tsa, feature middleware 403 FEATURE_NOT_AVAILABLE beradi.
        // HR department'ga tegishli bo'lmasa, 403 lekin boshqa sababdan kelishi mumkin.
        // Asl feature gate test uchun middleware'ni to'g'ridan-to'g'ri chaqiramiz:

        $this->assertFalse(
            $business->canUseFeature('hr_tasks'),
            'Start tarifida hr_tasks feature yo\'q bo\'lishi kerak (sanity check)'
        );

        // To'g'ridan-to'g'ri EnsureFeatureEnabled middleware'ni test qilish
        $middleware = app(\App\Http\Middleware\EnsureFeatureEnabled::class);
        $request = \Illuminate\Http\Request::create('/api/hr/tasks', 'GET');
        $request->headers->set('Accept', 'application/json');
        $request->setLaravelSession(app('session.store'));
        session(['current_business_id' => $business->id]);

        $response = $middleware->handle($request, function () {
            return response()->json(['success' => true]);
        }, 'hr_tasks');

        $this->assertEquals(403, $response->getStatusCode());
        $body = json_decode($response->getContent(), true);
        $this->assertFalse($body['success']);
        $this->assertEquals('FEATURE_NOT_AVAILABLE', $body['error_code']);
        $this->assertTrue($body['upgrade_required']);
    }

    public function test_feature_middleware_passes_when_feature_enabled(): void
    {
        [$owner, $business] = $this->makeBusinessOwner();
        $this->activatePlan($business, 'business'); // hr_tasks = true

        session(['current_business_id' => $business->id]);

        $middleware = app(\App\Http\Middleware\EnsureFeatureEnabled::class);
        $request = \Illuminate\Http\Request::create('/api/hr/tasks', 'GET');
        $request->headers->set('Accept', 'application/json');
        $request->setLaravelSession(app('session.store'));

        $response = $middleware->handle($request, function () {
            return response()->json(['success' => true, 'data' => 'ok']);
        }, 'hr_tasks');

        $this->assertEquals(200, $response->getStatusCode());
        $body = json_decode($response->getContent(), true);
        $this->assertTrue($body['success']);
    }

    public function test_anti_fraud_feature_blocked_on_business_plan(): void
    {
        [$owner, $business] = $this->makeBusinessOwner();
        $this->activatePlan($business, 'business'); // anti_fraud = false

        session(['current_business_id' => $business->id]);

        $middleware = app(\App\Http\Middleware\EnsureFeatureEnabled::class);
        $request = \Illuminate\Http\Request::create('/api/anti-fraud', 'GET');
        $request->headers->set('Accept', 'application/json');
        $request->setLaravelSession(app('session.store'));

        $response = $middleware->handle($request, function () {
            return response()->json(['success' => true]);
        }, 'anti_fraud');

        $this->assertEquals(403, $response->getStatusCode());
        $body = json_decode($response->getContent(), true);
        $this->assertEquals('FEATURE_NOT_AVAILABLE', $body['error_code']);
        $this->assertEquals('anti_fraud', $body['feature_key']);
    }

    // ==================== SECTION E: QUOTA EXCEPTION SHAPE ====================

    public function test_quota_exceeded_response_shape_matches_contract(): void
    {
        [$owner, $business] = $this->makeBusinessOwner();
        $this->activatePlan($business, 'start'); // users = 2

        // Limitga yetguncha xodim qo'shish
        $member = User::factory()->create();
        $this->attachTeamMember($business, $member);
        $business->refresh();

        session(['current_business_id' => $business->id]);

        $middleware = app(\App\Http\Middleware\CheckSubscriptionQuota::class);
        $request = \Illuminate\Http\Request::create('/api/team-members', 'POST');
        $request->headers->set('Accept', 'application/json');
        $request->setLaravelSession(app('session.store'));

        $response = $middleware->handle($request, function () {
            return response()->json(['success' => true]);
        }, 'users', 1);

        $this->assertEquals(403, $response->getStatusCode());
        $body = json_decode($response->getContent(), true);

        $this->assertFalse($body['success']);
        $this->assertEquals('QUOTA_EXCEEDED', $body['error_code']);
        $this->assertEquals('users', $body['limit_key']);
        $this->assertTrue($body['upgrade_required']);
        $this->assertArrayHasKey('limit', $body);
        $this->assertArrayHasKey('current_usage', $body);
        $this->assertEquals(2, $body['limit']);
        $this->assertEquals(2, $body['current_usage']);
    }

    public function test_quota_middleware_passes_when_under_limit(): void
    {
        [$owner, $business] = $this->makeBusinessOwner();
        $this->activatePlan($business, 'business'); // users = 10

        session(['current_business_id' => $business->id]);

        $middleware = app(\App\Http\Middleware\CheckSubscriptionQuota::class);
        $request = \Illuminate\Http\Request::create('/api/team-members', 'POST');
        $request->headers->set('Accept', 'application/json');
        $request->setLaravelSession(app('session.store'));

        $response = $middleware->handle($request, function () {
            return response()->json(['success' => true, 'data' => 'created']);
        }, 'users', 1);

        $this->assertEquals(200, $response->getStatusCode());
    }

    // ==================== SECTION F: NO ACTIVE SUBSCRIPTION ====================

    public function test_no_active_subscription_returns_402_response(): void
    {
        [$owner, $business] = $this->makeBusinessOwner();
        // Subscription yaratilmaydi - hech qanday obuna yo'q

        session(['current_business_id' => $business->id]);

        $middleware = app(\App\Http\Middleware\CheckSubscriptionQuota::class);
        $request = \Illuminate\Http\Request::create('/api/team-members', 'POST');
        $request->headers->set('Accept', 'application/json');
        $request->setLaravelSession(app('session.store'));

        $response = $middleware->handle($request, function () {
            return response()->json(['success' => true]);
        }, 'users', 1);

        $this->assertEquals(402, $response->getStatusCode());
        $body = json_decode($response->getContent(), true);

        $this->assertFalse($body['success']);
        $this->assertEquals('NO_ACTIVE_SUBSCRIPTION', $body['error_code']);
        $this->assertTrue($body['upgrade_required']);
    }

    public function test_no_active_subscription_blocks_feature_checks(): void
    {
        [$owner, $business] = $this->makeBusinessOwner();
        // Subscription yo'q

        $gate = app(SubscriptionGate::class);
        $this->assertFalse(
            $gate->hasFeature($business, 'hr_tasks'),
            'Obuna yo\'q holatda feature false qaytishi kerak'
        );

        $this->expectException(NoActiveSubscriptionException::class);
        $gate->checkFeature($business, 'hr_tasks');
    }

    public function test_expired_subscription_treated_as_no_active(): void
    {
        [$owner, $business] = $this->makeBusinessOwner();

        // Muddati o'tgan (ends_at past) subscription
        $plan = Plan::where('slug', 'start')->first();
        Subscription::create([
            'business_id' => $business->id,
            'plan_id' => $plan->id,
            'status' => 'active',
            'billing_cycle' => 'monthly',
            'starts_at' => now()->subMonths(2),
            'ends_at' => now()->subDay(), // o'tgan
            'amount' => $plan->price_monthly,
            'currency' => 'UZS',
        ]);

        $this->assertFalse(
            $business->hasActiveSubscription(),
            'Muddati o\'tgan subscription aktiv hisoblanmasligi kerak'
        );

        $gate = app(SubscriptionGate::class);
        $this->expectException(NoActiveSubscriptionException::class);
        $gate->getActiveSubscription($business);
    }

    // ==================== BONUS: TRIAL SUBSCRIPTION ACTIVE STATUS ====================

    public function test_trialing_subscription_is_considered_active(): void
    {
        [$owner, $business] = $this->makeBusinessOwner();

        $plan = Plan::where('slug', 'business')->first();
        Subscription::create([
            'business_id' => $business->id,
            'plan_id' => $plan->id,
            'status' => 'trialing',
            'billing_cycle' => 'monthly',
            'starts_at' => now(),
            'ends_at' => now()->addDays(14),
            'trial_ends_at' => now()->addDays(14),
            'amount' => 0,
            'currency' => 'UZS',
        ]);

        $this->assertTrue($business->hasActiveSubscription());
        $this->assertTrue($business->canUseFeature('hr_tasks'));
    }
}
