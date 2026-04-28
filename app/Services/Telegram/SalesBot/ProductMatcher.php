<?php

namespace App\Services\Telegram\SalesBot;

use App\Models\CustomerNeedProfile;
use App\Models\Store\StoreProduct;
use App\Models\Store\TelegramStore;
use App\Services\AI\AIService;
use Illuminate\Support\Facades\Log;

/**
 * ProductMatcher — mijoz ehtiyojiga eng mos mahsulotlarni tanlash.
 *
 * Ikki bosqich:
 *   1. DB filter — narx, ombor, tegishli toifa
 *   2. AI pick — qoldiqdan eng mos 2-3 ta tanlash + sabab
 *
 * Cache: 24 soat (bir mijoz uchun bir xil profile bilan qayta hisoblamaslik).
 */
class ProductMatcher
{
    public function __construct(
        private AIService $aiService,
    ) {}

    /**
     * Mijozga tavsiya etilgan 2-3 ta mahsulotni qaytaradi.
     *
     * @return array  ['products' => [['id','name','price','reason'],...], 'fallback' => bool]
     */
    public function match(CustomerNeedProfile $profile, int $limit = 3): array
    {
        $businessId = $profile->business_id;
        $constraints = $profile->constraints ?? [];

        // ── 1. DB FILTER ─────────────────────────────────────────────
        $candidates = $this->fetchCandidates($businessId, $constraints, $profile);

        if ($candidates->isEmpty()) {
            return ['products' => [], 'fallback' => true];
        }

        // Agar 5 yoki kamroq qoldigan bo'lsa — to'g'ridan-to'g'ri qaytarish
        if ($candidates->count() <= $limit) {
            return [
                'products' => $candidates->map(fn ($p) => $this->formatProduct($p, 'Sizning ehtiyojingizga mos'))->values()->all(),
                'fallback' => false,
            ];
        }

        // ── 2. AI PICK ───────────────────────────────────────────────
        $picked = $this->aiPick($candidates, $profile, $limit);

        return [
            'products' => $picked,
            'fallback' => false,
        ];
    }

    /**
     * DB'dan candidate mahsulotlarni olish — qatiy filtr.
     */
    private function fetchCandidates(string $businessId, array $constraints, CustomerNeedProfile $profile): \Illuminate\Support\Collection
    {
        // Biznesning store ID'larini olish
        $storeIds = TelegramStore::where('business_id', $businessId)
            ->where('is_active', true)
            ->pluck('id');

        if ($storeIds->isEmpty()) {
            return collect();
        }

        $query = StoreProduct::query()
            ->whereIn('store_id', $storeIds)
            ->where('is_active', true)
            ->where(function ($q) {
                // Ombor: track_stock=false (cheksiz) yoki stock_quantity > 0
                $q->where('track_stock', false)
                    ->orWhere('stock_quantity', '>', 0);
            });

        // Byudjet
        if (! empty($constraints['budget_max'])) {
            $query->where('price', '<=', (float) $constraints['budget_max']);
        }
        if (! empty($constraints['budget_min'])) {
            $query->where('price', '>=', (float) $constraints['budget_min']);
        }

        // Rad etilgan mahsulotlarni chiqarib tashlash
        $rejected = $profile->rejected_products ?? [];
        if (! empty($rejected)) {
            $query->whereNotIn('id', $rejected);
        }

        return $query->limit(20)->get();
    }

