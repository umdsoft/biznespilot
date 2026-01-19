<?php

namespace App\Http\Controllers\Shared;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Traits\HasCurrentBusiness;
use App\Models\CompetitorInsight;
use App\Services\CompetitorInsightsService;
use Illuminate\Http\Request;
use Inertia\Inertia;

/**
 * CompetitorInsightsController - Tavsiyalar va tahlillar boshqaruvi
 *
 * Bu controller biznes egasiga:
 * - Tavsiyalarni ko'rish
 * - Tavsiyalarni bajarish/rad etish
 * - Sotuv skriptlarini olish
 * - Haftalik hisobotni ko'rish
 * imkonini beradi.
 */
class CompetitorInsightsController extends Controller
{
    use HasCurrentBusiness;

    protected CompetitorInsightsService $insightsService;

    public function __construct(CompetitorInsightsService $insightsService)
    {
        $this->insightsService = $insightsService;
    }

    /**
     * Panel turini aniqlash
     */
    protected function getPanelType(Request $request): string
    {
        $prefix = $request->route()->getPrefix();

        if (str_contains($prefix, 'marketing')) {
            return 'marketing';
        }
        if (str_contains($prefix, 'sales-head')) {
            return 'saleshead';
        }

        return 'business';
    }

    /**
     * Tavsiyalar dashboard
     */
    public function index(Request $request)
    {
        $business = $this->getCurrentBusiness();

        if (! $business) {
            return redirect()->route('business.index');
        }

        $panelType = $this->getPanelType($request);

        // Faol tavsiyalarni olish
        $insights = CompetitorInsight::where('business_id', $business->id)
            ->active()
            ->orderBy('priority', 'asc') // high birinchi
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(fn ($insight) => [
                'id' => $insight->id,
                'type' => $insight->type,
                'type_name' => $insight->type_name,
                'priority' => $insight->priority,
                'priority_name' => $insight->priority_name,
                'title' => $insight->title,
                'competitor_name' => $insight->competitor_name,
                'description' => $insight->description,
                'recommendation' => $insight->recommendation,
                'action_text' => $insight->action_text,
                'action_data' => $insight->action_data,
                'icon_type' => $insight->icon_type,
                'color_type' => $insight->color_type,
                'is_read' => $insight->is_read,
                'is_completed' => $insight->is_completed,
                'created_at' => $insight->created_at->toIso8601String(),
            ]);

        // Statistika
        $stats = [
            'total' => $insights->count(),
            'high_priority' => $insights->where('priority', 'high')->count(),
            'opportunities' => $insights->where('type', 'opportunity')->count(),
            'threats' => $insights->where('type', 'threat')->count(),
            'unread' => $insights->where('is_read', false)->count(),
        ];

        // Yuqori muhimlikdagi amallar (top 5)
        $actionItems = $insights
            ->where('priority', 'high')
            ->take(5)
            ->values();

        return Inertia::render('Shared/CompetitorInsights/Index', [
            'insights' => $insights,
            'stats' => $stats,
            'actionItems' => $actionItems,
            'lastGenerated' => $business->insights_generated_at?->toIso8601String(),
            'panelType' => $panelType,
        ]);
    }

    /**
     * Yangi tavsiyalar generatsiya qilish
     */
    public function generate(Request $request)
    {
        $business = $this->getCurrentBusiness();

        if (! $business) {
            return back()->with('error', 'Biznes topilmadi');
        }

        $result = $this->insightsService->generateInsights($business);

        $message = sprintf('%d ta tavsiya yaratildi', count($result['insights']));

        // Inertia POST so'rovi - back() bilan qaytarish kerak
        return back()->with('success', $message);
    }

    /**
     * Sotuv skriptlarini olish
     */
    public function salesScripts(Request $request)
    {
        $business = $this->getCurrentBusiness();

        if (! $business) {
            return redirect()->route('business.index');
        }

        $panelType = $this->getPanelType($request);

        // Raqobatchilarni olish
        $competitors = $business->competitors()
            ->where('status', 'active')
            ->with(['metrics' => fn ($q) => $q->latest('recorded_date')->limit(1)])
            ->get();

        // Skriptlarni generatsiya qilish
        $scripts = $this->insightsService->generateSalesScripts($business, $competitors);

        return Inertia::render('Shared/CompetitorInsights/SalesScripts', [
            'scripts' => $scripts,
            'competitors' => $competitors->map(fn ($c) => [
                'id' => $c->id,
                'name' => $c->name,
                'threat_level' => $c->threat_level,
            ]),
            'panelType' => $panelType,
        ]);
    }

    /**
     * Tavsiyani o'qildi deb belgilash
     */
    public function markRead(Request $request, CompetitorInsight $insight)
    {
        $business = $this->getCurrentBusiness();

        if ($insight->business_id !== $business->id) {
            abort(403);
        }

        $insight->markAsRead();

        return back();
    }

    /**
     * Tavsiyani bajarildi deb belgilash
     */
    public function markCompleted(Request $request, CompetitorInsight $insight)
    {
        $business = $this->getCurrentBusiness();

        if ($insight->business_id !== $business->id) {
            abort(403);
        }

        $insight->markAsCompleted($request->input('notes'));

        return back()->with('success', 'Tavsiya bajarildi deb belgilandi');
    }

    /**
     * Tavsiyani rad etish
     */
    public function dismiss(Request $request, CompetitorInsight $insight)
    {
        $business = $this->getCurrentBusiness();

        if ($insight->business_id !== $business->id) {
            abort(403);
        }

        $insight->dismiss();

        return back()->with('info', 'Tavsiya rad etildi');
    }

    /**
     * API: Tavsiyalar ro'yxati
     */
    public function apiList(Request $request)
    {
        $business = $this->getCurrentBusiness();

        if (! $business) {
            return response()->json(['error' => 'Biznes topilmadi'], 404);
        }

        $query = CompetitorInsight::where('business_id', $business->id);

        // Filterlar
        if ($request->has('type') && $request->type !== 'all') {
            $query->where('type', $request->type);
        }

        if ($request->has('priority') && $request->priority !== 'all') {
            $query->where('priority', $request->priority);
        }

        if ($request->has('status') && $request->status !== 'all') {
            $query->where('status', $request->status);
        } else {
            $query->active();
        }

        $insights = $query
            ->orderBy('priority', 'asc')
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return response()->json($insights);
    }

    /**
     * API: Xulosa
     */
    public function apiSummary(Request $request)
    {
        $business = $this->getCurrentBusiness();

        if (! $business) {
            return response()->json(['error' => 'Biznes topilmadi'], 404);
        }

        $stats = [
            'total_active' => CompetitorInsight::where('business_id', $business->id)->active()->count(),
            'high_priority' => CompetitorInsight::where('business_id', $business->id)->active()->highPriority()->count(),
            'unread' => CompetitorInsight::where('business_id', $business->id)->active()->unread()->count(),
            'completed_this_week' => CompetitorInsight::where('business_id', $business->id)
                ->where('status', 'completed')
                ->where('completed_at', '>=', now()->startOfWeek())
                ->count(),
            'last_generated' => $business->insights_generated_at?->toIso8601String(),
        ];

        return response()->json($stats);
    }
}
