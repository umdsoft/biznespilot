<?php

namespace App\Services\Agent\Reputation;

use App\Services\AI\AIService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Obro' va sharhlar boshqaruvchisi.
 * Izohlarni yig'ish, kayfiyat tahlili, javob tavsiya qilish.
 *
 * Gibrid: 80% qoidaga asoslangan kayfiyat (bepul) + 20% Haiku (murakkab)
 */
class ReputationService
{
    // Kayfiyat aniqlash kalit so'zlari
    private const POSITIVE_WORDS = [
        "zo'r", 'ajoyib', 'rahmat', 'yoqdi', 'yaxshi', "a'lo", 'mukammal',
        'tavsiya', 'mamnun', 'отлично', 'супер', 'спасибо', 'нравится',
    ];

    private const NEGATIVE_WORDS = [
        'yomon', 'shikoyat', 'ishlamaydi', 'xato', 'qimmat', 'kechikdi',
        'norozilik', 'aldash', 'плохо', 'жалоба', 'обман', 'дорого',
    ];

    public function __construct(
        private AIService $aiService,
    ) {}

    /**
     * Yangi izohni qayta ishlash
     */
    public function processReview(
        string $businessId,
        string $source,
        string $reviewText,
        ?string $reviewerName = null,
        ?int $rating = null,
        ?string $sourceId = null,
    ): array {
        try {
            // 1. Kayfiyat tahlili (qoidaga asoslangan, bepul)
            $sentiment = $this->analyzeSentiment($reviewText);

            // 2. Saqlash
            $review = [
                'id' => Str::uuid()->toString(),
                'business_id' => $businessId,
                'source' => $source,
                'source_id' => $sourceId,
                'reviewer_name' => $reviewerName,
                'rating' => $rating,
                'review_text' => $reviewText,
                'language' => $this->detectLanguage($reviewText),
                'sentiment' => $sentiment['type'],
                'sentiment_score' => $sentiment['score'],
                'categories' => json_encode($sentiment['categories']),
                'flagged' => $sentiment['type'] === 'negative',
                'created_at' => now(),
                'updated_at' => now(),
            ];

            // 3. Salbiy izoh uchun javob tavsiya
            if ($sentiment['type'] === 'negative' || $sentiment['type'] === 'mixed') {
                $suggested = $this->suggestResponse($businessId, $reviewText, $sentiment);
                $review['suggested_response'] = $suggested;
                $review['response_status'] = 'suggested';
            }

            DB::table('customer_reviews')->insert($review);

            return [
                'success' => true,
                'review_id' => $review['id'],
                'sentiment' => $sentiment,
                'flagged' => $review['flagged'],
                'suggested_response' => $review['suggested_response'] ?? null,
            ];

        } catch (\Exception $e) {
            Log::error('Reputation: izoh qayta ishlash xatosi', ['error' => $e->getMessage()]);
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Obro' bali hisoblash (haftalik)
     */
    public function calculateReputationScore(string $businessId): array
    {
        try {
            $start = now()->subDays(7)->toDateString();
            $end = now()->toDateString();

            $stats = DB::table('customer_reviews')
                ->where('business_id', $businessId)
                ->whereBetween('created_at', [$start, $end . ' 23:59:59'])
                ->selectRaw("
                    COUNT(*) as total,
                    SUM(CASE WHEN sentiment = 'positive' THEN 1 ELSE 0 END) as positive,
                    SUM(CASE WHEN sentiment = 'negative' THEN 1 ELSE 0 END) as negative,
                    SUM(CASE WHEN sentiment = 'neutral' THEN 1 ELSE 0 END) as neutral,
                    AVG(sentiment_score) as avg_sentiment,
                    AVG(rating) as avg_rating
                ")
                ->first();

            if ($stats->total == 0) {
                return ['success' => true, 'message' => 'Bu davr uchun izoh topilmadi'];
            }

            DB::table('reputation_scores')->insert([
                'id' => Str::uuid()->toString(),
                'business_id' => $businessId,
                'period_start' => $start,
                'period_end' => $end,
                'overall_sentiment' => round($stats->avg_sentiment, 2),
                'total_reviews' => $stats->total,
                'positive_count' => $stats->positive,
                'negative_count' => $stats->negative,
                'neutral_count' => $stats->neutral,
                'avg_rating' => $stats->avg_rating ? round($stats->avg_rating, 2) : null,
                'created_at' => now(),
            ]);

            return [
                'success' => true,
                'total_reviews' => (int) $stats->total,
                'sentiment' => round($stats->avg_sentiment, 2),
                'positive' => (int) $stats->positive,
                'negative' => (int) $stats->negative,
                'avg_rating' => $stats->avg_rating ? round($stats->avg_rating, 1) : null,
            ];

        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Kayfiyat tahlili (qoidaga asoslangan — 80% holat, bepul)
     */
    private function analyzeSentiment(string $text): array
    {
        $normalized = mb_strtolower($text);

        $positiveCount = 0;
        $negativeCount = 0;
        $categories = [];

        foreach (self::POSITIVE_WORDS as $word) {
            if (str_contains($normalized, $word)) {
                $positiveCount++;
            }
        }

        foreach (self::NEGATIVE_WORDS as $word) {
            if (str_contains($normalized, $word)) {
                $negativeCount++;
                // Kategoriya aniqlash
                if (in_array($word, ['qimmat', 'дорого'])) $categories[] = 'price';
                if (in_array($word, ['ishlamaydi', 'xato'])) $categories[] = 'quality';
                if (in_array($word, ['kechikdi'])) $categories[] = 'delivery';
                if (in_array($word, ['shikoyat', 'norozilik', 'жалоба'])) $categories[] = 'service';
            }
        }

        if ($positiveCount > 0 && $negativeCount > 0) {
            $type = 'mixed';
            $score = round($positiveCount / ($positiveCount + $negativeCount), 2);
        } elseif ($positiveCount > 0) {
            $type = 'positive';
            $score = min(1.0, 0.6 + ($positiveCount * 0.1));
        } elseif ($negativeCount > 0) {
            $type = 'negative';
            $score = max(0.0, 0.4 - ($negativeCount * 0.1));
        } else {
            $type = 'neutral';
            $score = 0.5;
        }

        return [
            'type' => $type,
            'score' => $score,
            'categories' => array_unique($categories),
        ];
    }

    /**
     * Salbiy izohga javob tavsiya qilish
     */
    private function suggestResponse(string $businessId, string $reviewText, array $sentiment): string
    {
        $bizName = DB::table('businesses')->where('id', $businessId)->value('name') ?? 'Biznes';
        $categories = implode(', ', $sentiment['categories'] ?: ['umumiy']);

        $response = $this->aiService->ask(
            prompt: "Izoh: \"{$reviewText}\"\nKategoriya: {$categories}\nBiznes: {$bizName}",
            systemPrompt: "Sen biznes obro' boshqaruvchisisan. Salbiy izohga professional, samimiy javob yoz. O'zbek tilida. 2-3 gap. Muammoni tan ol, yechim taklif qil, aloqa yo'lini ko'rsat.",
            preferredModel: 'haiku',
            maxTokens: 200,
            businessId: $businessId,
            agentType: 'reputation',
        );

        return $response->success ? $response->content : "Hurmatli mijoz, fikringiz uchun rahmat. Muammoni hal qilishga tayyor turamiz. Iltimos biz bilan bog'laning.";
    }

    /**
     * Oddiy til aniqlash
     */
    private function detectLanguage(string $text): string
    {
        if (preg_match('/[а-яА-ЯёЁ]/u', $text)) return 'ru';
        if (preg_match('/[a-zA-Z]/', $text) && !preg_match("/[oʻgʼ']/u", $text)) return 'en';
        return 'uz';
    }
}
