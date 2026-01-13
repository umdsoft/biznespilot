<?php

namespace App\Models;

use App\Traits\BelongsToBusiness;
use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AttendanceSummary extends Model
{
    use BelongsToBusiness, HasUuid;

    protected $fillable = [
        'business_id',
        'user_id',
        'year',
        'month',
        'total_working_days',
        'present_days',
        'absent_days',
        'late_days',
        'half_days',
        'wfh_days',
        'leave_days',
        'total_work_hours',
        'attendance_percentage',
    ];

    protected $casts = [
        'total_work_hours' => 'decimal:2',
        'attendance_percentage' => 'decimal:2',
    ];

    // ==================== Relationships ====================

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // ==================== Scopes ====================

    public function scopeForUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeForMonth($query, $year, $month)
    {
        return $query->where('year', $year)->where('month', $month);
    }

    public function scopeThisMonth($query)
    {
        return $query->where('year', now()->year)
            ->where('month', now()->month);
    }

    // ==================== Methods ====================

    /**
     * Calculate and update summary from attendance records
     */
    public static function calculateForMonth($businessId, $userId, $year, $month): self
    {
        $records = AttendanceRecord::where('business_id', $businessId)
            ->where('user_id', $userId)
            ->whereYear('date', $year)
            ->whereMonth('date', $month)
            ->get();

        $totalWorkingDays = $records->count();
        $presentDays = $records->where('status', 'present')->count();
        $absentDays = $records->where('status', 'absent')->count();
        $lateDays = $records->where('status', 'late')->count();
        $halfDays = $records->where('status', 'half_day')->count();
        $wfhDays = $records->where('status', 'wfh')->count();
        $leaveDays = $records->where('status', 'leave')->count();
        $totalWorkHours = $records->sum('work_hours');

        $attendancePercentage = $totalWorkingDays > 0
            ? round((($presentDays + $lateDays + $wfhDays) / $totalWorkingDays) * 100, 2)
            : 0;

        return self::updateOrCreate(
            [
                'business_id' => $businessId,
                'user_id' => $userId,
                'year' => $year,
                'month' => $month,
            ],
            [
                'total_working_days' => $totalWorkingDays,
                'present_days' => $presentDays,
                'absent_days' => $absentDays,
                'late_days' => $lateDays,
                'half_days' => $halfDays,
                'wfh_days' => $wfhDays,
                'leave_days' => $leaveDays,
                'total_work_hours' => $totalWorkHours,
                'attendance_percentage' => $attendancePercentage,
            ]
        );
    }

    /**
     * Get monthly summary for user
     */
    public static function getMonthSummary($businessId, $userId, $year, $month): ?self
    {
        return self::where('business_id', $businessId)
            ->where('user_id', $userId)
            ->where('year', $year)
            ->where('month', $month)
            ->first();
    }

    /**
     * Get or create summary for current month
     */
    public static function getOrCreateThisMonth($businessId, $userId): self
    {
        return self::calculateForMonth($businessId, $userId, now()->year, now()->month);
    }
}
