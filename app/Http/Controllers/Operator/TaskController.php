<?php

namespace App\Http\Controllers\Operator;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Traits\HasCurrentBusiness;
use App\Models\Business;
use App\Models\Task;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class TaskController extends Controller
{
    use HasCurrentBusiness;

    public function index(Request $request)
    {
        $business = $this->getCurrentBusiness();
        $userId = Auth::id();

        if (!$business) {
            return Inertia::render('Operator/Tasks/Index', [
                'tasks' => [],
                'stats' => ['total' => 0, 'pending' => 0, 'completed' => 0, 'overdue' => 0],
            ]);
        }

        $query = Task::where('business_id', $business->id)
            ->where('assignee_id', $userId)
            ->with(['creator'])
            ->orderBy('due_date', 'asc');

        if ($request->has('status') && $request->status !== 'all') {
            $query->where('status', $request->status);
        }

        $tasks = $query->get()->map(function ($task) {
            return [
                'id' => $task->id,
                'title' => $task->title,
                'description' => $task->description,
                'status' => $task->status,
                'priority' => $task->priority,
                'due_date' => $task->due_date?->format('Y-m-d'),
                'creator' => $task->creator ? ['id' => $task->creator->id, 'name' => $task->creator->name] : null,
                'is_overdue' => $task->due_date && $task->due_date->isPast() && $task->status !== 'completed',
                'created_at' => $task->created_at->format('Y-m-d H:i'),
            ];
        });

        $stats = [
            'total' => $tasks->count(),
            'pending' => $tasks->where('status', 'pending')->count(),
            'in_progress' => $tasks->where('status', 'in_progress')->count(),
            'completed' => $tasks->where('status', 'completed')->count(),
            'overdue' => $tasks->where('is_overdue', true)->count(),
        ];

        return Inertia::render('Operator/Tasks/Index', [
            'tasks' => $tasks->values(),
            'stats' => $stats,
            'filters' => $request->only(['status']),
        ]);
    }

    public function update(Request $request, $id)
    {
        $business = $this->getCurrentBusiness();
        $userId = Auth::id();

        $task = Task::where('business_id', $business->id)
            ->where('assignee_id', $userId)
            ->findOrFail($id);

        $validated = $request->validate([
            'status' => 'sometimes|in:pending,in_progress,completed',
        ]);

        if (isset($validated['status']) && $validated['status'] === 'completed') {
            $validated['completed_at'] = now();
        }

        $task->update($validated);

        return redirect()->back()->with('success', 'Vazifa yangilandi');
    }

    public function complete($id)
    {
        $business = $this->getCurrentBusiness();
        $userId = Auth::id();

        $task = Task::where('business_id', $business->id)
            ->where('assignee_id', $userId)
            ->findOrFail($id);

        $task->update([
            'status' => 'completed',
            'completed_at' => now(),
        ]);

        return redirect()->back()->with('success', 'Vazifa bajarildi');
    }
}
