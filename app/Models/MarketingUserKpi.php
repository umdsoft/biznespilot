<?php

namespace App\Models;

use App\Traits\BelongsToBusiness;
use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MarketingUserKpi extends Model
{
    use HasUuid, BelongsToBusiness;

    protected $table = 'marketing_user_kpis';

    protected $fillable = [
        'business_id',
        'user_id',
        'date',
        'period_type',
        'leads_created',
        'leads_qualified',
        'leads_converted',
        'content_published',
        'campaigns_launched',
        'campaigns_managed',
        'spend_managed',
        'revenue_attributed',
        'tasks_completed',
        'reports_generated',
        'performance_score',
        'efficiency_score',
        'overall_score',
    ];

    protected $casts = [
        'date' => 'date',
        'leads_created' => 'integer',
        'leads_qualified' => 'integer',
        'leads_converted' => 'integer',
        'content_published' => 'integer',
        'campaigns_launched' => 'integer',
        'campaigns_managed' => 'integer',
        'spend_managed' => 'decimal:2',
        'revenue_attributed' => 'decimal:2',
        'tasks_completed' => 'integer',
        'reports_generated' => 'integer',
        'performance_score' => 'decimal:2',
        'efficiency_score' => 'decimal:2',
        'overall_score' => 'decimal:2',
    ];

    // RELATIONSHIPS

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // SCOPES

    public function scopeDaily(Builder $query): Builder
    {
        return $query->where('period_type', 'daily');
    }

    public function scopeWeekly(Builder $query): Builder
    {
        return $query->where('period_type', 'weekly');
    }

    public function scopeMonthly(Builder $query): Builder
    {
        return $query->where('period_type', 'monthly');
    }

    public function scopeForUser(Builder $query, $userId): Builder
    {
        return $query->where('user_id', $userId);
    }

    public function scopeForDateRange(Builder $query, $from, $to): Builder
    {
        return $query->whereBetween('date', [$from, $to]);
    }

    // HELPERS

    public function getRoiAttribute(): float
    {
        if ($this->spend_managed == 0) {
            return 0;
        }

        return round((($this->revenue_attributed - $this->spend_managed) / $this->spend_managed) * 100, 2);
    }

    public function getConversionRateAttribute(): float
    {
        if ($this->leads_created == 0) {
            return 0;
        }

        return round(($this->leads_converted / $this->leads_created) * 100, 2);
    }
}
