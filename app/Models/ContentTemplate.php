<?php

namespace App\Models;

use App\Traits\BelongsToBusiness;
use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class ContentTemplate extends Model
{
    use BelongsToBusiness, HasUuid, SoftDeletes;

    protected $fillable = [
        'business_id',
        'source_type',
        'source_id',
        'source_url',
        'content_type',
        'purpose',
        'content',
        'content_cleaned',
        'hashtags',
        'mentions',
        'links',
        'media_urls',
        'likes_count',
        'comments_count',
        'shares_count',
        'saves_count',
        'reach',
        'impressions',
        'engagement_rate',
        'performance_score',
        'ai_analysis',
        'target_channel',
        'is_top_performer',
        'is_approved',
        'is_active',
        'posted_at',
        'analyzed_at',
    ];

    protected $casts = [
        'hashtags' => 'array',
        'mentions' => 'array',
        'links' => 'array',
        'media_urls' => 'array',
        'ai_analysis' => 'array',
        'is_top_performer' => 'boolean',
        'is_approved' => 'boolean',
        'is_active' => 'boolean',
        'engagement_rate' => 'float',
        'performance_score' => 'float',
        'posted_at' => 'datetime',
        'analyzed_at' => 'datetime',
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
     * Source types
     */
    public const SOURCE_TYPES = [
        'manual' => 'Qo\'lda kiritilgan',
        'instagram' => 'Instagram',
        'telegram' => 'Telegram',
        'facebook' => 'Facebook',
        'imported' => 'Import qilingan',
    ];

    /**
     * Scope: Top performers only
     */
    public function scopeTopPerformers($query)
    {
        return $query->where('is_top_performer', true);
    }

    /**
     * Scope: By content type
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('content_type', $type);
    }

    /**
     * Scope: By purpose
     */
    public function scopeForPurpose($query, string $purpose)
    {
        return $query->where('purpose', $purpose);
    }

    /**
     * Scope: By channel
     */
    public function scopeForChannel($query, string $channel)
    {
        return $query->where('target_channel', $channel);
    }

    /**
     * Scope: Active and approved
     */
    public function scopeUsable($query)
    {
        return $query->where('is_active', true)->where('is_approved', true);
    }

    /**
     * Scope: Ordered by performance
     */
    public function scopeOrderByPerformance($query)
    {
        return $query->orderByDesc('performance_score');
    }

    /**
     * Calculate performance score
     */
    public function calculatePerformanceScore(): float
    {
        // Weighted scoring
        $score = 0;

        // Engagement rate (0-40 points)
        $score += min(40, $this->engagement_rate * 10);

        // Likes (0-20 points)
        $score += min(20, $this->likes_count / 50);

        // Comments (0-20 points) - comments are more valuable
        $score += min(20, $this->comments_count / 10);

        // Shares (0-10 points)
        $score += min(10, $this->shares_count / 5);

        // Saves (0-10 points)
        $score += min(10, $this->saves_count / 5);

        return round($score, 2);
    }

    /**
     * Update performance score
     */
    public function updatePerformanceScore(): self
    {
        $this->performance_score = $this->calculatePerformanceScore();
        $this->is_top_performer = $this->performance_score >= 50;
        $this->save();

        return $this;
    }

    /**
     * Clean content (remove hashtags, mentions, links)
     */
    public function cleanContent(): string
    {
        $content = $this->content;

        // Remove hashtags
        $content = preg_replace('/#\w+/u', '', $content);

        // Remove mentions
        $content = preg_replace('/@\w+/u', '', $content);

        // Remove URLs
        $content = preg_replace('/https?:\/\/\S+/i', '', $content);

        // Clean up extra whitespace
        $content = preg_replace('/\s+/', ' ', $content);

        return trim($content);
    }

    /**
     * Extract hashtags from content
     */
    public static function extractHashtags(string $content): array
    {
        preg_match_all('/#(\w+)/u', $content, $matches);

        return $matches[1] ?? [];
    }

    /**
     * Extract mentions from content
     */
    public static function extractMentions(string $content): array
    {
        preg_match_all('/@(\w+)/u', $content, $matches);

        return $matches[1] ?? [];
    }

    /**
     * Get AI analysis value
     */
    public function getAnalysisValue(string $key, $default = null)
    {
        return data_get($this->ai_analysis, $key, $default);
    }

    /**
     * Build context for AI generation
     */
    public function buildContextForGeneration(): string
    {
        $context = "Namuna post:\n";
        $context .= "---\n";
        $context .= $this->content_cleaned ?? $this->cleanContent();
        $context .= "\n---\n";
        $context .= "Engagement: {$this->engagement_rate}%\n";

        if ($analysis = $this->ai_analysis) {
            if (isset($analysis['tone'])) {
                $context .= "Ton: {$analysis['tone']}\n";
            }
            if (isset($analysis['hook_type'])) {
                $context .= "Hook turi: {$analysis['hook_type']}\n";
            }
        }

        return $context;
    }
}
