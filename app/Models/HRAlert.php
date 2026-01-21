<?php

namespace App\Models;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * HRAlert - HR ogohlantirishlari modeli
 *
 * HR moduli uchun barcha alertlarni saqlash va boshqarish
 */
class HRAlert extends Model
{
    use HasUuid;

    protected $table = 'hr_alerts';

    protected $fillable = [
        'business_id',
        'user_id',
        'related_user_id',
        'type',
        'title',
        'message',
        'priority',
        'status',
        'is_celebration',
        'data',
        'recommended_actions',
        'seen_at',
        'acknowledged_at',
        'resolved_at',
        'resolved_by',
    ];

    protected $casts = [
        'is_celebration' => 'boolean',
        'data' => 'array',
        'recommended_actions' => 'array',
        'seen_at' => 'datetime',
        'acknowledged_at' => 'datetime',
        'resolved_at' => 'datetime',
    ];

    // Status konstantalari
    public const STATUS_NEW = 'new';
    public const STATUS_SEEN = 'seen';
    public const STATUS_ACKNOWLEDGED = 'acknowledged';
    public const STATUS_RESOLVED = 'resolved';

    // Priority konstantalari
    public const PRIORITY_LOW = 'low';
    public const PRIORITY_MEDIUM = 'medium';
    public const PRIORITY_HIGH = 'high';
    public const PRIORITY_URGENT = 'urgent';

    // Alert turlari
    public const TYPE_EMPLOYEE_HIRED = 'employee_hired';
    public const TYPE_EMPLOYEE_TERMINATED = 'employee_terminated';
    public const TYPE_EMPLOYEE_PROMOTED = 'employee_promoted';
    public const TYPE_ENGAGEMENT_LOW = 'engagement_low';
    public const TYPE_FLIGHT_RISK_HIGH = 'flight_risk_high';
    public const TYPE_GOAL_COMPLETED = 'goal_completed';
    public const TYPE_OKR_BEHIND = 'okr_behind';
    public const TYPE_ATTENDANCE_PATTERN = 'attendance_pattern';
    public const TYPE_SURVEY_LOW_SCORE = 'survey_low_score';
    public const TYPE_WORK_ANNIVERSARY = 'work_anniversary';
    public const TYPE_RECOGNITION_RECEIVED = 'recognition_received';
    public const TYPE_FEEDBACK_RECEIVED = 'feedback_received';
    public const TYPE_ONE_ON_ONE_NEGATIVE = 'one_on_one_negative';
    public const TYPE_SURVEY_AVAILABLE = 'survey_available';

    // Relationships
    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function alertable(): MorphTo
    {
        return $this->morphTo();
    }

    public function relatedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'related_user_id');
    }

    public function resolvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'resolved_by');
    }

    // Scopes
    public function scopeNew($query)
    {
        return $query->where('status', self::STATUS_NEW);
    }

    public function scopeUnread($query)
    {
        return $query->whereIn('status', [self::STATUS_NEW, self::STATUS_SEEN]);
    }

    public function scopeResolved($query)
    {
        return $query->where('status', self::STATUS_RESOLVED);
    }

    public function scopeActive($query)
    {
        return $query->whereNotIn('status', [self::STATUS_RESOLVED]);
    }

    public function scopeByPriority($query, string $priority)
    {
        return $query->where('priority', $priority);
    }

    public function scopeUrgent($query)
    {
        return $query->where('priority', self::PRIORITY_URGENT);
    }

    public function scopeHighPriority($query)
    {
        return $query->whereIn('priority', [self::PRIORITY_HIGH, self::PRIORITY_URGENT]);
    }

    public function scopeCelebrations($query)
    {
        return $query->where('is_celebration', true);
    }

    public function scopeForUser($query, string $userId)
    {
        return $query->where(function ($q) use ($userId) {
            $q->whereNull('user_id')
              ->orWhere('user_id', $userId);
        });
    }

    // Accessors
    public function getPriorityLabelAttribute(): string
    {
        return match($this->priority) {
            self::PRIORITY_URGENT => 'Juda muhim',
            self::PRIORITY_HIGH => 'Muhim',
            self::PRIORITY_MEDIUM => "O'rtacha",
            self::PRIORITY_LOW => 'Past',
            default => "O'rtacha",
        };
    }

    public function getPriorityColorAttribute(): string
    {
        return match($this->priority) {
            self::PRIORITY_URGENT => 'red',
            self::PRIORITY_HIGH => 'orange',
            self::PRIORITY_MEDIUM => 'blue',
            self::PRIORITY_LOW => 'gray',
            default => 'blue',
        };
    }

    // Methods
    public function markAsSeen(): bool
    {
        return $this->update([
            'status' => self::STATUS_SEEN,
            'seen_at' => now(),
        ]);
    }

    public function markAsAcknowledged(): bool
    {
        return $this->update([
            'status' => self::STATUS_ACKNOWLEDGED,
            'acknowledged_at' => now(),
        ]);
    }

    public function markAsResolved(string $resolvedById): bool
    {
        return $this->update([
            'status' => self::STATUS_RESOLVED,
            'resolved_at' => now(),
            'resolved_by' => $resolvedById,
        ]);
    }

    public function isUrgent(): bool
    {
        return $this->priority === self::PRIORITY_URGENT;
    }

    public function isHighPriority(): bool
    {
        return in_array($this->priority, [self::PRIORITY_HIGH, self::PRIORITY_URGENT]);
    }
}
