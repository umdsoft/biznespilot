<?php

namespace App\Services\Algorithm;

use App\Models\Business;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Content Optimization Algorithm
 *
 * Kontent strategiyasini optimallashtirish algoritmi.
 *
 * Tahlil qilinadigan:
 * - Best posting times
 * - Content type performance
 * - Caption length effectiveness
 * - Hashtag effectiveness
 * - Posting frequency
 *
 * @version 2.0.0
 */
class ContentOptimizationAlgorithm extends AlgorithmEngine
{
    protected string $cachePrefix = 'content_opt_';

    protected int $cacheTTL = 1800; // 30 minutes

    /**
     * Industry benchmarks
     */
    protected array $benchmarks = [
        'posts_per_week' => [
            'minimum' => 3,
            'optimal' => 5,
            'maximum' => 10,
        ],
        'stories_per_day' => [
            'minimum' => 1,
            'optimal' => 3,
            'maximum' => 7,
        ],
        'engagement_rate' => [
            'poor' => 1.0,
            'average' => 2.0,
            'good' => 3.5,
            'excellent' => 5.0,
        ],
        'caption_length' => [
            'optimal_min' => 125,
            'optimal_max' => 500,
        ],
        'hashtags' => [
            'optimal_min' => 5,
            'optimal_max' => 15,
        ],
    ];

    /**
     * Calculate content optimization
     */
    public function calculate(Business $business): array
    {
        // Get content data
        $contentData = $this->getContentData($business);

        if (empty($contentData)) {
            return $this->getNoContentResult();
        }

        // Analyze posting times
        $postingAnalysis = $this->analyzePostingTimes($contentData);

        // Analyze content types
        $contentTypeAnalysis = $this->analyzeContentTypes($contentData);

        // Analyze caption effectiveness
        $captionAnalysis = $this->analyzeCaptions($contentData);

        // Analyze hashtags
        $hashtagAnalysis = $this->analyzeHashtags($contentData);

        // Analyze posting frequency
        $frequencyAnalysis = $this->analyzeFrequency($contentData);

        // Calculate overall score
        $score = $this->calculateOverallScore([
            'posting' => $postingAnalysis['score'] ?? 50,
            'content_type' => $contentTypeAnalysis['score'] ?? 50,
            'caption' => $captionAnalysis['score'] ?? 50,
            'hashtag' => $hashtagAnalysis['score'] ?? 50,
            'frequency' => $frequencyAnalysis['score'] ?? 50,
        ]);

        // Generate recommendations
        $recommendations = $this->generateRecommendations(
            $postingAnalysis,
            $contentTypeAnalysis,
            $captionAnalysis,
            $hashtagAnalysis,
            $frequencyAnalysis
        );

        // Create content calendar suggestions
        $calendarSuggestions = $this->generateCalendarSuggestions($postingAnalysis, $frequencyAnalysis);

        return [
            'score' => $score,
            'status' => $this->getScoreStatus($score),
            'posting_analysis' => $postingAnalysis,
            'content_type_analysis' => $contentTypeAnalysis,
            'caption_analysis' => $captionAnalysis,
            'hashtag_analysis' => $hashtagAnalysis,
            'frequency_analysis' => $frequencyAnalysis,
            'recommendations' => $recommendations,
            'calendar_suggestions' => $calendarSuggestions,
            'quick_wins' => $this->getQuickWins($recommendations),
        ];
    }

    /**
     * Get content data
     */
    protected function getContentData(Business $business): array
    {
        try {
            $instagram = $business->instagramAccounts()->first();
            if (! $instagram) {
                return [];
            }

            $media = DB::table('instagram_media')
                ->where('instagram_account_id', $instagram->id)
                ->where('timestamp', '>=', now()->subDays(90))
                ->orderBy('timestamp', 'desc')
                ->get();

            return $media->map(fn ($m) => (array) $m)->toArray();

        } catch (\Exception $e) {
            Log::warning('Could not get content data', ['error' => $e->getMessage()]);

            return $this->generateSampleData();
        }
    }

