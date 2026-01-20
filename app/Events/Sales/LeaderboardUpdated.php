<?php

namespace App\Events\Sales;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Leaderboard yangilanganida ishga tushadi
 * Real-time dashboard yangilash uchun
 */
class LeaderboardUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public const TYPE_DAILY = 'daily';
    public const TYPE_WEEKLY = 'weekly';
    public const TYPE_MONTHLY = 'monthly';

    public function __construct(
        public string $businessId,
        public string $type, // daily, weekly, monthly
        public array $topPerformers, // [{user_id, name, score, rank}, ...]
        public ?array $positionChanges = null // [{user_id, old_rank, new_rank}, ...]
    ) {}

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('business.'.$this->businessId),
        ];
    }

    public function broadcastAs(): string
    {
        return 'sales.leaderboard-updated';
    }

    public function broadcastWith(): array
    {
        return [
            'type' => $this->type,
            'top_performers' => $this->topPerformers,
            'position_changes' => $this->positionChanges,
            'updated_at' => now()->toISOString(),
        ];
    }
}
