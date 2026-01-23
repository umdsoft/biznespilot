<?php

namespace Tests\Feature;

use App\Models\Business;
use App\Models\Lead;
use App\Models\MarketingChannel;
use App\Models\User;
use App\Models\WeeklyAnalytics;
use App\Services\WeeklyAnalyticsService;
use Carbon\Carbon;
use Illuminate\Support\Str;
use Tests\TestCase;

class WeeklyAnalyticsTest extends TestCase
{
    protected User $user;
    protected Business $business;
    protected WeeklyAnalyticsService $service;

    protected function setUp(): void
    {
        parent::setUp();

        // Use existing database - don't refresh
        $this->user = User::first() ?? User::factory()->create();
        $this->business = Business::first() ?? Business::factory()->create(['user_id' => $this->user->id]);
        $this->service = app(WeeklyAnalyticsService::class);
    }

    public function test_can_generate_weekly_report(): void
    {
        $weekStart = now()->startOfWeek();

        // Delete existing to ensure fresh
        WeeklyAnalytics::where('business_id', $this->business->id)
            ->where('week_start', $weekStart->format('Y-m-d'))
            ->delete();

        $report = $this->service->generateWeeklyReport($this->business, $weekStart);

        $this->assertInstanceOf(WeeklyAnalytics::class, $report);
        $this->assertEquals($this->business->id, $report->business_id);
        $this->assertEquals($weekStart->format('Y-m-d'), $report->week_start->format('Y-m-d'));
        $this->assertIsArray($report->summary_stats);
    }

    public function test_does_not_duplicate_report_for_same_week(): void
    {
        $weekStart = now()->startOfWeek();

        $report1 = $this->service->generateWeeklyReport($this->business, $weekStart);
        $report2 = $this->service->generateWeeklyReport($this->business, $weekStart);

        $this->assertEquals($report1->id, $report2->id);
    }

    public function test_summary_stats_contains_required_keys(): void
    {
        $weekStart = now()->startOfWeek();

        $report = $this->service->generateWeeklyReport($this->business, $weekStart);

        $requiredKeys = [
            'total_leads', 'won', 'lost', 'in_progress', 'conversion_rate',
            'total_revenue', 'lost_revenue', 'pipeline_value', 'avg_deal_value',
            'win_loss_ratio', 'hot_leads', 'vs_last_week',
        ];

        foreach ($requiredKeys as $key) {
            $this->assertArrayHasKey($key, $report->summary_stats, "Missing key: {$key}");
        }
    }

    public function test_extended_stats_are_saved(): void
    {
        $weekStart = now()->startOfWeek();

        $report = $this->service->generateWeeklyReport($this->business, $weekStart);

        $this->assertIsArray($report->regional_stats);
        $this->assertIsArray($report->qualification_stats);
        $this->assertIsArray($report->call_stats);
        $this->assertIsArray($report->task_stats);
        $this->assertIsArray($report->pipeline_stats);
    }

    public function test_week_label_format(): void
    {
        $weekStart = now()->startOfWeek();

        $report = $this->service->generateWeeklyReport($this->business, $weekStart);

        // Should contain day numbers and month name
        $this->assertNotEmpty($report->week_label);
        $this->assertMatchesRegularExpression('/\d+-\d+\s+\w+|\d+\s+\w+\s+-\s+\d+\s+\w+/', $report->week_label);
    }

    public function test_has_ai_analysis_returns_false_when_no_analysis(): void
    {
        $weekStart = now()->startOfWeek();

        // Delete existing
        WeeklyAnalytics::where('business_id', $this->business->id)
            ->where('week_start', $weekStart->format('Y-m-d'))
            ->delete();

        $report = $this->service->generateWeeklyReport($this->business, $weekStart);

        $this->assertFalse($report->hasAiAnalysis());
    }

    public function test_index_page_loads_for_authenticated_user(): void
    {
        $this->actingAs($this->user);
        session(['current_business_id' => $this->business->id]);

        $response = $this->get(route('business.analytics.weekly-report'));

        $response->assertStatus(200);
    }

    public function test_get_week_data_returns_json(): void
    {
        $this->actingAs($this->user);
        session(['current_business_id' => $this->business->id]);

        $response = $this->getJson(route('business.analytics.api.weekly-report'));

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'id', 'week_start', 'week_end', 'week_label', 'summary',
            'channels', 'operators', 'time_stats', 'lost_reasons', 'trends',
            'regional', 'qualification', 'calls', 'tasks', 'pipeline', 'ai',
        ]);
    }

    public function test_regenerate_creates_new_report(): void
    {
        $this->actingAs($this->user);
        session(['current_business_id' => $this->business->id]);

        // First create a report
        $this->service->generateWeeklyReport($this->business, now()->startOfWeek());

        // Then regenerate
        $response = $this->postJson(route('business.analytics.api.weekly-report.regenerate'));

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);
    }

    public function test_time_stats_has_correct_structure(): void
    {
        $weekStart = now()->startOfWeek();

        $report = $this->service->generateWeeklyReport($this->business, $weekStart);

        $this->assertArrayHasKey('by_day', $report->time_stats);
        $this->assertArrayHasKey('by_hour', $report->time_stats);
        $this->assertArrayHasKey('best_day', $report->time_stats);
        $this->assertArrayHasKey('worst_day', $report->time_stats);
    }

    public function test_lost_reason_stats_has_correct_structure(): void
    {
        $weekStart = now()->startOfWeek();

        $report = $this->service->generateWeeklyReport($this->business, $weekStart);

        $this->assertArrayHasKey('total_lost', $report->lost_reason_stats);
        $this->assertArrayHasKey('total_value_lost', $report->lost_reason_stats);
        $this->assertArrayHasKey('reasons', $report->lost_reason_stats);
    }

    public function test_qualification_stats_has_mql_sql_data(): void
    {
        $weekStart = now()->startOfWeek();

        $report = $this->service->generateWeeklyReport($this->business, $weekStart);

        $this->assertArrayHasKey('mql', $report->qualification_stats);
        $this->assertArrayHasKey('sql', $report->qualification_stats);
        $this->assertArrayHasKey('mql_to_sql', $report->qualification_stats);
    }
}
