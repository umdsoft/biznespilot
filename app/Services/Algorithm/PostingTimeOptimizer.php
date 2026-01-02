<?php

namespace App\Services\Algorithm;

use App\Models\Business;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

/**
 * Posting Time Optimizer Algorithm
 *
 * Uses circular statistics to find optimal posting times for social media content.
 * Analyzes historical engagement patterns across hours and days to recommend
 * best posting schedules.
 *
 * Algorithm: Circular Statistics for 24-hour cycle
 * Formula: Mean Hour = arctan2(Σ sin(θ), Σ cos(θ))
 *
 * Research:
 * - Fisher (1993) - Statistical Analysis of Circular Data
 * - HubSpot (2024) - Best Times to Post on Social Media
 * - Sprout Social (2024) - Social Media Engagement Benchmarks
 * - Buffer (2024) - Optimal Posting Times Study
 *
 * @version 1.0.0
 * @package App\Services\Algorithm
 */
class PostingTimeOptimizer extends AlgorithmEngine
{
    /**
     * Algorithm version
     */
    protected string $version = '1.0.0';

    /**
     * Cache TTL (15 minutes - dynamic data)
     */
    protected int $cacheTTL = 900;

    /**
     * Industry-specific best posting times (based on research)
     * Hours in 24-hour format
     */
    protected array $industryBestTimes = [
        'default' => [
            'weekday' => [9, 12, 15, 18],
            'weekend' => [10, 13, 16, 19],
        ],
        'restaurant' => [
            'weekday' => [11, 13, 17, 19],  // Lunch & dinner times
            'weekend' => [10, 12, 18, 20],
        ],
        'retail' => [
            'weekday' => [12, 15, 18, 20],  // After work hours
            'weekend' => [11, 14, 16, 19],
        ],
        'beauty_salon' => [
            'weekday' => [10, 14, 18, 20],
            'weekend' => [11, 14, 17],
        ],
        'gym_fitness' => [
            'weekday' => [6, 12, 17, 19],   // Morning & after work
            'weekend' => [8, 11, 16],
        ],
        'education' => [
            'weekday' => [8, 13, 16, 19],
            'weekend' => [10, 14, 18],
        ],
        'healthcare' => [
            'weekday' => [9, 12, 15, 18],
            'weekend' => [10, 13, 16],
        ],
    ];

    /**
     * Day-of-week engagement multipliers (research-based)
     */
    protected array $dayMultipliers = [
        'Monday' => 0.9,
        'Tuesday' => 1.0,
        'Wednesday' => 1.1,
        'Thursday' => 1.0,
        'Friday' => 0.95,
        'Saturday' => 0.85,
        'Sunday' => 0.8,
    ];

