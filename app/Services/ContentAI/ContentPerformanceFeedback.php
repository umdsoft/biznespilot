<?php

namespace App\Services\ContentAI;

use App\Models\ContentPost;
use App\Models\InstagramMedia;
use App\Models\NicheTopicScore;
use App\Models\PainPointContentMap;
use Illuminate\Support\Facades\Log;

/**
 * Content Performance Feedback — "Qadam 3-4-5" Feedback Loop
 *
 * Sikl: Generate → Publish → Analyze → Score Update → Better Next Plan
 *
 * Bu servis:
 * 1. Published kontent natijalarini yig'adi (Instagram API dan)
 * 2. niche_topic_scores ni yangilaydi (Jamoaviy Aql uchun)
 * 3. pain_point_content_maps ni yangilaydi (og'riq-kontent bog'lanish)
 * 4. Keyingi plan uchun performance summary tayyorlaydi
 *
 * 100% ichki algoritm — AI API chaqirilMAYDI.
 */
class ContentPerformanceFeedback
{
    /**
     * Biznes uchun performance xulosa (plan yaratish uchun input)
     */
    public function getPerformanceSummary(string $businessId): array
    {
        try {
            // Oxirgi 30 kunda published kontentni tahlil qilish
            $published = ContentPost::withoutGlobalScope('business')
                ->where('business_id', $businessId)
                ->where('status', 'published')
                ->whereRaw('LOWER(platform) = ?', ['instagram'])
                ->where('scheduled_at', '>=', now()->subDays(30))
                ->get();

            if ($published->isEmpty()) {
                return [];
            }

            // Content type bo'yicha guruhlash (theme o'rniga content_type)
            $topThemes = $published->groupBy('content_type')
                ->map(fn ($group) => [
                    'count' => $group->count(),
                    'avg_engagement' => round($group->avg(fn ($p) => $this->getMetric($p, 'engagement_rate')), 4),
                    'total_reach' => (int) $group->sum(fn ($p) => $this->getMetric($p, 'reach')),
                    'total_saves' => (int) $group->sum(fn ($p) => $this->getMetric($p, 'saves')),
                    'total_shares' => (int) $group->sum('shares'),
                ])
                ->sortByDesc('avg_engagement')
                ->toArray();

            // Format bo'yicha natijalar
            $topTypes = $published->groupBy('format')
                ->map(fn ($group) => [
                    'count' => $group->count(),
                    'avg_engagement' => round($group->avg(fn ($p) => $this->getMetric($p, 'engagement_rate')), 4),
                    'avg_reach' => (int) round($group->avg(fn ($p) => $this->getMetric($p, 'reach'))),
                ])
                ->sortByDesc('avg_engagement')
                ->toArray();

            // Eng yaxshi 5 ta post
            $topPosts = $published->sortByDesc(fn ($p) => $this->getMetric($p, 'engagement_rate'))
                ->take(5)
                ->map(fn ($p) => [
                    'title' => $p->title,
                    'content_type' => $p->content_type,
                    'format' => $p->format,
                    'engagement_rate' => (float) $this->getMetric($p, 'engagement_rate'),
                    'scheduled_date' => $p->scheduled_at?->toDateString(),
                ])
                ->values()
                ->toArray();

            // Umumiy ko'rsatkichlar
            $avgEngagement = round($published->avg(fn ($p) => $this->getMetric($p, 'engagement_rate')), 4);
            $totalReach = (int) $published->sum(fn ($p) => $this->getMetric($p, 'reach'));

            return [
                'period_days' => 30,
                'total_published' => $published->count(),
                'avg_engagement_rate' => $avgEngagement,
                'total_reach' => $totalReach,
                'top_themes' => $topThemes,
                'top_types' => $topTypes,
                'top_posts' => $topPosts,
                'engagement_trend' => $this->calculateEngagementTrend($published),
            ];
        } catch (\Throwable $e) {
            Log::error('ContentPerformanceFeedback: getPerformanceSummary failed', [
                'business_id' => $businessId,
                'error' => $e->getMessage(),
            ]);

            return [];
        }
    }

