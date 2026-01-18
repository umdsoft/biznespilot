<?php

namespace App\Models;

use App\Traits\BelongsToBusiness;
use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class AutoDialerCampaign extends Model
{
    use BelongsToBusiness, HasUuid, SoftDeletes;

    protected $fillable = [
        'business_id',
        'created_by',
        'name',
        'description',
        'status',
        'caller_id',
        'calls_per_minute',
        'max_attempts',
        'retry_delay',
        'start_time',
        'end_time',
        'working_days',
        'audio_file',
        'script',
        'scheduled_at',
        'started_at',
        'completed_at',
    ];

    protected $casts = [
        'working_days' => 'array',
        'scheduled_at' => 'datetime',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    /**
     * Status constants
     */
    public const STATUS_DRAFT = 'draft';

    public const STATUS_SCHEDULED = 'scheduled';

    public const STATUS_RUNNING = 'running';

    public const STATUS_PAUSED = 'paused';

    public const STATUS_COMPLETED = 'completed';

    public const STATUS_CANCELLED = 'cancelled';

    /**
     * Get the business that owns this campaign
     */
    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    /**
     * Get the user who created this campaign
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get campaign leads
     */
    public function campaignLeads(): HasMany
    {
        return $this->hasMany(AutoDialerCampaignLead::class, 'campaign_id');
    }

    /**
     * Get the leads associated with this campaign
     */
    public function leads()
    {
        return $this->belongsToMany(Lead::class, 'auto_dialer_campaign_leads', 'campaign_id', 'lead_id')
            ->withPivot(['status', 'attempts', 'last_result', 'last_duration', 'last_called_at'])
            ->withTimestamps();
    }

    /**
     * Scope: Filter by status
     */
    public function scopeStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope: Only active campaigns
     */
    public function scopeActive($query)
    {
        return $query->whereIn('status', [self::STATUS_SCHEDULED, self::STATUS_RUNNING]);
    }

    /**
     * Check if campaign can be started
     */
    public function canStart(): bool
    {
        return in_array($this->status, [self::STATUS_DRAFT, self::STATUS_SCHEDULED, self::STATUS_PAUSED]);
    }

    /**
     * Check if campaign is running
     */
    public function isRunning(): bool
    {
        return $this->status === self::STATUS_RUNNING;
    }

    /**
     * Check if campaign can run at current time
     */
    public function canRunNow(): bool
    {
        // Check working days
        if ($this->working_days) {
            $currentDay = now()->dayOfWeek; // 0 = Sunday
            if (! in_array($currentDay, $this->working_days)) {
                return false;
            }
        }

        // Check time window
        if ($this->start_time && $this->end_time) {
            $currentTime = now()->format('H:i:s');
            if ($currentTime < $this->start_time || $currentTime > $this->end_time) {
                return false;
            }
        }

        return true;
    }

    /**
     * Start the campaign
     */
    public function start(): void
    {
        $this->update([
            'status' => self::STATUS_RUNNING,
            'started_at' => $this->started_at ?? now(),
        ]);
    }

    /**
     * Pause the campaign
     */
    public function pause(): void
    {
        $this->update(['status' => self::STATUS_PAUSED]);
    }

    /**
     * Complete the campaign
     */
    public function complete(): void
    {
        $this->update([
            'status' => self::STATUS_COMPLETED,
            'completed_at' => now(),
        ]);
    }

    /**
     * Cancel the campaign
     */
    public function cancel(): void
    {
        $this->update(['status' => self::STATUS_CANCELLED]);
    }

    /**
     * Get progress percentage
     */
    public function getProgressAttribute(): float
    {
        $total = $this->campaignLeads()->count();
        if ($total === 0) {
            return 0;
        }

        $completed = $this->campaignLeads()
            ->whereIn('status', ['completed', 'skipped'])
            ->count();

        return round(($completed / $total) * 100, 1);
    }

    /**
     * Get campaign statistics
     */
    public function getStatsAttribute(): array
    {
        $leads = $this->campaignLeads();

        return [
            'total' => $leads->count(),
            'pending' => $leads->clone()->where('status', 'pending')->count(),
            'completed' => $leads->clone()->where('status', 'completed')->count(),
            'failed' => $leads->clone()->where('status', 'failed')->count(),
            'skipped' => $leads->clone()->where('status', 'skipped')->count(),
            'answered' => $leads->clone()->where('last_result', 'answered')->count(),
            'no_answer' => $leads->clone()->where('last_result', 'no_answer')->count(),
        ];
    }

    /**
     * Get status label in Uzbek
     */
    public function getStatusLabelAttribute(): string
    {
        $labels = [
            self::STATUS_DRAFT => 'Qoralama',
            self::STATUS_SCHEDULED => 'Rejalashtirilgan',
            self::STATUS_RUNNING => 'Ishlayapti',
            self::STATUS_PAUSED => 'To\'xtatilgan',
            self::STATUS_COMPLETED => 'Tugallangan',
            self::STATUS_CANCELLED => 'Bekor qilingan',
        ];

        return $labels[$this->status] ?? $this->status;
    }
}
