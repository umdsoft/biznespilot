<?php

namespace App\Models;

use App\Traits\BelongsToBusiness;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SalesActivity extends Model
{
    use HasUuids, BelongsToBusiness;

    protected $fillable = [
        'business_id',
        'user_id',
        'sales_target_id',
        'activity_date',
        'calls_made',
        'calls_answered',
        'meetings_scheduled',
        'meetings_held',
        'proposals_sent',
        'deals_closed',
        'revenue_generated',
        'talk_time_minutes',
        'notes',
    ];

    protected $casts = [
        'activity_date' => 'date',
        'calls_made' => 'integer',
        'calls_answered' => 'integer',
        'meetings_scheduled' => 'integer',
        'meetings_held' => 'integer',
        'proposals_sent' => 'integer',
        'deals_closed' => 'integer',
        'revenue_generated' => 'decimal:2',
        'talk_time_minutes' => 'integer',
        'notes' => 'array',
    ];

    // Relationships
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function salesTarget(): BelongsTo
    {
        return $this->belongsTo(SalesTarget::class);
    }

    // Calculated attributes
    public function getCallAnswerRateAttribute(): float
    {
        if ($this->calls_made == 0) return 0;
        return round(($this->calls_answered / $this->calls_made) * 100, 2);
    }

    public function getMeetingConversionRateAttribute(): float
    {
        if ($this->meetings_scheduled == 0) return 0;
        return round(($this->meetings_held / $this->meetings_scheduled) * 100, 2);
    }

    public function getProposalToDealsRateAttribute(): float
    {
        if ($this->proposals_sent == 0) return 0;
        return round(($this->deals_closed / $this->proposals_sent) * 100, 2);
    }

    public function getAverageCallDurationAttribute(): float
    {
        if ($this->calls_answered == 0) return 0;
        return round($this->talk_time_minutes / $this->calls_answered, 2);
    }

    // Scopes
    public function scopeForUser($query, string $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeForDate($query, $date)
    {
        return $query->whereDate('activity_date', $date);
    }

    public function scopeForDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('activity_date', [$startDate, $endDate]);
    }

    public function scopeToday($query)
    {
        return $query->whereDate('activity_date', now());
    }

    public function scopeThisWeek($query)
    {
        return $query->whereBetween('activity_date', [now()->startOfWeek(), now()->endOfWeek()]);
    }

    public function scopeThisMonth($query)
    {
        return $query->whereBetween('activity_date', [now()->startOfMonth(), now()->endOfMonth()]);
    }
}
