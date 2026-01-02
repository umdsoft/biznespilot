<?php

namespace App\Services\Algorithm;

use App\Models\Business;
use App\Models\InstagramAccount;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Engagement Algorithm
 *
 * Ijtimoiy tarmoq engagement metrikalarini hisoblash algoritmi.
 *
 * Formulalar:
 * - Engagement Rate = ((Likes + Comments + Saves + Shares) / Reach) × 100
 * - Growth Rate = ((Current - Previous) / Previous) × 100
 * - Virality Rate = (Shares / Impressions) × 100
 *
 * @version 2.0.0
 */
class EngagementAlgorithm extends AlgorithmEngine
{
    protected string $cachePrefix = 'engagement_';
    protected int $cacheTTL = 1800; // 30 minutes

    /**
     * Industry benchmarks for engagement
     */
    protected array $benchmarks = [
        'instagram' => [
            'engagement_rate' => [
                'excellent' => 5.0,
                'good' => 3.0,
                'average' => 1.5,
                'poor' => 0.5,
            ],
            'follower_growth' => [
                'excellent' => 10.0,
                'good' => 5.0,
                'average' => 2.0,
                'poor' => 0.5,
            ],
            'reach_rate' => [
                'excellent' => 40.0,
                'good' => 25.0,
                'average' => 15.0,
                'poor' => 5.0,
            ],
        ],
        'telegram' => [
            'engagement_rate' => [
                'excellent' => 15.0,
                'good' => 8.0,
                'average' => 4.0,
                'poor' => 1.0,
            ],
            'growth_rate' => [
                'excellent' => 15.0,
                'good' => 8.0,
                'average' => 3.0,
                'poor' => 0.5,
            ],
        ],
        'facebook' => [
            'engagement_rate' => [
                'excellent' => 3.0,
                'good' => 1.5,
                'average' => 0.5,
                'poor' => 0.1,
            ],
        ],
    ];

    /**
     * Calculate engagement metrics
     */
    public function calculate(Business $business): array
    {
        $channelScores = [];
        $channelMetrics = [];

        // Instagram metrics
        $instagram = $this->calculateInstagramMetrics($business);
        if ($instagram['connected']) {
            $channelScores['instagram'] = $instagram;
            $channelMetrics['instagram'] = $instagram;
        }

        // Telegram metrics
        $telegram = $this->calculateTelegramMetrics($business);
        if ($telegram['connected']) {
            $channelScores['telegram'] = $telegram;
            $channelMetrics['telegram'] = $telegram;
        }

        // Facebook metrics
        $facebook = $this->calculateFacebookMetrics($business);
        if ($facebook['connected']) {
            $channelScores['facebook'] = $facebook;
            $channelMetrics['facebook'] = $facebook;
        }

        // Calculate overall score
        $overallScore = $this->calculateOverallScore($channelScores);

        // Get best performing channel
        $bestChannel = $this->getBestChannel($channelScores);

        // Get worst performing channel
        $worstChannel = $this->getWorstChannel($channelScores);

        // Content type analysis (if Instagram connected)
        $contentAnalysis = $instagram['connected']
            ? $this->analyzeContentTypes($business)
            : [];

        // Best posting times
        $bestTimes = $instagram['connected']
            ? $this->analyzeBestPostingTimes($business)
            : [];

        return [
            'score' => $overallScore,
            'connected_channels' => count($channelScores),
            'channel_scores' => $channelScores,
            'channel_metrics' => $channelMetrics,
            'best_channel' => $bestChannel,
            'worst_channel' => $worstChannel,
            'content_analysis' => $contentAnalysis,
            'best_posting_times' => $bestTimes,
            'recommendations' => $this->generateRecommendations($channelScores),
            'growth_trends' => $this->analyzeGrowthTrends($business, $channelScores),
        ];
    }

    /**
     * Calculate Instagram engagement rate (public method)
     */
    public function calculateInstagramER(InstagramAccount $account): float
    {
        $followers = $account->followers_count ?? 0;
        if ($followers === 0) return 0;

        // Get average engagement from recent posts
        $avgLikes = $account->metrics['avg_likes'] ?? 0;
        $avgComments = $account->metrics['avg_comments'] ?? 0;
        $avgSaves = $account->metrics['avg_saves'] ?? 0;
        $avgShares = $account->metrics['avg_shares'] ?? 0;

        $totalEngagement = $avgLikes + $avgComments + $avgSaves + $avgShares;

        return round(($totalEngagement / $followers) * 100, 2);
    }

