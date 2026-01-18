<?php

namespace App\Http\Controllers\SalesHead;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Traits\HasCurrentBusiness;
use App\Models\Todo;
use App\Models\TodoTemplate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class TodoController extends Controller
{
    use HasCurrentBusiness;

    /**
     * Display todos index page
     */
    public function index(Request $request)
    {
        $business = $this->getCurrentBusiness();

        if (! $business) {
            return redirect()->route('login');
        }

        $filter = $request->get('filter', 'all');
        $status = $request->get('status', 'active');

        $query = Todo::where('business_id', $business->id)
            ->whereNull('parent_id')
            ->with(['assignee:id,name', 'subtasks', 'recurrence', 'assignees.user:id,name'])
            ->orderBy('order')
            ->orderBy('due_date');

        // Type filter
        if ($filter !== 'all') {
            $query->where('type', $filter);
        }

        // Status filter
        if ($status === 'active') {
            $query->whereIn('status', [Todo::STATUS_PENDING, Todo::STATUS_IN_PROGRESS]);
        } elseif ($status === 'completed') {
            $query->where('status', Todo::STATUS_COMPLETED);
        }

        $allTodos = $query->get();

        // Group by time period
        $grouped = $this->groupTodosByPeriod($allTodos);

        // Stats
        $stats = $this->getTodoStats($business->id);

        // Team members for assignment
        $teamMembers = $business->teamMembers()
            ->get()
            ->map(fn ($user) => [
                'id' => $user->id,
                'name' => $user->name,
                'role' => $user->pivot->role ?? 'member',
            ]);

        // Active templates
        $templates = TodoTemplate::where('business_id', $business->id)
            ->active()
            ->withCount('items')
            ->get();

        return Inertia::render('SalesHead/Todos/Index', [
            'todos' => $grouped,
            'stats' => $stats,
            'teamMembers' => $teamMembers,
            'templates' => $templates,
            'types' => Todo::TYPES,
            'priorities' => Todo::PRIORITIES,
            'statuses' => Todo::STATUSES,
            'filter' => $filter,
            'statusFilter' => $status,
        ]);
    }

    /**
     * Group todos by time period
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
            'no_date' => [],
        ];

        foreach ($todos as $todo) {
            $todoData = $this->formatTodoForResponse($todo);

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

    /**
     * Format todo for API response
     */
    protected function formatTodoForResponse(Todo $todo): array
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

    /**
     * Get todo statistics
     */
    protected function getTodoStats(string $businessId): array
    {
        $userId = Auth::id();

        return [
            'total' => Todo::where('business_id', $businessId)
                ->whereNull('parent_id')
                ->whereIn('status', [Todo::STATUS_PENDING, Todo::STATUS_IN_PROGRESS])
                ->count(),
            'overdue' => Todo::where('business_id', $businessId)
                ->whereNull('parent_id')
                ->overdue()
                ->count(),
            'today' => Todo::where('business_id', $businessId)
                ->whereNull('parent_id')
                ->today()
                ->pending()
                ->count(),
            'completed_today' => Todo::where('business_id', $businessId)
                ->whereNull('parent_id')
                ->where('status', Todo::STATUS_COMPLETED)
                ->whereDate('completed_at', today())
                ->count(),
            'my_todos' => Todo::where('business_id', $businessId)
                ->whereNull('parent_id')
                ->myTodos($userId)
                ->whereIn('status', [Todo::STATUS_PENDING, Todo::STATUS_IN_PROGRESS])
                ->count(),
        ];
    }

    /**
     * Store a new todo
     */
    public function store(Request $request)
    {
        $business = $this->getCurrentBusiness();

        if (! $business) {
            return response()->json(['error' => 'Biznes topilmadi'], 404);
        }

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'type' => 'required|in:personal,team,process',
            'priority' => 'required|in:low,medium,high,urgent',
            'due_date' => 'nullable|date',
            'reminder_at' => 'nullable|date',
            'assigned_to' => 'nullable|exists:users,id',
            'assignee_ids' => 'nullable|array',
            'assignee_ids.*' => 'exists:users,id',
            'subtasks' => 'nullable|array',
            'subtasks.*.title' => 'required|string|max:255',
        ]);

        DB::beginTransaction();

        try {
            $maxOrder = Todo::where('business_id', $business->id)
                ->whereNull('parent_id')
                ->max('order') ?? -1;

            $todo = Todo::create([
                'business_id' => $business->id,
                'created_by' => Auth::id(),
                'assigned_to' => $validated['assigned_to'] ?? Auth::id(),
                'title' => $validated['title'],
                'description' => $validated['description'] ?? null,
                'type' => $validated['type'],
                'priority' => $validated['priority'],
                'status' => Todo::STATUS_PENDING,
                'due_date' => $validated['due_date'] ?? null,
                'reminder_at' => $validated['reminder_at'] ?? null,
                'order' => $maxOrder + 1,
            ]);

            if (! empty($validated['subtasks'])) {
                foreach ($validated['subtasks'] as $subtaskData) {
                    $todo->addSubtask($subtaskData['title'], $subtaskData['description'] ?? null);
                }
            }

            if ($validated['type'] === Todo::TYPE_TEAM && ! empty($validated['assignee_ids'])) {
                $todo->syncAssignees($validated['assignee_ids']);
            }

            $todo->load(['assignee:id,name', 'subtasks', 'assignees.user:id,name']);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Vazifa yaratildi',
                'todo' => $this->formatTodoForResponse($todo),
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'error' => 'Xatolik: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Update a todo
     */
    public function update(Request $request, Todo $todo)
    {
        $business = $this->getCurrentBusiness();

        if (! $business || $todo->business_id !== $business->id) {
            return response()->json(['error' => 'Ruxsat yo\'q'], 403);
        }

        $validated = $request->validate([
            'title' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'type' => 'sometimes|in:personal,team,process',
            'priority' => 'sometimes|in:low,medium,high,urgent',
            'status' => 'sometimes|in:pending,in_progress,completed,cancelled',
            'due_date' => 'nullable|date',
            'reminder_at' => 'nullable|date',
            'assigned_to' => 'nullable|exists:users,id',
            'assignee_ids' => 'nullable|array',
            'assignee_ids.*' => 'exists:users,id',
        ]);

        if (isset($validated['status'])) {
            if ($validated['status'] === Todo::STATUS_COMPLETED && $todo->status !== Todo::STATUS_COMPLETED) {
                $validated['completed_at'] = now();
            } elseif ($validated['status'] !== Todo::STATUS_COMPLETED) {
                $validated['completed_at'] = null;
            }
        }

        if (array_key_exists('assignee_ids', $validated)) {
            if (! empty($validated['assignee_ids']) && ($validated['type'] ?? $todo->type) === Todo::TYPE_TEAM) {
                $todo->syncAssignees($validated['assignee_ids']);
            } else {
                $todo->assignees()->delete();
                $todo->updateAssigneeCounts();
            }
            unset($validated['assignee_ids']);
        }

        $todo->update($validated);
        $todo->load(['assignee:id,name', 'subtasks', 'recurrence', 'assignees.user:id,name']);

        return response()->json([
            'success' => true,
            'message' => 'Vazifa yangilandi',
            'todo' => $this->formatTodoForResponse($todo),
        ]);
    }

    /**
     * Toggle todo completion
     */
    public function toggleComplete(Todo $todo)
    {
        $business = $this->getCurrentBusiness();

        if (! $business || $todo->business_id !== $business->id) {
            return response()->json(['error' => 'Ruxsat yo\'q'], 403);
        }

        if ($todo->is_team_task) {
            $todo->toggleUserCompletion();
        } else {
            $todo->toggleComplete();
        }

        $todo->load(['assignee:id,name', 'subtasks', 'recurrence', 'assignees.user:id,name']);
        $todo->refresh();

        return response()->json([
            'success' => true,
            'message' => $todo->status === Todo::STATUS_COMPLETED ? 'Vazifa bajarildi' : 'Vazifa qayta ochildi',
            'todo' => $this->formatTodoForResponse($todo),
        ]);
    }

    /**
     * Get single todo details
     */
    public function show(Todo $todo)
    {
        $business = $this->getCurrentBusiness();

        if (! $business || $todo->business_id !== $business->id) {
            return response()->json(['error' => 'Ruxsat yo\'q'], 403);
        }

        $todo->load(['assignee:id,name', 'subtasks', 'recurrence', 'assignees.user:id,name', 'creator:id,name']);

        return response()->json([
            'success' => true,
            'todo' => array_merge($this->formatTodoForResponse($todo), [
                'creator' => $todo->creator ? [
                    'id' => $todo->creator->id,
                    'name' => $todo->creator->name,
                ] : null,
            ]),
        ]);
    }

    /**
     * Delete a todo
     */
    public function destroy(Todo $todo)
    {
        $business = $this->getCurrentBusiness();

        if (! $business || $todo->business_id !== $business->id) {
            return response()->json(['error' => 'Ruxsat yo\'q'], 403);
        }

        $todo->delete();

        return response()->json([
            'success' => true,
            'message' => 'Vazifa o\'chirildi',
        ]);
    }

    /**
     * Add subtask to todo
     */
    public function addSubtask(Request $request, Todo $todo)
    {
        $business = $this->getCurrentBusiness();

        if (! $business || $todo->business_id !== $business->id) {
            return response()->json(['error' => 'Ruxsat yo\'q'], 403);
        }

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
        ]);

        $subtask = $todo->addSubtask($validated['title'], $validated['description'] ?? null);

        return response()->json([
            'success' => true,
            'message' => 'Sub-task qo\'shildi',
            'subtask' => [
                'id' => $subtask->id,
                'title' => $subtask->title,
                'status' => $subtask->status,
                'is_completed' => $subtask->status === Todo::STATUS_COMPLETED,
                'order' => $subtask->order,
            ],
        ]);
    }

    /**
     * Toggle subtask completion
     */
    public function toggleSubtask(Todo $todo, Todo $subtask)
    {
        $business = $this->getCurrentBusiness();

        if (! $business || $todo->business_id !== $business->id || $subtask->parent_id !== $todo->id) {
            return response()->json(['error' => 'Ruxsat yo\'q'], 403);
        }

        $subtask->toggleComplete();

        return response()->json([
            'success' => true,
            'message' => $subtask->status === Todo::STATUS_COMPLETED ? 'Sub-task bajarildi' : 'Sub-task qayta ochildi',
            'subtask' => [
                'id' => $subtask->id,
                'title' => $subtask->title,
                'status' => $subtask->status,
                'is_completed' => $subtask->status === Todo::STATUS_COMPLETED,
                'order' => $subtask->order,
            ],
        ]);
    }

    /**
     * Get todos for dashboard widget
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
