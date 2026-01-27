<?php

namespace App\Models;

use App\Traits\BelongsToBusiness;
use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class CallLog extends Model
{
    use BelongsToBusiness, HasUuid;

    protected $fillable = [
        'business_id',
        'lead_id',
        'user_id',
        'provider',
        'provider_call_id',
        'direction',
        'from_number',
        'to_number',
        'status',
        'duration',
        'conversation', // Actual talk time in seconds (0 if call was not answered)
        'wait_time',
        'recording_url',
        'notes',
        'metadata',
        'started_at',
        'answered_at',
        'ended_at',
        'analysis_status',
        'analysis_error',
    ];

    protected $casts = [
        'metadata' => 'array',
        'started_at' => 'datetime',
        'answered_at' => 'datetime',
        'ended_at' => 'datetime',
    ];

    /**
     * Status constants
     */
    public const STATUS_INITIATED = 'initiated';

    public const STATUS_RINGING = 'ringing';

    public const STATUS_ANSWERED = 'answered';

    public const STATUS_COMPLETED = 'completed';

    public const STATUS_FAILED = 'failed';

    public const STATUS_MISSED = 'missed';

    public const STATUS_BUSY = 'busy';

    public const STATUS_NO_ANSWER = 'no_answer';

    public const STATUS_CANCELLED = 'cancelled';

    /**
     * Direction constants
     */
    public const DIRECTION_INBOUND = 'inbound';

    public const DIRECTION_OUTBOUND = 'outbound';

    /**
     * Provider constants
     */
    public const PROVIDER_PBX = 'pbx';

    public const PROVIDER_SIPUNI = 'sipuni';

    /**
     * Analysis status constants
     */
    public const ANALYSIS_STATUS_PENDING = 'pending';

    public const ANALYSIS_STATUS_QUEUED = 'queued';

    public const ANALYSIS_STATUS_TRANSCRIBING = 'transcribing';

    public const ANALYSIS_STATUS_ANALYZING = 'analyzing';

    public const ANALYSIS_STATUS_COMPLETED = 'completed';

    public const ANALYSIS_STATUS_FAILED = 'failed';

    /**
     * Get the business that owns this call
     */
    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    /**
     * Get the lead associated with this call
     */
    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class);
    }

    /**
     * Get the user who made/received this call
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the analysis for this call
     */
    public function analysis(): HasOne
    {
        return $this->hasOne(CallAnalysis::class);
    }

    /**
     * Scope: Filter by status
     */
    public function scopeStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope: Only outbound calls
     */
    public function scopeOutbound($query)
    {
        return $query->where('direction', self::DIRECTION_OUTBOUND);
    }

    /**
     * Scope: Only inbound calls
     */
    public function scopeInbound($query)
    {
        return $query->where('direction', self::DIRECTION_INBOUND);
    }

    /**
     * Scope: Only answered calls
     */
    public function scopeAnswered($query)
    {
        return $query->whereIn('status', [self::STATUS_ANSWERED, self::STATUS_COMPLETED]);
    }

    /**
     * Scope: Only missed calls
     */
    public function scopeMissed($query)
    {
        return $query->whereIn('status', [self::STATUS_MISSED, self::STATUS_NO_ANSWER]);
    }

    /**
     * Check if call was answered
     */
    public function wasAnswered(): bool
    {
        return in_array($this->status, [self::STATUS_ANSWERED, self::STATUS_COMPLETED]);
    }

    /**
     * Check if call is inbound
     */
    public function isInbound(): bool
    {
        return $this->direction === self::DIRECTION_INBOUND;
    }

    /**
     * Check if call is outbound
     */
    public function isOutbound(): bool
    {
        return $this->direction === self::DIRECTION_OUTBOUND;
    }

    /**
     * Get formatted duration (mm:ss)
     */
    public function getFormattedDurationAttribute(): string
    {
        $minutes = floor($this->duration / 60);
        $seconds = $this->duration % 60;

        return sprintf('%02d:%02d', $minutes, $seconds);
    }

    /**
     * Get status label in Uzbek
     */
    public function getStatusLabelAttribute(): string
    {
        $labels = [
            self::STATUS_INITIATED => 'Boshlandi',
            self::STATUS_RINGING => 'Jiringlamoqda',
            self::STATUS_ANSWERED => 'Javob berildi',
            self::STATUS_COMPLETED => 'Tugallandi',
            self::STATUS_FAILED => 'Xatolik',
            self::STATUS_MISSED => 'O\'tkazib yuborildi',
            self::STATUS_BUSY => 'Band',
            self::STATUS_NO_ANSWER => 'Javob yo\'q',
            self::STATUS_CANCELLED => 'Bekor qilindi',
        ];

        return $labels[$this->status] ?? $this->status;
    }

    /**
     * Get direction label in Uzbek
     */
    public function getDirectionLabelAttribute(): string
    {
        return $this->direction === self::DIRECTION_INBOUND ? 'Kiruvchi' : 'Chiquvchi';
    }

    /**
     * Get full descriptive label combining direction and status in Uzbek
     * Example: "Kiruvchi - Javob berildi", "Chiquvchi - Javob berilmadi"
     */
    public function getFullLabelAttribute(): string
    {
        $direction = $this->direction === self::DIRECTION_INBOUND ? 'Kiruvchi' : 'Chiquvchi';

        // Determine status description
        $statusDesc = match ($this->status) {
            self::STATUS_COMPLETED, self::STATUS_ANSWERED => 'Javob berildi',
            self::STATUS_MISSED, self::STATUS_NO_ANSWER, self::STATUS_FAILED, self::STATUS_CANCELLED => 'Javob berilmadi',
            self::STATUS_BUSY => 'Band',
            self::STATUS_RINGING => 'Jiringlamoqda',
            self::STATUS_INITIATED => 'Boshlanmoqda',
            default => $this->status,
        };

        return "{$direction} - {$statusDesc}";
    }

    /**
     * Mark call as answered
     */
    public function markAsAnswered(): void
    {
        $this->update([
            'status' => self::STATUS_ANSWERED,
            'answered_at' => now(),
        ]);
    }

    /**
     * Mark call as completed
     */
    public function markAsCompleted(int $duration = 0): void
    {
        $this->update([
            'status' => self::STATUS_COMPLETED,
            'duration' => $duration,
            'ended_at' => now(),
        ]);
    }

    /**
     * Mark call as failed
     */
    public function markAsFailed(?string $reason = null): void
    {
        $metadata = $this->metadata ?? [];
        if ($reason) {
            $metadata['failure_reason'] = $reason;
        }

        $this->update([
            'status' => self::STATUS_FAILED,
            'metadata' => $metadata,
            'ended_at' => now(),
        ]);
    }

    /**
     * Scope: Filter by analysis status
     */
    public function scopeAnalysisStatus($query, string $status)
    {
        return $query->where('analysis_status', $status);
    }

    /**
     * Scope: Calls pending analysis
     */
    public function scopePendingAnalysis($query)
    {
        return $query->where('analysis_status', self::ANALYSIS_STATUS_PENDING);
    }

    /**
     * Scope: Calls with completed analysis
     */
    public function scopeAnalyzed($query)
    {
        return $query->where('analysis_status', self::ANALYSIS_STATUS_COMPLETED);
    }

    /**
     * Scope: Calls that can be analyzed (completed with recording)
     */
    public function scopeAnalyzable($query)
    {
        return $query->whereIn('status', [self::STATUS_COMPLETED, self::STATUS_ANSWERED])
            ->whereNotNull('recording_url')
            ->where('duration', '>=', 30);  // Minimum 30 sekund
    }

    /**
     * Check if call can be analyzed
     */
    public function canBeAnalyzed(): bool
    {
        return in_array($this->status, [self::STATUS_COMPLETED, self::STATUS_ANSWERED])
            && !empty($this->recording_url)
            && $this->duration >= 30
            && $this->analysis_status === self::ANALYSIS_STATUS_PENDING;
    }

    /**
     * Scope: Calls recommended for Smart Audit
     * Filters calls that need attention based on:
     * - Duration between 30s and 10 minutes
     * - Lead status is 'junk' or 'thinking'
     * - No AI analysis performed yet
     */
    public function scopeRecommended($query)
    {
        return $query->whereIn('status', [self::STATUS_COMPLETED, self::STATUS_ANSWERED])
            ->whereBetween('duration', [30, 600]) // 30s to 10 minutes
            ->whereHas('lead', function ($q) {
                $q->whereIn('status', ['junk', 'thinking']);
            })
            ->where(function ($q) {
                $q->where('analysis_status', self::ANALYSIS_STATUS_PENDING)
                    ->orWhereNull('analysis_status');
            });
    }

    /**
     * Get recommended reason for Smart Audit
     */
    public function getRecommendedReasonAttribute(): ?string
    {
        if (!$this->lead) {
            return null;
        }

        $reasons = [];

        if ($this->duration < 60) {
            $reasons[] = 'Qisqa muloqot (' . $this->formatted_duration . ')';
        }

        if ($this->lead->status === 'junk') {
            $reasons[] = 'Lid "Keraksiz" deb belgilangan';
        } elseif ($this->lead->status === 'thinking') {
            $reasons[] = 'Lid "O\'ylanmoqda" holatida';
        }

        if (!$this->analysis_status || $this->analysis_status === self::ANALYSIS_STATUS_PENDING) {
            $reasons[] = 'AI tahlil qilinmagan';
        }

        return !empty($reasons) ? implode(' • ', $reasons) : null;
    }

    /**
     * Get analysis status label in Uzbek
     */
    public function getAnalysisStatusLabelAttribute(): string
    {
        $labels = [
            self::ANALYSIS_STATUS_PENDING => 'Tahlil qilinmagan',
            self::ANALYSIS_STATUS_QUEUED => 'Navbatda',
            self::ANALYSIS_STATUS_TRANSCRIBING => 'Transkript qilinmoqda',
            self::ANALYSIS_STATUS_ANALYZING => 'Tahlil qilinmoqda',
            self::ANALYSIS_STATUS_COMPLETED => 'Tayyor',
            self::ANALYSIS_STATUS_FAILED => 'Xatolik',
        ];

        return $labels[$this->analysis_status] ?? $this->analysis_status ?? 'Noma\'lum';
    }

    /**
     * Queue call for analysis
     */
    public function queueForAnalysis(): void
    {
        $this->update([
            'analysis_status' => self::ANALYSIS_STATUS_QUEUED,
            'analysis_error' => null,
        ]);
    }

    /**
     * Mark analysis as transcribing
     */
    public function markAsTranscribing(): void
    {
        $this->update(['analysis_status' => self::ANALYSIS_STATUS_TRANSCRIBING]);
    }

    /**
     * Mark analysis as analyzing
     */
    public function markAsAnalyzing(): void
    {
        $this->update(['analysis_status' => self::ANALYSIS_STATUS_ANALYZING]);
    }

    /**
     * Mark analysis as completed
     */
    public function markAnalysisCompleted(): void
    {
        $this->update([
            'analysis_status' => self::ANALYSIS_STATUS_COMPLETED,
            'analysis_error' => null,
        ]);
    }

    /**
     * Mark analysis as failed
     */
    public function markAnalysisFailed(string $error): void
    {
        $this->update([
            'analysis_status' => self::ANALYSIS_STATUS_FAILED,
            'analysis_error' => $error,
        ]);
    }
}
