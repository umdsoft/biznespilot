<?php

namespace App\Models;

use App\Traits\BelongsToBusiness;
use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class ContentIdea extends Model
{
    use BelongsToBusiness, HasUuid, SoftDeletes;

    protected $fillable = [
        'business_id',
        'created_by_user_id',
        'industry_id',
        'title',
        'description',
        'example_content',
        'key_points',
        'suggested_hashtags',
        'suggested_emojis',
        'content_type',
        'purpose',
        'category',
        'tags',
        'is_seasonal',
        'season',
        'best_months',
        'times_used',
        'times_published',
        'avg_engagement_rate',
        'avg_likes',
        'avg_comments',
        'success_rate',
        'quality_score',
        'suitable_industries',
        'suitable_business_types',
        'is_global',
        'is_verified',
        'is_active',
    ];

    protected $casts = [
        'key_points' => 'array',
        'suggested_hashtags' => 'array',
        'suggested_emojis' => 'array',
        'tags' => 'array',
        'best_months' => 'array',
        'suitable_industries' => 'array',
        'suitable_business_types' => 'array',
        'is_seasonal' => 'boolean',
        'is_global' => 'boolean',
        'is_verified' => 'boolean',
        'is_active' => 'boolean',
        'avg_engagement_rate' => 'float',
        'avg_likes' => 'float',
        'avg_comments' => 'float',
        'success_rate' => 'float',
        'quality_score' => 'float',
    ];

    /**
     * Content types
     */
    public const CONTENT_TYPES = [
        'post' => 'Post',
        'story' => 'Story',
        'reel' => 'Reel',
        'ad' => 'Reklama',
        'carousel' => 'Carousel',
        'article' => 'Maqola',
    ];

    /**
     * Purpose types
     */
    public const PURPOSES = [
        'educate' => 'Ta\'lim',
        'inspire' => 'Ilhomlantirish',
        'sell' => 'Sotish',
        'engage' => 'Faollashtirish',
        'announce' => 'E\'lon',
        'entertain' => 'Ko\'ngil ochar',
    ];

    /**
     * Categories
     */
    public const CATEGORIES = [
        'promotion' => 'Aksiya/Chegirma',
        'holiday' => 'Bayram',
        'product' => 'Mahsulot',
        'behind_scenes' => 'Sahna ortida',
        'customer_story' => 'Mijoz hikoyasi',
        'tips' => 'Maslahatlar',
        'news' => 'Yangiliklar',
        'question' => 'Savol/So\'rovnoma',
        'motivation' => 'Motivatsiya',
        'educational' => 'Ta\'limiy',
        'entertainment' => 'Ko\'ngil ochar',
        'user_generated' => 'Mijoz kontenti',
    ];

    /**
     * Seasons
     */
    public const SEASONS = [
        'winter' => 'Qish',
        'spring' => 'Bahor',
        'summer' => 'Yoz',
        'autumn' => 'Kuz',
        'ramadan' => 'Ramazon',
        'new_year' => 'Yangi yil',
        'independence' => 'Mustaqillik',
        'navro\'z' => 'Navro\'z',
        'back_to_school' => 'Maktabga qaytish',
        'black_friday' => 'Black Friday',
    ];

    // ==================== RELATIONSHIPS ====================

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    public function industry(): BelongsTo
    {
        return $this->belongsTo(Industry::class);
    }

    public function usages(): HasMany
    {
        return $this->hasMany(ContentIdeaUsage::class);
    }

    public function collections(): BelongsToMany
    {
        return $this->belongsToMany(ContentIdeaCollection::class, 'content_idea_collection_items', 'idea_id', 'collection_id')
            ->withPivot('sort_order')
            ->withTimestamps();
    }

    // ==================== SCOPES ====================

    /**
     * Scope: Global g'oyalar (barcha bizneslar uchun)
     */
    public function scopeGlobal($query)
    {
        return $query->where('is_global', true)->where('is_active', true);
    }

    /**
     * Scope: Tasdiqlangan g'oyalar
     */
    public function scopeVerified($query)
    {
        return $query->where('is_verified', true);
    }

    /**
     * Scope: Muayyan biznes turi uchun
     */
    public function scopeForIndustry($query, $industryId)
    {
        return $query->where(function ($q) use ($industryId) {
            $q->where('industry_id', $industryId)
                ->orWhereJsonContains('suitable_industries', $industryId)
                ->orWhere('is_global', true);
        });
    }

    /**
     * Scope: Mavsumiy g'oyalar
     */
    public function scopeSeasonal($query, ?string $season = null)
    {
        $query->where('is_seasonal', true);

        if ($season) {
            $query->where('season', $season);
        }

        return $query;
    }

    /**
     * Scope: Joriy oy uchun mos
     */
    public function scopeForCurrentMonth($query)
    {
        $currentMonth = now()->month;

        return $query->where(function ($q) use ($currentMonth) {
            $q->whereJsonContains('best_months', $currentMonth)
                ->orWhereNull('best_months');
        });
    }

    /**
     * Scope: Kontent turi bo'yicha
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('content_type', $type);
    }

    /**
     * Scope: Maqsad bo'yicha
     */
    public function scopeForPurpose($query, string $purpose)
    {
        return $query->where('purpose', $purpose);
    }

    /**
     * Scope: Kategoriya bo'yicha
     */
    public function scopeInCategory($query, string $category)
    {
        return $query->where('category', $category);
    }

    /**
     * Scope: Eng yaxshilari (quality score bo'yicha)
     */
    public function scopeTopRated($query, int $limit = 10)
    {
        return $query->where('quality_score', '>=', 50)
            ->orderByDesc('quality_score')
            ->limit($limit);
    }

    /**
     * Scope: Trending (ko'p ishlatilgan)
     */
    public function scopeTrending($query, int $days = 30)
    {
        return $query->withCount(['usages' => function ($q) use ($days) {
            $q->where('created_at', '>=', now()->subDays($days));
        }])
            ->orderByDesc('usages_count');
    }

    // ==================== METHODS ====================

    /**
     * G'oya ishlatilganda statistikani yangilash
     */
    public function recordUsage(string $businessId, string $userId, ?string $generationId = null): ContentIdeaUsage
    {
        $usage = $this->usages()->create([
            'business_id' => $businessId,
            'user_id' => $userId,
            'content_generation_id' => $generationId,
            'outcome' => 'draft',
        ]);

        $this->increment('times_used');

        return $usage;
    }

    /**
     * Nashr natijasini qayd etish
     */
    public function recordPublish(ContentIdeaUsage $usage, array $metrics = []): void
    {
        $usage->update([
            'outcome' => 'published',
            'engagement_rate' => $metrics['engagement_rate'] ?? null,
            'likes_count' => $metrics['likes_count'] ?? null,
            'comments_count' => $metrics['comments_count'] ?? null,
            'shares_count' => $metrics['shares_count'] ?? null,
        ]);

        $this->increment('times_published');
        $this->updateQualityScore();
    }

    /**
     * Quality score ni hisoblash va yangilash
     */
    public function updateQualityScore(): void
    {
        // Barcha ishlatilishlar statistikasi
        $usages = $this->usages()->where('outcome', 'published')->get();

        if ($usages->isEmpty()) {
            return;
        }

        // O'rtacha ko'rsatkichlar
        $this->avg_engagement_rate = $usages->avg('engagement_rate') ?? 0;
        $this->avg_likes = $usages->avg('likes_count') ?? 0;
        $this->avg_comments = $usages->avg('comments_count') ?? 0;

        // Success rate
        $this->success_rate = $this->times_used > 0
            ? ($this->times_published / $this->times_used) * 100
            : 0;

        // Quality score hisoblash (0-100)
        $score = 0;

        // Engagement rate (0-30 ball)
        $score += min(30, $this->avg_engagement_rate * 5);

        // Success rate (0-25 ball)
        $score += min(25, $this->success_rate * 0.25);

        // Ko'p ishlatilganlik (0-20 ball)
        $score += min(20, $this->times_published * 2);

        // Ijobiy baholashlar (0-15 ball)
        $helpfulCount = $this->usages()->where('user_rating', 'helpful')->count();
        $totalRated = $this->usages()->whereNotNull('user_rating')->count();
        if ($totalRated > 0) {
            $score += ($helpfulCount / $totalRated) * 15;
        }

        // Tasdiqlangan bonus (10 ball)
        if ($this->is_verified) {
            $score += 10;
        }

        $this->quality_score = min(100, round($score, 2));
        $this->save();
    }

    /**
     * G'oyani generatsiya uchun context sifatida tayyorlash
     */
    public function buildGenerationContext(): string
    {
        $context = "G'oya: {$this->title}\n";
        $context .= "Tavsif: {$this->description}\n";

        if ($this->example_content) {
            $context .= "Namuna:\n---\n{$this->example_content}\n---\n";
        }

        if (!empty($this->key_points)) {
            $context .= "Asosiy fikrlar:\n";
            foreach ($this->key_points as $point) {
                $context .= "- {$point}\n";
            }
        }

        if (!empty($this->suggested_hashtags)) {
            $context .= "Tavsiya etiladigan hashtaglar: " . implode(' ', array_map(fn($t) => "#{$t}", $this->suggested_hashtags)) . "\n";
        }

        return $context;
    }

    /**
     * Shu g'oyaga o'xshash g'oyalarni topish
     */
    public function getSimilarIdeas(int $limit = 5)
    {
        return static::where('id', '!=', $this->id)
            ->where('is_active', true)
            ->where(function ($q) {
                $q->where('category', $this->category)
                    ->orWhere('purpose', $this->purpose)
                    ->orWhere('content_type', $this->content_type);
            })
            ->orderByDesc('quality_score')
            ->limit($limit)
            ->get();
    }
}