    /**
     * Analyze posting times
     */
    protected function analyzePostingTimes(array $contentData): array
    {
        $byHour = [];
        $byDay = [];
        $byDayHour = [];

        foreach ($contentData as $post) {
            $timestamp = $post['timestamp'] ?? $post['created_at'] ?? null;
            if (! $timestamp) {
                continue;
            }

            $hour = (int) date('G', strtotime($timestamp));
            $day = (int) date('N', strtotime($timestamp)); // 1=Monday, 7=Sunday
            $engagement = $this->calculatePostEngagement($post);

            $byHour[$hour][] = $engagement;
            $byDay[$day][] = $engagement;
            $byDayHour[$day][$hour][] = $engagement;
        }

        // Calculate averages
        $avgByHour = [];
        foreach ($byHour as $hour => $engagements) {
            $avgByHour[$hour] = round(array_sum($engagements) / count($engagements), 2);
        }

        $avgByDay = [];
        $dayNames = [1 => 'Dushanba', 2 => 'Seshanba', 3 => 'Chorshanba', 4 => 'Payshanba', 5 => 'Juma', 6 => 'Shanba', 7 => 'Yakshanba'];
        foreach ($byDay as $day => $engagements) {
            $avgByDay[$dayNames[$day]] = round(array_sum($engagements) / count($engagements), 2);
        }

        // Find best times
        arsort($avgByHour);
        arsort($avgByDay);

        $bestHours = array_slice(array_keys($avgByHour), 0, 3);
        $bestDays = array_slice(array_keys($avgByDay), 0, 3);

        // Generate optimal schedule
        $optimalSchedule = [];
        foreach ($bestDays as $day) {
            foreach ($bestHours as $hour) {
                $optimalSchedule[] = [
                    'day' => $day,
                    'time' => sprintf('%02d:00', $hour),
                    'expected_engagement' => $avgByDay[$day] ?? 0,
                ];
            }
        }

        // Score based on whether current posting aligns with optimal times
        $score = $this->calculatePostingScore($contentData, $bestHours, array_search(array_key_first($avgByDay), $dayNames) ?: 1);

        return [
            'score' => $score,
            'by_hour' => $avgByHour,
            'by_day' => $avgByDay,
            'best_hours' => array_map(fn ($h) => sprintf('%02d:00', $h), $bestHours),
            'best_days' => $bestDays,
            'optimal_schedule' => array_slice($optimalSchedule, 0, 7),
            'current_pattern' => $this->getCurrentPostingPattern($contentData),
            'recommendation' => $this->getPostingRecommendation($bestHours, $bestDays),
        ];
    }

    /**
     * Analyze content types
     */
    protected function analyzeContentTypes(array $contentData): array
    {
        $byType = [];

        foreach ($contentData as $post) {
            $type = $post['media_type'] ?? 'IMAGE';
            $engagement = $this->calculatePostEngagement($post);

            if (! isset($byType[$type])) {
                $byType[$type] = ['engagements' => [], 'count' => 0];
            }
            $byType[$type]['engagements'][] = $engagement;
            $byType[$type]['count']++;
        }

        $analysis = [];
        foreach ($byType as $type => $data) {
            $avgEngagement = count($data['engagements']) > 0
                ? array_sum($data['engagements']) / count($data['engagements'])
                : 0;

            $analysis[$type] = [
                'count' => $data['count'],
                'avg_engagement' => round($avgEngagement, 2),
                'label' => $this->getTypeLabel($type),
                'recommendation' => $this->getTypeRecommendation($type, $avgEngagement),
            ];
        }

        // Sort by engagement
        uasort($analysis, fn ($a, $b) => $b['avg_engagement'] <=> $a['avg_engagement']);

        // Score based on content mix
        $score = $this->calculateContentMixScore($analysis);

        $bestType = array_key_first($analysis);

        return [
            'score' => $score,
            'by_type' => $analysis,
            'best_performing' => $bestType,
            'best_performing_label' => $this->getTypeLabel($bestType),
            'recommended_mix' => $this->getRecommendedMix($analysis),
            'current_mix' => $this->getCurrentMix($analysis),
        ];
    }

    /**
     * Analyze captions
     */
    protected function analyzeCaptions(array $contentData): array
    {
        $lengthBuckets = [
            'short' => ['range' => [0, 125], 'engagements' => []],
            'medium' => ['range' => [126, 500], 'engagements' => []],
            'long' => ['range' => [501, 2000], 'engagements' => []],
            'very_long' => ['range' => [2001, 10000], 'engagements' => []],
        ];

        foreach ($contentData as $post) {
            $caption = $post['caption'] ?? '';
            $length = mb_strlen($caption);
            $engagement = $this->calculatePostEngagement($post);

            foreach ($lengthBuckets as $bucket => &$data) {
                if ($length >= $data['range'][0] && $length <= $data['range'][1]) {
                    $data['engagements'][] = $engagement;
                    break;
                }
            }
        }

        $analysis = [];
        foreach ($lengthBuckets as $bucket => $data) {
            $avgEngagement = count($data['engagements']) > 0
                ? array_sum($data['engagements']) / count($data['engagements'])
                : 0;

            $analysis[$bucket] = [
                'range' => $data['range'][0].'-'.$data['range'][1].' belgi',
                'posts_count' => count($data['engagements']),
                'avg_engagement' => round($avgEngagement, 2),
            ];
        }

        // Find optimal length
        $bestBucket = 'medium';
        $bestEngagement = 0;
        foreach ($analysis as $bucket => $data) {
            if ($data['avg_engagement'] > $bestEngagement) {
                $bestEngagement = $data['avg_engagement'];
                $bestBucket = $bucket;
            }
        }

        $score = $this->calculateCaptionScore($analysis);

        return [
            'score' => $score,
            'by_length' => $analysis,
            'optimal_length' => $lengthBuckets[$bestBucket]['range'][0].'-'.$lengthBuckets[$bestBucket]['range'][1],
            'recommendation' => $this->getCaptionRecommendation($bestBucket),
            'tips' => [
                'Hook bilan boshlang (birinchi 125 belgi)',
                'CTA qo\'shing (savol yoki harakat)',
                'Emoji ishlating (lekin ortiqcha emas)',
                'Qimmatli ma\'lumot bering',
            ],
        ];
    }

