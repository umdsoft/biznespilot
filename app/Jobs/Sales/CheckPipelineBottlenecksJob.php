<?php

namespace App\Jobs\Sales;

use App\Models\Business;
use App\Services\Pipeline\PipelineBottleneckService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class CheckPipelineBottlenecksJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct()
    {
    }

    public function handle(PipelineBottleneckService $service): void
    {
        Log::info('CheckPipelineBottlenecksJob: Starting bottleneck check');

        // Barcha active business lar uchun
        Business::whereHas('owner')
            ->chunk(50, function ($businesses) use ($service) {
                foreach ($businesses as $business) {
                    $this->checkBusinessBottlenecks($business, $service);
                }
            });

        Log::info('CheckPipelineBottlenecksJob: Completed');
    }

    /**
     * Bitta business uchun bottleneck tekshirish
     */
    protected function checkBusinessBottlenecks(Business $business, PipelineBottleneckService $service): void
    {
        try {
            $bottlenecks = $service->detectBottlenecks($business);

            // Critical va high severity bottleneck lar uchun alert yaratish
            foreach ($bottlenecks as $bottleneck) {
                if (in_array($bottleneck['severity'], ['critical', 'high'])) {
                    $service->createBottleneckAlert($business, $bottleneck);
                }
            }

            if (count($bottlenecks) > 0) {
                Log::info('CheckPipelineBottlenecksJob: Found bottlenecks', [
                    'business_id' => $business->id,
                    'count' => count($bottlenecks),
                    'critical_count' => collect($bottlenecks)->where('severity', 'critical')->count(),
                ]);
            }
        } catch (\Exception $e) {
            Log::error('CheckPipelineBottlenecksJob: Error checking business', [
                'business_id' => $business->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
