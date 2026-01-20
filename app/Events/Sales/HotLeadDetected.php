<?php

namespace App\Events\Sales;

use App\Models\Lead;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Lead score 80+ ga yetganida ishga tushadi
 * Smart Alert va Dashboard uchun real-time notification
 */
class HotLeadDetected implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public Lead $lead,
        public int $score,
        public int $previousScore,
        public string $detectedBy = 'system'
    ) {}

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('business.'.$this->lead->business_id),
        ];
    }

    public function broadcastAs(): string
    {
        return 'sales.hot-lead-detected';
    }

    public function broadcastWith(): array
    {
        return [
            'lead_id' => $this->lead->id,
            'lead_name' => $this->lead->name,
            'company' => $this->lead->company,
            'phone' => $this->lead->phone,
            'score' => $this->score,
            'previous_score' => $this->previousScore,
            'assigned_to' => $this->lead->assigned_to,
            'detected_at' => now()->toISOString(),
        ];
    }
}
