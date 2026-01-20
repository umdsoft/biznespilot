<?php

namespace App\Http\Controllers\Marketing;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Traits\HasCurrentBusiness;
use App\Models\MarketingTarget;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Inertia\Inertia;

class TargetController extends Controller
{
    use HasCurrentBusiness;

    public function index(Request $request)
    {
        $business = $this->getCurrentBusiness($request);

        if (!$business) {
            return redirect()->route('login');
        }

        $periodType = $request->get('period_type', 'monthly');
        $month = $request->get('month') ? Carbon::parse($request->get('month')) : now();

        $periodStart = match ($periodType) {
            'weekly' => $month->copy()->startOfWeek(),
            'monthly' => $month->copy()->startOfMonth(),
            'quarterly' => $month->copy()->firstOfQuarter(),
            default => $month->copy()->startOfMonth(),
        };

        $targets = MarketingTarget::where('business_id', $business->id)
            ->where('period_start', $periodStart)
            ->with('user')
            ->orderBy('target_type')
            ->get();

        return Inertia::render('Marketing/Targets/Index', [
            'targets' => $targets->map(fn($t) => [
                'id' => $t->id,
                'target_type' => $t->target_type,
                'type_label' => $t->getTypeLabel(),
                'target_value' => $t->target_value,
                'min_value' => $t->min_value,
                'max_value' => $t->max_value,
                'period_type' => $t->period_type,
                'period_start' => $t->period_start->format('Y-m-d'),
                'period_end' => $t->period_end?->format('Y-m-d'),
                'user_id' => $t->user_id,
                'user_name' => $t->user?->name,
                'is_active' => $t->is_active,
            ]),
            'targetTypes' => MarketingTarget::TARGET_TYPES,
            'periodType' => $periodType,
            'selectedMonth' => $month->format('Y-m'),
            'currentBusiness' => [
                'id' => $business->id,
                'name' => $business->name,
            ],
        ]);
    }

    public function store(Request $request)
    {
        $business = $this->getCurrentBusiness($request);

        if (!$business) {
            return response()->json(['error' => 'Business not found'], 404);
        }

        $validated = $request->validate([
            'target_type' => 'required|string',
            'target_value' => 'required|numeric|min:0',
            'min_value' => 'nullable|numeric|min:0',
            'max_value' => 'nullable|numeric|min:0',
            'period_type' => 'required|in:weekly,monthly,quarterly',
            'period_start' => 'required|date',
            'user_id' => 'nullable|exists:users,id',
            'description' => 'nullable|string|max:500',
        ]);

        $periodStart = Carbon::parse($validated['period_start']);
        $periodEnd = match ($validated['period_type']) {
            'weekly' => $periodStart->copy()->endOfWeek(),
            'monthly' => $periodStart->copy()->endOfMonth(),
            'quarterly' => $periodStart->copy()->lastOfQuarter(),
            default => $periodStart->copy()->endOfMonth(),
        };

        $target = MarketingTarget::create([
            'business_id' => $business->id,
            'user_id' => $validated['user_id'] ?? null,
            'target_type' => $validated['target_type'],
            'target_value' => $validated['target_value'],
            'min_value' => $validated['min_value'] ?? null,
            'max_value' => $validated['max_value'] ?? null,
            'period_type' => $validated['period_type'],
            'period_start' => $periodStart,
            'period_end' => $periodEnd,
            'description' => $validated['description'] ?? null,
            'is_active' => true,
        ]);

        return response()->json([
            'success' => true,
            'target' => $target,
            'message' => 'Target qo\'shildi',
        ]);
    }

    public function update(Request $request, MarketingTarget $target)
    {
        $business = $this->getCurrentBusiness($request);

        if (!$business || $target->business_id !== $business->id) {
            abort(404);
        }

        $validated = $request->validate([
            'target_value' => 'required|numeric|min:0',
            'min_value' => 'nullable|numeric|min:0',
            'max_value' => 'nullable|numeric|min:0',
            'description' => 'nullable|string|max:500',
            'is_active' => 'boolean',
        ]);

        $target->update($validated);

        return response()->json([
            'success' => true,
            'target' => $target,
            'message' => 'Target yangilandi',
        ]);
    }

