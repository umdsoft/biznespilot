<?php

namespace App\Listeners\HR;

use App\Events\LeadWon;
use App\Events\Sales\DealClosed;
use App\Events\Sales\AchievementUnlocked;
use App\Events\Sales\KpiMilestoneReached;
use App\Events\TaskCompleted;
use App\Services\HR\EngagementService;
use App\Services\HR\HRAlertService;
use App\Services\HR\PerformanceService;
use App\Services\HR\RetentionService;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;

/**
 * HRIntegrationListener - Sales va Marketing eventlarini tinglab HR yangilash
 *
 * Bu listener modullar o'rtasidagi bog'lanishni ta'minlaydi:
 * - DealClosed → Engagement ball oshirish
 * - AchievementUnlocked → Recognition yaratish
 * - KpiMilestoneReached → Performance tracking
 * - TaskCompleted → Faollik ball yangilash
 *
 * 80%+ avtomatizatsiya: Barcha o'zgarishlar avtomatik
 */
class HRIntegrationListener implements ShouldQueue
{
    public string $queue = 'hr-integration';

    public function __construct(
        protected EngagementService $engagementService,
        protected HRAlertService $alertService,
        protected RetentionService $retentionService,
        protected PerformanceService $performanceService
    ) {}

    /**
     * Subscribe to events
     */
    public function subscribe(Dispatcher $events): array
    {
        return [
            DealClosed::class => 'handleDealClosed',
            AchievementUnlocked::class => 'handleAchievementUnlocked',
            KpiMilestoneReached::class => 'handleKpiMilestoneReached',
            TaskCompleted::class => 'handleTaskCompleted',
            LeadWon::class => 'handleLeadWon',
        ];
    }

    /**
     * Deal yopilganda - hodim engagement oshirish
     */
    public function handleDealClosed(DealClosed $event): void
    {
        $employee = $event->closedBy;
        $lead = $event->lead;
        $amount = $event->amount;

        if (!$employee) {
            return;
        }

        Log::info('HRIntegrationListener: Processing DealClosed', [
            'employee_id' => $employee->id,
            'lead_id' => $lead->id,
            'amount' => $amount,
        ]);

        try {
            $business = $lead->business;

            // 1. Engagement ball oshirish (sotuv muvaffaqiyati)
            $boostPoints = $this->calculateDealBoostPoints($amount);
            $this->engagementService->boostEngagement(
                $employee,
                $business,
                'deal_closed',
                $boostPoints
            );

            // 2. Flight risk kamaytirish (muvaffaqiyat = sodiqlik)
            $this->retentionService->decreaseFlightRisk(
                $employee,
                $business,
                'deal_closed_success',
                2
            );

            // 3. Katta deal bo'lsa - alohida e'tirof
            if ($amount >= 10000000) { // 10M+ so'm
                $this->alertService->createAlert(
                    $business,
                    'big_deal_closed',
                    'Katta sotuv!',
                    "{$employee->name} - " . number_format($amount) . " so'mlik sotuv!",
                    [
                        'priority' => 'medium',
                        'is_celebration' => true,
                        'user_id' => null,
                        'data' => [
                            'employee_id' => $employee->id,
                            'amount' => $amount,
                            'lead_id' => $lead->id,
                        ],
                    ]
                );
            }

        } catch (\Exception $e) {
            Log::error('HRIntegrationListener: Failed to process DealClosed', [
                'error' => $e->getMessage(),
                'employee_id' => $employee->id,
            ]);
        }
    }

    /**
     * Achievement ochilganda - HR recognition
     */
    public function handleAchievementUnlocked(AchievementUnlocked $event): void
    {
        $employee = $event->user;
        $businessId = $event->businessId;

        Log::info('HRIntegrationListener: Processing AchievementUnlocked', [
            'employee_id' => $employee->id,
            'achievement' => $event->code,
        ]);

        try {
            $business = $employee->businesses()->where('businesses.id', $businessId)->first();

            if (!$business) {
                return;
            }

            // 1. Engagement ball oshirish
            $boostPoints = $event->points > 0 ? min(10, $event->points / 10) : 3;
            $this->engagementService->boostEngagement(
                $employee,
                $business,
                'achievement_unlocked',
                (int)$boostPoints
            );

            // 2. Flight risk kamaytirish
            $this->retentionService->decreaseFlightRisk(
                $employee,
                $business,
                'achievement_motivation',
                1
            );

            // 3. HR alert - yangi yutuq
            $this->alertService->createAlert(
                $business,
                'employee_achievement',
                'Yangi yutuq!',
                "{$employee->name} - \"{$event->name}\" yutuqiga erishdi!",
                [
                    'priority' => 'low',
                    'is_celebration' => true,
                    'user_id' => null,
                    'data' => [
                        'employee_id' => $employee->id,
                        'achievement_code' => $event->code,
                        'achievement_name' => $event->name,
                        'points' => $event->points,
                    ],
                ]
            );

        } catch (\Exception $e) {
            Log::error('HRIntegrationListener: Failed to process AchievementUnlocked', [
                'error' => $e->getMessage(),
                'employee_id' => $employee->id,
            ]);
        }
    }

