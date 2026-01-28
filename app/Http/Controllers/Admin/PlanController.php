<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Plan;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class PlanController extends Controller
{
    /**
     * Limitlar uchun konfiguratsiya
     */
    protected array $limitConfig = [
        'users' => ['label' => 'Foydalanuvchilar soni', 'icon' => 'users', 'suffix' => 'ta'],
        'branches' => ['label' => 'Filiallar soni', 'icon' => 'building', 'suffix' => 'ta'],
        'instagram_accounts' => ['label' => 'Instagram akkauntlar', 'icon' => 'instagram', 'suffix' => 'ta'],
        'monthly_leads' => ['label' => 'Oylik lidlar', 'icon' => 'user-plus', 'suffix' => 'ta'],
        'ai_call_minutes' => ['label' => 'Qo\'ng\'iroqlar AI tahlili', 'icon' => 'phone', 'suffix' => 'daq'],
        'extra_call_price' => ['label' => 'Qo\'shimcha daqiqa narxi', 'icon' => 'currency', 'suffix' => 'so\'m'],
        'chatbot_channels' => ['label' => 'Chatbot kanallari', 'icon' => 'chat', 'suffix' => 'ta'],
        'telegram_bots' => ['label' => 'Telegram botlar', 'icon' => 'telegram', 'suffix' => 'ta'],
        'ai_requests' => ['label' => 'AI so\'rovlar', 'icon' => 'sparkles', 'suffix' => 'ta'],
        'storage_mb' => ['label' => 'Saqlash hajmi', 'icon' => 'database', 'suffix' => 'MB'],
    ];

    /**
     * Xususiyatlar uchun konfiguratsiya
     */
    protected array $featureConfig = [
        'hr_tasks' => ['label' => 'HR vazifalar', 'description' => 'Vazifalar va loyihalar boshqaruvi'],
        'hr_bot' => ['label' => 'Ishga olish boti', 'description' => 'Avtomatlashtirilgan HR chatbot'],
        'anti_fraud' => ['label' => 'SMS ogohlantirish', 'description' => 'Fraud aniqlash va ogohlantirish'],
    ];

    /**
     * Display a listing of plans.
     */
    public function index()
    {
        $plans = Plan::orderBy('sort_order')
            ->orderBy('price_monthly')
            ->get()
            ->map(fn ($plan) => [
                'id' => $plan->id,
                'name' => $plan->name,
                'slug' => $plan->slug,
                'description' => $plan->description,
                'price_monthly' => $plan->price_monthly,
                'price_yearly' => $plan->price_yearly,
                'currency' => $plan->currency ?? 'UZS',
                'limits' => $plan->limits ?? [],
                'features' => $plan->features ?? [],
                'is_active' => $plan->is_active,
                'sort_order' => $plan->sort_order ?? 0,
                'subscriptions_count' => $plan->subscriptions()->count(),
                'active_subscriptions_count' => $plan->subscriptions()->where('status', 'active')->count(),
            ]);

        $stats = [
            'total' => Plan::count(),
            'active' => Plan::where('is_active', true)->count(),
            'inactive' => Plan::where('is_active', false)->count(),
            'total_subscribers' => \App\Models\Subscription::where('status', 'active')->count(),
        ];

        return Inertia::render('Admin/Plans/Index', [
            'plans' => $plans,
            'stats' => $stats,
            'limitConfig' => $this->limitConfig,
            'featureConfig' => $this->featureConfig,
        ]);
    }

    /**
     * Show the form for creating a new plan.
     */
    public function create()
    {
        return Inertia::render('Admin/Plans/Edit', [
            'plan' => null,
            'limitConfig' => $this->limitConfig,
            'featureConfig' => $this->featureConfig,
        ]);
    }

    /**
     * Store a newly created plan.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255|unique:plans,slug',
            'description' => 'nullable|string',
            'price_monthly' => 'required|numeric|min:0',
            'price_yearly' => 'required|numeric|min:0',
            'currency' => 'required|string|size:3',
            'limits' => 'required|array',
            'features' => 'required|array',
            'is_active' => 'boolean',
            'sort_order' => 'integer|min:0',
        ]);

        // Generate slug if not provided
        $validated['slug'] = $validated['slug'] ?? Str::slug($validated['name']);

        $plan = Plan::create($validated);

        return redirect()->route('admin.plans.index')
            ->with('success', 'Tarif rejasi muvaffaqiyatli yaratildi');
    }

    /**
     * Show the form for editing the specified plan.
     */
    public function edit(string $id)
    {
        $plan = Plan::findOrFail($id);

        return Inertia::render('Admin/Plans/Edit', [
            'plan' => [
                'id' => $plan->id,
                'name' => $plan->name,
                'slug' => $plan->slug,
                'description' => $plan->description,
                'price_monthly' => $plan->price_monthly,
                'price_yearly' => $plan->price_yearly,
                'currency' => $plan->currency ?? 'UZS',
                'limits' => $plan->limits ?? [],
                'features' => $plan->features ?? [],
                'is_active' => $plan->is_active,
                'sort_order' => $plan->sort_order ?? 0,
            ],
            'limitConfig' => $this->limitConfig,
            'featureConfig' => $this->featureConfig,
        ]);
    }

    /**
     * Update the specified plan.
     */
    public function update(Request $request, string $id)
    {
        $plan = Plan::findOrFail($id);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => ['nullable', 'string', 'max:255', Rule::unique('plans')->ignore($plan->id)],
            'description' => 'nullable|string',
            'price_monthly' => 'required|numeric|min:0',
            'price_yearly' => 'required|numeric|min:0',
            'currency' => 'required|string|size:3',
            'limits' => 'required|array',
            'features' => 'required|array',
            'is_active' => 'boolean',
            'sort_order' => 'integer|min:0',
        ]);

        // Also update legacy columns for backward compatibility
        $legacyMapping = [
            'team_member_limit' => $validated['limits']['users'] ?? null,
            'business_limit' => $validated['limits']['branches'] ?? null,
            'lead_limit' => $validated['limits']['monthly_leads'] ?? null,
            'chatbot_channel_limit' => $validated['limits']['chatbot_channels'] ?? null,
            'telegram_bot_limit' => $validated['limits']['telegram_bots'] ?? null,
            'audio_minutes_limit' => $validated['limits']['ai_call_minutes'] ?? null,
            'ai_requests_limit' => $validated['limits']['ai_requests'] ?? null,
            'storage_limit_mb' => $validated['limits']['storage_mb'] ?? null,
        ];

        $plan->update(array_merge($validated, $legacyMapping));

        return redirect()->route('admin.plans.index')
            ->with('success', 'Tarif rejasi muvaffaqiyatli yangilandi');
    }

    /**
     * Toggle plan active status.
     */
    public function toggleStatus(string $id)
    {
        $plan = Plan::findOrFail($id);
        $plan->update(['is_active' => !$plan->is_active]);

        return back()->with('success', $plan->is_active
            ? 'Tarif rejasi faollashtirildi'
            : 'Tarif rejasi o\'chirildi');
    }

    /**
     * Delete a plan (soft check for subscriptions).
     */
    public function destroy(string $id)
    {
        $plan = Plan::findOrFail($id);

        // Check if plan has active subscriptions
        if ($plan->subscriptions()->where('status', 'active')->exists()) {
            return back()->with('error', 'Bu tarifda faol obunalar mavjud. Avval obunalarni boshqa tarifga o\'tkazing.');
        }

        $plan->delete();

        return redirect()->route('admin.plans.index')
            ->with('success', 'Tarif rejasi o\'chirildi');
    }

    /**
     * Reorder plans.
     */
    public function reorder(Request $request)
    {
        $validated = $request->validate([
            'plans' => 'required|array',
            'plans.*.id' => 'required|exists:plans,id',
            'plans.*.sort_order' => 'required|integer|min:0',
        ]);

        foreach ($validated['plans'] as $planData) {
            Plan::where('id', $planData['id'])->update(['sort_order' => $planData['sort_order']]);
        }

        return response()->json(['success' => true]);
    }
}