    /**
     * Calculate Instagram metrics
     */
    protected function calculateInstagramMetrics(Business $business): array
    {
        $account = $business->instagramAccounts()->first();

        if (!$account) {
            return [
                'connected' => false,
                'score' => 0,
                'status' => 'not_connected',
                'message' => 'Instagram ulanmagan',
            ];
        }

        $followers = $account->followers_count ?? 0;
        $following = $account->follows_count ?? 0;
        $mediaCount = $account->media_count ?? 0;

        // Calculate engagement rate
        $engagementRate = $this->calculateInstagramER($account);

        // Calculate follower/following ratio
        $ffRatio = $following > 0 ? round($followers / $following, 2) : $followers;

        // Get engagement score
        $erScore = $this->getMetricScore($engagementRate, $this->benchmarks['instagram']['engagement_rate']);

        // Calculate reach rate (estimate based on followers and engagement)
        $reachRate = $engagementRate * 5; // Rough estimate
        $reachScore = $this->getMetricScore($reachRate, $this->benchmarks['instagram']['reach_rate']);

        // Growth rate (would need historical data)
        $growthRate = $this->estimateGrowthRate($account);
        $growthScore = $this->getMetricScore($growthRate, $this->benchmarks['instagram']['follower_growth']);

        // Overall Instagram score
        $score = (int) round(($erScore * 0.5) + ($growthScore * 0.3) + ($reachScore * 0.2));

        return [
            'connected' => true,
            'score' => $score,
            'status' => $this->getStatus($score),
            'metrics' => [
                'followers' => $followers,
                'following' => $following,
                'media_count' => $mediaCount,
                'engagement_rate' => $engagementRate,
                'engagement_rate_status' => $this->getERStatus($engagementRate, 'instagram'),
                'ff_ratio' => $ffRatio,
                'reach_rate' => round($reachRate, 1),
                'growth_rate' => round($growthRate, 1),
            ],
            'scores' => [
                'engagement' => $erScore,
                'growth' => $growthScore,
                'reach' => $reachScore,
            ],
            'recommendations' => $this->getInstagramRecommendations($engagementRate, $growthRate, $mediaCount),
        ];
    }

    /**
     * Calculate Telegram metrics
     */
    protected function calculateTelegramMetrics(Business $business): array
    {
        $integration = $business->integrations()
            ->whereIn('type', ['telegram', 'telegram_channel', 'telegram_bot'])
            ->where('status', 'connected')
            ->first();

        if (!$integration) {
            return [
                'connected' => false,
                'score' => 0,
                'status' => 'not_connected',
                'message' => 'Telegram ulanmagan',
            ];
        }

        $metadata = $integration->metadata ?? [];
        $subscribers = $metadata['subscribers'] ?? 0;
        $type = $metadata['type'] ?? 'bot';

        // For bots, calculate based on active users
        if ($type === 'bot') {
            $activeUsers = $metadata['active_users'] ?? 0;
            $messagesPerDay = $metadata['messages_per_day'] ?? 0;

            $engagementRate = $subscribers > 0
                ? round(($activeUsers / $subscribers) * 100, 1)
                : 0;

            $score = $this->getMetricScore($engagementRate, $this->benchmarks['telegram']['engagement_rate']);

            return [
                'connected' => true,
                'score' => $score,
                'status' => $this->getStatus($score),
                'type' => 'bot',
                'metrics' => [
                    'total_users' => $subscribers,
                    'active_users' => $activeUsers,
                    'engagement_rate' => $engagementRate,
                    'messages_per_day' => $messagesPerDay,
                ],
                'recommendations' => $this->getTelegramBotRecommendations($engagementRate, $activeUsers),
            ];
        }

        // For channels
        $viewsPerPost = $metadata['avg_views'] ?? 0;
        $engagementRate = $subscribers > 0
            ? round(($viewsPerPost / $subscribers) * 100, 1)
            : 0;

        $score = $this->getMetricScore($engagementRate, $this->benchmarks['telegram']['engagement_rate']);

        return [
            'connected' => true,
            'score' => $score,
            'status' => $this->getStatus($score),
            'type' => 'channel',
            'metrics' => [
                'subscribers' => $subscribers,
                'avg_views' => $viewsPerPost,
                'engagement_rate' => $engagementRate,
            ],
            'recommendations' => $this->getTelegramChannelRecommendations($engagementRate, $subscribers),
        ];
    }

