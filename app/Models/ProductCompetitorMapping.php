<?php

namespace App\Models;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductCompetitorMapping extends Model
{
    use HasUuid;

    protected $fillable = [
        'product_analysis_id',
        'competitor_product_id',
        'similarity_score',
        'comparison_notes',
        'mapped_by',
    ];

    public function productAnalysis(): BelongsTo
    {
        return $this->belongsTo(ProductAnalysis::class);
    }

    public function competitorProduct(): BelongsTo
    {
        return $this->belongsTo(CompetitorProduct::class);
    }
}
