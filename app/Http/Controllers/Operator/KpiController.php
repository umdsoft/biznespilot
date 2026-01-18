<?php

namespace App\Http\Controllers\Operator;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Traits\HasCurrentBusiness;
use App\Models\Lead;
use App\Models\Task;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class KpiController extends Controller
{
    use HasCurrentBusiness;

    public function index()
    {
        $business = $this->getCurrentBusiness();
        $userId = Auth::id();

        if (! $business) {
            return Inertia::render('Operator/KPI/Index', [
                'kpi' => null,
                'targets' => null,
                'history' => [],
            ]);
        }

        // Current KPI metrics
        $kpi = [
            'calls' => [
                'today' => 15,
                'this_week' => 67,
                'this_month' => 245,
                'target' => 20,
                'progress' => 75,
            ],
            'leads' => [
                'total_assigned' => Lead::where('business_id', $business->id)->where('assigned_to', $userId)->count(),
                'new' => Lead::where('business_id', $business->id)->where('assigned_to', $userId)->where('status', 'new')->count(),
                'converted' => Lead::where('business_id', $business->id)->where('assigned_to', $userId)->where('status', 'converted')->count(),
                'conversion_rate' => 15.5,
            ],
            'response' => [
                'avg_time' => 4.5, // minutes
                'target' => 5,
                'within_target' => 89, // percentage
            ],
            'tasks' => [
                'completed_today' => Task::where('business_id', $business->id)->where('assigned_to', $userId)->where('status', 'completed')->whereDate('completed_at', today())->count(),
                'completed_this_week' => Task::where('business_id', $business->id)->where('assigned_to', $userId)->where('status', 'completed')->whereBetween('completed_at', [now()->startOfWeek(), now()->endOfWeek()])->count(),
                'overdue' => Task::where('business_id', $business->id)->where('assigned_to', $userId)->where('status', '!=', 'completed')->where('due_date', '<', now())->count(),
            ],
            'satisfaction' => [
                'rating' => 4.5,
                'reviews' => 23,
            ],
        ];

        // Targets
        $targets = [
            ['metric' => 'Kunlik qo\'ng\'iroqlar', 'target' => 20, 'current' => 15, 'unit' => 'ta'],
            ['metric' => 'Haftalik konversiya', 'target' => 10, 'current' => 8, 'unit' => 'ta'],
            ['metric' => 'Javob vaqti', 'target' => 5, 'current' => 4.5, 'unit' => 'daqiqa'],
            ['metric' => 'Mijoz baholashi', 'target' => 4.5, 'current' => 4.5, 'unit' => '/5'],
        ];

        // History (last 7 days)
        $history = [
            ['date' => now()->subDays(6)->format('D'), 'calls' => 18, 'conversions' => 2],
            ['date' => now()->subDays(5)->format('D'), 'calls' => 22, 'conversions' => 3],
            ['date' => now()->subDays(4)->format('D'), 'calls' => 19, 'conversions' => 1],
            ['date' => now()->subDays(3)->format('D'), 'calls' => 25, 'conversions' => 4],
            ['date' => now()->subDays(2)->format('D'), 'calls' => 21, 'conversions' => 2],
            ['date' => now()->subDays(1)->format('D'), 'calls' => 17, 'conversions' => 2],
            ['date' => 'Bugun', 'calls' => 15, 'conversions' => 1],
        ];

        return Inertia::render('Operator/KPI/Index', [
            'kpi' => $kpi,
            'targets' => $targets,
            'history' => $history,
        ]);
    }
}
