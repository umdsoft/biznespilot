<?php

namespace App\Http\Controllers\SalesHead;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Traits\PanelTaskController;
use App\Models\Lead;
use App\Models\Task;
use Illuminate\Support\Facades\Auth;

class TaskController extends Controller
{
    use PanelTaskController;

    protected function getViewPrefix(): string
    {
        return 'SalesHead';
    }

    protected function getRoutePrefix(): string
    {
        return 'sales-head';
    }

    /**
     * Get tasks for a specific lead
     */
    public function leadTasks(Lead $lead)
    {
        $business = $this->getCurrentBusiness();

        if (! $business || $lead->business_id !== $business->id) {
            return response()->json(['error' => 'Ruxsat yo\'q'], 403);
        }

        $now = now();
        $endOfWeek = $now->copy()->addWeek()->endOfDay();

        $allTasks = Task::where('lead_id', $lead->id)
            ->with(['assignedUser:id,name'])
            ->orderBy('due_date', 'asc')
            ->get();

        $grouped = $this->groupTasks($allTasks, $now, $endOfWeek);

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

        if (! $business) {
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
