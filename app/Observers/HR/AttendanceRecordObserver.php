<?php

namespace App\Observers\HR;

use App\Models\AttendanceRecord;
use App\Services\HR\EngagementService;
use App\Services\HR\HROrchestrator;
use App\Services\HR\RetentionService;
use Illuminate\Support\Facades\Log;

/**
 * AttendanceRecordObserver - Davomat o'zgarishlarini kuzatish
 *
 * Avtomatik harakatlar:
 * - Kech kelish/erta ketish naqshlarini aniqlash
 * - Engagement ball yangilash
 * - Flight risk ga ta'sir qilish
 */
class AttendanceRecordObserver
{
    public function __construct(
        protected EngagementService $engagementService,
        protected RetentionService $retentionService
    ) {}

    /**
     * Davomat yaratilganda
     */
    public function created(AttendanceRecord $record): void
    {
        Log::debug('AttendanceRecordObserver: Created', [
            'record_id' => $record->id,
            'user_id' => $record->user_id,
            'status' => $record->status,
        ]);

        // Agar absent yoki late bo'lsa - engagement ta'sir
        if (in_array($record->status, ['absent', 'late'])) {
            $this->updateEngagementForAttendance($record, 'negative');
        }
    }

    /**
     * Davomat yangilanganda
     */
    public function updated(AttendanceRecord $record): void
    {
        // Status o'zgarganda
        if ($record->isDirty('status')) {
            $oldStatus = $record->getOriginal('status');
            $newStatus = $record->status;

            Log::info('AttendanceRecordObserver: Status changed', [
                'record_id' => $record->id,
                'user_id' => $record->user_id,
                'old_status' => $oldStatus,
                'new_status' => $newStatus,
            ]);

            // Kechikish yoki yo'qlik bo'lsa
            if (in_array($newStatus, ['absent', 'late'])) {
                $this->updateEngagementForAttendance($record, 'negative');
                $this->checkAttendancePattern($record);
            }
        }

        // Check-out bo'lganda - ish soatlarini tahlil qilish
        if ($record->isDirty('check_out') && $record->check_out) {
            $this->analyzeWorkHours($record);
        }
    }

    /**
     * Engagement ball yangilash
     */
    protected function updateEngagementForAttendance(AttendanceRecord $record, string $type): void
    {
        try {
            // Davomat asosida engagement ballini yangilash
            $business = $record->business;
            $employee = $record->user;

            if (!$business || !$employee) {
                return;
            }

            // Oxirgi 30 kunlik davomatni olish
            $recentRecords = AttendanceRecord::where('business_id', $business->id)
                ->where('user_id', $employee->id)
                ->where('date', '>=', now()->subDays(30))
                ->get();

            $totalRecords = $recentRecords->count();
            $lateCount = $recentRecords->where('status', 'late')->count();
            $absentCount = $recentRecords->where('status', 'absent')->count();

            $attendanceData = [
                'late_count' => $lateCount,
                'absent_count' => $absentCount,
                'present_count' => $recentRecords->where('status', 'present')->count(),
            ];

            $this->engagementService->updateAttendanceScore($employee, $business, $attendanceData);

        } catch (\Exception $e) {
            Log::error('AttendanceRecordObserver: Failed to update engagement', [
                'error' => $e->getMessage(),
                'record_id' => $record->id,
            ]);
        }
    }

    /**
     * Davomat naqshlarini tekshirish
     */
    protected function checkAttendancePattern(AttendanceRecord $record): void
    {
        try {
            $business = $record->business;
            $employee = $record->user;

            if (!$business || !$employee) {
                return;
            }

            // Oxirgi 30 kunlik davomatni tahlil qilish
            $recentRecords = AttendanceRecord::where('business_id', $business->id)
                ->where('user_id', $employee->id)
                ->where('date', '>=', now()->subDays(30))
                ->orderBy('date')
                ->get();

            if ($recentRecords->count() < 5) {
                return; // Yetarli ma'lumot yo'q
            }

            // Naqshlarni aniqlash
            $lateCount = $recentRecords->where('status', 'late')->count();
            $absentCount = $recentRecords->where('status', 'absent')->count();

            // Dushanba sindromi
            $mondayAbsent = $recentRecords->filter(function ($r) {
                return $r->date->dayOfWeek === 1 && $r->status === 'absent';
            })->count();

            // Juma sindromi
            $fridayAbsent = $recentRecords->filter(function ($r) {
                return $r->date->dayOfWeek === 5 && $r->status === 'absent';
            })->count();

            $totalDays = $recentRecords->count();
            $latePercentage = ($lateCount / $totalDays) * 100;
            $absentPercentage = ($absentCount / $totalDays) * 100;

            // Naqsh aniqlandi bo'lsa - HROrchestrator ga xabar berish
            $pattern = null;
            $severity = 0;

            if ($latePercentage > 30) {
                $pattern = 'late_arrivals';
                $severity = min(10, $latePercentage / 10);
            } elseif ($absentPercentage > 20) {
                $pattern = 'frequent_absences';
                $severity = min(10, $absentPercentage / 5);
            } elseif ($mondayAbsent >= 2) {
                $pattern = 'monday_syndrome';
                $severity = 6;
            } elseif ($fridayAbsent >= 2) {
                $pattern = 'friday_syndrome';
                $severity = 5;
            }

            if ($pattern && $severity >= 5) {
                // Flight risk ni yangilash
                $this->retentionService->increaseFlightRisk(
                    $employee,
                    $business,
                    "attendance_pattern_{$pattern}",
                    $severity / 2
                );

                Log::info('AttendanceRecordObserver: Pattern detected', [
                    'user_id' => $employee->id,
                    'pattern' => $pattern,
                    'severity' => $severity,
                ]);
            }

        } catch (\Exception $e) {
            Log::error('AttendanceRecordObserver: Failed to check pattern', [
                'error' => $e->getMessage(),
                'record_id' => $record->id,
            ]);
        }
    }

    /**
     * Ish soatlarini tahlil qilish
     */
    protected function analyzeWorkHours(AttendanceRecord $record): void
    {
        if (!$record->work_hours) {
            return;
        }

        // Kunlik me'yordan kam ishlagan bo'lsa
        $standardHours = 8; // TODO: Settings dan olish

        if ($record->work_hours < ($standardHours * 0.75)) {
            Log::info('AttendanceRecordObserver: Short work day detected', [
                'record_id' => $record->id,
                'work_hours' => $record->work_hours,
                'standard' => $standardHours,
            ]);
        }
    }
}
