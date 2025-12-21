<?php

namespace App\Services;

use App\Models\InstagramAccount;
use App\Models\InstagramMedia;
use App\Models\InstagramDailyInsight;
use App\Models\InstagramAudience;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Instagram Insights Service
 *
 * Bu service biznes egalari uchun amaliy savollarnga javob beradi:
 * - Qaysi kontent eng ko'p follower olib keldi?
 * - Qaysi reels eng yaxshi ishladi va nega?
 * - Qachon post qilish kerak?
 * - Qanday kontent yaratish kerak?
 */
class InstagramInsightsService
{
    /**
     * Get comprehensive business insights
     * Bu biznesga amaliy tavsiyalar beradi
     */
    public function getBusinessInsights(int $accountId): array
    {
        $account = InstagramAccount::find($accountId);
        if (!$account) {
            return [];
        }

        return [
            'growth_drivers' => $this->analyzeGrowthDrivers($accountId),
            'content_winners' => $this->getContentWinners($accountId),
            'best_posting_strategy' => $this->getBestPostingStrategy($accountId),
            'audience_insights' => $this->getAudienceActionableInsights($accountId),
            'content_recommendations' => $this->getContentRecommendations($accountId),
            'performance_trends' => $this->getPerformanceTrends($accountId),
            'viral_potential' => $this->analyzeViralPotential($accountId),
        ];
    }

    /**
     * Qaysi kontentlar o'sishga eng ko'p hissa qo'shgan?
     * Follower o'sishi bilan kontent o'rtasidagi bog'liqlikni tahlil qiladi
     */
    public function analyzeGrowthDrivers(int $accountId): array
    {
        // Kunlik follower o'sishi va o'sha kungi postlarni solishtirish
        $dailyInsights = InstagramDailyInsight::where('instagram_account_id', $accountId)
            ->orderBy('date')
            ->get();

        if ($dailyInsights->count() < 2) {
            return ['message' => 'Yetarli ma\'lumot yo\'q'];
        }

        // Follower o'sish trendini hisoblash
        $growthData = [];
        $previousFollowers = null;

        foreach ($dailyInsights as $insight) {
            if ($previousFollowers !== null && $insight->follower_count > 0) {
                $growth = $insight->follower_count - $previousFollowers;
                $growthData[$insight->date->format('Y-m-d')] = $growth;
            }
            $previousFollowers = $insight->follower_count;
        }

        // Eng yaxshi o'sish kunlarini topish
        arsort($growthData);
        $topGrowthDays = array_slice($growthData, 0, 10, true);

        // O'sha kunlardagi postlarni topish
        $growthDriverPosts = [];
        foreach ($topGrowthDays as $date => $growth) {
            $posts = InstagramMedia::where('instagram_account_id', $accountId)
                ->whereDate('posted_at', $date)
                ->orWhere(function ($q) use ($date) {
                    // 1 kun oldin joylangan postlar ham ta'sir qiladi
                    $q->whereDate('posted_at', Carbon::parse($date)->subDay());
                })
                ->orderByDesc('reach')
                ->limit(3)
                ->get();

            if ($posts->count() > 0) {
                $growthDriverPosts[] = [
                    'date' => $date,
                    'follower_growth' => $growth,
                    'posts' => $posts->map(fn($p) => [
                        'id' => $p->media_id,
                        'type' => $p->media_product_type,
                        'caption' => \Str::limit($p->caption, 100),
                        'thumbnail_url' => $p->thumbnail_url,
                        'reach' => $p->reach,
                        'engagement_rate' => $p->engagement_rate,
                        'permalink' => $p->permalink,
                    ])->toArray(),
                ];
            }
        }

        // Eng ko'p follower olib kelgan postlarni aniqlash
        $topGrowthPosts = collect($growthDriverPosts)
            ->sortByDesc('follower_growth')
            ->take(5)
            ->values()
            ->toArray();

        return [
            'top_growth_days' => $topGrowthPosts,
            'total_growth_analyzed' => array_sum($growthData),
            'avg_daily_growth' => count($growthData) > 0 ? round(array_sum($growthData) / count($growthData), 1) : 0,
            'insight' => $this->generateGrowthInsight($topGrowthPosts),
        ];
    }

