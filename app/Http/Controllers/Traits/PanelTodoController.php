<?php

namespace App\Http\Controllers\Traits;

use App\Models\Todo;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

trait PanelTodoController
{
    use HasCurrentBusiness;

    abstract protected function getViewPrefix(): string;

    abstract protected function getRoutePrefix(): string;

    public function index(Request $request)
    {
        $business = $this->getCurrentBusiness();

        if (! $business) {
            return inertia($this->getViewPrefix().'/Todos/Index', [
                'todos' => [
                    'overdue' => [],
                    'today' => [],
                    'tomorrow' => [],
                    'this_week' => [],
                    'later' => [],
                ],
                'stats' => [
                    'total' => 0,
                    'overdue' => 0,
                    'completed_today' => 0,
                ],
                'teamMembers' => [],
                'templates' => [],
                'types' => $this->getTodoTypes(),
                'priorities' => $this->getTodoPriorities(),
                'statuses' => $this->getTodoStatuses(),
                'filter' => $request->get('filter', 'all'),
                'statusFilter' => $request->get('status', 'active'),
            ]);
        }

        $today = Carbon::today();
        $tomorrow = Carbon::tomorrow();
        $weekEnd = Carbon::now()->endOfWeek();

        $filter = $request->get('filter', 'all');
        $statusFilter = $request->get('status', 'active');

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

        // Group todos by due date category
        $groupedTodos = [
            'overdue' => [],
            'today' => [],
            'tomorrow' => [],
            'this_week' => [],
            'later' => [],
        ];

        foreach ($allTodos as $todo) {
            $todoData = $this->formatTodo($todo);

            if ($todo->status === 'completed') {
                // Skip completed in active view, but they'll show in 'completed' status filter
                if ($statusFilter !== 'active') {
                    $groupedTodos['later'][] = $todoData;
                }

                continue;
            }

            if (! $todo->due_date) {
                $groupedTodos['later'][] = $todoData;
            } elseif ($todo->due_date->lt($today)) {
                $groupedTodos['overdue'][] = $todoData;
            } elseif ($todo->due_date->isSameDay($today)) {
                $groupedTodos['today'][] = $todoData;
            } elseif ($todo->due_date->isSameDay($tomorrow)) {
                $groupedTodos['tomorrow'][] = $todoData;
            } elseif ($todo->due_date->lte($weekEnd)) {
                $groupedTodos['this_week'][] = $todoData;
            } else {
                $groupedTodos['later'][] = $todoData;
            }
        }

        // Calculate stats
        $stats = [
            'total' => $allTodos->where('status', '!=', 'completed')->count(),
            'overdue' => count($groupedTodos['overdue']),
            'completed_today' => Todo::where('business_id', $business->id)
                ->where('status', 'completed')
                ->whereDate('completed_at', $today)
                ->count(),
        ];

        // Get team members for assigning
        $teamMembers = User::whereHas('teamBusinesses', function ($q) use ($business) {
            $q->where('businesses.id', $business->id);
        })->select('id', 'name', 'email')->get();

        return inertia($this->getViewPrefix().'/Todos/Index', [
            'todos' => $groupedTodos,
            'stats' => $stats,
            'teamMembers' => $teamMembers,
            'templates' => [],
            'types' => $this->getTodoTypes(),
            'priorities' => $this->getTodoPriorities(),
            'statuses' => $this->getTodoStatuses(),
            'filter' => $filter,
            'statusFilter' => $statusFilter,
        ]);
    }

