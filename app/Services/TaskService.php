<?php

namespace App\Services;

use App\Models\Business;
use App\Models\Lead;
use App\Models\Task;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;

class TaskService
{
    protected array $typeLabels = [
        'call' => 'Qo\'ng\'iroq',
        'meeting' => 'Uchrashuv',
        'task' => 'Vazifa',
        'follow_up' => 'Qayta aloqa',
        'email' => 'Email',
        'other' => 'Boshqa',
    ];

    protected array $priorityLabels = [
        'urgent' => 'Shoshilinch',
        'high' => 'Yuqori',
        'medium' => 'O\'rta',
        'low' => 'Past',
    ];

    protected array $statusLabels = [
        'pending' => 'Kutilmoqda',
        'in_progress' => 'Jarayonda',
        'completed' => 'Bajarildi',
    ];

    /**
     * Get all tasks grouped by due date category
     */
    public function getGroupedTasks(Business $business): array
    {
        $today = Carbon::today();
        $tomorrow = Carbon::tomorrow();
        $weekEnd = Carbon::now()->endOfWeek();

        // Get all active tasks
        $allTasks = Task::where('business_id', $business->id)
            ->where('status', '!=', 'completed')
            ->with(['assignedUser', 'lead'])
            ->orderBy('due_date', 'asc')
            ->get();

        // Get completed tasks (last 7 days)
        $completedTasks = Task::where('business_id', $business->id)
            ->where('status', 'completed')
            ->where('completed_at', '>=', now()->subDays(7))
            ->with(['assignedUser', 'lead'])
            ->orderBy('completed_at', 'desc')
            ->limit(20)
            ->get();

        $grouped = [
            'overdue' => [],
            'today' => [],
            'tomorrow' => [],
            'this_week' => [],
            'later' => [],
            'completed' => [],
        ];

        foreach ($allTasks as $task) {
            $taskData = $this->formatTask($task);

            if (! $task->due_date) {
                $grouped['later'][] = $taskData;
            } elseif ($task->due_date->lt($today)) {
                $grouped['overdue'][] = $taskData;
            } elseif ($task->due_date->isSameDay($today)) {
                $grouped['today'][] = $taskData;
            } elseif ($task->due_date->isSameDay($tomorrow)) {
                $grouped['tomorrow'][] = $taskData;
            } elseif ($task->due_date->lte($weekEnd)) {
                $grouped['this_week'][] = $taskData;
            } else {
                $grouped['later'][] = $taskData;
            }
        }

        foreach ($completedTasks as $task) {
            $grouped['completed'][] = $this->formatTask($task);
        }

        return $grouped;
    }

    /**
     * Get task statistics
     */
    public function getStats(Business $business): array
    {
        $today = Carbon::today();
        $tomorrow = Carbon::tomorrow();
        $weekEnd = Carbon::now()->endOfWeek();

        $allTasks = Task::where('business_id', $business->id)
            ->where('status', '!=', 'completed')
            ->get();

        $completedCount = Task::where('business_id', $business->id)
            ->where('status', 'completed')
            ->where('completed_at', '>=', now()->subDays(7))
            ->count();

        $overdue = $allTasks->filter(fn ($t) => $t->due_date && $t->due_date->lt($today))->count();
        $todayCount = $allTasks->filter(fn ($t) => $t->due_date && $t->due_date->isSameDay($today))->count();
        $tomorrowCount = $allTasks->filter(fn ($t) => $t->due_date && $t->due_date->isSameDay($tomorrow))->count();
        $thisWeekCount = $allTasks->filter(fn ($t) => $t->due_date && $t->due_date->gt($tomorrow) && $t->due_date->lte($weekEnd))->count();

        return [
            'total' => $allTasks->count(),
            'overdue' => $overdue,
            'today' => $todayCount,
            'tomorrow' => $tomorrowCount,
            'this_week' => $thisWeekCount,
            'completed' => $completedCount,
        ];
    }

