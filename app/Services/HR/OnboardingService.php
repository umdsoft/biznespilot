<?php

namespace App\Services\HR;

use App\Models\Business;
use App\Models\EmployeeOnboardingPlan;
use App\Models\EmployeeOnboardingTask;
use App\Models\OffboardingChecklist;
use App\Models\User;
use Illuminate\Support\Facades\Log;

/**
 * OnboardingService - Hodim onboarding va offboarding jarayonlarini boshqarish
 *
 * Vazifalar:
 * 1. 30-60-90 kunlik onboarding rejasini yaratish
 * 2. Onboarding tasklar va progress tracking
 * 3. Offboarding checklist
 * 4. Mentor tayinlash
 */
class OnboardingService
{
    /**
     * Yangi hodim uchun onboarding boshlash
     */
    public function startOnboarding(User $employee, Business $business, array $data = []): EmployeeOnboardingPlan
    {
        // Mavjud onboarding rejasini tekshirish
        $existingPlan = EmployeeOnboardingPlan::where('business_id', $business->id)
            ->where('user_id', $employee->id)
            ->where('status', 'active')
            ->first();

        if ($existingPlan) {
            return $existingPlan;
        }

        // Yangi onboarding reja yaratish
        $plan = EmployeeOnboardingPlan::create([
            'business_id' => $business->id,
            'user_id' => $employee->id,
            'start_date' => $data['start_date'] ?? now()->toDateString(),
            'day_30_date' => now()->addDays(30)->toDateString(),
            'day_60_date' => now()->addDays(60)->toDateString(),
            'day_90_date' => now()->addDays(90)->toDateString(),
            'department' => $data['department'] ?? null,
            'position' => $data['position'] ?? null,
            'mentor_id' => $data['mentor_id'] ?? null,
            'status' => 'active',
            'progress' => 0,
        ]);

        // Standart onboarding tasklarni yaratish
        $this->createDefaultEmployeeOnboardingTasks($plan, $data);

        Log::info('OnboardingService: Onboarding boshlandi', [
            'employee_id' => $employee->id,
            'plan_id' => $plan->id,
        ]);

        return $plan;
    }

    /**
     * Standart onboarding tasklarni yaratish
     */
    protected function createDefaultEmployeeOnboardingTasks(EmployeeOnboardingPlan $plan, array $data = []): void
    {
        $tasks = [
            // 1-7 kun - Boshlang'ich sozlashlar
            [
                'phase' => 'day_1',
                'title' => "Ish joyini sozlash",
                'description' => "Kompyuter, email, kerakli dasturlarni sozlash",
                'due_days' => 1,
                'category' => 'setup',
                'priority' => 'high',
            ],
            [
                'phase' => 'day_1',
                'title' => "Jamoa bilan tanishish",
                'description' => "Jamoa a'zolari bilan tanishish uchrashuvlari",
                'due_days' => 1,
                'category' => 'social',
                'priority' => 'high',
            ],
            [
                'phase' => 'week_1',
                'title' => "Kompaniya qoidalari bilan tanishish",
                'description' => "HR policy, ish vaqti, ta'til qoidalari",
                'due_days' => 3,
                'category' => 'training',
                'priority' => 'medium',
            ],
            [
                'phase' => 'week_1',
                'title' => "Mentor bilan birinchi uchrashuv",
                'description' => "Mentor bilan tanishish va maqsadlarni muhokama qilish",
                'due_days' => 3,
                'category' => 'mentoring',
                'priority' => 'high',
            ],
            [
                'phase' => 'week_1',
                'title' => "Asosiy vositalarni o'rganish",
                'description' => "Ishda ishlatiladigan dastur va vositalarni o'rganish",
                'due_days' => 7,
                'category' => 'training',
                'priority' => 'medium',
            ],

            // 8-30 kun - Birinchi oy
            [
                'phase' => 'day_30',
                'title' => "Jarayon va protseduralarni o'rganish",
                'description' => "Bo'lim ish jarayonlari va standartlarini o'rganish",
                'due_days' => 14,
                'category' => 'training',
                'priority' => 'medium',
            ],
            [
                'phase' => 'day_30',
                'title' => "Birinchi mustaqil vazifa",
                'description' => "Kichik vazifani mustaqil bajarish",
                'due_days' => 21,
                'category' => 'work',
                'priority' => 'medium',
            ],
            [
                'phase' => 'day_30',
                'title' => "30 kunlik tekshiruv uchrashuvii",
                'description' => "Rahbar bilan 30 kunlik progress muhokamasi",
                'due_days' => 30,
                'category' => 'review',
                'priority' => 'high',
            ],

            // 31-60 kun - Ikkinchi oy
            [
                'phase' => 'day_60',
                'title' => "Ko'proq mustaqillik",
                'description' => "Murakkab vazifalarni o'z zimmasiga olish",
                'due_days' => 45,
                'category' => 'work',
                'priority' => 'medium',
            ],
            [
                'phase' => 'day_60',
                'title' => "Jamoaviy loyihalarga qo'shilish",
                'description' => "Jamoa loyihalarida faol ishtirok etish",
                'due_days' => 50,
                'category' => 'work',
                'priority' => 'medium',
            ],
            [
                'phase' => 'day_60',
                'title' => "60 kunlik tekshiruv uchrashuvii",
                'description' => "Rahbar bilan 60 kunlik progress muhokamasi",
                'due_days' => 60,
                'category' => 'review',
                'priority' => 'high',
            ],

            // 61-90 kun - Uchinchi oy
            [
                'phase' => 'day_90',
                'title' => "To'liq integratsiya",
                'description' => "Jamoa a'zosi sifatida to'liq ishga tushish",
                'due_days' => 75,
                'category' => 'work',
                'priority' => 'medium',
            ],
            [
                'phase' => 'day_90',
                'title' => "Yillik maqsadlarni belgilash",
                'description' => "Keyingi chorak va yil uchun maqsadlar qo'yish",
                'due_days' => 85,
                'category' => 'goal_setting',
                'priority' => 'high',
            ],
            [
                'phase' => 'day_90',
                'title' => "90 kunlik yakuniy baholash",
                'description' => "Sinov muddati yakunlash muhokamasi",
                'due_days' => 90,
                'category' => 'review',
                'priority' => 'high',
            ],
        ];

        foreach ($tasks as $taskData) {
            EmployeeOnboardingTask::create([
                'onboarding_plan_id' => $plan->id,
                'business_id' => $plan->business_id,
                'phase' => $taskData['phase'],
                'title' => $taskData['title'],
                'description' => $taskData['description'],
                'due_date' => now()->addDays($taskData['due_days'])->toDateString(),
                'category' => $taskData['category'],
                'priority' => $taskData['priority'],
                'status' => 'pending',
            ]);
        }
    }