    protected function formatTodo($todo): array
    {
        $typeLabels = [
            'personal' => 'Shaxsiy',
            'team' => 'Jamoa',
            'process' => 'Jarayon',
        ];

        $priorityLabels = [
            'urgent' => 'Shoshilinch',
            'high' => 'Yuqori',
            'medium' => 'O\'rta',
            'low' => 'Past',
        ];

        // Calculate subtasks progress
        $subtasksCount = $todo->subtasks->count();
        $completedSubtasksCount = $todo->subtasks->where('is_completed', true)->count();
        $progress = $subtasksCount > 0 ? round(($completedSubtasksCount / $subtasksCount) * 100) : 0;

        // Calculate team progress for team todos
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
            'type_label' => $typeLabels[$todo->type ?? 'personal'] ?? 'Shaxsiy',
            'priority' => $todo->priority ?? 'medium',
            'priority_label' => $priorityLabels[$todo->priority ?? 'medium'] ?? 'O\'rta',
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

    protected function getTodoTypes(): array
    {
        return [
            'personal' => 'Shaxsiy',
            'team' => 'Jamoa',
            'process' => 'Jarayon',
        ];
    }

    protected function getTodoPriorities(): array
    {
        return [
            'urgent' => 'Shoshilinch',
            'high' => 'Yuqori',
            'medium' => 'O\'rta',
            'low' => 'Past',
        ];
    }

    protected function getTodoStatuses(): array
    {
        return [
            'pending' => 'Kutilmoqda',
            'in_progress' => 'Jarayonda',
            'completed' => 'Bajarildi',
        ];
    }

    public function show($id)
    {
        $todo = Todo::with(['subtasks', 'assignees.user', 'assignee', 'comments.user'])
            ->findOrFail($id);

        return response()->json([
            'todo' => $this->formatTodo($todo),
        ]);
    }

    public function toggle($id)
    {
        $todo = Todo::findOrFail($id);
        $newStatus = $todo->status === 'completed' ? 'pending' : 'completed';
        $todo->update([
            'status' => $newStatus,
            'completed_at' => $newStatus === 'completed' ? now() : null,
        ]);

        if (request()->wantsJson()) {
            return response()->json([
                'todo' => $this->formatTodo($todo->fresh(['subtasks', 'assignees.user', 'assignee'])),
            ]);
        }

        return redirect()->route($this->getRoutePrefix().'.todos.index');
    }

    public function store(Request $request)
    {
        $business = $this->getCurrentBusiness();

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'type' => 'nullable|in:personal,team,process',
            'due_date' => 'nullable|date',
            'due_time' => 'nullable|string',
            'priority' => 'nullable|in:urgent,high,medium,low',
            'assignee_ids' => 'nullable|array',
            'assignee_ids.*' => 'exists:users,id',
        ]);

        $todo = Todo::create([
            'business_id' => $business->id,
            'user_id' => Auth::id(),
            'title' => $validated['title'],
            'description' => $validated['description'] ?? null,
            'type' => $validated['type'] ?? 'personal',
            'due_date' => $validated['due_date'] ?? null,
            'due_time' => $validated['due_time'] ?? null,
            'priority' => $validated['priority'] ?? 'medium',
            'status' => 'pending',
        ]);

        // Attach assignees for team todos
        if (isset($validated['assignee_ids']) && $validated['type'] === 'team') {
            foreach ($validated['assignee_ids'] as $userId) {
                $todo->assignees()->create([
                    'user_id' => $userId,
                    'is_completed' => false,
                ]);
            }
        }

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'todo' => $this->formatTodo($todo->fresh(['subtasks', 'assignees.user', 'assignee'])),
            ]);
        }

        return redirect()->route($this->getRoutePrefix().'.todos.index')
            ->with('success', 'Vazifa yaratildi');
    }

    public function update(Request $request, $id)
    {
        $todo = Todo::findOrFail($id);

        $validated = $request->validate([
            'title' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'type' => 'nullable|in:personal,team,process',
            'due_date' => 'nullable|date',
            'due_time' => 'nullable|string',
            'priority' => 'nullable|in:urgent,high,medium,low',
            'status' => 'nullable|in:pending,in_progress,completed',
        ]);

        $todo->update($validated);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'todo' => $this->formatTodo($todo->fresh(['subtasks', 'assignees.user', 'assignee'])),
            ]);
        }

        return redirect()->route($this->getRoutePrefix().'.todos.index')
            ->with('success', 'Vazifa yangilandi');
    }

    public function destroy($id)
    {
        $todo = Todo::findOrFail($id);
        $todo->delete();

        if (request()->wantsJson()) {
            return response()->json(['success' => true]);
        }

        return redirect()->route($this->getRoutePrefix().'.todos.index')
            ->with('success', 'Vazifa o\'chirildi');
    }

    public function storeSubtask(Request $request, $todoId)
    {
        $todo = Todo::findOrFail($todoId);

        $validated = $request->validate([
            'title' => 'required|string|max:255',
        ]);

        $subtask = $todo->subtasks()->create([
            'title' => $validated['title'],
            'is_completed' => false,
        ]);

        return response()->json([
            'success' => true,
            'subtask' => [
                'id' => $subtask->id,
                'title' => $subtask->title,
                'is_completed' => $subtask->is_completed,
            ],
        ]);
    }

    public function toggleSubtask($todoId, $subtaskId)
    {
        $todo = Todo::findOrFail($todoId);
        $subtask = $todo->subtasks()->findOrFail($subtaskId);
        $subtask->update(['is_completed' => ! $subtask->is_completed]);

        return response()->json([
            'success' => true,
            'subtask' => [
                'id' => $subtask->id,
                'title' => $subtask->title,
                'is_completed' => $subtask->is_completed,
            ],
        ]);
    }
}
