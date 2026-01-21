<?php

namespace App\Observers\HR;

use App\Events\HR\FlightRiskLevelChanged;
use App\Models\FlightRisk;
use App\Services\HR\HRAlertService;
use App\Services\HR\PerformanceService;
use Illuminate\Support\Facades\Log;

/**
 * FlightRiskObserver - Ketish xavfi o'zgarishlarini kuzatish
 *
 * Avtomatik harakatlar:
 * - Xavf darajasi oshganda ogohlantirish
 * - Kritik darajada stay interview rejalashtirish
 * - HR dashboardni yangilash
 */
class FlightRiskObserver
{
    public function __construct(
        protected HRAlertService $alertService,
        protected PerformanceService $performanceService
    ) {}

    /**
     * Flight risk yaratilganda
     */
    public function created(FlightRisk $flightRisk): void
    {
        Log::info('FlightRiskObserver: Flight risk created', [
            'id' => $flightRisk->id,
            'user_id' => $flightRisk->user_id,
            'level' => $flightRisk->risk_level,
            'score' => $flightRisk->risk_score,
        ]);

        // Agar dastlab yuqori xavf bilan yaratilgan bo'lsa
        if ($flightRisk->requiresImmediateAction()) {
            $this->handleHighRisk($flightRisk);
        }
    }

    /**
     * Flight risk yangilanganda
     */
    public function updated(FlightRisk $flightRisk): void
    {
        // Risk level o'zgarganda
        if ($flightRisk->isDirty('risk_level')) {
            $oldLevel = $flightRisk->getOriginal('risk_level');
            $newLevel = $flightRisk->risk_level;

            Log::info('FlightRiskObserver: Risk level changed', [
                'user_id' => $flightRisk->user_id,
                'old_level' => $oldLevel,
                'new_level' => $newLevel,
                'score' => $flightRisk->risk_score,
            ]);

            // Event dispatch
            event(new FlightRiskLevelChanged(
                $flightRisk->user,
                $flightRisk->business,
                $oldLevel,
                $newLevel,
                $flightRisk->risk_score,
                $flightRisk->risk_factors ?? []
            ));

            // Xavf darajasi oshgan bo'lsa
            if ($this->isRiskIncreased($oldLevel, $newLevel)) {
                $this->handleRiskIncreased($flightRisk, $oldLevel, $newLevel);
            }

            // Xavf darajasi kamaygan bo'lsa
            if ($this->isRiskDecreased($oldLevel, $newLevel)) {
                $this->handleRiskDecreased($flightRisk, $oldLevel, $newLevel);
            }
        }
    }

    /**
     * Yuqori xavf holatini qayta ishlash
     */
    protected function handleHighRisk(FlightRisk $flightRisk): void
    {
        $employee = $flightRisk->user;
        $business = $flightRisk->business;

        if (!$employee || !$business) {
            return;
        }

        // Zudlik bilan ogohlantirish
        $priority = $flightRisk->risk_level === FlightRisk::LEVEL_CRITICAL ? 'urgent' : 'high';

        $this->alertService->createAlert(
            $business,
            'flight_risk_high',
            'Ketish xavfi yuqori!',
            "{$employee->name} - {$flightRisk->level_label}. Zudlik bilan harakat kerak!",
            [
                'priority' => $priority,
                'user_id' => null, // HR va manager
                'data' => [
                    'employee_id' => $employee->id,
                    'level' => $flightRisk->risk_level,
                    'score' => $flightRisk->risk_score,
                    'top_factors' => $flightRisk->top_risk_factors,
                    'recommended_actions' => $flightRisk->getRecommendedActions(),
                ],
            ]
        );

        // Kritik darajada - stay interview rejalashtirish
        if ($flightRisk->risk_level === FlightRisk::LEVEL_CRITICAL) {
            if (!$flightRisk->stay_interview_scheduled) {
                try {
                    $this->performanceService->scheduleStayInterview($employee, $business);

                    $flightRisk->update([
                        'stay_interview_scheduled' => true,
                        'stay_interview_date' => now()->addDays(3),
                    ]);

                    Log::info('FlightRiskObserver: Stay interview scheduled', [
                        'user_id' => $employee->id,
                    ]);
                } catch (\Exception $e) {
                    Log::error('FlightRiskObserver: Failed to schedule stay interview', [
                        'error' => $e->getMessage(),
                    ]);
                }
            }
        }
    }

