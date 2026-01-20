<?php

namespace App\Jobs\Marketing;

use App\Models\Business;
use App\Models\Campaign;
use App\Models\MarketingChannel;
use App\Services\Marketing\MarketingKpiCalculatorService;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * CalculateMarketingKpiSnapshotsJob - Marketing KPI snapshotlarni hisoblash
 * Har kuni, haftalik va oylik ishlatiladi
 */
class CalculateMarketingKpiSnapshotsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     */
    public int $tries = 3;

    /**
     * The maximum number of seconds the job can run.
     */
    public int $timeout = 600; // 10 daqiqa

    /**
     * Create a new job instance.
     */
    public function __construct(
        public ?string $businessId = null,
        public string $periodType = 'daily',
        public ?Carbon $date = null
    ) {
        $this->date = $date ?? Carbon::yesterday();
    }

    /**
     * Execute the job.
     */
    public function handle(MarketingKpiCalculatorService $calculator): void
    {
        Log::info('CalculateMarketingKpiSnapshotsJob: Starting', [
            'business_id' => $this->businessId,
            'period_type' => $this->periodType,
            'date' => $this->date->toDateString(),
        ]);

        // Agar business_id berilmagan bo'lsa, barcha bizneslar uchun hisoblash
        $businesses = $this->businessId
            ? Business::where('id', $this->businessId)->get()
            : Business::where('status', 'active')->get();

        foreach ($businesses as $business) {
            try {
                $this->calculateForBusiness($business, $calculator);
            } catch (\Exception $e) {
                Log::error('CalculateMarketingKpiSnapshotsJob: Failed for business', [
                    'business_id' => $business->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        Log::info('CalculateMarketingKpiSnapshotsJob: Completed', [
            'businesses_count' => $businesses->count(),
        ]);
    }

    /**
     * Calculate snapshots for a single business.
     */
    protected function calculateForBusiness(Business $business, MarketingKpiCalculatorService $calculator): void
    {
        // 1. Overall snapshot (no filter)
        $this->calculateSnapshot($calculator, $business->id, null, null);

        // 2. By Channel
        $channels = MarketingChannel::where('business_id', $business->id)
            ->where('is_active', true)
            ->get();

        foreach ($channels as $channel) {
            $this->calculateSnapshot($calculator, $business->id, $channel->id, null);
        }

        // 3. By Campaign (active and recently completed)
        $campaigns = Campaign::where('business_id', $business->id)
            ->whereIn('status', ['active', 'completed', 'paused'])
            ->where('created_at', '>=', now()->subMonths(3)) // Oxirgi 3 oy
            ->get();

        foreach ($campaigns as $campaign) {
            $this->calculateSnapshot($calculator, $business->id, null, $campaign->id);
        }

        Log::info('CalculateMarketingKpiSnapshotsJob: Business completed', [
            'business_id' => $business->id,
            'channels_count' => $channels->count(),
            'campaigns_count' => $campaigns->count(),
        ]);
    }

    /**
     * Calculate and save a single snapshot.
     */
    protected function calculateSnapshot(
        MarketingKpiCalculatorService $calculator,
        string $businessId,
        ?string $channelId,
        ?string $campaignId
    ): void {
        try {
            match ($this->periodType) {
                'daily' => $calculator->calculateDailySnapshot($businessId, $this->date, $channelId, $campaignId),
                'weekly' => $calculator->calculateWeeklySnapshot($businessId, $this->date, $channelId, $campaignId),
                'monthly' => $calculator->calculateMonthlySnapshot($businessId, $this->date, $channelId, $campaignId),
                default => throw new \InvalidArgumentException("Invalid period type: {$this->periodType}"),
            };
        } catch (\Exception $e) {
            Log::warning('CalculateMarketingKpiSnapshotsJob: Snapshot calculation failed', [
                'business_id' => $businessId,
                'channel_id' => $channelId,
                'campaign_id' => $campaignId,
                'period_type' => $this->periodType,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Get the tags that should be assigned to the job.
     */
    public function tags(): array
    {
        return [
            'marketing-kpi',
            'period:' . $this->periodType,
            $this->businessId ? 'business:' . $this->businessId : 'all-businesses',
        ];
    }
}
