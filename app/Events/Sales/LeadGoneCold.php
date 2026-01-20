<?php

namespace App\Events\Sales;

use App\Models\Lead;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Lead score cold (20-39) yoki frozen (0-19) ga tushganida ishga tushadi
 * Smart Alert va re-engagement uchun signal
 */
class LeadGoneCold implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public Lead $lead,
        public int $score,
        public int $previousScore,
        public string $category, // 'cold' or 'frozen'
        public ?int $daysWithoutContact = null
    ) {}

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('business.'.$this->lead->business_id),
        ];
    }

    public function broadcastAs(): string
    {
        return 'sales.lead-gone-cold';
    }

    public function broadcastWith(): array
    {
        return [
            'lead_id' => $this->lead->id,
            'lead_name' => $this->lead->name,
            'company' => $this->lead->company,
            'score' => $this->score,
            'previous_score' => $this->previousScore,
            'category' => $this->category,
            'days_without_contact' => $this->daysWithoutContact,
            'assigned_to' => $this->lead->assigned_to,
            'detected_at' => now()->toISOString(),
        ];
    }
}
