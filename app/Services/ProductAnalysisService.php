<?php

namespace App\Services;

use App\Models\CompetitorProduct;
use App\Models\ProductAnalysis;
use App\Models\ProductInsight;
use Illuminate\Support\Facades\DB;

class ProductAnalysisService
{
    /**
     * Mahsulot kartasi uchun to'liq ma'lumot
     */
    public function getProductCard(ProductAnalysis $product): array
    {
        $product->load(['competitorMappings.competitorProduct.competitor']);

        return [
            'product' => $this->formatProduct($product),
            'sales' => $this->getSalesMetrics($product),
            'competitors' => $this->getCompetitorData($product),
            'insights' => $this->getProductInsights($product),
            'scores' => [
                'usp' => $product->usp_score,
                'trend' => $product->trend_alignment_score,
                'competition' => $product->competitor_position_score,
                'health' => $product->health_score,
                'margin' => $product->margin_percent,
            ],
            'ai_analysis' => $product->ai_analysis,
            'ai_stale' => $product->ai_stale,
        ];
    }

    /**
     * Mahsulotni formatlash
     */
    private function formatProduct(ProductAnalysis $product): array
    {
        return [
            'id' => $product->id,
            'name' => $product->name,
            'short_desc' => $product->short_desc,
            'category' => $product->category,
            'pricing_model' => $product->pricing_model,
            'price' => $product->price,
            'cost' => $product->cost,
            'margin_percent' => $product->margin_percent,
            'advantages' => $product->advantages,
            'weaknesses' => $product->weaknesses,
            'target_audience' => $product->target_audience,
            'features' => $product->features ?? [],
            'usp_score' => $product->usp_score,
            'competition' => $product->competition,
            'marketing_status' => $product->marketing_status,
            'life_cycle_stage' => $product->life_cycle_stage,
            'market_avg_price' => $product->market_avg_price,
            'advantages_count' => $product->advantages_count,
            'weaknesses_count' => $product->weaknesses_count,
            'created_at' => $product->created_at->format('d.m.Y'),
        ];
    }

    /**
     * Sotuv metrikalari (hozircha sales_summary cache dan)
     */
    private function getSalesMetrics(ProductAnalysis $product): array
    {
        if ($product->sales_summary) {
            return $product->sales_summary;
        }

        // Agar cache yo'q bo'lsa, bo'sh qaytarish
        return [
            'total_revenue' => 0,
            'total_sales' => 0,
            'avg_deal_size' => 0,
            'conversion_rate' => 0,
            'trend' => 'stable', // up, down, stable
            'trend_percent' => 0,
            'top_channels' => [],
            'monthly_data' => [],
        ];
    }

    /**
     * Raqobatchi mahsulotlari ma'lumotlari
     */
    private function getCompetitorData(ProductAnalysis $product): array
    {
        return $product->competitorMappings->map(function ($mapping) use ($product) {
            $cp = $mapping->competitorProduct;
            $competitor = $cp->competitor ?? null;

            $priceGap = 0;
            if ($product->price > 0 && $cp->current_price > 0) {
                $priceGap = round((($product->price - $cp->current_price) / $cp->current_price) * 100, 1);
            }

            return [
                'mapping_id' => $mapping->id,
                'competitor_name' => $competitor->name ?? 'Noma\'lum',
                'product_name' => $cp->name,
                'product_url' => $cp->url,
                'current_price' => $cp->current_price,
                'original_price' => $cp->original_price,
                'is_on_sale' => $cp->is_on_sale,
                'stock_status' => $cp->stock_status,
                'similarity_score' => $mapping->similarity_score,
                'price_gap_percent' => $priceGap,
                'mapped_by' => $mapping->mapped_by,
            ];
        })->toArray();
    }

    /**
     * Mahsulotga tegishli tezkor tavsiyalar
     */
    private function getProductInsights(ProductAnalysis $product): array
    {
        return ProductInsight::where('product_analysis_id', $product->id)
            ->active()
            ->byPriority()
            ->limit(5)
            ->get()
            ->map(fn ($i) => [
                'id' => $i->id,
                'type' => $i->type,
                'priority' => $i->priority,
                'title' => $i->title,
                'description' => $i->description,
                'action_text' => $i->action_text,
                'created_at' => $i->created_at->diffForHumans(),
            ])
            ->toArray();
    }

