<?php

namespace App\Http\Controllers\Traits;

use App\Models\Lead;
use App\Models\Task;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

trait PanelTaskController
{
    use HasCurrentBusiness;

    abstract protected function getViewPrefix(): string;

    abstract protected function getRoutePrefix(): string;

    public function index(Request $request)
    {
        $business = $this->getCurrentBusiness();

        if (! $business) {
            return inertia($this->getViewPrefix().'/Tasks/Index', [
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

        // Group tasks by due date category
        $groupedTasks = [
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
                $groupedTasks['later'][] = $taskData;
            } elseif ($task->due_date->lt($today)) {
                $groupedTasks['overdue'][] = $taskData;
            } elseif ($task->due_date->isSameDay($today)) {
                $groupedTasks['today'][] = $taskData;
            } elseif ($task->due_date->isSameDay($tomorrow)) {
                $groupedTasks['tomorrow'][] = $taskData;
            } elseif ($task->due_date->lte($weekEnd)) {
                $groupedTasks['this_week'][] = $taskData;
            } else {
                $groupedTasks['later'][] = $taskData;
            }
        }

        foreach ($completedTasks as $task) {
            $groupedTasks['completed'][] = $this->formatTask($task);
        }

        // Calculate stats
        $stats = [
            'total' => $allTasks->count(),
            'overdue' => count($groupedTasks['overdue']),
            'today' => count($groupedTasks['today']),
            'tomorrow' => count($groupedTasks['tomorrow']),
            'this_week' => count($groupedTasks['this_week']),
            'completed' => $completedTasks->count(),
        ];

        // Get leads for task creation
        $leads = Lead::where('business_id', $business->id)
            ->whereNotIn('status', ['won', 'lost'])
            ->orderBy('name')
            ->get()
            ->map(fn ($lead) => [
                'id' => $lead->id,
                'name' => $lead->name,
                'phone' => $lead->phone,
            ]);

        return inertia($this->getViewPrefix().'/Tasks/Index', [
            'tasks' => $groupedTasks,
            'stats' => $stats,
            'leads' => $leads,
            'types' => $this->getTaskTypes(),
            'priorities' => $this->getTaskPriorities(),
            'statuses' => $this->getTaskStatuses(),
        ]);
    }

    protected function formatTask($task): array
    {
        $typeLabels = [
            'call' => 'Qo\'ng\'iroq',
            'meeting' => 'Uchrashuv',
            'task' => 'Vazifa',
            'follow_up' => 'Qayta aloqa',
            'email' => 'Email',
            'other' => 'Boshqa',
        ];

        $priorityLabels = [
            'urgent' => 'Shoshilinch',
            'high' => 'Yuqori',
            'medium' => 'O\'rta',
            'low' => 'Past',
        ];

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
            'type_label' => $typeLabels[$task->type ?? 'task'] ?? 'Vazifa',
            'priority' => $task->priority ?? 'medium',
            'priority_label' => $priorityLabels[$task->priority ?? 'medium'] ?? 'O\'rta',
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

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'due_date' => 'nullable|date',
            'priority' => 'required|in:low,medium,high,urgent',
            'assigned_to' => 'nullable|exists:users,id',
            'lead_id' => 'nullable|exists:leads,id',
            'type' => 'nullable|in:call,meeting,email,task,follow_up,other',
        ]);

        Task::create([
            'business_id' => $business->id,
            'user_id' => Auth::id(),
            'title' => $validated['title'],
            'description' => $validated['description'] ?? null,
            'due_date' => $validated['due_date'] ?? now(),
            'priority' => $validated['priority'],
            'assigned_to' => $validated['assigned_to'] ?? null,
            'lead_id' => $validated['lead_id'] ?? null,
            'type' => $validated['type'] ?? 'task',
            'status' => 'pending',
        ]);

        return redirect()->route($this->getRoutePrefix().'.tasks.index')
            ->with('success', 'Vazifa yaratildi');
    }

    public function update(Request $request, $id)
    {
        $task = Task::findOrFail($id);

        $validated = $request->validate([
            'title' => 'sometimes|string|max:255',
            'status' => 'sometimes|in:pending,in_progress,completed',
            'priority' => 'sometimes|in:low,medium,high',
        ]);

        $task->update($validated);

        return redirect()->route($this->getRoutePrefix().'.tasks.index')
            ->with('success', 'Vazifa yangilandi');
    }

    public function complete($id)
    {
        $task = Task::findOrFail($id);
        $task->update(['status' => 'completed', 'completed_at' => now()]);

        return redirect()->route($this->getRoutePrefix().'.tasks.index')
            ->with('success', 'Vazifa bajarildi');
    }

    public function destroy($id)
    {
        Task::findOrFail($id)->delete();

        return redirect()->route($this->getRoutePrefix().'.tasks.index')
            ->with('success', 'Vazifa o\'chirildi');
    }
}
