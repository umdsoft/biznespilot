<?php

namespace App\Services\HR;

use App\Models\Business;
use App\Models\EmployeeGoal;
use App\Models\OneOnOneMeeting;
use App\Models\PerformanceReview;
use App\Models\SalaryHistory;
use App\Models\SalaryStructure;
use App\Models\Task;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * PerformanceService - Hodim ish samaradorligini boshqarish
 *
 * Vazifalar:
 * 1. Maqsadlar va OKR boshqarish
 * 2. 1-on-1 uchrashuvlar
 * 3. Performance review
 * 4. Maosh o'zgarishlarini tracking
 */
class PerformanceService
{
    /**
     * Maoshni lavozim ko'tarilishi uchun yangilash
     */
    public function updateSalaryForPromotion(User $employee, Business $business, float $salaryChange): void
    {
        // Joriy maosh tuzilmasini olish
        $currentStructure = SalaryStructure::where('business_id', $business->id)
            ->where('user_id', $employee->id)
            ->where('is_active', true)
            ->first();

        if (!$currentStructure) {
            Log::warning('PerformanceService: Maosh tuzilmasi topilmadi', [
                'employee_id' => $employee->id,
            ]);
            return;
        }

        $oldSalary = $currentStructure->base_salary;
        $newSalary = $oldSalary + $salaryChange;

        // Eski tuzilmani deaktivatsiya qilish
        $currentStructure->update([
            'is_active' => false,
            'effective_until' => now()->toDateString(),
        ]);

        // Yangi tuzilma yaratish
        SalaryStructure::create([
            'business_id' => $business->id,
            'user_id' => $employee->id,
            'base_salary' => $newSalary,
            'payment_frequency' => $currentStructure->payment_frequency,
            'effective_from' => now()->toDateString(),
            'allowances' => $currentStructure->allowances,
            'deductions' => $currentStructure->deductions,
            'is_active' => true,
        ]);

        // Tarixni saqlash
        SalaryHistory::create([
            'business_id' => $business->id,
            'user_id' => $employee->id,
            'old_salary' => $oldSalary,
            'new_salary' => $newSalary,
            'change_amount' => $salaryChange,
            'change_percentage' => round(($salaryChange / $oldSalary) * 100, 2),
            'reason' => 'Lavozim ko\'tarilishi',
            'effective_date' => now()->toDateString(),
        ]);

        Log::info('PerformanceService: Maosh yangilandi', [
            'employee_id' => $employee->id,
            'old_salary' => $oldSalary,
            'new_salary' => $newSalary,
        ]);
    }

    /**
     * 1-on-1 uchrashuv rejalashtirish
     */
    public function scheduleOneOnOne(User $employee, Business $business, string $type, string $notes = ''): OneOnOneMeeting
    {
        // Keyingi ish kunini topish
        $scheduledDate = now()->addDays(3);
        while ($scheduledDate->isWeekend()) {
            $scheduledDate->addDay();
        }

        $meeting = OneOnOneMeeting::create([
            'business_id' => $business->id,
            'employee_id' => $employee->id,
            'manager_id' => $employee->manager_id ?? null,
            'meeting_type' => $type,
            'scheduled_date' => $scheduledDate->toDateString(),
            'scheduled_time' => '10:00',
            'duration_minutes' => 30,
            'status' => 'scheduled',
            'notes' => $notes,
        ]);

        Log::info('PerformanceService: 1-on-1 rejalashtirildi', [
            'meeting_id' => $meeting->id,
            'employee_id' => $employee->id,
            'type' => $type,
            'date' => $scheduledDate->toDateString(),
        ]);

        return $meeting;
    }

    /**
     * Keyingi 1-on-1 ni avtomatik rejalashtirish
     */
    public function scheduleNextOneOnOne(User $employee, User $manager, Business $business): void
    {
        // Oxirgi uchrashuvdan 2 hafta keyin
        $lastMeeting = OneOnOneMeeting::where('business_id', $business->id)
            ->where('employee_id', $employee->id)
            ->where('manager_id', $manager->id)
            ->orderBy('scheduled_date', 'desc')
            ->first();

        $nextDate = $lastMeeting
            ? $lastMeeting->scheduled_date->addWeeks(2)
            : now()->addWeeks(2);

        while ($nextDate->isWeekend()) {
            $nextDate->addDay();
        }

        OneOnOneMeeting::create([
            'business_id' => $business->id,
            'employee_id' => $employee->id,
            'manager_id' => $manager->id,
            'meeting_type' => 'regular',
            'scheduled_date' => $nextDate->toDateString(),
            'scheduled_time' => $lastMeeting?->scheduled_time ?? '10:00',
            'duration_minutes' => 30,
            'status' => 'scheduled',
        ]);

        Log::info('PerformanceService: Keyingi 1-on-1 rejalashtirildi', [
            'employee_id' => $employee->id,
            'manager_id' => $manager->id,
            'date' => $nextDate->toDateString(),
        ]);
    }

    /**
     * Stay interview rejalashtirish
     */
    public function scheduleStayInterview(User $employee, Business $business): OneOnOneMeeting
    {
        return $this->scheduleOneOnOne(
            $employee,
            $business,
            'stay_interview',
            "Ketish xavfi yuqori - retention suhbati zarur"
        );
    }

