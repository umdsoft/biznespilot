<?php

namespace App\Events\Sales;

use App\Models\User;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Jarima qo'llanilganida ishga tushadi
 * Notification, Dashboard yangilash uchun
 */
class PenaltyApplied implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public const TYPE_WARNING = 'warning';
    public const TYPE_PENALTY = 'penalty';

    public const TRIGGER_MANUAL = 'manual';
    public const TRIGGER_AUTO = 'auto';

    public function __construct(
        public User $user,
        public string $businessId,
        public string $type, // warning, penalty
        public string $reason,
        public ?float $amount = null,
        public string $trigger = self::TRIGGER_AUTO,
        public ?string $relatedEntityType = null, // lead, task
        public ?string $relatedEntityId = null,
        public ?string $notes = null
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
        return 'sales.penalty-applied';
    }

    public function broadcastWith(): array
    {
        return [
            'user_id' => $this->user->id,
            'user_name' => $this->user->name,
            'type' => $this->type,
            'reason' => $this->reason,
            'amount' => $this->amount,
            'trigger' => $this->trigger,
            'related_entity_type' => $this->relatedEntityType,
            'related_entity_id' => $this->relatedEntityId,
            'applied_at' => now()->toISOString(),
        ];
    }
}
