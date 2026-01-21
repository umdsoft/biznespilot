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
 * Hodim lavozimi oshganda ishga tushadi
 *
 * Avtomatik harakatlar:
 * - Maosh tuzilmasini yangilash
 * - Yangi vazifalar tayinlash
 * - Tabriklov xabarini yuborish
 * - HR analytics yangilash
 */
class EmployeePromoted implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public User $employee,
        public Business $business,
        public string $oldPosition,
        public string $newPosition,
        public ?string $oldDepartment = null,
        public ?string $newDepartment = null,
        public ?float $salaryChange = null,
        public ?string $promotedBy = null,
        public ?string $effectiveDate = null
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
        return 'hr.employee-promoted';
    }

    public function broadcastWith(): array
    {
        return [
            'employee_id' => $this->employee->id,
            'employee_name' => $this->employee->name,
            'old_position' => $this->oldPosition,
            'new_position' => $this->newPosition,
            'department_changed' => $this->oldDepartment !== $this->newDepartment,
            'effective_date' => $this->effectiveDate ?? now()->toDateString(),
        ];
    }

    /**
     * Bo'lim ham o'zgarganligini tekshirish
     */
    public function isDepartmentChanged(): bool
    {
        return $this->oldDepartment !== null
            && $this->newDepartment !== null
            && $this->oldDepartment !== $this->newDepartment;
    }

    /**
     * Maosh o'zgarish foizini hisoblash
     */
    public function getSalaryChangePercentage(?float $oldSalary = null): ?float
    {
        if ($this->salaryChange === null || $oldSalary === null || $oldSalary <= 0) {
            return null;
        }

        return round(($this->salaryChange / $oldSalary) * 100, 2);
    }
}
