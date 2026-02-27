<?php

namespace App\Http\Controllers\Api\Bot\Queue;

use App\Http\Controllers\Controller;
use App\Http\Requests\Bot\Queue\StoreQueueBookingRequest;
use App\Http\Resources\Bot\Queue\QueueBookingResource;
use App\Models\Bot\Queue\QueueBooking;
use App\Services\Bot\Queue\QueueBookingService;
use App\Services\Bot\Queue\QueueTrackingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class QueueBookingController extends Controller
{
    public function __construct(
        private QueueBookingService $bookingService,
        private QueueTrackingService $trackingService,
    ) {}

    public function store(StoreQueueBookingRequest $request): JsonResponse
    {
        $businessId = $request->header('X-Business-Id');

        try {
            $booking = $this->bookingService->createBooking(
                $businessId,
                $request->validated(),
            );
        } catch (\RuntimeException $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 422);
        }

        return response()->json([
            'booking' => new QueueBookingResource($booking),
        ], 201);
    }

    public function index(Request $request): JsonResponse
    {
        $businessId = $request->header('X-Business-Id');
        $telegramUserId = $request->input('telegram_user_id');

        $bookings = QueueBooking::forBusiness($businessId)
            ->byUser($telegramUserId)
            ->with(['service', 'branch', 'specialist'])
            ->latest()
            ->paginate(20);

        return response()->json([
            'bookings' => QueueBookingResource::collection($bookings),
            'meta' => [
                'current_page' => $bookings->currentPage(),
                'last_page' => $bookings->lastPage(),
                'total' => $bookings->total(),
            ],
        ]);
    }

    public function show(QueueBooking $booking): JsonResponse
    {
        $booking->load(['service', 'branch', 'specialist']);

        $position = $booking->isActive()
            ? $this->trackingService->getPosition($booking)
            : null;

        $estimatedWait = $booking->isActive()
            ? $this->trackingService->getEstimatedWait($booking)
            : null;

        return response()->json([
            'booking' => new QueueBookingResource($booking),
            'tracking' => [
                'position' => $position,
                'estimated_wait' => $estimatedWait,
            ],
        ]);
    }

    public function cancel(Request $request, QueueBooking $booking): JsonResponse
    {
        $request->validate(['reason' => 'nullable|string|max:500']);

        $cancelled = $this->bookingService->cancelBooking(
            $booking,
            $request->input('reason'),
        );

        if (! $cancelled) {
            return response()->json([
                'message' => 'Bu bandni bekor qilib bo\'lmaydi.',
            ], 422);
        }

        return response()->json([
            'booking' => new QueueBookingResource($booking->fresh(['service', 'branch', 'specialist'])),
        ]);
    }

    public function rate(Request $request, QueueBooking $booking): JsonResponse
    {
        $data = $request->validate([
            'rating' => 'required|integer|min:1|max:5',
            'review' => 'nullable|string|max:1000',
        ]);

        $rated = $this->bookingService->rateBooking(
            $booking,
            $data['rating'],
            $data['review'] ?? null,
        );

        if (! $rated) {
            return response()->json([
                'message' => 'Faqat yakunlangan bandlarni baholash mumkin.',
            ], 422);
        }

        return response()->json([
            'booking' => new QueueBookingResource($booking->fresh(['service', 'branch', 'specialist'])),
        ]);
    }
}
