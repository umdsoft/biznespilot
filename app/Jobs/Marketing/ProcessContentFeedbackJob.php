<?php

namespace App\Jobs\Marketing;

use App\Models\Business;
use App\Services\ContentAI\ContentPerformanceFeedback;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessContentFeedbackJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;

    public int $timeout = 600;

    public function __construct(
        public ?string $businessId = null
    ) {}

    public function handle(ContentPerformanceFeedback $feedback): void
    {
        if ($this->businessId) {
            $this->processForBusiness($feedback, $this->businessId);

            return;
        }

        // Barcha Instagram ulangan bizneslar uchun
        $processed = 0;

        Business::where('status', 'active')
            ->whereHas('instagramAccounts')
            ->chunkById(50, function ($businesses) use ($feedback, &$processed) {
                foreach ($businesses as $business) {
                    try {
                        $stats = $feedback->processPublishedContent($business->id);

                        if ($stats['updated'] > 0) {
                            Log::info('ContentFeedback: business processed', [
                                'business_id' => $business->id,
                                'updated' => $stats['updated'],
                                'niche_updated' => $stats['niche_updated'],
                            ]);
                        }

                        $processed++;
                    } catch (\Throwable $e) {
                        Log::warning('ContentFeedback: business failed', [
                            'business_id' => $business->id,
                            'error' => $e->getMessage(),
                        ]);
                    }
                }
            });

        Log::info("ProcessContentFeedbackJob completed: {$processed} businesses processed");
    }

    private function processForBusiness(ContentPerformanceFeedback $feedback, string $businessId): void
    {
        $stats = $feedback->processPublishedContent($businessId);

        Log::info('ContentFeedback: single business processed', [
            'business_id' => $businessId,
            'stats' => $stats,
        ]);
    }
}
