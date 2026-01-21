<?php

namespace App\Observers\HR;

use App\Models\EmployeeGoal;
use App\Services\HR\EngagementService;
use App\Services\HR\HROrchestrator;
use Illuminate\Support\Facades\Log;

/**
 * EmployeeGoalObserver - Hodim maqsadlarini kuzatish
 *
 * Avtomatik harakatlar:
 * - Progress yangilanganda HROrchestrator ga xabar
 * - Maqsad yakunlanganda tabrik va engagement oshirish
 * - Orqada qolish holatini aniqlash
 */
class EmployeeGoalObserver
{
    public function __construct(
        protected EngagementService $engagementService
    ) {}

    /**
     * Maqsad yaratilganda
     */
    public function created(EmployeeGoal $goal): void
    {
        Log::info('EmployeeGoalObserver: Goal created', [
            'goal_id' => $goal->id,
            'user_id' => $goal->user_id,
            'title' => $goal->title,
        ]);

        // Yangi maqsad qo'yildi - engagement boost
        try {
            $this->engagementService->boostEngagement(
                $goal->user,
                $goal->business,
                'new_goal_set',
                2
            );
        } catch (\Exception $e) {
            Log::error('EmployeeGoalObserver: Failed to boost engagement', [
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Maqsad yangilanganda
     */
    public function updated(EmployeeGoal $goal): void
    {
        // Progress o'zgarganda
        if ($goal->isDirty('progress')) {
            $oldProgress = $goal->getOriginal('progress');
            $newProgress = $goal->progress;

            Log::info('EmployeeGoalObserver: Progress updated', [
                'goal_id' => $goal->id,
                'old_progress' => $oldProgress,
                'new_progress' => $newProgress,
            ]);

            // 100% ga yetgan bo'lsa - yakunlash
            if ($newProgress >= 100 && $oldProgress < 100) {
                $this->handleGoalCompleted($goal);
            }

            // OKR progress event trigger
            $this->triggerOKRProgressEvent($goal, $oldProgress, $newProgress);
        }

        // Status o'zgarganda
        if ($goal->isDirty('status')) {
            $newStatus = $goal->status;

            if ($newStatus === 'completed') {
                $this->handleGoalCompleted($goal);
            }
        }
    }

    /**
     * Maqsad yakunlanganda
     */
    protected function handleGoalCompleted(EmployeeGoal $goal): void
    {
        try {
            $employee = $goal->user;
            $business = $goal->business;

            if (!$employee || !$business) {
                return;
            }

            // Muddatdan necha kun oldin bajarildi
            $daysEarly = null;
            if ($goal->due_date && now()->lt($goal->due_date)) {
                $daysEarly = now()->diffInDays($goal->due_date);
            }

            // Bajarilish sifatini aniqlash
            $quality = 'good';
            if ($daysEarly && $daysEarly > 7 && $goal->progress >= 100) {
                $quality = 'excellent';
            } elseif ($daysEarly && $daysEarly > 14 && $goal->progress >= 100) {
                $quality = 'exceptional';
            }

            // Engagement boost
            $boostPoints = match($quality) {
                'exceptional' => 8,
                'excellent' => 5,
                'good' => 3,
                default => 2,
            };

            $this->engagementService->boostEngagement(
                $employee,
                $business,
                'goal_completed',
                $boostPoints
            );

            Log::info('EmployeeGoalObserver: Goal completed', [
                'goal_id' => $goal->id,
                'user_id' => $employee->id,
                'quality' => $quality,
                'days_early' => $daysEarly,
            ]);

        } catch (\Exception $e) {
            Log::error('EmployeeGoalObserver: Failed to handle goal completion', [
                'error' => $e->getMessage(),
                'goal_id' => $goal->id,
            ]);
        }
    }

    /**
     * OKR progress eventini trigger qilish
     */
    protected function triggerOKRProgressEvent(EmployeeGoal $goal, float $oldProgress, float $newProgress): void
    {
        try {
            // Sezilarli progress bo'lgan bo'lsa (10%+ o'zgarish)
            if (abs($newProgress - $oldProgress) >= 10) {
                $employee = $goal->user;
                $business = $goal->business;

                if ($employee && $business) {
                    // HROrchestrator orqali event trigger
                    // Bu yerda dispatch qilish mumkin, lekin hozircha log qilamiz
                    Log::info('EmployeeGoalObserver: Significant progress detected', [
                        'goal_id' => $goal->id,
                        'user_id' => $employee->id,
                        'change' => $newProgress - $oldProgress,
                    ]);
                }
            }

        } catch (\Exception $e) {
            Log::error('EmployeeGoalObserver: Failed to trigger OKR event', [
                'error' => $e->getMessage(),
            ]);
        }
    }
}