    /**
     * Eng yaxshi ishlagan kontentlarni tahlil qilish
     * Har bir kategoriyada "g'olib"larni aniqlash
     */
    public function getContentWinners(int $accountId): array
    {
        // Eng ko'p reach olgan post
        $topReachPost = InstagramMedia::where('instagram_account_id', $accountId)
            ->orderByDesc('reach')
            ->first();

        // Eng ko'p engagement olgan post
        $topEngagementPost = InstagramMedia::where('instagram_account_id', $accountId)
            ->orderByDesc('engagement_rate')
            ->first();

        // Eng ko'p comment olgan post (odamlar bilan muloqot)
        $topCommentedPost = InstagramMedia::where('instagram_account_id', $accountId)
            ->orderByDesc('comments_count')
            ->first();

        // Eng ko'p saqlanganlar (foydalanuvchilar qayta ko'rish uchun saqlagan)
        $topSavedPost = InstagramMedia::where('instagram_account_id', $accountId)
            ->orderByDesc('saves_count')
            ->first();

        // Reelslar ichida eng yaxshisi
        $topReel = InstagramMedia::where('instagram_account_id', $accountId)
            ->where('media_product_type', 'REELS')
            ->orderByDesc('reach')
            ->first();

        // Carousel ichida eng yaxshisi
        $topCarousel = InstagramMedia::where('instagram_account_id', $accountId)
            ->where('media_product_type', 'CAROUSEL_ALBUM')
            ->orderByDesc('reach')
            ->first();

        return [
            'most_reached' => $topReachPost ? $this->formatWinnerPost($topReachPost, 'Eng ko\'p odamga yetib borgan') : null,
            'most_engaging' => $topEngagementPost ? $this->formatWinnerPost($topEngagementPost, 'Eng ko\'p faollik olgan') : null,
            'most_discussed' => $topCommentedPost ? $this->formatWinnerPost($topCommentedPost, 'Eng ko\'p muhokama qilingan') : null,
            'most_saved' => $topSavedPost ? $this->formatWinnerPost($topSavedPost, 'Eng ko\'p saqlangan (foydali kontent)') : null,
            'best_reel' => $topReel ? $this->formatWinnerPost($topReel, 'Eng yaxshi Reels') : null,
            'best_carousel' => $topCarousel ? $this->formatWinnerPost($topCarousel, 'Eng yaxshi Carousel') : null,
        ];
    }

