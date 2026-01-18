<?php

namespace App\Jobs;

use App\Models\Competitor;
use App\Services\CompetitorMonitoringService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ScrapeCompetitorData implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected ?string $competitorId;

    protected ?string $businessId;

    /**
     * Create a new job instance.
     *
     * @param  string|null  $competitorId  Monitor specific competitor (UUID)
     * @param  string|null  $businessId  Monitor all competitors for business (UUID)
     */
    public function __construct(?string $competitorId = null, ?string $businessId = null)
    {
        $this->competitorId = $competitorId;
        $this->businessId = $businessId;
    }

    /**
     * Execute the job.
     */
    public function handle(CompetitorMonitoringService $monitoringService): void
    {
        try {
            if ($this->competitorId) {
                // Monitor specific competitor
                $competitor = Competitor::findOrFail($this->competitorId);

                Log::info('Starting competitor monitoring', [
                    'competitor_id' => $competitor->id,
                    'competitor_name' => $competitor->name,
                ]);

                $result = $monitoringService->monitorCompetitor($competitor);

                Log::info('Competitor monitoring completed', [
                    'competitor_id' => $competitor->id,
                    'result' => $result,
                ]);

            } elseif ($this->businessId) {
                // Monitor all competitors for business
                Log::info('Starting bulk competitor monitoring', [
                    'business_id' => $this->businessId,
                ]);

                $results = $monitoringService->monitorAllCompetitors($this->businessId);

                Log::info('Bulk competitor monitoring completed', [
                    'business_id' => $this->businessId,
                    'total' => $results['total'],
                    'successful' => $results['successful'],
                    'failed' => $results['failed'],
                ]);

            } else {
                // Monitor all active competitors across all businesses
                $competitors = Competitor::where('status', 'active')
                    ->where('auto_monitor', true)
                    ->where(function ($query) {
                        // Only monitor if it's time based on check_frequency_hours
                        $query->whereNull('last_checked_at')
                            ->orWhereRaw('TIMESTAMPDIFF(HOUR, last_checked_at, NOW()) >= check_frequency_hours');
                    })
                    ->get();

                Log::info('Starting scheduled competitor monitoring', [
                    'total_competitors' => $competitors->count(),
                ]);

                $successful = 0;
                $failed = 0;

                foreach ($competitors as $competitor) {
                    $result = $monitoringService->monitorCompetitor($competitor);

                    if ($result['success']) {
                        $successful++;
                    } else {
                        $failed++;
                    }
                }

                Log::info('Scheduled competitor monitoring completed', [
                    'total' => $competitors->count(),
                    'successful' => $successful,
                    'failed' => $failed,
                ]);
            }

        } catch (\Exception $e) {
            Log::error('Competitor scraping job failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }

    /**
     * The job failed to process.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('ScrapeCompetitorData job failed', [
            'competitor_id' => $this->competitorId,
            'business_id' => $this->businessId,
            'error' => $exception->getMessage(),
        ]);
    }
}
