<?php

namespace App\Events\HR;

use App\Models\User;
use App\Models\Business;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Yangi hodim ishga qabul qilinganda ishga tushadi
 *
 * Avtomatik harakatlar:
 * - Onboarding jarayonini boshlash
 * - Welcome email yuborish
 * - 30-60-90 kunlik rejani yaratish
 * - HR dashboardni yangilash
 */
class EmployeeHired implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public User $employee,
        public Business $business,
        public ?string $department = null,
        public ?string $position = null,
        public ?string $hiredBy = null,
        public ?array $onboardingData = null
    ) {}

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('business.' . $this->business->id),
            new PrivateChannel('hr.' . $this->business->id),
        ];
    }

    public function broadcastAs(): string
    {
        return 'hr.employee-hired';
    }

    public function broadcastWith(): array
    {
        return [
            'employee_id' => $this->employee->id,
            'employee_name' => $this->employee->name,
            'department' => $this->department,
            'position' => $this->position,
            'hired_at' => now()->toISOString(),
        ];
    }

    /**
     * Onboarding ma'lumotlarini olish
     */
    public function getOnboardingData(): array
    {
        return $this->onboardingData ?? [
            'start_date' => now()->toDateString(),
            'department' => $this->department,
            'position' => $this->position,
            'mentor_id' => null,
            'probation_end' => now()->addMonths(3)->toDateString(),
        ];
    }
}
