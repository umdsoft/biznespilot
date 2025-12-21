<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OnboardingProgressResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'business_id' => $this->business_id,
            'current_phase' => $this->current_phase,
            'overall_completion_percent' => $this->overall_completion_percent,
            'phase_1' => [
                'status' => $this->phase_1_status,
                'percent' => $this->phase_1_completion_percent,
                'completed_at' => $this->phase_1_completed_at?->toIso8601String(),
            ],
            'phase_2' => [
                'status' => $this->phase_2_status,
                'is_unlocked' => $this->isPhase2Unlocked(),
                'unlocked_at' => $this->phase_2_unlocked_at?->toIso8601String(),
                'completed_at' => $this->phase_2_completed_at?->toIso8601String(),
            ],
            'phase_3' => [
                'status' => $this->phase_3_status,
                'is_unlocked' => $this->isPhase3Unlocked(),
                'unlocked_at' => $this->phase_3_unlocked_at?->toIso8601String(),
                'completed_at' => $this->phase_3_completed_at?->toIso8601String(),
            ],
            'phase_4' => [
                'status' => $this->phase_4_status,
                'is_unlocked' => $this->isPhase4Unlocked(),
                'launched_at' => $this->launched_at?->toIso8601String(),
            ],
            'can_start_phase_2' => $this->canStartPhase2(),
            'is_launched' => $this->isLaunched(),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
