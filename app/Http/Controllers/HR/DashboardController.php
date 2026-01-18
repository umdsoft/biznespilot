<?php

namespace App\Http\Controllers\HR;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Traits\HasCurrentBusiness;
use App\Models\AttendanceRecord;
use App\Models\BusinessUser;
use App\Models\LeaveBalance;
use App\Models\LeaveRequest;
use App\Models\Todo;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class DashboardController extends Controller
{
    use HasCurrentBusiness;

    public function index()
    {
        $business = $this->getCurrentBusiness();

        // Get team statistics
        $teamStats = [
            'total_employees' => BusinessUser::where('business_id', $business->id)->count(),
            'active_employees' => BusinessUser::where('business_id', $business->id)
                ->whereNotNull('accepted_at')
                ->count(),
            'pending_invitations' => BusinessUser::where('business_id', $business->id)
                ->whereNull('accepted_at')
                ->count(),
            'departments' => [
                'sales' => BusinessUser::where('business_id', $business->id)
                    ->whereIn('department', ['sales_head', 'sales_operator', 'operator'])
                    ->count(),
                'marketing' => BusinessUser::where('business_id', $business->id)
                    ->where('department', 'marketing')
                    ->count(),
                'finance' => BusinessUser::where('business_id', $business->id)
                    ->where('department', 'finance')
                    ->count(),
                'hr' => BusinessUser::where('business_id', $business->id)
                    ->where('department', 'hr')
                    ->count(),
            ],
        ];

        // Recent team activities - real data from business_user
        $recentActivities = BusinessUser::where('business_id', $business->id)
            ->with('user:id,name')
            ->orderBy('created_at', 'desc')
            ->take(5)
            ->get()
            ->map(function ($member) {
                $description = 'Yangi xodim qo\'shildi';
                $type = 'new_member';

                if ($member->accepted_at) {
                    $description = 'Xodim qo\'shildi';
                    $type = 'accepted';
                } elseif (! $member->accepted_at) {
                    $description = 'Taklifnoma yuborildi';
                    $type = 'invitation_sent';
                }

                return [
                    'id' => $member->id,
                    'type' => $type,
                    'description' => $description,
                    'user' => $member->user->name ?? 'N/A',
                    'department' => $member->department_label,
                    'date' => $member->created_at->format('d.m.Y H:i'),
                ];
            })
            ->toArray();

        // Pending tasks - real data from todos
        $pendingTasks = Todo::where('business_id', $business->id)
            ->notCompleted()
            ->orderBy('created_at', 'desc')
            ->take(3)
            ->get()
            ->map(function ($todo) {
                return [
                    'id' => $todo->id,
                    'title' => $todo->title,
                    'priority' => $todo->priority ?? 'medium',
                    'due_date' => $todo->due_date ? $todo->due_date->format('d.m.Y') : null,
                    'status' => $todo->status,
                ];
            })
            ->toArray();

        // Today's attendance for current user
        $todayAttendance = AttendanceRecord::getTodayAttendance($business->id, Auth::id());

        // Format attendance data
        $attendanceData = null;
        if ($todayAttendance) {
            $attendanceData = [
                'id' => $todayAttendance->id,
                'check_in' => $todayAttendance->formatted_check_in,
                'check_out' => $todayAttendance->formatted_check_out,
                'work_hours' => $todayAttendance->work_hours,
                'status' => $todayAttendance->status,
                'status_label' => $todayAttendance->status_label,
                'status_color' => $todayAttendance->status_color,
                'is_checked_in' => $todayAttendance->is_checked_in,
                'is_checked_out' => $todayAttendance->is_checked_out,
            ];
        }

        // Leave balances for current user
        $leaveBalances = LeaveBalance::where('business_id', $business->id)
            ->where('user_id', Auth::id())
            ->where('year', now()->year)
            ->with('leaveType')
            ->get()
            ->map(fn ($balance) => [
                'id' => $balance->id,
                'leave_type' => [
                    'name' => $balance->leaveType->name,
                    'code' => $balance->leaveType->code,
                ],
                'total_days' => $balance->total_days,
                'used_days' => $balance->used_days,
                'available_days' => $balance->available_days,
            ]);

        // Upcoming approved leaves
        $upcomingLeaves = LeaveRequest::where('business_id', $business->id)
            ->where('user_id', Auth::id())
            ->approved()
            ->where('start_date', '>', now())
            ->orderBy('start_date', 'asc')
            ->with('leaveType')
            ->take(3)
            ->get()
            ->map(fn ($leave) => [
                'id' => $leave->id,
                'leave_type' => $leave->leaveType->name,
                'start_date' => $leave->start_date->format('d.m.Y'),
                'end_date' => $leave->end_date->format('d.m.Y'),
                'total_days' => $leave->total_days,
            ]);

        return Inertia::render('HR/Dashboard', [
            'stats' => $teamStats,
            'recentActivities' => $recentActivities,
            'pendingTasks' => $pendingTasks,
            'todayAttendance' => $attendanceData,
            'leaveBalances' => $leaveBalances,
            'upcomingLeaves' => $upcomingLeaves,
            'currentBusiness' => $business ? [
                'id' => $business->id,
                'name' => $business->name,
            ] : null,
        ]);
    }

    public function apiStats()
    {
        return response()->json([
            'tasks_count' => 3,
            'unread_count' => 1,
        ]);
    }
}
