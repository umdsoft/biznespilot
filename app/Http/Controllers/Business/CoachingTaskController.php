<?php

namespace App\Http\Controllers\Business;

use App\Http\Controllers\Controller;
use App\Models\OperatorCoachingTask;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class CoachingTaskController extends Controller
{
    public function index()
    {
        $business = $this->getCurrentBusiness();
        if (!$business) return redirect()->route('login');

        return Inertia::render('Business/CoachingTasks/Index');
    }

    public function list(Request $request): JsonResponse
    {
        $business = $this->getCurrentBusiness();
        if (!$business) return response()->json(['error' => 'Biznes topilmadi'], 422);

        $query = OperatorCoachingTask::where('business_id', $business->id)
            ->with('operator:id,name,email')
            ->orderByRaw("FIELD(priority, 'urgent', 'high', 'medium', 'low')")
            ->orderByDesc('created_at');

        if ($request->has('status') && $request->status !== 'all') {
            $query->where('status', $request->status);
        }

        if ($request->has('operator_id')) {
            $query->where('operator_id', $request->operator_id);
        }

        $tasks = $query->limit(100)->get();

        return response()->json([
            'success' => true,
            'tasks' => $tasks->map(fn($t) => [
                'id' => $t->id,
                'title' => $t->title,
                'description' => $t->description,
                'weak_area' => $t->weak_area,
                'weak_area_label' => $t->weak_area_label,
                'priority' => $t->priority,
                'status' => $t->status,
                'score_at_creation' => $t->score_at_creation,
                'operator_name' => $t->operator?->name,
                'operator_id' => $t->operator_id,
                'due_date' => $t->due_date?->toISOString(),
                'completed_at' => $t->completed_at?->toISOString(),
                'created_at' => $t->created_at->toISOString(),
            ]),
            'stats' => [
                'total' => OperatorCoachingTask::where('business_id', $business->id)->count(),
                'pending' => OperatorCoachingTask::where('business_id', $business->id)->where('status', 'pending')->count(),
                'urgent' => OperatorCoachingTask::where('business_id', $business->id)->where('priority', 'urgent')->where('status', '!=', 'completed')->count(),
                'completed' => OperatorCoachingTask::where('business_id', $business->id)->where('status', 'completed')->count(),
            ],
        ]);
    }

    public function complete(Request $request, string $id): JsonResponse
    {
        $business = $this->getCurrentBusiness();
        $task = OperatorCoachingTask::where('business_id', $business->id)->findOrFail($id);
        $task->markCompleted($request->input('notes'));

        return response()->json(['success' => true]);
    }

    public function updateStatus(Request $request, string $id): JsonResponse
    {
        $business = $this->getCurrentBusiness();
        $task = OperatorCoachingTask::where('business_id', $business->id)->findOrFail($id);

        $validated = $request->validate([
            'status' => 'required|in:pending,in_progress,completed,skipped',
        ]);

        $task->update([
            'status' => $validated['status'],
            'completed_at' => $validated['status'] === 'completed' ? now() : null,
        ]);

        return response()->json(['success' => true]);
    }

    private function getCurrentBusiness()
    {
        $user = Auth::user();
        if (!$user) return null;
        $businessId = session('current_business_id');
        if ($businessId) return \App\Models\Business::find($businessId);
        return $user->business ?? $user->businesses()->first();
    }
}