    /**
     * Calculate Facebook metrics
     */
    protected function calculateFacebookMetrics(Business $business): array
    {
        $integration = $business->integrations()
            ->where('type', 'facebook')
            ->where('status', 'connected')
            ->first();

        if (!$integration) {
            return [
                'connected' => false,
                'score' => 0,
                'status' => 'not_connected',
                'message' => 'Facebook ulanmagan',
            ];
        }

        $metadata = $integration->metadata ?? [];
        $followers = $metadata['followers'] ?? 0;
        $engagementRate = $metadata['engagement_rate'] ?? 0;

        $score = $this->getMetricScore($engagementRate, $this->benchmarks['facebook']['engagement_rate']);

        return [
            'connected' => true,
            'score' => $score,
            'status' => $this->getStatus($score),
            'metrics' => [
                'followers' => $followers,
                'engagement_rate' => $engagementRate,
            ],
            'recommendations' => $this->getFacebookRecommendations($engagementRate, $followers),
        ];
    }

    /**
     * Get metric score based on thresholds
     */
    protected function getMetricScore(float $value, array $thresholds): int
    {
        if ($value >= $thresholds['excellent']) return 90;
        if ($value >= $thresholds['good']) return 70;
        if ($value >= $thresholds['average']) return 50;
        if ($value >= $thresholds['poor']) return 30;
        return 10;
    }

    /**
     * Get engagement rate status
     */
    protected function getERStatus(float $rate, string $platform): string
    {
        $thresholds = $this->benchmarks[$platform]['engagement_rate'] ?? [
            'excellent' => 5, 'good' => 3, 'average' => 1.5, 'poor' => 0.5
        ];

        if ($rate >= $thresholds['excellent']) return 'excellent';
        if ($rate >= $thresholds['good']) return 'good';
        if ($rate >= $thresholds['average']) return 'average';
        return 'poor';
    }

    /**
     * Get status from score
     */
    protected function getStatus(int $score): string
    {
        if ($score >= 80) return 'excellent';
        if ($score >= 60) return 'good';
        if ($score >= 40) return 'average';
        return 'poor';
    }

    /**
     * Estimate growth rate
     */
    protected function estimateGrowthRate(InstagramAccount $account): float
    {
        // Would need historical data for accurate calculation
        // For now, estimate based on account age and followers
        $followers = $account->followers_count ?? 0;
        $mediaCount = $account->media_count ?? 0;

        if ($mediaCount === 0) return 0;

        // Rough estimate: followers / posts ratio indicates organic growth
        $ratio = $followers / $mediaCount;

        if ($ratio > 100) return 8; // High growth
        if ($ratio > 50) return 5;  // Good growth
        if ($ratio > 20) return 2;  // Average growth
        return 0.5; // Low growth
    }

    /**
     * Calculate overall score
     */
    protected function calculateOverallScore(array $channelScores): int
    {
        if (empty($channelScores)) return 0;

        $totalScore = 0;
        $count = 0;

        foreach ($channelScores as $channel) {
            if ($channel['connected'] ?? false) {
                $totalScore += $channel['score'];
                $count++;
            }
        }

        return $count > 0 ? (int) round($totalScore / $count) : 0;
    }

    /**
     * Get best performing channel
     */
    protected function getBestChannel(array $channelScores): ?array
    {
        $best = null;
        $highestScore = -1;

        foreach ($channelScores as $name => $channel) {
            if (($channel['connected'] ?? false) && $channel['score'] > $highestScore) {
                $highestScore = $channel['score'];
                $best = [
                    'name' => $name,
                    'score' => $channel['score'],
                    'status' => $channel['status'],
                ];
            }
        }

        return $best;
    }

    /**
     * Get worst performing channel
     */
    protected function getWorstChannel(array $channelScores): ?array
    {
        $worst = null;
        $lowestScore = 101;

        foreach ($channelScores as $name => $channel) {
            if (($channel['connected'] ?? false) && $channel['score'] < $lowestScore) {
                $lowestScore = $channel['score'];
                $worst = [
                    'name' => $name,
                    'score' => $channel['score'],
                    'status' => $channel['status'],
                ];
            }
        }

        return $worst;
    }

