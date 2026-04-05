<?php

namespace App\Models;

use App\Traits\BelongsToBusiness;
use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProductAnalysis extends Model
{
    use BelongsToBusiness, HasUuid, SoftDeletes;

    protected $fillable = [
        'business_id',
        'product_id',
        'name',
        'short_desc',
        'category',
        'pricing_model',
        'price',
        'cost',
        'advantages',
        'weaknesses',
        'target_audience',
        'features',
        'usp_score',
        'competition',
        'marketing_status',
        'life_cycle_stage',
        'market_avg_price',
        'advantages_count',
        'weaknesses_count',
        'trend_alignment_score',
        'competitor_position_score',
        'metadata',
        'ai_analysis',
        'ai_analyzed_at',
        'sales_summary',
        'sales_updated_at',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'cost' => 'decimal:2',
        'market_avg_price' => 'decimal:2',
        'metadata' => 'array',
        'features' => 'array',
        'ai_analysis' => 'array',
        'sales_summary' => 'array',
        'ai_analyzed_at' => 'datetime',
        'sales_updated_at' => 'datetime',
    ];

    public function competitorMappings(): HasMany
    {
        return $this->hasMany(ProductCompetitorMapping::class);
    }

    public function insights(): HasMany
    {
        return $this->hasMany(ProductInsight::class);
    }

    public function activeInsights(): HasMany
    {
        return $this->insights()->active()->byPriority();
    }

    /**
     * Margin foizini hisoblash
     */
    public function getMarginPercentAttribute(): float
    {
        if (!$this->price || $this->price == 0) return 0;
        if (!$this->cost) return 100;
        return round((($this->price - $this->cost) / $this->price) * 100, 1);
    }

    /**
     * AI tahlil eskirganmi (7 kundan oshgan)
     */
    public function getAiStaleAttribute(): bool
    {
        if (!$this->ai_analyzed_at) return true;
        return $this->ai_analyzed_at->diffInDays(now()) > 7;
    }

    /**
     * Kompozit sog'lik bali
     */
    public function getHealthScoreAttribute(): int
    {
        $scores = array_filter([
            $this->usp_score,
            $this->trend_alignment_score,
            $this->competitor_position_score,
        ]);

        return count($scores) > 0 ? (int) round(array_sum($scores) / count($scores)) : 0;
    }
}
