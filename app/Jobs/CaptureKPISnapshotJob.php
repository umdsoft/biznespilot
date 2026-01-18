<?php

namespace App\Jobs;

use App\Models\Business;
use App\Services\KPISnapshotService;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class CaptureKPISnapshotJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $backoff = 60;

    public function __construct(
        public ?Business $business = null,
        public ?Carbon $date = null
    ) {
        $this->date = $date ?? Carbon::today();
    }

    public function handle(KPISnapshotService $snapshotService): void
    {
        if ($this->business) {
            $this->captureForBusiness($snapshotService, $this->business);
        } else {
            $this->captureForAllBusinesses($snapshotService);
        }
    }

    protected function captureForBusiness(KPISnapshotService $service, Business $business): void
    {
        try {
            $snapshot = $service->captureSnapshot($business, $this->date);

            Log::info('KPI snapshot captured', [
                'business_id' => $business->id,
                'date' => $this->date->format('Y-m-d'),
                'health_score' => $snapshot->health_score,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to capture KPI snapshot', [
                'business_id' => $business->id,
                'date' => $this->date->format('Y-m-d'),
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    protected function captureForAllBusinesses(KPISnapshotService $service): void
    {
        $businesses = Business::where('is_active', true)->get();

        foreach ($businesses as $business) {
            try {
                $this->captureForBusiness($service, $business);
            } catch (\Exception $e) {
                // Log but continue with other businesses
                continue;
            }
        }

        Log::info('KPI snapshots captured for all businesses', [
            'count' => $businesses->count(),
            'date' => $this->date->format('Y-m-d'),
        ]);
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('CaptureKPISnapshotJob failed', [
            'business_id' => $this->business?->id,
            'date' => $this->date?->format('Y-m-d'),
            'error' => $exception->getMessage(),
        ]);
    }
}
