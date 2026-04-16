<?php

namespace App\Http\Controllers\Business;

use App\Http\Controllers\Controller;
use App\Models\SalesScript;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class SalesScriptController extends Controller
{
    /**
     * Skriptlar ro'yxati sahifasi (Inertia)
     */
    public function index()
    {
        $business = $this->getCurrentBusiness();
        if (!$business) return redirect()->route('login');

        $scripts = SalesScript::forBusiness($business->id)
            ->orderByDesc('is_default')
            ->orderByDesc('created_at')
            ->get();

        return Inertia::render('Business/SalesScripts/Index', [
            'scripts' => $scripts,
            'defaultTemplate' => SalesScript::getDefaultTemplate(),
            'stageLabels' => SalesScript::STAGES,
        ]);
    }

    /**
     * Yangi skript yaratish sahifasi
     */
    public function create()
    {
        $business = $this->getCurrentBusiness();
        if (!$business) return redirect()->route('login');

        return Inertia::render('Business/SalesScripts/Create', [
            'defaultTemplate' => SalesScript::getDefaultTemplate(),
            'stageLabels' => SalesScript::STAGES,
        ]);
    }

    /**
     * Skript tahrirlash sahifasi
     */
    public function edit(string $id)
    {
        $business = $this->getCurrentBusiness();
        if (!$business) return redirect()->route('login');

        $script = SalesScript::forBusiness($business->id)->findOrFail($id);

        return Inertia::render('Business/SalesScripts/Edit', [
            'script' => $script,
            'stageLabels' => SalesScript::STAGES,
        ]);
    }

    /**
     * Yangi skript saqlash
     */
    public function store(Request $request): JsonResponse
    {
        $business = $this->getCurrentBusiness();
        if (!$business) return response()->json(['error' => 'Biznes topilmadi'], 422);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'script_type' => 'required|in:inbound,outbound,follow_up,general',
            'stages' => 'nullable|array',
            'global_required_phrases' => 'nullable|array',
            'global_forbidden_phrases' => 'nullable|array',
            'ideal_duration_min' => 'nullable|integer|min:30',
            'ideal_duration_max' => 'nullable|integer|max:3600',
            'ideal_talk_ratio_min' => 'nullable|numeric|min:0|max:100',
            'ideal_talk_ratio_max' => 'nullable|numeric|min:0|max:100',
            'is_default' => 'boolean',
        ]);

        // Agar is_default bo'lsa — boshqalarni false qilish
        if ($validated['is_default'] ?? false) {
            SalesScript::forBusiness($business->id)->update(['is_default' => false]);
        }

        $script = SalesScript::create(array_merge($validated, [
            'business_id' => $business->id,
            'created_by' => Auth::id(),
            'is_active' => true,
        ]));

        return response()->json(['success' => true, 'script' => $script]);
    }

    /**
     * Skript yangilash
     */
    public function update(Request $request, string $id): JsonResponse
    {
        $business = $this->getCurrentBusiness();
        if (!$business) return response()->json(['error' => 'Biznes topilmadi'], 422);

        $script = SalesScript::forBusiness($business->id)->findOrFail($id);

        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string',
            'script_type' => 'sometimes|in:inbound,outbound,follow_up,general',
            'stages' => 'nullable|array',
            'global_required_phrases' => 'nullable|array',
            'global_forbidden_phrases' => 'nullable|array',
            'ideal_duration_min' => 'nullable|integer|min:30',
            'ideal_duration_max' => 'nullable|integer|max:3600',
            'ideal_talk_ratio_min' => 'nullable|numeric|min:0|max:100',
            'ideal_talk_ratio_max' => 'nullable|numeric|min:0|max:100',
            'is_default' => 'boolean',
            'is_active' => 'boolean',
        ]);

        if (($validated['is_default'] ?? false) && !$script->is_default) {
            SalesScript::forBusiness($business->id)->update(['is_default' => false]);
        }

        $script->update($validated);

        return response()->json(['success' => true, 'script' => $script]);
    }

    /**
     * Skript o'chirish
     */
    public function destroy(string $id): JsonResponse
    {
        $business = $this->getCurrentBusiness();
        if (!$business) return response()->json(['error' => 'Biznes topilmadi'], 422);

        $script = SalesScript::forBusiness($business->id)->findOrFail($id);
        $script->delete();

        return response()->json(['success' => true]);
    }

    /**
     * Standart shablonni qaytarish (yangi yaratishda ishlatiladi)
     */
    public function defaultTemplate(): JsonResponse
    {
        return response()->json([
            'stages' => SalesScript::getDefaultTemplate(),
            'labels' => SalesScript::STAGES,
        ]);
    }

    /**
     * Joriy biznesni olish helper
     */
    private function getCurrentBusiness()
    {
        $user = Auth::user();
        if (!$user) return null;

        $businessId = session('current_business_id');
        if ($businessId) {
            return \App\Models\Business::find($businessId);
        }

        return $user->business ?? $user->businesses()->first();
    }
}