    /**
     * KPI milestone ga yetilganda
     */
    public function handleKpiMilestoneReached(KpiMilestoneReached $event): void
    {
        $employee = $event->user;
        $businessId = $event->businessId;

        Log::info('HRIntegrationListener: Processing KpiMilestoneReached', [
            'employee_id' => $employee->id,
            'kpi_type' => $event->kpiType,
            'milestone' => $event->milestone,
        ]);

        try {
            $business = $employee->businesses()->where('businesses.id', $businessId)->first();

            if (!$business) {
                return;
            }

            // Milestone ga qarab engagement boost
            $boostPoints = match($event->milestone) {
                KpiMilestoneReached::MILESTONE_150 => 10,
                KpiMilestoneReached::MILESTONE_120 => 7,
                KpiMilestoneReached::MILESTONE_100 => 5,
                KpiMilestoneReached::MILESTONE_75 => 3,
                KpiMilestoneReached::MILESTONE_50 => 2,
                default => 1,
            };

            // 1. Engagement oshirish
            $this->engagementService->boostEngagement(
                $employee,
                $business,
                'kpi_milestone',
                $boostPoints
            );

            // 2. 100%+ bo'lsa - Flight risk keskin kamaytirish
            if (in_array($event->milestone, [
                KpiMilestoneReached::MILESTONE_100,
                KpiMilestoneReached::MILESTONE_120,
                KpiMilestoneReached::MILESTONE_150,
            ])) {
                $this->retentionService->decreaseFlightRisk(
                    $employee,
                    $business,
                    'kpi_success',
                    $boostPoints / 2
                );
            }

        } catch (\Exception $e) {
            Log::error('HRIntegrationListener: Failed to process KpiMilestoneReached', [
                'error' => $e->getMessage(),
                'employee_id' => $employee->id,
            ]);
        }
    }

    /**
     * Task bajarilganda - faollik ball
     */
    public function handleTaskCompleted(TaskCompleted $event): void
    {
        $task = $event->task;
        $employee = $task->assignedTo;

        if (!$employee) {
            return;
        }

        Log::debug('HRIntegrationListener: Processing TaskCompleted', [
            'task_id' => $task->id,
            'employee_id' => $employee->id,
        ]);

        try {
            $business = $task->business;

            if (!$business) {
                return;
            }

            // Activity score yangilash
            // Har 5 ta task uchun 1 ball
            $completedTasks = $employee->tasks()
                ->where('business_id', $business->id)
                ->where('status', 'completed')
                ->where('completed_at', '>=', now()->startOfMonth())
                ->count();

            if ($completedTasks % 5 === 0) {
                $this->engagementService->boostEngagement(
                    $employee,
                    $business,
                    'task_streak',
                    1
                );
            }

        } catch (\Exception $e) {
            Log::error('HRIntegrationListener: Failed to process TaskCompleted', [
                'error' => $e->getMessage(),
                'task_id' => $task->id,
            ]);
        }
    }

    /**
     * Lead won bo'lganda
     */
    public function handleLeadWon(LeadWon $event): void
    {
        $lead = $event->lead;
        $sale = $event->sale;
        $employee = $lead->assignedTo;

        if (!$employee) {
            return;
        }

        Log::info('HRIntegrationListener: Processing LeadWon', [
            'lead_id' => $lead->id,
            'employee_id' => $employee->id,
            'revenue' => $event->revenue,
        ]);

        try {
            $business = $lead->business;

            // Engagement ball oshirish
            $this->engagementService->boostEngagement(
                $employee,
                $business,
                'lead_converted',
                3
            );

        } catch (\Exception $e) {
            Log::error('HRIntegrationListener: Failed to process LeadWon', [
                'error' => $e->getMessage(),
                'lead_id' => $lead->id,
            ]);
        }
    }

    /**
     * Deal summasiga qarab boost ballni hisoblash
     */
    protected function calculateDealBoostPoints(float $amount): int
    {
        return match(true) {
            $amount >= 100000000 => 15,  // 100M+
            $amount >= 50000000 => 10,   // 50M+
            $amount >= 10000000 => 7,    // 10M+
            $amount >= 5000000 => 5,     // 5M+
            $amount >= 1000000 => 3,     // 1M+
            default => 2,
        };
    }
}
