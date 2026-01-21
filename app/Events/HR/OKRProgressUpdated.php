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
 * OKR progress yangilanganda ishga tushadi
 *
 * Avtomatik harakatlar:
 * - Progress 100% - maqsadni yakunlash
 * - Progress past - ogohlantirish
 * - Team OKR yangilash
 * - Leaderboard yangilash
 */
class OKRProgressUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public User $employee,
        public Business $business,
        public string $objectiveId,
        public string $objectiveTitle,
        public float $oldProgress,
        public float $newProgress,
        public ?string $keyResultId = null,
        public ?string $keyResultTitle = null,
        public ?string $quarter = null
    ) {}

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('hr.' . $this->business->id),
            new PrivateChannel('user.' . $this->employee->id),
        ];
    }

    public function broadcastAs(): string
    {
        return 'hr.okr-progress-updated';
    }

    public function broadcastWith(): array
    {
        return [
            'employee_id' => $this->employee->id,
            'employee_name' => $this->employee->name,
            'objective_id' => $this->objectiveId,
            'objective_title' => $this->objectiveTitle,
            'old_progress' => $this->oldProgress,
            'new_progress' => $this->newProgress,
            'progress_change' => $this->getProgressChange(),
            'status' => $this->getStatus(),
            'status_label' => $this->getStatusLabel(),
        ];
    }

    /**
     * Progress o'zgarishini hisoblash
     */
    public function getProgressChange(): float
    {
        return round($this->newProgress - $this->oldProgress, 2);
    }

    /**
     * Joriy holatni aniqlash
     */
    public function getStatus(): string
    {
        return match(true) {
            $this->newProgress >= 100 => 'completed',
            $this->newProgress >= 70 => 'on_track',
            $this->newProgress >= 40 => 'at_risk',
            default => 'behind',
        };
    }

    /**
     * Holatni o'zbek tilida olish
     */
    public function getStatusLabel(): string
    {
        return match($this->getStatus()) {
            'completed' => "Yakunlandi",
            'on_track' => "Rejada",
            'at_risk' => "Xavf ostida",
            'behind' => "Orqada qolmoqda",
        };
    }

    /**
     * Maqsad yakunlanganmi?
     */
    public function isCompleted(): bool
    {
        return $this->newProgress >= 100;
    }

    /**
     * Sezilarli progress bormi?
     */
    public function hasSignificantProgress(): bool
    {
        return $this->getProgressChange() >= 10;
    }

    /**
     * Progress tushib ketdimi?
     */
    public function isRegression(): bool
    {
        return $this->newProgress < $this->oldProgress;
    }
}
