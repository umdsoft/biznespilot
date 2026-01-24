<?php

namespace App\Models;

use App\Traits\BelongsToBusiness;
use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * EmployeeOnboardingPlan - Hodim onboarding rejasi (30-60-90)
 *
 * Yangi hodimlar uchun strukturalashtirilgan onboarding jarayoni
 */
class EmployeeOnboardingPlan extends Model
{
    use BelongsToBusiness, HasUuid;

    protected $table = 'employee_onboarding_plans';

    protected $fillable = [
        'business_id',
        'user_id',
        'mentor_id',
        'manager_id',
        'hr_contact_id',
        // Reja parametrlari
        'start_date',
        'expected_end_date',
        'status',
        'progress',
        // Bosqich ballari
        'day_30_score',
        'day_60_score',
        'day_90_score',
        'day_30_completed',
        'day_60_completed',
        'day_90_completed',
        'day_30_completed_at',
        'day_60_completed_at',
        'day_90_completed_at',
        // Feedback
        'mentor_feedback',
        'manager_feedback',
        'employee_feedback',
        'overall_satisfaction',
        // Yakuniy natija
        'probation_passed',
        'final_notes',
        'completed_at',
    ];

    protected $casts = [
        'start_date' => 'date',
        'expected_end_date' => 'date',
        'progress' => 'integer',
        'day_30_score' => 'integer',
        'day_60_score' => 'integer',
        'day_90_score' => 'integer',
        'day_30_completed' => 'boolean',
        'day_60_completed' => 'boolean',
        'day_90_completed' => 'boolean',
        'day_30_completed_at' => 'datetime',
        'day_60_completed_at' => 'datetime',
        'day_90_completed_at' => 'datetime',
        'mentor_feedback' => 'array',
        'manager_feedback' => 'array',
        'employee_feedback' => 'array',
        'overall_satisfaction' => 'float',
        'probation_passed' => 'boolean',
        'completed_at' => 'datetime',
    ];

    // Status konstantalari
    public const STATUS_ACTIVE = 'active';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_PAUSED = 'paused';
    public const STATUS_CANCELLED = 'cancelled';

    // Phase konstantalari
    public const PHASE_DAY_1 = 'day_1';
    public const PHASE_WEEK_1 = 'week_1';
    public const PHASE_DAY_30 = 'day_30';
    public const PHASE_DAY_60 = 'day_60';
    public const PHASE_DAY_90 = 'day_90';

    // Relationships
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function mentor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'mentor_id');
    }

    public function manager(): BelongsTo
    {
        return $this->belongsTo(User::class, 'manager_id');
    }

    public function hrContact(): BelongsTo
    {
        return $this->belongsTo(User::class, 'hr_contact_id');
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(EmployeeOnboardingTask::class, 'plan_id');
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', self::STATUS_COMPLETED);
    }

    // Accessors
    public function getStatusLabelAttribute(): string
    {
        return match($this->status) {
            self::STATUS_ACTIVE => 'Faol',
            self::STATUS_COMPLETED => 'Yakunlangan',
            self::STATUS_PAUSED => "To'xtatilgan",
            self::STATUS_CANCELLED => 'Bekor qilingan',
            default => "Noma'lum",
        };
    }

    public function getCurrentPhaseLabel(): string
    {
        return match($this->current_phase) {
            self::PHASE_DAY_1 => '1-kun',
            self::PHASE_WEEK_1 => '1-hafta',
            self::PHASE_DAY_30 => '30-kun',
            self::PHASE_DAY_60 => '60-kun',
            self::PHASE_DAY_90 => '90-kun',
            default => 'Boshlang\'ich',
        };
    }

    public function getDaysRemainingAttribute(): int
    {
        if ($this->status !== self::STATUS_ACTIVE) {
            return 0;
        }

        return max(0, now()->diffInDays($this->expected_end_date, false));
    }

    public function getCurrentPhaseAttribute(): string
    {
        $daysPassed = $this->start_date ? now()->diffInDays($this->start_date) : 0;

        return match(true) {
            $daysPassed < 1 => self::PHASE_DAY_1,
            $daysPassed < 7 => self::PHASE_WEEK_1,
            $daysPassed < 30 => self::PHASE_DAY_30,
            $daysPassed < 60 => self::PHASE_DAY_60,
            default => self::PHASE_DAY_90,
        };
    }

    // Methods
    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    public function isCompleted(): bool
    {
        return $this->status === self::STATUS_COMPLETED;
    }

    public function completeDay30(int $score = null): bool
    {
        return $this->update([
            'day_30_completed' => true,
            'day_30_completed_at' => now(),
            'day_30_score' => $score,
        ]);
    }

    public function completeDay60(int $score = null): bool
    {
        return $this->update([
            'day_60_completed' => true,
            'day_60_completed_at' => now(),
            'day_60_score' => $score,
        ]);
    }

    public function completeDay90(int $score = null, bool $probationPassed = true): bool
    {
        return $this->update([
            'day_90_completed' => true,
            'day_90_completed_at' => now(),
            'day_90_score' => $score,
            'status' => self::STATUS_COMPLETED,
            'progress' => 100,
            'probation_passed' => $probationPassed,
            'completed_at' => now(),
        ]);
    }

    public function updateProgress(): float
    {
        $totalTasks = $this->tasks()->count();
        $completedTasks = $this->tasks()->where('status', 'completed')->count();

        $progress = $totalTasks > 0 ? ($completedTasks / $totalTasks) * 100 : 0;

        $this->update(['progress' => round($progress, 2)]);

        // 100% bo'lsa - yakunlash
        if ($progress >= 100 && $this->status === self::STATUS_ACTIVE) {
            $this->update(['status' => self::STATUS_COMPLETED]);
        }

        return $progress;
    }

    public function getTasksByPhase(): array
    {
        return $this->tasks()
            ->orderBy('due_date')
            ->get()
            ->groupBy('phase')
            ->toArray();
    }
}
