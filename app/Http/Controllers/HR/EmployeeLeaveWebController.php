<?php

namespace App\Http\Controllers\HR;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Traits\HasCurrentBusiness;
use App\Models\BusinessUser;
use App\Models\LeaveRequest;
use Inertia\Inertia;

class EmployeeLeaveWebController extends Controller
{
    use HasCurrentBusiness;

    public function index()
    {
        $business = $this->getCurrentBusiness();

        if (! $business) {
            return redirect()->route('login');
        }

        // Get all leave requests for the business
        $leaveRequests = LeaveRequest::where('business_id', $business->id)
            ->with(['user:id,name,email', 'leaveType:id,name,code', 'approver:id,name'])
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(fn ($request) => [
                'id' => $request->id,
                'user_name' => $request->user->name ?? 'N/A',
                'user_email' => $request->user->email ?? null,
                'leave_type' => $request->leaveType->name ?? 'N/A',
                'leave_type_code' => $request->leaveType->code ?? null,
                'start_date' => $request->start_date->format('d.m.Y'),
                'end_date' => $request->end_date->format('d.m.Y'),
                'total_days' => $request->total_days,
                'status' => $request->status,
                'status_label' => $request->status_label ?? ucfirst($request->status),
                'reason' => $request->reason,
                'approver_name' => $request->approver->name ?? null,
                'approved_at' => $request->approved_at?->format('d.m.Y H:i'),
                'created_at' => $request->created_at->format('d.m.Y H:i'),
            ]);

        // Get employees for creating new leave
        $employees = BusinessUser::where('business_id', $business->id)
            ->with('user:id,name')
            ->whereNotNull('accepted_at')
            ->get()
            ->map(fn ($member) => [
                'id' => $member->user_id,
                'name' => $member->user->name ?? 'N/A',
            ]);

        // Statistics
        $stats = [
            'total_requests' => $leaveRequests->count(),
            'pending' => $leaveRequests->where('status', 'pending')->count(),
            'approved' => $leaveRequests->where('status', 'approved')->count(),
            'rejected' => $leaveRequests->where('status', 'rejected')->count(),
            'on_leave_today' => $leaveRequests
                ->where('status', 'approved')
                ->filter(fn ($r) => now()->between($r['start_date'], $r['end_date']))
                ->count(),
        ];

        return Inertia::render('HR/EmployeeLeave/Index', [
            'leaveRequests' => $leaveRequests,
            'employees' => $employees,
            'stats' => $stats,
            'currentBusiness' => [
                'id' => $business->id,
                'name' => $business->name,
            ],
        ]);
    }
}
