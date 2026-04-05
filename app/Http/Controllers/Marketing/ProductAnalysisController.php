<?php

namespace App\Http\Controllers\Marketing;

use App\Http\Controllers\Controller;
use App\Models\DreamBuyer;
use App\Models\ProductAnalysis;
use App\Models\ProductInsight;
use App\Services\ProductAnalysisService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ProductAnalysisController extends Controller
{
    public function __construct(
        private ProductAnalysisService $service
    ) {}

    public function index(Request $request)
    {
        $panel = $request->route()->defaults['panel'] ?? 'business';
        $businessId = session('current_business_id');

        $products = ProductAnalysis::where('business_id', $businessId)
            ->withCount('competitorMappings')
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(fn ($p) => [
                'id' => $p->id,
                'name' => $p->name,
                'short_desc' => $p->short_desc,
                'category' => $this->getCategoryLabel($p->category),
                'category_key' => $p->category,
                'price' => $p->price,
                'cost' => $p->cost,
                'usp_score' => $p->usp_score,
                'competition' => $p->competition,
                'marketing_status' => $p->marketing_status,
                'life_cycle_stage' => $p->life_cycle_stage,
                'market_avg_price' => $p->market_avg_price,
                'advantages_count' => $p->advantages_count,
                'weaknesses_count' => $p->weaknesses_count,
                'health_score' => $p->health_score,
                'competitor_mappings_count' => $p->competitor_mappings_count,
                'margin_percent' => $p->margin_percent,
            ]);

        $stats = [
            'total' => $products->count(),
            'active_marketing' => $products->where('marketing_status', 'active')->count(),
            'avg_usp' => $products->count() > 0 ? round($products->avg('usp_score')) : 0,
            'needs_attention' => $products->filter(fn ($p) => $p['usp_score'] < 40 || $p['marketing_status'] === 'none')->count(),
        ];

        $insights = $this->service->getAllInsights($businessId);

        $page = $panel === 'marketing'
            ? 'Marketing/ProductAnalysis/Index'
            : 'Business/ProductAnalysis/Index';

        $dreamBuyers = DreamBuyer::where('business_id', $businessId)
            ->where('is_active', true)
            ->orderBy('is_primary', 'desc')
            ->get()
            ->map(fn ($db) => [
                'id' => $db->id,
                'name' => $db->name,
                'description' => $db->description,
                'age_range' => $db->age_range,
                'occupation' => $db->occupation,
                'is_primary' => $db->is_primary,
            ]);

        return Inertia::render($page, [
            'products' => $products,
            'stats' => $stats,
            'insights' => $insights,
            'dreamBuyers' => $dreamBuyers,
        ]);
    }

    public function show(Request $request, string $id)
    {
        $panel = $request->route()->defaults['panel'] ?? 'business';
        $product = ProductAnalysis::where('business_id', session('current_business_id'))
            ->findOrFail($id);

        $data = $this->service->getProductCard($product);

        $page = $panel === 'marketing'
            ? 'Marketing/ProductAnalysis/Show'
            : 'Business/ProductAnalysis/Show';

        return Inertia::render($page, $data);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'short_desc' => 'nullable|string|max:500',
            'category' => 'required|in:product,service,course,subscription,other',
            'pricing_model' => 'nullable|in:one_time,subscription,freemium',
            'price' => 'nullable|numeric|min:0',
            'cost' => 'nullable|numeric|min:0',
            'advantages' => 'nullable|string',
            'weaknesses' => 'nullable|string',
            'target_audience' => 'nullable|string|max:500',
            'features' => 'nullable|array',
        ]);

        $businessId = session('current_business_id');

        $advantagesCount = 0;
        if (!empty($validated['advantages'])) {
            $advantagesCount = count(array_filter(explode("\n", $validated['advantages'])));
        }
        $weaknessesCount = 0;
        if (!empty($validated['weaknesses'])) {
            $weaknessesCount = count(array_filter(explode("\n", $validated['weaknesses'])));
        }

        $product = ProductAnalysis::create([
            'business_id' => $businessId,
            'name' => $validated['name'],
            'short_desc' => $validated['short_desc'] ?? null,
            'category' => $validated['category'],
            'pricing_model' => $validated['pricing_model'] ?? 'one_time',
            'price' => $validated['price'] ?? 0,
            'cost' => $validated['cost'] ?? null,
            'advantages' => $validated['advantages'] ?? null,
            'weaknesses' => $validated['weaknesses'] ?? null,
            'target_audience' => $validated['target_audience'] ?? null,
            'features' => $validated['features'] ?? null,
            'advantages_count' => $advantagesCount,
            'weaknesses_count' => $weaknessesCount,
            'usp_score' => 0,
            'competition' => 'medium',
            'marketing_status' => 'none',
        ]);

        // USP balni hisoblash
        $product->update(['usp_score' => $this->service->calculateEnhancedUspScore($product)]);

        // Insight generatsiya
        $this->service->generateInsights($businessId);

        return redirect()->back()->with('success', 'Mahsulot muvaffaqiyatli qo\'shildi');
    }

    public function update(Request $request, string $id)
    {
        $product = ProductAnalysis::where('business_id', session('current_business_id'))
            ->findOrFail($id);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'short_desc' => 'nullable|string|max:500',
            'category' => 'required|in:product,service,course,subscription,other',
            'pricing_model' => 'nullable|in:one_time,subscription,freemium',
            'price' => 'nullable|numeric|min:0',
            'cost' => 'nullable|numeric|min:0',
            'advantages' => 'nullable|string',
            'weaknesses' => 'nullable|string',
            'target_audience' => 'nullable|string|max:500',
            'features' => 'nullable|array',
            'marketing_status' => 'nullable|in:active,planned,paused,none',
            'life_cycle_stage' => 'nullable|in:introduction,growth,maturity,decline',
            'competition' => 'nullable|in:low,medium,high',
            'market_avg_price' => 'nullable|numeric|min:0',
        ]);

        $advantagesCount = !empty($validated['advantages'])
            ? count(array_filter(explode("\n", $validated['advantages'])))
            : 0;
        $weaknessesCount = !empty($validated['weaknesses'])
            ? count(array_filter(explode("\n", $validated['weaknesses'])))
            : 0;

        $product->update(array_merge($validated, [
            'advantages_count' => $advantagesCount,
            'weaknesses_count' => $weaknessesCount,
        ]));

        $product->update(['usp_score' => $this->service->calculateEnhancedUspScore($product)]);

        return redirect()->back()->with('success', 'Mahsulot yangilandi');
    }

    public function destroy(string $id)
    {
        $product = ProductAnalysis::where('business_id', session('current_business_id'))
            ->findOrFail($id);

        $product->delete();

        return redirect()->back()->with('success', 'Mahsulot o\'chirildi');
    }

    public function dismissInsight(string $id)
    {
        ProductInsight::where('business_id', session('current_business_id'))
            ->where('id', $id)
            ->update(['status' => 'dismissed']);

        return redirect()->back();
    }

    private function getCategoryLabel(string $category): string
    {
        return match ($category) {
            'product' => 'Mahsulot',
            'service' => 'Xizmat',
            'course' => 'Kurs',
            'subscription' => 'Obuna',
            default => 'Boshqa',
        };
    }
}
