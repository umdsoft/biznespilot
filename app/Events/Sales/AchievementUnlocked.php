<?php

namespace App\Events\Sales;

use App\Models\User;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Yangi yutuq ochilganida ishga tushadi
 * Gamification, Notification, Dashboard uchun
 */
class AchievementUnlocked implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public User $user,
        public string $businessId,
        public string $achievementCode, // first_sale, streak_7, calls_100, etc.
        public string $achievementName,
        public string $achievementDescription,
        public string $achievementIcon,
        public int $points = 0,
        public ?string $tier = null // bronze, silver, gold, platinum
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
        return 'sales.achievement-unlocked';
    }

    public function broadcastWith(): array
    {
        return [
            'user_id' => $this->user->id,
            'user_name' => $this->user->name,
            'achievement_code' => $this->achievementCode,
            'achievement_name' => $this->achievementName,
            'achievement_description' => $this->achievementDescription,
            'achievement_icon' => $this->achievementIcon,
            'points' => $this->points,
            'tier' => $this->tier,
            'unlocked_at' => now()->toISOString(),
        ];
    }
}