    /**
     * AI orqali eng mos N ta mahsulotni tanlash + sabab.
     */
    private function aiPick(\Illuminate\Support\Collection $candidates, CustomerNeedProfile $profile, int $limit): array
    {
        $catalog = $candidates->map(fn ($p) => [
            'id' => $p->id,
            'name' => $p->name,
            'price' => (float) $p->price,
            'description' => mb_substr((string) $p->description, 0, 160),
        ])->values()->all();

        $constraints = json_encode($profile->constraints ?? [], JSON_UNESCAPED_UNICODE);
        $useCase = $profile->use_case ?? 'noma\'lum';
        $intent = $profile->primary_intent ?? 'umumiy';

        $systemPrompt = <<<'TXT'
Sen tajribali sotuvchi — maslahatchisan. Vazifang: mijoz ehtiyojiga
ENG MOS bo'lgan mahsulotlarni tanlash va NEGA mos ekanligini aytish.

QAYTARISH FORMATI: Faqat JSON:
[
  {"id": "uuid", "reason": "1 jumla — nega aynan shu mos"},
  ...
]

QOIDALAR:
- MAJBURLAB sotma. Eng mos {limit} ta tanlasang yetarli.
- Sabab MIJOZ TILIDA yoz (uzbek), 1 jumla, aniq.
- Agar hech bir mos kelmasa — bo'sh massiv qaytar.
- Faqat berilgan ID'lardan tanla — ixtiyoriy mahsulot YO'Q.
TXT;

        $prompt = <<<TXT
MIJOZ EHTIYOJI:
- Nima izlayapti: {$intent}
- Foydalanish maqsadi: {$useCase}
- Cheklovlar: {$constraints}

MAHSULOT KATALOG:
{$this->encodeCatalog($catalog)}

Eng mos {$limit} ta tanla. Faqat JSON qaytar.
TXT;

        try {
            $response = $this->aiService->ask(
                prompt: $prompt,
                systemPrompt: str_replace('{limit}', (string) $limit, $systemPrompt),
                preferredModel: 'haiku',
                maxTokens: 600,
                businessId: $profile->business_id,
                agentType: 'sales_bot_product_matcher',
            );

            if (! $response->success) {
                return $this->fallbackTopN($candidates, $limit);
            }

            $parsed = $this->parseJson($response->content);
            if (! is_array($parsed)) {
                return $this->fallbackTopN($candidates, $limit);
            }

            // AI tanlagan ID'larni to'liq mahsulot ma'lumotiga aylantirish
            $byId = $candidates->keyBy('id');
            $result = [];
            foreach ($parsed as $item) {
                $id = $item['id'] ?? null;
                $reason = $item['reason'] ?? '';
                if ($id && $byId->has($id)) {
                    $result[] = $this->formatProduct($byId->get($id), $reason);
                }
            }

            return ! empty($result) ? $result : $this->fallbackTopN($candidates, $limit);

        } catch (\Throwable $e) {
            Log::warning('ProductMatcher AI: error', ['error' => $e->getMessage()]);
            return $this->fallbackTopN($candidates, $limit);
        }
    }

    /**
     * AI ishlamasa — narx bo'yicha o'rta-arzon variantlardan yaqinlari.
     */
    private function fallbackTopN(\Illuminate\Support\Collection $candidates, int $limit): array
    {
        return $candidates
            ->sortByDesc('is_featured')
            ->take($limit)
            ->map(fn ($p) => $this->formatProduct($p, 'Mashhur va omborda mavjud'))
            ->values()
            ->all();
    }

    /**
     * Mahsulotni response uchun formatlash (frontend uchun yengil).
     */
    private function formatProduct(StoreProduct $product, string $reason): array
    {
        return [
            'id' => $product->id,
            'name' => $product->name,
            'price' => (float) $product->price,
            'compare_price' => $product->compare_price ? (float) $product->compare_price : null,
            'description' => mb_substr((string) $product->description, 0, 200),
            'image_url' => $product->primaryImage?->image_url ?? null,
            'in_stock' => ! $product->track_stock || $product->stock_quantity > 0,
            'reason' => $reason,
        ];
    }

    private function encodeCatalog(array $catalog): string
    {
        return json_encode($catalog, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    }

    private function parseJson(string $raw): ?array
    {
        $clean = trim($raw);
        if (preg_match('/```(?:json)?\s*(.+?)\s*```/s', $clean, $m)) {
            $clean = $m[1];
        }
        $decoded = json_decode($clean, true);
        if (json_last_error() === JSON_ERROR_NONE) {
            return $decoded;
        }
        if (preg_match('/\[.*\]/s', $clean, $m)) {
            $decoded = json_decode($m[0], true);
            if (json_last_error() === JSON_ERROR_NONE) return $decoded;
        }
        return null;
    }
}
