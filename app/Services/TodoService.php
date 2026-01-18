<?php

namespace App\Services;

use App\Models\Business;
use App\Models\Todo;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;

class TodoService
{
    protected array $typeLabels = [
        'personal' => 'Shaxsiy',
        'team' => 'Jamoa',
        'process' => 'Jarayon',
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
     * Get all todos grouped by due date category
     */
    public function getGroupedTodos(Business $business, ?string $filter = 'all', ?string $statusFilter = 'active'): array
    {
        $today = Carbon::today();
        $tomorrow = Carbon::tomorrow();
        $weekEnd = Carbon::now()->endOfWeek();

        // Build base query
        $query = Todo::where('business_id', $business->id)
            ->with(['subtasks', 'assignees.user', 'assignee'])
            ->orderBy('due_date', 'asc')
            ->orderBy('priority', 'desc');

        // Apply filter
        if ($filter === 'personal') {
            $query->where('type', 'personal');
        } elseif ($filter === 'team') {
            $query->where('type', 'team');
        } elseif ($filter === 'process') {
            $query->where('type', 'process');
        }

        // Apply status filter
        if ($statusFilter === 'active') {
            $query->where('status', '!=', 'completed');
        } elseif ($statusFilter === 'completed') {
            $query->where('status', 'completed');
        }

        $allTodos = $query->get();

        $grouped = [
            'overdue' => [],
            'today' => [],
            'tomorrow' => [],
            'this_week' => [],
            'later' => [],
        ];

        foreach ($allTodos as $todo) {
            $todoData = $this->formatTodo($todo);

            if ($todo->status === 'completed') {
                if ($statusFilter !== 'active') {
                    $grouped['later'][] = $todoData;
                }

                continue;
            }

            if (! $todo->due_date) {
                $grouped['later'][] = $todoData;
            } elseif ($todo->due_date->lt($today)) {
                $grouped['overdue'][] = $todoData;
            } elseif ($todo->due_date->isSameDay($today)) {
                $grouped['today'][] = $todoData;
            } elseif ($todo->due_date->isSameDay($tomorrow)) {
                $grouped['tomorrow'][] = $todoData;
            } elseif ($todo->due_date->lte($weekEnd)) {
                $grouped['this_week'][] = $todoData;
            } else {
                $grouped['later'][] = $todoData;
            }
        }

        return $grouped;
    }

    /**
     * Get todo statistics
     */
    public function getStats(Business $business): array
    {
        $today = Carbon::today();

        $activeTodos = Todo::where('business_id', $business->id)
            ->where('status', '!=', 'completed')
            ->get();

        $overdueCount = $activeTodos->filter(fn ($t) => $t->due_date && $t->due_date->lt($today))->count();

        $completedTodayCount = Todo::where('business_id', $business->id)
            ->where('status', 'completed')
            ->whereDate('completed_at', $today)
            ->count();

        return [
            'total' => $activeTodos->count(),
            'overdue' => $overdueCount,
            'completed_today' => $completedTodayCount,
        ];
    }

    /**
     * Format todo for frontend
     */
    public function formatTodo(Todo $todo): array
    {
        // Calculate subtasks progress
        $subtasksCount = $todo->subtasks->count();
        $completedSubtasksCount = $todo->subtasks->where('is_completed', true)->count();
        $progress = $subtasksCount > 0 ? round(($completedSubtasksCount / $subtasksCount) * 100) : 0;

        // Calculate team progress
        $teamProgress = 0;
        $assigneesCount = 0;
        $completedAssigneesCount = 0;
        if ($todo->type === 'team' && $todo->assignees) {
            $assigneesCount = $todo->assignees->count();
            $completedAssigneesCount = $todo->assignees->where('is_completed', true)->count();
            $teamProgress = $assigneesCount > 0 ? round(($completedAssigneesCount / $assigneesCount) * 100) : 0;
        }

        // Format due date
        $dueDateFormatted = null;
        $isOverdue = false;
        if ($todo->due_date) {
            $isOverdue = $todo->due_date->lt(Carbon::today()) && $todo->status !== 'completed';
            if ($todo->due_time) {
                $dueDateFormatted = $todo->due_date->format('d.m').' '.$todo->due_time;
            } else {
                $dueDateFormatted = $todo->due_date->format('d.m.Y');
            }
        }

        return [
            'id' => $todo->id,
            'title' => $todo->title,
            'description' => $todo->description,
            'type' => $todo->type ?? 'personal',
            'type_label' => $this->typeLabels[$todo->type ?? 'personal'] ?? 'Shaxsiy',
            'priority' => $todo->priority ?? 'medium',
            'priority_label' => $this->priorityLabels[$todo->priority ?? 'medium'] ?? 'O\'rta',
            'status' => $todo->status ?? 'pending',
            'due_date' => $todo->due_date?->format('Y-m-d'),
            'due_time' => $todo->due_time,
            'due_date_formatted' => $dueDateFormatted,
            'is_overdue' => $isOverdue,
            'is_recurring' => $todo->is_recurring ?? false,
            'recurrence_pattern' => $todo->recurrence_pattern,
            'subtasks' => $todo->subtasks->map(fn ($st) => [
                'id' => $st->id,
                'title' => $st->title,
                'is_completed' => $st->is_completed,
            ])->toArray(),
            'subtasks_count' => $subtasksCount,
            'completed_subtasks_count' => $completedSubtasksCount,
            'progress' => $progress,
            'assignee' => $todo->assignee ? [
                'id' => $todo->assignee->id,
                'name' => $todo->assignee->name,
            ] : null,
            'assignees' => $todo->assignees ? $todo->assignees->map(fn ($a) => [
                'id' => $a->id,
                'user_id' => $a->user_id,
                'user' => $a->user ? [
                    'id' => $a->user->id,
                    'name' => $a->user->name,
                ] : null,
                'is_completed' => $a->is_completed ?? false,
            ])->toArray() : [],
            'assignees_count' => $assigneesCount,
            'completed_assignees_count' => $completedAssigneesCount,
            'team_progress' => $teamProgress,
            'completed_at' => $todo->completed_at?->format('Y-m-d H:i'),
            'created_at' => $todo->created_at->format('Y-m-d H:i'),
        ];
    }

    /**
     * Create a new todo
     */
    public function create(Business $business, array $data): Todo
    {
        $todo = Todo::create([
            'business_id' => $business->id,
            'user_id' => Auth::id(),
            'title' => $data['title'],
            'description' => $data['description'] ?? null,
            'type' => $data['type'] ?? 'personal',
            'due_date' => $data['due_date'] ?? null,
            'due_time' => $data['due_time'] ?? null,
            'priority' => $data['priority'] ?? 'medium',
            'status' => 'pending',
        ]);

        // Attach assignees for team todos
        if (isset($data['assignee_ids']) && $data['type'] === 'team') {
            foreach ($data['assignee_ids'] as $userId) {
                $todo->assignees()->create([
                    'user_id' => $userId,
                    'is_completed' => false,
                ]);
            }
        }

        return $todo->fresh(['subtasks', 'assignees.user', 'assignee']);
    }

    /**
     * Update a todo
     */
    public function update(Todo $todo, array $data): Todo
    {
        $todo->update($data);

        return $todo->fresh(['subtasks', 'assignees.user', 'assignee']);
    }

    /**
     * Toggle todo status
     */
    public function toggle(Todo $todo): Todo
    {
        $newStatus = $todo->status === 'completed' ? 'pending' : 'completed';
        $todo->update([
            'status' => $newStatus,
            'completed_at' => $newStatus === 'completed' ? now() : null,
        ]);

        return $todo->fresh(['subtasks', 'assignees.user', 'assignee']);
    }

    /**
     * Delete a todo
     */
    public function delete(Todo $todo): bool
    {
        return $todo->delete();
    }

    /**
     * Add subtask to a todo
     */
    public function addSubtask(Todo $todo, string $title): object
    {
        return $todo->subtasks()->create([
            'title' => $title,
            'is_completed' => false,
        ]);
    }

    /**
     * Toggle subtask completion
     */
    public function toggleSubtask(Todo $todo, int $subtaskId): object
    {
        $subtask = $todo->subtasks()->findOrFail($subtaskId);
        $subtask->update(['is_completed' => ! $subtask->is_completed]);

        return $subtask;
    }

    /**
     * Get team members for assigning
     */
    public function getTeamMembers(Business $business): Collection
    {
        return User::whereHas('teamBusinesses', function ($q) use ($business) {
            $q->where('businesses.id', $business->id);
        })->select('id', 'name', 'email')->get();
    }

    /**
     * Get todo types
     */
    public function getTypes(): array
    {
        return $this->typeLabels;
    }

    /**
     * Get todo priorities
     */
    public function getPriorities(): array
    {
        return $this->priorityLabels;
    }

    /**
     * Get todo statuses
     */
    public function getStatuses(): array
    {
        return $this->statusLabels;
    }

    /**
     * Get overdue todos for a business
     */
    public function getOverdueTodos(Business $business): Collection
    {
        return Todo::where('business_id', $business->id)
            ->where('status', '!=', 'completed')
            ->whereNotNull('due_date')
            ->where('due_date', '<', Carbon::today())
            ->with(['subtasks', 'assignees.user', 'assignee'])
            ->orderBy('due_date', 'asc')
            ->get();
    }

    /**
     * Get todos due today
     */
    public function getTodayTodos(Business $business): Collection
    {
        return Todo::where('business_id', $business->id)
            ->where('status', '!=', 'completed')
            ->whereDate('due_date', Carbon::today())
            ->with(['subtasks', 'assignees.user', 'assignee'])
            ->orderBy('due_date', 'asc')
            ->get();
    }
}
