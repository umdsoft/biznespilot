<?php

namespace App\Models;

use App\Traits\BelongsToBusiness;
use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductInsight extends Model
{
    use BelongsToBusiness, HasUuid;

    protected $fillable = [
        'business_id',
        'product_analysis_id',
        'type',
        'priority',
        'title',
        'description',
        'action_text',
        'data',
        'status',
        'expires_at',
    ];

    protected $casts = [
        'data' => 'array',
        'expires_at' => 'datetime',
    ];

    public function productAnalysis(): BelongsTo
    {
        return $this->belongsTo(ProductAnalysis::class);
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active')
            ->where(function ($q) {
                $q->whereNull('expires_at')->orWhere('expires_at', '>', now());
            });
    }

    public function scopeByPriority($query)
    {
        return $query->orderByRaw("FIELD(priority, 'high', 'medium', 'low')");
    }
}
