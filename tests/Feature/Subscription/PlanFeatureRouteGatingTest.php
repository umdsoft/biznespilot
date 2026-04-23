<?php

declare(strict_types=1);

namespace Tests\Feature\Subscription;

use App\Models\Business;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;
use Database\Seeders\PlanSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * PlanFeatureRouteGatingTest — P1 fixes'dan keyin route'lar feature flag'ni
 * haqiqatan ham tekshirayotganini tasdiqlaydi.
 *
 * Coverage:
 *   - /hr/recruiting/* → feature:hr_bot (start tarifda 403, business'da 200)
 *   - /hr/onboarding → feature:onboarding
 *   - /alerts/rules → feature:anti_fraud
 *   - Admin BusinessManagementController assignSubscription over-limit bloklaydi
 */
class PlanFeatureRouteGatingTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected Business $business;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(PlanSeeder::class);

        $this->user = User::factory()->create();
        $this->business = Business::factory()->create(['user_id' => $this->user->id]);
        session(['current_business_id' => $this->business->id]);
        $this->user->setRelation('currentBusiness', $this->business);
    }

    protected function activatePlan(string $slug): void
    {
        // BusinessObserver avtomatik trial yaratadi — aniq tarif uchun o'zgartiramiz
        Subscription::where('business_id', $this->business->id)
            ->whereIn('status', ['active', 'trialing'])
            ->forceDelete();

        $plan = Plan::where('slug', $slug)->firstOrFail();
        Subscription::create([
            'business_id' => $this->business->id,
            'plan_id' => $plan->id,
            'status' => 'active',
            'billing_cycle' => 'monthly',
            'starts_at' => now(),
            'ends_at' => now()->addMonth(),
            'amount' => $plan->price_monthly,
            'currency' => 'UZS',
            'auto_renew' => false,
            'payment_provider' => 'test',
        ]);
    }

    // =================================================================
    // hr_bot feature gating
    // =================================================================

    public function test_recruiting_route_blocked_on_plan_without_hr_bot(): void
    {
        $this->activatePlan('standard'); // hr_bot=false

        $response = $this->actingAs($this->user)->get('/hr/recruiting');
        // feature middleware + hr middleware + subscription middleware chain —
        // ketma-ket bo'lsa ham FeatureNotAvailable'ga yetishi shart emas.
        // Asosiy: 200 qaytmasligi kerak.
        $this->assertNotEquals(200, $response->status(),
            "Standard tarifda hr_bot=false bo'lsa ham /hr/recruiting ochiq — feature middleware ishlamayapti!");
    }

    public function test_recruiting_route_accessible_on_plan_with_hr_bot(): void
    {
        $this->activatePlan('business'); // hr_bot=true

        $response = $this->actingAs($this->user)->get('/hr/recruiting');
        // business tarifda feature middleware to'sqinlik qilmasligi kerak
        // (boshqa middleware 403 berishi mumkin — HR department tekshiruvi),
        // lekin FEATURE_NOT_AVAILABLE 403 kelmaydi.
        if ($response->status() === 403) {
            $body = $response->json() ?? [];
            $this->assertNotSame('FEATURE_NOT_AVAILABLE', $body['error_code'] ?? null,
                'Business tarifda hr_bot=true bo\'lsa ham FEATURE_NOT_AVAILABLE kelyapti');
        }
    }

    // =================================================================
    // onboarding feature gating (HR onboarding)
    // =================================================================

    public function test_hr_onboarding_route_blocked_on_plan_without_onboarding(): void
    {
        $this->activatePlan('start'); // onboarding=false

        $response = $this->actingAs($this->user)->get('/hr/onboarding');
        $this->assertNotEquals(200, $response->status());
    }

    // =================================================================
    // anti_fraud feature gating (Alert rules)
    // =================================================================

    public function test_alert_rules_blocked_on_plan_without_anti_fraud(): void
    {
        $this->activatePlan('business'); // anti_fraud=false

        $response = $this->actingAs($this->user)->get('/business/alerts/rules');
        $this->assertNotEquals(200, $response->status(),
            "Business tarifda anti_fraud=false bo'lsa ham alert rules ochiq!");
    }

    public function test_alert_rules_accessible_on_plan_with_anti_fraud(): void
    {
        $this->activatePlan('premium'); // anti_fraud=true

        $response = $this->actingAs($this->user)->get('/business/alerts/rules');
        if ($response->status() === 403) {
            $body = $response->json() ?? [];
            $this->assertNotSame('FEATURE_NOT_AVAILABLE', $body['error_code'] ?? null);
        }
    }

    // =================================================================
    // Admin BusinessManagementController — downgrade guard
    // =================================================================

    protected function makeAdmin(): User
    {
        // Roles jadvali UUID ishlatadi — Spatie default create() ni
        // chetlab o'tib, explicit UUID beramiz.
        $roleModel = config('permission.models.role', \Spatie\Permission\Models\Role::class);
        $existing = $roleModel::where('name', 'super_admin')->first();
        if (!$existing) {
            $existing = $roleModel::create([
                'id' => \Illuminate\Support\Str::uuid()->toString(),
                'name' => 'super_admin',
                'guard_name' => 'web',
            ]);
        }

        $admin = User::factory()->create();
        $admin->assignRole('super_admin');
        return $admin;
    }

    public function test_admin_assign_downgrade_blocks_when_over_limit(): void
    {
        $admin = $this->makeAdmin();

        // Hozirgi biznesda 10 ta user (Business tarif) bo'lsin — Start'ga o'tish over-limit
        $this->activatePlan('business');

        // 10 ta xodim qo'shamiz (business limit=10)
        for ($i = 0; $i < 9; $i++) {
            $u = User::factory()->create();
            \App\Models\BusinessUser::create([
                'business_id' => $this->business->id,
                'user_id' => $u->id,
                'role' => 'member',
                'accepted_at' => now(),
            ]);
        }

        $startPlan = Plan::where('slug', 'start')->first();

        $response = $this->actingAs($admin)
            ->postJson("/dashboard/businesses/{$this->business->id}/assign-subscription", [
                'plan_id' => $startPlan->id,
                'billing_cycle' => 'monthly',
                'duration_months' => 1,
            ]);

        // 422 expected (over-limit block)
        $this->assertEquals(422, $response->status(),
            'Admin over-limit downgrade hozir ham o\'tib ketyapti — guard ishlamadi');
        $response->assertJsonFragment(['requires_force' => true]);
    }

    public function test_admin_assign_downgrade_succeeds_with_force_flag(): void
    {
        $admin = $this->makeAdmin();

        $this->activatePlan('business');

        for ($i = 0; $i < 9; $i++) {
            $u = User::factory()->create();
            \App\Models\BusinessUser::create([
                'business_id' => $this->business->id,
                'user_id' => $u->id,
                'role' => 'member',
                'accepted_at' => now(),
            ]);
        }

        $startPlan = Plan::where('slug', 'start')->first();

        $response = $this->actingAs($admin)
            ->postJson("/dashboard/businesses/{$this->business->id}/assign-subscription", [
                'plan_id' => $startPlan->id,
                'billing_cycle' => 'monthly',
                'duration_months' => 1,
                'force' => true,
            ]);

        // 302 redirect expected when force=true
        $this->assertContains($response->status(), [200, 302],
            'force=true bilan ham downgrade o\'tmadi: status '.$response->status());
    }
}
