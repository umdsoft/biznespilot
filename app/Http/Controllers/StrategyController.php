<?php

namespace App\Http\Controllers;

use App\Models\AnnualStrategy;
use App\Models\QuarterlyPlan;
use App\Models\MonthlyPlan;
use App\Models\WeeklyPlan;
use App\Models\StrategyTemplate;
use App\Models\KpiTarget;
use App\Models\BudgetAllocation;
use App\Services\StrategyBuilderService;
use App\Services\KPITargetService;
use App\Services\BudgetAllocationService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class StrategyController extends Controller
{
    public function __construct(
        private StrategyBuilderService $strategyService,
        private KPITargetService $kpiService,
        private BudgetAllocationService $budgetService
    ) {}

    /**
     * Strategy dashboard / index
     */
    public function index(Request $request)
    {
        $business = $request->user()->currentBusiness;
        $year = $request->input('year', now()->year);

        // Get current annual strategy
        $annualStrategy = AnnualStrategy::where('business_id', $business->id)
            ->forYear($year)
            ->first();

        // Get current quarter plan
        $currentQuarter = ceil(now()->month / 3);
        $quarterlyPlan = $annualStrategy?->quarterlyPlans()
            ->where('quarter', $currentQuarter)
            ->first();

        // Get current month plan
        $monthlyPlan = MonthlyPlan::where('business_id', $business->id)
            ->forYear($year)
            ->forMonth(now()->month)
            ->first();

        // Get current week plan
        $weeklyPlan = WeeklyPlan::where('business_id', $business->id)
            ->current()
            ->first();

        // Get KPI summary
        $kpiSummary = $this->kpiService->getKPISummary($business, 'monthly', $year, now()->month);

        // Get budget summary
        $budgetSummary = $this->budgetService->getBudgetSummary($business, 'monthly', $year, now()->month);

        return Inertia::render('Strategy/Index', [
            'annual_strategy' => $annualStrategy,
            'quarterly_plan' => $quarterlyPlan,
            'monthly_plan' => $monthlyPlan,
            'weekly_plan' => $weeklyPlan,
            'kpi_summary' => $kpiSummary,
            'budget_summary' => $budgetSummary,
            'year' => $year,
            'current_quarter' => $currentQuarter,
            'current_month' => now()->month,
            'has_strategy' => $annualStrategy !== null,
        ]);
    }

    /**
     * Strategy wizard - step by step creation
     */
    public function wizard(Request $request)
    {
        $business = $request->user()->currentBusiness;
        $step = $request->input('step', 1);
        $year = $request->input('year', now()->year);

        // Get templates
        $templates = StrategyTemplate::active()->get()->groupBy('type');

        // Get last diagnostic
        $diagnostic = $business->diagnostics()
            ->completed()
            ->latest()
            ->first();

        // Get existing strategy if any
        $existingStrategy = AnnualStrategy::where('business_id', $business->id)
            ->forYear($year)
            ->first();

        return Inertia::render('Strategy/Wizard', [
            'step' => $step,
            'year' => $year,
            'templates' => $templates,
            'diagnostic' => $diagnostic,
            'existing_strategy' => $existingStrategy,
            'business' => $business->only(['id', 'name', 'industry', 'business_type']),
        ]);
    }

    /**
     * Create annual strategy
     */
    public function createAnnual(Request $request)
    {
        $validated = $request->validate([
            'year' => 'required|integer|min:2024|max:2030',
            'title' => 'nullable|string|max:255',
            'vision_statement' => 'nullable|string|max:1000',
            'revenue_target' => 'nullable|numeric|min:0',
            'annual_budget' => 'nullable|numeric|min:0',
            'strategic_goals' => 'nullable|array',
            'focus_areas' => 'nullable|array',
            'primary_channels' => 'nullable|array',
            'use_ai' => 'boolean',
        ]);

        $business = $request->user()->currentBusiness;

        // Check if already exists
        $existing = AnnualStrategy::where('business_id', $business->id)
            ->forYear($validated['year'])
            ->first();

        if ($existing) {
            return back()->withErrors(['year' => 'Bu yil uchun strategiya allaqachon mavjud']);
        }

        // Get diagnostic if AI is requested
        $diagnostic = null;
        if ($validated['use_ai'] ?? false) {
            $diagnostic = $business->diagnostics()->completed()->latest()->first();
        }

        // Create strategy
        if ($diagnostic && ($validated['use_ai'] ?? false)) {
            $strategy = $this->strategyService->createAnnualStrategy($business, $validated['year'], $diagnostic);
        } else {
            $strategy = AnnualStrategy::create([
                'business_id' => $business->id,
                'year' => $validated['year'],
                'title' => $validated['title'] ?? "{$validated['year']}-yil strategiyasi",
                'status' => 'draft',
                'vision_statement' => $validated['vision_statement'] ?? null,
                'revenue_target' => $validated['revenue_target'] ?? null,
                'annual_budget' => $validated['annual_budget'] ?? null,
                'strategic_goals' => $validated['strategic_goals'] ?? [],
                'focus_areas' => $validated['focus_areas'] ?? [],
                'primary_channels' => $validated['primary_channels'] ?? [],
            ]);
        }

        return redirect()->route('strategy.annual.show', $strategy->id)
            ->with('success', 'Yillik strategiya yaratildi');
    }

    /**
     * Show annual strategy
     */
    public function showAnnual(Request $request, AnnualStrategy $annual)
    {
        $this->authorize('view', $annual);

        $annual->load(['quarterlyPlans', 'kpiTargets', 'budgetAllocations']);

        return Inertia::render('Strategy/Annual/Show', [
            'strategy' => $annual,
            'quarters' => $annual->quarterlyPlans,
            'kpis' => $annual->kpiTargets()->forPeriod('annual')->get(),
            'budget' => $annual->budgetAllocations()->forPeriod('annual')->get(),
        ]);
    }

    /**
     * Update annual strategy
     */
    public function updateAnnual(Request $request, AnnualStrategy $annual)
    {
        $this->authorize('update', $annual);

        $validated = $request->validate([
            'title' => 'nullable|string|max:255',
            'vision_statement' => 'nullable|string|max:1000',
            'mission_statement' => 'nullable|string|max:1000',
            'executive_summary' => 'nullable|string|max:2000',
            'revenue_target' => 'nullable|numeric|min:0',
            'profit_target' => 'nullable|numeric|min:0',
            'annual_budget' => 'nullable|numeric|min:0',
            'strategic_goals' => 'nullable|array',
            'focus_areas' => 'nullable|array',
            'primary_channels' => 'nullable|array',
        ]);

        $annual->update($validated);

        return back()->with('success', 'Strategiya yangilandi');
    }

    /**
     * Generate quarterly plans from annual strategy
     */
    public function generateQuarters(Request $request, AnnualStrategy $annual)
    {
        $this->authorize('update', $annual);

        $quarters = $this->strategyService->generateAllQuarters($annual);

        return back()->with('success', '4 ta choraklik reja yaratildi');
    }

    /**
     * Show quarterly plan
     */
    public function showQuarterly(Request $request, QuarterlyPlan $quarterly)
    {
        $this->authorize('view', $quarterly);

        $quarterly->load(['monthlyPlans', 'kpiTargets', 'budgetAllocations', 'annualStrategy']);

        return Inertia::render('Strategy/Quarterly/Show', [
            'plan' => $quarterly,
            'months' => $quarterly->monthlyPlans,
            'kpis' => $quarterly->kpiTargets,
            'budget' => $quarterly->budgetAllocations,
            'annual' => $quarterly->annualStrategy,
        ]);
    }

    /**
     * Update quarterly plan
     */
    public function updateQuarterly(Request $request, QuarterlyPlan $quarterly)
    {
        $this->authorize('update', $quarterly);

        $validated = $request->validate([
            'title' => 'nullable|string|max:255',
            'theme' => 'nullable|string|max:255',
            'executive_summary' => 'nullable|string|max:2000',
            'quarterly_objectives' => 'nullable|array',
            'goals' => 'nullable|array',
            'initiatives' => 'nullable|array',
            'campaigns' => 'nullable|array',
            'budget' => 'nullable|numeric|min:0',
        ]);

        $quarterly->update($validated);

        return back()->with('success', 'Choraklik reja yangilandi');
    }

    /**
     * Generate monthly plans from quarterly plan
     */
    public function generateMonths(Request $request, QuarterlyPlan $quarterly)
    {
        $this->authorize('update', $quarterly);

        $months = $this->strategyService->generateAllMonths($quarterly);

        return back()->with('success', '3 ta oylik reja yaratildi');
    }

    /**
     * Show monthly plan
     */
    public function showMonthly(Request $request, MonthlyPlan $monthly)
    {
        $this->authorize('view', $monthly);

        $monthly->load(['weeklyPlans', 'contentItems', 'kpiTargets', 'budgetAllocations', 'quarterlyPlan']);

        return Inertia::render('Strategy/Monthly/Show', [
            'plan' => $monthly,
            'weeks' => $monthly->weeklyPlans,
            'content' => $monthly->contentItems()->upcoming()->limit(10)->get(),
            'kpis' => $monthly->kpiTargets,
            'budget' => $monthly->budgetAllocations,
            'quarterly' => $monthly->quarterlyPlan,
        ]);
    }

    /**
     * Update monthly plan
     */
    public function updateMonthly(Request $request, MonthlyPlan $monthly)
    {
        $this->authorize('update', $monthly);

        $validated = $request->validate([
            'title' => 'nullable|string|max:255',
            'theme' => 'nullable|string|max:255',
            'executive_summary' => 'nullable|string|max:2000',
            'monthly_objectives' => 'nullable|array',
            'goals' => 'nullable|array',
            'content_themes' => 'nullable|array',
            'campaigns' => 'nullable|array',
            'budget' => 'nullable|numeric|min:0',
            'week_1_plan' => 'nullable|array',
            'week_2_plan' => 'nullable|array',
            'week_3_plan' => 'nullable|array',
            'week_4_plan' => 'nullable|array',
        ]);

        $monthly->update($validated);

        return back()->with('success', 'Oylik reja yangilandi');
    }

    /**
     * Generate weekly plans from monthly plan
     */
    public function generateWeeks(Request $request, MonthlyPlan $monthly)
    {
        $this->authorize('update', $monthly);

        $weeks = $this->strategyService->generateAllWeeks($monthly);

        return back()->with('success', 'Haftalik rejalar yaratildi');
    }

    /**
     * Show weekly plan
     */
    public function showWeekly(Request $request, WeeklyPlan $weekly)
    {
        $this->authorize('view', $weekly);

        $weekly->load(['contentCalendarItems', 'monthlyPlan']);

        return Inertia::render('Strategy/Weekly/Show', [
            'plan' => $weekly,
            'content' => $weekly->contentCalendarItems,
            'monthly' => $weekly->monthlyPlan,
        ]);
    }

    /**
     * Update weekly plan
     */
    public function updateWeekly(Request $request, WeeklyPlan $weekly)
    {
        $this->authorize('update', $weekly);

        $validated = $request->validate([
            'title' => 'nullable|string|max:255',
            'weekly_focus' => 'nullable|string|max:255',
            'priorities' => 'nullable|array',
            'goals' => 'nullable|array',
            'monday' => 'nullable|array',
            'tuesday' => 'nullable|array',
            'wednesday' => 'nullable|array',
            'thursday' => 'nullable|array',
            'friday' => 'nullable|array',
            'saturday' => 'nullable|array',
            'sunday' => 'nullable|array',
            'tasks' => 'nullable|array',
        ]);

        $weekly->update($validated);

        return back()->with('success', 'Haftalik reja yangilandi');
    }

    /**
     * Add task to weekly plan
     */
    public function addTask(Request $request, WeeklyPlan $weekly)
    {
        $this->authorize('update', $weekly);

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string|max:500',
            'day' => 'nullable|string',
            'priority' => 'nullable|integer|min:0|max:3',
        ]);

        $weekly->addTask($validated);

        return back()->with('success', 'Vazifa qo\'shildi');
    }

    /**
     * Complete task
     */
    public function completeTask(Request $request, WeeklyPlan $weekly, string $taskId)
    {
        $this->authorize('update', $weekly);

        $weekly->completeTask($taskId);

        return back()->with('success', 'Vazifa bajarildi');
    }

    /**
     * Approve plan (change status to active)
     */
    public function approve(Request $request, string $type, int $id)
    {
        $model = match($type) {
            'annual' => AnnualStrategy::findOrFail($id),
            'quarterly' => QuarterlyPlan::findOrFail($id),
            'monthly' => MonthlyPlan::findOrFail($id),
            'weekly' => WeeklyPlan::findOrFail($id),
        };

        $this->authorize('update', $model);

        $model->approve();

        return back()->with('success', 'Reja tasdiqlandi');
    }

    /**
     * Complete plan
     */
    public function complete(Request $request, string $type, int $id)
    {
        $validated = $request->validate([
            'actual_results' => 'nullable|array',
        ]);

        $model = match($type) {
            'annual' => AnnualStrategy::findOrFail($id),
            'quarterly' => QuarterlyPlan::findOrFail($id),
            'monthly' => MonthlyPlan::findOrFail($id),
            'weekly' => WeeklyPlan::findOrFail($id),
        };

        $this->authorize('update', $model);

        $model->complete($validated['actual_results'] ?? []);

        return back()->with('success', 'Reja tugallandi');
    }

    /**
     * Get KPI targets for a plan
     */
    public function getKPIs(Request $request, string $type, int $id)
    {
        $model = match($type) {
            'annual' => AnnualStrategy::findOrFail($id),
            'quarterly' => QuarterlyPlan::findOrFail($id),
            'monthly' => MonthlyPlan::findOrFail($id),
            'weekly' => WeeklyPlan::findOrFail($id),
        };

        $this->authorize('view', $model);

        return response()->json([
            'kpis' => $model->kpiTargets ?? collect(),
        ]);
    }

    /**
     * Update KPI value
     */
    public function updateKPI(Request $request, KpiTarget $kpi)
    {
        $this->authorize('update', $kpi);

        $validated = $request->validate([
            'current_value' => 'required|numeric',
        ]);

        $this->kpiService->updateKPIValue($kpi, $validated['current_value']);

        return back()->with('success', 'KPI yangilandi');
    }

    /**
     * Get budget allocations for a plan
     */
    public function getBudget(Request $request, string $type, int $id)
    {
        $model = match($type) {
            'annual' => AnnualStrategy::findOrFail($id),
            'quarterly' => QuarterlyPlan::findOrFail($id),
            'monthly' => MonthlyPlan::findOrFail($id),
            'weekly' => WeeklyPlan::findOrFail($id),
        };

        $this->authorize('view', $model);

        return response()->json([
            'allocations' => $model->budgetAllocations ?? collect(),
        ]);
    }

    /**
     * Record budget spending
     */
    public function recordSpending(Request $request, BudgetAllocation $allocation)
    {
        $this->authorize('update', $allocation);

        $validated = $request->validate([
            'amount' => 'required|numeric|min:0',
            'description' => 'nullable|string|max:255',
        ]);

        $this->budgetService->recordSpending($allocation, $validated['amount'], $validated['description']);

        return back()->with('success', 'Xarajat qayd etildi');
    }

    /**
     * Get templates list
     */
    public function templates(Request $request)
    {
        $type = $request->input('type');

        $query = StrategyTemplate::active();

        if ($type) {
            $query->forType($type);
        }

        return response()->json([
            'templates' => $query->get(),
        ]);
    }

    /**
     * Build complete strategy from scratch
     */
    public function buildComplete(Request $request)
    {
        $validated = $request->validate([
            'year' => 'required|integer|min:2024|max:2030',
        ]);

        $business = $request->user()->currentBusiness;

        // Get latest diagnostic
        $diagnostic = $business->diagnostics()->completed()->latest()->first();

        // Build complete strategy
        $result = $this->strategyService->buildCompleteStrategy($business, $validated['year'], $diagnostic);

        return redirect()->route('strategy.annual.show', $result['annual']->id)
            ->with('success', 'To\'liq strategiya yaratildi');
    }
}
