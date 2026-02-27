<?php

namespace App\Services\Bot\Queue;

use App\Models\Bot\Queue\QueueBooking;

class QueueTrackingService
{
    /**
     * Get the current position of a booking in the queue.
     */
    public function getPosition(QueueBooking $booking): int
    {
        return QueueBooking::where('branch_id', $booking->branch_id)
            ->where('date', $booking->date)
            ->whereIn('status', [QueueBooking::STATUS_CONFIRMED, QueueBooking::STATUS_IN_PROGRESS])
            ->where('queue_number', '<', $booking->queue_number)
            ->count();
    }

    /**
     * Get estimated wait time in minutes.
     */
    public function getEstimatedWait(QueueBooking $booking): int
    {
        $position = $this->getPosition($booking);

        $service = $booking->relationLoaded('service')
            ? $booking->service
            : $booking->service()->first();

        if (! $service) {
            return $position * 15; // default 15 min fallback
        }

        $avgDuration = ($service->duration_min + $service->duration_max) / 2;

        return (int) round($position * $avgDuration);
    }

    /**
     * Advance the queue: transition next confirmed booking to in_progress.
     */
    public function advanceQueue(string $branchId): ?QueueBooking
    {
        $today = now()->toDateString();

        $nextBooking = QueueBooking::where('branch_id', $branchId)
            ->where('date', $today)
            ->byStatus(QueueBooking::STATUS_CONFIRMED)
            ->orderBy('queue_number')
            ->first();

        if (! $nextBooking) {
            return null;
        }

        $nextBooking->transitionTo(QueueBooking::STATUS_IN_PROGRESS);

        // Update people_ahead for remaining confirmed bookings
        $remainingBookings = QueueBooking::where('branch_id', $branchId)
            ->where('date', $today)
            ->byStatus(QueueBooking::STATUS_CONFIRMED)
            ->orderBy('queue_number')
            ->get();

        foreach ($remainingBookings as $index => $booking) {
            $booking->update([
                'people_ahead' => $index,
                'estimated_wait' => $this->getEstimatedWait($booking),
            ]);
        }

        return $nextBooking->load(['service', 'branch', 'specialist']);
    }
}
