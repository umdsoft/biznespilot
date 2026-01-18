<?php

namespace App\Http\Controllers\HR;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Traits\HasCurrentBusiness;
use App\Models\LeaveBalance;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class LeaveController extends Controller
{
    use HasCurrentBusiness;

    /**
     * Display leave requests
     */
    public function index(Request $request)
    {
        $business = $this->getCurrentBusiness();

        if (! $business) {
            return redirect()->route('login');
        }

        $userId = $request->get('user_id', Auth::id());
        $status = $request->get('status');
        $year = $request->get('year', now()->year);

        // Get leave requests
        $query = LeaveRequest::where('business_id', $business->id)
            ->where('user_id', $userId)
            ->with(['leaveType', 'approver']);

        if ($status) {
            $query->where('status', $status);
        }

        $query->whereYear('start_date', $year);

        $requests = $query->orderBy('created_at', 'desc')
            ->get()
            ->map(fn ($request) => [
                'id' => $request->id,
                'leave_type' => [
                    'id' => $request->leaveType->id,
                    'name' => $request->leaveType->name,
                    'code' => $request->leaveType->code,
                ],
                'start_date' => $request->start_date->format('Y-m-d'),
                'start_date_formatted' => $request->start_date->format('d.m.Y'),
                'end_date' => $request->end_date->format('Y-m-d'),
                'end_date_formatted' => $request->end_date->format('d.m.Y'),
                'total_days' => $request->total_days,
                'reason' => $request->reason,
                'notes' => $request->notes,
                'status' => $request->status,
                'status_label' => $request->status_label,
                'status_color' => $request->status_color,
                'approved_by' => $request->approver ? $request->approver->name : null,
                'approved_at' => $request->approved_at?->format('d.m.Y H:i'),
                'rejection_reason' => $request->rejection_reason,
                'is_upcoming' => $request->is_upcoming,
                'is_active' => $request->is_active,
            ]);

        // Get leave balances
        $balances = LeaveBalance::where('business_id', $business->id)
            ->where('user_id', $userId)
            ->where('year', $year)
            ->with('leaveType')
            ->get()
            ->map(fn ($balance) => [
                'id' => $balance->id,
                'leave_type' => [
                    'id' => $balance->leaveType->id,
                    'name' => $balance->leaveType->name,
                    'code' => $balance->leaveType->code,
                ],
                'total_days' => $balance->total_days,
                'used_days' => $balance->used_days,
                'pending_days' => $balance->pending_days,
                'available_days' => $balance->available_days,
                'carried_forward' => $balance->carried_forward,
            ]);

        // Get active leave types
        $leaveTypes = LeaveType::where('business_id', $business->id)
            ->active()
            ->get()
            ->map(fn ($type) => [
                'id' => $type->id,
                'name' => $type->name,
                'code' => $type->code,
                'description' => $type->description,
                'notice_days' => $type->notice_days,
                'max_consecutive_days' => $type->max_consecutive_days,
            ]);

        return Inertia::render('HR/Leave/Index', [
            'requests' => $requests,
            'balances' => $balances,
            'leaveTypes' => $leaveTypes,
            'selectedUserId' => $userId,
            'selectedStatus' => $status,
            'selectedYear' => $year,
        ]);
    }

    /**
     * Show pending approvals
     */
    public function approvals(Request $request)
    {
        $business = $this->getCurrentBusiness();

        if (! $business) {
            return redirect()->route('login');
        }

        // Get all pending requests for this business
        $requests = LeaveRequest::where('business_id', $business->id)
            ->pending()
            ->with(['user', 'leaveType'])
            ->orderBy('created_at', 'asc')
            ->get()
            ->map(fn ($request) => [
                'id' => $request->id,
                'user' => [
                    'id' => $request->user->id,
                    'name' => $request->user->name,
                ],
                'leave_type' => [
                    'id' => $request->leaveType->id,
                    'name' => $request->leaveType->name,
                    'code' => $request->leaveType->code,
                ],
                'start_date' => $request->start_date->format('Y-m-d'),
                'start_date_formatted' => $request->start_date->format('d.m.Y'),
                'end_date' => $request->end_date->format('Y-m-d'),
                'end_date_formatted' => $request->end_date->format('d.m.Y'),
                'total_days' => $request->total_days,
                'reason' => $request->reason,
                'notes' => $request->notes,
                'emergency_contact' => $request->emergency_contact,
                'emergency_phone' => $request->emergency_phone,
                'created_at' => $request->created_at->format('d.m.Y H:i'),
            ]);

        return Inertia::render('HR/Leave/Approvals', [
            'requests' => $requests,
        ]);
    }

    /**
     * Store leave request
     */
    public function store(Request $request)
    {
        $business = $this->getCurrentBusiness();

        if (! $business) {
            return back()->with('error', 'Biznes topilmadi');
        }

        $validated = $request->validate([
            'leave_type_id' => 'required|exists:leave_types,id',
            'start_date' => 'required|date|after_or_equal:today',
            'end_date' => 'required|date|after_or_equal:start_date',
            'reason' => 'required|string|max:1000',
            'notes' => 'nullable|string|max:500',
            'emergency_contact' => 'nullable|string|max:255',
            'emergency_phone' => 'nullable|string|max:50',
        ]);

        $startDate = Carbon::parse($validated['start_date']);
        $endDate = Carbon::parse($validated['end_date']);

        // Calculate working days
        $totalDays = LeaveRequest::calculateWorkingDays($startDate, $endDate);

        // Check for overlapping leaves
        if (LeaveRequest::hasOverlap($business->id, Auth::id(), $startDate, $endDate)) {
            return back()->with('error', 'Shu sanalar uchun allaqachon ta\'til so\'rovi mavjud');
        }

        // Check if sufficient balance
        $balance = LeaveBalance::getOrCreate(
            $business->id,
            Auth::id(),
            $validated['leave_type_id'],
            $startDate->year
        );

        if ($balance->available_days < $totalDays) {
            return back()->with('error', 'Yetarli ta\'til kunlari yo\'q');
        }

        // Create leave request
        $leaveRequest = LeaveRequest::create([
            'business_id' => $business->id,
            'user_id' => Auth::id(),
            'leave_type_id' => $validated['leave_type_id'],
            'start_date' => $startDate,
            'end_date' => $endDate,
            'total_days' => $totalDays,
            'reason' => $validated['reason'],
            'notes' => $validated['notes'] ?? null,
            'emergency_contact' => $validated['emergency_contact'] ?? null,
            'emergency_phone' => $validated['emergency_phone'] ?? null,
            'status' => 'pending',
        ]);

        // Add pending days to balance
        $balance->addPendingDays($totalDays);

        return back()->with('success', 'Ta\'til so\'rovi yuborildi');
    }

    /**
     * Approve leave request
     */
    public function approve(Request $request, LeaveRequest $leaveRequest)
    {
        $business = $this->getCurrentBusiness();

        if (! $business || $leaveRequest->business_id !== $business->id) {
            return back()->with('error', 'Ruxsat yo\'q');
        }

        $validated = $request->validate([
            'comments' => 'nullable|string|max:500',
        ]);

        if ($leaveRequest->approve(Auth::id(), $validated['comments'] ?? null)) {
            return back()->with('success', 'Ta\'til so\'rovi tasdiqlandi');
        }

        return back()->with('error', 'Ta\'til so\'rovini tasdiqlashda xatolik');
    }

    /**
     * Reject leave request
     */
    public function reject(Request $request, LeaveRequest $leaveRequest)
    {
        $business = $this->getCurrentBusiness();

        if (! $business || $leaveRequest->business_id !== $business->id) {
            return back()->with('error', 'Ruxsat yo\'q');
        }

        $validated = $request->validate([
            'reason' => 'required|string|max:500',
            'comments' => 'nullable|string|max:500',
        ]);

        if ($leaveRequest->reject(Auth::id(), $validated['reason'], $validated['comments'] ?? null)) {
            return back()->with('success', 'Ta\'til so\'rovi rad etildi');
        }

        return back()->with('error', 'Ta\'til so\'rovini rad etishda xatolik');
    }

    /**
     * Cancel leave request
     */
    public function cancel(LeaveRequest $leaveRequest)
    {
        $business = $this->getCurrentBusiness();

        if (! $business || $leaveRequest->business_id !== $business->id) {
            return back()->with('error', 'Ruxsat yo\'q');
        }

        // Only owner can cancel their own request
        if ($leaveRequest->user_id !== Auth::id()) {
            return back()->with('error', 'Faqat o\'z so\'rovingizni bekor qila olasiz');
        }

        if ($leaveRequest->cancel()) {
            return back()->with('success', 'Ta\'til so\'rovi bekor qilindi');
        }

        return back()->with('error', 'Ta\'til so\'rovini bekor qilishda xatolik');
    }

    /**
     * Calendar view
     */
    public function calendar(Request $request)
    {
        $business = $this->getCurrentBusiness();

        if (! $business) {
            return redirect()->route('login');
        }

        $month = $request->get('month', now()->month);
        $year = $request->get('year', now()->year);

        $startDate = Carbon::create($year, $month, 1)->startOfMonth();
        $endDate = $startDate->copy()->endOfMonth();

        // Get all approved leave requests for this period
        $requests = LeaveRequest::where('business_id', $business->id)
            ->approved()
            ->dateRange($startDate, $endDate)
            ->with(['user', 'leaveType'])
            ->get()
            ->map(fn ($request) => [
                'id' => $request->id,
                'user_name' => $request->user->name,
                'user_id' => $request->user_id,
                'leave_type' => $request->leaveType->name,
                'leave_type_code' => $request->leaveType->code,
                'start_date' => $request->start_date->format('Y-m-d'),
                'end_date' => $request->end_date->format('Y-m-d'),
                'total_days' => $request->total_days,
            ]);

        return Inertia::render('HR/Leave/Calendar', [
            'requests' => $requests,
            'month' => $month,
            'year' => $year,
        ]);
    }
}
