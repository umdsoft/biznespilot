<?php

namespace App\Observers;

use App\Jobs\Sales\UpdateUserKpiSnapshotJob;
use App\Models\CallLog;
use App\Models\Lead;
use App\Services\Pipeline\PipelineAutomationService;
use App\Services\Sales\AchievementService;
use App\Services\Sales\LeaderboardService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class CallLogObserver
{
    /**
     * CallLog yaratilganda
     */
    public function created(CallLog $callLog): void
    {
        Log::info('CallLogObserver: Call logged', [
            'call_id' => $callLog->id,
            'business_id' => $callLog->business_id,
            'user_id' => $callLog->user_id,
            'direction' => $callLog->direction,
            'status' => $callLog->status,
        ]);

        // Completed yoki answered status bo'lsa ishlov berish
        if (in_array($callLog->status, [CallLog::STATUS_COMPLETED, CallLog::STATUS_ANSWERED])) {
            $this->handleCallCompleted($callLog);
        }
    }

    /**
     * CallLog yangilanganda
     */
    public function updated(CallLog $callLog): void
    {
        // Status o'zgargan bo'lsa
        if ($callLog->isDirty('status')) {
            $newStatus = $callLog->status;

            // Qo'ng'iroq tugallandi
            if (in_array($newStatus, [CallLog::STATUS_COMPLETED, CallLog::STATUS_ANSWERED])) {
                $this->handleCallCompleted($callLog);
            }
        }

        // Duration o'zgargan bo'lsa
        if ($callLog->isDirty('duration')) {
            $this->handleDurationUpdated($callLog);
        }
    }

    /**
     * Qo'ng'iroq tugallaganda
     */
    protected function handleCallCompleted(CallLog $callLog): void
    {
        if (!$callLog->user_id) {
            return;
        }

        Log::info('CallLogObserver: Call completed', [
            'call_id' => $callLog->id,
            'user_id' => $callLog->user_id,
            'duration' => $callLog->duration,
            'direction' => $callLog->direction,
        ]);

        // 1. Lid bilan bog'langan bo'lsa, lead.last_contacted_at ni yangilash
        if ($callLog->lead_id) {
            $this->updateLeadContactTime($callLog);

            // Pipeline avtomatizatsiya - qo'ng'iroq bo'yicha stage o'zgartirish
            $this->processPipelineAutomation($callLog);
        }

        // 2. KPI snapshotni yangilash (async)
        UpdateUserKpiSnapshotJob::dispatch(
            $callLog->business_id,
            $callLog->user_id,
            Carbon::today()
        );

        // 3. Leaderboardni yangilash
        try {
            // Qo'ng'iroq davomiyligiga qarab ball berish
            $points = $this->calculateCallPoints($callLog);

            app(LeaderboardService::class)->updateUserScore(
                $callLog->business_id,
                $callLog->user_id,
                'call_completed',
                $points
            );
        } catch (\Exception $e) {
            Log::error('CallLogObserver: Failed to update leaderboard', [
                'error' => $e->getMessage(),
            ]);
        }

        // 4. Achievement tekshirish
        try {
            app(AchievementService::class)->checkAndAwardAchievements(
                $callLog->business_id,
                $callLog->user_id,
                'call_completed'
            );
        } catch (\Exception $e) {
            Log::error('CallLogObserver: Failed to check achievements', [
                'error' => $e->getMessage(),
            ]);
        }

        // 5. Streak yangilash
        try {
            app(AchievementService::class)->updateStreak(
                $callLog->business_id,
                $callLog->user_id,
                'calls_made'
            );
        } catch (\Exception $e) {
            Log::error('CallLogObserver: Failed to update streak', [
                'error' => $e->getMessage(),
            ]);
        }

        // 6. Kunlik qo'ng'iroq maqsadini tekshirish
        $this->checkDailyCallTarget($callLog);
    }

    /**
     * Duration yangilanganda
     */
    protected function handleDurationUpdated(CallLog $callLog): void
    {
        if (!$callLog->user_id) {
            return;
        }

        // Call duration KPIsini yangilash
        UpdateUserKpiSnapshotJob::dispatch(
            $callLog->business_id,
            $callLog->user_id,
            Carbon::today()
        );
    }

    /**
     * Lid bilan aloqa vaqtini yangilash
     */
    protected function updateLeadContactTime(CallLog $callLog): void
    {
        try {
            Lead::withoutGlobalScope('business')
                ->where('id', $callLog->lead_id)
                ->update(['last_contacted_at' => $callLog->ended_at ?? now()]);
        } catch (\Exception $e) {
            Log::error('CallLogObserver: Failed to update lead contact time', [
                'lead_id' => $callLog->lead_id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Qo'ng'iroq uchun ball hisoblash
     */
    protected function calculateCallPoints(CallLog $callLog): int
    {
        $basePoints = 10;

        // Duration bo'yicha ball qo'shish
        if ($callLog->duration) {
            $minutes = $callLog->duration / 60;

            if ($minutes >= 5) {
                $basePoints += 20; // 5+ daqiqalik suhbat
            } elseif ($minutes >= 2) {
                $basePoints += 10; // 2-5 daqiqalik suhbat
            } elseif ($minutes >= 1) {
                $basePoints += 5; // 1-2 daqiqalik suhbat
            }
        }

        // Chiquvchi qo'ng'iroq uchun qo'shimcha ball
        if ($callLog->isOutbound()) {
            $basePoints += 5;
        }

        return $basePoints;
    }

    /**
     * Kunlik qo'ng'iroq maqsadini tekshirish
     */
    protected function checkDailyCallTarget(CallLog $callLog): void
    {
        if (!$callLog->user_id) {
            return;
        }

        try {
            // Bugungi qo'ng'iroqlar sonini hisoblash
            $todayCalls = CallLog::withoutGlobalScope('business')
                ->where('business_id', $callLog->business_id)
                ->where('user_id', $callLog->user_id)
                ->whereIn('status', [CallLog::STATUS_ANSWERED, CallLog::STATUS_COMPLETED])
                ->whereDate('started_at', today())
                ->count();

            // Kunlik maqsad bo'yicha achievement
            $milestones = [10, 25, 50, 100];

            foreach ($milestones as $milestone) {
                if ($todayCalls === $milestone) {
                    app(AchievementService::class)->checkAndAwardAchievements(
                        $callLog->business_id,
                        $callLog->user_id,
                        "daily_calls_{$milestone}"
                    );
                    break;
                }
            }
        } catch (\Exception $e) {
            Log::error('CallLogObserver: Failed to check daily call target', [
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Pipeline avtomatizatsiya
     */
    protected function processPipelineAutomation(CallLog $callLog): void
    {
        if (! $callLog->lead_id || ! $callLog->lead) {
            return;
        }

        try {
            app(PipelineAutomationService::class)->processEvent(
                'call_log_created',
                $callLog->lead,
                [
                    'direction' => $callLog->direction,
                    'status' => $callLog->status,
                    'duration' => $callLog->duration,
                ]
            );
        } catch (\Exception $e) {
            Log::error('CallLogObserver: Failed to process pipeline automation', [
                'lead_id' => $callLog->lead_id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
