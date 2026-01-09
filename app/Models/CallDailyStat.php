<?php

namespace App\Models;

use App\Traits\BelongsToBusiness;
use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CallDailyStat extends Model
{
    use BelongsToBusiness, HasUuid;

    protected $fillable = [
        'business_id',
        'stat_date',
        'total_calls',
        'outbound_calls',
        'inbound_calls',
        'answered_calls',
        'missed_calls',
        'failed_calls',
        'total_duration',
        'avg_duration',
    ];

    protected $casts = [
        'stat_date' => 'date',
    ];

    /**
     * Get the business that owns this stat
     */
    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    /**
     * Calculate answer rate percentage
     */
    public function getAnswerRateAttribute(): float
    {
        if ($this->total_calls === 0) {
            return 0;
        }

        return round(($this->answered_calls / $this->total_calls) * 100, 1);
    }

    /**
     * Get formatted total duration (HH:MM:SS)
     */
    public function getFormattedTotalDurationAttribute(): string
    {
        $hours = floor($this->total_duration / 3600);
        $minutes = floor(($this->total_duration % 3600) / 60);
        $seconds = $this->total_duration % 60;

        return sprintf('%02d:%02d:%02d', $hours, $minutes, $seconds);
    }

    /**
     * Get formatted average duration (MM:SS)
     */
    public function getFormattedAvgDurationAttribute(): string
    {
        $minutes = floor($this->avg_duration / 60);
        $seconds = $this->avg_duration % 60;

        return sprintf('%02d:%02d', $minutes, $seconds);
    }

    /**
     * Scope: Get stats for a specific date range
     */
    public function scopeDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('stat_date', [$startDate, $endDate]);
    }

    /**
     * Scope: Get today's stats
     */
    public function scopeToday($query)
    {
        return $query->where('stat_date', today());
    }

    /**
     * Update stats when a call is logged
     */
    public static function recordCall(string $businessId, CallLog $call): void
    {
        $stat = self::firstOrCreate(
            [
                'business_id' => $businessId,
                'stat_date' => today(),
            ],
            [
                'total_calls' => 0,
                'outbound_calls' => 0,
                'inbound_calls' => 0,
                'answered_calls' => 0,
                'missed_calls' => 0,
                'failed_calls' => 0,
                'total_duration' => 0,
                'avg_duration' => 0,
            ]
        );

        $stat->increment('total_calls');

        if ($call->isOutbound()) {
            $stat->increment('outbound_calls');
        } else {
            $stat->increment('inbound_calls');
        }

        if ($call->wasAnswered()) {
            $stat->increment('answered_calls');
            $stat->increment('total_duration', $call->duration);

            // Recalculate average
            if ($stat->answered_calls > 0) {
                $stat->avg_duration = (int) ($stat->total_duration / $stat->answered_calls);
                $stat->save();
            }
        } elseif (in_array($call->status, [CallLog::STATUS_MISSED, CallLog::STATUS_NO_ANSWER])) {
            $stat->increment('missed_calls');
        } elseif ($call->status === CallLog::STATUS_FAILED) {
            $stat->increment('failed_calls');
        }
    }
}
