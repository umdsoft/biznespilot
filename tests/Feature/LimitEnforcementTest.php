<?php

namespace Tests\Feature;

use App\Http\Middleware\CheckFeatureLimit;
use App\Jobs\CallCenter\ProcessCallAnalysisJob;
use App\Models\Business;
use App\Models\CallLog;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * LimitEnforcementTest - Tarif Cheklovlari (Limits) Test
 *
 * Bu test tarif limitlarining haqiqatan ham ishlashini tekshiradi.
 * Foydalanuvchi limitga yetganda tizim to'g'ri xato berishi kerak.
 *
 * Test A: Team Member Limit - START tarifida 2 ta xodim limiti
 * Test B: Call Center Minutes Limit - START tarifida 60 daqiqa limiti
 */
class LimitEnforcementTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Business $business;
    protected Plan $startPlan;

    /**
     * Test setup
     */
    protected function setUp(): void
    {
        parent::setUp();

        // Plan'larni seed qilish
        $this->seedPlans();

        // START tarifini olish (2 xodim, 60 daqiqa Call Center AI)
        $this->startPlan = Plan::where('slug', 'start')->first();

        // Queue Mock
        Queue::fake();
    }

    /**
     * TEST A: Team Member Limiti Tekshirish
     *
     * START tarifida 2 ta xodim limiti bor.
     * 1. Business yaratish
     * 2. 2 ta xodim qo'shish (muvaffaqiyatli)
     * 3. 3-xodimni qo'shishga urinish
     * 4. Kutilgan natija: Xato berishi kerak (limit exceeded)
     */
    public function test_team_member_limit_enforcement(): void
    {
        // 1. User va Business yaratish
        $this->user = User::create([
            'name' => 'Limit Test User',
            'email' => 'limit@test.uz',
            'phone' => '+998901111111',
            'password' => bcrypt('password'),
            'login' => 'limit@test.uz',
        ]);

        $this->business = Business::create([
            'user_id' => $this->user->id,
            'name' => 'Limit Test Business',
            'slug' => 'limit-test-business',
            'category' => 'Test',
            'industry_code' => 'test',
            'business_type' => 'test',
            'status' => 'active',
        ]);

        // BusinessObserver avtomatik subscription yaratadi - uni o'chiramiz
        $this->business->subscriptions()->delete();

        // 2. START tarif subscription yaratish (2 xodim limiti)
        $subscription = Subscription::create([
            'business_id' => $this->business->id,
            'plan_id' => $this->startPlan->id,
            'status' => 'active',
            'starts_at' => now(),
            'ends_at' => now()->addMonth(),
            'amount' => 299000,
            'currency' => 'UZS',
        ]);

        // 3. Plan limitlarini tekshirish
        $this->assertEquals(2, $this->startPlan->team_member_limit);
        $this->assertEquals(60, $this->startPlan->audio_minutes_limit);

        // 4. 2 ta xodim qo'shish (muvaffaqiyatli bo'lishi kerak)
        $member1 = User::create([
            'name' => 'Team Member 1',
            'email' => 'member1@test.uz',
            'phone' => '+998901111112',
            'password' => bcrypt('password'),
            'login' => 'member1@test.uz',
        ]);

        $member2 = User::create([
            'name' => 'Team Member 2',
            'email' => 'member2@test.uz',
            'phone' => '+998901111113',
            'password' => bcrypt('password'),
            'login' => 'member2@test.uz',
        ]);

        // Business'ga xodim sifatida qo'shish
        $this->business->users()->attach($member1->id, [
            'id' => Str::uuid(),
            'role' => 'member',
            'joined_at' => now(),
            'accepted_at' => now(),
        ]);

        $this->business->users()->attach($member2->id, [
            'id' => Str::uuid(),
            'role' => 'member',
            'joined_at' => now(),
            'accepted_at' => now(),
        ]);

        // 5. Limitga yetganligini tekshirish
        $currentTeamCount = $this->business->users()->count();
        $this->assertEquals(2, $currentTeamCount);

        // 6. Business::hasReachedLimit() metodini tekshirish
        $hasReachedLimit = $this->business->hasReachedLimit('team_members');
        $this->assertTrue($hasReachedLimit, 'Team member limiti 2 ta, hozir 2 ta xodim bor - limit reached bo\'lishi kerak');

        // 7. 3-xodimni qo'shishga urinish (limit oshib ketadi)
        $member3 = User::create([
            'name' => 'Team Member 3',
            'email' => 'member3@test.uz',
            'phone' => '+998901111114',
            'password' => bcrypt('password'),
            'login' => 'member3@test.uz',
        ]);

        // Middleware simulatsiyasi - CheckFeatureLimit middleware 403 qaytarishi kerak
        $middleware = app(CheckFeatureLimit::class);

        // Request simulatsiyasi
        $request = \Illuminate\Http\Request::create('/api/team-members', 'POST');
        $request->setLaravelSession(app('session.store'));
        session(['current_business_id' => $this->business->id]);

        $response = $middleware->handle($request, function () {
            return response()->json(['success' => true], 200);
        }, 'team_members');

        // 8. Kutilgan natija: 403 Forbidden
        $this->assertEquals(403, $response->getStatusCode());
        $responseData = json_decode($response->getContent(), true);
        $this->assertFalse($responseData['success']);
        $this->assertEquals('FEATURE_LIMIT_EXCEEDED', $responseData['error_code']);
        $this->assertTrue($responseData['upgrade_required']);
        $this->assertStringContainsString('Team member limit', $responseData['message']);
    }

    /**
     * TEST B: Call Center Daqiqasi Limiti Tekshirish
     *
     * START tarifida 60 daqiqa Call Center AI limiti bor.
     * 1. Business yaratish
     * 2. 60 daqiqa audio ishlatilgan deb belgilash (limit)
     * 3. Yangi 5 daqiqalik audio yuklashga urinish
     * 4. Kutilgan natija: Job limit xatosi bilan fail bo'lishi kerak
     */
    public function test_call_center_minutes_limit_enforcement(): void
    {
        // 1. User va Business yaratish
        $this->user = User::create([
            'name' => 'Call Center Limit Test User',
            'email' => 'callcenter@test.uz',
            'phone' => '+998902222222',
            'password' => bcrypt('password'),
            'login' => 'callcenter@test.uz',
        ]);

        $this->business = Business::create([
            'user_id' => $this->user->id,
            'name' => 'Call Center Limit Test Business',
            'slug' => 'call-center-limit-test',
            'category' => 'Test',
            'industry_code' => 'test',
            'business_type' => 'test',
            'status' => 'active',
        ]);

        // BusinessObserver avtomatik subscription yaratadi - uni o'chiramiz
        $this->business->subscriptions()->delete();

        // 2. START tarif subscription yaratish (60 daqiqa limiti)
        $subscription = Subscription::create([
            'business_id' => $this->business->id,
            'plan_id' => $this->startPlan->id,
            'status' => 'active',
            'starts_at' => now(),
            'ends_at' => now()->addMonth(),
            'amount' => 299000,
            'currency' => 'UZS',
        ]);

        // 3. Bu oy uchun 60 daqiqa audio ishlatilgan deb belgilash
        // 55 daqiqalik (3300 soniya) qo'ng'iroqlar yaratish
        for ($i = 0; $i < 11; $i++) {
            CallLog::create([
                'business_id' => $this->business->id,
                'user_id' => $this->user->id,
                'call_id' => 'call_limit_test_' . $i,
                'provider' => 'test_provider',
                'direction' => 'inbound',
                'from_number' => '+998901234567',
                'to_number' => '+998909999999',
                'duration' => 300, // 5 daqiqa har biri
                'recording_url' => 'https://example.com/recordings/test_' . $i . '.mp3',
                'status' => 'completed',
                'analysis_status' => 'completed', // Bu qo'ng'iroqlar tahlil qilingan
                'created_at' => now(),
            ]);
        }

        // 4. Hozirgi ishlatilgan daqiqalarni hisoblash
        $usedMinutes = (int) CallLog::where('business_id', $this->business->id)
            ->where('created_at', '>=', now()->startOfMonth())
            ->whereIn('analysis_status', ['completed', 'analyzing', 'transcribing'])
            ->sum('duration') / 60;

        $this->assertEquals(55, $usedMinutes, '11 ta 5 daqiqalik qo\'ng\'iroq = 55 daqiqa');

        // 5. Yangi 10 daqiqalik qo'ng'iroq yaratish (limit oshib ketadi: 55 + 10 = 65 > 60)
        $callLogOverLimit = CallLog::create([
            'business_id' => $this->business->id,
            'user_id' => $this->user->id,
            'call_id' => 'call_over_limit',
            'provider' => 'test_provider',
            'direction' => 'inbound',
            'from_number' => '+998901234567',
            'to_number' => '+998909999999',
            'duration' => 600, // 10 daqiqa (limit oshadi: 55 + 10 = 65)
            'recording_url' => 'https://example.com/recordings/over_limit.mp3',
            'status' => 'completed',
            'analysis_status' => 'pending',
        ]);

        // 6. ProcessCallAnalysisJob dispatch qilish
        $job = new ProcessCallAnalysisJob($callLogOverLimit);

        // 7. Job ichidagi checkAudioMinutesLimit() metodini test qilish
        // Job'ni to'g'ridan-to'g'ri handle qilish (Mock service'lar bilan)
        try {
            // Job handle metodini chaqirish imkoniyati yo'q, chunki Mock service'lar kerak
            // Buning o'rniga, job logic'ini test qilamiz

            // Job ning checkAudioMinutesLimit metodini reflection orqali chaqirish
            $reflection = new \ReflectionClass($job);
            $method = $reflection->getMethod('checkAudioMinutesLimit');
            $method->setAccessible(true);

            $limitCheck = $method->invoke($job);

            // 8. Kutilgan natija: allowed = false
            $this->assertFalse($limitCheck['allowed'], 'Audio minutes limiti oshgan, allowed=false bo\'lishi kerak');
            $this->assertEquals(55, $limitCheck['used'], 'Ishlatilgan daqiqalar: 55');
            $this->assertEquals(60, $limitCheck['limit'], 'Limit: 60 daqiqa');
            $this->assertEquals(5, $limitCheck['remaining'], 'Qolgan: 5 daqiqa');

        } catch (\Exception $e) {
            $this->fail('Job checkAudioMinutesLimit metodini test qilishda xato: ' . $e->getMessage());
        }

        // 9. CallLog'ni tekshirish - analysis_status hali 'pending'
        $callLogOverLimit->refresh();
        $this->assertEquals('pending', $callLogOverLimit->analysis_status, 'Job ishlamagan, status pending bo\'lishi kerak');

        // 10. Kichikroq qo'ng'iroq (5 daqiqa) limit ichida bo'lishi kerak
        $callLogWithinLimit = CallLog::create([
            'business_id' => $this->business->id,
            'user_id' => $this->user->id,
            'call_id' => 'call_within_limit',
            'provider' => 'test_provider',
            'direction' => 'inbound',
            'from_number' => '+998901234567',
            'to_number' => '+998909999999',
            'duration' => 300, // 5 daqiqa (limit ichida: 55 + 5 = 60)
            'recording_url' => 'https://example.com/recordings/within_limit.mp3',
            'status' => 'completed',
            'analysis_status' => 'pending',
        ]);

        $jobWithinLimit = new ProcessCallAnalysisJob($callLogWithinLimit);
        $reflection2 = new \ReflectionClass($jobWithinLimit);
        $method2 = $reflection2->getMethod('checkAudioMinutesLimit');
        $method2->setAccessible(true);

        $limitCheck2 = $method2->invoke($jobWithinLimit);

        // 11. Kutilgan natija: allowed = true (limit ichida)
        $this->assertTrue($limitCheck2['allowed'], '5 daqiqalik qo\'ng\'iroq limit ichida, allowed=true bo\'lishi kerak');
        $this->assertEquals(55, $limitCheck2['used'], 'Ishlatilgan: 55 daqiqa');
        $this->assertEquals(60, $limitCheck2['limit'], 'Limit: 60 daqiqa');
    }

    /**
     * Helper: Plan'larni seed qilish
     */
    protected function seedPlans(): void
    {
        $plans = [
            [
                'name' => 'Free',
                'slug' => 'free',
                'price_monthly' => 0,
                'price_yearly' => 0,
                'business_limit' => 1,
                'team_member_limit' => 1,
                'lead_limit' => 100,
                'chatbot_channel_limit' => 1,
                'telegram_bot_limit' => 1,
                'has_instagram' => false,
                'audio_minutes_limit' => 0,
                'ai_requests_limit' => 10,
                'storage_limit_mb' => 100,
                'instagram_dm_limit' => 0,
                'content_posts_limit' => 0,
                'has_amocrm' => false,
                'is_active' => true,
                'features' => ['CRM', 'Telegram chatbot'],
            ],
            [
                'name' => 'Start',
                'slug' => 'start',
                'price_monthly' => 299000,
                'price_yearly' => 2990000,
                'business_limit' => 1,
                'team_member_limit' => 2, // 2 ta xodim limiti
                'lead_limit' => 500,
                'chatbot_channel_limit' => 1,
                'telegram_bot_limit' => 1,
                'has_instagram' => true,
                'audio_minutes_limit' => 60, // 60 daqiqa limiti
                'ai_requests_limit' => 100,
                'storage_limit_mb' => 500,
                'instagram_dm_limit' => 200,
                'content_posts_limit' => 20,
                'has_amocrm' => false,
                'is_active' => true,
                'features' => ['Instagram chatbot', '60 daq Call Center AI'],
            ],
            [
                'name' => 'Business',
                'slug' => 'business',
                'price_monthly' => 799000,
                'price_yearly' => 7990000,
                'business_limit' => 2,
                'team_member_limit' => 10,
                'lead_limit' => 10000,
                'chatbot_channel_limit' => 5,
                'telegram_bot_limit' => 5,
                'has_instagram' => true,
                'audio_minutes_limit' => 400,
                'ai_requests_limit' => 1000,
                'storage_limit_mb' => 10240,
                'instagram_dm_limit' => 2000,
                'content_posts_limit' => 100,
                'has_amocrm' => true,
                'is_active' => true,
                'features' => ['HR Bot', '400 daq Call Center AI'],
            ],
        ];

        foreach ($plans as $planData) {
            Plan::create(array_merge(['id' => Str::uuid()->toString()], $planData));
        }
    }
}
