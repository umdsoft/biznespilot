<?php

namespace App\Http\Controllers\Operator;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Traits\HasCurrentBusiness;
use App\Models\Task;
use App\Models\Lead;
use App\Models\Business;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class TaskController extends Controller
{
    use HasCurrentBusiness;

    /**
     * Display tasks index - Kanban Board
     */
    public function index(Request $request)
    {
        $business = $this->getCurrentBusiness();
        $userId = Auth::id();

        if (!$business) {
            return redirect()->route('login');
        }

        $now = now();
        $endOfWeek = $now->copy()->addWeek()->endOfDay();

        // Get only tasks assigned to current operator
        $allTasks = Task::where('business_id', $business->id)
            ->where('assigned_to', $userId)
            ->with(['lead:id,name,phone', 'assignedUser:id,name'])
            ->orderBy('due_date', 'asc')
            ->get();

        // Group tasks
        $grouped = $this->groupTasks($allTasks, $now, $endOfWeek);

        // Stats
        $stats = [
            'total' => count($grouped['overdue']) + count($grouped['today']) + count($grouped['tomorrow']) + count($grouped['this_week']) + count($grouped['later']),
            'overdue' => count($grouped['overdue']),
            'today' => count($grouped['today']),
            'tomorrow' => count($grouped['tomorrow']),
            'this_week' => count($grouped['this_week']),
            'completed' => count($grouped['completed']),
        ];

        // Get leads assigned to this operator for task modal
        $leads = Lead::where('business_id', $business->id)
            ->where('assigned_to', $userId)
            ->select('id', 'name', 'phone')
            ->orderBy('name')
            ->get();

        return Inertia::render('Operator/Tasks/Index', [
            'tasks' => $grouped,
            'stats' => $stats,
            'leads' => $leads,
            'types' => Task::TYPES,
            'priorities' => Task::PRIORITIES,
            'statuses' => Task::STATUSES,
        ]);
    }

    /**
     * Store a new task
     */
    public function store(Request $request)
    {
        $business = $this->getCurrentBusiness();
        $userId = Auth::id();

        if (!$business) {
            return response()->json(['error' => 'Biznes topilmadi'], 404);
        }

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'type' => 'required|in:call,meeting,email,task,follow_up,other',
            'priority' => 'required|in:low,normal,medium,high,urgent',
            'due_date' => 'required|date',
            'due_time' => 'nullable|string',
            'reminder_at' => 'nullable|date',
            'lead_id' => 'nullable|exists:leads,id',
        ]);

        // Verify lead belongs to this operator if provided
        if (isset($validated['lead_id'])) {
            $lead = Lead::where('id', $validated['lead_id'])
                ->where('business_id', $business->id)
                ->where('assigned_to', $userId)
                ->first();

            if (!$lead) {
                return response()->json(['error' => 'Lead topilmadi yoki ruxsat yo\'q'], 403);
            }
        }

        // Combine date and time
        $dueDate = $validated['due_date'];
        if (!empty($validated['due_time'])) {
            $dueDate .= ' ' . $validated['due_time'];
        }

        // Convert priority if needed
        $priority = $validated['priority'];
        if ($priority === 'normal') {
            $priority = 'medium';
        }

        $task = Task::create([
            'business_id' => $business->id,
            'user_id' => $userId,
            'assigned_to' => $userId, // Operator creates tasks for themselves
            'title' => $validated['title'],
            'description' => $validated['description'] ?? null,
            'type' => $validated['type'],
            'priority' => $priority,
            'due_date' => $dueDate,
            'reminder_at' => $validated['reminder_at'] ?? null,
            'lead_id' => $validated['lead_id'] ?? null,
            'status' => 'pending',
        ]);

        $task->load(['lead:id,name,phone', 'assignedUser:id,name']);

        return response()->json([
            'success' => true,
            'message' => 'Vazifa yaratildi',
            'task' => $task,
        ]);
    }

    /**
     * Update a task
     */
    public function update(Request $request, Task $task)
    {
        $business = $this->getCurrentBusiness();
        $userId = Auth::id();

        // Verify task belongs to this operator
        if (!$business || $task->business_id !== $business->id || $task->assigned_to !== $userId) {
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
            'result' => 'nullable|string',
        ]);

        // Verify lead if provided
        if (isset($validated['lead_id'])) {
            $lead = Lead::where('id', $validated['lead_id'])
                ->where('business_id', $business->id)
                ->where('assigned_to', $userId)
                ->first();

            if (!$lead) {
                return response()->json(['error' => 'Lead topilmadi yoki ruxsat yo\'q'], 403);
            }
        }

        // Combine date and time
        if (isset($validated['due_date']) && !empty($validated['due_time'])) {
            $validated['due_date'] .= ' ' . $validated['due_time'];
        }
        unset($validated['due_time']);

        // Convert priority if needed
        if (isset($validated['priority']) && $validated['priority'] === 'normal') {
            $validated['priority'] = 'medium';
        }

        // If marking as completed
        if (isset($validated['status']) && $validated['status'] === 'completed' && $task->status !== 'completed') {
            $validated['completed_at'] = now();
        }

        $task->update($validated);
        $task->load(['lead:id,name,phone', 'assignedUser:id,name']);

        return response()->json([
            'success' => true,
            'message' => 'Vazifa yangilandi',
            'task' => $task,
        ]);
    }

    /**
     * Complete a task
     */
    public function complete(Request $request, Task $task)
    {
        $business = $this->getCurrentBusiness();
        $userId = Auth::id();

        // Verify task belongs to this operator
        if (!$business || $task->business_id !== $business->id || $task->assigned_to !== $userId) {
            return response()->json(['error' => 'Ruxsat yo\'q'], 403);
        }

        $result = $request->get('result');
        $task->markAsCompleted($result);

        return response()->json([
            'success' => true,
            'message' => 'Vazifa bajarildi',
            'task' => $task,
        ]);
    }

    /**
     * Delete a task
     */
    public function destroy(Task $task)
    {
        $business = $this->getCurrentBusiness();
        $userId = Auth::id();

        // Verify task belongs to this operator
        if (!$business || $task->business_id !== $business->id || $task->assigned_to !== $userId) {
            return response()->json(['error' => 'Ruxsat yo\'q'], 403);
        }

        $task->delete();

        return response()->json([
            'success' => true,
            'message' => 'Vazifa o\'chirildi',
        ]);
    }

    /**
     * Helper to group tasks by time period
     */
    protected function groupTasks($allTasks, $now, $endOfWeek)
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
            $taskData = [
                'id' => $task->id,
                'title' => $task->title,
                'description' => $task->description,
                'type' => $task->type,
                'type_label' => $task->type_label,
                'priority' => $task->priority,
                'priority_label' => $task->priority_label,
                'status' => $task->status,
                'status_label' => $task->status_label,
                'due_date' => $task->due_date->format('Y-m-d H:i'),
                'due_date_human' => $task->due_date->format('H:i'),
                'due_date_full' => $task->due_date->format('d.m.Y H:i'),
                'is_overdue' => $task->status === 'pending' && $task->due_date->lt($now),
                'completed_at' => $task->completed_at?->format('Y-m-d H:i'),
                'result' => $task->result,
                'lead' => $task->lead,
                'assigned_user' => $task->assignedUser,
                'created_at' => $task->created_at->format('d.m.Y H:i'),
            ];

            if ($task->status === 'completed') {
                $grouped['completed'][] = $taskData;
            } elseif ($task->due_date->lt($now)) {
                $grouped['overdue'][] = $taskData;
            } elseif ($task->due_date->isToday()) {
                $grouped['today'][] = $taskData;
            } elseif ($task->due_date->isTomorrow()) {
                $grouped['tomorrow'][] = $taskData;
            } elseif ($task->due_date->lte($endOfWeek)) {
                $grouped['this_week'][] = $taskData;
            } else {
                $grouped['later'][] = $taskData;
            }
        }

        return $grouped;
    }
}
