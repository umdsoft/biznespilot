<?php

namespace App\Services\ContentAI;

use App\Models\Business;
use App\Models\ContentPost;
use App\Models\NicheTopicScore;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * "Jamoaviy Aql" — Cross-Business Learning Service
 *
 * Shu sohadagi barcha bizneslarning muvaffaqiyatli kontentini tahlil qilib,
 * boshqa bizneslarga anonim holda tavsiya beradi.
 *
 * Manba: instagram_media + content_calendar → niche_topic_scores
 */
class CrossBusinessLearningService
{
    /**
     * Soha bo'yicha eng yaxshi mavzularni olish (SQL-based ranking)
     *
     * @return array<int, array{topic: string, category: string, content_type: string, score: float, trend: string, total_posts: int, avg_engagement_rate: float}>
     */
    public function getTopTopicsForIndustry(string $industryId, int $limit = 15, ?string $contentType = null): array
    {
        try {
            $query = NicheTopicScore::forIndustry($industryId)
                ->where('total_posts', '>=', 3)
                ->orderByDesc('score');

            if ($contentType) {
                $query->ofType($contentType);
            }

            return $query->limit($limit)
                ->get()
                ->map(fn (NicheTopicScore $score) => [
                    'topic' => $score->topic,
                    'category' => $score->category,
                    'content_type' => $score->content_type,
                    'score' => (float) $score->score,
                    'trend' => $score->trend,
                    'total_posts' => $score->total_posts,
                    'avg_engagement_rate' => (float) $score->avg_engagement_rate,
                    'avg_saves' => $score->avg_saves,
                    'avg_shares' => $score->avg_shares,
                    'sample_hashtags' => $score->sample_hashtags ?? [],
                    'best_posting_times' => $score->best_posting_times ?? [],
                ])
                ->toArray();
        } catch (\Throwable $e) {
            Log::error('CrossBusinessLearning: getTopTopics failed', [
                'industry_id' => $industryId,
                'error' => $e->getMessage(),
            ]);

            return [];
        }
    }

    /**
     * O'sish trendidagi mavzularni olish
     */
    public function getRisingTopics(string $industryId, int $limit = 10): array
    {
        try {
            return NicheTopicScore::forIndustry($industryId)
                ->rising()
                ->where('total_posts', '>=', 2)
                ->orderByDesc('score')
                ->limit($limit)
                ->get()
                ->map(fn (NicheTopicScore $s) => [
                    'topic' => $s->topic,
                    'category' => $s->category,
                    'content_type' => $s->content_type,
                    'score' => (float) $s->score,
                    'avg_engagement_rate' => (float) $s->avg_engagement_rate,
                ])
                ->toArray();
        } catch (\Throwable $e) {
            Log::error('CrossBusinessLearning: getRisingTopics failed', ['error' => $e->getMessage()]);

            return [];
        }
    }

    /**
     * Barcha sohalar bo'yicha niche_topic_scores ni yangilash.
     * Bu method Job/Command orqali chaqiriladi (kunlik yoki haftalik).
     */
    public function recalculateAllScores(): array
    {
        $stats = ['processed' => 0, 'updated' => 0, 'errors' => 0];

        try {
            // Barcha industry_id larni olish (instagram ulangan bizneslar)
            $industries = Business::whereNotNull('industry_id')
                ->whereHas('instagramAccounts')
                ->select('industry_id')
                ->distinct()
                ->pluck('industry_id');

            foreach ($industries as $industryId) {
                try {
                    $this->recalculateForIndustry($industryId);
                    $stats['updated']++;
                } catch (\Throwable $e) {
                    Log::warning('CrossBusinessLearning: industry failed', [
                        'industry_id' => $industryId,
                        'error' => $e->getMessage(),
                    ]);
                    $stats['errors']++;
                }
                $stats['processed']++;
            }
        } catch (\Throwable $e) {
            Log::error('CrossBusinessLearning: recalculateAllScores failed', ['error' => $e->getMessage()]);
        }

        return $stats;
    }

