<?php

namespace Tests\Feature;

use App\Models\Business;
use App\Models\KpiDailyActual;
use App\Models\KpiDefinition;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class KpiTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Business $business;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->business = Business::factory()->create(['user_id' => $this->user->id]);
        $this->user->teamBusinesses()->attach($this->business->id, ['role' => 'owner']);

        // Create KPI definitions required for foreign key constraints
        KpiDefinition::create([
            'category' => 'sales',
            'kpi_code' => 'leads_count',
            'kpi_name' => 'Leads Count',
            'kpi_name_uz' => 'Lidlar soni',
            'default_unit' => 'dona',
            'is_active' => true,
        ]);

        KpiDefinition::create([
            'category' => 'sales',
            'kpi_code' => 'sales_count',
            'kpi_name' => 'Sales Count',
            'kpi_name_uz' => 'Sotuvlar soni',
            'default_unit' => 'dona',
            'is_active' => true,
        ]);

        KpiDefinition::create([
            'category' => 'marketing',
            'kpi_code' => 'conversion_rate',
            'kpi_name' => 'Conversion Rate',
            'kpi_name_uz' => 'Konversiya darajasi',
            'default_unit' => '%',
            'is_active' => true,
        ]);
    }

    /**
     * Test KPI daily actual creation.
     */
    public function test_kpi_daily_actual_can_be_created(): void
    {
        session(['current_business_id' => $this->business->id]);

        $kpi = KpiDailyActual::create([
            'business_id' => $this->business->id,
            'kpi_code' => 'leads_count',
            'date' => now()->toDateString(),
            'planned_value' => 100,
            'actual_value' => 50,
            'unit' => 'dona',
        ]);

        $this->assertNotNull($kpi->id);
        $this->assertEquals('leads_count', $kpi->kpi_code);
        $this->assertEquals(50, $kpi->actual_value);
    }

    /**
     * Test KPI is isolated by business.
     */
    public function test_kpi_data_is_isolated_by_business(): void
    {
        $business2 = Business::factory()->create();

        KpiDailyActual::create([
            'business_id' => $this->business->id,
            'kpi_code' => 'leads_count',
            'date' => now()->toDateString(),
            'planned_value' => 150,
            'actual_value' => 100,
            'unit' => 'dona',
        ]);

        KpiDailyActual::create([
            'business_id' => $business2->id,
            'kpi_code' => 'leads_count',
            'date' => now()->toDateString(),
            'planned_value' => 250,
            'actual_value' => 200,
            'unit' => 'dona',
        ]);

        session(['current_business_id' => $this->business->id]);
        $kpis = KpiDailyActual::all();

        $this->assertCount(1, $kpis);
        $this->assertEquals(100, $kpis->first()->actual_value);
    }

    /**
     * Test multiple KPIs can exist for same date.
     */
    public function test_multiple_kpis_for_same_date(): void
    {
        session(['current_business_id' => $this->business->id]);

        KpiDailyActual::create([
            'business_id' => $this->business->id,
            'kpi_code' => 'leads_count',
            'date' => now()->toDateString(),
            'planned_value' => 100,
            'actual_value' => 50,
            'unit' => 'dona',
        ]);

        KpiDailyActual::create([
            'business_id' => $this->business->id,
            'kpi_code' => 'sales_count',
            'date' => now()->toDateString(),
            'planned_value' => 20,
            'actual_value' => 10,
            'unit' => 'dona',
        ]);

        $kpis = KpiDailyActual::whereDate('date', now()->toDateString())->get();
        $this->assertCount(2, $kpis);
    }

    /**
     * Test KPI date filtering.
     */
    public function test_kpi_date_filtering(): void
    {
        session(['current_business_id' => $this->business->id]);

        KpiDailyActual::create([
            'business_id' => $this->business->id,
            'kpi_code' => 'leads_count',
            'date' => now()->toDateString(),
            'planned_value' => 100,
            'actual_value' => 50,
            'unit' => 'dona',
        ]);

        KpiDailyActual::create([
            'business_id' => $this->business->id,
            'kpi_code' => 'leads_count',
            'date' => now()->subDays(7)->toDateString(),
            'planned_value' => 60,
            'actual_value' => 30,
            'unit' => 'dona',
        ]);

        $todayKpis = KpiDailyActual::whereDate('date', now()->toDateString())->get();
        $this->assertCount(1, $todayKpis);
        $this->assertEquals(50, $todayKpis->first()->actual_value);
    }

    /**
     * Test KPI can store decimal values.
     */
    public function test_kpi_decimal_values(): void
    {
        session(['current_business_id' => $this->business->id]);

        $kpi = KpiDailyActual::create([
            'business_id' => $this->business->id,
            'kpi_code' => 'conversion_rate',
            'date' => now()->toDateString(),
            'planned_value' => 30.00,
            'actual_value' => 25.75,
            'unit' => '%',
        ]);

        $this->assertEquals(25.75, $kpi->actual_value);
    }

    /**
     * Test KPI belongs to business.
     */
    public function test_kpi_belongs_to_business(): void
    {
        $kpi = KpiDailyActual::create([
            'business_id' => $this->business->id,
            'kpi_code' => 'leads_count',
            'date' => now()->toDateString(),
            'planned_value' => 100,
            'actual_value' => 50,
            'unit' => 'dona',
        ]);

        $this->assertEquals($this->business->id, $kpi->business_id);
    }

    /**
     * Test KPI update.
     */
    public function test_kpi_can_be_updated(): void
    {
        session(['current_business_id' => $this->business->id]);

        $kpi = KpiDailyActual::create([
            'business_id' => $this->business->id,
            'kpi_code' => 'leads_count',
            'date' => now()->toDateString(),
            'planned_value' => 100,
            'actual_value' => 50,
            'unit' => 'dona',
        ]);

        $kpi->update(['actual_value' => 75]);

        $this->assertEquals(75, $kpi->fresh()->actual_value);
    }

    /**
     * Test KPI delete.
     */
    public function test_kpi_can_be_deleted(): void
    {
        session(['current_business_id' => $this->business->id]);

        $kpi = KpiDailyActual::create([
            'business_id' => $this->business->id,
            'kpi_code' => 'leads_count',
            'date' => now()->toDateString(),
            'planned_value' => 100,
            'actual_value' => 50,
            'unit' => 'dona',
        ]);

        $kpiId = $kpi->id;
        $kpi->delete();

        // KpiDailyActual uses SoftDeletes, so check for soft deleted
        $this->assertSoftDeleted('kpi_daily_actuals', ['id' => $kpiId]);
    }

    /**
     * Test KPI by code query.
     */
    public function test_kpi_query_by_code(): void
    {
        session(['current_business_id' => $this->business->id]);

        KpiDailyActual::create([
            'business_id' => $this->business->id,
            'kpi_code' => 'leads_count',
            'date' => now()->toDateString(),
            'planned_value' => 100,
            'actual_value' => 50,
            'unit' => 'dona',
        ]);

        KpiDailyActual::create([
            'business_id' => $this->business->id,
            'kpi_code' => 'sales_count',
            'date' => now()->toDateString(),
            'planned_value' => 20,
            'actual_value' => 10,
            'unit' => 'dona',
        ]);

        $leadsKpis = KpiDailyActual::where('kpi_code', 'leads_count')->get();
        $this->assertCount(1, $leadsKpis);
    }
}
