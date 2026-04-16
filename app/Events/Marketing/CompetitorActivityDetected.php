<?php

namespace App\Events\Marketing;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Raqobatchi faoliyati aniqlanganda (yangi post, narx o'zgarishi, aksiya)
 */
class CompetitorActivityDetected
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public string $businessId,
        public string $competitorId,
        public string $activityType, // new_post, price_change, promotion, content_trend
        public array $data,
    ) {}
}
