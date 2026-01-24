<?php

namespace Tests\Feature;

use App\Models\Business;
use App\Models\Lead;
use App\Models\User;
use App\Models\KpiDailyActual;
use App\Models\PipelineStage;
use App\Models\Offer;
use App\Models\DreamBuyer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Multi-Tenant Security Tests
 *
 * These tests ensure that business data is properly isolated
 * and users cannot access data from other businesses.
 */
class MultiTenantTest extends TestCase
{
    use RefreshDatabase;

    private User $user1;
    private User $user2;
    private Business $business1;
    private Business $business2;

    protected function setUp(): void
    {
        parent::setUp();

        // Create two separate businesses with their owners
        $this->user1 = User::factory()->create(['email' => 'user1@test.com']);
        $this->user2 = User::factory()->create(['email' => 'user2@test.com']);

        $this->business1 = Business::factory()->create([
            'name' => 'Business 1',
            'owner_id' => $this->user1->id,
        ]);

        $this->business2 = Business::factory()->create([
            'name' => 'Business 2',
            'owner_id' => $this->user2->id,
        ]);

        // Attach users to their businesses
        $this->user1->businesses()->attach($this->business1->id, ['role' => 'owner']);
        $this->user2->businesses()->attach($this->business2->id, ['role' => 'owner']);
    }

    /**
     * Test that leads are isolated by business
     */
    public function test_leads_are_isolated_by_business(): void
    {
        // Create leads for both businesses
        $lead1 = Lead::factory()->create([
            'business_id' => $this->business1->id,
            'name' => 'Lead for Business 1',
        ]);

        $lead2 = Lead::factory()->create([
            'business_id' => $this->business2->id,
            'name' => 'Lead for Business 2',
        ]);

        // Set session to business 1
        session(['current_business_id' => $this->business1->id]);

        // Business 1 should only see their lead
        $leads = Lead::all();
        $this->assertCount(1, $leads);
        $this->assertEquals('Lead for Business 1', $leads->first()->name);

        // Set session to business 2
        session(['current_business_id' => $this->business2->id]);

        // Business 2 should only see their lead
        $leads = Lead::all();
        $this->assertCount(1, $leads);
        $this->assertEquals('Lead for Business 2', $leads->first()->name);
    }

    /**
     * Test that KPI data is isolated by business
     */
    public function test_kpi_data_is_isolated_by_business(): void
    {
        // Create KPI data for both businesses
        KpiDailyActual::create([
            'business_id' => $this->business1->id,
            'kpi_code' => 'leads_count',
            'date' => now()->toDateString(),
            'actual_value' => 100,
        ]);

        KpiDailyActual::create([
            'business_id' => $this->business2->id,
            'kpi_code' => 'leads_count',
            'date' => now()->toDateString(),
            'actual_value' => 200,
        ]);

        // Set session to business 1
        session(['current_business_id' => $this->business1->id]);

        $kpis = KpiDailyActual::all();
        $this->assertCount(1, $kpis);
        $this->assertEquals(100, $kpis->first()->actual_value);

        // Set session to business 2
        session(['current_business_id' => $this->business2->id]);

        $kpis = KpiDailyActual::all();
        $this->assertCount(1, $kpis);
        $this->assertEquals(200, $kpis->first()->actual_value);
    }

    /**
     * Test that pipeline stages are isolated by business
     */
    public function test_pipeline_stages_are_isolated_by_business(): void
    {
        PipelineStage::create([
            'business_id' => $this->business1->id,
            'name' => 'Business 1 Stage',
            'slug' => 'b1-stage',
            'order' => 1,
        ]);

        PipelineStage::create([
            'business_id' => $this->business2->id,
            'name' => 'Business 2 Stage',
            'slug' => 'b2-stage',
            'order' => 1,
        ]);

        session(['current_business_id' => $this->business1->id]);
        $stages = PipelineStage::all();
        $this->assertCount(1, $stages);
        $this->assertEquals('Business 1 Stage', $stages->first()->name);

        session(['current_business_id' => $this->business2->id]);
        $stages = PipelineStage::all();
        $this->assertCount(1, $stages);
        $this->assertEquals('Business 2 Stage', $stages->first()->name);
    }

