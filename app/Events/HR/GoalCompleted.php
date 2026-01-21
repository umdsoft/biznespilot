<?php

namespace App\Events\HR;

use App\Models\User;
use App\Models\Business;
use App\Models\EmployeeGoal;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Hodim maqsadini yakunlaganda ishga tushadi
 *
 * Avtomatik harakatlar:
 * - Tabriklov xabari yuborish
 * - Achievement ochish
 * - Bonus hisoblash (agar bog'langan bo'lsa)
 * - Performance ball yangilash
 */
class GoalCompleted implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public User $employee,
        public Business $business,
        public EmployeeGoal $goal,
        public ?int $daysEarly = null,  // Muddatdan necha kun oldin
        public ?float $achievementPercentage = null
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
        return 'hr.goal-completed';
    }

    public function broadcastWith(): array
    {
        return [
            'employee_id' => $this->employee->id,
            'employee_name' => $this->employee->name,
            'goal_id' => $this->goal->id,
            'goal_title' => $this->goal->title,
            'completed_early' => $this->isCompletedEarly(),
            'days_early' => $this->daysEarly,
            'achievement_percentage' => $this->achievementPercentage ?? 100,
            'completion_quality' => $this->getCompletionQuality(),
        ];
    }

    /**
     * Muddatdan oldin yakunlanganmi?
     */
    public function isCompletedEarly(): bool
    {
        return $this->daysEarly !== null && $this->daysEarly > 0;
    }

    /**
     * Yakunlash sifatini baholash
     */
    public function getCompletionQuality(): string
    {
        $percentage = $this->achievementPercentage ?? 100;
        $early = $this->daysEarly ?? 0;

        if ($percentage >= 120 && $early > 7) {
            return 'exceptional';
        } elseif ($percentage >= 100 && $early > 0) {
            return 'excellent';
        } elseif ($percentage >= 100) {
            return 'good';
        } else {
            return 'partial';
        }
    }

    /**
     * Sifat labelini o'zbek tilida olish
     */
    public function getCompletionQualityLabel(): string
    {
        return match($this->getCompletionQuality()) {
            'exceptional' => "Ajoyib",
            'excellent' => "A'lo",
            'good' => "Yaxshi",
            'partial' => "Qisman",
        };
    }

    /**
     * Bonus hisoblash uchun koeffitsient
     */
    public function getBonusMultiplier(): float
    {
        return match($this->getCompletionQuality()) {
            'exceptional' => 1.5,
            'excellent' => 1.2,
            'good' => 1.0,
            'partial' => 0.5,
        };
    }
}
