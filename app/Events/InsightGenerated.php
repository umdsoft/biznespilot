<?php

namespace App\Events;

use App\Models\AiInsight;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class InsightGenerated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public AiInsight $insight
    ) {}

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('business.' . $this->insight->business_id),
        ];
    }

    public function broadcastAs(): string
    {
        return 'insight.generated';
    }

    public function broadcastWith(): array
    {
        return [
            'id' => $this->insight->id,
            'type' => $this->insight->type,
            'category' => $this->insight->category,
            'priority' => $this->insight->priority,
            'title' => $this->insight->title,
            'summary' => $this->insight->summary,
            'created_at' => $this->insight->created_at->toISOString(),
        ];
    }
}
