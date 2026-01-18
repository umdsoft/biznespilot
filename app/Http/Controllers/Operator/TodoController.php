<?php

namespace App\Http\Controllers\Operator;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Traits\HasCurrentBusiness;
use App\Models\Todo;
use App\Models\TodoTemplate;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
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
            return redirect()->route('operator.dashboard')
                ->with('error', 'Avval biznes tanlang');
        }

        $filter = $request->get('filter', 'all');
        $status = $request->get('status', 'active');

        $query = Todo::where('business_id', $business->id)
            ->whereNull('parent_id')
            ->where(function ($q) {
                $q->where('assigned_to', Auth::id())
                    ->orWhereHas('assignees', function ($q) {
                        $q->where('user_id', Auth::id());
                    });
            })
            ->with(['assignee:id,name', 'subtasks', 'recurrence', 'assignees.user:id,name'])
            ->orderBy('order')
            ->orderBy('due_date');

        if ($filter !== 'all') {
            $query->where('type', $filter);
        }

        if ($status === 'active') {
            $query->whereIn('status', [Todo::STATUS_PENDING, Todo::STATUS_IN_PROGRESS]);
        } elseif ($status === 'completed') {
            $query->where('status', Todo::STATUS_COMPLETED);
        }

        $allTodos = $query->get();
        $grouped = $this->groupTodosByPeriod($allTodos);
        $stats = $this->getTodoStats($business->id);

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

        return Inertia::render('Operator/Todos/Index', [
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
        ];

        foreach ($todos as $todo) {
            if (! $todo->due_date) {
                $grouped['later'][] = $todo;

                continue;
            }

            $dueDate = $todo->due_date->startOfDay();

            if ($dueDate < $now->copy()->startOfDay()) {
                $grouped['overdue'][] = $todo;
            } elseif ($dueDate->isSameDay($now)) {
                $grouped['today'][] = $todo;
            } elseif ($dueDate->isSameDay($now->copy()->addDay())) {
                $grouped['tomorrow'][] = $todo;
            } elseif ($dueDate <= $now->copy()->endOfWeek()) {
                $grouped['this_week'][] = $todo;
            } else {
                $grouped['later'][] = $todo;
            }
        }

        return $grouped;
    }

    /**
     * Get todo stats
     */
    protected function getTodoStats($businessId): array
    {
        $userId = Auth::id();

        return [
            'total' => Todo::where('business_id', $businessId)
                ->whereNull('parent_id')
                ->where(function ($q) use ($userId) {
                    $q->where('assigned_to', $userId)
                        ->orWhereHas('assignees', fn ($q) => $q->where('user_id', $userId));
                })
                ->whereIn('status', [Todo::STATUS_PENDING, Todo::STATUS_IN_PROGRESS])
                ->count(),
            'overdue' => Todo::where('business_id', $businessId)
                ->whereNull('parent_id')
                ->where(function ($q) use ($userId) {
                    $q->where('assigned_to', $userId)
                        ->orWhereHas('assignees', fn ($q) => $q->where('user_id', $userId));
                })
                ->whereIn('status', [Todo::STATUS_PENDING, Todo::STATUS_IN_PROGRESS])
                ->where('due_date', '<', now()->startOfDay())
                ->count(),
            'completed_today' => Todo::where('business_id', $businessId)
                ->whereNull('parent_id')
                ->where(function ($q) use ($userId) {
                    $q->where('assigned_to', $userId)
                        ->orWhereHas('assignees', fn ($q) => $q->where('user_id', $userId));
                })
                ->where('status', Todo::STATUS_COMPLETED)
                ->whereDate('completed_at', today())
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
            return back()->with('error', 'Biznes topilmadi');
        }

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'type' => 'required|string',
            'priority' => 'nullable|string',
            'due_date' => 'nullable|date',
            'due_time' => 'nullable|string',
            'assigned_to' => 'nullable|exists:users,id',
            'assignees' => 'nullable|array',
            'tags' => 'nullable|array',
        ]);

        $todo = Todo::create([
            'business_id' => $business->id,
            'user_id' => Auth::id(),
            'assigned_to' => $validated['assigned_to'] ?? Auth::id(),
            'title' => $validated['title'],
            'description' => $validated['description'] ?? null,
            'type' => $validated['type'],
            'priority' => $validated['priority'] ?? Todo::PRIORITY_MEDIUM,
            'due_date' => $validated['due_date'] ?? null,
            'due_time' => $validated['due_time'] ?? null,
            'tags' => $validated['tags'] ?? [],
            'status' => Todo::STATUS_PENDING,
            'order' => Todo::where('business_id', $business->id)->max('order') + 1,
        ]);

        if (! empty($validated['assignees'])) {
            foreach ($validated['assignees'] as $userId) {
                $todo->assignees()->create(['user_id' => $userId]);
            }
        }

        return back()->with('success', 'Vazifa qo\'shildi');
    }

    /**
     * Show a single todo
     */
    public function show(Todo $todo)
    {
        $todo->load(['assignee:id,name', 'subtasks', 'recurrence', 'assignees.user:id,name']);

        return response()->json($todo);
    }

    /**
     * Update a todo
     */
    public function update(Request $request, Todo $todo)
    {
        $validated = $request->validate([
            'title' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string',
            'type' => 'sometimes|required|string',
            'priority' => 'nullable|string',
            'status' => 'sometimes|required|string',
            'due_date' => 'nullable|date',
            'due_time' => 'nullable|string',
            'assigned_to' => 'nullable|exists:users,id',
            'assignees' => 'nullable|array',
            'tags' => 'nullable|array',
        ]);

        if (isset($validated['status']) && $validated['status'] === Todo::STATUS_COMPLETED) {
            $validated['completed_at'] = now();
        }

        $todo->update($validated);

        if (isset($validated['assignees'])) {
            $todo->assignees()->delete();
            foreach ($validated['assignees'] as $userId) {
                $todo->assignees()->create(['user_id' => $userId]);
            }
        }

        return back()->with('success', 'Vazifa yangilandi');
    }

    /**
     * Delete a todo
     */
    public function destroy(Todo $todo)
    {
        $todo->subtasks()->delete();
        $todo->recurrence()->delete();
        $todo->assignees()->delete();
        $todo->delete();

        return back()->with('success', 'Vazifa o\'chirildi');
    }

    /**
     * Toggle todo complete status
     */
    public function toggleComplete(Request $request, Todo $todo)
    {
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

        return back()->with('success', 'Vazifa holati o\'zgartirildi');
    }

    /**
     * Toggle user completion for team todos
     */
    public function toggleUserComplete(Request $request, Todo $todo)
    {
        $assignee = $todo->assignees()->where('user_id', Auth::id())->first();

        if ($assignee) {
            $assignee->update([
                'completed' => ! $assignee->completed,
                'completed_at' => ! $assignee->completed ? now() : null,
            ]);

            // Check if all assignees completed
            $allCompleted = $todo->assignees()->where('completed', false)->count() === 0;
            if ($allCompleted && $todo->assignees()->count() > 0) {
                $todo->update([
                    'status' => Todo::STATUS_COMPLETED,
                    'completed_at' => now(),
                ]);
            }
        }

        return back()->with('success', 'Vazifa holati o\'zgartirildi');
    }

    /**
     * Reorder todos
     */
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
}