    /**
     * Format task for frontend
     */
    public function formatTask(Task $task): array
    {
        $dueDateHuman = '';
        $dueDateFull = '';

        if ($task->due_date) {
            $dueDateHuman = $task->due_date->format('d-M H:i');
            $dueDateFull = $task->due_date->format('d.m.Y H:i');
        }

        return [
            'id' => $task->id,
            'title' => $task->title,
            'description' => $task->description,
            'type' => $task->type ?? 'task',
            'type_label' => $this->typeLabels[$task->type ?? 'task'] ?? 'Vazifa',
            'priority' => $task->priority ?? 'medium',
            'priority_label' => $this->priorityLabels[$task->priority ?? 'medium'] ?? 'O\'rta',
            'status' => $task->status,
            'due_date' => $task->due_date?->format('Y-m-d H:i'),
            'due_date_human' => $dueDateHuman,
            'due_date_full' => $dueDateFull,
            'assignee' => $task->assignedUser ? [
                'id' => $task->assignedUser->id,
                'name' => $task->assignedUser->name,
            ] : null,
            'lead' => $task->lead ? [
                'id' => $task->lead->id,
                'name' => $task->lead->name,
                'phone' => $task->lead->phone ?? null,
            ] : null,
            'completed_at' => $task->completed_at?->format('Y-m-d H:i'),
            'created_at' => $task->created_at->format('Y-m-d H:i'),
        ];
    }

    /**
     * Get leads for task creation
     */
    public function getLeadsForSelection(Business $business): Collection
    {
        return Lead::where('business_id', $business->id)
            ->whereNotIn('status', ['won', 'lost'])
            ->orderBy('name')
            ->get()
            ->map(fn ($lead) => [
                'id' => $lead->id,
                'name' => $lead->name,
                'phone' => $lead->phone,
            ]);
    }

    /**
     * Create a new task
     */
    public function create(Business $business, array $data): Task
    {
        return Task::create([
            'business_id' => $business->id,
            'user_id' => Auth::id(),
            'title' => $data['title'],
            'description' => $data['description'] ?? null,
            'due_date' => $data['due_date'] ?? now(),
            'priority' => $data['priority'] ?? 'medium',
            'assigned_to' => $data['assigned_to'] ?? null,
            'lead_id' => $data['lead_id'] ?? null,
            'type' => $data['type'] ?? 'task',
            'status' => 'pending',
        ]);
    }

    /**
     * Update a task
     */
    public function update(Task $task, array $data): Task
    {
        $task->update($data);

        return $task->fresh();
    }

    /**
     * Mark task as completed
     */
    public function complete(Task $task): Task
    {
        $task->update([
            'status' => 'completed',
            'completed_at' => now(),
        ]);

        return $task->fresh();
    }

    /**
     * Delete a task
     */
    public function delete(Task $task): bool
    {
        return $task->delete();
    }

    /**
     * Get task types with labels
     */
    public function getTypes(): array
    {
        return $this->typeLabels;
    }

    /**
     * Get task priorities with labels
     */
    public function getPriorities(): array
    {
        return $this->priorityLabels;
    }

    /**
     * Get task statuses with labels
     */
    public function getStatuses(): array
    {
        return $this->statusLabels;
    }

    /**
     * Get tasks for a specific user
     */
    public function getUserTasks(User $user, ?Business $business = null): Collection
    {
        $query = Task::where('assigned_to', $user->id)
            ->where('status', '!=', 'completed');

        if ($business) {
            $query->where('business_id', $business->id);
        }

        return $query->orderBy('due_date', 'asc')->get();
    }

    /**
     * Get overdue tasks for a business
     */
    public function getOverdueTasks(Business $business): Collection
    {
        return Task::where('business_id', $business->id)
            ->where('status', '!=', 'completed')
            ->whereNotNull('due_date')
            ->where('due_date', '<', Carbon::today())
            ->with(['assignedUser', 'lead'])
            ->orderBy('due_date', 'asc')
            ->get();
    }

    /**
     * Get tasks due today
     */
    public function getTodayTasks(Business $business): Collection
    {
        return Task::where('business_id', $business->id)
            ->where('status', '!=', 'completed')
            ->whereDate('due_date', Carbon::today())
            ->with(['assignedUser', 'lead'])
            ->orderBy('due_date', 'asc')
            ->get();
    }
}
