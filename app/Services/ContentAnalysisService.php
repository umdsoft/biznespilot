<?php

namespace App\Services;

use App\Models\Competitor;
use App\Models\CompetitorContent;
use App\Models\CompetitorContentStat;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class ContentAnalysisService
{
    protected ?SocialMediaScraperService $scraper = null;

    protected ClaudeAIService $claudeAI;

    public function __construct(?SocialMediaScraperService $scraper, ClaudeAIService $claudeAI)
    {
        $this->scraper = $scraper;
        $this->claudeAI = $claudeAI;
    }

    /**
     * Analyze content for a competitor
     */
    public function analyzeCompetitor(Competitor $competitor): array
    {
        $results = [
            'success' => false,
            'platforms_analyzed' => [],
            'new_content_count' => 0,
            'errors' => [],
        ];

        try {
            // Analyze Instagram content
            if ($competitor->instagram_handle) {
                $instagramResult = $this->analyzeInstagramContent($competitor);
                if ($instagramResult) {
                    $results['platforms_analyzed'][] = 'instagram';
                    $results['new_content_count'] += $instagramResult['new_posts'];
                }
            }

            // Analyze Telegram content
            if ($competitor->telegram_handle) {
                $telegramResult = $this->analyzeTelegramContent($competitor);
                if ($telegramResult) {
                    $results['platforms_analyzed'][] = 'telegram';
                    $results['new_content_count'] += $telegramResult['new_posts'];
                }
            }

            // Calculate daily stats
            $this->calculateDailyStats($competitor);

            $results['success'] = true;

        } catch (\Exception $e) {
            Log::error('Content analysis error', [
                'competitor_id' => $competitor->id,
                'error' => $e->getMessage(),
            ]);
            $results['errors'][] = $e->getMessage();
        }

        return $results;
    }

    /**
     * Analyze Instagram content
     */
    protected function analyzeInstagramContent(Competitor $competitor): ?array
    {
        Log::info('Analyzing Instagram content', ['handle' => $competitor->instagram_handle]);

        if (! $this->scraper) {
            Log::warning('SocialMediaScraperService not available');

            return null;
        }

        try {
            // Get recent posts via scraper
            $posts = $this->scraper->getInstagramPosts($competitor->instagram_handle, 12);

            if (! $posts) {
                return null;
            }

            $newPosts = 0;

            foreach ($posts as $post) {
                // Check if post already exists
                $exists = CompetitorContent::where('competitor_id', $competitor->id)
                    ->where('platform', 'instagram')
                    ->where('external_id', $post['id'] ?? null)
                    ->exists();

                if (! $exists) {
                    $this->saveContent($competitor, 'instagram', $post);
                    $newPosts++;
                }
            }

            return ['new_posts' => $newPosts];

        } catch (\Exception $e) {
            Log::error('Instagram content analysis failed', [
                'handle' => $competitor->instagram_handle,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Analyze Telegram content
     */
    protected function analyzeTelegramContent(Competitor $competitor): ?array
    {
        Log::info('Analyzing Telegram content', ['handle' => $competitor->telegram_handle]);

        if (! $this->scraper) {
            Log::warning('SocialMediaScraperService not available');

            return null;
        }

        try {
            // Get recent posts via scraper
            $posts = $this->scraper->getTelegramPosts($competitor->telegram_handle, 20);

            if (! $posts) {
                return null;
            }

            $newPosts = 0;

            foreach ($posts as $post) {
                // Check if post already exists
                $exists = CompetitorContent::where('competitor_id', $competitor->id)
                    ->where('platform', 'telegram')
                    ->where('external_id', $post['id'] ?? null)
                    ->exists();

                if (! $exists) {
                    $this->saveContent($competitor, 'telegram', $post);
                    $newPosts++;
                }
            }

            return ['new_posts' => $newPosts];

        } catch (\Exception $e) {
            Log::error('Telegram content analysis failed', [
                'handle' => $competitor->telegram_handle,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Save content to database
     */
    protected function saveContent(Competitor $competitor, string $platform, array $post): CompetitorContent
    {
        $publishedAt = isset($post['timestamp']) ? Carbon::parse($post['timestamp']) : now();

        $content = CompetitorContent::create([
            'competitor_id' => $competitor->id,
            'platform' => $platform,
            'content_type' => $post['type'] ?? 'post',
            'external_id' => $post['id'] ?? null,
            'caption' => $post['caption'] ?? $post['text'] ?? null,
            'hashtags' => $this->extractHashtags($post['caption'] ?? $post['text'] ?? ''),
            'mentions' => $this->extractMentions($post['caption'] ?? $post['text'] ?? ''),
            'media_type' => $post['media_type'] ?? null,
            'media_url' => $post['media_url'] ?? null,
            'thumbnail_url' => $post['thumbnail_url'] ?? null,
            'permalink' => $post['permalink'] ?? null,
            'likes' => $post['likes'] ?? 0,
            'comments' => $post['comments'] ?? 0,
            'shares' => $post['shares'] ?? 0,
            'saves' => $post['saves'] ?? 0,
            'views' => $post['views'] ?? 0,
            'is_sponsored' => $this->detectSponsored($post['caption'] ?? ''),
            'published_at' => $publishedAt,
            'day_of_week' => $publishedAt->format('l'),
            'hour_of_day' => $publishedAt->hour,
        ]);

        // Calculate engagement rate if we have follower data
        $this->calculateEngagementRate($content, $competitor);

        return $content;
    }

    /**
     * Extract hashtags from text
     */
    protected function extractHashtags(string $text): array
    {
        preg_match_all('/#(\w+)/u', $text, $matches);

        return $matches[1] ?? [];
    }

    /**
     * Extract mentions from text
     */
    protected function extractMentions(string $text): array
    {
        preg_match_all('/@(\w+)/u', $text, $matches);

        return $matches[1] ?? [];
    }

    /**
     * Detect if content is sponsored
     */
    protected function detectSponsored(string $text): bool
    {
        $sponsoredKeywords = [
            'reklama', 'ad', 'sponsored', 'реклама', 'promo',
            '#ad', '#sponsored', '#reklama', 'hamkorlik', 'партнер',
        ];

        $textLower = mb_strtolower($text);
        foreach ($sponsoredKeywords as $keyword) {
            if (str_contains($textLower, $keyword)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Calculate engagement rate for content
     */
    protected function calculateEngagementRate(CompetitorContent $content, Competitor $competitor): void
    {
        // Get latest follower count
        $latestMetric = $competitor->metrics()
            ->latest('recorded_date')
            ->first();

        $followers = 0;
        if ($content->platform === 'instagram') {
            $followers = $latestMetric?->instagram_followers ?? 0;
        } elseif ($content->platform === 'telegram') {
            $followers = $latestMetric?->telegram_members ?? 0;
        }

        if ($followers > 0) {
            $totalEngagement = $content->likes + $content->comments + $content->shares + $content->saves;
            $engagementRate = ($totalEngagement / $followers) * 100;

            $content->engagement_rate = round($engagementRate, 4);
            $content->is_viral = $engagementRate > 10; // 10% = viral
            $content->save();
        }
    }

    /**
     * Calculate daily content stats
     */
    protected function calculateDailyStats(Competitor $competitor): void
    {
        $platforms = ['instagram', 'telegram', 'facebook', 'tiktok'];

        foreach ($platforms as $platform) {
            $todayContent = $competitor->contents()
                ->where('platform', $platform)
                ->whereDate('published_at', today())
                ->get();

            if ($todayContent->isEmpty()) {
                continue;
            }

            $topContent = $todayContent->sortByDesc(function ($c) {
                return $c->likes + $c->comments + $c->shares;
            })->first();

            // Aggregate hashtags
            $allHashtags = [];
            foreach ($todayContent as $content) {
                foreach ($content->hashtags ?? [] as $tag) {
                    $allHashtags[$tag] = ($allHashtags[$tag] ?? 0) + 1;
                }
            }
            arsort($allHashtags);
            $topHashtags = array_slice($allHashtags, 0, 10, true);

            CompetitorContentStat::updateOrCreate(
                [
                    'competitor_id' => $competitor->id,
                    'platform' => $platform,
                    'stat_date' => today(),
                ],
                [
                    'posts_count' => $todayContent->where('content_type', 'post')->count(),
                    'reels_count' => $todayContent->where('content_type', 'reel')->count(),
                    'stories_count' => $todayContent->where('content_type', 'story')->count(),
                    'videos_count' => $todayContent->where('content_type', 'video')->count(),
                    'total_likes' => $todayContent->sum('likes'),
                    'total_comments' => $todayContent->sum('comments'),
                    'total_shares' => $todayContent->sum('shares'),
                    'total_views' => $todayContent->sum('views'),
                    'avg_engagement_rate' => $todayContent->avg('engagement_rate'),
                    'top_content_id' => $topContent?->id,
                    'top_content_engagement' => $topContent ? ($topContent->likes + $topContent->comments + $topContent->shares) : 0,
                    'top_hashtags' => array_map(fn ($tag, $count) => ['tag' => $tag, 'count' => $count], array_keys($topHashtags), $topHashtags),
                ]
            );
        }
    }

    /**
     * Get content insights for competitor
     */
    public function getContentInsights(Competitor $competitor, int $days = 30): array
    {
        $contents = $competitor->contents()
            ->where('published_at', '>=', now()->subDays($days))
            ->get();

        if ($contents->isEmpty()) {
            return [
                'total_posts' => 0,
                'avg_engagement_rate' => 0,
                'best_posting_times' => [],
                'top_hashtags' => [],
                'content_types' => [],
            ];
        }

        // Best posting times
        $hourlyEngagement = $contents->groupBy('hour_of_day')->map(function ($group) {
            return [
                'posts' => $group->count(),
                'avg_engagement' => $group->avg('engagement_rate'),
            ];
        })->sortByDesc('avg_engagement')->take(5);

        // Top hashtags
        $allHashtags = [];
        foreach ($contents as $content) {
            foreach ($content->hashtags ?? [] as $tag) {
                $allHashtags[$tag] = ($allHashtags[$tag] ?? 0) + 1;
            }
        }
        arsort($allHashtags);

        // Content types breakdown
        $contentTypes = $contents->groupBy('content_type')->map->count();

        return [
            'total_posts' => $contents->count(),
            'avg_engagement_rate' => round($contents->avg('engagement_rate'), 2),
            'total_likes' => $contents->sum('likes'),
            'total_comments' => $contents->sum('comments'),
            'viral_posts' => $contents->where('is_viral', true)->count(),
            'sponsored_posts' => $contents->where('is_sponsored', true)->count(),
            'best_posting_times' => $hourlyEngagement->toArray(),
            'top_hashtags' => array_slice($allHashtags, 0, 20, true),
            'content_types' => $contentTypes->toArray(),
            'posts_per_day' => round($contents->count() / $days, 1),
        ];
    }

    /**
     * Analyze content sentiment using AI
     */
    public function analyzeContentSentiment(CompetitorContent $content): ?string
    {
        if (! $content->caption) {
            return null;
        }

        try {
            $prompt = "Analyze the sentiment of this social media post and respond with only one word: positive, negative, or neutral.\n\nPost: {$content->caption}";

            $response = $this->claudeAI->complete($prompt, null, 50);
            $sentiment = strtolower(trim($response));

            if (in_array($sentiment, ['positive', 'negative', 'neutral'])) {
                $content->update(['sentiment' => $sentiment]);

                return $sentiment;
            }

            return null;

        } catch (\Exception $e) {
            Log::warning('Sentiment analysis failed', ['error' => $e->getMessage()]);

            return null;
        }
    }
}
