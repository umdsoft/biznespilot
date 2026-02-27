<?php

namespace App\Services\Bot\Queue;

use App\Models\Bot\Queue\QueueBooking;
use App\Models\Bot\Queue\QueueService;
use App\Models\Bot\Queue\QueueTimeSlot;
use Illuminate\Support\Facades\DB;

class QueueBookingService
{
    public function __construct(
        private QueueSlotService $slotService,
        private QueueTrackingService $trackingService,
    ) {}

    /**
     * Create a new queue booking.
     */
    public function createBooking(string $businessId, array $data): QueueBooking
    {
        return DB::transaction(function () use ($businessId, $data) {
            // Find the available time slot
            $slot = QueueTimeSlot::forBranch($data['branch_id'])
                ->forDate($data['date'])
                ->available()
                ->where('start_time', $data['start_time'])
                ->when(! empty($data['specialist_id']), function ($q) use ($data) {
                    $q->forSpecialist($data['specialist_id']);
                })
                ->first();

            if (! $slot) {
                throw new \RuntimeException('Tanlangan vaqt bandi mavjud emas.');
            }

            // Get service for pricing
            $service = QueueService::findOrFail($data['service_id']);

            // Calculate queue number (daily sequential for branch/date)
            $queueNumber = QueueBooking::where('branch_id', $data['branch_id'])
                ->whereDate('date', $data['date'])
                ->max('queue_number') ?? 0;
            $queueNumber++;

            // Count people ahead
            $peopleAhead = QueueBooking::where('branch_id', $data['branch_id'])
                ->whereDate('date', $data['date'])
                ->whereIn('status', QueueBooking::ACTIVE_STATUSES)
                ->count();

            $booking = QueueBooking::create([
                'business_id' => $businessId,
                'booking_number' => QueueBooking::generateBookingNumber(),
                'telegram_user_id' => $data['telegram_user_id'],
                'customer_name' => $data['customer_name'],
                'customer_phone' => $data['customer_phone'],
                'service_id' => $data['service_id'],
                'branch_id' => $data['branch_id'],
                'specialist_id' => $data['specialist_id'] ?? $slot->specialist_id,
                'date' => $data['date'],
                'start_time' => $slot->start_time,
                'end_time' => $slot->end_time,
                'queue_number' => $queueNumber,
                'status' => QueueBooking::STATUS_PENDING,
                'people_ahead' => $peopleAhead,
                'estimated_wait' => (int) round($peopleAhead * (($service->duration_min + $service->duration_max) / 2)),
                'price' => $service->price,
                'payment_method' => $data['payment_method'] ?? null,
                'notes' => $data['notes'] ?? null,
            ]);

            // Book the time slot
            $this->slotService->bookSlot($slot, $booking);

            return $booking->load(['service', 'branch', 'specialist']);
        });
    }

    /**
     * Cancel a booking.
     */
    public function cancelBooking(QueueBooking $booking, ?string $reason = null): bool
    {
        if (! $booking->canTransitionTo(QueueBooking::STATUS_CANCELLED)) {
            return false;
        }

        // Release booked time slot
        $this->slotService->releaseSlot($booking);

        $booking->cancel_reason = $reason;

        return $booking->transitionTo(QueueBooking::STATUS_CANCELLED);
    }

    /**
     * Rate a completed booking.
     */
    public function rateBooking(QueueBooking $booking, int $rating, ?string $review = null): bool
    {
        if ($booking->status !== QueueBooking::STATUS_COMPLETED) {
            return false;
        }

        $booking->rating = $rating;
        $booking->review = $review;
        $booking->save();

        // Update specialist rating
        if ($booking->specialist_id) {
            $specialist = $booking->specialist;
            if ($specialist) {
                $newCount = $specialist->rating_count + 1;
                $newAvg = (($specialist->rating_avg * $specialist->rating_count) + $rating) / $newCount;

                $specialist->update([
                    'rating_avg' => round($newAvg, 2),
                    'rating_count' => $newCount,
                ]);
            }
        }

        return true;
    }
}
