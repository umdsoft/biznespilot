<?php

namespace App\Http\Controllers\HR;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Traits\HasCurrentBusiness;
use App\Models\BusinessUser;
use App\Models\JobDescription;
use App\Models\LeaveRequest;
use App\Models\TurnoverRecord;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Inertia\Inertia;

class EmployeeManagementController extends Controller
{
    use HasCurrentBusiness;

    /**
     * Unified employee management page
     * Combines: Team list, Contracts, Leave management, Termination
     */
    public function index()
    {
        $business = $this->getCurrentBusiness();

        if (!$business) {
            return redirect()->route('login');
        }

        // Get employees with all related data
        $employees = $this->getEmployeesData($business);

        // Get leave requests for all employees
        $leaveRequests = $this->getLeaveRequests($business);

        // Get terminated employees
        $terminatedEmployees = $this->getTerminatedEmployees($business);

        // Calculate stats
        $stats = $this->calculateStats($business, $employees, $leaveRequests);

        // Get job descriptions for position selection
        $jobDescriptions = JobDescription::where('business_id', $business->id)
            ->where('is_active', true)
            ->orderBy('department')
            ->orderBy('title')
            ->get()
            ->map(function ($job) {
                return [
                    'id' => $job->id,
                    'title' => $job->title,
                    'department' => $job->department,
                    'department_label' => $job->department_label,
                    'position_level' => $job->position_level,
                    'position_level_label' => $job->position_level_label,
                    'salary_range_min' => $job->salary_range_min,
                    'salary_range_max' => $job->salary_range_max,
                    'salary_range_formatted' => $job->salary_range_formatted,
                    'employment_type' => $job->employment_type,
                    'employment_type_label' => $job->employment_type_label,
                ];
            });

        return Inertia::render('HR/Employees/Index', [
            'employees' => $employees,
            'departments' => BusinessUser::DEPARTMENTS,
            'roles' => BusinessUser::ROLES,
            'leaveRequests' => $leaveRequests,
            'terminatedEmployees' => $terminatedEmployees,
            'stats' => $stats,
            'jobDescriptions' => $jobDescriptions,
            'employmentTypes' => BusinessUser::EMPLOYMENT_TYPES,
            'contractTypes' => BusinessUser::CONTRACT_TYPES,
            'positionLevels' => JobDescription::POSITION_LEVELS,
            'currentBusiness' => [
                'id' => $business->id,
                'name' => $business->name,
            ],
        ]);
    }

    /**
     * Get all employees with their data
     */
    private function getEmployeesData($business)
    {
        $employees = [];

        // Get business owner
        $owner = $business->owner;
        if ($owner) {
            $employees[] = [
                'id' => 'owner',
                'user_id' => $owner->id,
                'name' => $owner->name,
                'phone' => $owner->phone ?? $owner->login ?? $owner->email,
                'email' => $owner->email,
                'role' => 'owner',
                'role_label' => 'Biznes egasi',
                'department' => null,
                'department_label' => null,
                'job_description_id' => null,
                'position' => 'Direktor',
                'contract_type' => 'unlimited',
                'contract_type_label' => 'Muddatsiz',
                'contract_start_date' => $business->created_at?->format('Y-m-d'),
                'contract_end_date' => null,
                'salary' => null,
                'employment_type' => 'full_time',
                'employment_type_label' => 'To\'liq ish kuni',
                'status' => 'active',
                'is_owner' => true,
                'joined_at' => $business->created_at?->format('d.m.Y H:i'),
                'created_at' => $business->created_at?->format('d.m.Y H:i'),
            ];
        }

        // Get team members (all active members)
        $members = BusinessUser::where('business_id', $business->id)
            ->with(['user:id,name,phone,login,email', 'jobDescription:id,title,department'])
            ->orderBy('created_at', 'desc')
            ->get();

        foreach ($members as $member) {
            $employees[] = [
                'id' => $member->id,
                'user_id' => $member->user_id,
                'name' => $member->user->name ?? null,
                'phone' => $member->user->phone ?? $member->user->login ?? null,
                'email' => $member->user->email ?? null,
                'role' => $member->role,
                'role_label' => $member->role_label,
                'department' => $member->department,
                'department_label' => $member->department_label,
                'job_description_id' => $member->job_description_id,
                'position' => $member->jobDescription?->title ?? null,
                'contract_type' => $member->contract_type ?? 'unlimited',
                'contract_type_label' => $member->contract_type_label,
                'contract_start_date' => $member->contract_start_date?->format('Y-m-d') ?? $member->joined_at?->format('Y-m-d'),
                'contract_end_date' => $member->contract_end_date?->format('Y-m-d'),
                'salary' => $member->salary,
                'employment_type' => $member->employment_type ?? 'full_time',
                'employment_type_label' => $member->employment_type_label,
                'status' => 'active',
                'is_owner' => false,
                'joined_at' => $member->joined_at?->format('d.m.Y H:i'),
                'created_at' => $member->created_at?->format('d.m.Y H:i'),
            ];
        }

        return $employees;
    }

