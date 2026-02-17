<?php

namespace App\Http\Controllers\Operator;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Traits\PanelTodoController;
use App\Models\Todo;
use Illuminate\Support\Facades\Auth;

class TodoController extends Controller
{
    use PanelTodoController;

    protected function getViewPrefix(): string
    {
        return 'Operator';
    }

    protected function getRoutePrefix(): string
    {
        return 'operator';
    }

    /**
     * Operator faqat o'ziga tayinlangan todolarni ko'radi
     */
    protected function getBaseQuery($business)
    {
        return Todo::where('business_id', $business->id)
            ->whereNull('parent_id')
            ->where(function ($q) {
                $userId = Auth::id();
                $q->where('assigned_to', $userId)
                    ->orWhereHas('assignees', fn ($q) => $q->where('user_id', $userId));
            });
    }

    protected function getTodoStats($business): array
    {
        $userId = Auth::id();

        return [
            'total' => Todo::where('business_id', $business->id)
                ->whereNull('parent_id')
                ->where(function ($q) use ($userId) {
                    $q->where('assigned_to', $userId)
                        ->orWhereHas('assignees', fn ($q) => $q->where('user_id', $userId));
                })
                ->whereIn('status', [Todo::STATUS_PENDING, Todo::STATUS_IN_PROGRESS])
                ->count(),
            'overdue' => Todo::where('business_id', $business->id)
                ->whereNull('parent_id')
                ->where(function ($q) use ($userId) {
                    $q->where('assigned_to', $userId)
                        ->orWhereHas('assignees', fn ($q) => $q->where('user_id', $userId));
                })
                ->whereIn('status', [Todo::STATUS_PENDING, Todo::STATUS_IN_PROGRESS])
                ->where('due_date', '<', now()->startOfDay())
                ->count(),
            'completed_today' => Todo::where('business_id', $business->id)
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
}
