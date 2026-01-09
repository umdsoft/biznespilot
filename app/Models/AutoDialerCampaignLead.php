<?php

namespace App\Models;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AutoDialerCampaignLead extends Model
{
    use HasUuid;

    protected $fillable = [
        'campaign_id',
        'lead_id',
        'status',
        'attempts',
        'last_result',
        'last_duration',
        'last_called_at',
        'next_retry_at',
    ];

    protected $casts = [
        'last_called_at' => 'datetime',
        'next_retry_at' => 'datetime',
    ];

    /**
     * Status constants
     */
    public const STATUS_PENDING = 'pending';
    public const STATUS_CALLING = 'calling';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_FAILED = 'failed';
    public const STATUS_SKIPPED = 'skipped';

    /**
     * Result constants
     */
    public const RESULT_ANSWERED = 'answered';
    public const RESULT_NO_ANSWER = 'no_answer';
    public const RESULT_BUSY = 'busy';
    public const RESULT_FAILED = 'failed';

    /**
     * Get the campaign
     */
    public function campaign(): BelongsTo
    {
        return $this->belongsTo(AutoDialerCampaign::class, 'campaign_id');
    }

    /**
     * Get the lead
     */
    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class);
    }

    /**
     * Scope: Only pending leads
     */
    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    /**
     * Scope: Ready to call (pending or retry time passed)
     */
    public function scopeReadyToCall($query)
    {
        return $query->where(function ($q) {
            $q->where('status', self::STATUS_PENDING)
                ->orWhere(function ($sq) {
                    $sq->where('status', self::STATUS_FAILED)
                        ->where('next_retry_at', '<=', now());
                });
        });
    }

    /**
     * Check if can retry
     */
    public function canRetry(int $maxAttempts): bool
    {
        return $this->attempts < $maxAttempts
            && in_array($this->status, [self::STATUS_PENDING, self::STATUS_FAILED])
            && (!$this->next_retry_at || $this->next_retry_at <= now());
    }

    /**
     * Record call attempt
     */
    public function recordAttempt(string $result, int $duration = 0, int $retryDelay = 60): void
    {
        $this->increment('attempts');

        $data = [
            'last_result' => $result,
            'last_duration' => $duration,
            'last_called_at' => now(),
        ];

        if ($result === self::RESULT_ANSWERED) {
            $data['status'] = self::STATUS_COMPLETED;
            $data['next_retry_at'] = null;
        } else {
            $data['status'] = self::STATUS_FAILED;
            $data['next_retry_at'] = now()->addMinutes($retryDelay);
        }

        $this->update($data);
    }

    /**
     * Mark as skipped
     */
    public function skip(string $reason = null): void
    {
        $this->update([
            'status' => self::STATUS_SKIPPED,
            'last_result' => $reason ?? 'skipped',
        ]);
    }

    /**
     * Get formatted duration
     */
    public function getFormattedDurationAttribute(): string
    {
        $minutes = floor($this->last_duration / 60);
        $seconds = $this->last_duration % 60;

        return sprintf('%02d:%02d', $minutes, $seconds);
    }

    /**
     * Get result label in Uzbek
     */
    public function getResultLabelAttribute(): string
    {
        $labels = [
            self::RESULT_ANSWERED => 'Javob berildi',
            self::RESULT_NO_ANSWER => 'Javob yo\'q',
            self::RESULT_BUSY => 'Band',
            self::RESULT_FAILED => 'Xatolik',
            'skipped' => 'O\'tkazib yuborildi',
        ];

        return $labels[$this->last_result] ?? $this->last_result ?? '-';
    }
}