    /**
     * Eng yaxshi post qilish strategiyasini aniqlash
     */
    public function getBestPostingStrategy(int $accountId): array
    {
        $media = InstagramMedia::where('instagram_account_id', $accountId)
            ->whereNotNull('posted_at')
            ->get();

        if ($media->count() < 5) {
            return ['message' => 'Yetarli ma\'lumot yo\'q'];
        }

        // Hafta kunlari bo'yicha tahlil
        $dayPerformance = [];
        foreach ($media as $post) {
            $dayOfWeek = $post->posted_at->dayOfWeek;
            if (!isset($dayPerformance[$dayOfWeek])) {
                $dayPerformance[$dayOfWeek] = [
                    'posts' => 0,
                    'total_reach' => 0,
                    'total_engagement' => 0,
                ];
            }
            $dayPerformance[$dayOfWeek]['posts']++;
            $dayPerformance[$dayOfWeek]['total_reach'] += $post->reach;
            $dayPerformance[$dayOfWeek]['total_engagement'] += $post->engagement_rate;
        }

        // O'rtacha metrikalarni hisoblash
        $dayAnalysis = [];
        $dayNames = ['Yakshanba', 'Dushanba', 'Seshanba', 'Chorshanba', 'Payshanba', 'Juma', 'Shanba'];
        foreach ($dayPerformance as $day => $data) {
            $dayAnalysis[$day] = [
                'name' => $dayNames[$day],
                'posts_count' => $data['posts'],
                'avg_reach' => $data['posts'] > 0 ? round($data['total_reach'] / $data['posts']) : 0,
                'avg_engagement' => $data['posts'] > 0 ? round($data['total_engagement'] / $data['posts'], 2) : 0,
            ];
        }

        // Eng yaxshi kunlarni aniqlash
        uasort($dayAnalysis, fn($a, $b) => $b['avg_reach'] <=> $a['avg_reach']);
        $bestDays = array_slice($dayAnalysis, 0, 3, true);

        // Soatlar bo'yicha tahlil
        $hourPerformance = [];
        foreach ($media as $post) {
            $hour = $post->posted_at->hour;
            if (!isset($hourPerformance[$hour])) {
                $hourPerformance[$hour] = [
                    'posts' => 0,
                    'total_reach' => 0,
                    'total_engagement' => 0,
                ];
            }
            $hourPerformance[$hour]['posts']++;
            $hourPerformance[$hour]['total_reach'] += $post->reach;
            $hourPerformance[$hour]['total_engagement'] += $post->engagement_rate;
        }

        // Eng yaxshi soatlarni aniqlash
        $hourAnalysis = [];
        foreach ($hourPerformance as $hour => $data) {
            if ($data['posts'] >= 2) { // Kamida 2 ta post bo'lsin
                $hourAnalysis[$hour] = [
                    'hour' => sprintf('%02d:00', $hour),
                    'posts_count' => $data['posts'],
                    'avg_reach' => round($data['total_reach'] / $data['posts']),
                    'avg_engagement' => round($data['total_engagement'] / $data['posts'], 2),
                ];
            }
        }
        uasort($hourAnalysis, fn($a, $b) => $b['avg_reach'] <=> $a['avg_reach']);
        $bestHours = array_slice($hourAnalysis, 0, 3, true);

        // Content type bo'yicha tahlil
        $typePerformance = InstagramMedia::where('instagram_account_id', $accountId)
            ->select(
                'media_product_type',
                DB::raw('COUNT(*) as count'),
                DB::raw('AVG(reach) as avg_reach'),
                DB::raw('AVG(engagement_rate) as avg_engagement')
            )
            ->groupBy('media_product_type')
            ->get()
            ->keyBy('media_product_type');

        // Eng yaxshi content turini aniqlash
        $bestContentType = $typePerformance->sortByDesc('avg_reach')->first();

        return [
            'best_days' => array_values($bestDays),
            'best_hours' => array_values($bestHours),
            'content_type_analysis' => $typePerformance,
            'recommended_content_type' => $bestContentType ? $bestContentType->media_product_type : 'REELS',
            'strategy_summary' => $this->generateStrategySummary($bestDays, $bestHours, $bestContentType),
        ];
    }

    /**
     * Auditoriya haqida amaliy insights
     */
    public function getAudienceActionableInsights(int $accountId): array
    {
        $audience = InstagramAudience::where('instagram_account_id', $accountId)->first();
        $account = InstagramAccount::find($accountId);

        if (!$audience || !$account) {
            return ['message' => 'Auditoriya ma\'lumotlari topilmadi'];
        }

        $insights = [];

        // Gender tahlili
        $genderDist = $audience->gender_distribution;
        if ($genderDist['male'] > 60) {
            $insights['gender_insight'] = 'Auditoriyangizning aksariyati erkaklar. Erkaklar uchun qiziqarli kontent yarating.';
        } elseif ($genderDist['female'] > 60) {
            $insights['gender_insight'] = 'Auditoriyangizning aksariyati ayollar. Ayollar uchun qiziqarli kontent yarating.';
        } else {
            $insights['gender_insight'] = 'Auditoriyangiz teng taqsimlangan. Universal kontent ishlaydi.';
        }

        // Yosh tahlili
        $dominantAge = $audience->dominant_age_group;
        if ($dominantAge) {
            $insights['age_insight'] = "{$dominantAge} yoshdagilar sizning asosiy auditoriyangiz. Ular uchun mos kontent yarating.";
        }

        // Lokatsiya tahlili
        $topCities = $audience->top_cities ?? [];
        $topCountries = $audience->top_countries ?? [];

        if (!empty($topCities)) {
            $topCity = array_key_first($topCities);
            $insights['location_insight'] = "Followerlaringizning ko'pchiligi {$topCity} da yashaydi. Mahalliy kontentni ko'paytiring.";
        }

        // Online vaqtlar
        $onlineHours = $audience->online_hours ?? [];
        if (!empty($onlineHours)) {
            arsort($onlineHours);
            $bestHour = array_key_first($onlineHours);
            $insights['timing_insight'] = "Auditoriyangiz eng faol soat {$bestHour}:00 da. Shu paytda post qiling.";
        }

        return [
            'gender_distribution' => $genderDist,
            'dominant_age' => $dominantAge,
            'top_city' => array_key_first($topCities ?? []),
            'top_country' => array_key_first($topCountries ?? []),
            'insights' => $insights,
            'target_audience_profile' => $this->generateAudienceProfile($audience, $account),
        ];
    }