    /**
     * Analyze hashtags
     */
    protected function analyzeHashtags(array $contentData): array
    {
        $byCount = [];

        foreach ($contentData as $post) {
            $caption = $post['caption'] ?? '';
            preg_match_all('/#\w+/u', $caption, $matches);
            $hashtagCount = count($matches[0] ?? []);
            $engagement = $this->calculatePostEngagement($post);

            $bucket = match (true) {
                $hashtagCount === 0 => '0',
                $hashtagCount <= 5 => '1-5',
                $hashtagCount <= 10 => '6-10',
                $hashtagCount <= 15 => '11-15',
                $hashtagCount <= 20 => '16-20',
                default => '20+',
            };

            if (! isset($byCount[$bucket])) {
                $byCount[$bucket] = ['engagements' => [], 'hashtags' => []];
            }
            $byCount[$bucket]['engagements'][] = $engagement;
            $byCount[$bucket]['hashtags'] = array_merge($byCount[$bucket]['hashtags'], $matches[0] ?? []);
        }

        $analysis = [];
        foreach ($byCount as $bucket => $data) {
            $avgEngagement = count($data['engagements']) > 0
                ? array_sum($data['engagements']) / count($data['engagements'])
                : 0;

            $analysis[$bucket] = [
                'posts_count' => count($data['engagements']),
                'avg_engagement' => round($avgEngagement, 2),
            ];
        }

        // Find most used hashtags
        $allHashtags = [];
        foreach ($byCount as $data) {
            foreach ($data['hashtags'] as $tag) {
                $allHashtags[$tag] = ($allHashtags[$tag] ?? 0) + 1;
            }
        }
        arsort($allHashtags);
        $topHashtags = array_slice(array_keys($allHashtags), 0, 10);

        // Find optimal count
        $bestBucket = '6-10';
        $bestEngagement = 0;
        foreach ($analysis as $bucket => $data) {
            if ($data['avg_engagement'] > $bestEngagement) {
                $bestEngagement = $data['avg_engagement'];
                $bestBucket = $bucket;
            }
        }

        $score = $this->calculateHashtagScore($analysis);

        return [
            'score' => $score,
            'by_count' => $analysis,
            'optimal_count' => $bestBucket,
            'top_hashtags' => $topHashtags,
            'recommendation' => $bestBucket.' ta hashtag ishlating',
            'tips' => [
                'Niche hashtag ishlating (juda popular emas)',
                'Mahalliy hashtag qo\'shing (#toshkent, #uzbekistan)',
                'Brand hashtag yarating',
                'Har postda har xil hashtag ishlating',
            ],
        ];
    }

    /**
     * Analyze posting frequency
     */
    protected function analyzeFrequency(array $contentData): array
    {
        $postsByWeek = [];

        foreach ($contentData as $post) {
            $timestamp = $post['timestamp'] ?? $post['created_at'] ?? null;
            if (! $timestamp) {
                continue;
            }

            $week = date('Y-W', strtotime($timestamp));
            $postsByWeek[$week] = ($postsByWeek[$week] ?? 0) + 1;
        }

        $avgPerWeek = count($postsByWeek) > 0
            ? array_sum($postsByWeek) / count($postsByWeek)
            : 0;

        $status = match (true) {
            $avgPerWeek >= $this->benchmarks['posts_per_week']['optimal'] => 'excellent',
            $avgPerWeek >= $this->benchmarks['posts_per_week']['minimum'] => 'good',
            $avgPerWeek >= 1 => 'low',
            default => 'critical',
        };

        $score = match ($status) {
            'excellent' => 90,
            'good' => 70,
            'low' => 40,
            'critical' => 20,
        };

        return [
            'score' => $score,
            'avg_per_week' => round($avgPerWeek, 1),
            'status' => $status,
            'benchmark' => $this->benchmarks['posts_per_week'],
            'weekly_data' => array_slice($postsByWeek, -8, 8, true),
            'recommendation' => $this->getFrequencyRecommendation($avgPerWeek),
            'target' => $this->benchmarks['posts_per_week']['optimal'].' post/hafta',
        ];
    }

