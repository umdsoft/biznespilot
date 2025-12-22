<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SalesMetricsHistory extends Model
{
    use HasUuids;

    protected $table = 'sales_metrics_history';

    protected $fillable = [
        'business_id',
        'sales_metrics_id',
        'monthly_lead_volume',
        'lead_sources',
        'lead_quality',
        'monthly_sales_volume',
        'avg_deal_size',
        'sales_cycle',
        'sales_team_type',
        'sales_tools',
        'sales_challenges',
        'additional_data',
        'recorded_at',
        'change_type',
        'note',
    ];

    protected $casts = [
        'lead_sources' => 'array',
        'sales_tools' => 'array',
        'additional_data' => 'array',
        'recorded_at' => 'datetime',
    ];

    /**
     * Biznes bilan bog'lanish
     */
    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    /**
     * Joriy metrika bilan bog'lanish
     */
    public function salesMetrics(): BelongsTo
    {
        return $this->belongsTo(SalesMetrics::class);
    }

    /**
     * Scope: Biznes bo'yicha
     */
    public function scopeForBusiness($query, $businessId)
    {
        return $query->where('business_id', $businessId);
    }

    /**
     * Scope: Sana oralig'i bo'yicha
     */
    public function scopeDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('recorded_at', [$startDate, $endDate]);
    }

    /**
     * Scope: Oxirgi N kun
     */
    public function scopeLastDays($query, int $days)
    {
        return $query->where('recorded_at', '>=', now()->subDays($days));
    }
}
