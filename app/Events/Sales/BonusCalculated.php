<?php

namespace App\Events\Sales;

use App\Models\User;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Bonus hisoblanganida ishga tushadi
 * Notification, Dashboard yangilash uchun
 */
class BonusCalculated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public const STATUS_PENDING = 'pending';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_REJECTED = 'rejected';
    public const STATUS_PAID = 'paid';

    public function __construct(
        public User $user,
        public string $businessId,
        public float $amount,
        public string $period, // 'monthly', 'quarterly'
        public string $periodLabel, // '2026-01', 'Q1-2026'
        public float $kpiScore,
        public float $multiplier,
        public string $status = self::STATUS_PENDING,
        public ?array $breakdown = null
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
        return 'sales.bonus-calculated';
    }

    public function broadcastWith(): array
    {
        return [
            'user_id' => $this->user->id,
            'user_name' => $this->user->name,
            'amount' => $this->amount,
            'period' => $this->period,
            'period_label' => $this->periodLabel,
            'kpi_score' => $this->kpiScore,
            'multiplier' => $this->multiplier,
            'status' => $this->status,
            'calculated_at' => now()->toISOString(),
        ];
    }
}
