<?php

namespace App\Events\Team;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class HotLeadUnanswered
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public string $businessId,
        public string $leadId,
        public string $leadName,
        public int $score,
        public int $minutesWaiting,
    ) {}
}
