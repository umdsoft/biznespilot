<?php

namespace App\Http\Controllers\Traits;

use App\Models\Lead;
use App\Models\Task;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

trait PanelTaskController
{
    use HasCurrentBusiness;

    abstract protected function getViewPrefix(): string;

    abstract protected function getRoutePrefix(): string;

    /**
     * Base query for tasks — override for panel-specific scoping
     */
    protected function getBaseTaskQuery($business)
    {
        return Task::where('business_id', $business->id);
    }

    /**
     * Leads query for task modal — override for panel-specific scoping
     */
    protected function getLeadsQuery($business)
    {
        return Lead::where('business_id', $business->id)
            ->whereNotIn('status', Lead::TERMINAL_STATUSES)
            ->select('id', 'name', 'phone')
            ->orderBy('name');
    }

    /**
     * Authorization check for task management — override for stricter access
     */
    protected function canManageTask(Task $task): bool
    {
        $business = $this->getCurrentBusiness();

        return $business && $task->business_id === $business->id;
    }

    public function index(Request $request)
    {
        $business = $this->getCurrentBusiness();

        if (! $business) {
            return Inertia::render($this->getViewPrefix().'/Tasks/Index', [
                'tasks' => [
                    'overdue' => [],
                    'today' => [],
                    'tomorrow' => [],
                    'this_week' => [],
                    'later' => [],
                    'completed' => [],
                ],
                'stats' => [
                    'total' => 0,
                    'overdue' => 0,
                    'today' => 0,
                    'tomorrow' => 0,
                    'this_week' => 0,
                    'completed' => 0,
                ],
                'leads' => [],
                'types' => $this->getTaskTypes(),
                'priorities' => $this->getTaskPriorities(),
                'statuses' => $this->getTaskStatuses(),
            ]);
        }

        $now = now();
        $endOfWeek = $now->copy()->addWeek()->endOfDay();

        $allTasks = $this->getBaseTaskQuery($business)
            ->with(['lead:id,name,phone', 'assignedUser:id,name'])
            ->orderBy('due_date', 'asc')
            ->get();

        $grouped = $this->groupTasks($allTasks, $now, $endOfWeek);

        $stats = [
            'total' => count($grouped['overdue']) + count($grouped['today']) + count($grouped['tomorrow']) + count($grouped['this_week']) + count($grouped['later']),
            'overdue' => count($grouped['overdue']),
            'today' => count($grouped['today']),
            'tomorrow' => count($grouped['tomorrow']),
            'this_week' => count($grouped['this_week']),
            'completed' => count($grouped['completed']),
        ];

        $leads = $this->getLeadsQuery($business)->get();

        return Inertia::render($this->getViewPrefix().'/Tasks/Index', [
            'tasks' => $grouped,
            'stats' => $stats,
            'leads' => $leads,
            'types' => $this->getTaskTypes(),
            'priorities' => $this->getTaskPriorities(),
            'statuses' => $this->getTaskStatuses(),
        ]);
    }

    /**
     * Group tasks by time period
     */
    protected function groupTasks($allTasks, $now, $endOfWeek): array
    {
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

            if ($task->status === 'completed') {
                $grouped['completed'][] = $taskData;
            } elseif ($task->due_date && $task->due_date->lt($now)) {
                $grouped['overdue'][] = $taskData;
            } elseif ($task->due_date && $task->due_date->isToday()) {
                $grouped['today'][] = $taskData;
            } elseif ($task->due_date && $task->due_date->isTomorrow()) {
                $grouped['tomorrow'][] = $taskData;
            } elseif ($task->due_date && $task->due_date->lte($endOfWeek)) {
                $grouped['this_week'][] = $taskData;
            } else {
                $grouped['later'][] = $taskData;
            }
        }

