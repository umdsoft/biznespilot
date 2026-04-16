<?php

namespace App\Events\Marketing;

use App\Models\ContentGeneration;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Kontent engagement yangilanganda (like, comment, share kelganida)
 */
class ContentEngagementUpdated
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public ContentGeneration $content,
        public string $businessId,
        public float $oldEngagement,
        public float $newEngagement,
    ) {}
}
