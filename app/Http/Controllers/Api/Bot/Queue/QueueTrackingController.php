<?php

namespace App\Http\Controllers\Api\Bot\Queue;

use App\Http\Controllers\Controller;
use App\Models\Bot\Queue\QueueBooking;
use App\Services\Bot\Queue\QueueTrackingService;
use Illuminate\Http\JsonResponse;

class QueueTrackingController extends Controller
{
    public function __construct(
        private QueueTrackingService $trackingService,
    ) {}

    public function position(QueueBooking $booking): JsonResponse
    {
        if (! $booking->isActive()) {
            return response()->json([
                'position' => null,
                'estimated_wait' => null,
                'status' => $booking->status,
            ]);
        }

        $position = $this->trackingService->getPosition($booking);
        $estimatedWait = $this->trackingService->getEstimatedWait($booking);

        return response()->json([
            'position' => $position,
            'estimated_wait' => $estimatedWait,
            'status' => $booking->status,
            'queue_number' => $booking->queue_number,
        ]);
    }
}
