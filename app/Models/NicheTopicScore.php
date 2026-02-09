<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NicheTopicScore extends Model
{
    use HasUuids;

    protected $fillable = [
        'industry_id',
        'topic',
        'category',
        'content_type',
        'total_posts',
        'total_engagement',
        'avg_engagement_rate',
        'avg_reach',
        'avg_saves',
        'avg_shares',
        'score',
        'trend',
        'contributing_businesses',
        'sample_hashtags',
        'best_posting_times',
        'last_calculated_at',
    ];

    protected $casts = [
        'avg_engagement_rate' => 'decimal:4',
        'score' => 'decimal:2',
        'contributing_businesses' => 'array',
        'sample_hashtags' => 'array',
        'best_posting_times' => 'array',
        'last_calculated_at' => 'datetime',
    ];

    public const CATEGORIES = [
        'educational' => "Ta'limiy",
        'promotional' => 'Reklama',
        'engagement' => 'Faollashtirish',
        'behind_scenes' => 'Sahna ortida',
        'testimonial' => 'Mijoz fikrlari',
        'product' => 'Mahsulot',
        'tips' => 'Maslahatlar',
        'motivation' => 'Motivatsiya',
        'pain_point' => "Og'riq nuqtasi",
    ];

    public const TRENDS = [
        'rising' => "O'sish",
        'stable' => 'Barqaror',
        'falling' => 'Tushish',
    ];

    // Relationships
    public function industry(): BelongsTo
    {
        return $this->belongsTo(Industry::class);
    }

    // Scopes
    public function scopeForIndustry($query, string $industryId)
    {
        return $query->where('industry_id', $industryId);
    }

    public function scopeTopPerformers($query, int $limit = 10)
    {
        return $query->where('total_posts', '>=', 3)
            ->orderByDesc('score')
            ->limit($limit);
    }

    public function scopeRising($query)
    {
        return $query->where('trend', 'rising');
    }

    public function scopeOfType($query, string $contentType)
    {
        return $query->where('content_type', $contentType);
    }

    public function scopeInCategory($query, string $category)
    {
        return $query->where('category', $category);
    }

    // Helpers

    /**
     * Niche Score ni qayta hisoblash
     * Formula: (engagement_rate * 35) + (saves_ratio * 25) + (shares_ratio * 20) + (reach_factor * 20)
     */
    public function recalculateScore(): void
    {
        if ($this->total_posts < 1) {
            $this->update(['score' => 0]);

            return;
        }

        $engagementScore = min($this->avg_engagement_rate * 7, 35);
        $savesScore = $this->total_posts > 0
            ? min(($this->avg_saves / max($this->avg_reach, 1)) * 100 * 25, 25)
            : 0;
        $sharesScore = $this->total_posts > 0
            ? min(($this->avg_shares / max($this->avg_reach, 1)) * 100 * 20, 20)
            : 0;
        $reachScore = min(($this->avg_reach / 1000) * 2, 20);

        $totalScore = round($engagementScore + $savesScore + $sharesScore + $reachScore, 2);

        $this->update([
            'score' => min($totalScore, 100),
            'last_calculated_at' => now(),
        ]);
    }

    /**
     * Trend ni yangilash (oxirgi 30 kunlik o'zgarish)
     */
    public function updateTrend(float $previousScore): void
    {
        $change = $this->score - $previousScore;

        $trend = match (true) {
            $change > 5 => 'rising',
            $change < -5 => 'falling',
            default => 'stable',
        };

        $this->update(['trend' => $trend]);
    }
}
