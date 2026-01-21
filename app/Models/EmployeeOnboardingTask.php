<?php

namespace App\Models;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * EmployeeOnboardingTask - Onboarding vazifalar
 *
 * Onboarding rejasidagi alohida vazifalar
 */
class EmployeeOnboardingTask extends Model
{
    use HasUuid;

    protected $table = 'employee_onboarding_tasks';

    protected $fillable = [
        'plan_id',
        'business_id',
        // Vazifa ma'lumotlari
        'title',
        'description',
        'category',
        'phase',
        'day_number',
        'order',
        // Mas'ul
        'assigned_to_id',
        'assigned_role',
        // Holat
        'status',
        'is_required',
        'due_date',
        'completed_at',
        'completed_by',
        'completion_notes',
        // Resurslar
        'resources',
    ];

    protected $casts = [
        'day_number' => 'integer',
        'order' => 'integer',
        'is_required' => 'boolean',
        'due_date' => 'datetime',
        'completed_at' => 'datetime',
        'resources' => 'array',
    ];

    // Status konstantalari
    public const STATUS_PENDING = 'pending';
    public const STATUS_IN_PROGRESS = 'in_progress';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_SKIPPED = 'skipped';

    // Category konstantalari
    public const CATEGORY_SETUP = 'setup';
    public const CATEGORY_TRAINING = 'training';
    public const CATEGORY_SOCIAL = 'social';
    public const CATEGORY_MENTORING = 'mentoring';
    public const CATEGORY_WORK = 'work';
    public const CATEGORY_REVIEW = 'review';
    public const CATEGORY_GOAL_SETTING = 'goal_setting';

    // Priority konstantalari
    public const PRIORITY_LOW = 'low';
    public const PRIORITY_MEDIUM = 'medium';
    public const PRIORITY_HIGH = 'high';

    // Role konstantalari
    public const ROLE_EMPLOYEE = 'employee';
    public const ROLE_MENTOR = 'mentor';
    public const ROLE_MANAGER = 'manager';
    public const ROLE_HR = 'hr';
    public const ROLE_IT = 'it';

    // Relationships
    public function plan(): BelongsTo
    {
        return $this->belongsTo(EmployeeOnboardingPlan::class, 'plan_id');
    }

    // Alias for backward compatibility
    public function onboardingPlan(): BelongsTo
    {
        return $this->plan();
    }

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    public function assignedTo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to_id');
    }

    public function completedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'completed_by');
    }

    // Scopes
    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', self::STATUS_COMPLETED);
    }

    public function scopeOverdue($query)
    {
        return $query->where('status', self::STATUS_PENDING)
            ->where('due_date', '<', now());
    }

    public function scopeByPhase($query, string $phase)
    {
        return $query->where('phase', $phase);
    }

    public function scopeByCategory($query, string $category)
    {
        return $query->where('category', $category);
    }

    // Accessors
    public function getStatusLabelAttribute(): string
    {
        return match($this->status) {
            self::STATUS_PENDING => 'Kutilmoqda',
            self::STATUS_IN_PROGRESS => 'Jarayonda',
            self::STATUS_COMPLETED => 'Bajarildi',
            self::STATUS_SKIPPED => "O'tkazib yuborildi",
            default => "Noma'lum",
        };
    }

    public function getStatusColorAttribute(): string
    {
        return match($this->status) {
            self::STATUS_PENDING => 'gray',
            self::STATUS_IN_PROGRESS => 'blue',
            self::STATUS_COMPLETED => 'green',
            self::STATUS_SKIPPED => 'yellow',
            default => 'gray',
        };
    }

    public function getCategoryLabelAttribute(): string
    {
        return match($this->category) {
            self::CATEGORY_SETUP => 'Sozlash',
            self::CATEGORY_TRAINING => "O'qitish",
            self::CATEGORY_SOCIAL => 'Ijtimoiy',
            self::CATEGORY_MENTORING => 'Mentorlik',
            self::CATEGORY_WORK => 'Ish vazifasi',
            self::CATEGORY_REVIEW => "Ko'rib chiqish",
            self::CATEGORY_GOAL_SETTING => "Maqsad qo'yish",
            default => 'Boshqa',
        };
    }

    public function getPriorityLabelAttribute(): string
    {
        return match($this->priority) {
            self::PRIORITY_HIGH => 'Yuqori',
            self::PRIORITY_MEDIUM => "O'rtacha",
            self::PRIORITY_LOW => 'Past',
            default => "O'rtacha",
        };
    }

    public function getIsOverdueAttribute(): bool
    {
        return $this->status === self::STATUS_PENDING
            && $this->due_date
            && $this->due_date->isPast();
    }

    // Methods
    public function markAsCompleted(?string $notes = null, ?string $completedById = null): bool
    {
        $result = $this->update([
            'status' => self::STATUS_COMPLETED,
            'completed_at' => now(),
            'completion_notes' => $notes ?? $this->completion_notes,
            'completed_by' => $completedById,
        ]);

        // Onboarding plan progressni yangilash
        $this->plan->updateProgress();

        return $result;
    }

    public function markAsInProgress(): bool
    {
        return $this->update([
            'status' => self::STATUS_IN_PROGRESS,
        ]);
    }

    public function skip(?string $reason = null): bool
    {
        $result = $this->update([
            'status' => self::STATUS_SKIPPED,
            'completion_notes' => $reason ?? $this->completion_notes,
        ]);

        $this->plan->updateProgress();

        return $result;
    }

    public function getRoleLabelAttribute(): string
    {
        return match($this->assigned_role) {
            self::ROLE_EMPLOYEE => 'Hodim',
            self::ROLE_MENTOR => 'Mentor',
            self::ROLE_MANAGER => 'Menejer',
            self::ROLE_HR => 'HR',
            self::ROLE_IT => 'IT',
            default => 'Boshqa',
        };
    }
}
