<?php

namespace App\Events\Team;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class KPIAnomalyDetected
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public string $businessId,
        public string $kpiName,
        public float $currentValue,
        public float $expectedValue,
        public float $changePercent,
    ) {}
}
