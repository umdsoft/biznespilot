<?php

namespace App\Http\Controllers\Api\Bot\Queue;

use App\Http\Controllers\Controller;
use App\Services\Bot\Queue\QueueSlotService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class QueueSlotController extends Controller
{
    public function __construct(
        private QueueSlotService $slotService,
    ) {}

    public function available(Request $request): JsonResponse
    {
        $request->validate([
            'branch_id' => 'required|uuid|exists:queue_branches,id',
            'date' => 'required|date|after_or_equal:today',
            'service_id' => 'required|uuid|exists:queue_services,id',
            'specialist_id' => 'nullable|uuid',
        ]);

        $slots = $this->slotService->getAvailableSlots(
            $request->input('branch_id'),
            $request->input('service_id'),
            $request->input('date'),
            $request->input('specialist_id'),
        );

        return response()->json([
            'slots' => $slots->map(fn ($slot) => [
                'id' => $slot->id,
                'start_time' => $slot->start_time,
                'end_time' => $slot->end_time,
                'specialist_id' => $slot->specialist_id,
            ]),
        ]);
    }
}