    /**
     * Published kontent natijasini feedback loop ga yuborish.
     * Instagram metrikalarni content_posts ga yozib, niche_topic_scores ni yangilaydi.
     */
    public function processPublishedContent(string $businessId): array
    {
        $stats = ['processed' => 0, 'updated' => 0, 'niche_updated' => 0, 'errors' => 0];

        try {
            // Published lekin metrikalari yangilanmagan kontentni topish
            $pendingItems = ContentPost::withoutGlobalScope('business')
                ->where('business_id', $businessId)
                ->where('status', 'published')
                ->whereRaw('LOWER(platform) = ?', ['instagram'])
                ->where(function ($q) {
                    $q->whereNull('metrics')
                        ->orWhereJsonLength('metrics', 0)
                        ->orWhereRaw("JSON_EXTRACT(metrics, '$.engagement_rate') IS NULL")
                        ->orWhereRaw("JSON_EXTRACT(metrics, '$.engagement_rate') = 0");
                })
                ->where('scheduled_at', '>=', now()->subDays(30))
                ->get();

            foreach ($pendingItems as $item) {
                try {
                    $updated = $this->syncItemMetrics($item);
                    if ($updated) {
                        $stats['updated']++;

                        // Niche score ni ham yangilash
                        $this->updateNicheScoreForItem($item);
                        $stats['niche_updated']++;

                        // Pain point map ni ham yangilash
                        $this->updatePainPointMapForItem($item);
                    }
                    $stats['processed']++;
                } catch (\Throwable $e) {
                    $stats['errors']++;
                    Log::warning('ContentPerformanceFeedback: item process failed', [
                        'item_id' => $item->id,
                        'error' => $e->getMessage(),
                    ]);
                }
            }
        } catch (\Throwable $e) {
            Log::error('ContentPerformanceFeedback: processPublishedContent failed', [
                'business_id' => $businessId,
                'error' => $e->getMessage(),
            ]);
        }

        return $stats;
    }

    /**
     * Bitta kontent itemning Instagram metrikalari bilan sinxronlash
     */
    private function syncItemMetrics(ContentPost $item): bool
    {
        // external_id orqali Instagram media ni topish
        if ($item->external_id) {
            $media = InstagramMedia::where('media_id', $item->external_id)->first();

            if ($media) {
                $item->update([
                    'likes' => $media->like_count ?? 0,
                    'comments' => $media->comments_count ?? 0,
                    'shares' => $media->shares ?? 0,
                    'metrics' => [
                        'engagement_rate' => $media->engagement_rate ?? 0,
                        'reach' => $media->reach ?? 0,
                        'impressions' => $media->impressions ?? 0,
                        'saves' => $media->saved ?? 0,
                        'shares' => $media->shares ?? 0,
                        'likes' => $media->like_count ?? 0,
                        'comments' => $media->comments_count ?? 0,
                    ],
                ]);

                return true;
            }
        }

        return false;
    }