    /**
     * Action item dan task yaratish
     */
    public function createActionItemTask(User $employee, Business $business, array $item, string $meetingId): Task
    {
        $task = Task::create([
            'business_id' => $business->id,
            'title' => $item['title'] ?? $item['description'] ?? 'Action item',
            'description' => $item['description'] ?? null,
            'assigned_to' => $item['assigned_to'] ?? $employee->id,
            'due_date' => $item['due_date'] ?? now()->addWeeks(1)->toDateString(),
            'priority' => $item['priority'] ?? 'medium',
            'status' => 'pending',
            'source_type' => 'one_on_one',
            'source_id' => $meetingId,
        ]);

        Log::info('PerformanceService: Action item task yaratildi', [
            'task_id' => $task->id,
            'employee_id' => $employee->id,
            'meeting_id' => $meetingId,
        ]);

        return $task;
    }

    /**
     * Yangi maqsad yaratish
     */
    public function createGoal(User $employee, Business $business, array $data): EmployeeGoal
    {
        $goal = EmployeeGoal::create([
            'business_id' => $business->id,
            'user_id' => $employee->id,
            'kpi_template_id' => $data['kpi_template_id'] ?? null,
            'title' => $data['title'],
            'description' => $data['description'] ?? null,
            'start_date' => $data['start_date'] ?? now()->toDateString(),
            'due_date' => $data['due_date'],
            'target_value' => $data['target_value'] ?? null,
            'current_value' => $data['current_value'] ?? 0,
            'measurement_unit' => $data['measurement_unit'] ?? null,
            'status' => 'active',
            'progress' => 0,
            'created_by' => $data['created_by'] ?? null,
        ]);

        Log::info('PerformanceService: Yangi maqsad yaratildi', [
            'goal_id' => $goal->id,
            'employee_id' => $employee->id,
            'title' => $data['title'],
        ]);

        return $goal;
    }

    /**
     * Maqsad progressini yangilash
     */
    public function updateGoalProgress(EmployeeGoal $goal, float $progress, ?float $currentValue = null): void
    {
        $oldProgress = $goal->progress;

        $goal->update([
            'progress' => min(100, max(0, $progress)),
            'current_value' => $currentValue ?? $goal->current_value,
        ]);

        // 100% ga yetsa - yakunlash
        if ($progress >= 100 && $oldProgress < 100) {
            $goal->update([
                'status' => 'completed',
                'completed_at' => now(),
            ]);
        }

        Log::info('PerformanceService: Maqsad progress yangilandi', [
            'goal_id' => $goal->id,
            'old_progress' => $oldProgress,
            'new_progress' => $progress,
        ]);
    }

    /**
     * Performance review yaratish
     */
    public function createPerformanceReview(User $employee, Business $business, string $period, array $data = []): PerformanceReview
    {
        $review = PerformanceReview::create([
            'business_id' => $business->id,
            'user_id' => $employee->id,
            'reviewer_id' => $data['reviewer_id'] ?? null,
            'review_period' => $period,
            'review_date' => $data['review_date'] ?? now()->toDateString(),
            'status' => 'draft',
            'overall_rating' => $data['overall_rating'] ?? null,
            'strengths' => $data['strengths'] ?? null,
            'improvements' => $data['improvements'] ?? null,
            'goals_achieved' => $data['goals_achieved'] ?? null,
            'notes' => $data['notes'] ?? null,
        ]);

        Log::info('PerformanceService: Performance review yaratildi', [
            'review_id' => $review->id,
            'employee_id' => $employee->id,
            'period' => $period,
        ]);

        return $review;
    }

    /**
     * Hodim performance statistikasi
     */
    public function getEmployeePerformanceStats(User $employee, Business $business): array
    {
        $goals = EmployeeGoal::where('business_id', $business->id)
            ->where('user_id', $employee->id);

        $reviews = PerformanceReview::where('business_id', $business->id)
            ->where('user_id', $employee->id);

        $meetings = OneOnOneMeeting::where('business_id', $business->id)
            ->where('employee_id', $employee->id);

        return [
            'active_goals' => $goals->clone()->where('status', 'active')->count(),
            'completed_goals' => $goals->clone()->where('status', 'completed')->count(),
            'average_goal_progress' => round($goals->clone()->where('status', 'active')->avg('progress') ?? 0, 2),
            'total_reviews' => $reviews->clone()->count(),
            'average_rating' => round($reviews->clone()->whereNotNull('overall_rating')->avg('overall_rating') ?? 0, 2),
            'one_on_ones_this_quarter' => $meetings->clone()
                ->where('status', 'completed')
                ->where('scheduled_date', '>=', now()->startOfQuarter())
                ->count(),
            'upcoming_meetings' => $meetings->clone()
                ->where('status', 'scheduled')
                ->where('scheduled_date', '>=', now())
                ->count(),
        ];
    }

    /**
     * Team performance statistikasi
     */
    public function getTeamPerformanceStats(Business $business, ?string $department = null): array
    {
        $goalsQuery = EmployeeGoal::where('business_id', $business->id);

        if ($department) {
            $goalsQuery->whereHas('user', function ($q) use ($department) {
                $q->whereHas('businessUsers', function ($bq) use ($department) {
                    $bq->where('department', $department);
                });
            });
        }

        $activeGoals = $goalsQuery->clone()->where('status', 'active');
        $completedGoals = $goalsQuery->clone()->where('status', 'completed');

        return [
            'total_active_goals' => $activeGoals->count(),
            'total_completed_goals' => $completedGoals->count(),
            'average_team_progress' => round($activeGoals->avg('progress') ?? 0, 2),
            'on_track_goals' => $activeGoals->clone()->where('progress', '>=', 70)->count(),
            'at_risk_goals' => $activeGoals->clone()->whereBetween('progress', [40, 70])->count(),
            'behind_goals' => $activeGoals->clone()->where('progress', '<', 40)->count(),
        ];
    }
}