    /**
     * Content tavsiyalari - nima haqida post qilish kerak?
     */
    public function getContentRecommendations(int $accountId): array
    {
        // Eng yaxshi ishlagan postlar captionlarini tahlil qilish
        $topPosts = InstagramMedia::where('instagram_account_id', $accountId)
            ->orderByDesc('reach')
            ->limit(20)
            ->get();

        if ($topPosts->count() < 5) {
            return ['message' => 'Yetarli ma\'lumot yo\'q'];
        }

        // Hashtag tahlili - qaysi hashtaglar yaxshi ishlaydi
        $hashtagPerformance = DB::table('instagram_hashtag_stats')
            ->where('instagram_account_id', $accountId)
            ->orderByDesc('avg_engagement_rate')
            ->limit(10)
            ->get();

        // Content turi tavsiyasi
        $contentTypeStats = InstagramMedia::where('instagram_account_id', $accountId)
            ->select(
                'media_product_type',
                DB::raw('AVG(reach) as avg_reach'),
                DB::raw('AVG(engagement_rate) as avg_engagement'),
                DB::raw('COUNT(*) as count')
            )
            ->groupBy('media_product_type')
            ->get();

        $recommendedType = $contentTypeStats->sortByDesc('avg_reach')->first();

        // Caption uzunligi tahlili
        $captionAnalysis = $this->analyzeCaptionLength($accountId);

        // Top postlardan mavzularni aniqlash
        $topTopics = $this->extractTopTopics($topPosts);

        return [
            'best_content_type' => $recommendedType ? [
                'type' => $recommendedType->media_product_type,
                'avg_reach' => round($recommendedType->avg_reach),
                'reason' => $this->getContentTypeReason($recommendedType->media_product_type),
            ] : null,
            'top_hashtags' => $hashtagPerformance->map(fn($h) => [
                'hashtag' => '#' . $h->hashtag,
                'usage' => $h->usage_count,
                'engagement' => round($h->avg_engagement_rate, 2) . '%',
            ])->toArray(),
            'caption_recommendation' => $captionAnalysis,
            'trending_topics' => $topTopics,
            'action_items' => $this->generateContentActionItems($recommendedType, $hashtagPerformance, $captionAnalysis),
        ];
    }

    /**
     * Performance trendlari - o'sish yoki pasayish bormi?
     */
    public function getPerformanceTrends(int $accountId): array
    {
        // Oxirgi 4 hafta vs oldingi 4 hafta
        $last4Weeks = InstagramMedia::where('instagram_account_id', $accountId)
            ->where('posted_at', '>=', now()->subWeeks(4))
            ->get();

        $previous4Weeks = InstagramMedia::where('instagram_account_id', $accountId)
            ->whereBetween('posted_at', [now()->subWeeks(8), now()->subWeeks(4)])
            ->get();

        if ($last4Weeks->count() < 2 || $previous4Weeks->count() < 2) {
            return ['message' => 'Yetarli ma\'lumot yo\'q'];
        }

        $currentMetrics = [
            'avg_reach' => $last4Weeks->avg('reach'),
            'avg_engagement' => $last4Weeks->avg('engagement_rate'),
            'total_posts' => $last4Weeks->count(),
            'total_likes' => $last4Weeks->sum('like_count'),
            'total_comments' => $last4Weeks->sum('comments_count'),
        ];

        $previousMetrics = [
            'avg_reach' => $previous4Weeks->avg('reach'),
            'avg_engagement' => $previous4Weeks->avg('engagement_rate'),
            'total_posts' => $previous4Weeks->count(),
            'total_likes' => $previous4Weeks->sum('like_count'),
            'total_comments' => $previous4Weeks->sum('comments_count'),
        ];

        // O'zgarishlarni hisoblash
        $changes = [];
        foreach ($currentMetrics as $key => $value) {
            $prev = $previousMetrics[$key] ?: 1;
            $changes[$key] = round((($value - $prev) / $prev) * 100, 1);
        }

        // Trend xulosa
        $overallTrend = 'stable';
        if ($changes['avg_reach'] > 10 && $changes['avg_engagement'] > 5) {
            $overallTrend = 'growing';
        } elseif ($changes['avg_reach'] < -10 || $changes['avg_engagement'] < -10) {
            $overallTrend = 'declining';
        }

        return [
            'current_period' => $currentMetrics,
            'previous_period' => $previousMetrics,
            'changes' => $changes,
            'overall_trend' => $overallTrend,
            'trend_insight' => $this->generateTrendInsight($overallTrend, $changes),
        ];
    }