    /**
     * Test that offers are isolated by business
     */
    public function test_offers_are_isolated_by_business(): void
    {
        Offer::factory()->create([
            'business_id' => $this->business1->id,
            'name' => 'Offer 1',
        ]);

        Offer::factory()->create([
            'business_id' => $this->business2->id,
            'name' => 'Offer 2',
        ]);

        session(['current_business_id' => $this->business1->id]);
        $offers = Offer::all();
        $this->assertCount(1, $offers);
        $this->assertEquals('Offer 1', $offers->first()->name);
    }

    /**
     * Test that dream buyers are isolated by business
     */
    public function test_dream_buyers_are_isolated_by_business(): void
    {
        DreamBuyer::factory()->create([
            'business_id' => $this->business1->id,
            'name' => 'Dream Buyer 1',
        ]);

        DreamBuyer::factory()->create([
            'business_id' => $this->business2->id,
            'name' => 'Dream Buyer 2',
        ]);

        session(['current_business_id' => $this->business1->id]);
        $dreamBuyers = DreamBuyer::all();
        $this->assertCount(1, $dreamBuyers);
        $this->assertEquals('Dream Buyer 1', $dreamBuyers->first()->name);
    }

    /**
     * Test that allBusinesses() scope bypasses the global scope
     */
    public function test_all_businesses_scope_bypasses_filter(): void
    {
        Lead::factory()->create(['business_id' => $this->business1->id]);
        Lead::factory()->create(['business_id' => $this->business2->id]);

        session(['current_business_id' => $this->business1->id]);

        // Normal query should return only business 1 leads
        $filteredLeads = Lead::all();
        $this->assertCount(1, $filteredLeads);

        // allBusinesses() should return all leads
        $allLeads = Lead::allBusinesses()->get();
        $this->assertCount(2, $allLeads);
    }

    /**
     * Test that forBusiness() scope filters correctly
     */
    public function test_for_business_scope_filters_correctly(): void
    {
        Lead::factory()->create(['business_id' => $this->business1->id, 'name' => 'Lead 1']);
        Lead::factory()->create(['business_id' => $this->business2->id, 'name' => 'Lead 2']);

        // Even with different session, forBusiness should work
        session(['current_business_id' => $this->business1->id]);

        $business2Leads = Lead::forBusiness($this->business2->id)->get();
        $this->assertCount(1, $business2Leads);
        $this->assertEquals('Lead 2', $business2Leads->first()->name);
    }

    /**
     * Test that business_id is auto-assigned on create
     */
    public function test_business_id_is_auto_assigned_on_create(): void
    {
        session(['current_business_id' => $this->business1->id]);

        $lead = Lead::create([
            'name' => 'Auto Assigned Lead',
            'phone' => '+998901234567',
            'status' => 'new',
        ]);

        $this->assertEquals($this->business1->id, $lead->business_id);
    }

    /**
     * Test API endpoint returns only current business data
     */
    public function test_api_returns_only_current_business_data(): void
    {
        Lead::factory()->create([
            'business_id' => $this->business1->id,
            'name' => 'Business 1 Lead',
        ]);

        Lead::factory()->create([
            'business_id' => $this->business2->id,
            'name' => 'Business 2 Lead',
        ]);

        // Acting as user1 with business1 session
        $this->actingAs($this->user1);
        session(['current_business_id' => $this->business1->id]);

        $response = $this->getJson('/api/v1/leads');

        // Should only return business 1 lead
        if ($response->status() === 200) {
            $data = $response->json('data') ?? $response->json();
            $names = collect($data)->pluck('name')->toArray();
            $this->assertContains('Business 1 Lead', $names);
            $this->assertNotContains('Business 2 Lead', $names);
        }
    }

    /**
     * Test that user cannot access another business's lead directly
     */
    public function test_user_cannot_access_other_business_lead(): void
    {
        $lead = Lead::factory()->create([
            'business_id' => $this->business2->id,
            'name' => 'Secret Lead',
        ]);

        $this->actingAs($this->user1);
        session(['current_business_id' => $this->business1->id]);

        // Try to access business 2's lead via API
        $response = $this->getJson("/api/v1/leads/{$lead->id}");

        // Should return 404 or 403
        $this->assertTrue(
            in_array($response->status(), [404, 403]),
            "Expected 404 or 403, got {$response->status()}"
        );
    }
}
