<?php

namespace Tests\Feature;

use App\Models\Business;
use App\Models\GeneratedReport;
use App\Models\KpiDailyActual;
use App\Models\KpiDefinition;
use App\Models\Lead;
use App\Models\Offer;
use App\Models\User;
use App\Policies\BusinessPolicy;
use App\Policies\KpiPolicy;
use App\Policies\LeadPolicy;
use App\Policies\OfferPolicy;
use App\Policies\ReportPolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PolicyTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;
    private User $otherUser;
    private Business $business;
    private Business $otherBusiness;

    protected function setUp(): void
    {
        parent::setUp();

        $this->owner = User::factory()->create();
        $this->otherUser = User::factory()->create();

        $this->business = Business::factory()->create(['user_id' => $this->owner->id]);
        $this->otherBusiness = Business::factory()->create(['user_id' => $this->otherUser->id]);

        $this->owner->teamBusinesses()->attach($this->business->id, ['role' => 'owner']);
        $this->otherUser->teamBusinesses()->attach($this->otherBusiness->id, ['role' => 'owner']);

        // Set current business for owner
        $this->owner->setRelation('currentBusiness', $this->business);

        // Create KPI definition for foreign key constraints
        KpiDefinition::create([
            'category' => 'sales',
            'kpi_code' => 'leads_count',
            'kpi_name' => 'Leads Count',
            'kpi_name_uz' => 'Lidlar soni',
            'default_unit' => 'dona',
            'is_active' => true,
        ]);
    }

    // ===========================================
    // LEAD POLICY TESTS
    // ===========================================

    /**
     * Test user can view own business leads.
     */
    public function test_lead_policy_view_any(): void
    {
        $policy = new LeadPolicy();

        $this->assertTrue($policy->viewAny($this->owner));
    }

    /**
     * Test user can view lead from own business.
     */
    public function test_lead_policy_view(): void
    {
        $policy = new LeadPolicy();
        $lead = Lead::factory()->forBusiness($this->business)->create();

        $this->assertTrue($policy->view($this->owner, $lead));
    }

    /**
     * Test user cannot view lead from other business.
     */
    public function test_lead_policy_cannot_view_other_business(): void
    {
        $policy = new LeadPolicy();
        $lead = Lead::factory()->forBusiness($this->otherBusiness)->create();

        $this->assertFalse($policy->view($this->owner, $lead));
    }

    /**
     * Test user can create leads.
     */
    public function test_lead_policy_create(): void
    {
        $policy = new LeadPolicy();

        $this->assertTrue($policy->create($this->owner));
    }

    /**
     * Test user can update own business lead.
     */
    public function test_lead_policy_update(): void
    {
        $policy = new LeadPolicy();
        $lead = Lead::factory()->forBusiness($this->business)->create();

        $this->assertTrue($policy->update($this->owner, $lead));
    }

    /**
     * Test user cannot update other business lead.
     */
    public function test_lead_policy_cannot_update_other_business(): void
    {
        $policy = new LeadPolicy();
        $lead = Lead::factory()->forBusiness($this->otherBusiness)->create();

        $this->assertFalse($policy->update($this->owner, $lead));
    }

    /**
     * Test user can delete own business lead.
     */
    public function test_lead_policy_delete(): void
    {
        $policy = new LeadPolicy();
        $lead = Lead::factory()->forBusiness($this->business)->create();

        $this->assertTrue($policy->delete($this->owner, $lead));
    }

    /**
     * Test export requires admin/manager role.
     */
    public function test_lead_policy_export(): void
    {
        $policy = new LeadPolicy();

        // Export requires admin, sales_head, or manager role
        // Regular user without role cannot export
        $this->assertFalse($policy->export($this->owner));
    }

    // ===========================================
    // OFFER POLICY TESTS
    // ===========================================

    /**
     * Test user can view any offers.
     */
    public function test_offer_policy_view_any(): void
    {
        $policy = new OfferPolicy();

        $this->assertTrue($policy->viewAny($this->owner));
    }

    /**
     * Test user can view own business offer.
     */
    public function test_offer_policy_view(): void
    {
        $policy = new OfferPolicy();
        $offer = Offer::factory()->forBusiness($this->business)->create();

        $this->assertTrue($policy->view($this->owner, $offer));
    }

    /**
     * Test user cannot view other business offer.
     */
    public function test_offer_policy_cannot_view_other_business(): void
    {
        $policy = new OfferPolicy();
        $offer = Offer::factory()->forBusiness($this->otherBusiness)->create();

        $this->assertFalse($policy->view($this->owner, $offer));
    }

    /**
     * Test user can create offers.
     */
    public function test_offer_policy_create(): void
    {
        $policy = new OfferPolicy();

        $this->assertTrue($policy->create($this->owner));
    }

    /**
     * Test user can update own business offer.
     */
    public function test_offer_policy_update(): void
    {
        $policy = new OfferPolicy();
        $offer = Offer::factory()->forBusiness($this->business)->create();

        $this->assertTrue($policy->update($this->owner, $offer));
    }

    /**
     * Test user can delete own business offer.
     */
    public function test_offer_policy_delete(): void
    {
        $policy = new OfferPolicy();
        $offer = Offer::factory()->forBusiness($this->business)->create();

        $this->assertTrue($policy->delete($this->owner, $offer));
    }

    /**
     * Test user can duplicate own business offer.
     */
    public function test_offer_policy_duplicate(): void
    {
        $policy = new OfferPolicy();
        $offer = Offer::factory()->forBusiness($this->business)->create();

        $this->assertTrue($policy->duplicate($this->owner, $offer));
    }

    /**
     * Test user can view offer analytics.
     */
    public function test_offer_policy_view_analytics(): void
    {
        $policy = new OfferPolicy();
        $offer = Offer::factory()->forBusiness($this->business)->create();

        $this->assertTrue($policy->viewAnalytics($this->owner, $offer));
    }

    // ===========================================
    // KPI POLICY TESTS
    // ===========================================

    /**
     * Test user can view any KPIs.
     */
    public function test_kpi_policy_view_any(): void
    {
        $policy = new KpiPolicy();

        $this->assertTrue($policy->viewAny($this->owner));
    }

    /**
     * Test user can view own business KPI.
     */
    public function test_kpi_policy_view(): void
    {
        $policy = new KpiPolicy();
        $kpi = KpiDailyActual::create([
            'business_id' => $this->business->id,
            'kpi_code' => 'leads_count',
            'date' => now()->toDateString(),
            'planned_value' => 150,
            'actual_value' => 100,
            'unit' => 'dona',
        ]);

        $this->assertTrue($policy->view($this->owner, $kpi));
    }

    /**
     * Test user cannot view other business KPI.
     */
    public function test_kpi_policy_cannot_view_other_business(): void
    {
        $policy = new KpiPolicy();
        $kpi = KpiDailyActual::create([
            'business_id' => $this->otherBusiness->id,
            'kpi_code' => 'leads_count',
            'date' => now()->toDateString(),
            'planned_value' => 150,
            'actual_value' => 100,
            'unit' => 'dona',
        ]);

        $this->assertFalse($policy->view($this->owner, $kpi));
    }

    /**
     * Test user can create KPI data.
     */
    public function test_kpi_policy_create(): void
    {
        $policy = new KpiPolicy();

        $this->assertTrue($policy->create($this->owner));
    }

    /**
     * Test user can update own business KPI.
     */
    public function test_kpi_policy_update(): void
    {
        $policy = new KpiPolicy();
        $kpi = KpiDailyActual::create([
            'business_id' => $this->business->id,
            'kpi_code' => 'leads_count',
            'date' => now()->toDateString(),
            'planned_value' => 150,
            'actual_value' => 100,
            'unit' => 'dona',
        ]);

        $this->assertTrue($policy->update($this->owner, $kpi));
    }

    /**
     * Test user can export KPI data.
     */
    public function test_kpi_policy_export(): void
    {
        $policy = new KpiPolicy();

        $this->assertTrue($policy->export($this->owner));
    }

    /**
     * Test user can view KPI dashboard.
     */
    public function test_kpi_policy_view_dashboard(): void
    {
        $policy = new KpiPolicy();

        $this->assertTrue($policy->viewDashboard($this->owner));
    }

    // ===========================================
    // BUSINESS POLICY TESTS
    // ===========================================

    /**
     * Test user can view own business.
     */
    public function test_business_policy_view(): void
    {
        $policy = new BusinessPolicy();

        $this->assertTrue($policy->view($this->owner, $this->business));
    }

    /**
     * Test user cannot view other business.
     */
    public function test_business_policy_cannot_view_other(): void
    {
        $policy = new BusinessPolicy();

        $this->assertFalse($policy->view($this->owner, $this->otherBusiness));
    }

    /**
     * Test user can update own business.
     */
    public function test_business_policy_update(): void
    {
        $policy = new BusinessPolicy();

        $this->assertTrue($policy->update($this->owner, $this->business));
    }

    /**
     * Test user can create business.
     */
    public function test_business_policy_create(): void
    {
        $policy = new BusinessPolicy();

        $this->assertTrue($policy->create($this->owner));
    }

    // ===========================================
    // REPORT POLICY TESTS
    // ===========================================

    /**
     * Test user can view any reports.
     */
    public function test_report_policy_view_any(): void
    {
        $policy = new ReportPolicy();

        $this->assertTrue($policy->viewAny($this->owner));
    }

    /**
     * Test user can generate reports.
     */
    public function test_report_policy_generate(): void
    {
        $policy = new ReportPolicy();

        $this->assertTrue($policy->generate($this->owner));
    }

    /**
     * Test user can view sales reports.
     */
    public function test_report_policy_view_sales(): void
    {
        $policy = new ReportPolicy();

        $this->assertTrue($policy->viewSales($this->owner));
    }

    /**
     * Test user can view marketing reports.
     */
    public function test_report_policy_view_marketing(): void
    {
        $policy = new ReportPolicy();

        $this->assertTrue($policy->viewMarketing($this->owner));
    }

    /**
     * Test user without business cannot view reports.
     */
    public function test_report_policy_no_business(): void
    {
        $policy = new ReportPolicy();
        $userWithoutBusiness = User::factory()->create();

        $this->assertFalse($policy->viewAny($userWithoutBusiness));
        $this->assertFalse($policy->generate($userWithoutBusiness));
    }
}
