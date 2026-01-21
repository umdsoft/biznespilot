<?php

namespace App\Models;

use App\Traits\BelongsToBusiness;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Sales Funnel Stages - Track deal progression
 */
class SalesFunnelStage extends Model
{
    use HasUuids, BelongsToBusiness;

    protected $fillable = [
        'business_id',
        'name',
        'code',
        'order',
        'color',
        'is_active',
        'is_won_stage',
        'is_lost_stage',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_won_stage' => 'boolean',
        'is_lost_stage' => 'boolean',
    ];

    // Relationships
    public function lostDeals(): HasMany
    {
        return $this->hasMany(LostDeal::class, 'lost_at_stage_id');
    }

    // Get stage type
    public function getStageTypeAttribute(): string
    {
        if ($this->is_won_stage) return 'won';
        if ($this->is_lost_stage) return 'lost';
        return 'progress';
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('order');
    }

    public function scopeWinStages($query)
    {
        return $query->where('is_won_stage', true);
    }

    public function scopeLostStages($query)
    {
        return $query->where('is_lost_stage', true);
    }

    public function scopeProgressStages($query)
    {
        return $query->where('is_won_stage', false)
                     ->where('is_lost_stage', false);
    }
}