    /**
     * Bitta soha uchun niche score larni hisoblash.
     *
     * Manba: ContentPost (published) + metrics
     */
    public function recalculateForIndustry(string $industryId): void
    {
        // Shu sohadagi bizneslarni olish
        $businessIds = Business::where('industry_id', $industryId)
            ->whereHas('instagramAccounts')
            ->pluck('id')
            ->toArray();

        if (empty($businessIds)) {
            return;
        }

        // Published content + metrikalari (oxirgi 90 kun)
        $topicData = ContentPost::withoutGlobalScope('business')
            ->whereIn('business_id', $businessIds)
            ->where('status', 'published')
            ->whereRaw('LOWER(platform) = ?', ['instagram'])
            ->where('scheduled_at', '>=', now()->subDays(90))
            ->whereNotNull('title')
            ->select(
                'title',
                'content_type',
                'format',
                'business_id',
                'metrics',
                DB::raw('COALESCE(likes, 0) as likes'),
                DB::raw('COALESCE(comments, 0) as comments'),
                DB::raw('COALESCE(shares, 0) as shares')
            )
            ->get();

        if ($topicData->isEmpty()) {
            return;
        }

        // Mavzular bo'yicha guruhlash (title + format)
        $grouped = $topicData->groupBy(function ($item) {
            return $this->normalizeTopicKey($item->title).'|'.($item->format ?? 'post');
        });

        foreach ($grouped as $key => $items) {
            [$topicKey, $contentType] = explode('|', $key);

            if (mb_strlen($topicKey) < 3) {
                continue;
            }

            $totalPosts = $items->count();
            $avgEngagement = round($items->avg(fn ($i) => $this->getMetricFromItem($i, 'engagement_rate')), 4);
            $avgReach = (int) round($items->avg(fn ($i) => $this->getMetricFromItem($i, 'reach')));
            $avgSaves = (int) round($items->avg(fn ($i) => $this->getMetricFromItem($i, 'saves')));
            $avgShares = (int) round($items->avg('shares'));
            $totalEngagement = (int) $items->sum(fn ($i) => $i->likes + $i->comments + $this->getMetricFromItem($i, 'saves') + $i->shares);
            $category = $items->first()->content_type;
            $contributingBusinesses = $items->pluck('business_id')->unique()->values()->toArray();

            $existing = NicheTopicScore::withoutGlobalScopes()
                ->where('industry_id', $industryId)
                ->where('topic', $topicKey)
                ->where('content_type', $contentType)
                ->first();

            $previousScore = $existing?->score ?? 0;

            $record = NicheTopicScore::updateOrCreate(
                [
                    'industry_id' => $industryId,
                    'topic' => $topicKey,
                    'content_type' => $contentType,
                ],
                [
                    'category' => $category,
                    'total_posts' => $totalPosts,
                    'total_engagement' => $totalEngagement,
                    'avg_engagement_rate' => $avgEngagement,
                    'avg_reach' => $avgReach,
                    'avg_saves' => $avgSaves,
                    'avg_shares' => $avgShares,
                    'contributing_businesses' => $contributingBusinesses,
                ]
            );

            $record->recalculateScore();
            $record->updateTrend($previousScore);
        }
    }

    /**
     * ContentPost metrics JSON dan qiymat olish
     */
    private function getMetricFromItem($item, string $key): float
    {
        $metrics = is_string($item->metrics) ? json_decode($item->metrics, true) : $item->metrics;
        if (is_array($metrics) && isset($metrics[$key])) {
            return (float) $metrics[$key];
        }

        return 0.0;
    }

    /**
     * Mavzu nomini normalizatsiya qilish (grupplash uchun).
     * "Reel - engagement (15.02)" → "engagement"
     */
    private function normalizeTopicKey(string $title): string
    {
        // Pattern: "Post - theme (dd.mm)" → "theme" ni ajratish
        if (preg_match('/^(?:Post|Reel|Story|Carousel)\s*-\s*(.+?)\s*\(/', $title, $matches)) {
            return mb_strtolower(trim($matches[1]));
        }

        // Boshqa holda: to'liq titleni kichik harflarga o'girish
        $cleaned = preg_replace('/\(\d{2}\.\d{2}(?:\.\d{4})?\)/', '', $title);
        $cleaned = preg_replace('/\s+/', ' ', $cleaned);

        return mb_strtolower(trim($cleaned));
    }
}