    /**
     * Viral potensial - qaysi postlar viral bo'lish imkoniga ega?
     */
    public function analyzeViralPotential(int $accountId): array
    {
        $account = InstagramAccount::find($accountId);
        if (!$account) {
            return [];
        }

        // Viral = reach > followers * 2 (yoki reach/followers ratio > 2)
        $viralPosts = InstagramMedia::where('instagram_account_id', $accountId)
            ->where('reach', '>', $account->followers_count)
            ->orderByDesc('reach')
            ->limit(10)
            ->get();

        // Viral postlarning umumiy xususiyatlari
        $viralCharacteristics = [];
        if ($viralPosts->count() > 0) {
            // Content type
            $typeCount = $viralPosts->groupBy('media_product_type')->map->count();
            $viralCharacteristics['dominant_type'] = $typeCount->sort()->keys()->last();

            // Caption uzunligi
            $avgCaptionLength = $viralPosts->avg(fn($p) => strlen($p->caption ?? ''));
            $viralCharacteristics['avg_caption_length'] = round($avgCaptionLength);

            // Posting soati
            $hourCount = $viralPosts->groupBy(fn($p) => $p->posted_at->hour)->map->count();
            $viralCharacteristics['best_posting_hour'] = $hourCount->sort()->keys()->last();

            // Eng ko'p ishlatiladigan hashtaglar
            $allHashtags = [];
            foreach ($viralPosts as $post) {
                $allHashtags = array_merge($allHashtags, $post->hashtags ?? []);
            }
            $hashtagCount = array_count_values($allHashtags);
            arsort($hashtagCount);
            $viralCharacteristics['top_hashtags'] = array_slice($hashtagCount, 0, 5, true);
        }

        return [
            'viral_posts' => $viralPosts->map(fn($p) => [
                'id' => $p->media_id,
                'type' => $p->media_product_type,
                'thumbnail_url' => $p->thumbnail_url,
                'permalink' => $p->permalink,
                'reach' => $p->reach,
                'viral_ratio' => round($p->reach / max($account->followers_count, 1), 2),
                'caption' => \Str::limit($p->caption, 100),
                'posted_at' => $p->posted_at->format('d.m.Y'),
            ])->toArray(),
            'viral_characteristics' => $viralCharacteristics,
            'viral_formula' => $this->generateViralFormula($viralCharacteristics),
        ];
    }

    // ==================== HELPER METHODS ====================

    private function formatWinnerPost($post, string $title): array
    {
        return [
            'title' => $title,
            'id' => $post->media_id,
            'type' => $post->media_product_type,
            'thumbnail_url' => $post->thumbnail_url,
            'permalink' => $post->permalink,
            'caption' => \Str::limit($post->caption, 150),
            'reach' => $post->reach,
            'engagement_rate' => round($post->engagement_rate, 2),
            'likes' => $post->like_count,
            'comments' => $post->comments_count,
            'saves' => $post->saves_count,
            'posted_at' => $post->posted_at->format('d.m.Y'),
            'why_it_worked' => $this->analyzeWhyItWorked($post),
        ];
    }

