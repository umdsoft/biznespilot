<?php

namespace App\Observers;

use App\Jobs\Sales\UpdateUserKpiSnapshotJob;
use App\Models\SalesPenaltyWarning;
use App\Models\Task;
use App\Services\Pipeline\PipelineAutomationService;
use App\Services\Sales\AchievementService;
use App\Services\Sales\LeaderboardService;
use App\Services\Sales\PenaltyService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class TaskObserver
{
    /**
     * Task yaratilganda
     */
    public function created(Task $task): void
    {
        // Vazifa deadline bor bo'lsa, ogohlantirish yaratish
        if ($task->assigned_to && $task->due_date) {
            $this->scheduleOverdueWarning($task);
        }

        Log::info('TaskObserver: Task created', [
            'task_id' => $task->id,
            'business_id' => $task->business_id,
            'assigned_to' => $task->assigned_to,
            'due_date' => $task->due_date?->format('Y-m-d H:i'),
        ]);

        // Pipeline avtomatizatsiya - vazifa yaratilganda stage o'zgartirish
        if ($task->lead_id) {
            $this->processPipelineAutomation($task, 'task_created');
        }
    }

    /**
     * Task yangilanganda
     */
    public function updated(Task $task): void
    {
        // Status o'zgargan bo'lsa
        if ($task->isDirty('status')) {
            $this->handleStatusChange($task);
        }

        // Due date o'zgargan bo'lsa
        if ($task->isDirty('due_date')) {
            $this->handleDueDateChange($task);
        }

        // Tayinlangan odam o'zgargan bo'lsa
        if ($task->isDirty('assigned_to')) {
            $this->handleAssignmentChange($task);
        }
    }

    /**
     * Status o'zgarishini qayta ishlash
     */
    protected function handleStatusChange(Task $task): void
    {
        $oldStatus = $task->getOriginal('status');
        $newStatus = $task->status;

        Log::info('TaskObserver: Status changed', [
            'task_id' => $task->id,
            'old_status' => $oldStatus,
            'new_status' => $newStatus,
        ]);

        // Vazifa bajarildi
        if ($newStatus === 'completed') {
            $this->handleTaskCompleted($task);
        }

        // Vazifa bekor qilindi
        if ($newStatus === 'cancelled') {
            $this->handleTaskCancelled($task);
        }
    }

    /**
     * Vazifa bajarilganda
     */
    protected function handleTaskCompleted(Task $task): void
    {
        if (!$task->assigned_to) {
            return;
        }

        Log::info('TaskObserver: Task completed', [
            'task_id' => $task->id,
            'user_id' => $task->assigned_to,
            'type' => $task->type,
            'completed_on_time' => $task->due_date ? $task->due_date->isFuture() || $task->due_date->isToday() : true,
        ]);

        // 1. Warning larni bekor qilish
        SalesPenaltyWarning::where('business_id', $task->business_id)
            ->where('user_id', $task->assigned_to)
            ->where('related_type', 'task')
            ->where('related_id', $task->id)
            ->whereIn('status', ['pending', 'warned'])
            ->update(['status' => 'resolved']);

        // 2. KPI snapshotni yangilash (async)
        UpdateUserKpiSnapshotJob::dispatch(
            $task->business_id,
            $task->assigned_to,
            Carbon::today()
        );

        // 3. Leaderboardni yangilash
        try {
            // Vazifa turiga qarab ball berish
            $points = match ($task->type) {
                'meeting' => 50,
                'call' => 20,
                'email' => 10,
                'proposal' => 30,
                default => 15,
            };

            // Muddatidan oldin bajarsa bonus
            if ($task->due_date && $task->due_date->isFuture()) {
                $points = (int) ($points * 1.2); // 20% bonus
            }

            app(LeaderboardService::class)->updateUserScore(
                $task->business_id,
                $task->assigned_to,
                'task_completed',
                $points
            );
        } catch (\Exception $e) {
            Log::error('TaskObserver: Failed to update leaderboard', [
                'error' => $e->getMessage(),
            ]);
        }

        // 4. Achievement tekshirish
        try {
            app(AchievementService::class)->checkAndAwardAchievements(
                $task->business_id,
                $task->assigned_to,
                'task_completed'
            );
        } catch (\Exception $e) {
            Log::error('TaskObserver: Failed to check achievements', [
                'error' => $e->getMessage(),
            ]);
        }

        // 5. Streak yangilash
        try {
            app(AchievementService::class)->updateStreak(
                $task->business_id,
                $task->assigned_to,
                'tasks_completed'
            );
        } catch (\Exception $e) {
            Log::error('TaskObserver: Failed to update streak', [
                'error' => $e->getMessage(),
            ]);
        }

        // 6. Pipeline avtomatizatsiya - vazifa bajarilganda stage o'zgartirish
        if ($task->lead_id) {
            $this->processPipelineAutomation($task, 'task_completed');
        }
    }

    /**
     * Vazifa bekor qilinganda
     */
    protected function handleTaskCancelled(Task $task): void
    {
        // Warning larni bekor qilish
        SalesPenaltyWarning::where('business_id', $task->business_id)
            ->where('related_type', 'task')
            ->where('related_id', $task->id)
            ->whereIn('status', ['pending', 'warned'])
            ->update(['status' => 'cancelled']);
    }

    /**
     * Due date o'zgarishini qayta ishlash
     */
    protected function handleDueDateChange(Task $task): void
    {
        // Eski warningni o'chirish
        SalesPenaltyWarning::where('business_id', $task->business_id)
            ->where('related_type', 'task')
            ->where('related_id', $task->id)
            ->whereIn('status', ['pending', 'warned'])
            ->delete();

        // Yangi warning yaratish
        if ($task->assigned_to && $task->due_date && $task->status === 'pending') {
            $this->scheduleOverdueWarning($task);
        }
    }

    /**
     * Tayinlash o'zgarishini qayta ishlash
     */
    protected function handleAssignmentChange(Task $task): void
    {
        $oldAssignee = $task->getOriginal('assigned_to');
        $newAssignee = $task->assigned_to;

        Log::info('TaskObserver: Assignment changed', [
            'task_id' => $task->id,
            'old_assignee' => $oldAssignee,
            'new_assignee' => $newAssignee,
        ]);

        // Eski odamning warninglarini o'chirish
        if ($oldAssignee) {
            SalesPenaltyWarning::where('business_id', $task->business_id)
                ->where('user_id', $oldAssignee)
                ->where('related_type', 'task')
                ->where('related_id', $task->id)
                ->whereIn('status', ['pending', 'warned'])
                ->delete();
        }

        // Yangi odam uchun warning yaratish
        if ($newAssignee && $task->due_date && $task->status === 'pending') {
            $this->scheduleOverdueWarning($task);
        }
    }

    /**
     * Muddati o'tish ogohlantirishini yaratish
     */
    protected function scheduleOverdueWarning(Task $task): void
    {
        if (!$task->assigned_to || !$task->due_date) {
            return;
        }

        // Muddat allaqachon o'tgan bo'lsa
        if ($task->due_date->isPast()) {
            return;
        }

        try {
            // Mavjud warning bormi tekshirish
            $exists = SalesPenaltyWarning::where('business_id', $task->business_id)
                ->where('user_id', $task->assigned_to)
                ->where('related_type', 'task')
                ->where('related_id', $task->id)
                ->whereIn('status', ['pending', 'warned'])
                ->exists();

            if ($exists) {
                return;
            }

            SalesPenaltyWarning::create([
                'business_id' => $task->business_id,
                'user_id' => $task->assigned_to,
                'rule_code' => 'task_overdue',
                'related_type' => 'task',
                'related_id' => $task->id,
                'deadline_at' => $task->due_date,
                'status' => 'pending',
                'auto_convert' => true,
            ]);
        } catch (\Exception $e) {
            Log::error('TaskObserver: Failed to create overdue warning', [
                'task_id' => $task->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Pipeline avtomatizatsiya
     */
    protected function processPipelineAutomation(Task $task, string $triggerType): void
    {
        if (! $task->lead_id || ! $task->lead) {
            return;
        }

        try {
            app(PipelineAutomationService::class)->processEvent(
                $triggerType,
                $task->lead,
                [
                    'type' => $task->type,
                    'priority' => $task->priority,
                    'result' => $task->result,
                ]
            );
        } catch (\Exception $e) {
            Log::error('TaskObserver: Failed to process pipeline automation', [
                'task_id' => $task->id,
                'lead_id' => $task->lead_id,
                'trigger' => $triggerType,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
