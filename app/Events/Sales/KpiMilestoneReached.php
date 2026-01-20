<?php

namespace App\Events\Sales;

use App\Models\User;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * KPI maqsadiga yetilganida ishga tushadi
 * Gamification, Achievement unlock, Notification uchun
 */
class KpiMilestoneReached implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public const MILESTONE_50 = '50_percent';
    public const MILESTONE_75 = '75_percent';
    public const MILESTONE_100 = '100_percent';
    public const MILESTONE_120 = '120_percent';
    public const MILESTONE_150 = '150_percent';

    public function __construct(
        public User $user,
        public string $businessId,
        public string $kpiType, // calls_made, leads_converted, revenue, etc.
        public string $milestone, // 50_percent, 100_percent, etc.
        public float $currentValue,
        public float $targetValue,
        public string $period = 'monthly' // daily, weekly, monthly
    ) {}

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('business.'.$this->businessId),
            new PrivateChannel('user.'.$this->user->id),
        ];
    }

    public function broadcastAs(): string
    {
        return 'sales.kpi-milestone-reached';
    }

    public function broadcastWith(): array
    {
        return [
            'user_id' => $this->user->id,
            'user_name' => $this->user->name,
            'kpi_type' => $this->kpiType,
            'milestone' => $this->milestone,
            'current_value' => $this->currentValue,
            'target_value' => $this->targetValue,
            'percentage' => round(($this->currentValue / $this->targetValue) * 100, 1),
            'period' => $this->period,
            'reached_at' => now()->toISOString(),
        ];
    }
}
