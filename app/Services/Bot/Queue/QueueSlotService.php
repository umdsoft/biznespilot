<?php

namespace App\Services\Bot\Queue;

use App\Models\Bot\Queue\QueueBooking;
use App\Models\Bot\Queue\QueueBranch;
use App\Models\Bot\Queue\QueueTimeSlot;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class QueueSlotService
{
    /**
     * Generate time slots for a specific date and branch.
     */
    public function generateSlotsForDate(QueueBranch $branch, string $date): void
    {
        $dayOfWeek = strtolower(Carbon::parse($date)->format('l'));
        $workingHours = $branch->working_hours[$dayOfWeek] ?? null;

        if (! $workingHours || empty($workingHours['from']) || empty($workingHours['to'])) {
            return;
        }

        $lunchBreak = $branch->lunch_break;
        $lunchFrom = $lunchBreak['from'] ?? null;
        $lunchTo = $lunchBreak['to'] ?? null;

        $slotDuration = $branch->slot_duration ?? 30;
        $specialists = $branch->specialists()->where('is_active', true)->get();
        $hasSpecialists = $specialists->isNotEmpty();

        $start = Carbon::parse("{$date} {$workingHours['from']}");
        $end = Carbon::parse("{$date} {$workingHours['to']}");

        // Delete existing generated slots for this date (only available ones)
        QueueTimeSlot::forBranch($branch->id)
            ->forDate($date)
            ->whereIn('status', ['available', 'lunch'])
            ->whereNull('booking_id')
            ->delete();

        $current = $start->copy();

        while ($current->copy()->addMinutes($slotDuration)->lte($end)) {
            $slotEnd = $current->copy()->addMinutes($slotDuration);
            $slotStartTime = $current->format('H:i');
            $slotEndTime = $slotEnd->format('H:i');

            // Check if slot falls within lunch break
            $isLunch = false;
            if ($lunchFrom && $lunchTo) {
                $lunchStart = Carbon::parse("{$date} {$lunchFrom}");
                $lunchEnd = Carbon::parse("{$date} {$lunchTo}");
                if ($current->lt($lunchEnd) && $slotEnd->gt($lunchStart)) {
                    $isLunch = true;
                }
            }

            $status = $isLunch ? 'lunch' : 'available';

            if ($hasSpecialists) {
                foreach ($specialists as $specialist) {
                    QueueTimeSlot::create([
                        'branch_id' => $branch->id,
                        'specialist_id' => $specialist->id,
                        'date' => $date,
                        'start_time' => $slotStartTime,
                        'end_time' => $slotEndTime,
                        'status' => $status,
                    ]);
                }
            } else {
                QueueTimeSlot::create([
                    'branch_id' => $branch->id,
                    'specialist_id' => null,
                    'date' => $date,
                    'start_time' => $slotStartTime,
                    'end_time' => $slotEndTime,
                    'status' => $status,
                ]);
            }

            $current->addMinutes($slotDuration);
        }
    }

    /**
     * Get available time slots for a branch on a specific date.
     */
    public function getAvailableSlots(
        string $branchId,
        string $serviceId,
        string $date,
        ?string $specialistId = null,
    ): Collection {
        $query = QueueTimeSlot::forBranch($branchId)
            ->forDate($date)
            ->available();

        if ($specialistId) {
            $query->forSpecialist($specialistId);
        }

        // If date is today, only return future slots
        if (Carbon::parse($date)->isToday()) {
            $now = now()->format('H:i');
            $query->where('start_time', '>', $now);
        }

        return $query->orderBy('start_time')->get();
    }

    /**
     * Book a specific time slot for a booking.
     */
    public function bookSlot(QueueTimeSlot $slot, QueueBooking $booking): void
    {
        $slot->status = 'booked';
        $slot->booking_id = $booking->id;
        $slot->save();
    }

    /**
     * Release time slots associated with a booking.
     */
    public function releaseSlot(QueueBooking $booking): void
    {
        QueueTimeSlot::where('booking_id', $booking->id)
            ->update([
                'status' => 'available',
                'booking_id' => null,
            ]);
    }
}
