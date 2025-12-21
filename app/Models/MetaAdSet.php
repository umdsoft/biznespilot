<?php

namespace App\Models;

use App\Traits\BelongsToBusiness;
use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MetaAdSet extends Model
{
    use BelongsToBusiness, HasUuid;

    protected $fillable = [
        'ad_account_id',
        'campaign_id',
        'meta_campaign_id',
        'business_id',
        'meta_adset_id',
        'name',
        'status',
        'effective_status',
        'optimization_goal',
        'billing_event',
        'bid_strategy',
        'daily_budget',
        'lifetime_budget',
        'bid_amount',
        'targeting',
        'start_time',
        'end_time',
        'metadata',
    ];

    protected $casts = [
        'daily_budget' => 'decimal:2',
        'lifetime_budget' => 'decimal:2',
        'bid_amount' => 'decimal:4',
        'targeting' => 'array',
        'metadata' => 'array',
        'start_time' => 'datetime',
        'end_time' => 'datetime',
    ];

    public function adAccount(): BelongsTo
    {
        return $this->belongsTo(MetaAdAccount::class, 'ad_account_id');
    }

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(MetaCampaign::class, 'campaign_id');
    }

    public function ads(): HasMany
    {
        return $this->hasMany(MetaAd::class, 'adset_id');
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'ACTIVE');
    }

    public function getAgeRangeAttribute(): ?array
    {
        $targeting = $this->targeting ?? [];
        return [
            'min' => $targeting['age_min'] ?? null,
            'max' => $targeting['age_max'] ?? null,
        ];
    }

    public function getGendersAttribute(): array
    {
        $targeting = $this->targeting ?? [];
        $genders = $targeting['genders'] ?? [1, 2];

        return collect($genders)->map(fn($g) => match ($g) {
            1 => 'male',
            2 => 'female',
            default => 'unknown',
        })->toArray();
    }
}