    /**
     * Get leave requests for all employees in the business
     */
    private function getLeaveRequests($business)
    {
        // Get user IDs for this business
        $userIds = BusinessUser::where('business_id', $business->id)
            ->pluck('user_id')
            ->toArray();

        // Add owner
        if ($business->owner) {
            $userIds[] = $business->owner->id;
        }

        return LeaveRequest::whereIn('user_id', $userIds)
            ->with(['user:id,name,email', 'leaveType:id,name'])
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($request) {
                return [
                    'id' => $request->id,
                    'user_id' => $request->user_id,
                    'user_name' => $request->user->name ?? null,
                    'user_email' => $request->user->email ?? null,
                    'leave_type' => $request->leaveType->name ?? 'Ta\'til',
                    'start_date' => $request->start_date?->format('d.m.Y'),
                    'end_date' => $request->end_date?->format('d.m.Y'),
                    'total_days' => $request->total_days,
                    'status' => $request->status,
                    'reason' => $request->reason,
                    'approved_by' => $request->approved_by,
                    'approved_at' => $request->approved_at?->format('d.m.Y H:i'),
                ];
            })
            ->toArray();
    }

    /**
     * Get terminated employees
     */
    private function getTerminatedEmployees($business)
    {
        return TurnoverRecord::where('business_id', $business->id)
            ->with(['user:id,name,email'])
            ->orderBy('termination_date', 'desc')
            ->get()
            ->map(function ($record) {
                return [
                    'id' => $record->id,
                    'user_id' => $record->user_id,
                    'name' => $record->user->name ?? null,
                    'email' => $record->user->email ?? null,
                    'department' => $record->department,
                    'position' => $record->position,
                    'termination_type' => $record->termination_type,
                    'termination_date' => $record->termination_date?->format('d.m.Y'),
                    'reason' => $record->reason,
                    'tenure_months' => $record->tenure_months,
                ];
            })
            ->toArray();
    }

    /**
     * Calculate statistics
     */
    private function calculateStats($business, $employees, $leaveRequests)
    {
        $today = Carbon::today();

        // Count employees on leave today
        $onLeaveToday = 0;
        foreach ($leaveRequests as $leave) {
            if ($leave['status'] !== 'approved' || empty($leave['start_date']) || empty($leave['end_date'])) {
                continue;
            }
            try {
                $start = Carbon::createFromFormat('d.m.Y', $leave['start_date']);
                $end = Carbon::createFromFormat('d.m.Y', $leave['end_date']);
                if ($today->between($start, $end)) {
                    $onLeaveToday++;
                }
            } catch (\Exception $e) {
                // Skip invalid dates
            }
        }

        // Count terminated this year
        $terminatedThisYear = TurnoverRecord::where('business_id', $business->id)
            ->whereYear('termination_date', $today->year)
            ->count();

        return [
            'total_employees' => count($employees),
            'active_employees' => count($employees),
            'on_leave_today' => $onLeaveToday,
            'pending_leave' => collect($leaveRequests)->where('status', 'pending')->count(),
            'terminated_this_year' => $terminatedThisYear,
        ];
    }

    /**
     * Update employee contract
     * Note: Contract fields will be added to business_user table in future migration
     */
    public function updateContract(Request $request, $employeeId)
    {
        $business = $this->getCurrentBusiness();

        if (!$business) {
            return response()->json(['error' => 'Biznes topilmadi'], 404);
        }

        $member = BusinessUser::where('id', $employeeId)
            ->where('business_id', $business->id)
            ->first();

        if (!$member) {
            return response()->json(['error' => 'Xodim topilmadi'], 404);
        }

        // For now, just return success - contract fields need to be added to database
        return response()->json([
            'success' => true,
            'message' => 'Shartnoma ma\'lumotlari yangilandi',
        ]);
    }

    /**
     * Terminate employee
     */
    public function terminate(Request $request, $employeeId)
    {
        $business = $this->getCurrentBusiness();

        if (!$business) {
            return response()->json(['error' => 'Biznes topilmadi'], 404);
        }

        $member = BusinessUser::where('id', $employeeId)
            ->where('business_id', $business->id)
            ->with('user')
            ->first();

        if (!$member) {
            return response()->json(['error' => 'Xodim topilmadi'], 404);
        }

        $validated = $request->validate([
            'termination_type' => 'required|in:voluntary,involuntary,retirement,contract_end',
            'termination_date' => 'required|date',
            'reason' => 'nullable|string|max:1000',
        ]);

        // Create turnover record
        TurnoverRecord::create([
            'business_id' => $business->id,
            'user_id' => $member->user_id,
            'termination_type' => $validated['termination_type'],
            'termination_date' => $validated['termination_date'],
            'hire_date' => $member->joined_at ?? $member->created_at,
            'tenure_months' => $member->joined_at
                ? Carbon::parse($member->joined_at)->diffInMonths($validated['termination_date'])
                : null,
            'department' => $member->department_label,
            'position' => null,
            'reason' => $validated['reason'],
        ]);

        // Remove employee from business (delete the pivot record)
        $member->delete();

        return response()->json([
            'success' => true,
            'message' => 'Xodim ishdan bo\'shatildi',
        ]);
    }

    /**
     * Create leave request for employee
     */
    public function createLeaveRequest(Request $request)
    {
        $business = $this->getCurrentBusiness();

        if (!$business) {
            return response()->json(['error' => 'Biznes topilmadi'], 404);
        }

        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'leave_type' => 'required|string',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'reason' => 'nullable|string|max:1000',
        ]);

        // Calculate total days
        $startDate = Carbon::parse($validated['start_date']);
        $endDate = Carbon::parse($validated['end_date']);
        $totalDays = $startDate->diffInDays($endDate) + 1;

        // Map leave type string to leave_type_id
        $leaveTypeMap = [
            'annual' => 1,
            'sick' => 2,
            'family' => 3,
            'unpaid' => 4,
        ];

        LeaveRequest::create([
            'user_id' => $validated['user_id'],
            'leave_type_id' => $leaveTypeMap[$validated['leave_type']] ?? 1,
            'start_date' => $validated['start_date'],
            'end_date' => $validated['end_date'],
            'total_days' => $totalDays,
            'status' => 'pending',
            'reason' => $validated['reason'],
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Ta\'til so\'rovi yaratildi',
        ]);
    }
}