    /**
     * Biznes uchun barcha mahsulotlar insight'lari
     */
    public function getAllInsights(string $businessId): array
    {
        return ProductInsight::where('business_id', $businessId)
            ->active()
            ->byPriority()
            ->with('productAnalysis:id,name')
            ->limit(10)
            ->get()
            ->map(fn ($i) => [
                'id' => $i->id,
                'type' => $i->type,
                'priority' => $i->priority,
                'title' => $i->title,
                'description' => $i->description,
                'action_text' => $i->action_text,
                'product_name' => $i->productAnalysis->name ?? null,
                'product_id' => $i->product_analysis_id,
                'created_at' => $i->created_at->diffForHumans(),
            ])
            ->toArray();
    }

    /**
     * USP balini hisoblash (kengaytirilgan)
     */
    public function calculateEnhancedUspScore(ProductAnalysis $product): int
    {
        $score = 5; // Base

        // Tavsif to'liqligi
        if (!empty($product->short_desc)) $score += 10;
        if (!empty($product->target_audience)) $score += 15;

        // Afzalliklar
        if (!empty($product->advantages)) {
            $count = count(array_filter(explode("\n", $product->advantages)));
            $score += min($count * 8, 25);
        }

        // Xususiyatlar
        if (!empty($product->features)) {
            $score += min(count($product->features) * 5, 15);
        }

        // Narx pozitsiyasi
        if ($product->price > 0) $score += 10;
        if ($product->cost > 0) $score += 5; // Margin aniqlangan

        // Raqobatchi tahlili
        if ($product->competitorMappings()->count() > 0) $score += 10;

        // Marketing faolligi
        if ($product->marketing_status === 'active') $score += 10;

        return min($score, 100);
    }

    /**
     * Tezkor tavsiyalar generatsiyasi (qoidalar asosida)
     */
    public function generateInsights(string $businessId): int
    {
        $products = ProductAnalysis::where('business_id', $businessId)->get();
        $created = 0;

        foreach ($products as $product) {
            // 1. USP past — yaxshilash kerak
            if ($product->usp_score < 30) {
                $created += $this->createInsight($businessId, $product->id, [
                    'type' => 'usp_gap',
                    'priority' => 'high',
                    'title' => "{$product->name} — USP bali juda past ({$product->usp_score}%)",
                    'description' => "Afzalliklar, maqsadli auditoriya va xususiyatlarni to'ldiring",
                    'action_text' => "Mahsulotni tahrirlash",
                ]);
            }

            // 2. Marketing yo'q, lekin USP yaxshi
            if ($product->usp_score >= 50 && $product->marketing_status === 'none') {
                $created += $this->createInsight($businessId, $product->id, [
                    'type' => 'marketing_gap',
                    'priority' => 'high',
                    'title' => "{$product->name} — marketing faoliyati yo'q",
                    'description' => "USP bali {$product->usp_score}% lekin marketing holati 'yo'q'. Reklama boshlang!",
                    'action_text' => "Marketing rejasini yaratish",
                ]);
            }

            // 3. Narx raqobatchilardan yuqori
            $product->load('competitorMappings.competitorProduct');
            foreach ($product->competitorMappings as $mapping) {
                $cp = $mapping->competitorProduct;
                if ($cp && $cp->current_price > 0 && $product->price > 0) {
                    $gap = (($product->price - $cp->current_price) / $cp->current_price) * 100;
                    if ($gap > 20) {
                        $competitorName = $cp->competitor->name ?? 'Raqobatchi';
                        $created += $this->createInsight($businessId, $product->id, [
                            'type' => 'price_alert',
                            'priority' => $gap > 50 ? 'high' : 'medium',
                            'title' => "{$product->name} — {$competitorName} dan " . round($gap) . "% qimmat",
                            'description' => "Sizning narx: " . number_format($product->price) . ", raqobatchi: " . number_format($cp->current_price),
                            'action_text' => "Narx strategiyasini ko'rish",
                        ]);
                    }
                }
            }

            // 4. Margin past
            if ($product->cost > 0 && $product->margin_percent < 20) {
                $created += $this->createInsight($businessId, $product->id, [
                    'type' => 'price_alert',
                    'priority' => 'medium',
                    'title' => "{$product->name} — margin juda past ({$product->margin_percent}%)",
                    'description' => "Narx: " . number_format($product->price) . ", tannarx: " . number_format($product->cost),
                    'action_text' => "Narxni qayta ko'rish",
                ]);
            }
        }

        return $created;
    }

    private function createInsight(string $businessId, string $productId, array $data): int
    {
        // Dublikat tekshirish
        $exists = ProductInsight::where('business_id', $businessId)
            ->where('product_analysis_id', $productId)
            ->where('type', $data['type'])
            ->where('status', 'active')
            ->exists();

        if ($exists) return 0;

        ProductInsight::create(array_merge($data, [
            'business_id' => $businessId,
            'product_analysis_id' => $productId,
            'expires_at' => now()->addDays(14),
        ]));

        return 1;
    }
}
