<?php

namespace App\Http\Controllers\Api\Admin\Queue;

use App\Http\Controllers\Controller;
use App\Models\Bot\Queue\QueueBranch;
use App\Models\Bot\Queue\QueueTimeSlot;
use App\Services\Bot\Queue\QueueSlotService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class QueueSlotAdminController extends Controller
{
    public function __construct(
        private QueueSlotService $slotService,
    ) {}

    public function generate(Request $request): JsonResponse
    {
        $data = $request->validate([
            'branch_id' => 'required|uuid|exists:queue_branches,id',
            'date_from' => 'required|date|after_or_equal:today',
            'date_to' => 'required|date|after_or_equal:date_from',
        ]);

        $branch = QueueBranch::findOrFail($data['branch_id']);
        $from = Carbon::parse($data['date_from']);
        $to = Carbon::parse($data['date_to']);
        $generated = 0;

        $current = $from->copy();
        while ($current->lte($to)) {
            $this->slotService->generateSlotsForDate($branch, $current->toDateString());
            $generated++;
            $current->addDay();
        }

        return response()->json([
            'message' => "{$generated} kun uchun slotlar yaratildi.",
            'days' => $generated,
        ]);
    }

    public function block(QueueTimeSlot $slot): JsonResponse
    {
        if ($slot->status !== 'available') {
            return response()->json([
                'message' => 'Faqat bo\'sh slotlarni bloklash mumkin.',
            ], 422);
        }

        $slot->update(['status' => 'blocked']);

        return response()->json([
            'message' => 'Slot bloklandi.',
            'slot' => [
                'id' => $slot->id,
                'start_time' => $slot->start_time,
                'end_time' => $slot->end_time,
                'status' => $slot->status,
            ],
        ]);
    }

    public function unblock(QueueTimeSlot $slot): JsonResponse
    {
        if ($slot->status !== 'blocked') {
            return response()->json([
                'message' => 'Faqat bloklangan slotlarni ochish mumkin.',
            ], 422);
        }

        $slot->update(['status' => 'available']);

        return response()->json([
            'message' => 'Slot blokdan chiqarildi.',
            'slot' => [
                'id' => $slot->id,
                'start_time' => $slot->start_time,
                'end_time' => $slot->end_time,
                'status' => $slot->status,
            ],
        ]);
    }
}
