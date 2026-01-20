<?php

namespace App\Jobs\Sales;

use App\Models\Business;
use App\Models\BusinessUser;
use App\Services\Sales\AchievementService;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Yutuqlarni tekshirish va berish
 * Kuniga bir marta soat 00:05 da ishga tushadi
 */
class CheckAchievementsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Qayta urinishlar soni
     */
    public int $tries = 3;

    /**
     * Job timeout (15 daqiqa)
     */
    public int $timeout = 900;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public ?string $businessId = null
    ) {}

    /**
     * Execute the job.
     */
    public function handle(AchievementService $achievementService): void
    {
        Log::info('CheckAchievementsJob started', [
            'business_id' => $this->businessId,
        ]);

        if ($this->businessId) {
            $this->processBusinessAchievements($achievementService, $this->businessId);
        } else {
            $this->processAllBusinesses($achievementService);
        }

        Log::info('CheckAchievementsJob completed');
    }

    /**
     * Bitta biznes uchun yutuqlarni tekshirish
     */
    protected function processBusinessAchievements(AchievementService $service, string $businessId): void
    {
        try {
            // Sotuv operatorlarini olish
            $operators = BusinessUser::where('business_id', $businessId)
                ->whereIn('department', ['sales_operator', 'sales_head'])
                ->whereNotNull('accepted_at')
                ->pluck('user_id');

            $awardedCount = 0;

            foreach ($operators as $userId) {
                try {
                    // Kunlik yutuqlarni tekshirish
                    $awarded = $this->checkUserAchievements($service, $businessId, $userId);
                    $awardedCount += $awarded;

                    // Streakni yangilash
                    $service->processEndOfDayStreaks($businessId, $userId);
                } catch (\Exception $e) {
                    Log::warning('Failed to check achievements for user', [
                        'business_id' => $businessId,
                        'user_id' => $userId,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            Log::info('Business achievements checked', [
                'business_id' => $businessId,
                'operators_count' => $operators->count(),
                'awarded_count' => $awardedCount,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to process business achievements', [
                'business_id' => $businessId,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Foydalanuvchi yutuqlarini tekshirish
     */
    protected function checkUserAchievements(AchievementService $service, string $businessId, string $userId): int
    {
        $awarded = 0;

        // Turli xil triggerlarni tekshirish
        $triggers = [
            'daily_summary',
            'weekly_summary',
            'monthly_summary',
            'streak_check',
            'milestone_check',
        ];

        foreach ($triggers as $trigger) {
            try {
                $result = $service->checkAndAwardAchievements($businessId, $userId, $trigger);
                if ($result) {
                    $awarded++;
                }
            } catch (\Exception $e) {
                Log::debug('Achievement trigger check failed', [
                    'trigger' => $trigger,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $awarded;
    }

    /**
     * Barcha bizneslar uchun yutuqlarni tekshirish
     */
    protected function processAllBusinesses(AchievementService $service): void
    {
        $businesses = Business::where('status', 'active')
            ->whereHas('achievementDefinitions', fn ($q) => $q->where('is_active', true))
            ->pluck('id');

        $processedCount = 0;
        $errorCount = 0;

        foreach ($businesses as $businessId) {
            try {
                $this->processBusinessAchievements($service, $businessId);
                $processedCount++;
            } catch (\Exception $e) {
                $errorCount++;
                Log::error('Failed to process achievements', [
                    'business_id' => $businessId,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        Log::info('All businesses achievements completed', [
            'businesses_processed' => $processedCount,
            'businesses_failed' => $errorCount,
        ]);
    }

    /**
     * Job muvaffaqiyatsiz tugadi
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('CheckAchievementsJob failed', [
            'business_id' => $this->businessId,
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString(),
        ]);
    }
}
