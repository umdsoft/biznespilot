<?php

namespace App\Http\Controllers\Marketing;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Traits\HasCurrentBusiness;
use App\Models\MarketingBonus;
use App\Models\MarketingPenalty;
use App\Services\MarketingBonusCalculatorService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Inertia\Inertia;

class BonusController extends Controller
{
    use HasCurrentBusiness;

    public function __construct(
        private MarketingBonusCalculatorService $bonusService
    ) {}

    public function index(Request $request)
    {
        $business = $this->getCurrentBusiness($request);

        if (!$business) {
            return redirect()->route('login');
        }

        $user = auth()->user();
        $month = $request->get('month') ? Carbon::parse($request->get('month')) : now();

        // Get user's bonuses
        $bonuses = MarketingBonus::where('business_id', $business->id)
            ->where('user_id', $user->id)
            ->orderBy('period_start', 'desc')
            ->limit(12)
            ->get();

        // Get user's penalties
        $penalties = MarketingPenalty::where('business_id', $business->id)
            ->where('user_id', $user->id)
            ->orderBy('date', 'desc')
            ->limit(20)
            ->get();

        // Current month bonus preview
        $currentBonus = MarketingBonus::where('business_id', $business->id)
            ->where('user_id', $user->id)
            ->where('period_start', now()->startOfMonth())
            ->first();

        // Penalty summary
        $penaltySummary = $this->bonusService->getPenaltySummary($business, $user, now());

        return Inertia::render('Marketing/Bonus/Index', [
            'bonuses' => $bonuses,
            'penalties' => $penalties,
            'currentBonus' => $currentBonus,
            'penaltySummary' => $penaltySummary,
            'selectedMonth' => $month->format('Y-m'),
            'currentBusiness' => [
                'id' => $business->id,
                'name' => $business->name,
            ],
        ]);
    }

    public function show(Request $request, MarketingBonus $bonus)
    {
        $business = $this->getCurrentBusiness($request);

        if (!$business || $bonus->business_id !== $business->id) {
            abort(404);
        }

        $bonus->load(['user', 'penalties']);

        return Inertia::render('Marketing/Bonus/Show', [
            'bonus' => $bonus,
            'currentBusiness' => [
                'id' => $business->id,
                'name' => $business->name,
            ],
        ]);
    }

    public function history(Request $request)
    {
        $business = $this->getCurrentBusiness($request);

        if (!$business) {
            return response()->json(['error' => 'Business not found'], 404);
        }

        $user = auth()->user();
        $months = $request->get('months', 6);

        $bonuses = MarketingBonus::where('business_id', $business->id)
            ->where('user_id', $user->id)
            ->orderBy('period_start', 'desc')
            ->limit($months)
            ->get();

        return response()->json([
            'bonuses' => $bonuses->map(fn($b) => [
                'id' => $b->id,
                'period' => $b->period_start->format('Y-m'),
                'base_amount' => $b->base_amount,
                'penalty_deduction' => $b->penalty_deduction,
                'final_amount' => $b->final_amount,
                'status' => $b->status,
            ]),
            'total' => $bonuses->sum('final_amount'),
            'average' => $bonuses->avg('final_amount'),
        ]);
    }

    // Admin methods

    public function adminIndex(Request $request)
    {
        $business = $this->getCurrentBusiness($request);

        if (!$business) {
            return redirect()->route('login');
        }

        $month = $request->get('month') ? Carbon::parse($request->get('month')) : now();

        $bonusSummary = $this->bonusService->getBonusSummary($business, $month);

        $bonuses = MarketingBonus::where('business_id', $business->id)
            ->where('period_start', $month->copy()->startOfMonth())
            ->with('user')
            ->get();

        return Inertia::render('Marketing/Bonus/Admin', [
            'summary' => $bonusSummary,
            'bonuses' => $bonuses,
            'selectedMonth' => $month->format('Y-m'),
            'currentBusiness' => [
                'id' => $business->id,
                'name' => $business->name,
            ],
        ]);
    }

    public function approve(Request $request, MarketingBonus $bonus)
    {
        $business = $this->getCurrentBusiness($request);

        if (!$business || $bonus->business_id !== $business->id) {
            abort(404);
        }

        $this->bonusService->approveBonus($bonus, auth()->id());

        return back()->with('success', 'Bonus tasdiqlandi');
    }

    public function markPaid(Request $request, MarketingBonus $bonus)
    {
        $business = $this->getCurrentBusiness($request);

        if (!$business || $bonus->business_id !== $business->id) {
            abort(404);
        }

        $notes = $request->input('notes');
        $this->bonusService->markAsPaid($bonus, $notes);

        return back()->with('success', 'Bonus to\'langan deb belgilandi');
    }

    public function recalculate(Request $request)
    {
        $business = $this->getCurrentBusiness($request);

        if (!$business) {
            return response()->json(['error' => 'Business not found'], 404);
        }

        $month = $request->get('month') ? Carbon::parse($request->get('month')) : now()->subMonth();

        $bonuses = $this->bonusService->calculateAllBonuses($business, $month);

        return response()->json([
            'success' => true,
            'message' => $bonuses->count() . ' ta bonus qayta hisoblandi',
            'total_amount' => $bonuses->sum('final_amount'),
        ]);
    }

    // Penalty methods

    public function penalties(Request $request)
    {
        $business = $this->getCurrentBusiness($request);

        if (!$business) {
            return redirect()->route('login');
        }

        $penalties = MarketingPenalty::where('business_id', $business->id)
            ->with('user')
            ->orderBy('date', 'desc')
            ->paginate(20);

        return Inertia::render('Marketing/Bonus/Penalties', [
            'penalties' => $penalties,
            'penaltyTypes' => MarketingPenalty::TYPES,
            'currentBusiness' => [
                'id' => $business->id,
                'name' => $business->name,
            ],
        ]);
    }

    public function storePenalty(Request $request)
    {
        $business = $this->getCurrentBusiness($request);

        if (!$business) {
            return response()->json(['error' => 'Business not found'], 404);
        }

        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'type' => 'required|string',
            'reason' => 'required|string|max:255',
            'description' => 'nullable|string',
            'amount' => 'required|numeric|min:0',
        ]);

        $penalty = $this->bonusService->createPenalty(
            $business,
            \App\Models\User::find($validated['user_id']),
            $validated['type'],
            $validated['reason'],
            $validated['amount'],
            $validated['description'] ?? null
        );

        return response()->json([
            'success' => true,
            'penalty' => $penalty,
            'message' => 'Jarima qo\'shildi',
        ]);
    }

    public function disputePenalty(Request $request, MarketingPenalty $penalty)
    {
        $business = $this->getCurrentBusiness($request);

        if (!$business || $penalty->business_id !== $business->id) {
            abort(404);
        }

        // Only the penalty owner can dispute
        if ($penalty->user_id !== auth()->id()) {
            abort(403);
        }

        $validated = $request->validate([
            'reason' => 'required|string|max:500',
        ]);

        $penalty->dispute($validated['reason']);

        return back()->with('success', 'Jarima bo\'yicha da\'vo bildirildi');
    }

    public function waivePenalty(Request $request, MarketingPenalty $penalty)
    {
        $business = $this->getCurrentBusiness($request);

        if (!$business || $penalty->business_id !== $business->id) {
            abort(404);
        }

        $penalty->waive();

        return back()->with('success', 'Jarima bekor qilindi');
    }
}
