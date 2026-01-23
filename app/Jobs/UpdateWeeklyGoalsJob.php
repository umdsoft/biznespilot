<?php

namespace App\Jobs;

use App\Models\Business;
use App\Services\WeeklyGoalsService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class UpdateWeeklyGoalsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public ?string $businessId = null
    ) {}

    public function handle(WeeklyGoalsService $goalsService): void
    {
        Log::info('UpdateWeeklyGoalsJob started', [
            'business_id' => $this->businessId,
        ]);

        if ($this->businessId) {
            $businesses = Business::where('id', $this->businessId)->get();
        } else {
            $businesses = Business::where('status', 'active')
                ->whereHas('leads')
                ->get();
        }

        $updated = 0;
        $errors = 0;

        foreach ($businesses as $business) {
            try {
                // Get or create current week goal
                $goal = $goalsService->getOrCreateGoal($business);

                // Update actuals from analytics
                $goalsService->updateActuals($goal);

                // Update operator KPIs
                $goalsService->updateOperatorKpis($business, $goal->week_start);

                $updated++;
            } catch (\Exception $e) {
                $errors++;
                Log::error('Failed to update weekly goal', [
                    'business_id' => $business->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        Log::info('UpdateWeeklyGoalsJob completed', [
            'updated' => $updated,
            'errors' => $errors,
        ]);
    }
}
