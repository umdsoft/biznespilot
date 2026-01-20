<?php

namespace App\Jobs\Sales;

use App\Models\Business;
use App\Models\SalesKpiDailySnapshot;
use App\Services\Sales\AlertService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendProgressReminderJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Job timeout
     */
    public int $timeout = 300;

    /**
     * Max attempts
     */
    public int $tries = 3;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public ?string $businessId = null
    ) {}

    /**
     * Execute the job.
     */
    public function handle(AlertService $alertService): void
    {
        $startTime = microtime(true);

        try {
            if ($this->businessId) {
                $business = Business::find($this->businessId);
                if ($business) {
                    $this->processForBusiness($alertService, $business);
                }
            } else {
                Business::whereHas('subscription', function ($q) {
                    $q->where('status', 'active');
                })->chunk(50, function ($businesses) use ($alertService) {
                    foreach ($businesses as $business) {
                        $this->processForBusiness($alertService, $business);
                    }
                });
            }

            $duration = round(microtime(true) - $startTime, 2);
            Log::info('SendProgressReminderJob: Completed', [
                'duration_seconds' => $duration,
            ]);
        } catch (\Exception $e) {
            Log::error('SendProgressReminderJob: Failed', [
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Bitta biznes uchun progress reminder yuborish
     */
    protected function processForBusiness(AlertService $alertService, Business $business): void
    {
        try {
            // Sotuv operatorlarini olish
            $operators = $business->users()
                ->wherePivot('sales_role', 'sales_operator')
                ->get();

            foreach ($operators as $operator) {
                $todayScore = SalesKpiDailySnapshot::getDailyOverallScore(
                    $business->id,
                    $operator->id,
                    today()
                );

                // Agar progress 50% dan past bo'lsa, eslatma yuborish
                if ($todayScore < 50) {
                    $alertService->createAlert(
                        $business,
                        'target_reminder',
                        'Kun yarimga yetdi!',
                        "Hozirgi KPI: {$todayScore}%. Maqsadga yetish uchun tezlashtiring!",
                        [
                            'user_id' => $operator->id,
                            'priority' => $todayScore < 30 ? 'high' : 'medium',
                            'expires_at' => today()->setTime(18, 0),
                            'data' => [
                                'current_score' => $todayScore,
                                'target_score' => 100,
                            ],
                            'channels' => ['app'],
                        ]
                    );
                }
            }
        } catch (\Exception $e) {
            Log::error('SendProgressReminderJob: Business failed', [
                'business_id' => $business->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
