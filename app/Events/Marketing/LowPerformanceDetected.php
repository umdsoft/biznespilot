<?php

namespace App\Events\Marketing;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Past samaradorlik aniqlanganda (ROAS, engagement, konversiya)
 */
class LowPerformanceDetected
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public string $businessId,
        public string $metricType, // roas, engagement, conversion
        public float $currentValue,
        public float $threshold,
        public array $context = [],
    ) {}
}
