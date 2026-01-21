<?php

namespace App\Observers\HR;

use App\Events\HR\EngagementScoreChanged;
use App\Models\EmployeeEngagement;
use App\Services\HR\HRAlertService;
use App\Services\HR\RetentionService;
use Illuminate\Support\Facades\Log;

/**
 * EmployeeEngagementObserver - Engagement o'zgarishlarini kuzatish
 *
 * Avtomatik harakatlar:
 * - Engagement pasayganda ogohlantirish
 * - Flight risk ni yangilash
 * - Trend tahlili
 */
class EmployeeEngagementObserver
{
    public function __construct(
        protected HRAlertService $alertService,
        protected RetentionService $retentionService
    ) {}

    /**
     * Engagement yaratilganda
     */
    public function created(EmployeeEngagement $engagement): void
    {
        Log::info('EmployeeEngagementObserver: Engagement record created', [
            'id' => $engagement->id,
            'user_id' => $engagement->user_id,
            'overall_score' => $engagement->overall_score,
        ]);
    }

    /**
     * Engagement yangilanganda
     */
    public function updated(EmployeeEngagement $engagement): void
    {
        // Overall score o'zgarganda
        if ($engagement->isDirty('overall_score')) {
            $oldScore = $engagement->getOriginal('overall_score');
            $newScore = $engagement->overall_score;

            Log::info('EmployeeEngagementObserver: Score changed', [
                'user_id' => $engagement->user_id,
                'old_score' => $oldScore,
                'new_score' => $newScore,
            ]);

            // Score tarixini saqlash
            $engagement->recordScoreChange($oldScore, $newScore, 'auto_update');

            // Sezilarli o'zgarish bo'lsa
            if (abs($newScore - $oldScore) >= 10) {
                $this->handleSignificantChange($engagement, $oldScore, $newScore);
            }

            // Ball pasaygan bo'lsa - flight risk yangilash
            if ($newScore < $oldScore) {
                $this->updateFlightRiskFromEngagement($engagement, $newScore);
            }
        }
    }

    /**
     * Sezilarli o'zgarishni qayta ishlash
     */
    protected function handleSignificantChange(EmployeeEngagement $engagement, float $oldScore, float $newScore): void
    {
        $employee = $engagement->user;
        $business = $engagement->business;

        if (!$employee || !$business) {
            return;
        }

        $change = $newScore - $oldScore;

        // Ball keskin tushgan bo'lsa - ogohlantirish
        if ($change <= -15) {
            $this->alertService->createAlert(
                $business,
                'engagement_dropped',
                'Engagement keskin tushdi!',
                "{$employee->name} - engagement {$oldScore}% dan {$newScore}% ga tushdi",
                [
                    'priority' => 'high',
                    'user_id' => null, // HR va manager
                    'data' => [
                        'employee_id' => $employee->id,
                        'old_score' => $oldScore,
                        'new_score' => $newScore,
                        'change' => $change,
                    ],
                ]
            );

            Log::warning('EmployeeEngagementObserver: Significant drop detected', [
                'user_id' => $employee->id,
                'change' => $change,
            ]);
        }

        // Ball keskin oshgan bo'lsa - ijobiy xabar
        if ($change >= 15) {
            $this->alertService->createAlert(
                $business,
                'engagement_improved',
                'Engagement yaxshilandi!',
                "{$employee->name} - engagement {$newScore}% ga oshdi",
                [
                    'priority' => 'low',
                    'user_id' => null,
                    'is_celebration' => true,
                    'data' => [
                        'employee_id' => $employee->id,
                        'old_score' => $oldScore,
                        'new_score' => $newScore,
                    ],
                ]
            );
        }

        // Event dispatch
        event(new EngagementScoreChanged(
            $employee,
            $business,
            $oldScore,
            $newScore,
            'observer'
        ));
    }

    /**
     * Engagement asosida flight risk yangilash
     */
    protected function updateFlightRiskFromEngagement(EmployeeEngagement $engagement, float $newScore): void
    {
        try {
            $employee = $engagement->user;
            $business = $engagement->business;

            if (!$employee || !$business) {
                return;
            }

            $this->retentionService->updateFlightRiskFromEngagement(
                $employee,
                $business,
                $newScore
            );

        } catch (\Exception $e) {
            Log::error('EmployeeEngagementObserver: Failed to update flight risk', [
                'error' => $e->getMessage(),
                'engagement_id' => $engagement->id,
            ]);
        }
    }
}