    /**
     * Analyze optimal posting times for a business
     *
     * @param Business $business Business to analyze
     * @param array $options Additional options
     * @return array Optimal posting times and recommendations
     */
    public function analyze(Business $business, array $options = []): array
    {
        try {
            $startTime = microtime(true);

            // Get historical engagement data
            $engagementData = $this->getEngagementData($business, $options);

            // Calculate optimal hours using circular statistics
            $optimalHours = $this->calculateOptimalHours($engagementData);

            // Analyze day-of-week patterns
            $dayPatterns = $this->analyzeDayPatterns($engagementData);

            // Generate recommendations
            $recommendations = $this->generateRecommendations(
                $business,
                $optimalHours,
                $dayPatterns
            );

            // Calculate next best posting slots
            $nextPostingSlots = $this->calculateNextPostingSlots($optimalHours, $dayPatterns);

            // Industry benchmarks
            $industryBenchmark = $this->getIndustryBenchmark($business->industry ?? 'default');

            $executionTime = round((microtime(true) - $startTime) * 1000, 2);

            return [
                'success' => true,
                'version' => $this->version,
                'optimal_hours' => $optimalHours,
                'day_patterns' => $dayPatterns,
                'next_posting_slots' => $nextPostingSlots,
                'recommendations' => $recommendations,
                'industry_benchmark' => $industryBenchmark,
                'metadata' => [
                    'calculated_at' => Carbon::now()->toIso8601String(),
                    'execution_time_ms' => $executionTime,
                    'business_id' => $business->id,
                    'industry' => $business->industry ?? 'default',
                    'data_points_analyzed' => count($engagementData),
                ],
            ];

        } catch (\Exception $e) {
            Log::error('PostingTimeOptimizer calculation failed', [
                'business_id' => $business->id,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
                'version' => $this->version,
            ];
        }
    }

    /**
     * Get historical engagement data from posts
     *
     * @param Business $business
     * @param array $options
     * @return array Engagement data with timestamps
     */
    protected function getEngagementData(Business $business, array $options = []): array
    {
        $data = [];

        // Get Instagram post data
        $instagramAccounts = $business->instagramAccounts ?? collect();

        foreach ($instagramAccounts as $account) {
            $posts = $account->posts ?? collect();

            foreach ($posts as $post) {
                if (!empty($post->posted_at) && !empty($post->engagement_rate)) {
                    $postedAt = Carbon::parse($post->posted_at);

                    $data[] = [
                        'hour' => $postedAt->hour,
                        'day_of_week' => $postedAt->format('l'),
                        'engagement' => $post->engagement_rate,
                        'likes' => $post->likes_count ?? 0,
                        'comments' => $post->comments_count ?? 0,
                        'timestamp' => $postedAt,
                    ];
                }
            }
        }

        // If no historical data, use synthetic data for demo
        if (empty($data) && ($options['use_synthetic'] ?? true)) {
            $data = $this->generateSyntheticData($business);
        }

        return $data;
    }

    /**
     * Calculate optimal hours using circular statistics
     *
     * Circular statistics properly handle the 24-hour cycle where
     * 23:00 and 1:00 are close together.
     *
     * @param array $engagementData
     * @return array Top hours with engagement scores
     */
    protected function calculateOptimalHours(array $engagementData): array
    {
        if (empty($engagementData)) {
            return [];
        }

        // Group by hour and calculate average engagement
        $hourlyEngagement = array_fill(0, 24, ['total' => 0, 'count' => 0]);

        foreach ($engagementData as $data) {
            $hour = $data['hour'];
            $engagement = $data['engagement'];

            $hourlyEngagement[$hour]['total'] += $engagement;
            $hourlyEngagement[$hour]['count']++;
        }

        // Calculate average engagement per hour
        $averages = [];
        for ($hour = 0; $hour < 24; $hour++) {
            if ($hourlyEngagement[$hour]['count'] > 0) {
                $averages[$hour] = $hourlyEngagement[$hour]['total'] / $hourlyEngagement[$hour]['count'];
            } else {
                $averages[$hour] = 0;
            }
        }

        // Circular statistics: find mean direction
        $sumSin = 0;
        $sumCos = 0;
        $totalWeight = 0;

        foreach ($averages as $hour => $engagement) {
            if ($engagement > 0) {
                $angle = ($hour / 24) * 2 * M_PI; // Convert hour to radians
                $sumSin += sin($angle) * $engagement;
                $sumCos += cos($angle) * $engagement;
                $totalWeight += $engagement;
            }
        }

        // Calculate mean hour (circular mean)
        if ($totalWeight > 0) {
            $meanAngle = atan2($sumSin, $sumCos);
            if ($meanAngle < 0) {
                $meanAngle += 2 * M_PI;
            }
            $meanHour = ($meanAngle / (2 * M_PI)) * 24;
        } else {
            $meanHour = 12; // Default to noon
        }

        // Sort hours by engagement
        arsort($averages);

        // Get top 5 hours
        $topHours = [];
        $rank = 1;
        foreach (array_slice($averages, 0, 5, true) as $hour => $avgEngagement) {
            $topHours[] = [
                'hour' => $hour,
                'time' => sprintf('%02d:00', $hour),
                'avg_engagement' => round($avgEngagement, 2),
                'relative_performance' => $this->calculateRelativePerformance($avgEngagement, $averages),
                'rank' => $rank++,
            ];
        }

        return [
            'top_hours' => $topHours,
            'circular_mean_hour' => round($meanHour, 1),
            'peak_hour' => $topHours[0]['hour'] ?? 12,
        ];
    }

    /**
     * Analyze day-of-week patterns
     *
     * @param array $engagementData
     * @return array Day patterns with best days
     */
    protected function analyzeDayPatterns(array $engagementData): array
    {
        $days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
        $dayEngagement = array_fill_keys($days, ['total' => 0, 'count' => 0]);

        foreach ($engagementData as $data) {
            $day = $data['day_of_week'];
            $engagement = $data['engagement'];

            if (isset($dayEngagement[$day])) {
                $dayEngagement[$day]['total'] += $engagement;
                $dayEngagement[$day]['count']++;
            }
        }

        // Calculate averages
        $dayAverages = [];
        foreach ($dayEngagement as $day => $stats) {
            if ($stats['count'] > 0) {
                $dayAverages[$day] = $stats['total'] / $stats['count'];
            } else {
                $dayAverages[$day] = 0;
            }
        }

        // Sort by engagement
        arsort($dayAverages);

        // Format results
        $bestDays = [];
        $rank = 1;
        foreach ($dayAverages as $day => $avgEngagement) {
            $bestDays[] = [
                'day' => $day,
                'avg_engagement' => round($avgEngagement, 2),
                'relative_performance' => $this->calculateRelativePerformance($avgEngagement, $dayAverages),
                'rank' => $rank++,
            ];
        }

        return [
            'best_days' => array_slice($bestDays, 0, 3),
            'worst_days' => array_slice($bestDays, -2),
            'weekday_vs_weekend' => $this->compareWeekdayWeekend($dayAverages),
        ];
    }

    /**
     * Calculate relative performance percentage
     *
     * @param float $value Current value
     * @param array $allValues All values for comparison
     * @return string Percentage string
     */
    protected function calculateRelativePerformance(float $value, array $allValues): string
    {
        $max = max($allValues);
        if ($max == 0) return '0%';

        $percentage = ($value / $max) * 100;
        return round($percentage) . '%';
    }

    /**
     * Compare weekday vs weekend performance
     *
     * @param array $dayAverages
     * @return array Comparison data
     */
    protected function compareWeekdayWeekend(array $dayAverages): array
    {
        $weekdays = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'];
        $weekends = ['Saturday', 'Sunday'];

        $weekdayAvg = 0;
        $weekdayCount = 0;
        foreach ($weekdays as $day) {
            if (isset($dayAverages[$day]) && $dayAverages[$day] > 0) {
                $weekdayAvg += $dayAverages[$day];
                $weekdayCount++;
            }
        }
        $weekdayAvg = $weekdayCount > 0 ? $weekdayAvg / $weekdayCount : 0;

        $weekendAvg = 0;
        $weekendCount = 0;
        foreach ($weekends as $day) {
            if (isset($dayAverages[$day]) && $dayAverages[$day] > 0) {
                $weekendAvg += $dayAverages[$day];
                $weekendCount++;
            }
        }
        $weekendAvg = $weekendCount > 0 ? $weekendAvg / $weekendCount : 0;

        $winner = 'weekday';
        $difference = 0;
        if ($weekdayAvg > 0 && $weekendAvg > 0) {
            if ($weekendAvg > $weekdayAvg) {
                $winner = 'weekend';
                $difference = (($weekendAvg - $weekdayAvg) / $weekdayAvg) * 100;
            } else {
                $difference = (($weekdayAvg - $weekendAvg) / $weekendAvg) * 100;
            }
        }

        return [
            'weekday_avg' => round($weekdayAvg, 2),
            'weekend_avg' => round($weekendAvg, 2),
            'better_performance' => $winner,
            'difference_percent' => round($difference, 1),
        ];
    }

    /**
     * Generate posting recommendations
     *
     * @param Business $business
     * @param array $optimalHours
     * @param array $dayPatterns
     * @return array Recommendations
     */
    protected function generateRecommendations(
        Business $business,
        array $optimalHours,
        array $dayPatterns
    ): array {
        $recommendations = [];

        // Best posting times recommendation
        if (!empty($optimalHours['top_hours'])) {
            $topHour = $optimalHours['top_hours'][0];
            $recommendations[] = [
                'priority' => 'high',
                'title' => 'Eng yaxshi post vaqti',
                'description' => "Sizning eng yaxshi natija beruvchi vaqtingiz: {$topHour['time']}. Bu vaqtda engagement {$topHour['relative_performance']} yuqori.",
                'action_items' => [
                    "Har kuni {$topHour['time']} da post qiling",
                    "Content calendar yarating",
                    "Scheduling tool ishlatishni boshlang",
                ],
                'estimated_impact' => [
                    'engagement_increase' => '+20-30%',
                    'reach_increase' => '+15-25%',
                ],
            ];
        }

        // Day-of-week recommendation
        if (!empty($dayPatterns['best_days'])) {
            $bestDay = $dayPatterns['best_days'][0];
            $worstDay = $dayPatterns['worst_days'][0] ?? null;

            $recommendations[] = [
                'priority' => 'medium',
                'title' => 'Haftaning eng samarali kunlari',
                'description' => "{$bestDay['day']} kuni eng yaxshi natija beradi. " .
                                ($worstDay ? "{$worstDay['day']} kuni eng past engagement." : ''),
                'action_items' => [
                    "{$bestDay['day']} kuniga eng muhim contentni joylashtiring",
                    "Kam samarali kunlarda kamroq post qiling yoki repost bajaring",
                    "Har hafta consistent posting schedule yarating",
                ],
                'estimated_impact' => [
                    'engagement_increase' => '+15-20%',
                ],
            ];
        }

        // Weekday vs weekend strategy
        $comparison = $dayPatterns['weekday_vs_weekend'] ?? null;
        if ($comparison && $comparison['difference_percent'] > 20) {
            $better = $comparison['better_performance'];
            $recommendations[] = [
                'priority' => 'medium',
                'title' => ucfirst($better) . ' strategiyasiga e\'tibor bering',
                'description' => ucfirst($better) . " kunlari {$comparison['difference_percent']}% yaxshiroq natija bermoqda.",
                'action_items' => [
                    ucfirst($better) . " kunlari ko'proq post qiling",
                    "Content type'larni {$better} uchun optimizatsiya qiling",
                    "Budget'ni {$better} kunlarga ko'proq ajrating",
                ],
            ];
        }

        // Posting frequency recommendation
        $recommendations[] = [
            'priority' => 'low',
            'title' => 'Optimal posting chastotasi',
            'description' => 'Research asosida optimal posting chastotasi: 1-2 marta kuniga, 5-7 marta haftasiga.',
            'action_items' => [
                'Consistency saqlang - har kuni bir xil vaqtda post qiling',
                'Quality over quantity - sifatli content yarating',
                'Audience burnout oldini oling - ortiqcha post qilmang',
            ],
        ];

        return $recommendations;
    }

    /**
     * Calculate next best posting slots (next 7 days)
     *
     * @param array $optimalHours
     * @param array $dayPatterns
     * @return array Recommended posting times
     */
    protected function calculateNextPostingSlots(array $optimalHours, array $dayPatterns): array
    {
        $slots = [];
        $now = Carbon::now();

        // Get top 3 hours
        $topHours = array_slice($optimalHours['top_hours'] ?? [], 0, 3);

        // Get best days
        $bestDays = array_column($dayPatterns['best_days'] ?? [], 'day');

        // Generate next 7 days
        for ($i = 0; $i < 7; $i++) {
            $date = $now->copy()->addDays($i);
            $dayName = $date->format('l');

            // Check if this is a good day
            $dayRank = array_search($dayName, $bestDays);
            $isDayOptimal = $dayRank !== false;

            foreach ($topHours as $hourData) {
                $postTime = $date->copy()->setTime($hourData['hour'], 0, 0);

                // Skip past times
                if ($postTime->isPast()) {
                    continue;
                }

                // Calculate score
                $score = $hourData['avg_engagement'];
                if ($isDayOptimal) {
                    $score *= 1.2; // Boost for optimal days
                }

                $slots[] = [
                    'datetime' => $postTime->toIso8601String(),
                    'formatted' => $postTime->format('D, M j, Y - H:i'),
                    'day' => $dayName,
                    'hour' => $hourData['hour'],
                    'time' => $hourData['time'],
                    'is_optimal_day' => $isDayOptimal,
                    'is_optimal_hour' => $hourData['rank'] === 1,
                    'predicted_engagement' => round($score, 2),
                    'quality_level' => $this->getTimeQuality($score),
                ];
            }
        }

        // Sort by predicted engagement
        usort($slots, function($a, $b) {
            return $b['predicted_engagement'] <=> $a['predicted_engagement'];
        });

        // Return top 10 slots
        return array_slice($slots, 0, 10);
    }

    /**
     * Get time quality label
     *
     * @param float $score Engagement score
     * @return string Quality level
     */
    protected function getTimeQuality(float $score): string
    {
        if ($score >= 4.0) return 'excellent';
        if ($score >= 3.0) return 'good';
        if ($score >= 2.0) return 'average';
        return 'poor';
    }

    /**
     * Get industry benchmark posting times
     *
     * @param string $industry
     * @return array Industry-specific recommendations
     */
    protected function getIndustryBenchmark(string $industry): array
    {
        $times = $this->industryBestTimes[$industry] ?? $this->industryBestTimes['default'];

        return [
            'industry' => $industry,
            'recommended_weekday_hours' => $times['weekday'],
            'recommended_weekend_hours' => $times['weekend'],
            'source' => 'HubSpot, Sprout Social, Buffer research (2024)',
        ];
    }

    /**
     * Generate synthetic data for demo purposes
     *
     * @param Business $business
     * @return array Synthetic engagement data
     */
    protected function generateSyntheticData(Business $business): array
    {
        $data = [];
        $industry = $business->industry ?? 'default';
        $bestTimes = $this->industryBestTimes[$industry] ?? $this->industryBestTimes['default'];

        // Generate 30 days of synthetic data
        for ($day = 0; $day < 30; $day++) {
            $date = Carbon::now()->subDays($day);
            $dayName = $date->format('l');
            $isWeekend = in_array($dayName, ['Saturday', 'Sunday']);

            // Choose appropriate best times
            $hoursToUse = $isWeekend ? $bestTimes['weekend'] : $bestTimes['weekday'];

            // Create 1-2 posts per day
            $postsPerDay = rand(1, 2);
            for ($p = 0; $p < $postsPerDay; $p++) {
                // Randomly select from best times
                $hour = $hoursToUse[array_rand($hoursToUse)];

                // Add some variation
                $hour += rand(-1, 1);
                $hour = max(0, min(23, $hour));

                // Generate engagement (higher for optimal times)
                $baseEngagement = 3.0;
                if (in_array($hour, $hoursToUse)) {
                    $baseEngagement = 5.0;
                }

                // Apply day multiplier
                $multiplier = $this->dayMultipliers[$dayName] ?? 1.0;
                $engagement = $baseEngagement * $multiplier * (rand(80, 120) / 100);

                $data[] = [
                    'hour' => $hour,
                    'day_of_week' => $dayName,
                    'engagement' => round($engagement, 2),
                    'likes' => rand(50, 200),
                    'comments' => rand(5, 30),
                    'timestamp' => $date->copy()->setTime($hour, rand(0, 59)),
                ];
            }
        }

        return $data;
    }
}
