<?php

namespace App\Models;

use App\Traits\BelongsToBusiness;
use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * OneOnOneMeeting - 1-on-1 uchrashuvlar
 *
 * Manager-hodim uchrashuvlarini tracking qilish
 */
class OneOnOneMeeting extends Model
{
    use BelongsToBusiness, HasUuid;

    protected $table = 'one_on_one_meetings';

    protected $fillable = [
        'business_id',
        'employee_id',
        'manager_id',
        'meeting_type',
        'scheduled_date',
        'scheduled_time',
        'actual_date',
        'duration_minutes',
        'status',
        'location',
        'notes',
        'action_items',
        'topics_discussed',
        'sentiment',
        'employee_mood',
        'follow_up_date',
        'cancelled_reason',
    ];

    protected $casts = [
        'scheduled_date' => 'date',
        'actual_date' => 'datetime',
        'duration_minutes' => 'integer',
        'action_items' => 'array',
        'topics_discussed' => 'array',
        'sentiment' => 'integer',
        'employee_mood' => 'integer',
        'follow_up_date' => 'date',
    ];

    // Status konstantalari
    public const STATUS_SCHEDULED = 'scheduled';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_CANCELLED = 'cancelled';
    public const STATUS_RESCHEDULED = 'rescheduled';

    // Meeting type konstantalari
    public const TYPE_REGULAR = 'regular';
    public const TYPE_ONBOARDING = 'onboarding';
    public const TYPE_PERFORMANCE = 'performance';
    public const TYPE_STAY_INTERVIEW = 'stay_interview';
    public const TYPE_ENGAGEMENT_CONCERN = 'engagement_concern';
    public const TYPE_CAREER_DISCUSSION = 'career_discussion';

    // Relationships
    public function employee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'employee_id');
    }

    public function manager(): BelongsTo
    {
        return $this->belongsTo(User::class, 'manager_id');
    }

    // Scopes
    public function scopeScheduled($query)
    {
        return $query->where('status', self::STATUS_SCHEDULED);
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', self::STATUS_COMPLETED);
    }

    public function scopeUpcoming($query)
    {
        return $query->scheduled()
            ->where('scheduled_date', '>=', now());
    }

    public function scopeOverdue($query)
    {
        return $query->scheduled()
            ->where('scheduled_date', '<', now());
    }

    public function scopeForEmployee($query, string $employeeId)
    {
        return $query->where('employee_id', $employeeId);
    }

    public function scopeForManager($query, string $managerId)
    {
        return $query->where('manager_id', $managerId);
    }

    public function scopeThisQuarter($query)
    {
        return $query->where('scheduled_date', '>=', now()->startOfQuarter())
            ->where('scheduled_date', '<=', now()->endOfQuarter());
    }

    // Accessors
    public function getStatusLabelAttribute(): string
    {
        return match($this->status) {
            self::STATUS_SCHEDULED => 'Rejalashtirilgan',
            self::STATUS_COMPLETED => 'Yakunlangan',
            self::STATUS_CANCELLED => 'Bekor qilingan',
            self::STATUS_RESCHEDULED => 'Qayta rejalashtirilgan',
            default => "Noma'lum",
        };
    }

    public function getStatusColorAttribute(): string
    {
        return match($this->status) {
            self::STATUS_SCHEDULED => 'blue',
            self::STATUS_COMPLETED => 'green',
            self::STATUS_CANCELLED => 'red',
            self::STATUS_RESCHEDULED => 'yellow',
            default => 'gray',
        };
    }

    public function getTypeLabelAttribute(): string
    {
        return match($this->meeting_type) {
            self::TYPE_REGULAR => 'Muntazam',
            self::TYPE_ONBOARDING => 'Onboarding',
            self::TYPE_PERFORMANCE => 'Samaradorlik',
            self::TYPE_STAY_INTERVIEW => 'Stay Interview',
            self::TYPE_ENGAGEMENT_CONCERN => 'Engagement muhokamasi',
            self::TYPE_CAREER_DISCUSSION => 'Karyera muhokamasi',
            default => 'Boshqa',
        };
    }

    public function getSentimentLabelAttribute(): ?string
    {
        if ($this->sentiment === null) {
            return null;
        }

        return match($this->sentiment) {
            1 => 'Juda yomon',
            2 => 'Yomon',
            3 => "O'rtacha",
            4 => 'Yaxshi',
            5 => "A'lo",
            default => null,
        };
    }

    // Methods
    public function isScheduled(): bool
    {
        return $this->status === self::STATUS_SCHEDULED;
    }

    public function isCompleted(): bool
    {
        return $this->status === self::STATUS_COMPLETED;
    }

    public function isOverdue(): bool
    {
        return $this->isScheduled() && $this->scheduled_date->isPast();
    }

    public function complete(array $data = []): bool
    {
        return $this->update(array_merge([
            'status' => self::STATUS_COMPLETED,
            'actual_date' => now(),
        ], $data));
    }

    public function cancel(string $reason): bool
    {
        return $this->update([
            'status' => self::STATUS_CANCELLED,
            'cancelled_reason' => $reason,
        ]);
    }

    public function reschedule(\DateTime $newDate, ?string $newTime = null): bool
    {
        return $this->update([
            'status' => self::STATUS_RESCHEDULED,
            'scheduled_date' => $newDate,
            'scheduled_time' => $newTime ?? $this->scheduled_time,
        ]);
    }

    public function addActionItem(array $item): bool
    {
        $items = $this->action_items ?? [];
        $items[] = array_merge($item, [
            'added_at' => now()->toISOString(),
            'completed' => false,
        ]);

        return $this->update(['action_items' => $items]);
    }

    public function isPositive(): bool
    {
        return $this->sentiment !== null && $this->sentiment >= 4;
    }

    public function isNegative(): bool
    {
        return $this->sentiment !== null && $this->sentiment <= 2;
    }

    public function hasActionItems(): bool
    {
        return !empty($this->action_items);
    }

    public function getOpenActionItems(): array
    {
        if (empty($this->action_items)) {
            return [];
        }

        return collect($this->action_items)
            ->where('completed', false)
            ->values()
            ->toArray();
    }
}
