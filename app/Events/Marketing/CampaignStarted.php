<?php

namespace App\Events\Marketing;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class CampaignStarted
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public string $businessId,
        public string $campaignId,
        public ?float $budget = null,
    ) {}
}
