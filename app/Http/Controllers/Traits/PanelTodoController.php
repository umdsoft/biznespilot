<?php

namespace App\Http\Controllers\Traits;

use App\Models\Todo;
use App\Models\TodoTemplate;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

trait PanelTodoController
{
    use HasCurrentBusiness;

    abstract protected function getViewPrefix(): string;

    abstract protected function getRoutePrefix(): string;

    /**
     * Base query for todos — override for panel-specific scoping
     */
    protected function getBaseQuery($business)
    {
        return Todo::where('business_id', $business->id)
            ->whereNull('parent_id');
    }

    public function index(Request $request)
    {
        $business = $this->getCurrentBusiness();

        if (! $business) {
            return Inertia::render($this->getViewPrefix().'/Todos/Index', [
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

        $filter = $request->get('filter', 'all');
        $statusFilter = $request->get('status', 'active');

        $query = $this->getBaseQuery($business)
            ->with(['subtasks', 'assignees.user', 'assignee', 'recurrence'])
            ->orderBy('order')
            ->orderBy('due_date');

        if ($filter !== 'all') {
            $query->where('type', $filter);
        }

        if ($statusFilter === 'active') {
            $query->whereIn('status', [Todo::STATUS_PENDING, Todo::STATUS_IN_PROGRESS]);
        } elseif ($statusFilter === 'completed') {
            $query->where('status', Todo::STATUS_COMPLETED);
        }

        $allTodos = $query->get();
        $grouped = $this->groupTodosByPeriod($allTodos);
        $stats = $this->getTodoStats($business);

        $teamMembers = $business->teamMembers()
            ->get()
            ->map(fn ($user) => [
                'id' => $user->id,
                'name' => $user->name,
                'role' => $user->pivot->role ?? 'member',
            ]);

        $templates = TodoTemplate::where('business_id', $business->id)
            ->active()
            ->withCount('items')
            ->get();

        return Inertia::render($this->getViewPrefix().'/Todos/Index', [
            'todos' => $grouped,
            'stats' => $stats,
            'teamMembers' => $teamMembers,
            'templates' => $templates,
            'types' => $this->getTodoTypes(),
            'priorities' => $this->getTodoPriorities(),
            'statuses' => $this->getTodoStatuses(),
            'filter' => $filter,
            'statusFilter' => $statusFilter,
        ]);
    }

    /**
     * Group todos by time period — override for custom grouping
     */
    protected function groupTodosByPeriod($todos): array
    {
        $now = now();
        $grouped = [
            'overdue' => [],
            'today' => [],
            'tomorrow' => [],
            'this_week' => [],
            'later' => [],
        ];

        foreach ($todos as $todo) {
            $todoData = $this->formatTodo($todo);

            if (! $todo->due_date) {
                $grouped['later'][] = $todoData;

                continue;
            }

            $dueDate = $todo->due_date->startOfDay();

            if ($dueDate < $now->copy()->startOfDay()) {
                $grouped['overdue'][] = $todoData;
            } elseif ($dueDate->isSameDay($now)) {
                $grouped['today'][] = $todoData;
            } elseif ($dueDate->isSameDay($now->copy()->addDay())) {
                $grouped['tomorrow'][] = $todoData;
            } elseif ($dueDate <= $now->copy()->endOfWeek()) {
                $grouped['this_week'][] = $todoData;
            } else {
                $grouped['later'][] = $todoData;
            }
        }

        return $grouped;
    }

    /**
     * Calculate todo stats — override for panel-specific stats
     */
    protected function getTodoStats($business): array
    {
        $baseQuery = fn () => $this->getBaseQuery($business);

        return [
            'total' => $baseQuery()
                ->whereIn('status', [Todo::STATUS_PENDING, Todo::STATUS_IN_PROGRESS])
                ->count(),
            'overdue' => $baseQuery()
                ->whereIn('status', [Todo::STATUS_PENDING, Todo::STATUS_IN_PROGRESS])
                ->where('due_date', '<', now()->startOfDay())
                ->count(),
            'completed_today' => $baseQuery()
                ->where('status', Todo::STATUS_COMPLETED)
                ->whereDate('completed_at', today())
                ->count(),
        ];
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

        $subtasksCount = $todo->subtasks->count();
        $completedSubtasksCount = $todo->subtasks->where('is_completed', true)->count();
        $progress = $subtasksCount > 0 ? round(($completedSubtasksCount / $subtasksCount) * 100) : 0;

        $teamProgress = 0;
        $assigneesCount = 0;
        $completedAssigneesCount = 0;
        if ($todo->type === 'team' && $todo->assignees) {
            $assigneesCount = $todo->assignees->count();
            $completedAssigneesCount = $todo->assignees->where('is_completed', true)->count();
            $teamProgress = $assigneesCount > 0 ? round(($completedAssigneesCount / $assigneesCount) * 100) : 0;
        }

        $dueDateFormatted = null;
        $isOverdue = false;
        if ($todo->due_date) {
            $isOverdue = $todo->due_date->lt(Carbon::today()) && $todo->status !== Todo::STATUS_COMPLETED;
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
        $business = $this->getCurrentBusiness();
        $todo = Todo::with(['subtasks', 'assignees.user', 'assignee', 'recurrence'])
            ->findOrFail($id);

        if ($business && $todo->business_id !== $business->id) {
            return response()->json(['error' => 'Ruxsat yo\'q'], 403);
        }

        return response()->json([
            'success' => true,
            'todo' => $this->formatTodo($todo),
        ]);
    }

    public function store(Request $request)
    {
        $business = $this->getCurrentBusiness();

        if (! $business) {
            return back()->with('error', 'Biznes topilmadi');
        }

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'type' => 'nullable|in:personal,team,process',
            'priority' => 'nullable|in:urgent,high,medium,low',
            'due_date' => 'nullable|date',
            'due_time' => 'nullable|string',
            'assigned_to' => 'nullable|exists:users,id',
            'assignee_ids' => 'nullable|array',
            'assignee_ids.*' => 'exists:users,id',
            'subtasks' => 'nullable|array',
            'subtasks.*.title' => 'required|string|max:255',
            'tags' => 'nullable|array',
        ]);

        $todo = Todo::create([
            'business_id' => $business->id,
            'user_id' => Auth::id(),
            'created_by' => Auth::id(),
            'assigned_to' => $validated['assigned_to'] ?? Auth::id(),
            'title' => $validated['title'],
            'description' => $validated['description'] ?? null,
            'type' => $validated['type'] ?? 'personal',
            'priority' => $validated['priority'] ?? 'medium',
            'due_date' => $validated['due_date'] ?? null,
            'due_time' => $validated['due_time'] ?? null,
            'tags' => $validated['tags'] ?? [],
            'status' => Todo::STATUS_PENDING,
            'order' => Todo::where('business_id', $business->id)->max('order') + 1,
        ]);

        if (! empty($validated['subtasks'])) {
            foreach ($validated['subtasks'] as $subtaskData) {
                $todo->subtasks()->create([
                    'title' => $subtaskData['title'],
                    'is_completed' => false,
                ]);
            }
        }

        if (isset($validated['assignee_ids']) && ($validated['type'] ?? 'personal') === 'team') {
            foreach ($validated['assignee_ids'] as $userId) {
                $todo->assignees()->create([
                    'user_id' => $userId,
                    'is_completed' => false,
                ]);
            }
        }

        $todo->load(['subtasks', 'assignees.user', 'assignee']);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'todo' => $this->formatTodo($todo),
            ]);
        }

