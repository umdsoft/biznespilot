<?php

namespace App\Models;

use App\Traits\BelongsToBusiness;
use App\Traits\HasUuid;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TodoRecurrence extends Model
{
    use BelongsToBusiness, HasUuid;

    // Frequencies
    public const FREQUENCY_DAILY = 'daily';
    public const FREQUENCY_WEEKLY = 'weekly';
    public const FREQUENCY_MONTHLY = 'monthly';
    public const FREQUENCY_YEARLY = 'yearly';

    public const FREQUENCIES = [
        self::FREQUENCY_DAILY => 'Har kuni',
        self::FREQUENCY_WEEKLY => 'Har hafta',
        self::FREQUENCY_MONTHLY => 'Har oy',
        self::FREQUENCY_YEARLY => 'Har yil',
    ];

    // Generation Modes
    public const MODE_ADVANCE = 'advance';
    public const MODE_ON_TIME = 'on_time';

    public const MODES = [
        self::MODE_ADVANCE => 'Oldindan (7 kun)',
        self::MODE_ON_TIME => "O'z vaqtida",
    ];

    // Days of week
    public const DAYS_OF_WEEK = [
        1 => 'Dushanba',
        2 => 'Seshanba',
        3 => 'Chorshanba',
        4 => 'Payshanba',
        5 => 'Juma',
        6 => 'Shanba',
        7 => 'Yakshanba',
    ];

    protected $fillable = [
        'business_id',
        'todo_id',
        'frequency',
        'interval',
        'days_of_week',
        'day_of_month',
        'start_date',
        'end_date',
        'next_occurrence',
        'generation_mode',
        'occurrences_count',
        'is_active',
    ];

    protected $casts = [
        'days_of_week' => 'array',
        'start_date' => 'date',
        'end_date' => 'date',
        'next_occurrence' => 'date',
        'is_active' => 'boolean',
    ];

    // ==================== Relationships ====================

    public function todo(): BelongsTo
    {
        return $this->belongsTo(Todo::class);
    }

    public function generatedTodos(): HasMany
    {
        return $this->hasMany(Todo::class, 'recurrence_id');
    }

    // ==================== Scopes ====================

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeDueToGenerate($query)
    {
        return $query->active()
            ->where('next_occurrence', '<=', today());
    }

    public function scopeAdvanceMode($query)
    {
        return $query->where('generation_mode', self::MODE_ADVANCE);
    }

    public function scopeOnTimeMode($query)
    {
        return $query->where('generation_mode', self::MODE_ON_TIME);
    }

    // ==================== Accessors ====================

    public function getFrequencyLabelAttribute(): string
    {
        return self::FREQUENCIES[$this->frequency] ?? $this->frequency;
    }

    public function getModeLabelAttribute(): string
    {
        return self::MODES[$this->generation_mode] ?? $this->generation_mode;
    }

    public function getDaysOfWeekLabelsAttribute(): array
    {
        if (!$this->days_of_week) {
            return [];
        }

        return array_map(
            fn($day) => self::DAYS_OF_WEEK[$day] ?? $day,
            $this->days_of_week
        );
    }

    public function getIsExpiredAttribute(): bool
    {
        return $this->end_date && $this->end_date->isPast();
    }

    public function getDescriptionAttribute(): string
    {
        $parts = [];

        switch ($this->frequency) {
            case self::FREQUENCY_DAILY:
                $parts[] = $this->interval === 1 ? 'Har kuni' : "Har {$this->interval} kunda";
                break;

            case self::FREQUENCY_WEEKLY:
                $days = $this->days_of_week_labels;
                if ($days) {
                    $parts[] = implode(', ', $days);
                }
                $parts[] = $this->interval === 1 ? 'har hafta' : "har {$this->interval} haftada";
                break;

            case self::FREQUENCY_MONTHLY:
                $parts[] = $this->day_of_month ? "Har oyning {$this->day_of_month}-kuni" : 'Har oy';
                if ($this->interval > 1) {
                    $parts[] = "har {$this->interval} oyda";
                }
                break;

            case self::FREQUENCY_YEARLY:
                $parts[] = 'Har yil';
                break;
        }

        return implode(' ', $parts);
    }

    // ==================== Methods ====================

    public function calculateNextOccurrence(?Carbon $fromDate = null): Carbon
    {
        $from = $fromDate ?? $this->next_occurrence ?? today();

        switch ($this->frequency) {
            case self::FREQUENCY_DAILY:
                return $from->copy()->addDays($this->interval);

            case self::FREQUENCY_WEEKLY:
                return $this->calculateNextWeeklyOccurrence($from);

            case self::FREQUENCY_MONTHLY:
                $next = $from->copy()->addMonths($this->interval);
                if ($this->day_of_month) {
                    $next->day = min($this->day_of_month, $next->daysInMonth);
                }
                return $next;

            case self::FREQUENCY_YEARLY:
                return $from->copy()->addYears($this->interval);

            default:
                return $from->copy()->addDay();
        }
    }

    protected function calculateNextWeeklyOccurrence(Carbon $from): Carbon
    {
        if (empty($this->days_of_week)) {
            return $from->copy()->addWeeks($this->interval);
        }

        $daysOfWeek = $this->days_of_week;
        sort($daysOfWeek);

        $currentDayOfWeek = $from->dayOfWeekIso;
        $next = $from->copy();

        // Find next day in current week
        foreach ($daysOfWeek as $day) {
            if ($day > $currentDayOfWeek) {
                return $next->next($this->carbonDayConstant($day));
            }
        }

        // Move to next week(s) and get first day
        $next->addWeeks($this->interval)->startOfWeek();
        $firstDay = $daysOfWeek[0];

        return $next->next($this->carbonDayConstant($firstDay));
    }

    protected function carbonDayConstant(int $day): int
    {
        // Convert ISO day (1=Mon, 7=Sun) to Carbon constant
        return $day === 7 ? Carbon::SUNDAY : $day;
    }

    public function generateNextTodo(): ?Todo
    {
        if (!$this->is_active || $this->is_expired) {
            return null;
        }

        $originalTodo = $this->todo;

        if (!$originalTodo) {
            return null;
        }

        // Create new todo based on template
        $newTodo = $originalTodo->replicate(['completed_at', 'recurrence_id']);
        $newTodo->status = Todo::STATUS_PENDING;
        $newTodo->due_date = $this->next_occurrence;
        $newTodo->is_recurring = true;
        $newTodo->recurrence_id = $this->id;
        $newTodo->save();

        // Copy subtasks
        foreach ($originalTodo->subtasks as $subtask) {
            $newSubtask = $subtask->replicate(['completed_at']);
            $newSubtask->parent_id = $newTodo->id;
            $newSubtask->status = Todo::STATUS_PENDING;
            $newSubtask->save();
        }

        // Update recurrence
        $this->update([
            'next_occurrence' => $this->calculateNextOccurrence(),
            'occurrences_count' => $this->occurrences_count + 1,
        ]);

        // Check if expired after this generation
        if ($this->end_date && $this->next_occurrence > $this->end_date) {
            $this->pause();
        }

        return $newTodo;
    }

    public function pause(): void
    {
        $this->update(['is_active' => false]);
    }

    public function resume(): void
    {
        // Recalculate next occurrence from today
        $this->update([
            'is_active' => true,
            'next_occurrence' => $this->calculateNextOccurrence(today()),
        ]);
    }

    public function shouldGenerate(): bool
    {
        if (!$this->is_active || $this->is_expired) {
            return false;
        }

        $generateDate = $this->generation_mode === self::MODE_ADVANCE
            ? today()->addDays(7)
            : today();

        return $this->next_occurrence <= $generateDate;
    }
}
