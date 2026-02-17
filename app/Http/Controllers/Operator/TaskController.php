<?php

namespace App\Http\Controllers\Operator;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Traits\PanelTaskController;
use App\Models\Lead;
use App\Models\Task;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TaskController extends Controller
{
    use PanelTaskController;

    protected function getViewPrefix(): string
    {
        return 'Operator';
    }

    protected function getRoutePrefix(): string
    {
        return 'operator';
    }

    /**
     * Operator faqat o'ziga tayinlangan tasklarni ko'radi
     */
    protected function getBaseTaskQuery($business)
    {
        return Task::where('business_id', $business->id)
            ->where('assigned_to', Auth::id());
    }

    protected function getLeadsQuery($business)
    {
        return Lead::where('business_id', $business->id)
            ->where('assigned_to', Auth::id())
            ->select('id', 'name', 'phone')
            ->orderBy('name');
    }

    protected function canManageTask(Task $task): bool
    {
        $business = $this->getCurrentBusiness();

        return $business && $task->business_id === $business->id && $task->assigned_to === Auth::id();
    }

    /**
     * Operator faqat o'ziga task yaratadi
     */
    public function store(Request $request)
    {
        $business = $this->getCurrentBusiness();
        $userId = Auth::id();

        if (! $business) {
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

        if (isset($validated['lead_id'])) {
            $lead = Lead::where('id', $validated['lead_id'])
                ->where('business_id', $business->id)
                ->where('assigned_to', $userId)
                ->first();

            if (! $lead) {
                return response()->json(['error' => 'Lead topilmadi yoki ruxsat yo\'q'], 403);
            }
        }

        $dueDate = $validated['due_date'];
        if (! empty($validated['due_time'])) {
            $dueDate .= ' '.$validated['due_time'];
        }

        $priority = $validated['priority'];
        if ($priority === 'normal') {
            $priority = 'medium';
        }

        $task = Task::create([
            'business_id' => $business->id,
            'user_id' => $userId,
            'assigned_to' => $userId,
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
}
