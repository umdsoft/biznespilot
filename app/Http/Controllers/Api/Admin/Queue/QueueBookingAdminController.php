<?php

namespace App\Http\Controllers\Api\Admin\Queue;

use App\Http\Controllers\Controller;
use App\Http\Requests\Bot\Queue\UpdateQueueBookingStatusRequest;
use App\Http\Resources\Bot\Queue\QueueBookingResource;
use App\Models\Bot\Queue\QueueBooking;
use App\Services\Bot\Queue\QueueBookingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class QueueBookingAdminController extends Controller
{
    public function __construct(
        private QueueBookingService $bookingService,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $query = QueueBooking::with(['service', 'branch', 'specialist']);

        if ($request->filled('status')) {
            $query->byStatus($request->input('status'));
        }

        if ($request->filled('branch_id')) {
            $query->where('branch_id', $request->input('branch_id'));
        }

        if ($request->filled('date_from')) {
            $query->whereDate('date', '>=', $request->input('date_from'));
        }

        if ($request->filled('date_to')) {
            $query->whereDate('date', '<=', $request->input('date_to'));
        }

        if ($request->filled('search')) {
            $term = $request->input('search');
            $query->where(function ($q) use ($term) {
                $q->where('booking_number', 'like', "%{$term}%")
                    ->orWhere('customer_name', 'like', "%{$term}%")
                    ->orWhere('customer_phone', 'like', "%{$term}%");
            });
        }

        $bookings = $query->latest()->paginate($request->input('per_page', 20));

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

        return response()->json([
            'booking' => new QueueBookingResource($booking),
        ]);
    }

    public function updateStatus(UpdateQueueBookingStatusRequest $request, QueueBooking $booking): JsonResponse
    {
        $newStatus = $request->input('status');

        if ($newStatus === QueueBooking::STATUS_CANCELLED) {
            $this->bookingService->cancelBooking($booking, $request->input('cancel_reason'));
        } else {
            $booking->transitionTo($newStatus);
        }

        return response()->json([
            'booking' => new QueueBookingResource($booking->fresh(['service', 'branch', 'specialist'])),
        ]);
    }

    public function bulkCancel(Request $request): JsonResponse
    {
        $data = $request->validate([
            'booking_ids' => 'required|array|min:1',
            'booking_ids.*' => 'uuid|exists:queue_bookings,id',
            'reason' => 'nullable|string|max:500',
        ]);

        $cancelled = 0;

        foreach ($data['booking_ids'] as $bookingId) {
            $booking = QueueBooking::find($bookingId);
            if ($booking && $this->bookingService->cancelBooking($booking, $data['reason'] ?? null)) {
                $cancelled++;
            }
        }

        return response()->json([
            'cancelled' => $cancelled,
            'total' => count($data['booking_ids']),
            'message' => "{$cancelled} ta band bekor qilindi.",
        ]);
    }
}
