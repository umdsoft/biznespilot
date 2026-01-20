<?php

namespace App\Jobs\Marketing;

use App\Models\Business;
use App\Services\MarketingLeaderboardService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * UpdateMarketingLeaderboardsJob - Marketing leaderboardlarini yangilash
 * Har hafta va har oy ishga tushiriladi
 */
class UpdateMarketingLeaderboardsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $timeout = 600;

    public function __construct(
        public ?string $businessId = null,
        public string $periodType = 'weekly'
    ) {}

    public function handle(MarketingLeaderboardService $leaderboardService): void
    {
        Log::info('UpdateMarketingLeaderboardsJob: Starting', [
            'business_id' => $this->businessId,
            'period_type' => $this->periodType,
        ]);

        $businesses = $this->businessId
            ? Business::where('id', $this->businessId)->get()
            : Business::where('status', 'active')->get();

        $totalEntries = 0;

        foreach ($businesses as $business) {
            try {
                $entries = match ($this->periodType) {
                    'weekly' => $leaderboardService->updateWeeklyLeaderboard($business),
                    'monthly' => $leaderboardService->updateMonthlyLeaderboard($business),
                    default => collect(),
                };

                $totalEntries += $entries->count();

                Log::info('UpdateMarketingLeaderboardsJob: Business completed', [
                    'business_id' => $business->id,
                    'entries_count' => $entries->count(),
                ]);
            } catch (\Exception $e) {
                Log::error('UpdateMarketingLeaderboardsJob: Failed for business', [
                    'business_id' => $business->id,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);
            }
        }

        Log::info('UpdateMarketingLeaderboardsJob: Completed', [
            'businesses_count' => $businesses->count(),
            'total_entries' => $totalEntries,
        ]);
    }

    public function tags(): array
    {
        return [
            'marketing-leaderboard',
            'period:' . $this->periodType,
            $this->businessId ? 'business:' . $this->businessId : 'all-businesses',
        ];
    }
}
