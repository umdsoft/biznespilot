<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\GlobalTrend;
use App\Services\TrendSee\TrendEngineService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * FetchTrendsJob - Weekly Trends Fetcher
 *
 * "Fetch Once, Serve Many" - Fetches global search trends for all niches.
 *
 * Workflow:
 * 1. Iterate through all configured niches
 * 2. Fetch trends from external API (DataForSEO) or use mock data
 * 3. Store in database with 7-day expiry
 * 4. Update cache for immediate availability
 *
 * Scheduling: Weekly (Sundays at 6:00 AM)
 */
class FetchTrendsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $timeout = 600; // 10 minutes max

    private string $region;
    private string $platform;
    private ?array $niches;

    /**
     * Create a new job instance.
     *
     * @param string $region Region code (default: UZ)
     * @param string $platform Platform: google, tiktok (default: google)
     * @param array|null $niches Specific niches to fetch (null = all)
     */
    public function __construct(
        string $region = 'UZ',
        string $platform = 'google',
        ?array $niches = null
    ) {
        $this->region = $region;
        $this->platform = $platform;
        $this->niches = $niches;
    }

    /**
     * Execute the job.
     */
    public function handle(TrendEngineService $trendEngine): void
    {
        $niches = $this->niches ?? array_keys(GlobalTrend::getAvailableNiches());

        Log::info('FetchTrendsJob: Starting trends fetch', [
            'region' => $this->region,
            'platform' => $this->platform,
            'niches_count' => count($niches),
        ]);

        $stats = [
            'total' => count($niches),
            'success' => 0,
            'failed' => 0,
            'from_api' => 0,
            'from_mock' => 0,
            'total_keywords' => 0,
        ];

        foreach ($niches as $niche) {
            try {
                $result = $trendEngine->refresh($niche, $this->region, $this->platform);

                if ($result['success']) {
                    $stats['success']++;

                    // Track data source
                    if (($result['source'] ?? '') === 'dataforseo') {
                        $stats['from_api']++;
                    } else {
                        $stats['from_mock']++;
                    }

                    // Count keywords
                    $keywordsCount = count($result['data']['top_keywords'] ?? []);
                    $stats['total_keywords'] += $keywordsCount;

                    Log::debug('FetchTrendsJob: Niche processed', [
                        'niche' => $niche,
                        'keywords_count' => $keywordsCount,
                        'source' => $result['source'] ?? 'unknown',
                    ]);
                } else {
                    $stats['failed']++;
                    Log::warning('FetchTrendsJob: Niche fetch failed', [
                        'niche' => $niche,
                        'error' => $result['error'] ?? 'Unknown error',
                    ]);
                }

                // Delay between requests to avoid rate limits
                usleep(500000); // 0.5 second

            } catch (\Exception $e) {
                $stats['failed']++;
                Log::error('FetchTrendsJob: Error processing niche', [
                    'niche' => $niche,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        // Cleanup old trends (older than 30 days)
        $this->cleanupOldTrends();

        Log::info('FetchTrendsJob: Fetch completed', $stats);
    }

    /**
     * Clean up old trends data.
     */
    private function cleanupOldTrends(): void
    {
        $deleted = GlobalTrend::where('fetched_at', '<', now()->subDays(30))->delete();

        if ($deleted > 0) {
            Log::info('FetchTrendsJob: Cleaned up old trends', ['deleted' => $deleted]);
        }
    }

    /**
     * Handle job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('FetchTrendsJob: Job failed', [
            'region' => $this->region,
            'platform' => $this->platform,
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString(),
        ]);
    }
}