    /**
     * Analyze content types performance
     */
    protected function analyzeContentTypes(Business $business): array
    {
        $account = $business->instagramAccounts()->first();
        if (!$account) return [];

        // Get media by type
        try {
            $mediaStats = DB::table('instagram_media')
                ->where('instagram_account_id', $account->id)
                ->selectRaw('media_type, COUNT(*) as count, AVG(engagement_rate) as avg_er')
                ->groupBy('media_type')
                ->get();

            $analysis = [];
            foreach ($mediaStats as $stat) {
                $analysis[$stat->media_type] = [
                    'count' => $stat->count,
                    'avg_engagement_rate' => round($stat->avg_er ?? 0, 2),
                    'recommendation' => $this->getContentTypeRecommendation($stat->media_type, $stat->avg_er ?? 0),
                ];
            }

            return $analysis;
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * Analyze best posting times
     */
    protected function analyzeBestPostingTimes(Business $business): array
    {
        $account = $business->instagramAccounts()->first();
        if (!$account) return [];

        try {
            // Get best hours
            $hourStats = DB::table('instagram_media')
                ->where('instagram_account_id', $account->id)
                ->whereNotNull('timestamp')
                ->selectRaw('HOUR(timestamp) as hour, AVG(engagement_rate) as avg_er')
                ->groupBy('hour')
                ->orderByDesc('avg_er')
                ->limit(3)
                ->get();

            // Get best days
            $dayStats = DB::table('instagram_media')
                ->where('instagram_account_id', $account->id)
                ->whereNotNull('timestamp')
                ->selectRaw('DAYOFWEEK(timestamp) as day, AVG(engagement_rate) as avg_er')
                ->groupBy('day')
                ->orderByDesc('avg_er')
                ->limit(3)
                ->get();

            $dayNames = ['', 'Yakshanba', 'Dushanba', 'Seshanba', 'Chorshanba', 'Payshanba', 'Juma', 'Shanba'];

            return [
                'best_hours' => $hourStats->map(fn($h) => [
                    'hour' => sprintf('%02d:00', $h->hour),
                    'avg_engagement' => round($h->avg_er, 2),
                ])->toArray(),
                'best_days' => $dayStats->map(fn($d) => [
                    'day' => $dayNames[$d->day] ?? 'Noma\'lum',
                    'avg_engagement' => round($d->avg_er, 2),
                ])->toArray(),
                'optimal_slots' => $this->getOptimalSlots($hourStats, $dayStats, $dayNames),
            ];
        } catch (\Exception $e) {
            // Default recommendations
            return [
                'best_hours' => [
                    ['hour' => '09:00', 'avg_engagement' => 3.5],
                    ['hour' => '12:00', 'avg_engagement' => 3.2],
                    ['hour' => '19:00', 'avg_engagement' => 4.0],
                ],
                'best_days' => [
                    ['day' => 'Seshanba', 'avg_engagement' => 3.8],
                    ['day' => 'Chorshanba', 'avg_engagement' => 3.5],
                    ['day' => 'Juma', 'avg_engagement' => 3.3],
                ],
                'optimal_slots' => [
                    '19:00 Seshanba',
                    '12:00 Chorshanba',
                    '09:00 Juma',
                ],
            ];
        }
    }

    /**
     * Get optimal posting slots
     */
    protected function getOptimalSlots($hourStats, $dayStats, array $dayNames): array
    {
        $slots = [];

        foreach ($hourStats as $hour) {
            foreach ($dayStats as $day) {
                $slots[] = sprintf('%02d:00 %s', $hour->hour, $dayNames[$day->day] ?? '');
            }
        }

        return array_slice($slots, 0, 5);
    }

    /**
     * Analyze growth trends
     */
    protected function analyzeGrowthTrends(Business $business, array $channelScores): array
    {
        $trends = [];

        foreach ($channelScores as $channel => $data) {
            if (!($data['connected'] ?? false)) continue;

            $metrics = $data['metrics'] ?? [];

            $trends[$channel] = [
                'current_score' => $data['score'],
                'trend' => $this->determineTrend($metrics),
                'growth_potential' => 100 - $data['score'],
                'focus_areas' => $this->getFocusAreas($channel, $data),
            ];
        }

        return $trends;
    }

    /**
     * Determine trend direction
     */
    protected function determineTrend(array $metrics): string
    {
        $growthRate = $metrics['growth_rate'] ?? $metrics['engagement_rate'] ?? 0;

        if ($growthRate >= 5) return 'up';
        if ($growthRate >= 2) return 'stable';
        return 'down';
    }

    /**
     * Get focus areas for improvement
     */
    protected function getFocusAreas(string $channel, array $data): array
    {
        $areas = [];
        $scores = $data['scores'] ?? [];

        foreach ($scores as $metric => $score) {
            if ($score < 60) {
                $areas[] = [
                    'metric' => $metric,
                    'score' => $score,
                    'priority' => $score < 40 ? 'high' : 'medium',
                ];
            }
        }

        return $areas;
    }

    /**
     * Generate recommendations
     */
    protected function generateRecommendations(array $channelScores): array
    {
        $recommendations = [];

        foreach ($channelScores as $channel => $data) {
            if (!($data['connected'] ?? false)) {
                $recommendations[] = [
                    'channel' => $channel,
                    'priority' => 'medium',
                    'title' => ucfirst($channel) . ' ni ulang',
                    'description' => 'Ko\'proq auditoriyaga yetish uchun ' . $channel . ' ni ulang',
                ];
                continue;
            }

            $channelRecs = $data['recommendations'] ?? [];
            foreach ($channelRecs as $rec) {
                $rec['channel'] = $channel;
                $recommendations[] = $rec;
            }
        }

        // Sort by priority
        usort($recommendations, function ($a, $b) {
            $order = ['high' => 0, 'medium' => 1, 'low' => 2];
            return ($order[$a['priority']] ?? 2) <=> ($order[$b['priority']] ?? 2);
        });

        return array_slice($recommendations, 0, 5);
    }

    /**
     * Get Instagram recommendations
     */
    protected function getInstagramRecommendations(float $er, float $growth, int $posts): array
    {
        $recs = [];

        if ($er < 1.5) {
            $recs[] = [
                'priority' => 'high',
                'title' => 'Engagement oshiring',
                'description' => 'Reels va carousel postlar engagement ni 2-3x oshiradi',
            ];
        }

        if ($growth < 2) {
            $recs[] = [
                'priority' => 'medium',
                'title' => 'O\'sish tezligini oshiring',
                'description' => 'Hashtag strategiyasi va kollaboratsiyalar yordamida',
            ];
        }

        if ($posts < 30) {
            $recs[] = [
                'priority' => 'medium',
                'title' => 'Post chastotasini oshiring',
                'description' => 'Haftada kamida 3-5 ta post joylang',
            ];
        }

        return $recs;
    }

    /**
     * Get Telegram bot recommendations
     */
    protected function getTelegramBotRecommendations(float $er, int $active): array
    {
        $recs = [];

        if ($er < 30) {
            $recs[] = [
                'priority' => 'high',
                'title' => 'Foydalanuvchilarni faollashtiring',
                'description' => 'Push notification va maxsus takliflar yuboring',
            ];
        }

        if ($active < 100) {
            $recs[] = [
                'priority' => 'medium',
                'title' => 'Foydalanuvchilar bazasini kengaytiring',
                'description' => 'Bot haqida boshqa kanallarda reklama qiling',
            ];
        }

        return $recs;
    }

    /**
     * Get Telegram channel recommendations
     */
    protected function getTelegramChannelRecommendations(float $er, int $subs): array
    {
        $recs = [];

        if ($er < 30) {
            $recs[] = [
                'priority' => 'high',
                'title' => 'Kontent sifatini oshiring',
                'description' => 'Qimmatli va foydali kontentlar joylang',
            ];
        }

        if ($subs < 1000) {
            $recs[] = [
                'priority' => 'medium',
                'title' => 'Obunachilar sonini oshiring',
                'description' => 'Cross-promo va viral kontentlar yarating',
            ];
        }

        return $recs;
    }

    /**
     * Get Facebook recommendations
     */
    protected function getFacebookRecommendations(float $er, int $followers): array
    {
        $recs = [];

        if ($er < 1) {
            $recs[] = [
                'priority' => 'high',
                'title' => 'Facebook engagement oshiring',
                'description' => 'Video kontentlar va live streamlar engagement ni oshiradi',
            ];
        }

        return $recs;
    }

    /**
     * Get content type recommendation
     */
    protected function getContentTypeRecommendation(string $type, float $avgER): string
    {
        if ($avgER >= 4) {
            return 'Ajoyib natija! Bu turdagi kontentlarni ko\'proq joylang';
        }

        $tips = [
            'IMAGE' => 'Rasm sifatini yaxshilang, carousel formatdan foydalaning',
            'VIDEO' => 'Videolarni qisqartiring (15-30 sekund), subtitrl qo\'shing',
            'CAROUSEL_ALBUM' => 'Birinchi slaydni jozibador qiling, hikoya telling qo\'llang',
            'REELS' => 'Trending audio ishlating, hook ni 1-sekundda bering',
        ];

        return $tips[$type] ?? 'Kontent sifatini yaxshilang';
    }
}
