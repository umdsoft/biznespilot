<?php

namespace App\Events;

use App\Models\Lead;
use App\Models\PipelineStage;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class LeadStageChanged
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public Lead $lead,
        public ?PipelineStage $oldStage,
        public PipelineStage $newStage,
        public string $reason,
        public bool $automated = false
    ) {}
}
