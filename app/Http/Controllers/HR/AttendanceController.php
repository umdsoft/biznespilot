<?php

namespace App\Http\Controllers\HR;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Traits\HasCurrentBusiness;
use App\Models\AttendanceRecord;
use App\Models\AttendanceSetting;
use App\Models\AttendanceSummary;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class AttendanceController extends Controller
{
    use HasCurrentBusiness;

    /**
     * Display attendance records
     */
    public function index(Request $request)
    {
        $business = $this->getCurrentBusiness();

        if (! $business) {
            return redirect()->route('login');
        }

        $userId = $request->get('user_id', Auth::id());
        $date = $request->get('date', today()->format('Y-m-d'));
        $view = $request->get('view', 'daily'); // daily, weekly, monthly

        // Get team members for filter
        $teamMembers = $business->teamMembers()
            ->select('users.id', 'users.name', 'business_user.department')
            ->get()
            ->map(fn ($user) => [
                'id' => $user->id,
                'name' => $user->name,
                'department' => $user->pivot->department ?? null,
            ]);

        // Get attendance records based on view
        $records = $this->getAttendanceRecords($business->id, $userId, $date, $view);

        // Get summary for current month
        $summary = AttendanceSummary::getMonthSummary(
            $business->id,
            $userId,
            now()->year,
            now()->month
        );

        // Get today's attendance
        $todayAttendance = AttendanceRecord::getTodayAttendance($business->id, $userId);

        return Inertia::render('HR/Attendance/Index', [
            'records' => $records,
            'summary' => $summary,
            'todayAttendance' => $todayAttendance,
            'teamMembers' => $teamMembers,
            'selectedUserId' => $userId,
            'selectedDate' => $date,
            'view' => $view,
        ]);
    }

    /**
     * Get attendance records based on view type
     */
    protected function getAttendanceRecords($businessId, $userId, $date, $view)
    {
        $query = AttendanceRecord::where('business_id', $businessId)
            ->where('user_id', $userId)
            ->with('user:id,name');

        $carbonDate = Carbon::parse($date);

        switch ($view) {
            case 'weekly':
                $startOfWeek = $carbonDate->copy()->startOfWeek();
                $endOfWeek = $carbonDate->copy()->endOfWeek();
                $query->whereBetween('date', [$startOfWeek, $endOfWeek]);
                break;

            case 'monthly':
                $query->whereYear('date', $carbonDate->year)
                    ->whereMonth('date', $carbonDate->month);
                break;

            case 'daily':
            default:
                $query->whereDate('date', $carbonDate);
                break;
        }

        return $query->orderBy('date', 'desc')
            ->get()
            ->map(fn ($record) => [
                'id' => $record->id,
                'date' => $record->date->format('Y-m-d'),
                'date_formatted' => $record->date->format('d.m.Y'),
                'check_in' => $record->formatted_check_in,
                'check_out' => $record->formatted_check_out,
                'work_hours' => $record->work_hours,
                'status' => $record->status,
                'status_label' => $record->status_label,
                'status_color' => $record->status_color,
                'notes' => $record->notes,
                'location' => $record->location,
                'is_checked_in' => $record->is_checked_in,
                'is_checked_out' => $record->is_checked_out,
            ]);
    }

    /**
     * Check-in user
     */
    public function checkIn(Request $request)
    {
        $business = $this->getCurrentBusiness();

        if (! $business) {
            return back()->with('error', 'Biznes topilmadi');
        }

        $validated = $request->validate([
            'location' => 'nullable|string|max:255',
            'notes' => 'nullable|string|max:500',
        ]);

        // Check if already checked in today
        $todayAttendance = AttendanceRecord::getTodayAttendance($business->id, Auth::id());

        if ($todayAttendance && $todayAttendance->check_in) {
            return back()->with('error', 'Siz allaqachon check-in qilgansiz');
        }

        // Get or create today's attendance
        $attendance = AttendanceRecord::getOrCreateToday($business->id, Auth::id());

        // Check-in
        $attendance->checkIn(
            $validated['location'] ?? null,
            $request->ip()
        );

        if (isset($validated['notes'])) {
            $attendance->update(['notes' => $validated['notes']]);
        }

        return back()->with('success', 'Check-in muvaffaqiyatli!');
    }

    /**
     * Check-out user
     */
    public function checkOut(Request $request)
    {
        $business = $this->getCurrentBusiness();

        if (! $business) {
            return back()->with('error', 'Biznes topilmadi');
        }

        $validated = $request->validate([
            'notes' => 'nullable|string|max:500',
        ]);

        // Get today's attendance
        $attendance = AttendanceRecord::getTodayAttendance($business->id, Auth::id());

        if (! $attendance) {
            return back()->with('error', 'Bugun check-in qilmagansiz');
        }

        if ($attendance->check_out) {
            return back()->with('error', 'Siz allaqachon check-out qilgansiz');
        }

        // Check-out
        $attendance->checkOut();

        if (isset($validated['notes'])) {
            $attendance->update(['notes' => $validated['notes']]);
        }

        // Update monthly summary
        AttendanceSummary::calculateForMonth(
            $business->id,
            Auth::id(),
            now()->year,
            now()->month
        );

        return back()->with('success', 'Check-out muvaffaqiyatli!');
    }

    /**
     * Store manual attendance record (HR only)
     */
    public function store(Request $request)
    {
        $business = $this->getCurrentBusiness();

        if (! $business) {
            return back()->with('error', 'Biznes topilmadi');
        }

        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'date' => 'required|date',
            'check_in' => 'nullable|date_format:H:i',
            'check_out' => 'nullable|date_format:H:i',
            'status' => 'required|in:present,absent,late,half_day,wfh,leave',
            'notes' => 'nullable|string|max:500',
        ]);

        // Check if attendance already exists
        $exists = AttendanceRecord::where('business_id', $business->id)
            ->where('user_id', $validated['user_id'])
            ->where('date', $validated['date'])
            ->exists();

        if ($exists) {
            return back()->with('error', 'Bu sana uchun davomat yozuvi mavjud');
        }

        // Create attendance record
        $attendance = AttendanceRecord::create([
            'business_id' => $business->id,
            'user_id' => $validated['user_id'],
            'date' => $validated['date'],
            'check_in' => $validated['check_in'] ? Carbon::parse($validated['date'].' '.$validated['check_in']) : null,
            'check_out' => $validated['check_out'] ? Carbon::parse($validated['date'].' '.$validated['check_out']) : null,
            'status' => $validated['status'],
            'notes' => $validated['notes'] ?? null,
        ]);

        // Calculate work hours if both times provided
        if ($attendance->check_in && $attendance->check_out) {
            $attendance->update([
                'work_hours' => $attendance->calculateWorkHours(),
            ]);
        }

        // Update monthly summary
        $date = Carbon::parse($validated['date']);
        AttendanceSummary::calculateForMonth(
            $business->id,
            $validated['user_id'],
            $date->year,
            $date->month
        );

        return back()->with('success', 'Davomat yozuvi yaratildi');
    }

    /**
     * Update attendance record
     */
    public function update(Request $request, AttendanceRecord $attendance)
    {
        $business = $this->getCurrentBusiness();

        if (! $business || $attendance->business_id !== $business->id) {
            return back()->with('error', 'Ruxsat yo\'q');
        }

        $validated = $request->validate([
            'check_in' => 'nullable|date_format:H:i',
            'check_out' => 'nullable|date_format:H:i',
            'status' => 'sometimes|in:present,absent,late,half_day,wfh,leave',
            'notes' => 'nullable|string|max:500',
        ]);

        // Update check-in time
        if (isset($validated['check_in'])) {
            $attendance->update([
                'check_in' => Carbon::parse($attendance->date->format('Y-m-d').' '.$validated['check_in']),
            ]);
        }

        // Update check-out time
        if (isset($validated['check_out'])) {
            $attendance->update([
                'check_out' => Carbon::parse($attendance->date->format('Y-m-d').' '.$validated['check_out']),
            ]);
        }

        // Recalculate work hours if both times exist
        if ($attendance->check_in && $attendance->check_out) {
            $attendance->update([
                'work_hours' => $attendance->calculateWorkHours(),
            ]);
        }

        // Update other fields
        if (isset($validated['status'])) {
            $attendance->update(['status' => $validated['status']]);
        }

        if (isset($validated['notes'])) {
            $attendance->update(['notes' => $validated['notes']]);
        }

        // Update monthly summary
        AttendanceSummary::calculateForMonth(
            $business->id,
            $attendance->user_id,
            $attendance->date->year,
            $attendance->date->month
        );

        return back()->with('success', 'Davomat yozuvi yangilandi');
    }

    /**
     * Delete attendance record
     */
    public function destroy(AttendanceRecord $attendance)
    {
        $business = $this->getCurrentBusiness();

        if (! $business || $attendance->business_id !== $business->id) {
            return back()->with('error', 'Ruxsat yo\'q');
        }

        $userId = $attendance->user_id;
        $year = $attendance->date->year;
        $month = $attendance->date->month;

        $attendance->delete();

        // Update monthly summary
        AttendanceSummary::calculateForMonth($business->id, $userId, $year, $month);

        return back()->with('success', 'Davomat yozuvi o\'chirildi');
    }

    /**
     * Get monthly report
     */
    public function monthlyReport(Request $request)
    {
        $business = $this->getCurrentBusiness();

        if (! $business) {
            return response()->json(['error' => 'Biznes topilmadi'], 404);
        }

        $userId = $request->get('user_id', Auth::id());
        $year = $request->get('year', now()->year);
        $month = $request->get('month', now()->month);

        // Get or calculate summary
        $summary = AttendanceSummary::calculateForMonth($business->id, $userId, $year, $month);

        // Get all records for the month
        $records = AttendanceRecord::where('business_id', $business->id)
            ->where('user_id', $userId)
            ->whereYear('date', $year)
            ->whereMonth('date', $month)
            ->orderBy('date')
            ->get();

        return response()->json([
            'summary' => $summary,
            'records' => $records,
        ]);
    }

    /**
     * Get attendance settings
     */
    public function settings()
    {
        $business = $this->getCurrentBusiness();

        if (! $business) {
            return redirect()->route('login');
        }

        $settings = AttendanceSetting::getOrCreateForBusiness($business->id);

        return Inertia::render('HR/Attendance/Settings', [
            'settings' => $settings,
        ]);
    }

    /**
     * Update attendance settings
     */
    public function updateSettings(Request $request)
    {
        $business = $this->getCurrentBusiness();

        if (! $business) {
            return back()->with('error', 'Biznes topilmadi');
        }

        $validated = $request->validate([
            'work_start_time' => 'required|date_format:H:i',
            'work_end_time' => 'required|date_format:H:i',
            'work_hours_per_day' => 'required|integer|min:1|max:24',
            'late_threshold_minutes' => 'required|integer|min:0|max:120',
            'require_location' => 'boolean',
            'allow_remote_checkin' => 'boolean',
        ]);

        $settings = AttendanceSetting::getOrCreateForBusiness($business->id);
        $settings->update($validated);

        return back()->with('success', 'Sozlamalar yangilandi');
    }
}
