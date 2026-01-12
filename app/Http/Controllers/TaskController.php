<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Traits\HasCurrentBusiness;
use App\Models\Task;
use App\Models\Lead;
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

        if (!$business) {
            return redirect()->route('business.index')
                ->with('error', 'Avval biznes tanlang');
        }

        $now = now();
        $endOfWeek = $now->copy()->addWeek()->endOfDay();

        $allTasks = Task::where('business_id', $business->id)
            ->with(['lead:id,name,phone', 'assignedUser:id,name'])
            ->orderBy('due_date', 'asc')
            ->get();

        // Group tasks
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
                // Vaqti o'tib ketgan (hatto bugun bo'lsa ham)
                $grouped['overdue'][] = $taskData;
            } elseif ($task->due_date->isToday()) {
                // Bugun, lekin vaqti hali kelmagan
                $grouped['today'][] = $taskData;
            } elseif ($task->due_date->isTomorrow()) {
                $grouped['tomorrow'][] = $taskData;
            } elseif ($task->due_date->lte($endOfWeek)) {
                $grouped['this_week'][] = $taskData;
            } else {
                $grouped['later'][] = $taskData;
            }
        }

        // Stats
        $stats = [
            'total' => count($grouped['overdue']) + count($grouped['today']) + count($grouped['tomorrow']) + count($grouped['this_week']) + count($grouped['later']),
            'overdue' => count($grouped['overdue']),
            'today' => count($grouped['today']),
            'tomorrow' => count($grouped['tomorrow']),
            'this_week' => count($grouped['this_week']),
            'completed' => count($grouped['completed']),
        ];

        // Get leads for task modal
        $leads = Lead::where('business_id', $business->id)
            ->select('id', 'name', 'phone')
            ->orderBy('name')
            ->get();

        return Inertia::render('Business/Tasks/Index', [
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

        if (!$business) {
            return response()->json(['error' => 'Biznes topilmadi'], 404);
        }

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'type' => 'required|in:call,meeting,email,task,follow_up,other',
            'priority' => 'required|in:low,medium,high,urgent',
            'due_date' => 'required|date',
            'reminder_at' => 'nullable|date',
            'lead_id' => 'nullable|exists:leads,id',
            'assigned_to' => 'nullable|exists:users,id',
        ]);

        // Determine assigned_to - auto-assign to lead's operator if not explicitly set
        $assignedTo = $validated['assigned_to'] ?? null;
        if (!$assignedTo && isset($validated['lead_id'])) {
            $lead = Lead::find($validated['lead_id']);
            if ($lead && $lead->assigned_to) {
                $assignedTo = $lead->assigned_to;
            }
        }
        // Fallback to current user if still null
        if (!$assignedTo) {
            $assignedTo = Auth::id();
        }

        $task = Task::create([
            'business_id' => $business->id,
            'user_id' => Auth::id(),
            'assigned_to' => $assignedTo,
            'title' => $validated['title'],
            'description' => $validated['description'] ?? null,
            'type' => $validated['type'],
            'priority' => $validated['priority'],
            'due_date' => $validated['due_date'],
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

        if (!$business || $task->business_id !== $business->id) {
            return response()->json(['error' => 'Ruxsat yo\'q'], 403);
        }

        $validated = $request->validate([
            'title' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'type' => 'sometimes|in:call,meeting,email,task,follow_up,other',
            'priority' => 'sometimes|in:low,medium,high,urgent',
            'status' => 'sometimes|in:pending,in_progress,completed,cancelled',
            'due_date' => 'sometimes|date',
            'reminder_at' => 'nullable|date',
            'assigned_to' => 'nullable|exists:users,id',
            'result' => 'nullable|string',
        ]);

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

        if (!$business || $task->business_id !== $business->id) {
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

        if (!$business || $task->business_id !== $business->id) {
            return response()->json(['error' => 'Ruxsat yo\'q'], 403);
        }

        $task->delete();

        return response()->json([
            'success' => true,
            'message' => 'Vazifa o\'chirildi',
        ]);
    }

    /**
     * Get tasks for a specific lead
     */
    public function leadTasks(Lead $lead)
    {
        $business = $this->getCurrentBusiness();

        if (!$business || $lead->business_id !== $business->id) {
            return response()->json(['error' => 'Ruxsat yo\'q'], 403);
        }

        $now = now();
        $today = $now->copy()->startOfDay();
        $tomorrow = $now->copy()->addDay()->startOfDay();
        $endOfTomorrow = $tomorrow->copy()->endOfDay();
        $endOfWeek = $now->copy()->addWeek()->endOfDay();

        $allTasks = Task::where('lead_id', $lead->id)
            ->with(['assignedUser:id,name'])
            ->orderBy('due_date', 'asc')
            ->get();

        // Group tasks
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
                'assigned_user' => $task->assignedUser,
                'created_at' => $task->created_at->format('d.m.Y H:i'),
            ];

            if ($task->status === 'completed') {
                $grouped['completed'][] = $taskData;
            } elseif ($task->due_date->lt($now)) {
                // Vaqti o'tib ketgan (hatto bugun bo'lsa ham)
                $grouped['overdue'][] = $taskData;
            } elseif ($task->due_date->isToday()) {
                // Bugun, lekin vaqti hali kelmagan
                $grouped['today'][] = $taskData;
            } elseif ($task->due_date->isTomorrow()) {
                $grouped['tomorrow'][] = $taskData;
            } elseif ($task->due_date->lte($endOfWeek)) {
                $grouped['this_week'][] = $taskData;
            } else {
                $grouped['later'][] = $taskData;
            }
        }

        return response()->json([
            'tasks' => $grouped,
            'types' => Task::TYPES,
            'priorities' => Task::PRIORITIES,
        ]);
    }

    /**
     * Get task stats for dashboard
     */
    public function stats()
    {
        $business = $this->getCurrentBusiness();

        if (!$business) {
            return response()->json(['error' => 'Biznes topilmadi'], 404);
        }

        return response()->json([
            'total' => Task::where('business_id', $business->id)->pending()->count(),
            'overdue' => Task::where('business_id', $business->id)->overdue()->count(),
            'today' => Task::where('business_id', $business->id)->today()->pending()->count(),
            'my_tasks' => Task::where('business_id', $business->id)
                ->assignedTo(Auth::id())
                ->pending()
                ->count(),
        ]);
    }
}
