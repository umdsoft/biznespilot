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
 * Hodim ishdan ketganda ishga tushadi
 *
 * Avtomatik harakatlar:
 * - Offboarding jarayonini boshlash
 * - Exit interview yaratish
 * - Hisob-kitoblarni yakunlash
 * - Turnover analytics yangilash
 */
class EmployeeTerminated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public const REASON_VOLUNTARY = 'voluntary';      // O'z xohishi bilan
    public const REASON_INVOLUNTARY = 'involuntary';  // Majburiy
    public const REASON_RETIREMENT = 'retirement';    // Pensiya
    public const REASON_CONTRACT_END = 'contract_end'; // Shartnoma tugashi

    public function __construct(
        public User $employee,
        public Business $business,
        public string $reason,
        public ?string $terminatedBy = null,
        public ?string $notes = null,
        public ?\DateTime $lastWorkingDay = null
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
        return 'hr.employee-terminated';
    }

    public function broadcastWith(): array
    {
        return [
            'employee_id' => $this->employee->id,
            'employee_name' => $this->employee->name,
            'reason' => $this->reason,
            'reason_label' => $this->getReasonLabel(),
            'last_working_day' => $this->lastWorkingDay?->format('Y-m-d') ?? now()->format('Y-m-d'),
        ];
    }

    /**
     * Ketish sababini o'zbek tilida olish
     */
    public function getReasonLabel(): string
    {
        return match($this->reason) {
            self::REASON_VOLUNTARY => "O'z xohishi bilan",
            self::REASON_INVOLUNTARY => "Majburiy",
            self::REASON_RETIREMENT => "Pensiyaga chiqish",
            self::REASON_CONTRACT_END => "Shartnoma tugashi",
            default => "Boshqa sabab",
        };
    }

    /**
     * Ixtiyoriy ketish ekanligini tekshirish
     */
    public function isVoluntary(): bool
    {
        return $this->reason === self::REASON_VOLUNTARY;
    }
}