    /**
     * Xavf oshganini qayta ishlash
     */
    protected function handleRiskIncreased(FlightRisk $flightRisk, string $oldLevel, string $newLevel): void
    {
        $employee = $flightRisk->user;
        $business = $flightRisk->business;

        if (!$employee || !$business) {
            return;
        }

        // Yuqori yoki kritik darajaga o'tgan bo'lsa
        if (in_array($newLevel, [FlightRisk::LEVEL_HIGH, FlightRisk::LEVEL_CRITICAL])) {
            $this->handleHighRisk($flightRisk);
        }
        // O'rtachaga o'tgan bo'lsa
        elseif ($newLevel === FlightRisk::LEVEL_MODERATE && $oldLevel === FlightRisk::LEVEL_LOW) {
            $this->alertService->createAlert(
                $business,
                'flight_risk_moderate',
                'Ketish xavfi oshdi',
                "{$employee->name} - xavf darajasi o'rtacha ga oshdi",
                [
                    'priority' => 'medium',
                    'user_id' => null,
                    'data' => [
                        'employee_id' => $employee->id,
                        'old_level' => $oldLevel,
                        'new_level' => $newLevel,
                    ],
                ]
            );
        }
    }

    /**
     * Xavf kamayganini qayta ishlash
     */
    protected function handleRiskDecreased(FlightRisk $flightRisk, string $oldLevel, string $newLevel): void
    {
        $employee = $flightRisk->user;
        $business = $flightRisk->business;

        if (!$employee || !$business) {
            return;
        }

        // Yuqori darajadan past darajaga tushgan bo'lsa - ijobiy xabar
        if (in_array($oldLevel, [FlightRisk::LEVEL_HIGH, FlightRisk::LEVEL_CRITICAL])
            && in_array($newLevel, [FlightRisk::LEVEL_LOW, FlightRisk::LEVEL_MODERATE])) {

            $this->alertService->createAlert(
                $business,
                'flight_risk_decreased',
                'Ketish xavfi kamaydi',
                "{$employee->name} - xavf darajasi {$flightRisk->level_label} ga tushdi",
                [
                    'priority' => 'low',
                    'user_id' => null,
                    'is_celebration' => true,
                    'data' => [
                        'employee_id' => $employee->id,
                        'old_level' => $oldLevel,
                        'new_level' => $newLevel,
                    ],
                ]
            );

            Log::info('FlightRiskObserver: Risk decreased', [
                'user_id' => $employee->id,
                'old_level' => $oldLevel,
                'new_level' => $newLevel,
            ]);
        }
    }

    /**
     * Xavf oshganmi tekshirish
     */
    protected function isRiskIncreased(string $oldLevel, string $newLevel): bool
    {
        $levels = [
            FlightRisk::LEVEL_LOW => 1,
            FlightRisk::LEVEL_MODERATE => 2,
            FlightRisk::LEVEL_HIGH => 3,
            FlightRisk::LEVEL_CRITICAL => 4,
        ];

        return ($levels[$newLevel] ?? 0) > ($levels[$oldLevel] ?? 0);
    }

    /**
     * Xavf kamayganmi tekshirish
     */
    protected function isRiskDecreased(string $oldLevel, string $newLevel): bool
    {
        $levels = [
            FlightRisk::LEVEL_LOW => 1,
            FlightRisk::LEVEL_MODERATE => 2,
            FlightRisk::LEVEL_HIGH => 3,
            FlightRisk::LEVEL_CRITICAL => 4,
        ];

        return ($levels[$newLevel] ?? 0) < ($levels[$oldLevel] ?? 0);
    }
}
