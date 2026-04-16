<?php

namespace App\Services\Marketing\Orchestrator;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Content Feedback Loop — sinxron kontent o'rganish zanjiri
 *
 * Vazifalari:
 *   1. Top performer content patterns → ContentIdeas
 *   2. Failed content patterns → Style guide yangilash
 *   3. Weekly analysis — qaysi turdagi post ishlaydi
 *   4. Cross-channel learning (Instagram post Telegram uchun adaptatsiya)
 */
class ContentFeedbackLoop
{
    /**
     * Top performer'lardan ContentIdea yaratish
     */
    public function extractTopPerformers(string $businessId, int $days = 30): array
    {
        $since = now()->subDays($days);

        // Top 20% postlar (engagement bo'yicha)
        $topPosts = DB::table('content_generations')
            ->where('business_id', $businessId)
            ->where('created_at', '>=', $since)
            ->where('was_published', true)
            ->whereNotNull('post_engagement_rate')
            ->where('post_engagement_rate', '>', 0)
            ->orderByDesc('post_engagement_rate')
            ->limit(20)
            ->get(['id', 'topic', 'content_type', 'target_channel', 'post_engagement_rate', 'post_likes', 'generated_hashtags', 'generated_content']);

        if ($topPosts->isEmpty()) {
            return ['success' => false, 'message' => 'Yetarli tahlil qilingan post yo\'q'];
        }

        // Pattern'larni topish
        $patterns = $this->detectPatterns($topPosts);

        // ContentIdeas ga qo'shish
        $ideasCreated = 0;
        foreach ($patterns['top_topics'] as $topic) {
            if ($this->createIdeaFromTopic($businessId, $topic, $patterns)) {
                $ideasCreated++;
            }
        }

        return [
            'success' => true,
            'top_posts_analyzed' => $topPosts->count(),
            'patterns' => $patterns,
            'ideas_created' => $ideasCreated,
        ];
    }

    /**
     * Failed content tahlili — qaysi turdagi post ishlamaydi
     */
    public function analyzeFailures(string $businessId, int $days = 30): array
    {
        $since = now()->subDays($days);

        $failedPosts = DB::table('content_generations')
            ->where('business_id', $businessId)
            ->where('created_at', '>=', $since)
            ->where('was_published', true)
            ->whereNotNull('post_engagement_rate')
            ->where('post_engagement_rate', '<', 1)
            ->get(['content_type', 'target_channel', 'topic', 'post_engagement_rate']);

        if ($failedPosts->isEmpty()) {
            return ['success' => true, 'message' => 'Yomon post yo\'q'];
        }

        // Type bo'yicha statistika
        $byType = $failedPosts->groupBy('content_type')->map(fn($g) => $g->count());
        $byChannel = $failedPosts->groupBy('target_channel')->map(fn($g) => $g->count());

        $recommendations = [];

        // Eng ko'p yomon content_type — undan kamroq qilish
        if ($byType->count() > 0) {
            $worstType = $byType->sortDesc()->keys()->first();
            $recommendations[] = [
                'type' => 'reduce_content_type',
                'message' => "\"{$worstType}\" turidagi postlar ko'p marta yomon chiqyapti. Kamroq yarating.",
                'data' => $worstType,
            ];
        }

        // Eng ko'p yomon channel
        if ($byChannel->count() > 0) {
            $worstChannel = $byChannel->sortDesc()->keys()->first();
            $recommendations[] = [
                'type' => 'review_channel',
                'message' => "\"{$worstChannel}\" kanalida engagement past. Strategiyani qayta ko'rib chiqing.",
                'data' => $worstChannel,
            ];
        }

        return [
            'success' => true,
            'failed_posts_count' => $failedPosts->count(),
            'by_type' => $byType,
            'by_channel' => $byChannel,
            'recommendations' => $recommendations,
        ];
    }

    /**
     * Haftalik full feedback analiz
     */
    public function runWeeklyAnalysis(string $businessId): array
    {
        try {
            $topResult = $this->extractTopPerformers($businessId, 7);
            $failResult = $this->analyzeFailures($businessId, 7);

            return [
                'success' => true,
                'analyzed_at' => now()->toISOString(),
                'top_performers' => $topResult,
                'failures' => $failResult,
            ];
        } catch (\Exception $e) {
            Log::error('ContentFeedbackLoop xato', ['error' => $e->getMessage()]);
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Pattern'larni topish (top postlardan)
     */
    private function detectPatterns($topPosts): array
    {
        $topics = [];
        $contentTypes = [];
        $channels = [];
        $allHashtags = [];

        foreach ($topPosts as $post) {
            if (!empty($post->topic)) {
                $topics[] = $post->topic;
            }
            $contentTypes[] = $post->content_type;
            $channels[] = $post->target_channel;

            $hashtags = is_string($post->generated_hashtags)
                ? json_decode($post->generated_hashtags, true)
                : $post->generated_hashtags;
            if (is_array($hashtags)) {
                $allHashtags = array_merge($allHashtags, $hashtags);
            }
        }

        return [
            'top_topics' => array_slice(array_count_values(array_filter($topics)), 0, 5, true),
            'best_content_types' => array_count_values($contentTypes),
            'best_channels' => array_count_values($channels),
            'popular_hashtags' => array_slice(array_count_values($allHashtags), 0, 10, true),
            'avg_engagement' => round($topPosts->avg('post_engagement_rate'), 2),
        ];
    }

    /**
     * Topik'dan yangi ContentIdea yaratish (agar hali yo'q bo'lsa)
     */
    private function createIdeaFromTopic(string $businessId, string $topic, array $patterns): bool
    {
        try {
            // Mavjud g'oya bormi tekshirish
            $exists = DB::table('content_ideas')
                ->where('business_id', $businessId)
                ->where('title', 'LIKE', "%{$topic}%")
                ->exists();

            if ($exists) return false;

            // Hashtags ro'yxatini tayyorlash
            $hashtags = array_keys($patterns['popular_hashtags'] ?? []);

            DB::table('content_ideas')->insert([
                'id' => Str::uuid()->toString(),
                'business_id' => $businessId,
                'title' => 'Yana "' . $topic . '" mavzusida post (top performer asosida)',
                'description' => 'Oldingi postingiz bu mavzuda yuqori engagement bergan. Shu naqsh asosida yangi post.',
                'status' => 'pending',
                'source' => 'top_performer_feedback',
                'metadata' => json_encode([
                    'from_topic' => $topic,
                    'suggested_hashtags' => array_slice($hashtags, 0, 5),
                    'avg_engagement' => $patterns['avg_engagement'],
                ]),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            return true;
        } catch (\Exception $e) {
            Log::warning('ContentIdea create xato', ['error' => $e->getMessage()]);
            return false;
        }
    }
}