    public function destroy(Request $request, MarketingTarget $target)
    {
        $business = $this->getCurrentBusiness($request);

        if (!$business || $target->business_id !== $business->id) {
            abort(404);
        }

        $target->delete();

        return response()->json([
            'success' => true,
            'message' => 'Target o\'chirildi',
        ]);
    }

    public function bulkStore(Request $request)
    {
        $business = $this->getCurrentBusiness($request);

        if (!$business) {
            return response()->json(['error' => 'Business not found'], 404);
        }

        $validated = $request->validate([
            'targets' => 'required|array',
            'targets.*.target_type' => 'required|string',
            'targets.*.target_value' => 'required|numeric|min:0',
            'targets.*.min_value' => 'nullable|numeric|min:0',
            'targets.*.max_value' => 'nullable|numeric|min:0',
            'period_type' => 'required|in:weekly,monthly,quarterly',
            'period_start' => 'required|date',
            'user_id' => 'nullable|exists:users,id',
        ]);

        $periodStart = Carbon::parse($validated['period_start']);
        $periodEnd = match ($validated['period_type']) {
            'weekly' => $periodStart->copy()->endOfWeek(),
            'monthly' => $periodStart->copy()->endOfMonth(),
            'quarterly' => $periodStart->copy()->lastOfQuarter(),
            default => $periodStart->copy()->endOfMonth(),
        };

        $created = 0;
        foreach ($validated['targets'] as $targetData) {
            MarketingTarget::updateOrCreate(
                [
                    'business_id' => $business->id,
                    'user_id' => $validated['user_id'] ?? null,
                    'target_type' => $targetData['target_type'],
                    'period_start' => $periodStart,
                    'period_type' => $validated['period_type'],
                ],
                [
                    'target_value' => $targetData['target_value'],
                    'min_value' => $targetData['min_value'] ?? null,
                    'max_value' => $targetData['max_value'] ?? null,
                    'period_end' => $periodEnd,
                    'is_active' => true,
                ]
            );
            $created++;
        }

        return response()->json([
            'success' => true,
            'created_count' => $created,
            'message' => $created . ' ta target saqlandi',
        ]);
    }

    public function copyFromPrevious(Request $request)
    {
        $business = $this->getCurrentBusiness($request);

        if (!$business) {
            return response()->json(['error' => 'Business not found'], 404);
        }

        $validated = $request->validate([
            'period_type' => 'required|in:weekly,monthly,quarterly',
            'period_start' => 'required|date',
        ]);

        $targetPeriodStart = Carbon::parse($validated['period_start']);
        $sourcePeriodStart = match ($validated['period_type']) {
            'weekly' => $targetPeriodStart->copy()->subWeek(),
            'monthly' => $targetPeriodStart->copy()->subMonth(),
            'quarterly' => $targetPeriodStart->copy()->subQuarter(),
            default => $targetPeriodStart->copy()->subMonth(),
        };

        $previousTargets = MarketingTarget::where('business_id', $business->id)
            ->where('period_start', $sourcePeriodStart)
            ->where('period_type', $validated['period_type'])
            ->get();

        if ($previousTargets->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'Oldingi davr targetlari topilmadi',
            ], 404);
        }

        $targetPeriodEnd = match ($validated['period_type']) {
            'weekly' => $targetPeriodStart->copy()->endOfWeek(),
            'monthly' => $targetPeriodStart->copy()->endOfMonth(),
            'quarterly' => $targetPeriodStart->copy()->lastOfQuarter(),
            default => $targetPeriodStart->copy()->endOfMonth(),
        };

        $copied = 0;
        foreach ($previousTargets as $prevTarget) {
            MarketingTarget::create([
                'business_id' => $business->id,
                'user_id' => $prevTarget->user_id,
                'target_type' => $prevTarget->target_type,
                'target_value' => $prevTarget->target_value,
                'min_value' => $prevTarget->min_value,
                'max_value' => $prevTarget->max_value,
                'period_type' => $validated['period_type'],
                'period_start' => $targetPeriodStart,
                'period_end' => $targetPeriodEnd,
                'description' => $prevTarget->description,
                'is_active' => true,
            ]);
            $copied++;
        }

        return response()->json([
            'success' => true,
            'copied_count' => $copied,
            'message' => $copied . ' ta target nusxalandi',
        ]);
    }
}
