<?php

namespace App\Services\ContentAI;

use App\Models\Business;
use App\Models\ContentIdea;
use App\Models\ContentIdeaCollection;
use App\Models\ContentIdeaUsage;
use App\Models\ContentTemplate;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class ContentIdeaRecommendationService
{
    /**
     * Biznes uchun tavsiya etiladigan g'oyalarni olish
     */
    public function getRecommendations(
        string $businessId,
        int $limit = 10,
        ?string $contentType = null,
        ?string $purpose = null
    ): Collection {
        $business = Business::findOrFail($businessId);
        $industryId = $business->industry_id;

        $cacheKey = "content_ideas_recommendations:{$businessId}:{$contentType}:{$purpose}:{$limit}";

        return Cache::remember($cacheKey, 3600, function () use ($business, $industryId, $limit, $contentType, $purpose) {
            $query = ContentIdea::query()
                ->where('is_active', true)
                ->where(function ($q) use ($industryId) {
                    // Global g'oyalar yoki shu industry uchun mos
                    $q->where('is_global', true)
                        ->orWhere('industry_id', $industryId)
                        ->orWhereJsonContains('suitable_industries', $industryId);
                });

            // Filterlar
            if ($contentType) {
                $query->where('content_type', $contentType);
            }

            if ($purpose) {
                $query->where('purpose', $purpose);
            }

            // Joriy oy uchun mos bo'lganlar
            $currentMonth = now()->month;
            $query->where(function ($q) use ($currentMonth) {
                $q->whereJsonContains('best_months', $currentMonth)
                    ->orWhereNull('best_months')
                    ->orWhere('best_months', '[]');
            });

            // Bu biznes ishlatmagan g'oyalar birinchi
            $usedIdeaIds = ContentIdeaUsage::where('business_id', $business->id)
                ->pluck('content_idea_id')
                ->toArray();

            // Scoring formula
            $query->orderByRaw("
                CASE WHEN id NOT IN ('" . implode("','", $usedIdeaIds) . "') THEN 1 ELSE 0 END DESC,
                quality_score DESC,
                is_verified DESC,
                times_published DESC
            ");

            return $query->limit($limit)->get();
        });
    }

    /**
     * Trending g'oyalarni olish (oxirgi 30 kunda ko'p ishlatilgan)
     */
    public function getTrending(string $businessId, int $limit = 10): Collection
    {
        $business = Business::findOrFail($businessId);
        $industryId = $business->industry_id;

        return ContentIdea::query()
            ->where('is_active', true)
            ->where(function ($q) use ($industryId) {
                $q->where('is_global', true)
                    ->orWhere('industry_id', $industryId)
                    ->orWhereJsonContains('suitable_industries', $industryId);
            })
            ->withCount(['usages' => function ($q) {
                $q->where('created_at', '>=', now()->subDays(30));
            }])
            ->having('usages_count', '>', 0)
            ->orderByDesc('usages_count')
            ->limit($limit)
            ->get();
    }

    /**
     * Mavsumiy g'oyalar
     */
    public function getSeasonalIdeas(string $businessId, int $limit = 10): Collection
    {
        $business = Business::findOrFail($businessId);
        $industryId = $business->industry_id;

        // Joriy mavsumni aniqlash
        $currentSeason = $this->getCurrentSeason();

        return ContentIdea::query()
            ->where('is_active', true)
            ->where('is_seasonal', true)
            ->where(function ($q) use ($industryId) {
                $q->where('is_global', true)
                    ->orWhere('industry_id', $industryId)
                    ->orWhereJsonContains('suitable_industries', $industryId);
            })
            ->where(function ($q) use ($currentSeason) {
                $q->where('season', $currentSeason['key'])
                    ->orWhereJsonContains('best_months', now()->month);
            })
            ->orderByDesc('quality_score')
            ->limit($limit)
            ->get();
    }

    /**
     * Kategoriya bo'yicha g'oyalar
     */
    public function getByCategory(string $businessId, string $category, int $limit = 10): Collection
    {
        $business = Business::findOrFail($businessId);
        $industryId = $business->industry_id;

        return ContentIdea::query()
            ->where('is_active', true)
            ->where('category', $category)
            ->where(function ($q) use ($industryId) {
                $q->where('is_global', true)
                    ->orWhere('industry_id', $industryId)
                    ->orWhereJsonContains('suitable_industries', $industryId);
            })
            ->orderByDesc('quality_score')
            ->limit($limit)
            ->get();
    }

    /**
     * O'xshash bizneslar ishlatgan g'oyalar
     */
    public function getUsedBySimilarBusinesses(string $businessId, int $limit = 10): Collection
    {
        $business = Business::findOrFail($businessId);
        $industryId = $business->industry_id;

        // Shu industriyadagi boshqa bizneslar ishlatgan va muvaffaqiyatli bo'lgan
        return ContentIdea::query()
            ->where('is_active', true)
            ->whereHas('usages', function ($q) use ($industryId, $businessId) {
                $q->where('outcome', 'published')
                    ->whereHas('business', function ($bq) use ($industryId, $businessId) {
                        $bq->where('industry_id', $industryId)
                            ->where('id', '!=', $businessId);
                    });
            })
            ->withCount(['usages' => function ($q) {
                $q->where('outcome', 'published');
            }])
            ->orderByDesc('usages_count')
            ->orderByDesc('avg_engagement_rate')
            ->limit($limit)
            ->get();
    }

    /**
     * G'oyalar to'plamlarini olish
     */
    public function getCollections(string $businessId): Collection
    {
        $business = Business::findOrFail($businessId);
        $industryId = $business->industry_id;

        return ContentIdeaCollection::query()
            ->where(function ($q) use ($businessId, $industryId) {
                $q->where('business_id', $businessId) // Biznesning shaxsiy to'plamlari
                    ->orWhere(function ($q) use ($industryId) {
                        $q->where('is_global', true) // Global to'plamlar
                            ->where(function ($iq) use ($industryId) {
                                $iq->whereNull('industry_id')
                                    ->orWhere('industry_id', $industryId)
                                    ->orWhereJsonContains('suitable_industries', $industryId);
                            });
                    });
            })
            ->withCount('ideas')
            ->orderByDesc('is_featured')
            ->orderBy('sort_order')
            ->get();
    }

    /**
     * AI siz g'oya tavsiya qilish (template-based)
     * Mavjud muvaffaqiyatli postlardan g'oyalar yaratish
     */
    public function generateIdeasFromTemplates(string $businessId, int $limit = 5): Collection
    {
        // Eng yaxshi templatelardan pattern aniqlash
        $topTemplates = ContentTemplate::where('business_id', $businessId)
            ->where('is_top_performer', true)
            ->orderByDesc('performance_score')
            ->limit(20)
            ->get();

        if ($topTemplates->isEmpty()) {
            return collect();
        }

        // Mavjud g'oyalar bilan solishtirish
        $existingIdeas = ContentIdea::where(function ($q) use ($businessId) {
            $q->where('business_id', $businessId)
                ->orWhere('is_global', true);
        })->pluck('title')->toArray();

        $suggestions = collect();

        foreach ($topTemplates as $template) {
            // G'oya mavjudligini tekshirish
            $ideaTitle = $this->extractIdeaFromTemplate($template);

            if (!in_array($ideaTitle, $existingIdeas) && $ideaTitle) {
                $suggestions->push([
                    'title' => $ideaTitle,
                    'description' => $this->generateDescription($template),
                    'content_type' => $template->content_type,
                    'purpose' => $template->purpose,
                    'category' => $this->detectCategory($template),
                    'example_content' => $template->content_cleaned ?? $template->content,
                    'suggested_hashtags' => $template->hashtags,
                    'performance_score' => $template->performance_score,
                    'source_template_id' => $template->id,
                ]);
            }
        }

        return $suggestions->take($limit);
    }

    /**
     * Smart tavsiyalar - barcha manbalarni birlashtirish
     */
    public function getSmartRecommendations(string $businessId, int $limit = 15): array
    {
        $recommendations = [
            'top_picks' => $this->getRecommendations($businessId, 5),
            'trending' => $this->getTrending($businessId, 5),
            'seasonal' => $this->getSeasonalIdeas($businessId, 5),
            'from_similar' => $this->getUsedBySimilarBusinesses($businessId, 5),
            'your_best' => $this->generateIdeasFromTemplates($businessId, 5),
        ];

        // Statistika
        $stats = $this->getUsageStats($businessId);

        return [
            'recommendations' => $recommendations,
            'stats' => $stats,
            'current_season' => $this->getCurrentSeason(),
        ];
    }

    /**
     * Ishlatish statistikasi
     */
    public function getUsageStats(string $businessId): array
    {
        $usages = ContentIdeaUsage::where('business_id', $businessId);

        return [
            'total_ideas_used' => $usages->count(),
            'published_count' => $usages->clone()->where('outcome', 'published')->count(),
            'avg_engagement' => $usages->clone()->where('outcome', 'published')->avg('engagement_rate') ?? 0,
            'helpful_rate' => $this->calculateHelpfulRate($businessId),
            'most_used_category' => $this->getMostUsedCategory($businessId),
        ];
    }

    // ==================== HELPER METHODS ====================

    /**
     * Joriy mavsumni aniqlash
     */
    protected function getCurrentSeason(): array
    {
        $month = now()->month;
        $day = now()->day;

        // O'zbekiston uchun maxsus bayramlar
        if ($month === 1 && $day <= 15) {
            return ['key' => 'new_year', 'label' => 'Yangi yil'];
        }

        if ($month === 3 && $day >= 15 && $day <= 25) {
            return ['key' => 'navro\'z', 'label' => 'Navro\'z'];
        }

        if ($month === 9 && $day === 1) {
            return ['key' => 'independence', 'label' => 'Mustaqillik'];
        }

        // Ramazon (taxminiy - har yil o'zgaradi)
        // Bu yerda Hijriy kalendar bilan hisoblash kerak

        // Mavsumlar
        if (in_array($month, [12, 1, 2])) {
            return ['key' => 'winter', 'label' => 'Qish'];
        }
        if (in_array($month, [3, 4, 5])) {
            return ['key' => 'spring', 'label' => 'Bahor'];
        }
        if (in_array($month, [6, 7, 8])) {
            return ['key' => 'summer', 'label' => 'Yoz'];
        }

        return ['key' => 'autumn', 'label' => 'Kuz'];
    }

    /**
     * Templatedan g'oya nomini chiqarish
     */
    protected function extractIdeaFromTemplate(ContentTemplate $template): ?string
    {
        $content = $template->content_cleaned ?? $template->content;

        // Birinchi jumlani olish
        $firstSentence = strtok($content, ".!?\n");

        if (strlen($firstSentence) > 100) {
            $firstSentence = substr($firstSentence, 0, 100) . '...';
        }

        return $firstSentence ?: null;
    }

    /**
     * Template uchun tavsif yaratish
     */
    protected function generateDescription(ContentTemplate $template): string
    {
        $type = ContentIdea::CONTENT_TYPES[$template->content_type] ?? $template->content_type;
        $purpose = ContentIdea::PURPOSES[$template->purpose] ?? $template->purpose;

        return "{$type} formatida {$purpose} maqsadidagi kontent. Engagement: {$template->engagement_rate}%";
    }

    /**
     * Kategoriyani aniqlash
     */
    protected function detectCategory(ContentTemplate $template): string
    {
        $content = strtolower($template->content);

        $patterns = [
            'promotion' => ['aksiya', 'chegirma', 'skidka', 'sale', '%'],
            'holiday' => ['bayram', 'tabrik', 'muborak'],
            'product' => ['mahsulot', 'yangi', 'narx', 'product'],
            'tips' => ['maslahat', 'tip', 'qanday', 'yo\'l'],
            'question' => ['?', 'qaysi', 'nima'],
            'motivation' => ['motivatsiya', 'muvaffaqiyat', 'ilhom'],
        ];

        foreach ($patterns as $category => $keywords) {
            foreach ($keywords as $keyword) {
                if (strpos($content, $keyword) !== false) {
                    return $category;
                }
            }
        }

        return 'general';
    }

    /**
     * Foydali baholash foizi
     */
    protected function calculateHelpfulRate(string $businessId): float
    {
        $total = ContentIdeaUsage::where('business_id', $businessId)
            ->whereNotNull('user_rating')
            ->count();

        if ($total === 0) {
            return 0;
        }

        $helpful = ContentIdeaUsage::where('business_id', $businessId)
            ->where('user_rating', 'helpful')
            ->count();

        return round(($helpful / $total) * 100, 1);
    }

    /**
     * Eng ko'p ishlatilgan kategoriya
     */
    protected function getMostUsedCategory(string $businessId): ?string
    {
        $result = ContentIdeaUsage::query()
            ->where('content_idea_usages.business_id', $businessId)
            ->join('content_ideas', 'content_idea_usages.content_idea_id', '=', 'content_ideas.id')
            ->select('content_ideas.category', DB::raw('COUNT(*) as count'))
            ->groupBy('content_ideas.category')
            ->orderByDesc('count')
            ->first();

        return $result?->category;
    }

    /**
     * Cache ni tozalash
     */
    public function clearCache(string $businessId): void
    {
        Cache::forget("content_ideas_recommendations:{$businessId}:*");
    }
}