        return $grouped;
    }

    protected function formatTask($task): array
    {
        return [
            'id' => $task->id,
            'title' => $task->title,
            'description' => $task->description,
            'type' => $task->type,
            'type_label' => $task->type_label,
            'priority' => $task->priority,
            'priority_label' => $task->priority_label,
            'status' => $task->status,
            'status_label' => $task->status_label,
            'due_date' => $task->due_date?->format('Y-m-d H:i'),
            'due_date_human' => $task->due_date?->format('H:i') ?? '',
            'due_date_full' => $task->due_date?->format('d.m.Y H:i') ?? '',
            'is_overdue' => $task->status === 'pending' && $task->due_date && $task->due_date->lt(now()),
            'completed_at' => $task->completed_at?->format('Y-m-d H:i'),
            'result' => $task->result,
            'lead' => $task->lead,
            'assigned_user' => $task->assignedUser,
            'assignee' => $task->assignedUser ? [
                'id' => $task->assignedUser->id,
                'name' => $task->assignedUser->name,
            ] : null,
            'created_at' => $task->created_at->format('d.m.Y H:i'),
        ];
    }

    protected function getTaskTypes(): array
    {
        return [
            'call' => 'Qo\'ng\'iroq',
            'meeting' => 'Uchrashuv',
            'task' => 'Vazifa',
            'follow_up' => 'Qayta aloqa',
            'email' => 'Email',
            'other' => 'Boshqa',
        ];
    }

    protected function getTaskPriorities(): array
    {
        return [
            'urgent' => 'Shoshilinch',
            'high' => 'Yuqori',
            'medium' => 'O\'rta',
            'low' => 'Past',
        ];
    }

    protected function getTaskStatuses(): array
    {
        return [
            'pending' => 'Kutilmoqda',
            'in_progress' => 'Jarayonda',
            'completed' => 'Bajarildi',
        ];
    }

    public function store(Request $request)
    {
        $business = $this->getCurrentBusiness();

        if (! $business) {
            return response()->json(['error' => 'Biznes topilmadi'], 404);
        }

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'type' => 'nullable|in:call,meeting,email,task,follow_up,other',
            'priority' => 'required|in:low,normal,medium,high,urgent',
            'due_date' => 'nullable|date',
            'due_time' => 'nullable|string',
            'reminder_at' => 'nullable|date',
            'assigned_to' => 'nullable|exists:users,id',
            'lead_id' => 'nullable|exists:leads,id',
        ]);

        $dueDate = $validated['due_date'] ?? now()->toDateString();
        if (! empty($validated['due_time'])) {
            $dueDate .= ' '.$validated['due_time'];
        }

        $priority = $validated['priority'];
        if ($priority === 'normal') {
            $priority = 'medium';
        }

        $assignedTo = $validated['assigned_to'] ?? Auth::id();

        $task = Task::create([
            'business_id' => $business->id,
            'user_id' => Auth::id(),
            'assigned_to' => $assignedTo,
            'title' => $validated['title'],
            'description' => $validated['description'] ?? null,
            'type' => $validated['type'] ?? 'task',
            'priority' => $priority,
            'due_date' => $dueDate,
            'reminder_at' => $validated['reminder_at'] ?? null,
            'lead_id' => $validated['lead_id'] ?? null,
            'status' => 'pending',
        ]);

        $task->load(['lead:id,name,phone', 'assignedUser:id,name']);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Vazifa yaratildi',
                'task' => $task,
            ]);
        }

        return redirect()->route($this->getRoutePrefix().'.tasks.index')
            ->with('success', 'Vazifa yaratildi');
    }

    public function update(Request $request, $id)
    {
        $task = Task::findOrFail($id);

        if (! $this->canManageTask($task)) {
            return response()->json(['error' => 'Ruxsat yo\'q'], 403);
        }

        $validated = $request->validate([
            'title' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'type' => 'sometimes|in:call,meeting,email,task,follow_up,other',
            'priority' => 'sometimes|in:low,normal,medium,high,urgent',
            'status' => 'sometimes|in:pending,in_progress,completed,cancelled',
            'due_date' => 'sometimes|date',
            'due_time' => 'nullable|string',
            'reminder_at' => 'nullable|date',
            'assigned_to' => 'nullable|exists:users,id',
            'result' => 'nullable|string',
        ]);

        if (isset($validated['due_date']) && ! empty($validated['due_time'])) {
            $validated['due_date'] .= ' '.$validated['due_time'];
        }
        unset($validated['due_time']);

        if (isset($validated['priority']) && $validated['priority'] === 'normal') {
            $validated['priority'] = 'medium';
        }

        if (isset($validated['status']) && $validated['status'] === 'completed' && $task->status !== 'completed') {
            $validated['completed_at'] = now();
        }

        $task->update($validated);
        $task->load(['lead:id,name,phone', 'assignedUser:id,name']);

        if (request()->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Vazifa yangilandi',
                'task' => $task,
            ]);
        }

        return redirect()->route($this->getRoutePrefix().'.tasks.index')
            ->with('success', 'Vazifa yangilandi');
    }

    public function complete(Request $request, $id)
    {
        $task = Task::findOrFail($id);

        if (! $this->canManageTask($task)) {
            return response()->json(['error' => 'Ruxsat yo\'q'], 403);
        }

        $result = $request->get('result');
        $task->markAsCompleted($result);

        if (request()->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Vazifa bajarildi',
                'task' => $task,
            ]);
        }

        return redirect()->route($this->getRoutePrefix().'.tasks.index')
            ->with('success', 'Vazifa bajarildi');
    }

    public function destroy($id)
    {
        $task = Task::findOrFail($id);

        if (! $this->canManageTask($task)) {
            return response()->json(['error' => 'Ruxsat yo\'q'], 403);
        }

        $task->delete();

        if (request()->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Vazifa o\'chirildi',
            ]);
        }

        return redirect()->route($this->getRoutePrefix().'.tasks.index')
            ->with('success', 'Vazifa o\'chirildi');
    }
}