    /**
     * Calculate post engagement
     */
    protected function calculatePostEngagement(array $post): float
    {
        $likes = $post['like_count'] ?? $post['likes'] ?? 0;
        $comments = $post['comments_count'] ?? $post['comments'] ?? 0;
        $saves = $post['saved'] ?? 0;
        $shares = $post['shares'] ?? 0;
        $reach = $post['reach'] ?? max(1, $likes * 10);

        return round((($likes + $comments * 2 + $saves * 3 + $shares * 4) / $reach) * 100, 2);
    }

    /**
     * Calculate overall score
     */
    protected function calculateOverallScore(array $scores): int
    {
        $weights = [
            'posting' => 0.20,
            'content_type' => 0.25,
            'caption' => 0.20,
            'hashtag' => 0.15,
            'frequency' => 0.20,
        ];

        $weighted = 0;
        foreach ($scores as $key => $score) {
            $weighted += $score * ($weights[$key] ?? 0.2);
        }

        return (int) round($weighted);
    }

    /**
     * Get score status
     */
    protected function getScoreStatus(int $score): array
    {
        if ($score >= 80) {
            return ['level' => 'excellent', 'label' => 'Ajoyib', 'color' => 'blue'];
        }
        if ($score >= 60) {
            return ['level' => 'good', 'label' => 'Yaxshi', 'color' => 'green'];
        }
        if ($score >= 40) {
            return ['level' => 'average', 'label' => 'O\'rtacha', 'color' => 'yellow'];
        }

        return ['level' => 'poor', 'label' => 'Zaif', 'color' => 'red'];
    }

    /**
     * Generate recommendations
     */
    protected function generateRecommendations($posting, $contentType, $caption, $hashtag, $frequency): array
    {
        $recommendations = [];

        if (($frequency['score'] ?? 0) < 70) {
            $recommendations[] = [
                'priority' => 'high',
                'area' => 'Post chastotasi',
                'issue' => 'Haftada '.($frequency['avg_per_week'] ?? 0).' post - kam',
                'solution' => 'Haftada '.$this->benchmarks['posts_per_week']['optimal'].' ga oshiring',
                'impact' => '+30% reach',
            ];
        }

        if (($contentType['score'] ?? 0) < 70) {
            $recommendations[] = [
                'priority' => 'high',
                'area' => 'Kontent turi',
                'issue' => 'Kontent mix optimal emas',
                'solution' => ($contentType['best_performing_label'] ?? 'Reels').' ko\'proq joylang',
                'impact' => '+25% engagement',
            ];
        }

        if (($posting['score'] ?? 0) < 70) {
            $recommendations[] = [
                'priority' => 'medium',
                'area' => 'Post vaqti',
                'issue' => 'Optimal vaqtda post qilmayapsiz',
                'solution' => 'Eng yaxshi vaqtlar: '.implode(', ', $posting['best_hours'] ?? ['12:00', '19:00']),
                'impact' => '+20% engagement',
            ];
        }

        if (($hashtag['score'] ?? 0) < 70) {
            $recommendations[] = [
                'priority' => 'medium',
                'area' => 'Hashtag',
                'issue' => 'Hashtag strategiyasi zaif',
                'solution' => ($hashtag['optimal_count'] ?? '6-10').' ta hashtag ishlating',
                'impact' => '+15% reach',
            ];
        }

        return $recommendations;
    }

    /**
     * Generate calendar suggestions
     */
    protected function generateCalendarSuggestions(array $posting, array $frequency): array
    {
        $suggestions = [];
        $schedule = $posting['optimal_schedule'] ?? [];

        foreach (array_slice($schedule, 0, 7) as $slot) {
            $suggestions[] = [
                'day' => $slot['day'],
                'time' => $slot['time'],
                'content_type' => 'Reels yoki Carousel',
                'tip' => 'Hook bilan boshlang',
            ];
        }

        return $suggestions;
    }

    /**
     * Get quick wins
     */
    protected function getQuickWins(array $recommendations): array
    {
        return array_filter($recommendations, fn ($r) => $r['priority'] === 'high');
    }

