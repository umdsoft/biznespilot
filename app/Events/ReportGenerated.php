<?php

namespace App\Events;

use App\Models\GeneratedReport;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ReportGenerated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public GeneratedReport $report
    ) {}

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('business.' . $this->report->business_id),
        ];
    }

    public function broadcastAs(): string
    {
        return 'report.generated';
    }

    public function broadcastWith(): array
    {
        return [
            'id' => $this->report->id,
            'type' => $this->report->report_type,
            'title' => $this->report->title,
            'period' => $this->report->getPeriodLabel(),
            'has_pdf' => $this->report->hasPdf(),
            'created_at' => $this->report->created_at->toISOString(),
        ];
    }
}
