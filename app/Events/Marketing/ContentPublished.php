<?php

namespace App\Events\Marketing;

use App\Models\ContentGeneration;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Kontent publish qilinganda chiqaradigan event.
 * Listen qiladigan modullar: MarketingOrchestrator cache invalidate, KPI update.
 */
class ContentPublished
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public ContentGeneration $content,
        public string $businessId,
    ) {}
}
