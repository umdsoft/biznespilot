<?php

namespace App\Services;

use App\Models\InstagramAccount;
use App\Models\InstagramMedia;
use App\Models\InstagramDailyInsight;
use App\Models\InstagramAudience;
use App\Models\InstagramHashtagStat;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class InstagramDataService
{
    /**
     * Get account overview stats
     */
    public function getOverview(string $accountId, string $datePreset = 'last_30d'): array
    {
        $account = InstagramAccount::find($accountId);
        if (!$account) {
            return [];
        }

        $dates = $this->getDateRange($datePreset);
        $previousDates = $this->getPreviousDateRange($datePreset);

        $current = $this->getAggregatedInsights($accountId, $dates['start'], $dates['end']);
        $previous = $this->getAggregatedInsights($accountId, $previousDates['start'], $previousDates['end']);

        return [
            'account' => [
                'username' => $account->username,
                'name' => $account->name,
                'profile_picture_url' => $account->profile_picture_url,
                'followers_count' => $account->followers_count,
                'follows_count' => $account->follows_count,
                'media_count' => $account->media_count,
                'engagement_rate' => $account->engagement_rate,
            ],
            'current' => $current,
            'change' => $this->calculateChange($current, $previous),
        ];
    }

    /**
     * Get media performance analysis
     */
    public function getMediaPerformance(string $accountId, string $datePreset = 'last_30d'): array
    {
        // Use smart date range - falls back to all data if preset range is empty
        $dates = $this->getSmartDateRange($accountId, $datePreset);

        // All media for filtering/pagination in frontend
        $allMedia = InstagramMedia::where('account_id', $accountId)
            ->whereBetween('posted_at', [$dates['start'], $dates['end']])
            ->orderByDesc('engagement_rate')
            ->get()
            ->map(fn($m) => $this->formatMediaFull($m));

        // Top performing posts (FEED + CAROUSEL)
        $topPosts = InstagramMedia::where('account_id', $accountId)
            ->whereIn('media_product_type', ['FEED', 'CAROUSEL_ALBUM'])
            ->whereBetween('posted_at', [$dates['start'], $dates['end']])
            ->orderByDesc('engagement_rate')
            ->limit(10)
            ->get()
            ->map(fn($m) => $this->formatMediaFull($m));

        // Top performing reels
        $topReels = InstagramMedia::where('account_id', $accountId)
            ->where('media_product_type', 'REELS')
            ->whereBetween('posted_at', [$dates['start'], $dates['end']])
            ->orderByDesc('reach')
            ->limit(10)
            ->get()
            ->map(fn($m) => $this->formatMediaFull($m));

        // Media by type stats
        $mediaByType = InstagramMedia::where('account_id', $accountId)
            ->whereBetween('posted_at', [$dates['start'], $dates['end']])
            ->select(
                'media_product_type',
                DB::raw('COUNT(*) as count'),
                DB::raw('AVG(engagement_rate) as avg_engagement'),
                DB::raw('SUM(reach) as total_reach'),
                DB::raw('SUM(impressions) as total_impressions'),
                DB::raw('SUM(like_count) as total_likes'),
                DB::raw('SUM(comments_count) as total_comments')
            )
            ->groupBy('media_product_type')
            ->get()
            ->keyBy('media_product_type');

        return [
            'all_media' => $allMedia,
            'top_posts' => $topPosts,
            'top_reels' => $topReels,
            'media_by_type' => $mediaByType,
            'date_range' => [
                'start' => $dates['start']->format('Y-m-d'),
                'end' => $dates['end']->format('Y-m-d'),
            ],
        ];
    }

    /**
     * Get reels specific analytics
     */
    public function getReelsAnalytics(string $accountId, string $datePreset = 'last_30d'): array
    {
        // Use smart date range - falls back to all data if preset range is empty
        $dates = $this->getSmartDateRange($accountId, $datePreset);

        $reels = InstagramMedia::where('account_id', $accountId)
            ->where('media_product_type', 'REELS')
            ->whereBetween('posted_at', [$dates['start'], $dates['end']])
            ->orderByDesc('posted_at')
            ->get();

        // Sort by reach instead of plays (plays is deprecated in new API)
        $mostViewed = $reels->sortByDesc('reach')->take(5)->values();
        $mostEngaged = $reels->sortByDesc('engagement_rate')->take(5)->values();
        $mostCommented = $reels->sortByDesc('comments_count')->take(5)->values();

        // Average metrics
        $avgPlays = $reels->avg('plays') ?? 0;
        $avgReach = $reels->avg('reach') ?? 0;
        $avgEngagement = $reels->avg('engagement_rate') ?? 0;
        $totalReels = $reels->count();

        // Performance trend
        $weeklyPerformance = $reels->groupBy(fn($r) => $r->posted_at->startOfWeek()->format('Y-m-d'))
            ->map(fn($group) => [
                'count' => $group->count(),
                'avg_plays' => round($group->avg('plays')),
                'avg_engagement' => round($group->avg('engagement_rate'), 2),
                'total_reach' => $group->sum('reach'),
            ]);

        return [
            'summary' => [
                'total_reels' => $totalReels,
                'avg_plays' => round($avgPlays),
                'avg_reach' => round($avgReach),
                'avg_engagement_rate' => round($avgEngagement, 2),
                'total_plays' => $reels->sum('plays'),
                'total_reach' => $reels->sum('reach'),
            ],
            'most_viewed' => $mostViewed->map(fn($m) => $this->formatMediaFull($m)),
            'most_engaged' => $mostEngaged->map(fn($m) => $this->formatMediaFull($m)),
            'most_commented' => $mostCommented->map(fn($m) => $this->formatMediaFull($m)),
            'weekly_performance' => $weeklyPerformance,
            'all_reels' => $reels->map(fn($m) => $this->formatMediaFull($m)),
            'date_range' => [
                'start' => $dates['start']->format('Y-m-d'),
                'end' => $dates['end']->format('Y-m-d'),
            ],
        ];
    }

    /**
     * Get engagement analytics
     */
    public function getEngagementAnalytics(string $accountId, string $datePreset = 'last_30d'): array
    {
        // Use smart date range - falls back to all data if preset range is empty
        $dates = $this->getSmartDateRange($accountId, $datePreset);

        $media = InstagramMedia::where('account_id', $accountId)
            ->whereBetween('posted_at', [$dates['start'], $dates['end']])
            ->get();

        // Engagement by day of week
        $byDayOfWeek = $media->groupBy(fn($m) => $m->posted_at->dayOfWeek)
            ->map(fn($group) => [
                'count' => $group->count(),
                'avg_likes' => round($group->avg('like_count')),
                'avg_comments' => round($group->avg('comments_count')),
                'avg_engagement' => round($group->avg('engagement_rate'), 2),
            ]);

        // Engagement by hour
        $byHour = $media->groupBy(fn($m) => $m->posted_at->hour)
            ->map(fn($group) => [
                'count' => $group->count(),
                'avg_engagement' => round($group->avg('engagement_rate'), 2),
            ]);

        // Find best posting times
        $bestDays = collect($byDayOfWeek)->sortByDesc('avg_engagement')->take(3)->keys();
        $bestHours = collect($byHour)->sortByDesc('avg_engagement')->take(3)->keys();

        // Engagement trend over time
        $dailyEngagement = $media->groupBy(fn($m) => $m->posted_at->format('Y-m-d'))
            ->map(fn($group) => [
                'posts' => $group->count(),
                'total_likes' => $group->sum('like_count'),
                'total_comments' => $group->sum('comments_count'),
                'avg_engagement' => round($group->avg('engagement_rate'), 2),
            ]);

        return [
            'by_day_of_week' => $byDayOfWeek,
            'by_hour' => $byHour,
            'best_posting_days' => $bestDays->map(fn($d) => $this->getDayName($d))->values(),
            'best_posting_hours' => $bestHours->values(),
            'daily_trend' => $dailyEngagement,
        ];
    }

    /**
     * Get audience demographics
     */
    public function getAudienceDemographics(string $accountId): array
    {
        $audience = InstagramAudience::where('account_id', $accountId)->first();

        if (!$audience) {
            return [
                'age_gender' => [],
                'age_distribution' => [],
                'top_cities' => [],
                'top_countries' => [],
                'gender_distribution' => ['male' => 50, 'female' => 50],
                'best_posting_times' => [],
            ];
        }

        // Calculate age distribution from age_gender data
        $ageGenderData = $audience->age_gender ?? [];
        $ageDistribution = $this->calculateAgeDistribution($ageGenderData);

        // Calculate gender distribution from new API format
        $genderDistribution = $this->calculateGenderDistribution($ageGenderData);

        return [
            'age_gender' => $this->formatAgeGenderData($ageGenderData),
            'age_distribution' => $ageDistribution,
            'top_cities' => $this->formatTopLocations($audience->top_cities ?? [], 10),
            'top_countries' => $this->formatTopLocations($audience->top_countries ?? [], 10),
            'gender_distribution' => $genderDistribution,
            'dominant_age_group' => $audience->dominant_age_group,
            'best_posting_times' => $audience->best_posting_times,
            'best_posting_days' => $audience->best_posting_days,
            'online_hours' => $audience->online_hours,
            'calculated_at' => $audience->calculated_at?->format('Y-m-d H:i'),
        ];
    }

    /**
     * Calculate gender distribution from age_gender data
     */
    private function calculateGenderDistribution(array $ageGenderData): array
    {
        // New API format (has 'by_gender' key)
        if (isset($ageGenderData['by_gender'])) {
            $byGender = $ageGenderData['by_gender'];
            $total = array_sum($byGender);

            if ($total > 0) {
                return [
                    'male' => round((($byGender['M'] ?? 0) / $total) * 100, 1),
                    'female' => round((($byGender['F'] ?? 0) / $total) * 100, 1),
                    'unknown' => round((($byGender['U'] ?? 0) / $total) * 100, 1),
                ];
            }
        }

        // Old format or fallback
        $male = 0;
        $female = 0;

        foreach ($ageGenderData as $key => $value) {
            if (\is_array($value)) {
                continue;
            }
            if (str_starts_with($key, 'M.')) {
                $male += $value;
            } elseif (str_starts_with($key, 'F.')) {
                $female += $value;
            }
        }

        $total = $male + $female;
        if ($total > 0) {
            return [
                'male' => round(($male / $total) * 100, 1),
                'female' => round(($female / $total) * 100, 1),
            ];
        }

        return ['male' => 50, 'female' => 50];
    }

    /**
     * Calculate age distribution from age_gender breakdown
     * Supports both old format (M.18-24, F.25-34) and new API format (by_age, by_gender)
     */
    private function calculateAgeDistribution(array $ageGenderData): array
    {
        if (empty($ageGenderData)) {
            return [];
        }

        $ageGroups = [];

        // Check if new API format (has 'by_age' key)
        if (isset($ageGenderData['by_age'])) {
            $ageGroups = $ageGenderData['by_age'];
        } else {
            // Old format: aggregate by age range (combine M and F)
            foreach ($ageGenderData as $key => $value) {
                // Skip if value is an array (new format nested data)
                if (is_array($value)) {
                    continue;
                }

                $parts = explode('.', $key);
                $ageRange = $parts[1] ?? $key;

                if (!isset($ageGroups[$ageRange])) {
                    $ageGroups[$ageRange] = 0;
                }
                $ageGroups[$ageRange] += $value;
            }
        }

        if (empty($ageGroups)) {
            return [];
        }

        // Sort by age range
        uksort($ageGroups, function ($a, $b) {
            $aNum = (int) preg_replace('/[^0-9]/', '', explode('-', $a)[0]);
            $bNum = (int) preg_replace('/[^0-9]/', '', explode('-', $b)[0]);
            return $aNum - $bNum;
        });

        // Calculate total for percentages
        $total = array_sum($ageGroups);

        // Format for frontend
        return collect($ageGroups)->map(fn($count, $range) => [
            'range' => $range,
            'count' => $count,
            'percentage' => $total > 0 ? round(($count / $total) * 100, 1) : 0,
        ])->values()->toArray();
    }

    /**
     * Get hashtag performance
     */
    public function getHashtagPerformance(string $accountId, int $limit = 20): array
    {
        $hashtags = InstagramHashtagStat::where('account_id', $accountId)
            ->orderByDesc('avg_engagement_rate')
            ->limit($limit)
            ->get();

        $topByEngagement = $hashtags->sortByDesc('avg_engagement_rate')->take(10)->values();
        $topByReach = $hashtags->sortByDesc('total_reach')->take(10)->values();
        $mostUsed = $hashtags->sortByDesc('usage_count')->take(10)->values();

        return [
            'top_by_engagement' => $topByEngagement->map(fn($h) => [
                'hashtag' => '#' . $h->hashtag,
                'usage_count' => $h->usage_count,
                'avg_engagement_rate' => round($h->avg_engagement_rate, 2),
                'avg_reach' => $h->avg_reach_per_use,
            ]),
            'top_by_reach' => $topByReach->map(fn($h) => [
                'hashtag' => '#' . $h->hashtag,
                'usage_count' => $h->usage_count,
                'total_reach' => $h->total_reach,
                'avg_reach' => $h->avg_reach_per_use,
            ]),
            'most_used' => $mostUsed->map(fn($h) => [
                'hashtag' => '#' . $h->hashtag,
                'usage_count' => $h->usage_count,
                'avg_engagement_rate' => round($h->avg_engagement_rate, 2),
            ]),
        ];
    }

    /**
     * Get growth trend
     */
    public function getGrowthTrend(string $accountId, int $days = 30): array
    {
        $insights = InstagramDailyInsight::where('account_id', $accountId)
            ->where('insight_date', '>=', now()->subDays($days))
            ->orderBy('insight_date')
            ->get();

        return $insights->map(fn($i) => [
            'date' => $i->insight_date->format('Y-m-d'),
            'followers' => $i->follower_count,
            'impressions' => $i->impressions,
            'reach' => $i->reach,
            'profile_views' => $i->profile_views,
            'website_clicks' => $i->website_clicks,
        ])->values()->toArray();
    }

    /**
     * Get AI-ready summary for analytics
     */
    public function getAISummary(string $accountId, string $datePreset = 'last_30d'): array
    {
        $overview = $this->getOverview($accountId, $datePreset);
        $mediaPerf = $this->getMediaPerformance($accountId, $datePreset);
        $reelsAnalytics = $this->getReelsAnalytics($accountId, $datePreset);
        $engagement = $this->getEngagementAnalytics($accountId, $datePreset);
        $audience = $this->getAudienceDemographics($accountId);
        $hashtags = $this->getHashtagPerformance($accountId, 10);

        return [
            'account' => $overview['account'] ?? [],
            'metrics' => $overview['current'] ?? [],
            'changes' => $overview['change'] ?? [],
            'top_content' => [
                'best_post' => $mediaPerf['top_posts'][0] ?? null,
                'best_reel' => $mediaPerf['top_reels'][0] ?? null,
            ],
            'reels_summary' => $reelsAnalytics['summary'] ?? [],
            'best_posting' => [
                'days' => $engagement['best_posting_days'] ?? [],
                'hours' => $engagement['best_posting_hours'] ?? [],
            ],
            'audience' => [
                'dominant_age' => $audience['dominant_age_group'] ?? null,
                'gender' => $audience['gender_distribution'] ?? [],
                'top_city' => ($audience['top_cities'][0] ?? null),
            ],
            'top_hashtags' => array_slice($hashtags['top_by_engagement']->toArray(), 0, 5),
        ];
    }

    /**
     * Get content performance comparison
     */
    public function getContentComparison(string $accountId, string $datePreset = 'last_30d'): array
    {
        // Use smart date range - falls back to all data if preset range is empty
        $dates = $this->getSmartDateRange($accountId, $datePreset);

        // Map backend types to frontend display names
        $typeMapping = [
            'REELS' => 'Reels',
            'CAROUSEL_ALBUM' => 'Carousel',
            'FEED' => 'Image',
        ];

        $comparison = [];

        foreach ($typeMapping as $dbType => $displayName) {
            $query = InstagramMedia::where('account_id', $accountId)
                ->whereBetween('posted_at', [$dates['start'], $dates['end']]);

            // FEED includes single images
            if ($dbType === 'FEED') {
                $query->whereIn('media_product_type', ['FEED', 'IMAGE']);
            } else {
                $query->where('media_product_type', $dbType);
            }

            $media = $query->get();

            $comparison[$displayName] = [
                'count' => $media->count(),
                'avg_reach' => round($media->avg('reach') ?? 0),
                'avg_impressions' => round($media->avg('impressions') ?? 0),
                'avg_engagement_rate' => round($media->avg('engagement_rate') ?? 0, 2),
                'total_likes' => $media->sum('like_count'),
                'total_comments' => $media->sum('comments_count'),
                'total_saves' => $media->sum('saved'),
                'total_shares' => $media->sum('shares'),
            ];
        }

        return $comparison;
    }

    // ==================== HELPER METHODS ====================

    private function getAggregatedInsights(string $accountId, Carbon $start, Carbon $end): array
    {
        $insights = InstagramDailyInsight::where('account_id', $accountId)
            ->whereBetween('insight_date', [$start, $end])
            ->select(
                DB::raw('SUM(impressions) as impressions'),
                DB::raw('SUM(reach) as reach'),
                DB::raw('SUM(profile_views) as profile_views'),
                DB::raw('SUM(website_clicks) as website_clicks'),
                DB::raw('AVG(follower_count) as avg_followers')
            )
            ->first();

        $media = InstagramMedia::where('account_id', $accountId)
            ->whereBetween('posted_at', [$start, $end])
            ->select(
                DB::raw('COUNT(*) as posts_count'),
                DB::raw('SUM(like_count) as total_likes'),
                DB::raw('SUM(comments_count) as total_comments'),
                DB::raw('SUM(saved) as total_saves'),
                DB::raw('SUM(shares) as total_shares'),
                DB::raw('AVG(engagement_rate) as avg_engagement')
            )
            ->first();

        return [
            'impressions' => (int) ($insights->impressions ?? 0),
            'reach' => (int) ($insights->reach ?? 0),
            'profile_views' => (int) ($insights->profile_views ?? 0),
            'website_clicks' => (int) ($insights->website_clicks ?? 0),
            'posts_count' => (int) ($media->posts_count ?? 0),
            'total_likes' => (int) ($media->total_likes ?? 0),
            'total_comments' => (int) ($media->total_comments ?? 0),
            'total_saves' => (int) ($media->total_saves ?? 0),
            'total_shares' => (int) ($media->total_shares ?? 0),
            'avg_engagement_rate' => round($media->avg_engagement ?? 0, 2),
        ];
    }

    private function calculateChange(array $current, array $previous): array
    {
        $metrics = ['impressions', 'reach', 'profile_views', 'posts_count', 'total_likes', 'total_comments'];
        $changes = [];

        foreach ($metrics as $metric) {
            $currentVal = $current[$metric] ?? 0;
            $previousVal = $previous[$metric] ?? 0;

            if ($previousVal > 0) {
                $changes[$metric] = round((($currentVal - $previousVal) / $previousVal) * 100, 1);
            } else {
                $changes[$metric] = $currentVal > 0 ? 100 : 0;
            }
        }

        return $changes;
    }

    private function formatMedia($media): array
    {
        return [
            'id' => $media->media_id,
            'type' => $media->media_product_type,
            'caption' => Str::limit($media->caption, 100),
            'permalink' => $media->permalink,
            'thumbnail_url' => $media->thumbnail_url,
            'like_count' => $media->like_count,
            'comments_count' => $media->comments_count,
            'saves_count' => $media->saved,
            'shares_count' => $media->shares,
            'reach' => $media->reach,
            'impressions' => $media->impressions,
            'plays' => $media->plays,
            'engagement_rate' => round($media->engagement_rate, 2),
            'posted_at' => $media->posted_at?->format('Y-m-d H:i'),
        ];
    }

    private function formatMediaFull($media): array
    {
        // Map media_product_type to frontend expected media_type
        $mediaType = match($media->media_product_type) {
            'REELS' => 'VIDEO',
            'CAROUSEL_ALBUM' => 'CAROUSEL_ALBUM',
            'FEED' => $media->media_type ?? 'IMAGE',
            default => $media->media_type ?? 'IMAGE',
        };

        return [
            'id' => $media->media_id,
            'media_type' => $mediaType,
            'media_product_type' => $media->media_product_type,
            'caption' => $media->caption,
            'permalink' => $media->permalink,
            'media_url' => $media->media_url,
            'thumbnail_url' => $media->thumbnail_url,
            'like_count' => $media->like_count ?? 0,
            'comments_count' => $media->comments_count ?? 0,
            'saves_count' => $media->saved ?? 0,
            'shares_count' => $media->shares ?? 0,
            'reach' => $media->reach ?? 0,
            'impressions' => $media->impressions ?? 0,
            'plays' => $media->plays ?? 0,
            'engagement_rate' => round($media->engagement_rate ?? 0, 2),
            'timestamp' => $media->posted_at?->toIso8601String(),
            'posted_at' => $media->posted_at?->format('Y-m-d H:i'),
        ];
    }

    private function formatAgeGenderData(array $data): array
    {
        $formatted = [];

        // New API format (has 'by_age' and 'by_gender' keys)
        if (isset($data['by_age']) || isset($data['by_gender'])) {
            $byAge = $data['by_age'] ?? [];
            $byGender = $data['by_gender'] ?? [];

            // Format age data
            $totalAge = array_sum($byAge);
            foreach ($byAge as $ageRange => $count) {
                $formatted[] = [
                    'type' => 'age',
                    'age_range' => $ageRange,
                    'count' => $count,
                    'percentage' => $totalAge > 0 ? round(($count / $totalAge) * 100, 1) : 0,
                ];
            }

            // Format gender data
            $totalGender = array_sum($byGender);
            $genderLabels = ['M' => 'Erkak', 'F' => 'Ayol', 'U' => 'Noma\'lum'];
            foreach ($byGender as $gender => $count) {
                $formatted[] = [
                    'type' => 'gender',
                    'gender' => $genderLabels[$gender] ?? $gender,
                    'count' => $count,
                    'percentage' => $totalGender > 0 ? round(($count / $totalGender) * 100, 1) : 0,
                ];
            }

            return $formatted;
        }

        // Old format (M.18-24, F.25-34)
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                continue;
            }

            $parts = explode('.', $key);
            $gender = $parts[0] === 'M' ? 'Erkak' : 'Ayol';
            $ageRange = $parts[1] ?? $key;

            $formatted[] = [
                'gender' => $gender,
                'age_range' => $ageRange,
                'percentage' => round($value, 1),
            ];
        }

        return $formatted;
    }

    private function formatTopLocations(array $data, int $limit): array
    {
        arsort($data);
        $data = array_slice($data, 0, $limit, true);

        $formatted = [];
        foreach ($data as $location => $count) {
            $formatted[] = [
                'name' => $location,
                'count' => $count,
            ];
        }

        return $formatted;
    }

    private function getDayName(int $dayOfWeek): string
    {
        $days = ['Yakshanba', 'Dushanba', 'Seshanba', 'Chorshanba', 'Payshanba', 'Juma', 'Shanba'];
        return $days[$dayOfWeek] ?? '';
    }

    private function getDateRange(string $preset): array
    {
        $end = Carbon::today()->endOfDay();
        $start = match ($preset) {
            'last_7d' => Carbon::today()->subDays(7)->startOfDay(),
            'last_14d' => Carbon::today()->subDays(14)->startOfDay(),
            'last_30d' => Carbon::today()->subDays(30)->startOfDay(),
            'last_90d' => Carbon::today()->subDays(90)->startOfDay(),
            'all_time' => Carbon::create(2020, 1, 1)->startOfDay(),
            default => Carbon::today()->subDays(30)->startOfDay(),
        };

        return ['start' => $start, 'end' => $end];
    }

    /**
     * Get actual date range from media data for an account
     * Returns the range of dates where media actually exists
     */
    private function getActualMediaDateRange(string $accountId): array
    {
        $oldest = InstagramMedia::where('account_id', $accountId)
            ->orderBy('posted_at', 'asc')
            ->first();

        $newest = InstagramMedia::where('account_id', $accountId)
            ->orderBy('posted_at', 'desc')
            ->first();

        if (!$oldest || !$newest) {
            return [
                'start' => Carbon::today()->subDays(30)->startOfDay(),
                'end' => Carbon::today()->endOfDay(),
            ];
        }

        return [
            'start' => $oldest->posted_at->startOfDay(),
            'end' => $newest->posted_at->endOfDay(),
        ];
    }

    /**
     * Get smart date range - uses actual data range if preset range is empty
     */
    private function getSmartDateRange(string $accountId, string $preset): array
    {
        $dates = $this->getDateRange($preset);

        // Check if there's any media in the preset range
        $hasMedia = InstagramMedia::where('account_id', $accountId)
            ->whereBetween('posted_at', [$dates['start'], $dates['end']])
            ->exists();

        if ($hasMedia) {
            return $dates;
        }

        // No media in preset range - use all available data
        return $this->getActualMediaDateRange($accountId);
    }

    private function getPreviousDateRange(string $preset): array
    {
        $days = match ($preset) {
            'last_7d' => 7,
            'last_14d' => 14,
            'last_30d' => 30,
            'last_90d' => 90,
            default => 30,
        };

        return [
            'start' => Carbon::today()->subDays($days * 2),
            'end' => Carbon::today()->subDays($days),
        ];
    }
}
