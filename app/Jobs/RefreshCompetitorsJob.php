<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\CompetitorMonitor;
use App\Services\TrendSee\CompetitorSpyService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * RefreshCompetitorsJob - Weekly Competitor Data Refresher
 *
 * "Hybrid Spy" - Refreshes stale competitor data using internal or external sources.
 *
 * Workflow:
 * 1. Find all stale competitor monitors (> 14 days old)
 * 2. For each, check if internal match exists
 * 3. If internal -> recalculate from orders/leads (Cost = $0)
 * 4. If external -> call RapidAPI (Cost = $$$)
 * 5. Update competitor_monitor record
 *
 * Scheduling: Weekly (Tuesdays at 3:00 AM)
 */
class RefreshCompetitorsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $timeout = 600; // 10 minutes max

    private int $limit;

    /**
     * Create a new job instance.
     *
     * @param int $limit Maximum competitors to process per run
     */
    public function __construct(int $limit = 20)
    {
        $this->limit = $limit;
    }

    /**
     * Execute the job.
     */
    public function handle(CompetitorSpyService $spyService): void
    {
        Log::info('RefreshCompetitorsJob: Starting competitor refresh', [
            'limit' => $this->limit,
        ]);

        $stats = [
            'total_stale' => CompetitorMonitor::stale(14)->active()->count(),
            'processed' => 0,
            'internal_refreshed' => 0,
            'external_refreshed' => 0,
            'errors' => 0,
            'api_cost' => 0,
        ];

        // Get stale competitors
        $staleMonitors = CompetitorMonitor::stale(14)
            ->active()
            ->orderBy('last_scraped_at')
            ->limit($this->limit)
            ->get();

        foreach ($staleMonitors as $monitor) {
            try {
                $result = $spyService->refresh($monitor->target_url);

                $stats['processed']++;

                if ($result['success']) {
                    if ($result['is_internal'] ?? false) {
                        $stats['internal_refreshed']++;
                    } else {
                        $stats['external_refreshed']++;
                        $stats['api_cost'] += $result['cost'] ?? 0;
                    }

                    Log::debug('RefreshCompetitorsJob: Competitor refreshed', [
                        'url' => $monitor->target_url,
                        'source' => $result['source'] ?? 'unknown',
                        'is_internal' => $result['is_internal'] ?? false,
                    ]);
                } else {
                    $stats['errors']++;
                    Log::warning('RefreshCompetitorsJob: Refresh failed', [
                        'url' => $monitor->target_url,
                        'error' => $result['error'] ?? 'Unknown error',
                    ]);
                }

                // Delay between requests to avoid rate limits
                sleep(1);

            } catch (\Exception $e) {
                $stats['errors']++;
                Log::error('RefreshCompetitorsJob: Error processing competitor', [
                    'url' => $monitor->target_url,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        // Cleanup inactive monitors
        $this->cleanupInactiveMonitors();

        Log::info('RefreshCompetitorsJob: Refresh completed', $stats);
    }

    /**
     * Clean up inactive competitor monitors.
     */
    private function cleanupInactiveMonitors(): void
    {
        // Deactivate monitors not updated in 60 days
        $deactivated = CompetitorMonitor::where('last_scraped_at', '<', now()->subDays(60))
            ->update(['is_active' => false]);

        if ($deactivated > 0) {
            Log::info('RefreshCompetitorsJob: Deactivated stale monitors', ['count' => $deactivated]);
        }

        // Delete very old inactive monitors (90+ days)
        $deleted = CompetitorMonitor::where('is_active', false)
            ->where('updated_at', '<', now()->subDays(90))
            ->delete();

        if ($deleted > 0) {
            Log::info('RefreshCompetitorsJob: Deleted old inactive monitors', ['count' => $deleted]);
        }
    }

    /**
     * Handle job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('RefreshCompetitorsJob: Job failed', [
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString(),
        ]);
    }
}
