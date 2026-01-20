<?php

namespace App\Http\Controllers\Shared;

use App\Http\Controllers\Controller;
use App\Models\PipelineAutomationRule;
use App\Models\PipelineStage;
use App\Services\Pipeline\PipelineBottleneckService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PipelineAutomationController extends Controller
{
    public function __construct(
        private PipelineBottleneckService $bottleneckService
    ) {}

    /**
     * Avtomatlashtirish qoidalari ro'yxati
     */
    public function index(Request $request): Response
    {
        $business = $request->user()->currentBusiness;

        $rules = PipelineAutomationRule::forBusiness($business->id)
            ->orderBy('trigger_type')
            ->orderByDesc('priority')
            ->get()
            ->map(function ($rule) use ($business) {
                $fromStage = $rule->from_stage_slug
                    ? PipelineStage::where('business_id', $business->id)
                        ->where('slug', $rule->from_stage_slug)
                        ->first()
                    : null;

                $toStage = PipelineStage::where('business_id', $business->id)
                    ->where('slug', $rule->to_stage_slug)
                    ->first();

                return [
                    'id' => $rule->id,
                    'trigger_type' => $rule->trigger_type,
                    'trigger_info' => $rule->trigger_info,
                    'trigger_conditions' => $rule->trigger_conditions,
                    'from_stage_slug' => $rule->from_stage_slug,
                    'from_stage_name' => $fromStage?->name,
                    'to_stage_slug' => $rule->to_stage_slug,
                    'to_stage_name' => $toStage?->name,
                    'to_stage_color' => $toStage?->color,
                    'only_if_current_stage' => $rule->only_if_current_stage,
                    'prevent_backward' => $rule->prevent_backward,
                    'is_active' => $rule->is_active,
                    'priority' => $rule->priority,
                ];
            });

        $stages = PipelineStage::where('business_id', $business->id)
            ->ordered()
            ->get(['id', 'name', 'slug', 'color', 'is_won', 'is_lost']);

        $triggerTypes = PipelineAutomationRule::TRIGGER_TYPES;

        // Bottleneck ma'lumotlari
        $bottlenecks = $this->bottleneckService->detectBottlenecks($business);
        $pipelineStats = $this->bottleneckService->getPipelineStats($business);

        return Inertia::render('Shared/PipelineAutomation/Index', [
            'rules' => $rules,
            'stages' => $stages,
            'triggerTypes' => $triggerTypes,
            'bottlenecks' => $bottlenecks,
            'pipelineStats' => $pipelineStats,
        ]);
    }

    /**
     * Yangi qoida yaratish
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'trigger_type' => 'required|string|in:' . implode(',', array_keys(PipelineAutomationRule::TRIGGER_TYPES)),
            'trigger_conditions' => 'nullable|array',
            'from_stage_slug' => 'nullable|string',
            'to_stage_slug' => 'required|string',
            'only_if_current_stage' => 'boolean',
            'prevent_backward' => 'boolean',
            'priority' => 'integer|min:0|max:100',
        ]);

        $business = $request->user()->currentBusiness;

        // Stage mavjudligini tekshirish
        $toStageExists = PipelineStage::where('business_id', $business->id)
            ->where('slug', $validated['to_stage_slug'])
            ->exists();

        if (! $toStageExists) {
            return back()->withErrors(['to_stage_slug' => 'Maqsad bosqich topilmadi']);
        }

        PipelineAutomationRule::create([
            'business_id' => $business->id,
            'trigger_type' => $validated['trigger_type'],
            'trigger_conditions' => $validated['trigger_conditions'] ?? [],
            'from_stage_slug' => $validated['from_stage_slug'],
            'to_stage_slug' => $validated['to_stage_slug'],
            'only_if_current_stage' => $validated['only_if_current_stage'] ?? false,
            'prevent_backward' => $validated['prevent_backward'] ?? true,
            'priority' => $validated['priority'] ?? 0,
            'is_active' => true,
        ]);

        return back()->with('success', 'Avtomatlashtirish qoidasi qo\'shildi');
    }

    /**
     * Qoidani yangilash
     */
    public function update(Request $request, PipelineAutomationRule $rule)
    {
        $business = $request->user()->currentBusiness;

        if ($rule->business_id !== $business->id) {
            abort(403);
        }

        $validated = $request->validate([
            'trigger_conditions' => 'nullable|array',
            'from_stage_slug' => 'nullable|string',
            'to_stage_slug' => 'required|string',
            'only_if_current_stage' => 'boolean',
            'prevent_backward' => 'boolean',
            'priority' => 'integer|min:0|max:100',
        ]);

        $rule->update($validated);

        return back()->with('success', 'Qoida yangilandi');
    }

    /**
     * Qoidani yoqish/o'chirish
     */
    public function toggle(Request $request, PipelineAutomationRule $rule)
    {
        $business = $request->user()->currentBusiness;

        if ($rule->business_id !== $business->id) {
            abort(403);
        }

        $rule->update(['is_active' => ! $rule->is_active]);

        return back()->with('success', $rule->is_active ? 'Qoida yoqildi' : 'Qoida o\'chirildi');
    }

    /**
     * Qoidani o'chirish
     */
    public function destroy(Request $request, PipelineAutomationRule $rule)
    {
        $business = $request->user()->currentBusiness;

        if ($rule->business_id !== $business->id) {
            abort(403);
        }

        $rule->delete();

        return back()->with('success', 'Qoida o\'chirildi');
    }

    /**
     * Standart qoidalarni tiklash
     */
    public function resetToDefaults(Request $request)
    {
        $business = $request->user()->currentBusiness;

        // Mavjud rules ni o'chirish
        PipelineAutomationRule::where('business_id', $business->id)->delete();

        // Default rules yaratish
        PipelineAutomationRule::createDefaultRules($business);

        return back()->with('success', 'Standart qoidalar tiklandi');
    }

    /**
     * Stagnant leads ro'yxati
     */
    public function stagnantLeads(Request $request)
    {
        $business = $request->user()->currentBusiness;
        $days = $request->input('days', 7);

        $leads = $this->bottleneckService->getStagnantLeads($business, $days);

        if ($request->wantsJson()) {
            return response()->json(['leads' => $leads]);
        }

        return Inertia::render('Shared/PipelineAutomation/StagnantLeads', [
            'leads' => $leads,
            'daysThreshold' => $days,
        ]);
    }
}
