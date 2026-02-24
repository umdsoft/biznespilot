<?php

namespace App\Http\Controllers\Api\MiniApp;

use App\Http\Controllers\Controller;
use App\Models\Store\StoreBooking;
use App\Models\Store\StoreService;
use App\Models\Store\StoreStaff;
use App\Models\Store\StoreStaffSchedule;
use App\Models\Store\StoreStaffTimeOff;
use App\Models\Store\TelegramStore;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BookingController extends Controller
{
    /**
     * GET /bookings/slots — Available time slots for a service on a given date.
     */
    public function slots(Request $request, TelegramStore $store): JsonResponse
    {
        $validated = $request->validate([
            'service_id' => 'required|uuid',
            'date' => 'required|date|after_or_equal:today',
            'staff_id' => 'nullable|uuid',
        ]);

        $service = StoreService::where('store_id', $store->id)
            ->where('id', $validated['service_id'])
            ->active()
            ->first();

        if (! $service) {
            return response()->json(['success' => false, 'message' => 'Xizmat topilmadi'], 404);
        }

        $duration = $service->duration_minutes ?? 30;
        $date = Carbon::parse($validated['date']);
        $dayOfWeek = $date->dayOfWeekIso - 1; // 0=Monday

        // Get relevant staff
        $staffQuery = StoreStaff::where('store_id', $store->id)->active();
        if (! empty($validated['staff_id'])) {
            $staffQuery->where('id', $validated['staff_id']);
        }
        $staffMembers = $staffQuery->with(['schedules', 'timeOffs'])->get();

        if ($staffMembers->isEmpty()) {
            // No staff — generate default 9-18 slots
            return response()->json([
                'success' => true,
                'slots' => $this->generateDefaultSlots($duration),
            ]);
        }

        $allSlots = [];

        foreach ($staffMembers as $staff) {
            // Check time off
            $hasTimeOff = $staff->timeOffs->contains(function ($timeOff) use ($date) {
                return $date->between($timeOff->date_from, $timeOff->date_to);
            });
            if ($hasTimeOff) continue;

            // Get schedule for this day
            $schedule = $staff->schedules->firstWhere('day_of_week', $dayOfWeek);
            if (! $schedule || ! $schedule->is_working) continue;

            // Generate time slots based on schedule
            $start = Carbon::parse($date->format('Y-m-d') . ' ' . $schedule->start_time);
            $end = Carbon::parse($date->format('Y-m-d') . ' ' . $schedule->end_time);
            $breakStart = $schedule->break_start ? Carbon::parse($date->format('Y-m-d') . ' ' . $schedule->break_start) : null;
            $breakEnd = $schedule->break_end ? Carbon::parse($date->format('Y-m-d') . ' ' . $schedule->break_end) : null;

            // Get existing bookings for this staff on this date
            $existingBookings = StoreBooking::where('staff_id', $staff->id)
                ->whereDate('booked_at', $date)
                ->whereNotIn('status', ['cancelled', 'no_show'])
                ->get(['booked_at', 'ends_at']);

            $current = $start->copy();
            while ($current->copy()->addMinutes($duration)->lte($end)) {
                $slotEnd = $current->copy()->addMinutes($duration);
                $timeStr = $current->format('H:i');

                // Check if in break
                $inBreak = $breakStart && $breakEnd &&
                    $current->lt($breakEnd) && $slotEnd->gt($breakStart);

                // Check if overlaps with existing booking
                $isBooked = $existingBookings->contains(function ($booking) use ($current, $slotEnd) {
                    $bStart = Carbon::parse($booking->booked_at);
                    $bEnd = $booking->ends_at ? Carbon::parse($booking->ends_at) : $bStart->copy()->addMinutes(30);
                    return $current->lt($bEnd) && $slotEnd->gt($bStart);
                });

                // Skip past times for today
                $isPast = $date->isToday() && $current->lt(now());

                $allSlots[$timeStr] = [
                    'time' => $timeStr,
                    'available' => ! $inBreak && ! $isBooked && ! $isPast,
                    'staff_id' => $staff->id,
                ];

                $current->addMinutes($duration <= 30 ? 30 : $duration);
            }
        }

        ksort($allSlots);

        return response()->json([
            'success' => true,
            'slots' => array_values($allSlots),
        ]);
    }

    /**
     * GET /bookings — User's bookings list.
     */
    public function index(Request $request, TelegramStore $store): JsonResponse
    {
        $customer = $request->attributes->get('store_customer');

        $bookings = StoreBooking::where('store_id', $store->id)
            ->where('customer_id', $customer->id)
            ->with(['staff:id,name,photo_url,position', 'bookable:id,name'])
            ->orderByDesc('booked_at')
            ->paginate(20);

        $items = $bookings->getCollection()->map(function ($booking) {
            return [
                'id' => $booking->id,
                'service_name' => $booking->bookable?->name,
                'staff' => $booking->staff ? [
                    'id' => $booking->staff->id,
                    'name' => $booking->staff->name,
                    'photo_url' => $booking->staff->photo_url,
                ] : null,
                'booked_at' => $booking->booked_at?->toISOString(),
                'ends_at' => $booking->ends_at?->toISOString(),
                'status' => $booking->status,
                'notes' => $booking->notes,
                'created_at' => $booking->created_at->toISOString(),
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $items,
            'has_more' => $bookings->hasMorePages(),
        ]);
    }

    /**
     * GET /bookings/{id} — Booking detail.
     */
    public function show(Request $request, TelegramStore $store, string $id): JsonResponse
    {
        $customer = $request->attributes->get('store_customer');

        $booking = StoreBooking::where('store_id', $store->id)
            ->where('customer_id', $customer->id)
            ->where('id', $id)
            ->with(['staff:id,name,photo_url,position', 'bookable:id,name'])
            ->first();

        if (! $booking) {
            return response()->json(['success' => false, 'message' => 'Bandlov topilmadi'], 404);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $booking->id,
                'service_name' => $booking->bookable?->name,
                'staff' => $booking->staff ? [
                    'id' => $booking->staff->id,
                    'name' => $booking->staff->name,
                    'photo_url' => $booking->staff->photo_url,
                    'position' => $booking->staff->position,
                ] : null,
                'booked_at' => $booking->booked_at?->toISOString(),
                'ends_at' => $booking->ends_at?->toISOString(),
                'guests_count' => $booking->guests_count,
                'status' => $booking->status,
                'notes' => $booking->notes,
                'cancel_reason' => $booking->cancel_reason,
                'metadata' => $booking->metadata,
                'created_at' => $booking->created_at->toISOString(),
            ],
        ]);
    }

    /**
     * POST /bookings — Create a new booking.
     */
    public function store(Request $request, TelegramStore $store): JsonResponse
    {
        $customer = $request->attributes->get('store_customer');

        $validated = $request->validate([
            'service_id' => 'required|uuid',
            'staff_id' => 'nullable|uuid',
            'date' => 'required|date|after_or_equal:today',
            'time' => 'required|date_format:H:i',
            'guests_count' => 'nullable|integer|min:1|max:50',
            'notes' => 'nullable|string|max:1000',
        ]);

        $service = StoreService::where('store_id', $store->id)
            ->where('id', $validated['service_id'])
            ->active()
            ->first();

        if (! $service) {
            return response()->json(['success' => false, 'message' => 'Xizmat topilmadi'], 404);
        }

        $duration = $service->duration_minutes ?? 30;
        $bookedAt = Carbon::parse($validated['date'] . ' ' . $validated['time']);
        $endsAt = $bookedAt->copy()->addMinutes($duration);

        // Validate staff if provided
        $staffId = null;
        if (! empty($validated['staff_id'])) {
            $staff = StoreStaff::where('store_id', $store->id)
                ->where('id', $validated['staff_id'])
                ->active()
                ->first();
            if ($staff) {
                $staffId = $staff->id;
            }
        }

        $booking = StoreBooking::create([
            'store_id' => $store->id,
            'customer_id' => $customer->id,
            'bookable_type' => StoreService::class,
            'bookable_id' => $service->id,
            'staff_id' => $staffId,
            'booked_at' => $bookedAt,
            'ends_at' => $endsAt,
            'guests_count' => $validated['guests_count'] ?? 1,
            'status' => 'pending',
            'notes' => $validated['notes'] ?? null,
        ]);

        $booking->load(['staff:id,name,photo_url', 'bookable:id,name']);

        return response()->json([
            'success' => true,
            'message' => 'Bandlov yaratildi',
            'data' => [
                'booking' => [
                    'id' => $booking->id,
                    'service_name' => $booking->bookable?->name,
                    'staff' => $booking->staff ? [
                        'id' => $booking->staff->id,
                        'name' => $booking->staff->name,
                        'photo_url' => $booking->staff->photo_url,
                    ] : null,
                    'booked_at' => $booking->booked_at->toISOString(),
                    'ends_at' => $booking->ends_at->toISOString(),
                    'status' => $booking->status,
                ],
            ],
        ], 201);
    }

    /**
     * POST /bookings/{id}/cancel — Cancel a booking.
     */
    public function cancel(Request $request, TelegramStore $store, string $id): JsonResponse
    {
        $customer = $request->attributes->get('store_customer');

        $booking = StoreBooking::where('store_id', $store->id)
            ->where('customer_id', $customer->id)
            ->where('id', $id)
            ->whereIn('status', ['pending', 'confirmed'])
            ->first();

        if (! $booking) {
            return response()->json(['success' => false, 'message' => 'Bandlov topilmadi yoki bekor qilib bo\'lmaydi'], 404);
        }

        $booking->update([
            'status' => 'cancelled',
            'cancel_reason' => $request->input('reason', 'Mijoz tomonidan bekor qilindi'),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Bandlov bekor qilindi',
            'data' => [
                'id' => $booking->id,
                'status' => 'cancelled',
            ],
        ]);
    }

    /**
     * GET /staff — Staff list for the store.
     */
    public function staff(Request $request, TelegramStore $store): JsonResponse
    {
        $query = StoreStaff::where('store_id', $store->id)->active()->orderBy('sort_order');

        $staff = $query->get()->map(fn ($s) => [
            'id' => $s->id,
            'name' => $s->name,
            'position' => $s->position,
            'photo_url' => $s->photo_url,
            'bio' => $s->bio,
            'specializations' => $s->specializations,
        ]);

        return response()->json([
            'success' => true,
            'data' => $staff,
        ]);
    }

    /**
     * GET /staff/{id} — Staff profile.
     */
    public function staffShow(Request $request, TelegramStore $store, string $id): JsonResponse
    {
        $staff = StoreStaff::where('store_id', $store->id)
            ->where('id', $id)
            ->active()
            ->first();

        if (! $staff) {
            return response()->json(['success' => false, 'message' => 'Xodim topilmadi'], 404);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $staff->id,
                'name' => $staff->name,
                'position' => $staff->position,
                'photo_url' => $staff->photo_url,
                'bio' => $staff->bio,
                'phone' => $staff->phone,
                'specializations' => $staff->specializations,
            ],
        ]);
    }

    /**
     * Generate default 9:00-18:00 time slots.
     */
    private function generateDefaultSlots(int $duration): array
    {
        $slots = [];
        $interval = $duration <= 30 ? 30 : $duration;
        for ($h = 9; $h < 18; $h++) {
            for ($m = 0; $m < 60; $m += $interval) {
                if ($h === 17 && $m + $interval > 60) break;
                $time = sprintf('%02d:%02d', $h, $m);
                $isPast = now()->format('Y-m-d') === now()->format('Y-m-d') && now()->format('H:i') > $time;
                $slots[] = ['time' => $time, 'available' => ! $isPast];
            }
        }
        return $slots;
    }
}
