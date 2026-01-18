<?php

namespace App\Events;

use App\Models\Alert;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class AlertTriggered implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public Alert $alert
    ) {}

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('business.'.$this->alert->business_id),
        ];
    }

    public function broadcastAs(): string
    {
        return 'alert.triggered';
    }

    public function broadcastWith(): array
    {
        return [
            'id' => $this->alert->id,
            'type' => $this->alert->type,
            'severity' => $this->alert->severity,
            'title' => $this->alert->title,
            'message' => $this->alert->message,
            'triggered_at' => $this->alert->triggered_at->toISOString(),
        ];
    }
}
