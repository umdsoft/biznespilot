<?php

namespace App\Models;

use App\Traits\BelongsToBusiness;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class PainPointContentMap extends Model
{
    use BelongsToBusiness, HasUuids;

    protected $fillable = [
        'business_id',
        'pain_point_category',
        'pain_point_text',
        'extracted_keywords',
        'suggested_topics',
        'suggested_content_types',
        'suggested_hooks',
        'relevance_score',
        'times_used',
        'avg_engagement_rate',
        'is_active',
    ];

    protected $casts = [
        'extracted_keywords' => 'array',
        'suggested_topics' => 'array',
        'suggested_content_types' => 'array',
        'suggested_hooks' => 'array',
        'relevance_score' => 'decimal:2',
        'avg_engagement_rate' => 'decimal:4',
        'is_active' => 'boolean',
    ];

    public const CATEGORIES = [
        'frustrations' => 'Muammolar',
        'fears' => "Qo'rquvlar",
        'dreams' => 'Orzular',
        'daily_routine' => 'Kundalik hayot',
        'happiness_triggers' => 'Baxt omillari',
    ];

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeForCategory($query, string $category)
    {
        return $query->where('pain_point_category', $category);
    }

    public function scopeTopRelevant($query, int $limit = 10)
    {
        return $query->active()
            ->orderByDesc('relevance_score')
            ->limit($limit);
    }

    // Helpers
    public function recordUsage(float $engagementRate = 0): void
    {
        $newCount = $this->times_used + 1;
        $newAvg = $this->times_used > 0
            ? (($this->avg_engagement_rate * $this->times_used) + $engagementRate) / $newCount
            : $engagementRate;

        $this->update([
            'times_used' => $newCount,
            'avg_engagement_rate' => round($newAvg, 4),
        ]);
    }
}
