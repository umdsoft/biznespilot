<?php

namespace App\Models;

use App\Traits\BelongsToBusiness;
use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class MarketingTarget extends Model
{
    use HasUuid, BelongsToBusiness, SoftDeletes;

    protected $fillable = [
        'business_id',
        'period_start',
        'period_end',
        'period_type',
        'channel_id',
        'campaign_id',
        'user_id',
        'leads_target',
        'mql_target',
        'sql_target',
        'won_target',
        'spend_budget',
        'revenue_target',
        'cpl_target',
        'roas_target',
        'roi_target',
        'conversion_target',
        'status',
        'created_by',
    ];

    protected $casts = [
        'period_start' => 'date',
        'period_end' => 'date',
        'leads_target' => 'integer',
        'mql_target' => 'integer',
        'sql_target' => 'integer',
        'won_target' => 'integer',
        'spend_budget' => 'decimal:2',
        'revenue_target' => 'decimal:2',
        'cpl_target' => 'decimal:2',
        'roas_target' => 'decimal:4',
        'roi_target' => 'decimal:4',
        'conversion_target' => 'decimal:2',
    ];

    // RELATIONSHIPS

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    public function channel(): BelongsTo
    {
        return $this->belongsTo(MarketingChannel::class, 'channel_id');
    }

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(Campaign::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function createdByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function bonuses(): HasMany
    {
        return $this->hasMany(MarketingBonus::class, 'target_id');
    }

    // SCOPES

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 'active');
    }

    public function scopeForPeriod(Builder $query, $date): Builder
    {
        return $query->where('period_start', '<=', $date)
                     ->where('period_end', '>=', $date);
    }

    public function scopeOverall(Builder $query): Builder
    {
        return $query->whereNull('channel_id')
                     ->whereNull('campaign_id')
                     ->whereNull('user_id');
    }

    public function scopeForUser(Builder $query, $userId): Builder
    {
        return $query->where('user_id', $userId);
    }

    public function scopeForChannel(Builder $query, $channelId): Builder
    {
        return $query->where('channel_id', $channelId);
    }

    public function scopeForCampaign(Builder $query, $campaignId): Builder
    {
        return $query->where('campaign_id', $campaignId);
    }

    public function scopeMonthly(Builder $query): Builder
    {
        return $query->where('period_type', 'monthly');
    }

    // HELPERS

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function isOverall(): bool
    {
        return !$this->channel_id && !$this->campaign_id && !$this->user_id;
    }

    public function getDaysRemaining(): int
    {
        return max(0, now()->diffInDays($this->period_end, false));
    }

    public function getDaysElapsed(): int
    {
        return now()->diffInDays($this->period_start);
    }

    public function getTotalDays(): int
    {
        return $this->period_start->diffInDays($this->period_end);
    }

    public function getProgressPercent(): float
    {
        $total = $this->getTotalDays();
        if ($total === 0) {
            return 100;
        }

        return min(100, round(($this->getDaysElapsed() / $total) * 100, 2));
    }
}
