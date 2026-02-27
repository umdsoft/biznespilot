<?php

namespace App\Observers\Bot;

use App\Models\Bot\Queue\QueueBooking;
use App\Services\Bot\Queue\QueueStatsService;
use Illuminate\Support\Facades\Log;

class QueueBookingObserver
{
    public function creating(QueueBooking $booking): void
    {
        if (! $booking->booking_number) {
            $booking->booking_number = QueueBooking::generateBookingNumber();
        }
    }

    public function created(QueueBooking $booking): void
    {
        Log::info("Queue booking created: {$booking->booking_number}", [
            'business_id' => $booking->business_id,
            'service_id' => $booking->service_id,
            'branch_id' => $booking->branch_id,
        ]);
    }

    public function updated(QueueBooking $booking): void
    {
        if (! $booking->wasChanged('status')) {
            return;
        }

        $newStatus = $booking->status;

        Log::info("Queue booking status changed: {$booking->booking_number} → {$newStatus}");

        if ($newStatus === QueueBooking::STATUS_COMPLETED) {
            try {
                app(QueueStatsService::class)
                    ->calculateDailyStats($booking->business_id, $booking->date->toDateString());
            } catch (\Throwable $e) {
                Log::error("Failed to update queue stats: {$e->getMessage()}");
            }
        }
    }
}
