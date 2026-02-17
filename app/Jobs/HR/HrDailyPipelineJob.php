<?php

namespace App\Jobs\HR;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * HrDailyPipelineJob — Kunlik HR vazifalarni ketma-ket bajarish
 *
 * Quyidagilarni o'z ichiga oladi:
 * 1. CalculateEngagementScoresJob — Hodimlar engagement ballari
 * 2. CheckWorkAnniversariesJob — Ish yilliklari tekshirish
 * 3. SendOnboardingRemindersJob — Onboarding eslatmalari
 *
 * Har kuni 05:00 da ishga tushiriladi
 */
class HrDailyPipelineJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;
    public int $timeout = 1800;

    public function __construct(
        public ?string $businessId = null
    ) {}

    public function handle(): void
    {
        Log::info('HrDailyPipelineJob: Starting HR daily pipeline');

        // 1. Engagement scores — boshqa HR vazifalar uchun asos
        try {
            CalculateEngagementScoresJob::dispatchSync($this->businessId);
        } catch (\Exception $e) {
            Log::error('HrDailyPipelineJob: Engagement scores failed', [
                'error' => $e->getMessage(),
            ]);
        }

        // 2. Work anniversaries
        try {
            CheckWorkAnniversariesJob::dispatchSync($this->businessId);
        } catch (\Exception $e) {
            Log::error('HrDailyPipelineJob: Work anniversaries failed', [
                'error' => $e->getMessage(),
            ]);
        }

        // 3. Onboarding reminders
        try {
            SendOnboardingRemindersJob::dispatchSync($this->businessId);
        } catch (\Exception $e) {
            Log::error('HrDailyPipelineJob: Onboarding reminders failed', [
                'error' => $e->getMessage(),
            ]);
        }

        Log::info('HrDailyPipelineJob: HR daily pipeline completed');
    }

    public function tags(): array
    {
        return [
            'hr-daily-pipeline',
            $this->businessId ? 'business:' . $this->businessId : 'all-businesses',
        ];
    }
}
