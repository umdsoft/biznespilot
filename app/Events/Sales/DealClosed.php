<?php

namespace App\Events\Sales;

use App\Models\Lead;
use App\Models\User;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Deal yopilganida (won) ishga tushadi
 * KPI hisoblash, Bonus, Leaderboard yangilash uchun
 */
class DealClosed implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public Lead $lead,
        public float $amount,
        public ?User $closedBy = null,
        public ?string $notes = null
    ) {}

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('business.'.$this->lead->business_id),
        ];
    }

    public function broadcastAs(): string
    {
        return 'sales.deal-closed';
    }

    public function broadcastWith(): array
    {
        return [
            'lead_id' => $this->lead->id,
            'lead_name' => $this->lead->name,
            'company' => $this->lead->company,
            'amount' => $this->amount,
            'closed_by' => $this->closedBy?->id,
            'closed_by_name' => $this->closedBy?->name,
            'closed_at' => now()->toISOString(),
        ];
    }
}
