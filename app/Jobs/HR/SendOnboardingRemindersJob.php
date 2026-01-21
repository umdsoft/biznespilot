<?php

namespace App\Jobs\HR;

use App\Models\Business;
use App\Models\EmployeeOnboardingPlan;
use App\Models\EmployeeOnboardingTask;
use App\Services\HR\HRAlertService;
use App\Services\HR\OnboardingService;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * SendOnboardingRemindersJob - Onboarding eslatmalarini yuborish
 *
 * Bu job har kuni ishga tushadi va:
 * - Bugungi vazifalar haqida eslatma yuboradi
 * - Kechikkan vazifalar haqida ogohlantiradi
 * - 30/60/90 kun milestone larni tekshiradi
 * - Onboarding progressni yangilaydi
 */
class SendOnboardingRemindersJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $backoff = 60;
    public int $timeout = 600;

    public function __construct(
        public ?string $businessId = null
    ) {}

    public function handle(HRAlertService $alertService, OnboardingService $onboardingService): void
    {
        Log::info('SendOnboardingRemindersJob boshlandi', [
            'business_id' => $this->businessId,
        ]);

        if ($this->businessId) {
            $this->processForBusiness($alertService, $onboardingService, $this->businessId);
        } else {
            $this->processForAllBusinesses($alertService, $onboardingService);
        }

        Log::info('SendOnboardingRemindersJob yakunlandi');
    }

    protected function processForAllBusinesses(HRAlertService $alertService, OnboardingService $onboardingService): void
    {
        $businesses = Business::where('status', 'active')->pluck('id');

        foreach ($businesses as $businessId) {
            try {
                $this->processForBusiness($alertService, $onboardingService, $businessId);
            } catch (\Exception $e) {
                Log::error('Biznes onboarding reminders xatosi', [
                    'business_id' => $businessId,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    protected function processForBusiness(
        HRAlertService $alertService,
        OnboardingService $onboardingService,
        string $businessId
    ): void {
        $business = Business::find($businessId);
        if (!$business) {
            return;
        }

        // Faol onboarding rejalarni olish
        $activePlans = EmployeeOnboardingPlan::where('business_id', $businessId)
            ->where('status', EmployeeOnboardingPlan::STATUS_ACTIVE)
            ->with(['user', 'mentor', 'manager', 'tasks'])
            ->get();

        $remindersCount = 0;
        $milestonesCount = 0;
        $overdueCount = 0;

        foreach ($activePlans as $plan) {
            // 1. Bugungi vazifalar eslatmasi
            $remindersCount += $this->sendTodayTaskReminders($alertService, $plan);

            // 2. Kechikkan vazifalar ogohlantirishi
            $overdueCount += $this->sendOverdueTaskAlerts($alertService, $plan);

            // 3. Milestone tekshiruvi (30/60/90 kun)
            $milestonesCount += $this->checkMilestones($alertService, $onboardingService, $plan);

            // 4. Progress yangilash
            $plan->updateProgress();
        }

        Log::info('Biznes onboarding reminders yakunlandi', [
            'business_id' => $businessId,
            'active_plans' => $activePlans->count(),
            'reminders_sent' => $remindersCount,
            'milestones_checked' => $milestonesCount,
            'overdue_alerts' => $overdueCount,
        ]);
    }

    protected function sendTodayTaskReminders(HRAlertService $alertService, EmployeeOnboardingPlan $plan): int
    {
        $todayTasks = $plan->tasks()
            ->where('status', EmployeeOnboardingTask::STATUS_PENDING)
            ->whereDate('due_date', Carbon::today())
            ->get();

        if ($todayTasks->isEmpty()) {
            return 0;
        }

        $employee = $plan->user;
        $business = $plan->business;

        // Hodimga eslatma
        $alertService->createAlert(
            $business,
            'onboarding_task_today',
            'Bugungi onboarding vazifalari',
            "{$employee->name}, sizda bugun {$todayTasks->count()} ta vazifa bor",
            [
                'priority' => 'medium',
                'user_id' => $employee->id,
                'related_user_id' => $employee->id,
                'data' => [
                    'plan_id' => $plan->id,
                    'tasks_count' => $todayTasks->count(),
                    'tasks' => $todayTasks->pluck('title')->toArray(),
                ],
            ]
        );

        // Mentorga eslatma
        if ($plan->mentor) {
            $alertService->createAlert(
                $business,
                'onboarding_mentor_reminder',
                'Mentee bugungi vazifalari',
                "{$employee->name} ning bugun {$todayTasks->count()} ta onboarding vazifasi bor",
                [
                    'priority' => 'low',
                    'user_id' => $plan->mentor->id,
                    'related_user_id' => $employee->id,
                    'data' => [
                        'plan_id' => $plan->id,
                        'employee_name' => $employee->name,
                    ],
                ]
            );
        }

        return $todayTasks->count();
    }

    protected function sendOverdueTaskAlerts(HRAlertService $alertService, EmployeeOnboardingPlan $plan): int
    {
        $overdueTasks = $plan->tasks()
            ->where('status', EmployeeOnboardingTask::STATUS_PENDING)
            ->whereDate('due_date', '<', Carbon::today())
            ->get();

        if ($overdueTasks->isEmpty()) {
            return 0;
        }

        $employee = $plan->user;
        $business = $plan->business;

        // HR ga yuqori priority ogohlantirish
        $alertService->createAlert(
            $business,
            'onboarding_task_overdue',
            'Kechikkan onboarding vazifalari!',
            "{$employee->name} ning {$overdueTasks->count()} ta kechikkan vazifasi bor",
            [
                'priority' => 'high',
                'user_id' => null, // HR
                'related_user_id' => $employee->id,
                'data' => [
                    'plan_id' => $plan->id,
                    'employee_id' => $employee->id,
                    'employee_name' => $employee->name,
                    'overdue_count' => $overdueTasks->count(),
                    'tasks' => $overdueTasks->map(fn($t) => [
                        'title' => $t->title,
                        'due_date' => $t->due_date?->format('Y-m-d'),
                        'days_overdue' => $t->due_date ? Carbon::parse($t->due_date)->diffInDays(now()) : 0,
                    ])->toArray(),
                ],
            ]
        );

        return $overdueTasks->count();
    }

    protected function checkMilestones(
        HRAlertService $alertService,
        OnboardingService $onboardingService,
        EmployeeOnboardingPlan $plan
    ): int {
        $employee = $plan->user;
        $business = $plan->business;
        $daysSinceStart = Carbon::parse($plan->start_date)->diffInDays(now());

        $milestonesFound = 0;

        // 30-kun milestone
        if ($daysSinceStart >= 30 && !$plan->day_30_completed) {
            $this->checkMilestoneCompletion($alertService, $onboardingService, $plan, 30);
            $milestonesFound++;
        }

        // 60-kun milestone
        if ($daysSinceStart >= 60 && !$plan->day_60_completed) {
            $this->checkMilestoneCompletion($alertService, $onboardingService, $plan, 60);
            $milestonesFound++;
        }

        // 90-kun milestone
        if ($daysSinceStart >= 90 && !$plan->day_90_completed) {
            $this->checkMilestoneCompletion($alertService, $onboardingService, $plan, 90);
            $milestonesFound++;
        }

        return $milestonesFound;
    }

    protected function checkMilestoneCompletion(
        HRAlertService $alertService,
        OnboardingService $onboardingService,
        EmployeeOnboardingPlan $plan,
        int $day
    ): void {
        $phase = "day_{$day}";
        $employee = $plan->user;
        $business = $plan->business;

        // Bu bosqichdagi vazifalarni tekshirish
        $phaseTasks = $plan->tasks()->where('phase', $phase)->get();
        $completedTasks = $phaseTasks->where('status', EmployeeOnboardingTask::STATUS_COMPLETED);
        $pendingTasks = $phaseTasks->where('status', EmployeeOnboardingTask::STATUS_PENDING);

        if ($phaseTasks->isEmpty()) {
            // Vazifalar yo'q - milestone ni complete qilish
            $plan->{"completeDay{$day}"}(100);
            return;
        }

        if ($pendingTasks->isEmpty()) {
            // Barcha vazifalar bajarildi - milestone complete
            $score = 100;
            $plan->{"completeDay{$day}"}($score);

            // Muvaffaqiyat xabari
            $alertService->createAlert(
                $business,
                'onboarding_milestone_completed',
                "{$day}-kun milestone yakunlandi!",
                "{$employee->name} {$day}-kun onboarding bosqichini muvaffaqiyatli yakunladi",
                [
                    'priority' => 'low',
                    'is_celebration' => true,
                    'user_id' => null,
                    'related_user_id' => $employee->id,
                    'data' => [
                        'plan_id' => $plan->id,
                        'milestone' => $day,
                        'score' => $score,
                    ],
                ]
            );
        } else {
            // Vazifalar bajarilmagan - ogohlantirish
            $alertService->createAlert(
                $business,
                'onboarding_milestone_pending',
                "{$day}-kun milestone muddati o'tdi",
                "{$employee->name} ning {$day}-kun bosqichida {$pendingTasks->count()} ta bajarilmagan vazifa bor",
                [
                    'priority' => 'high',
                    'user_id' => null,
                    'related_user_id' => $employee->id,
                    'data' => [
                        'plan_id' => $plan->id,
                        'milestone' => $day,
                        'pending_count' => $pendingTasks->count(),
                        'total_tasks' => $phaseTasks->count(),
                    ],
                ]
            );
        }
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('SendOnboardingRemindersJob muvaffaqiyatsiz', [
            'business_id' => $this->businessId,
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString(),
        ]);
    }
}