    private function analyzeWhyItWorked($post): string
    {
        $reasons = [];

        if ($post->engagement_rate > 5) {
            $reasons[] = 'Juda yuqori engagement';
        }

        if ($post->comments_count > 50) {
            $reasons[] = 'Ko\'p muhokama qilingan';
        }

        if ($post->saves_count > 100) {
            $reasons[] = 'Ko\'p saqlangan (foydali kontent)';
        }

        if ($post->media_product_type === 'REELS') {
            $reasons[] = 'Reels formati algoritm tomonidan qo\'llab-quvvatlanadi';
        }

        if (strlen($post->caption ?? '') > 500) {
            $reasons[] = 'Batafsil caption yozilgan';
        }

        $hashtagCount = count($post->hashtags ?? []);
        if ($hashtagCount >= 10 && $hashtagCount <= 20) {
            $reasons[] = 'Optimal hashtag soni ishlatilgan';
        }

        return implode('. ', $reasons) ?: 'Auditoriyaga yoqgan kontent';
    }

    private function generateGrowthInsight(array $topGrowthPosts): string
    {
        if (empty($topGrowthPosts)) {
            return 'Yetarli ma\'lumot yo\'q';
        }

        $types = [];
        foreach ($topGrowthPosts as $day) {
            foreach ($day['posts'] as $post) {
                $types[] = $post['type'];
            }
        }

        $typeCounts = array_count_values($types);
        arsort($typeCounts);
        $dominantType = array_key_first($typeCounts);

        $typeLabels = [
            'REELS' => 'Reelslar',
            'FEED' => 'Oddiy postlar',
            'CAROUSEL_ALBUM' => 'Carousellar',
        ];

        return ($typeLabels[$dominantType] ?? $dominantType) . ' sizga eng ko\'p follower olib kelmoqda. Shu formatni ko\'proq ishlating.';
    }

    private function generateStrategySummary(array $bestDays, array $bestHours, $bestContentType): string
    {
        $summary = [];

        if (!empty($bestDays)) {
            $topDay = reset($bestDays);
            $summary[] = "{$topDay['name']} kuni post qiling";
        }

        if (!empty($bestHours)) {
            $topHour = reset($bestHours);
            $summary[] = "soat {$topHour['hour']} da";
        }

        if ($bestContentType) {
            $typeLabels = [
                'REELS' => 'Reels formatida',
                'FEED' => 'oddiy post',
                'CAROUSEL_ALBUM' => 'Carousel formatida',
            ];
            $summary[] = $typeLabels[$bestContentType->media_product_type] ?? '';
        }

        return implode(' ', $summary) . '.';
    }

    private function generateAudienceProfile($audience, $account): string
    {
        $parts = [];

        // Yosh
        $dominantAge = $audience->dominant_age_group;
        if ($dominantAge) {
            $parts[] = "{$dominantAge} yoshli";
        }

        // Jins
        $genderDist = $audience->gender_distribution;
        if ($genderDist['male'] > 60) {
            $parts[] = 'erkaklar';
        } elseif ($genderDist['female'] > 60) {
            $parts[] = 'ayollar';
        }

        // Lokatsiya
        $topCities = $audience->top_cities ?? [];
        if (!empty($topCities)) {
            $parts[] = array_key_first($topCities) . 'da yashovchi';
        }

        return 'Sizning tipik followeringiz: ' . implode(', ', $parts) . '.';
    }

    private function analyzeCaptionLength(int $accountId): array
    {
        $media = InstagramMedia::where('instagram_account_id', $accountId)->get();

        $shortCaptions = $media->filter(fn($m) => strlen($m->caption ?? '') < 100);
        $mediumCaptions = $media->filter(fn($m) => strlen($m->caption ?? '') >= 100 && strlen($m->caption ?? '') < 500);
        $longCaptions = $media->filter(fn($m) => strlen($m->caption ?? '') >= 500);

        $results = [
            'short' => ['count' => $shortCaptions->count(), 'avg_reach' => round($shortCaptions->avg('reach'))],
            'medium' => ['count' => $mediumCaptions->count(), 'avg_reach' => round($mediumCaptions->avg('reach'))],
            'long' => ['count' => $longCaptions->count(), 'avg_reach' => round($longCaptions->avg('reach'))],
        ];

        // Eng yaxshi uzunlikni aniqlash
        $best = collect($results)->sortByDesc('avg_reach')->keys()->first();

        $recommendations = [
            'short' => 'Qisqa captionlar (100 belgidan kam) sizda yaxshi ishlaydi.',
            'medium' => 'O\'rtacha uzunlikdagi captionlar (100-500 belgi) eng yaxshi natija beradi.',
            'long' => 'Batafsil captionlar (500+ belgi) auditoriyangizga yoqadi.',
        ];

        return [
            'analysis' => $results,
            'best_length' => $best,
            'recommendation' => $recommendations[$best] ?? '',
        ];
    }

