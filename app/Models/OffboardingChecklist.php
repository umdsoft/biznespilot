<?php

namespace App\Models;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * OffboardingChecklist - Hodim offboarding jarayoni
 *
 * Ishdan ketayotgan hodimlar uchun checklist
 */
class OffboardingChecklist extends Model
{
    use HasUuid;

    protected $table = 'offboarding_checklists';

    protected $fillable = [
        'business_id',
        'user_id',
        'termination_reason',
        'last_working_day',
        'status',
        'checklist_items',
        'progress',
        'hr_contact_id',
        'knowledge_transfer_completed',
        'assets_returned',
        'access_revoked',
        'final_payment_processed',
        'exit_interview_date',
        'notes',
    ];

    protected $casts = [
        'last_working_day' => 'date',
        'checklist_items' => 'array',
        'progress' => 'float',
        'knowledge_transfer_completed' => 'boolean',
        'assets_returned' => 'boolean',
        'access_revoked' => 'boolean',
        'final_payment_processed' => 'boolean',
        'exit_interview_date' => 'datetime',
    ];

    // Status konstantalari
    public const STATUS_ACTIVE = 'active';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_CANCELLED = 'cancelled';

    // Relationships
    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function hrContact(): BelongsTo
    {
        return $this->belongsTo(User::class, 'hr_contact_id');
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

    public function scopeUpcoming($query)
    {
        return $query->where('status', self::STATUS_ACTIVE)
            ->where('last_working_day', '>=', now());
    }

    // Accessors
    public function getStatusLabelAttribute(): string
    {
        return match($this->status) {
            self::STATUS_ACTIVE => 'Jarayonda',
            self::STATUS_COMPLETED => 'Yakunlangan',
            self::STATUS_CANCELLED => 'Bekor qilingan',
            default => "Noma'lum",
        };
    }

    public function getDaysRemainingAttribute(): int
    {
        if (!$this->last_working_day) {
            return 0;
        }
        return max(0, now()->diffInDays($this->last_working_day, false));
    }

    public function getCompletedItemsCountAttribute(): int
    {
        if (empty($this->checklist_items)) {
            return 0;
        }
        return collect($this->checklist_items)->where('completed', true)->count();
    }

    public function getTotalItemsCountAttribute(): int
    {
        return count($this->checklist_items ?? []);
    }

    // Methods
    public function updateChecklistItem(int $index, bool $completed): bool
    {
        $items = $this->checklist_items ?? [];

        if (!isset($items[$index])) {
            return false;
        }

        $items[$index]['completed'] = $completed;
        $items[$index]['completed_at'] = $completed ? now()->toISOString() : null;

        return $this->update([
            'checklist_items' => $items,
            'progress' => $this->calculateProgress($items),
        ]);
    }

    protected function calculateProgress(array $items): float
    {
        if (empty($items)) {
            return 0;
        }

        $completed = collect($items)->where('completed', true)->count();
        return round(($completed / count($items)) * 100, 2);
    }

    public function completeItem(string $category): bool
    {
        $items = $this->checklist_items ?? [];

        foreach ($items as $index => $item) {
            if ($item['category'] === $category && !$item['completed']) {
                $items[$index]['completed'] = true;
                $items[$index]['completed_at'] = now()->toISOString();
            }
        }

        return $this->update([
            'checklist_items' => $items,
            'progress' => $this->calculateProgress($items),
        ]);
    }

    public function completeOffboarding(): bool
    {
        return $this->update([
            'status' => self::STATUS_COMPLETED,
            'progress' => 100,
        ]);
    }

    public function isComplete(): bool
    {
        return $this->status === self::STATUS_COMPLETED;
    }

    public function getMissingItems(): array
    {
        if (empty($this->checklist_items)) {
            return [];
        }

        return collect($this->checklist_items)
            ->where('completed', false)
            ->values()
            ->toArray();
    }
}
