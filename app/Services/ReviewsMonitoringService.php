<?php

namespace App\Services;

use App\Models\Competitor;
use App\Models\CompetitorAlert;
use App\Models\CompetitorReview;
use App\Models\CompetitorReviewSource;
use App\Models\CompetitorReviewStat;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ReviewsMonitoringService
{
    protected ClaudeAIService $claudeAI;

    protected int $cacheTTL = 3600;

    public function __construct(ClaudeAIService $claudeAI)
    {
        $this->claudeAI = $claudeAI;
    }

    /**
     * Monitor reviews for a competitor
     */
    public function monitorReviews(Competitor $competitor): array
    {
        $results = [
            'success' => false,
            'sources_checked' => 0,
            'new_reviews' => 0,
            'errors' => [],
        ];

        try {
            $sources = $competitor->reviewSources()->tracked()->get();

            foreach ($sources as $source) {
                $sourceResult = $this->checkReviewSource($source);
                $results['sources_checked']++;
                $results['new_reviews'] += $sourceResult['new_reviews'];
            }

            // Calculate daily stats
            $this->calculateDailyStats($competitor);

            $results['success'] = true;

        } catch (\Exception $e) {
            Log::error('Reviews monitoring error', [
                'competitor_id' => $competitor->id,
                'error' => $e->getMessage(),
            ]);
            $results['errors'][] = $e->getMessage();
        }

        return $results;
    }

    /**
     * Check a specific review source
     */
    protected function checkReviewSource(CompetitorReviewSource $source): array
    {
        $result = ['new_reviews' => 0];

        try {
            $reviewData = match ($source->platform) {
                'google' => $this->fetchGoogleReviews($source),
                '2gis' => $this->fetch2GISReviews($source),
                'yandex' => $this->fetchYandexReviews($source),
                default => null,
            };

            if ($reviewData) {
                // Update source ratings
                $source->update([
                    'current_rating' => $reviewData['rating'] ?? $source->current_rating,
                    'total_reviews' => $reviewData['total_reviews'] ?? $source->total_reviews,
                    'five_star_count' => $reviewData['rating_breakdown'][5] ?? $source->five_star_count,
                    'four_star_count' => $reviewData['rating_breakdown'][4] ?? $source->four_star_count,
                    'three_star_count' => $reviewData['rating_breakdown'][3] ?? $source->three_star_count,
                    'two_star_count' => $reviewData['rating_breakdown'][2] ?? $source->two_star_count,
                    'one_star_count' => $reviewData['rating_breakdown'][1] ?? $source->one_star_count,
                    'last_checked_at' => now(),
                ]);

                // Save new reviews
                foreach ($reviewData['reviews'] ?? [] as $review) {
                    if ($this->saveReview($source, $review)) {
                        $result['new_reviews']++;
                    }
                }
            }

        } catch (\Exception $e) {
            Log::warning('Review source check failed', [
                'source_id' => $source->id,
                'platform' => $source->platform,
                'error' => $e->getMessage(),
            ]);
        }

        return $result;
    }

    /**
     * Fetch Google reviews
     */
    protected function fetchGoogleReviews(CompetitorReviewSource $source): ?array
    {
        if (! $source->place_id) {
            return null;
        }

        $cacheKey = "google_reviews_{$source->place_id}";

        if ($cached = Cache::get($cacheKey)) {
            return $cached;
        }

        try {
            // Try to scrape Google Maps
            $url = "https://www.google.com/maps/place/?q=place_id:{$source->place_id}";

            $response = Http::withHeaders([
                'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
                'Accept-Language' => 'uz-UZ,uz;q=0.9,ru;q=0.8,en;q=0.7',
            ])->timeout(20)->get($url);

            if (! $response->successful()) {
                return null;
            }

            $html = $response->body();
            $data = $this->parseGoogleReviews($html);

            if ($data) {
                Cache::put($cacheKey, $data, $this->cacheTTL);
            }

            return $data;

        } catch (\Exception $e) {
            Log::warning('Google reviews fetch failed', ['error' => $e->getMessage()]);

            return null;
        }
    }

    /**
     * Parse Google reviews from HTML
     */
    protected function parseGoogleReviews(string $html): ?array
    {
        $data = [
            'rating' => null,
            'total_reviews' => 0,
            'rating_breakdown' => [5 => 0, 4 => 0, 3 => 0, 2 => 0, 1 => 0],
            'reviews' => [],
        ];

        // Extract rating
        if (preg_match('/(\d+[.,]\d+)\s*(?:out of 5|stars|yulduz)/i', $html, $matches)) {
            $data['rating'] = (float) str_replace(',', '.', $matches[1]);
        }

        // Extract total reviews count
        if (preg_match('/([\d,]+)\s*(?:reviews|отзыв|sharh)/i', $html, $matches)) {
            $data['total_reviews'] = (int) str_replace(',', '', $matches[1]);
        }

        // Extract individual reviews (simplified)
        if (preg_match_all('/data-review-id="([^"]+)".*?aria-label="(\d)\s*stars?".*?>(.*?)<\/span>/isu', $html, $matches, PREG_SET_ORDER)) {
            foreach (array_slice($matches, 0, 10) as $match) {
                $data['reviews'][] = [
                    'review_id' => $match[1],
                    'rating' => (int) $match[2],
                    'text' => strip_tags($match[3]),
                ];
            }
        }

        return $data['rating'] ? $data : null;
    }

    /**
     * Fetch 2GIS reviews
     */
    protected function fetch2GISReviews(CompetitorReviewSource $source): ?array
    {
        if (! $source->place_id) {
            return null;
        }

        $cacheKey = "2gis_reviews_{$source->place_id}";

        if ($cached = Cache::get($cacheKey)) {
            return $cached;
        }

        try {
            // 2GIS API endpoint
            $apiUrl = "https://public-api.reviews.2gis.com/2.0/branches/{$source->place_id}/reviews";

            $response = Http::withHeaders([
                'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
            ])->timeout(15)->get($apiUrl, [
                'limit' => 20,
                'sort_by' => 'date_created',
            ]);

            if (! $response->successful()) {
                // Fallback to web scraping
                return $this->scrape2GISReviews($source);
            }

            $json = $response->json();
            $data = $this->parse2GISApiResponse($json);

            if ($data) {
                Cache::put($cacheKey, $data, $this->cacheTTL);
            }

            return $data;

        } catch (\Exception $e) {
            Log::warning('2GIS reviews fetch failed', ['error' => $e->getMessage()]);

            return null;
        }
    }

    /**
     * Scrape 2GIS reviews from web
     */
    protected function scrape2GISReviews(CompetitorReviewSource $source): ?array
    {
        try {
            $url = $source->profile_url ?? "https://2gis.uz/tashkent/firm/{$source->place_id}";

            $response = Http::withHeaders([
                'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
            ])->timeout(15)->get($url);

            if (! $response->successful()) {
                return null;
            }

            $html = $response->body();
            $data = [
                'rating' => null,
                'total_reviews' => 0,
                'rating_breakdown' => [5 => 0, 4 => 0, 3 => 0, 2 => 0, 1 => 0],
                'reviews' => [],
            ];

            // Extract rating
            if (preg_match('/(\d+[.,]\d+).*?(?:rating|рейтинг)/i', $html, $matches)) {
                $data['rating'] = (float) str_replace(',', '.', $matches[1]);
            }

            // Extract review count
            if (preg_match('/([\d\s]+)\s*(?:отзыв|review|sharh)/iu', $html, $matches)) {
                $data['total_reviews'] = (int) preg_replace('/\s+/', '', $matches[1]);
            }

            return $data['rating'] ? $data : null;

        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Parse 2GIS API response
     */
    protected function parse2GISApiResponse(array $json): ?array
    {
        $data = [
            'rating' => $json['meta']['branch_rating'] ?? null,
            'total_reviews' => $json['meta']['total_count'] ?? 0,
            'rating_breakdown' => [5 => 0, 4 => 0, 3 => 0, 2 => 0, 1 => 0],
            'reviews' => [],
        ];

        foreach ($json['reviews'] ?? [] as $review) {
            $data['reviews'][] = [
                'review_id' => $review['id'] ?? null,
                'author_name' => $review['user']['name'] ?? null,
                'rating' => $review['rating'] ?? 0,
                'text' => $review['text'] ?? '',
                'date' => $review['date_created'] ?? null,
                'likes' => $review['likes_count'] ?? 0,
                'has_reply' => ! empty($review['official_answer']),
                'reply_text' => $review['official_answer']['text'] ?? null,
            ];

            // Update rating breakdown
            $rating = $review['rating'] ?? 0;
            if ($rating >= 1 && $rating <= 5) {
                $data['rating_breakdown'][$rating]++;
            }
        }

        return $data;
    }

    /**
     * Fetch Yandex reviews
     */
    protected function fetchYandexReviews(CompetitorReviewSource $source): ?array
    {
        // Similar implementation for Yandex Maps
        // For now, return null as Yandex requires special handling
        return null;
    }

    /**
     * Save review to database
     */
    protected function saveReview(CompetitorReviewSource $source, array $reviewData): bool
    {
        // Check if review already exists
        if (isset($reviewData['review_id'])) {
            $exists = CompetitorReview::where('source_id', $source->id)
                ->where('review_id', $reviewData['review_id'])
                ->exists();

            if ($exists) {
                return false;
            }
        }

        $review = CompetitorReview::create([
            'source_id' => $source->id,
            'competitor_id' => $source->competitor_id,
            'review_id' => $reviewData['review_id'] ?? null,
            'author_name' => $reviewData['author_name'] ?? null,
            'author_avatar' => $reviewData['author_avatar'] ?? null,
            'review_text' => $reviewData['text'] ?? null,
            'rating' => $reviewData['rating'] ?? 0,
            'review_date' => isset($reviewData['date']) ? Carbon::parse($reviewData['date']) : now(),
            'likes_count' => $reviewData['likes'] ?? 0,
            'has_owner_response' => $reviewData['has_reply'] ?? false,
            'owner_response' => $reviewData['reply_text'] ?? null,
        ]);

        // Analyze sentiment
        $this->analyzeReviewSentiment($review);

        // Check for critical reviews
        if ($review->rating <= 2) {
            $review->update(['is_critical' => true]);
            $this->createNegativeReviewAlert($review);
        }

        return true;
    }

    /**
     * Analyze review sentiment using AI
     */
    protected function analyzeReviewSentiment(CompetitorReview $review): void
    {
        if (! $review->review_text) {
            return;
        }

        try {
            // Simple sentiment based on rating
            if ($review->rating >= 4) {
                $sentiment = 'positive';
                $score = 0.8;
            } elseif ($review->rating <= 2) {
                $sentiment = 'negative';
                $score = -0.8;
            } else {
                $sentiment = 'neutral';
                $score = 0;
            }

            // Extract topics
            $topics = $this->extractTopics($review->review_text);

            $review->update([
                'sentiment' => $sentiment,
                'sentiment_score' => $score,
                'topics' => $topics,
            ]);

        } catch (\Exception $e) {
            Log::warning('Sentiment analysis failed', ['error' => $e->getMessage()]);
        }
    }

    /**
     * Extract topics from review text
     */
    protected function extractTopics(string $text): array
    {
        $topics = [];
        $textLower = mb_strtolower($text);

        $topicKeywords = [
            'service' => ['xizmat', 'обслуживание', 'service', 'персонал', 'xodim'],
            'quality' => ['sifat', 'качество', 'quality', 'yaxshi', 'хороший'],
            'price' => ['narx', 'цена', 'price', 'arzon', 'дешево', 'qimmat', 'дорого'],
            'speed' => ['tez', 'быстро', 'fast', 'sekin', 'медленно', 'slow'],
            'location' => ['joylashuv', 'расположение', 'location', 'manzil'],
            'cleanliness' => ['toza', 'чистота', 'clean', 'iflos', 'грязно'],
            'food' => ['taom', 'еда', 'food', 'mazali', 'вкусно'],
            'atmosphere' => ['muhit', 'атмосфера', 'atmosphere', 'qulay'],
        ];

        foreach ($topicKeywords as $topic => $keywords) {
            foreach ($keywords as $keyword) {
                if (str_contains($textLower, $keyword)) {
                    $topics[] = $topic;
                    break;
                }
            }
        }

        return array_unique($topics);
    }

    /**
     * Create alert for negative review
     */
    protected function createNegativeReviewAlert(CompetitorReview $review): void
    {
        $competitor = $review->competitor;
        $source = $review->source;

        CompetitorAlert::create([
            'competitor_id' => $competitor->id,
            'business_id' => $competitor->business_id,
            'type' => 'negative_review',
            'severity' => $review->rating === 1 ? 'high' : 'medium',
            'title' => "{$competitor->name} - Salbiy sharh",
            'message' => "Yangi {$review->rating}-yulduzli sharh {$source->platform}da. ".
                mb_substr($review->review_text ?? '', 0, 100).'...',
            'data' => [
                'review_id' => $review->id,
                'platform' => $source->platform,
                'rating' => $review->rating,
            ],
        ]);
    }

    /**
     * Calculate daily review stats
     */
    protected function calculateDailyStats(Competitor $competitor): void
    {
        $sources = $competitor->reviewSources()->get();
        $platforms = $sources->pluck('platform')->unique();

        foreach ($platforms as $platform) {
            $platformSources = $sources->where('platform', $platform);
            $todayReviews = CompetitorReview::whereIn('source_id', $platformSources->pluck('id'))
                ->whereDate('review_date', today())
                ->get();

            if ($todayReviews->isEmpty() && $platformSources->isEmpty()) {
                continue;
            }

            // Calculate cumulative stats
            $allReviews = CompetitorReview::whereIn('source_id', $platformSources->pluck('id'))->get();

            $positiveCount = $todayReviews->where('sentiment', 'positive')->count();
            $negativeCount = $todayReviews->where('sentiment', 'negative')->count();
            $neutralCount = $todayReviews->where('sentiment', 'neutral')->count();

            // Top topics
            $allTopics = [];
            foreach ($todayReviews as $review) {
                foreach ($review->topics ?? [] as $topic) {
                    $sentiment = $review->sentiment ?? 'neutral';
                    if (! isset($allTopics[$topic])) {
                        $allTopics[$topic] = ['positive' => 0, 'negative' => 0, 'neutral' => 0];
                    }
                    $allTopics[$topic][$sentiment]++;
                }
            }

            $topPositive = collect($allTopics)->sortByDesc('positive')->take(5)->keys()->toArray();
            $topNegative = collect($allTopics)->sortByDesc('negative')->take(5)->keys()->toArray();

            CompetitorReviewStat::updateOrCreate(
                [
                    'competitor_id' => $competitor->id,
                    'platform' => $platform,
                    'stat_date' => today(),
                ],
                [
                    'new_reviews_count' => $todayReviews->count(),
                    'avg_rating' => $todayReviews->avg('rating'),
                    'cumulative_rating' => $platformSources->avg('current_rating'),
                    'cumulative_reviews' => $platformSources->sum('total_reviews'),
                    'positive_reviews' => $positiveCount,
                    'neutral_reviews' => $neutralCount,
                    'negative_reviews' => $negativeCount,
                    'sentiment_score' => $todayReviews->avg('sentiment_score'),
                    'responded_reviews' => $todayReviews->where('has_owner_response', true)->count(),
                    'response_rate' => $todayReviews->count() > 0
                        ? round(($todayReviews->where('has_owner_response', true)->count() / $todayReviews->count()) * 100, 1)
                        : null,
                    'top_positive_topics' => $topPositive,
                    'top_negative_topics' => $topNegative,
                ]
            );
        }
    }

    /**
     * Add review source
     */
    public function addReviewSource(Competitor $competitor, array $data): CompetitorReviewSource
    {
        return CompetitorReviewSource::create(array_merge($data, [
            'competitor_id' => $competitor->id,
            'is_tracked' => true,
        ]));
    }

    /**
     * Get review insights for competitor
     */
    public function getReviewInsights(Competitor $competitor): array
    {
        $sources = $competitor->reviewSources()->get();

        if ($sources->isEmpty()) {
            return [
                'total_sources' => 0,
                'avg_rating' => null,
                'total_reviews' => 0,
                'platforms' => [],
            ];
        }

        // Aggregate by platform
        $platforms = [];
        foreach ($sources as $source) {
            if (! isset($platforms[$source->platform])) {
                $platforms[$source->platform] = [
                    'rating' => null,
                    'reviews' => 0,
                    'sources' => 0,
                ];
            }
            $platforms[$source->platform]['sources']++;
            $platforms[$source->platform]['reviews'] += $source->total_reviews;

            if ($source->current_rating) {
                $currentRating = $platforms[$source->platform]['rating'];
                $platforms[$source->platform]['rating'] = $currentRating
                    ? ($currentRating + $source->current_rating) / 2
                    : $source->current_rating;
            }
        }

        // Recent reviews analysis
        $recentReviews = CompetitorReview::whereIn('source_id', $sources->pluck('id'))
            ->where('review_date', '>=', now()->subDays(30))
            ->get();

        $sentimentBreakdown = [
            'positive' => $recentReviews->where('sentiment', 'positive')->count(),
            'neutral' => $recentReviews->where('sentiment', 'neutral')->count(),
            'negative' => $recentReviews->where('sentiment', 'negative')->count(),
        ];

        return [
            'total_sources' => $sources->count(),
            'avg_rating' => round($sources->whereNotNull('current_rating')->avg('current_rating'), 1),
            'total_reviews' => $sources->sum('total_reviews'),
            'platforms' => $platforms,
            'recent_reviews_30d' => $recentReviews->count(),
            'sentiment_breakdown' => $sentimentBreakdown,
            'critical_reviews' => $recentReviews->where('is_critical', true)->count(),
            'response_rate' => $recentReviews->count() > 0
                ? round(($recentReviews->where('has_owner_response', true)->count() / $recentReviews->count()) * 100, 1)
                : 0,
        ];
    }
}