    private function extractTopTopics(object $topPosts): array
    {
        // Oddiy so'z chastotasi tahlili
        $words = [];
        foreach ($topPosts as $post) {
            $caption = strtolower($post->caption ?? '');
            // Hashtaglar va mentionlarni olib tashlash
            $caption = preg_replace('/#\w+|@\w+/', '', $caption);
            // So'zlarni ajratish
            preg_match_all('/\b\w{4,}\b/u', $caption, $matches);
            $words = array_merge($words, $matches[0] ?? []);
        }

        $wordCount = array_count_values($words);
        arsort($wordCount);

        // Stop so'zlarni olib tashlash
        $stopWords = ['uchun', 'bilan', 'haqida', 'kerak', 'emas', 'this', 'that', 'with', 'from', 'have'];
        foreach ($stopWords as $stop) {
            unset($wordCount[$stop]);
        }

        return array_slice($wordCount, 0, 10, true);
    }

    private function getContentTypeReason(string $type): string
    {
        return match ($type) {
            'REELS' => 'Reelslar Instagram algoritmida ustuvor. Yangi auditoriyaga yetib borish osonroq.',
            'CAROUSEL_ALBUM' => 'Carousellar ko\'proq vaqt sarf qildiradi, bu engagement oshiradi.',
            'FEED' => 'Oddiy rasmlar tez tayyorlanadi va doimiy mavjudlikni ta\'minlaydi.',
            default => 'Bu format auditoriyangizga yoqadi.',
        };
    }

    private function generateContentActionItems($recommendedType, $hashtagPerformance, $captionAnalysis): array
    {
        $items = [];

        if ($recommendedType) {
            $items[] = [
                'priority' => 'high',
                'action' => "Ko'proq {$recommendedType->media_product_type} joylashtiring - bu sizda eng yaxshi ishlaydi",
            ];
        }

        if ($hashtagPerformance->count() > 0) {
            $topHashtags = $hashtagPerformance->take(3)->pluck('hashtag')->map(fn($h) => '#' . $h)->implode(', ');
            $items[] = [
                'priority' => 'medium',
                'action' => "Shu hashtaglarni ishlating: {$topHashtags}",
            ];
        }

        if (!empty($captionAnalysis['recommendation'])) {
            $items[] = [
                'priority' => 'medium',
                'action' => $captionAnalysis['recommendation'],
            ];
        }

        return $items;
    }

    private function generateTrendInsight(string $trend, array $changes): string
    {
        return match ($trend) {
            'growing' => "Ajoyib! Reach {$changes['avg_reach']}% ga, engagement {$changes['avg_engagement']}% ga oshdi. Shu strategiyani davom ettiring!",
            'declining' => "Ogohlantirish: Reach {$changes['avg_reach']}% ga kamaydi. Kontent strategiyangizni qayta ko'ring.",
            default => "Barqaror holat. O'sish uchun yangi formatlar sinab ko'ring.",
        };
    }

    private function generateViralFormula(array $characteristics): string
    {
        if (empty($characteristics)) {
            return 'Yetarli ma\'lumot yo\'q';
        }

        $formula = [];

        if (!empty($characteristics['dominant_type'])) {
            $typeLabels = ['REELS' => 'Reels', 'CAROUSEL_ALBUM' => 'Carousel', 'FEED' => 'Post'];
            $formula[] = $typeLabels[$characteristics['dominant_type']] ?? $characteristics['dominant_type'];
        }

        if (!empty($characteristics['best_posting_hour'])) {
            $formula[] = "soat {$characteristics['best_posting_hour']}:00 da";
        }

        if (!empty($characteristics['avg_caption_length'])) {
            $len = $characteristics['avg_caption_length'];
            if ($len > 500) {
                $formula[] = 'batafsil caption bilan';
            } elseif ($len > 100) {
                $formula[] = "o'rtacha uzunlikdagi caption bilan";
            } else {
                $formula[] = 'qisqa caption bilan';
            }
        }

        return 'Viral formula: ' . implode(' ', $formula) . '.';
    }
}