    /**
     * Published kontent natijasini niche_topic_scores ga yansalash
     * Bu "Jamoaviy Aql" ni mustahkamlaydi
     */
    private function updateNicheScoreForItem(ContentPost $item): void
    {
        $business = $item->business;
        if (! $business || ! $business->industry_id || ! $item->title) {
            return;
        }

        $normalizedTopic = mb_strtolower(trim($item->title));
        $engagementRate = $this->getMetric($item, 'engagement_rate');
        $reach = (int) $this->getMetric($item, 'reach');
        $saves = (int) $this->getMetric($item, 'saves');
        $shares = (int) $item->shares;

        $existing = NicheTopicScore::withoutGlobalScopes()
            ->where('industry_id', $business->industry_id)
            ->where('topic', $normalizedTopic)
            ->where('content_type', $item->content_type)
            ->first();

        if ($existing) {
            // Mavjud score ni yangilash (running average)
            $newCount = $existing->total_posts + 1;
            $newEngagement = (($existing->avg_engagement_rate * $existing->total_posts) + $engagementRate) / $newCount;
            $newReach = (int) ((($existing->avg_reach * $existing->total_posts) + $reach) / $newCount);
            $newSaves = (int) ((($existing->avg_saves * $existing->total_posts) + $saves) / $newCount);
            $newShares = (int) ((($existing->avg_shares * $existing->total_posts) + $shares) / $newCount);

            $contributing = $existing->contributing_businesses ?? [];
            if (! in_array($business->id, $contributing)) {
                $contributing[] = $business->id;
            }

            $previousScore = $existing->score;

            $existing->update([
                'total_posts' => $newCount,
                'total_engagement' => $existing->total_engagement + ($item->likes ?? 0) + ($item->comments ?? 0) + $saves + $shares,
                'avg_engagement_rate' => round($newEngagement, 4),
                'avg_reach' => $newReach,
                'avg_saves' => $newSaves,
                'avg_shares' => $newShares,
                'contributing_businesses' => $contributing,
            ]);

            $existing->recalculateScore();
            $existing->updateTrend($previousScore);
        } else {
            // Yangi niche topic yaratish
            $record = NicheTopicScore::create([
                'industry_id' => $business->industry_id,
                'topic' => $normalizedTopic,
                'category' => $item->content_type,
                'content_type' => $item->format ?? 'post',
                'total_posts' => 1,
                'total_engagement' => ($item->likes ?? 0) + ($item->comments ?? 0) + $saves + $shares,
                'avg_engagement_rate' => $engagementRate,
                'avg_reach' => $reach,
                'avg_saves' => $saves,
                'avg_shares' => $shares,
                'contributing_businesses' => [$business->id],
            ]);

            $record->recalculateScore();
        }
    }

    /**
     * Pain Point Content Map engagement ni yangilash
     */
    private function updatePainPointMapForItem(ContentPost $item): void
    {
        if (! $item->ai_suggestions || empty($item->ai_suggestions['pain_text'])) {
            return;
        }

        $painText = $item->ai_suggestions['pain_text'];
        $engagementRate = $this->getMetric($item, 'engagement_rate');

        $map = PainPointContentMap::where('business_id', $item->business_id)
            ->where('pain_point_text', $painText)
            ->first();

        if ($map) {
            $map->recordUsage((float) $engagementRate);
        }
    }

    /**
     * ContentPost dan metrika olish (metrics JSON yoki individual column)
     */
    private function getMetric(ContentPost $item, string $key): float
    {
        // 1. metrics JSON dan olish
        $metrics = $item->metrics;
        if (is_array($metrics) && isset($metrics[$key])) {
            return (float) $metrics[$key];
        }

        // 2. Individual column dan olish (likes, comments, shares, views)
        if (in_array($key, ['likes', 'comments', 'shares', 'views']) && isset($item->{$key})) {
            return (float) $item->{$key};
        }

        return 0.0;
    }

    /**
     * Engagement trend (o'sish/tushish) hisoblash
     */
    private function calculateEngagementTrend(mixed $published): string
    {
        if ($published->count() < 4) {
            return 'insufficient_data';
        }

        // Birinchi yarmi vs ikkinchi yarmi
        $sorted = $published->sortBy('scheduled_at');
        $half = (int) ceil($sorted->count() / 2);

        $firstHalf = $sorted->take($half);
        $secondHalf = $sorted->skip($half);

        $firstAvg = $firstHalf->avg(fn ($p) => $this->getMetric($p, 'engagement_rate'));
        $secondAvg = $secondHalf->avg(fn ($p) => $this->getMetric($p, 'engagement_rate'));

        if ($firstAvg == 0) {
            return 'no_baseline';
        }

        $change = (($secondAvg - $firstAvg) / $firstAvg) * 100;

        return match (true) {
            $change > 10 => 'rising',
            $change < -10 => 'falling',
            default => 'stable',
        };
    }
}
