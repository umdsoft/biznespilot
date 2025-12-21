<?php

namespace App\Models;

use App\Traits\BelongsToBusiness;
use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MetaCampaign extends Model
{
    use BelongsToBusiness, HasUuid;

    protected $fillable = [
        'ad_account_id',
        'business_id',
        'meta_campaign_id',
        'name',
        'objective',
        'status',
        'effective_status',
        'daily_budget',
        'lifetime_budget',
        'budget_remaining',
        'start_time',
        'stop_time',
        'metadata',
    ];

    protected $casts = [
        'daily_budget' => 'decimal:2',
        'lifetime_budget' => 'decimal:2',
        'metadata' => 'array',
        'start_time' => 'datetime',
        'stop_time' => 'datetime',
    ];

    public function adAccount(): BelongsTo
    {
        return $this->belongsTo(MetaAdAccount::class, 'ad_account_id');
    }

    public function adSets(): HasMany
    {
        return $this->hasMany(MetaAdSet::class, 'campaign_id');
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'ACTIVE');
    }

    public function scopeByObjective($query, string $objective)
    {
        return $query->where('objective', $objective);
    }

    public function getObjectiveLabelAttribute(): string
    {
        return match ($this->objective) {
            'OUTCOME_AWARENESS' => 'Awareness',
            'OUTCOME_TRAFFIC' => 'Traffic',
            'OUTCOME_ENGAGEMENT' => 'Engagement',
            'OUTCOME_LEADS' => 'Leads',
            'OUTCOME_APP_PROMOTION' => 'App Promotion',
            'OUTCOME_SALES' => 'Sales',
            default => $this->objective ?? 'Unknown',
        };
    }
}
