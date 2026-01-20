<?php

namespace App\Jobs\Marketing;

use App\Models\Business;
use App\Services\MarketingBonusCalculatorService;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * CalculateMarketingBonusesJob - Marketing bonuslarini hisoblash
 * Har oy oxirida ishga tushiriladi
 */
class CalculateMarketingBonusesJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $timeout = 900; // 15 minutes

    public function __construct(
        public ?string $businessId = null,
        public ?Carbon $month = null
    ) {
        $this->month = $month ?? now()->subMonth(); // Default to previous month
    }

    public function handle(MarketingBonusCalculatorService $bonusService): void
    {
        Log::info('CalculateMarketingBonusesJob: Starting', [
            'business_id' => $this->businessId,
            'month' => $this->month->format('Y-m'),
        ]);

        $businesses = $this->businessId
            ? Business::where('id', $this->businessId)->get()
            : Business::where('status', 'active')->get();

        $totalBonuses = 0;
        $totalAmount = 0;

        foreach ($businesses as $business) {
            try {
                $bonuses = $bonusService->calculateAllBonuses($business, $this->month);
                $totalBonuses += $bonuses->count();
                $totalAmount += $bonuses->sum('final_amount');

                Log::info('CalculateMarketingBonusesJob: Business completed', [
                    'business_id' => $business->id,
                    'bonuses_count' => $bonuses->count(),
                    'total_amount' => $bonuses->sum('final_amount'),
                ]);
            } catch (\Exception $e) {
                Log::error('CalculateMarketingBonusesJob: Failed for business', [
                    'business_id' => $business->id,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);
            }
        }

        Log::info('CalculateMarketingBonusesJob: Completed', [
            'businesses_count' => $businesses->count(),
            'total_bonuses' => $totalBonuses,
            'total_amount' => $totalAmount,
        ]);
    }

    public function tags(): array
    {
        return [
            'marketing-bonus',
            'month:' . $this->month->format('Y-m'),
            $this->businessId ? 'business:' . $this->businessId : 'all-businesses',
        ];
    }
}