        return redirect()->route($this->getRoutePrefix().'.todos.index')
            ->with('success', 'Vazifa yaratildi');
    }

    public function update(Request $request, $id)
    {
        $business = $this->getCurrentBusiness();
        $todo = Todo::findOrFail($id);

        if ($business && $todo->business_id !== $business->id) {
            return response()->json(['error' => 'Ruxsat yo\'q'], 403);
        }

        $validated = $request->validate([
            'title' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'type' => 'sometimes|in:personal,team,process',
            'priority' => 'nullable|in:urgent,high,medium,low',
            'status' => 'sometimes|in:pending,in_progress,completed,cancelled',
            'due_date' => 'nullable|date',
            'due_time' => 'nullable|string',
            'assigned_to' => 'nullable|exists:users,id',
            'assignee_ids' => 'nullable|array',
            'assignee_ids.*' => 'exists:users,id',
            'tags' => 'nullable|array',
        ]);

        if (isset($validated['status'])) {
            if ($validated['status'] === Todo::STATUS_COMPLETED && $todo->status !== Todo::STATUS_COMPLETED) {
                $validated['completed_at'] = now();
            } elseif ($validated['status'] !== Todo::STATUS_COMPLETED) {
                $validated['completed_at'] = null;
            }
        }

        if (array_key_exists('assignee_ids', $validated)) {
            $todo->assignees()->delete();
            if (! empty($validated['assignee_ids'])) {
                foreach ($validated['assignee_ids'] as $userId) {
                    $todo->assignees()->create([
                        'user_id' => $userId,
                        'is_completed' => false,
                    ]);
                }
            }
            unset($validated['assignee_ids']);
        }

        $todo->update($validated);
        $todo->load(['subtasks', 'assignees.user', 'assignee', 'recurrence']);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'todo' => $this->formatTodo($todo),
            ]);
        }

        return redirect()->route($this->getRoutePrefix().'.todos.index')
            ->with('success', 'Vazifa yangilandi');
    }

    public function destroy($id)
    {
        $business = $this->getCurrentBusiness();
        $todo = Todo::findOrFail($id);

        if ($business && $todo->business_id !== $business->id) {
            return response()->json(['error' => 'Ruxsat yo\'q'], 403);
        }

        $todo->delete();

        if (request()->wantsJson()) {
            return response()->json(['success' => true]);
        }

        return redirect()->route($this->getRoutePrefix().'.todos.index')
            ->with('success', 'Vazifa o\'chirildi');
    }

    public function toggleComplete($id)
    {
        $business = $this->getCurrentBusiness();
        $todo = Todo::findOrFail($id);

        if ($business && $todo->business_id !== $business->id) {
            return response()->json(['error' => 'Ruxsat yo\'q'], 403);
        }

        if ($todo->is_team_task) {
            $todo->toggleUserCompletion();
        } else {
            if ($todo->status === Todo::STATUS_COMPLETED) {
                $todo->update([
                    'status' => Todo::STATUS_PENDING,
                    'completed_at' => null,
                ]);
            } else {
                $todo->update([
                    'status' => Todo::STATUS_COMPLETED,
                    'completed_at' => now(),
                ]);
            }
        }

        $todo->load(['subtasks', 'assignees.user', 'assignee', 'recurrence']);
        $todo->refresh();

        if (request()->wantsJson()) {
            return response()->json([
                'success' => true,
                'todo' => $this->formatTodo($todo),
            ]);
        }

        return redirect()->route($this->getRoutePrefix().'.todos.index')
            ->with('success', 'Vazifa holati o\'zgartirildi');
    }

    /**
     * Backward compat alias — Marketing routes use 'toggle'
     */
    public function toggle($id)
    {
        return $this->toggleComplete($id);
    }

    public function toggleUserComplete(Request $request, $id)
    {
        $business = $this->getCurrentBusiness();
        $todo = Todo::findOrFail($id);

        if ($business && $todo->business_id !== $business->id) {
            return response()->json(['error' => 'Ruxsat yo\'q'], 403);
        }

        $assignee = $todo->assignees()->where('user_id', Auth::id())->first();

        if ($assignee) {
            $assignee->update([
                'completed' => ! $assignee->completed,
                'completed_at' => ! $assignee->completed ? now() : null,
            ]);

            $allCompleted = $todo->assignees()->where('completed', false)->count() === 0;
            if ($allCompleted && $todo->assignees()->count() > 0) {
                $todo->update([
                    'status' => Todo::STATUS_COMPLETED,
                    'completed_at' => now(),
                ]);
            }
        }

        $todo->load(['subtasks', 'assignees.user', 'assignee']);

        if (request()->wantsJson()) {
            return response()->json([
                'success' => true,
                'todo' => $this->formatTodo($todo),
            ]);
        }

        return back()->with('success', 'Vazifa holati o\'zgartirildi');
    }

    public function reorder(Request $request)
    {
        $validated = $request->validate([
            'todos' => 'required|array',
            'todos.*.id' => 'required|exists:todos,id',
            'todos.*.order' => 'required|integer',
        ]);

        foreach ($validated['todos'] as $item) {
            Todo::where('id', $item['id'])->update(['order' => $item['order']]);
        }

        return response()->json(['success' => true]);
    }

    public function addSubtask(Request $request, $todoId)
    {
        $business = $this->getCurrentBusiness();
        $todo = Todo::findOrFail($todoId);

        if ($business && $todo->business_id !== $business->id) {
            return response()->json(['error' => 'Ruxsat yo\'q'], 403);
        }

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
                'is_completed' => $subtask->is_completed ?? false,
            ],
        ]);
    }

    /**
     * Backward compat alias — Marketing routes use 'storeSubtask'
     */
    public function storeSubtask(Request $request, $todoId)
    {
        return $this->addSubtask($request, $todoId);
    }

    public function toggleSubtask($todoId, $subtaskId)
    {
        $business = $this->getCurrentBusiness();
        $todo = Todo::findOrFail($todoId);

        if ($business && $todo->business_id !== $business->id) {
            return response()->json(['error' => 'Ruxsat yo\'q'], 403);
        }

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
