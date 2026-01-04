<?php

namespace App\Services;

use App\Models\Competitor;
use App\Models\CompetitorProduct;
use App\Models\CompetitorPriceHistory;
use App\Models\CompetitorPromotion;
use App\Models\CompetitorAlert;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PriceMonitoringService
{
    protected ClaudeAIService $claudeAI;

    public function __construct(ClaudeAIService $claudeAI)
    {
        $this->claudeAI = $claudeAI;
    }

    /**
     * Monitor prices for a competitor
     */
    public function monitorPrices(Competitor $competitor): array
    {
        $results = [
            'success' => false,
            'products_checked' => 0,
            'price_changes' => 0,
            'new_promotions' => 0,
            'errors' => [],
        ];

        try {
            // Check tracked products
            $products = $competitor->products()->tracked()->get();

            foreach ($products as $product) {
                $this->checkProductPrice($product);
                $results['products_checked']++;

                if ($product->wasChanged('current_price')) {
                    $results['price_changes']++;
                }
            }

            // Scan for promotions from social media
            $newPromos = $this->scanForPromotions($competitor);
            $results['new_promotions'] = count($newPromos);

            $results['success'] = true;

        } catch (\Exception $e) {
            Log::error('Price monitoring error', [
                'competitor_id' => $competitor->id,
                'error' => $e->getMessage(),
            ]);
            $results['errors'][] = $e->getMessage();
        }

        return $results;
    }

    /**
     * Check price for a specific product
     */
    protected function checkProductPrice(CompetitorProduct $product): void
    {
        if (!$product->url) return;

        try {
            $priceData = $this->fetchPriceFromUrl($product->url);

            if ($priceData && isset($priceData['price'])) {
                $oldPrice = $product->current_price;
                $newPrice = $priceData['price'];

                // Update product
                $product->update([
                    'current_price' => $newPrice,
                    'original_price' => $priceData['original_price'] ?? $product->original_price,
                    'discount_percent' => $priceData['discount_percent'] ?? null,
                    'is_on_sale' => $priceData['is_on_sale'] ?? false,
                    'sale_label' => $priceData['sale_label'] ?? null,
                    'stock_status' => $priceData['stock_status'] ?? 'unknown',
                    'last_checked_at' => now(),
                ]);

                // Record price history
                $product->recordPrice();

                // Check for significant price change
                if ($oldPrice && $newPrice && $oldPrice != $newPrice) {
                    $changePercent = (($newPrice - $oldPrice) / $oldPrice) * 100;

                    if (abs($changePercent) >= 5) {
                        $product->update(['price_changed_at' => now()]);
                        $this->createPriceChangeAlert($product, $oldPrice, $newPrice, $changePercent);
                    }
                }
            }

        } catch (\Exception $e) {
            Log::warning('Price fetch failed', [
                'product_id' => $product->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Fetch price from product URL
     */
    protected function fetchPriceFromUrl(string $url): ?array
    {
        try {
            $response = Http::withHeaders([
                'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
            ])->timeout(15)->get($url);

            if (!$response->successful()) {
                return null;
            }

            $html = $response->body();
            return $this->extractPriceFromHTML($html, $url);

        } catch (\Exception $e) {
            Log::warning('Price URL fetch failed', ['url' => $url, 'error' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * Extract price from HTML
     */
    protected function extractPriceFromHTML(string $html, string $url): ?array
    {
        $priceData = [
            'price' => null,
            'original_price' => null,
            'is_on_sale' => false,
            'stock_status' => 'unknown',
        ];

        // Common price patterns
        $pricePatterns = [
            // UZS format: 1 234 567 so'm
            '/([\d\s]+)\s*(?:so\'?m|сум|UZS)/iu',
            // JSON-LD structured data
            '/"price"\s*:\s*"?([\d.,]+)"?/i',
            // Common price class patterns
            '/class="[^"]*price[^"]*"[^>]*>.*?([\d\s.,]+)/isu',
            '/itemprop="price"[^>]*content="([\d.,]+)"/i',
        ];

        foreach ($pricePatterns as $pattern) {
            if (preg_match($pattern, $html, $matches)) {
                $priceStr = preg_replace('/\s+/', '', $matches[1]);
                $priceStr = str_replace(',', '.', $priceStr);
                $price = (float) $priceStr;

                if ($price > 0) {
                    $priceData['price'] = $price;
                    break;
                }
            }
        }

        // Check for original/sale price
        if (preg_match('/class="[^"]*(?:old|original|regular)[^"]*price[^"]*"[^>]*>.*?([\d\s.,]+)/isu', $html, $matches)) {
            $originalPrice = (float) preg_replace('/\s+/', '', str_replace(',', '.', $matches[1]));
            if ($originalPrice > ($priceData['price'] ?? 0)) {
                $priceData['original_price'] = $originalPrice;
                $priceData['is_on_sale'] = true;
                $priceData['discount_percent'] = round((1 - ($priceData['price'] / $originalPrice)) * 100, 1);
            }
        }

        // Check stock status
        if (preg_match('/(?:out\s*of\s*stock|sold\s*out|mavjud\s*emas|нет\s*в\s*наличии)/iu', $html)) {
            $priceData['stock_status'] = 'out_of_stock';
        } elseif (preg_match('/(?:in\s*stock|available|mavjud|в\s*наличии)/iu', $html)) {
            $priceData['stock_status'] = 'in_stock';
        }

        return $priceData['price'] ? $priceData : null;
    }

    /**
     * Create price change alert
     */
    protected function createPriceChangeAlert(CompetitorProduct $product, float $oldPrice, float $newPrice, float $changePercent): void
    {
        $competitor = $product->competitor;
        $direction = $changePercent > 0 ? 'oshdi' : 'tushdi';
        $severity = abs($changePercent) >= 20 ? 'high' : 'medium';

        CompetitorAlert::create([
            'competitor_id' => $competitor->id,
            'business_id' => $competitor->business_id,
            'type' => 'price_change',
            'severity' => $severity,
            'title' => "{$competitor->name} - Narx o'zgarishi",
            'message' => "{$product->name} narxi " . abs(round($changePercent, 1)) . "% {$direction}. " .
                number_format($oldPrice, 0, '', ' ') . " → " . number_format($newPrice, 0, '', ' ') . " so'm",
            'data' => [
                'product_id' => $product->id,
                'product_name' => $product->name,
                'old_price' => $oldPrice,
                'new_price' => $newPrice,
                'change_percent' => round($changePercent, 2),
            ],
        ]);
    }

    /**
     * Scan social media for promotions
     */
    protected function scanForPromotions(Competitor $competitor): array
    {
        $newPromotions = [];

        // Get recent content
        $recentContent = $competitor->contents()
            ->where('published_at', '>=', now()->subDays(7))
            ->get();

        foreach ($recentContent as $content) {
            $promoData = $this->detectPromotion($content->caption ?? '');

            if ($promoData) {
                // Check if promotion already exists
                $exists = CompetitorPromotion::where('competitor_id', $competitor->id)
                    ->where('title', $promoData['title'])
                    ->where('is_active', true)
                    ->exists();

                if (!$exists) {
                    $promotion = CompetitorPromotion::create(array_merge($promoData, [
                        'competitor_id' => $competitor->id,
                        'detected_from' => $content->platform,
                        'source_url' => $content->permalink,
                    ]));
                    $newPromotions[] = $promotion;

                    // Create alert for new promotion
                    $this->createPromotionAlert($competitor, $promotion);
                }
            }
        }

        return $newPromotions;
    }

    /**
     * Detect promotion from text
     */
    protected function detectPromotion(string $text): ?array
    {
        $textLower = mb_strtolower($text);

        // Keywords indicating promotion
        $promoKeywords = [
            'chegirma', 'скидка', 'discount', 'sale', 'aksiya', 'акция',
            'chegirma', 'black friday', 'flash sale', 'promo', 'chegirmalar',
            '% off', 'tekin', 'бесплатно', 'free', 'bonus', 'sovg\'a', 'подарок'
        ];

        $isPromo = false;
        foreach ($promoKeywords as $keyword) {
            if (str_contains($textLower, $keyword)) {
                $isPromo = true;
                break;
            }
        }

        if (!$isPromo) return null;

        // Extract discount value
        $discountValue = null;
        $discountType = null;

        // Percentage discount
        if (preg_match('/(\d+)\s*%/', $text, $matches)) {
            $discountValue = (float) $matches[1];
            $discountType = 'percent';
        }

        // Determine promo type
        $promoType = 'sale';
        if (str_contains($textLower, 'tekin') || str_contains($textLower, 'бесплатно') || str_contains($textLower, 'free')) {
            $promoType = 'free_shipping';
        } elseif (str_contains($textLower, 'flash')) {
            $promoType = 'flash_sale';
        } elseif (str_contains($textLower, 'bundle') || str_contains($textLower, 'komplekt')) {
            $promoType = 'bundle';
        }

        // Extract promo code if present
        $promoCode = null;
        if (preg_match('/(?:kod|code|промокод|promo)\s*:?\s*([A-Z0-9]+)/iu', $text, $matches)) {
            $promoCode = strtoupper($matches[1]);
        }

        // Create title from first line or keywords
        $title = mb_substr($text, 0, 100);
        if (str_contains($title, "\n")) {
            $title = explode("\n", $title)[0];
        }

        return [
            'title' => trim($title),
            'description' => mb_substr($text, 0, 500),
            'promo_type' => $promoType,
            'discount_value' => $discountValue,
            'discount_type' => $discountType,
            'promo_code' => $promoCode,
            'is_active' => true,
        ];
    }

    /**
     * Create promotion alert
     */
    protected function createPromotionAlert(Competitor $competitor, CompetitorPromotion $promotion): void
    {
        $discountText = $promotion->discount_value
            ? ($promotion->discount_type === 'percent' ? "{$promotion->discount_value}% chegirma" : "{$promotion->discount_value} so'm chegirma")
            : 'Maxsus taklif';

        CompetitorAlert::create([
            'competitor_id' => $competitor->id,
            'business_id' => $competitor->business_id,
            'type' => 'new_promotion',
            'severity' => 'high',
            'title' => "{$competitor->name} - Yangi aksiya",
            'message' => "{$promotion->title}. {$discountText}",
            'data' => [
                'promotion_id' => $promotion->id,
                'promo_type' => $promotion->promo_type,
                'discount_value' => $promotion->discount_value,
            ],
        ]);
    }

    /**
     * Add product for tracking
     */
    public function addProduct(Competitor $competitor, array $data): CompetitorProduct
    {
        $product = CompetitorProduct::create(array_merge($data, [
            'competitor_id' => $competitor->id,
            'is_tracked' => true,
            'currency' => $data['currency'] ?? 'UZS',
        ]));

        // Record initial price
        if ($product->current_price) {
            $product->recordPrice();
        }

        return $product;
    }

    /**
     * Get price insights for competitor
     */
    public function getPriceInsights(Competitor $competitor): array
    {
        $products = $competitor->products()->tracked()->get();
        $promotions = $competitor->promotions()->active()->get();

        if ($products->isEmpty()) {
            return [
                'total_products' => 0,
                'products_on_sale' => 0,
                'avg_discount' => 0,
                'active_promotions' => $promotions->count(),
            ];
        }

        // Products on sale
        $onSale = $products->where('is_on_sale', true);

        // Average discount
        $avgDiscount = $onSale->avg('discount_percent') ?? 0;

        // Price changes in last 30 days
        $priceChanges = CompetitorPriceHistory::whereIn('product_id', $products->pluck('id'))
            ->where('recorded_date', '>=', now()->subDays(30))
            ->count();

        // Price trend
        $priceTrend = $this->calculatePriceTrend($products);

        return [
            'total_products' => $products->count(),
            'products_on_sale' => $onSale->count(),
            'sale_percent' => round(($onSale->count() / $products->count()) * 100, 1),
            'avg_discount' => round($avgDiscount, 1),
            'max_discount' => $onSale->max('discount_percent') ?? 0,
            'price_changes_30d' => $priceChanges,
            'price_trend' => $priceTrend,
            'active_promotions' => $promotions->count(),
            'out_of_stock' => $products->where('stock_status', 'out_of_stock')->count(),
        ];
    }

    /**
     * Calculate overall price trend
     */
    protected function calculatePriceTrend(iterable $products): string
    {
        $changes = [];

        foreach ($products as $product) {
            $change = $product->price_change_percent;
            if ($change !== null) {
                $changes[] = $change;
            }
        }

        if (empty($changes)) return 'stable';

        $avgChange = array_sum($changes) / count($changes);

        if ($avgChange > 5) return 'increasing';
        if ($avgChange < -5) return 'decreasing';
        return 'stable';
    }

    /**
     * Add manual promotion
     */
    public function addPromotion(Competitor $competitor, array $data): CompetitorPromotion
    {
        return CompetitorPromotion::create(array_merge($data, [
            'competitor_id' => $competitor->id,
            'is_active' => true,
            'detected_from' => 'manual',
        ]));
    }
}
