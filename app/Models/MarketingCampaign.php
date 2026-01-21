<?php

namespace App\Models;

use App\Traits\BelongsToBusiness;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MarketingCampaign extends Model
{
    use HasUuids, BelongsToBusiness, SoftDeletes;

    protected $fillable = [
        'business_id',
        'channel_id',
        'name',
        'description',
        'campaign_type',
        'start_date',
        'end_date',
        'budget_planned',
        'budget_spent',
        'impressions',
        'clicks',
        'leads_generated',
        'deals_closed',
        'revenue_generated',
        'cpl',
        'cpa',
        'roi',
        'status',
        'responsible_user_id',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'budget_planned' => 'decimal:2',
        'budget_spent' => 'decimal:2',
        'revenue_generated' => 'decimal:2',
        'cpl' => 'decimal:2',
        'cpa' => 'decimal:2',
        'roi' => 'decimal:2',
    ];

    protected static function booted(): void
    {
        static::saving(function ($campaign) {
            $campaign->calculateMetrics();
        });
    }

    // Relationships
    public function channel(): BelongsTo
    {
        return $this->belongsTo(MarketingChannel::class, 'channel_id');
    }

    public function responsibleUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'responsible_user_id');
    }

    public function contentItems(): HasMany
    {
        return $this->hasMany(ContentCalendar::class, 'campaign_id');
    }

    public function leadFlows(): HasMany
    {
        return $this->hasMany(LeadFlowTracking::class, 'marketing_campaign_id');
    }

    // Calculate metrics
    public function calculateMetrics(): void
    {
        // CPL (Cost Per Lead)
        $this->cpl = $this->leads_generated > 0
            ? round($this->budget_spent / $this->leads_generated, 2)
            : 0;

        // CPA (Cost Per Acquisition)
        $this->cpa = $this->deals_closed > 0
            ? round($this->budget_spent / $this->deals_closed, 2)
            : 0;

        // ROI
        $this->roi = $this->budget_spent > 0
            ? round((($this->revenue_generated - $this->budget_spent) / $this->budget_spent) * 100, 2)
            : 0;
    }

    // Get CTR (Click Through Rate)
    public function getCtrAttribute(): float
    {
        if ($this->impressions == 0) return 0;
        return round(($this->clicks / $this->impressions) * 100, 2);
    }

    // Get Lead Conversion Rate
    public function getLeadConversionRateAttribute(): float
    {
        if ($this->clicks == 0) return 0;
        return round(($this->leads_generated / $this->clicks) * 100, 2);
    }

    // Get Deal Conversion Rate
    public function getDealConversionRateAttribute(): float
    {
        if ($this->leads_generated == 0) return 0;
        return round(($this->deals_closed / $this->leads_generated) * 100, 2);
    }

    // Get Budget Usage Percent
    public function getBudgetUsagePercentAttribute(): float
    {
        if ($this->budget_planned == 0) return 0;
        return round(($this->budget_spent / $this->budget_planned) * 100, 2);
    }

    // Check if over budget
    public function getIsOverBudgetAttribute(): bool
    {
        return $this->budget_spent > $this->budget_planned;
    }

    // Get status color
    public function getStatusColorAttribute(): string
    {
        return match($this->status) {
            'draft' => 'gray',
            'active' => 'green',
            'paused' => 'yellow',
            'completed' => 'blue',
            default => 'gray',
        };
    }

    // Get ROI color
    public function getRoiColorAttribute(): string
    {
        if ($this->roi >= 100) return 'green';
        if ($this->roi >= 50) return 'blue';
        if ($this->roi >= 0) return 'yellow';
        return 'red';
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeForChannel($query, string $channelId)
    {
        return $query->where('channel_id', $channelId);
    }

    public function scopeRunning($query)
    {
        return $query->where('start_date', '<=', now())
                     ->where(function($q) {
                         $q->whereNull('end_date')
                           ->orWhere('end_date', '>=', now());
                     });
    }

    public function scopeProfitable($query)
    {
        return $query->where('roi', '>', 0);
    }
}
