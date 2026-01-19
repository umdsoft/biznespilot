<?php

namespace App\Http\Controllers\Business;

use App\Http\Controllers\Controller;
use App\Models\Lead;
use App\Models\PipelineStage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class PipelineStageController extends Controller
{
    /**
     * Display pipeline stages settings page.
     */
    public function index(Request $request)
    {
        $business = $request->user()->currentBusiness;

        $stages = PipelineStage::forBusiness($business->id)
            ->ordered()
            ->withCount(['leads' => function ($query) use ($business) {
                $query->where('business_id', $business->id);
            }])
            ->get();

        // Determine which layout to use based on route prefix
        $routePrefix = $request->route()->getPrefix();
        $view = str_contains($routePrefix, 'sales-head')
            ? 'SalesHead/Settings/PipelineStages'
            : 'Business/Settings/PipelineStages';

        return Inertia::render($view, [
            'stages' => $stages,
            'colors' => array_keys(PipelineStage::COLORS),
        ]);
    }

    /**
     * Get stages as JSON (for API calls).
     */
    public function list(Request $request)
    {
        $business = $request->user()->currentBusiness;

        $stages = PipelineStage::forBusiness($business->id)
            ->active()
            ->ordered()
            ->get(['id', 'name', 'slug', 'color', 'order', 'is_system', 'is_won', 'is_lost']);

        return response()->json($stages);
    }

    /**
     * Store a newly created stage.
     */
    public function store(Request $request)
    {
        $business = $request->user()->currentBusiness;

        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'color' => 'required|string|in:' . implode(',', array_keys(PipelineStage::COLORS)),
        ]);

        // Generate unique slug
        $slug = PipelineStage::generateSlug($validated['name'], $business->id);

        // Get next order number (before won/lost which are at 100+)
        $order = PipelineStage::getNextOrder($business->id);

        $stage = PipelineStage::create([
            'business_id' => $business->id,
            'name' => $validated['name'],
            'slug' => $slug,
            'color' => $validated['color'],
            'order' => $order,
            'is_system' => false,
            'is_won' => false,
            'is_lost' => false,
            'is_active' => true,
        ]);

        return back()->with('success', 'Bosqich muvaffaqiyatli qo\'shildi');
    }

    /**
     * Update the specified stage.
     */
    public function update(Request $request, PipelineStage $pipelineStage)
    {
        $business = $request->user()->currentBusiness;

        // Ensure stage belongs to this business
        if ($pipelineStage->business_id !== $business->id) {
            abort(403);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'color' => 'required|string|in:' . implode(',', array_keys(PipelineStage::COLORS)),
        ]);

        // System stages can only have name updated (for customization)
        if ($pipelineStage->is_system) {
            $pipelineStage->update([
                'name' => $validated['name'],
            ]);
        } else {
            // Non-system stages can have name and color updated
            // Also update slug if name changed
            $newSlug = PipelineStage::generateSlug($validated['name'], $business->id, $pipelineStage->id);
            $oldSlug = $pipelineStage->slug;

            DB::transaction(function () use ($pipelineStage, $validated, $newSlug, $oldSlug, $business) {
                // Update leads with old slug to new slug
                if ($oldSlug !== $newSlug) {
                    Lead::where('business_id', $business->id)
                        ->where('status', $oldSlug)
                        ->update(['status' => $newSlug]);
                }

                $pipelineStage->update([
                    'name' => $validated['name'],
                    'slug' => $newSlug,
                    'color' => $validated['color'],
                ]);
            });
        }

        return back()->with('success', 'Bosqich muvaffaqiyatli yangilandi');
    }

    /**
     * Remove the specified stage.
     */
    public function destroy(Request $request, PipelineStage $pipelineStage)
    {
        $business = $request->user()->currentBusiness;

        // Ensure stage belongs to this business
        if ($pipelineStage->business_id !== $business->id) {
            abort(403);
        }

        // System stages cannot be deleted
        if ($pipelineStage->is_system) {
            return back()->with('error', 'Tizim bosqichlarini o\'chirib bo\'lmaydi');
        }

        $validated = $request->validate([
            'move_to_stage' => 'required|exists:pipeline_stages,id',
        ]);

        $targetStage = PipelineStage::find($validated['move_to_stage']);

        // Ensure target stage belongs to same business
        if ($targetStage->business_id !== $business->id) {
            abort(403);
        }

        DB::transaction(function () use ($pipelineStage, $targetStage, $business) {
            // Move all leads to target stage
            Lead::where('business_id', $business->id)
                ->where('status', $pipelineStage->slug)
                ->update(['status' => $targetStage->slug]);

            // Delete the stage
            $pipelineStage->delete();
        });

        return back()->with('success', 'Bosqich o\'chirildi va leadlar ko\'chirildi');
    }

    /**
     * Reorder stages.
     */
    public function reorder(Request $request)
    {
        $business = $request->user()->currentBusiness;

        $validated = $request->validate([
            'stages' => 'required|array',
            'stages.*.id' => 'required|exists:pipeline_stages,id',
            'stages.*.order' => 'required|integer|min:1',
        ]);

        DB::transaction(function () use ($validated, $business) {
            foreach ($validated['stages'] as $stageData) {
                $stage = PipelineStage::find($stageData['id']);

                // Ensure stage belongs to this business and is not system
                if ($stage->business_id !== $business->id) {
                    continue;
                }

                // System stages (won/lost) keep their order (100+)
                if ($stage->is_system && ($stage->is_won || $stage->is_lost)) {
                    continue;
                }

                // "new" stage always stays at order 1
                if ($stage->slug === 'new') {
                    $stage->update(['order' => 1]);
                    continue;
                }

                // Ensure order is between 2 and 99 (not 1 which is new, and not 100+ which is won/lost)
                $order = max(2, min(99, $stageData['order']));
                $stage->update(['order' => $order]);
            }
        });

        return back()->with('success', 'Bosqichlar tartibi yangilandi');
    }

    /**
     * Toggle stage active status.
     */
    public function toggleActive(Request $request, PipelineStage $pipelineStage)
    {
        $business = $request->user()->currentBusiness;

        // Ensure stage belongs to this business
        if ($pipelineStage->business_id !== $business->id) {
            abort(403);
        }

        // System stages cannot be deactivated
        if ($pipelineStage->is_system) {
            return back()->with('error', 'Tizim bosqichlarini o\'chirib bo\'lmaydi');
        }

        $pipelineStage->update([
            'is_active' => !$pipelineStage->is_active,
        ]);

        $status = $pipelineStage->is_active ? 'faollashtirildi' : 'o\'chirildi';
        return back()->with('success', "Bosqich {$status}");
    }
}
