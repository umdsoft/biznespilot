<?php

namespace App\Models;

use App\Traits\BelongsToBusiness;
use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InstagramHashtagStat extends Model
{
    use BelongsToBusiness, HasUuid;

    protected $fillable = [
        'account_id',
        'business_id',
        'hashtag',
        'usage_count',
        'total_reach',
        'total_impressions',
        'total_engagement',
        'avg_engagement_rate',
        'last_used_at',
    ];

    protected $casts = [
        'avg_engagement_rate' => 'decimal:4',
        'last_used_at' => 'datetime',
    ];

    public function instagramAccount(): BelongsTo
    {
        return $this->belongsTo(InstagramAccount::class, 'account_id');
    }

    public function getAvgReachPerUseAttribute(): float
    {
        if ($this->usage_count <= 0) {
            return 0;
        }

        return round($this->total_reach / $this->usage_count, 0);
    }

    public function getAvgEngagementPerUseAttribute(): float
    {
        if ($this->usage_count <= 0) {
            return 0;
        }

        return round($this->total_engagement / $this->usage_count, 0);
    }

    public function scopeTopPerforming($query, int $limit = 10)
    {
        return $query->orderByDesc('avg_engagement_rate')->limit($limit);
    }
}