    /**
     * Onboarding progressni yangilash
     */
    public function updateProgress(EmployeeOnboardingPlan $plan): float
    {
        $totalTasks = $plan->tasks()->count();
        $completedTasks = $plan->tasks()->where('status', 'completed')->count();

        $progress = $totalTasks > 0 ? ($completedTasks / $totalTasks) * 100 : 0;

        $plan->update([
            'progress' => round($progress, 2),
            'status' => $progress >= 100 ? 'completed' : 'active',
        ]);

        return $progress;
    }

    /**
     * Onboarding taskni yakunlash
     */
    public function completeTask(EmployeeOnboardingTask $task, ?string $notes = null): bool
    {
        $result = $task->update([
            'status' => 'completed',
            'completed_at' => now(),
            'notes' => $notes,
        ]);

        // Progressni yangilash
        $this->updateProgress($task->onboardingPlan);

        Log::info('OnboardingService: Task yakunlandi', [
            'task_id' => $task->id,
            'plan_id' => $task->onboarding_plan_id,
        ]);

        return $result;
    }

    /**
     * Offboarding jarayonini boshlash
     */
    public function startOffboarding(User $employee, Business $business, string $reason, array $data = []): OffboardingChecklist
    {
        $checklist = OffboardingChecklist::create([
            'business_id' => $business->id,
            'user_id' => $employee->id,
            'termination_reason' => $reason,
            'last_working_day' => $data['last_working_day'] ?? now()->addWeeks(2)->toDateString(),
            'status' => 'active',
            'checklist_items' => $this->getDefaultOffboardingItems($reason),
            'progress' => 0,
        ]);

        Log::info('OnboardingService: Offboarding boshlandi', [
            'employee_id' => $employee->id,
            'checklist_id' => $checklist->id,
            'reason' => $reason,
        ]);

        return $checklist;
    }

    /**
     * Standart offboarding checklist
     */
    protected function getDefaultOffboardingItems(string $reason): array
    {
        return [
            [
                'category' => 'documentation',
                'title' => "Ishdan bo'shatish arizasi",
                'completed' => false,
            ],
            [
                'category' => 'documentation',
                'title' => "Hisob-kitob hujjatlari",
                'completed' => false,
            ],
            [
                'category' => 'assets',
                'title' => "Kompyuter va qurilmalarni qaytarish",
                'completed' => false,
            ],
            [
                'category' => 'assets',
                'title' => "Kirish kartasi/kalitlarni qaytarish",
                'completed' => false,
            ],
            [
                'category' => 'access',
                'title' => "Email va tizim loginlarini o'chirish",
                'completed' => false,
            ],
            [
                'category' => 'access',
                'title' => "Muhim fayllarni uzatish",
                'completed' => false,
            ],
            [
                'category' => 'knowledge',
                'title' => "Bilim uzatish sessiyasi",
                'completed' => false,
            ],
            [
                'category' => 'knowledge',
                'title' => "Joriy loyihalar holatini hujjatlashtirish",
                'completed' => false,
            ],
            [
                'category' => 'interview',
                'title' => "Exit interview o'tkazish",
                'completed' => false,
            ],
            [
                'category' => 'final',
                'title' => "Yakuniy to'lov hisoblash",
                'completed' => false,
            ],
            [
                'category' => 'final',
                'title' => "Ish guvohnomasi tayyorlash",
                'completed' => false,
            ],
        ];
    }

    /**
     * Onboarding so'rovnomasi yuborish
     */
    public function sendOnboardingSurvey(EmployeeOnboardingPlan $plan, string $phase): void
    {
        // Bu yerda onboarding feedback so'rovnomasi yuboriladi
        Log::info('OnboardingService: Onboarding so\'rovnomasi yuborildi', [
            'plan_id' => $plan->id,
            'phase' => $phase,
        ]);
    }

    /**
     * Mentor tayinlash
     */
    public function assignMentor(EmployeeOnboardingPlan $plan, User $mentor): bool
    {
        return $plan->update([
            'mentor_id' => $mentor->id,
        ]);
    }

    /**
     * Onboarding statistikasi
     */
    public function getOnboardingStats(Business $business): array
    {
        $plans = EmployeeOnboardingPlan::where('business_id', $business->id);

        return [
            'active_onboardings' => $plans->clone()->where('status', 'active')->count(),
            'completed_onboardings' => $plans->clone()->where('status', 'completed')->count(),
            'average_completion_rate' => round($plans->clone()->where('status', 'active')->avg('progress') ?? 0, 2),
            'overdue_tasks' => EmployeeOnboardingTask::where('business_id', $business->id)
                ->where('status', 'pending')
                ->where('due_date', '<', now())
                ->count(),
        ];
    }
}
