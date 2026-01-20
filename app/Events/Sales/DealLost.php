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
 * Deal yo'qotilganida ishga tushadi
 * KPI hisoblash, tahlil, coaching uchun
 */
class DealLost implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public Lead $lead,
        public string $lostReason,
        public ?float $estimatedValue = null,
        public ?User $lostBy = null,
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
        return 'sales.deal-lost';
    }

    public function broadcastWith(): array
    {
        return [
            'lead_id' => $this->lead->id,
            'lead_name' => $this->lead->name,
            'company' => $this->lead->company,
            'lost_reason' => $this->lostReason,
            'estimated_value' => $this->estimatedValue,
            'lost_by' => $this->lostBy?->id,
            'lost_by_name' => $this->lostBy?->name,
            'lost_at' => now()->toISOString(),
        ];
    }
}
