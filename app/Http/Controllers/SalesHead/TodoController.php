<?php

namespace App\Http\Controllers\SalesHead;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Traits\PanelTodoController;
use App\Models\Todo;
use Illuminate\Support\Facades\Auth;

class TodoController extends Controller
{
    use PanelTodoController;

    protected function getViewPrefix(): string
    {
        return 'SalesHead';
    }

    protected function getRoutePrefix(): string
    {
        return 'sales-head';
    }

    protected function groupTodosByPeriod($todos): array
    {
        $now = now();
        $grouped = [
            'overdue' => [],
            'today' => [],
            'tomorrow' => [],
            'this_week' => [],
            'later' => [],
            'no_date' => [],
        ];

        foreach ($todos as $todo) {
            $todoData = $this->formatTodo($todo);

            if ($todo->status === Todo::STATUS_COMPLETED) {
                continue;
            }

            if (! $todo->due_date) {
                $grouped['no_date'][] = $todoData;
            } elseif ($todo->due_date->lt($now) && ! $todo->due_date->isToday()) {
                $grouped['overdue'][] = $todoData;
            } elseif ($todo->due_date->isToday()) {
                $grouped['today'][] = $todoData;
            } elseif ($todo->due_date->isTomorrow()) {
                $grouped['tomorrow'][] = $todoData;
            } elseif ($todo->due_date->isCurrentWeek()) {
                $grouped['this_week'][] = $todoData;
            } else {
                $grouped['later'][] = $todoData;
            }
        }

        return $grouped;
    }

    protected function formatTodo($todo): array
    {
        $subtasksData = $todo->subtasks->map(fn ($subtask) => [
            'id' => $subtask->id,
            'title' => $subtask->title,
            'status' => $subtask->status,
            'is_completed' => $subtask->status === Todo::STATUS_COMPLETED,
            'order' => $subtask->order,
        ])->toArray();

        $assigneesData = $todo->assignees->map(fn ($assignee) => [
            'id' => $assignee->id,
            'user_id' => $assignee->user_id,
            'user' => $assignee->user ? [
                'id' => $assignee->user->id,
                'name' => $assignee->user->name,
            ] : null,
            'is_completed' => $assignee->is_completed,
            'completed_at' => $assignee->completed_at?->format('d.m.Y H:i'),
            'note' => $assignee->note,
        ])->toArray();

        $myAssignment = $todo->assignees->where('user_id', Auth::id())->first();

        return [
            'id' => $todo->id,
            'title' => $todo->title,
            'description' => $todo->description,
            'type' => $todo->type,
            'type_label' => $todo->type_label,
            'priority' => $todo->priority,
            'priority_label' => $todo->priority_label,
            'priority_color' => $todo->priority_color,
            'status' => $todo->status,
            'status_label' => $todo->status_label,
            'due_date' => $todo->due_date?->format('Y-m-d H:i'),
            'due_date_formatted' => $todo->due_date_formatted,
            'time_period' => $todo->time_period,
            'is_overdue' => $todo->is_overdue,
            'is_recurring' => $todo->is_recurring,
            'assignee' => $todo->assignee ? [
                'id' => $todo->assignee->id,
                'name' => $todo->assignee->name,
            ] : null,
            'is_team_task' => $todo->is_team_task,
            'assignees' => $assigneesData,
            'assignees_count' => $todo->assignees_count,
            'completed_assignees_count' => $todo->completed_assignees_count,
            'team_progress' => $todo->team_progress,
            'is_team_completed' => $todo->is_team_completed,
            'my_assignment' => $myAssignment ? [
                'id' => $myAssignment->id,
                'is_completed' => $myAssignment->is_completed,
                'completed_at' => $myAssignment->completed_at?->format('d.m.Y H:i'),
            ] : null,
            'can_complete' => $todo->canCurrentUserComplete(),
            'subtasks' => $subtasksData,
            'subtasks_count' => count($subtasksData),
            'completed_subtasks_count' => collect($subtasksData)->where('is_completed', true)->count(),
            'progress' => $todo->progress,
            'recurrence' => $todo->recurrence ? [
                'id' => $todo->recurrence->id,
                'frequency' => $todo->recurrence->frequency,
                'frequency_label' => $todo->recurrence->frequency_label,
                'description' => $todo->recurrence->description,
                'is_active' => $todo->recurrence->is_active,
            ] : null,
            'order' => $todo->order,
            'created_by' => $todo->created_by,
            'created_at' => $todo->created_at->format('d.m.Y H:i'),
        ];
    }

    protected function getTodoStats($business): array
    {
        $userId = Auth::id();

        return [
            'total' => Todo::where('business_id', $business->id)
                ->whereNull('parent_id')
                ->whereIn('status', [Todo::STATUS_PENDING, Todo::STATUS_IN_PROGRESS])
                ->count(),
            'overdue' => Todo::where('business_id', $business->id)
                ->whereNull('parent_id')
                ->overdue()
                ->count(),
            'today' => Todo::where('business_id', $business->id)
                ->whereNull('parent_id')
                ->today()
                ->pending()
                ->count(),
            'completed_today' => Todo::where('business_id', $business->id)
                ->whereNull('parent_id')
                ->where('status', Todo::STATUS_COMPLETED)
                ->whereDate('completed_at', today())
                ->count(),
            'my_todos' => Todo::where('business_id', $business->id)
                ->whereNull('parent_id')
                ->myTodos($userId)
                ->whereIn('status', [Todo::STATUS_PENDING, Todo::STATUS_IN_PROGRESS])
                ->count(),
        ];
    }

    /**
     * Dashboard widget — SalesHead specific
     */
    public function dashboard()
    {
        $business = $this->getCurrentBusiness();

        if (! $business) {
            return response()->json(['error' => 'Biznes topilmadi'], 404);
        }

        $userId = Auth::id();

        $todayTodos = Todo::where('business_id', $business->id)
            ->whereNull('parent_id')
            ->where(function ($q) use ($userId) {
                $q->where('assigned_to', $userId)
                    ->orWhere('created_by', $userId);
            })
            ->whereDate('due_date', today())
            ->orderBy('priority', 'desc')
            ->orderBy('due_date')
            ->limit(5)
            ->get()
            ->map(fn ($todo) => [
                'id' => $todo->id,
                'title' => $todo->title,
                'priority' => $todo->priority,
                'priority_color' => $todo->priority_color,
                'status' => $todo->status,
                'due_time' => $todo->due_date?->format('H:i'),
                'is_completed' => $todo->status === Todo::STATUS_COMPLETED,
            ]);

        $stats = [
            'total_today' => Todo::where('business_id', $business->id)
                ->whereNull('parent_id')
                ->whereDate('due_date', today())
                ->count(),
            'completed_today' => Todo::where('business_id', $business->id)
                ->whereNull('parent_id')
                ->whereDate('due_date', today())
                ->where('status', Todo::STATUS_COMPLETED)
                ->count(),
            'overdue' => Todo::where('business_id', $business->id)
                ->whereNull('parent_id')
                ->overdue()
                ->count(),
        ];

        $stats['progress'] = $stats['total_today'] > 0
            ? round(($stats['completed_today'] / $stats['total_today']) * 100)
            : 0;

        return response()->json([
            'todos' => $todayTodos,
            'stats' => $stats,
        ]);
    }
}
