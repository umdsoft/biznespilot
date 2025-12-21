<?php

namespace App\Models;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OnboardingStep extends Model
{
    use HasUuid;
    protected $fillable = [
        'business_id',
        'step_definition_id',
        'is_completed',
        'completion_percent',
        'validation_errors',
        'started_at',
        'completed_at',
        'last_updated_at',
    ];

    protected $casts = [
        'is_completed' => 'boolean',
        'validation_errors' => 'array',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'last_updated_at' => 'datetime',
    ];

    // Relationships
    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    public function stepDefinition(): BelongsTo
    {
        return $this->belongsTo(StepDefinition::class);
    }

    // Scopes
    public function scopeCompleted($query)
    {
        return $query->where('is_completed', true);
    }

    public function scopeIncomplete($query)
    {
        return $query->where('is_completed', false);
    }

    public function scopeForBusiness($query, string $businessId)
    {
        return $query->where('business_id', $businessId);
    }

    // Helpers
    public function markStarted(): void
    {
        if (is_null($this->started_at)) {
            $this->update(['started_at' => now()]);
        }
    }

    public function markCompleted(): void
    {
        $this->update([
            'is_completed' => true,
            'completion_percent' => 100,
            'completed_at' => now(),
            'last_updated_at' => now(),
            'validation_errors' => null,
        ]);
    }

    public function updateProgress(int $percent, ?array $errors = null): void
    {
        $this->update([
            'completion_percent' => min(100, max(0, $percent)),
            'validation_errors' => $errors,
            'last_updated_at' => now(),
            'is_completed' => $percent >= 100,
            'completed_at' => $percent >= 100 ? now() : $this->completed_at,
        ]);
    }

    public function hasErrors(): bool
    {
        return !empty($this->validation_errors);
    }

    public function getCode(): string
    {
        return $this->stepDefinition->code;
    }
}
