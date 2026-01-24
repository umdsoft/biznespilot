<?php

namespace App\Models;

use App\Traits\BelongsToBusiness;
use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OnboardingProgress extends Model
{
    use BelongsToBusiness, HasUuid;

    protected $table = 'onboarding_progress';

    protected $fillable = [
        'business_id',
        'current_phase',
        'current_step',
        'phase_1_status',
        'phase_1_completion_percent',
        'phase_1_completed_at',
        'phase_2_status',
        'phase_2_started_at',
        'phase_2_completed_at',
        'phase_3_status',
        'phase_3_completed_at',
        'phase_4_status',
        'launched_at',
        'overall_completion_percent',
        'onboarding_completed_at',
    ];

    protected $casts = [
        'phase_1_completed_at' => 'datetime',
        'phase_2_started_at' => 'datetime',
        'phase_2_completed_at' => 'datetime',
        'phase_3_completed_at' => 'datetime',
        'launched_at' => 'datetime',
        'onboarding_completed_at' => 'datetime',
    ];

    // Scopes
    public function scopeNotCompleted($query)
    {
        return $query->whereNull('onboarding_completed_at');
    }

    public function scopeCompleted($query)
    {
        return $query->whereNotNull('onboarding_completed_at');
    }

    // Status Checks
    public function isPhase1Completed(): bool
    {
        return $this->phase_1_status === 'completed' && $this->phase_1_completion_percent >= 100;
    }

    public function isPhase2Unlocked(): bool
    {
        return $this->isPhase1Completed() && in_array($this->phase_2_status, ['ready', 'processing', 'completed']);
    }

    public function isPhase2Completed(): bool
    {
        return $this->phase_2_status === 'completed';
    }

    public function isPhase3Unlocked(): bool
    {
        return $this->isPhase2Completed() && in_array($this->phase_3_status, ['in_progress', 'completed']);
    }

    public function isPhase3Completed(): bool
    {
        return $this->phase_3_status === 'completed';
    }

    public function isPhase4Unlocked(): bool
    {
        return $this->isPhase3Completed() && in_array($this->phase_4_status, ['ready', 'launched']);
    }

    public function isLaunched(): bool
    {
        return $this->phase_4_status === 'launched' && ! is_null($this->launched_at);
    }

    public function isOnboardingCompleted(): bool
    {
        return ! is_null($this->onboarding_completed_at);
    }

    // Helpers
    public function canStartPhase2(): bool
    {
        return $this->isPhase1Completed() && $this->phase_2_status === 'locked';
    }

    public function unlockPhase2(): void
    {
        $this->update([
            'phase_2_status' => 'ready',
            'current_phase' => 2,
        ]);
    }

    public function startPhase2(): void
    {
        $this->update([
            'phase_2_status' => 'processing',
            'phase_2_started_at' => now(),
        ]);
    }

    public function completePhase2(): void
    {
        $this->update([
            'phase_2_status' => 'completed',
            'phase_2_completed_at' => now(),
            'phase_3_status' => 'in_progress',
            'current_phase' => 3,
        ]);
    }

    public function completePhase3(): void
    {
        $this->update([
            'phase_3_status' => 'completed',
            'phase_3_completed_at' => now(),
            'phase_4_status' => 'ready',
            'current_phase' => 4,
        ]);
    }

    public function launch(): void
    {
        $now = now();
        $this->update([
            'phase_4_status' => 'launched',
            'launched_at' => $now,
            'onboarding_completed_at' => $now,
            'overall_completion_percent' => 100,
        ]);

        // Update business
        $this->business->update([
            'is_onboarding_completed' => true,
            'onboarding_completed_at' => $now,
            'launched_at' => $now,
        ]);
    }
}