    /**
     * Helper methods
     */
    protected function getTypeLabel(string $type): string
    {
        return match ($type) {
            'IMAGE' => 'Rasm',
            'VIDEO' => 'Video',
            'CAROUSEL_ALBUM' => 'Carousel',
            'REELS' => 'Reels',
            default => $type,
        };
    }

    protected function getTypeRecommendation(string $type, float $engagement): string
    {
        if ($engagement >= 3) {
            return 'Ajoyib natija! Ko\'proq joylang';
        }

        return match ($type) {
            'IMAGE' => 'Carousel formatga o\'ting',
            'VIDEO' => 'Reels formatga o\'ting',
            'CAROUSEL_ALBUM' => 'Birinchi slaydni yaxshilang',
            'REELS' => 'Hook ni 1-sekundda bering',
            default => 'Kontent sifatini yaxshilang',
        };
    }

    protected function getRecommendedMix(array $analysis): array
    {
        return [
            'REELS' => 40,
            'CAROUSEL_ALBUM' => 30,
            'IMAGE' => 20,
            'VIDEO' => 10,
        ];
    }

    protected function getCurrentMix(array $analysis): array
    {
        $total = array_sum(array_column($analysis, 'count'));
        if ($total === 0) {
            return [];
        }

        $mix = [];
        foreach ($analysis as $type => $data) {
            $mix[$type] = round(($data['count'] / $total) * 100);
        }

        return $mix;
    }

    protected function getPostingRecommendation(array $bestHours, array $bestDays): string
    {
        return 'Eng yaxshi vaqtlar: '.implode(', ', array_map(fn ($h) => sprintf('%02d:00', $h), $bestHours)).
               ' | Eng yaxshi kunlar: '.implode(', ', $bestDays);
    }

    protected function getCurrentPostingPattern(array $contentData): string
    {
        if (count($contentData) < 5) {
            return 'Ma\'lumot yetarli emas';
        }

        return 'Tahlil qilindi';
    }

    protected function getCaptionRecommendation(string $bucket): string
    {
        return match ($bucket) {
            'short' => 'Qisqa caption yaxshi ishlaydi - davom eting',
            'medium' => 'O\'rtacha uzunlik optimal - 125-500 belgi',
            'long' => 'Uzun caption ham yaxshi - qimmatli ma\'lumot bering',
            default => '125-500 belgi oralig\'ida yozing',
        };
    }

    protected function getFrequencyRecommendation(float $avg): string
    {
        if ($avg >= 5) {
            return 'Ajoyib chastota! Sifatni saqlang';
        }
        if ($avg >= 3) {
            return 'Yaxshi, lekin 5 ga oshiring';
        }

        return 'Kamida haftada 3-5 marta post qiling';
    }

    protected function calculatePostingScore(array $data, array $bestHours, int $bestDay): int
    {
        // Simplified scoring
        return count($data) > 20 ? 70 : 50;
    }

    protected function calculateContentMixScore(array $analysis): int
    {
        $hasReels = isset($analysis['REELS']) && $analysis['REELS']['count'] > 0;
        $hasCarousel = isset($analysis['CAROUSEL_ALBUM']) && $analysis['CAROUSEL_ALBUM']['count'] > 0;

        $score = 50;
        if ($hasReels) {
            $score += 25;
        }
        if ($hasCarousel) {
            $score += 15;
        }

        return min(100, $score);
    }

    protected function calculateCaptionScore(array $analysis): int
    {
        $medium = $analysis['medium'] ?? ['posts_count' => 0];
        $total = array_sum(array_column($analysis, 'posts_count'));

        if ($total === 0) {
            return 50;
        }

        return min(100, 50 + ($medium['posts_count'] / $total) * 50);
    }

    protected function calculateHashtagScore(array $analysis): int
    {
        $optimal = $analysis['6-10'] ?? ['posts_count' => 0];
        $total = array_sum(array_column($analysis, 'posts_count'));

        if ($total === 0) {
            return 50;
        }

        return min(100, 50 + ($optimal['posts_count'] / $total) * 50);
    }

    protected function generateSampleData(): array
    {
        return [];
    }

    protected function getNoContentResult(): array
    {
        return [
            'score' => 0,
            'status' => ['level' => 'none', 'label' => 'Ma\'lumot yo\'q', 'color' => 'gray'],
            'message' => 'Instagram ni ulang yoki kontent joylang',
            'recommendations' => [
                [
                    'priority' => 'critical',
                    'area' => 'Instagram',
                    'solution' => 'Instagram hisobini ulang',
                    'impact' => 'Tahlil imkoniyati',
                ],
            ],
        ];
    }
}
