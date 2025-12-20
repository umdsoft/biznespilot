<?php

namespace App\Http\Controllers;

use App\Models\AiMonthlyStrategy;
use App\Services\MonthlyStrategyService;
use App\Jobs\GenerateMonthlyStrategy;
use Illuminate\Http\Request;
use Inertia\Inertia;

class MonthlyStrategyController extends Controller
{
    protected MonthlyStrategyService $strategyService;

    public function __construct(MonthlyStrategyService $strategyService)
    {
        $this->strategyService = $strategyService;
    }

    /**
     * Display strategies list
     */
    public function index(Request $request)
    {
        $businessId = session('current_business_id');
        $business = \App\Models\Business::findOrFail($businessId);

        $year = $request->input('year', now()->year);

        $strategies = $this->strategyService->getStrategiesForBusiness($business, $year);
        $currentMonthStrategy = $this->strategyService->getCurrentMonthStrategy($business);

        // Get available years
        $availableYears = AiMonthlyStrategy::where('business_id', $businessId)
            ->selectRaw('DISTINCT year')
            ->orderBy('year', 'desc')
            ->pluck('year');

        return Inertia::render('Business/AI/MonthlyStrategy/Index', [
            'strategies' => $strategies,
            'currentMonthStrategy' => $currentMonthStrategy,
            'selectedYear' => $year,
            'availableYears' => $availableYears,
        ]);
    }

    /**
     * Show single strategy
     */
    public function show(AiMonthlyStrategy $strategy)
    {
        return Inertia::render('Business/AI/MonthlyStrategy/Show', [
            'strategy' => $strategy,
        ]);
    }

    /**
     * Generate new strategy
     */
    public function generate(Request $request)
    {
        $businessId = session('current_business_id');

        $request->validate([
            'year' => 'nullable|integer|min:2024|max:2030',
            'month' => 'nullable|integer|min:1|max:12',
        ]);

        $year = $request->input('year', now()->year);
        $month = $request->input('month', now()->month);

        try {
            $business = \App\Models\Business::findOrFail($businessId);
            $strategy = $this->strategyService->generateMonthlyStrategy($business, $year, $month);

            return redirect()
                ->route('ai.strategy.show', $strategy->id)
                ->with('success', 'Monthly strategy generated successfully');
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to generate strategy: ' . $e->getMessage());
        }
    }

    /**
     * Queue strategy generation
     */
    public function queueGeneration(Request $request)
    {
        $businessId = session('current_business_id');

        $request->validate([
            'year' => 'nullable|integer|min:2024|max:2030',
            'month' => 'nullable|integer|min:1|max:12',
        ]);

        GenerateMonthlyStrategy::dispatch(
            $businessId,
            $request->input('year'),
            $request->input('month')
        );

        return back()->with('success', 'Strategy generation queued. You will receive it shortly.');
    }

    /**
     * Approve strategy
     */
    public function approve(AiMonthlyStrategy $strategy)
    {
        $strategy->approve();

        return back()->with('success', 'Strategy approved and activated');
    }

    /**
     * Complete strategy with actual results
     */
    public function complete(Request $request, AiMonthlyStrategy $strategy)
    {
        $request->validate([
            'actual_results' => 'required|array',
        ]);

        $strategy->complete($request->actual_results);

        return back()->with('success', 'Strategy marked as completed with actual results');
    }

    /**
     * Archive strategy
     */
    public function archive(AiMonthlyStrategy $strategy)
    {
        $strategy->archive();

        return back()->with('success', 'Strategy archived');
    }

    /**
     * Delete strategy
     */
    public function destroy(AiMonthlyStrategy $strategy)
    {
        $strategy->delete();

        return redirect()
            ->route('ai.strategy.index')
            ->with('success', 'Strategy deleted successfully');
    }

    /**
     * Get strategy statistics (API endpoint)
     */
    public function statistics()
    {
        $businessId = session('current_business_id');
        $business = \App\Models\Business::findOrFail($businessId);

        $strategies = $this->strategyService->getStrategiesForBusiness($business);
        $currentStrategy = $this->strategyService->getCurrentMonthStrategy($business);

        return response()->json([
            'total_strategies' => $strategies->count(),
            'active_strategies' => $strategies->where('status', 'active')->count(),
            'completed_strategies' => $strategies->where('status', 'completed')->count(),
            'current_strategy' => $currentStrategy,
            'average_success_rate' => $strategies->where('status', 'completed')->avg('success_rate'),
        ]);
    }
}
