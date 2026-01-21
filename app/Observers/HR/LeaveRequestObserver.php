<?php

namespace App\Observers\HR;

use App\Models\LeaveRequest;
use App\Services\HR\EngagementService;
use App\Services\HR\HRAlertService;
use App\Services\HR\RetentionService;
use Illuminate\Support\Facades\Log;

/**
 * LeaveRequestObserver - Ta'til so'rovlarini kuzatish
 *
 * Avtomatik harakatlar:
 * - Ta'til so'rovi tasdiqlanganda/rad etilganda harakatlar
 * - Ko'p ta'til olish naqshlarini aniqlash
 * - Engagement va retention tracking
 */
class LeaveRequestObserver
{
    public function __construct(
        protected HRAlertService $alertService,
        protected EngagementService $engagementService,
        protected RetentionService $retentionService
    ) {}

    /**
     * Ta'til so'rovi yaratilganda
     */
    public function created(LeaveRequest $request): void
    {
        Log::info('LeaveRequestObserver: Leave request created', [
            'request_id' => $request->id,
            'user_id' => $request->user_id,
            'leave_type' => $request->leave_type_id,
            'days' => $request->total_days,
        ]);

        // HR ga ogohlantirish - yangi so'rov
        $this->alertService->createAlert(
            $request->business,
            'leave_request_new',
            "Yangi ta'til so'rovi",
            "{$request->user->name} - {$request->total_days} kun ta'til so'radi",
            [
                'priority' => $request->total_days > 5 ? 'medium' : 'low',
                'user_id' => null, // HR uchun
                'data' => [
                    'request_id' => $request->id,
                    'employee_id' => $request->user_id,
                    'employee_name' => $request->user->name,
                    'days' => $request->total_days,
                    'start_date' => $request->start_date->toDateString(),
                    'end_date' => $request->end_date->toDateString(),
                ],
            ]
        );
    }

    /**
     * Ta'til so'rovi yangilanganda
     */
    public function updated(LeaveRequest $request): void
    {
        // Status o'zgarganda
        if ($request->isDirty('status')) {
            $oldStatus = $request->getOriginal('status');
            $newStatus = $request->status;

            Log::info('LeaveRequestObserver: Status changed', [
                'request_id' => $request->id,
                'old_status' => $oldStatus,
                'new_status' => $newStatus,
            ]);

            $this->handleStatusChange($request, $oldStatus, $newStatus);
        }
    }

    /**
     * Status o'zgarishini qayta ishlash
     */
    protected function handleStatusChange(LeaveRequest $request, string $oldStatus, string $newStatus): void
    {
        $employee = $request->user;
        $business = $request->business;

        switch ($newStatus) {
            case 'approved':
                $this->handleApproved($request, $employee, $business);
                break;

            case 'rejected':
                $this->handleRejected($request, $employee, $business);
                break;

            case 'cancelled':
                $this->handleCancelled($request, $employee, $business);
                break;
        }
    }

    /**
     * Ta'til tasdiqlanganda
     */
    protected function handleApproved(LeaveRequest $request, $employee, $business): void
    {
        // Hodimga xabar yuborish
        $this->alertService->createAlert(
            $business,
            'leave_request_approved',
            "Ta'til tasdiqlandi!",
            "{$request->start_date->format('d.m.Y')} - {$request->end_date->format('d.m.Y')} uchun ta'tilingiz tasdiqlandi",
            [
                'priority' => 'low',
                'user_id' => $employee->id,
                'is_celebration' => true,
                'data' => [
                    'request_id' => $request->id,
                    'days' => $request->total_days,
                ],
            ]
        );

        // Ijobiy engagement ta'sir (so'rov ko'rib chiqildi)
        $this->engagementService->boostEngagement($employee, $business, 'leave_approved', 1);

        Log::info('LeaveRequestObserver: Leave approved', [
            'request_id' => $request->id,
            'user_id' => $employee->id,
        ]);
    }

    /**
     * Ta'til rad etilganda
     */
    protected function handleRejected(LeaveRequest $request, $employee, $business): void
    {
        // Hodimga xabar yuborish
        $this->alertService->createAlert(
            $business,
            'leave_request_rejected',
            "Ta'til rad etildi",
            "{$request->start_date->format('d.m.Y')} uchun so'rovingiz rad etildi. Sabab: {$request->rejection_reason}",
            [
                'priority' => 'medium',
                'user_id' => $employee->id,
                'data' => [
                    'request_id' => $request->id,
                    'reason' => $request->rejection_reason,
                ],
            ]
        );

        // Salbiy ta'til tajribasi - engagement past, flight risk oshishi mumkin
        // Ketma-ket rad etishlar tekshirish
        $recentRejections = LeaveRequest::where('business_id', $business->id)
            ->where('user_id', $employee->id)
            ->where('status', 'rejected')
            ->where('created_at', '>=', now()->subMonths(3))
            ->count();

        if ($recentRejections >= 2) {
            // Ko'p rad etilish - flight risk oshirish
            $this->retentionService->increaseFlightRisk(
                $employee,
                $business,
                'multiple_leave_rejections',
                3
            );

            Log::warning('LeaveRequestObserver: Multiple leave rejections detected', [
                'user_id' => $employee->id,
                'rejection_count' => $recentRejections,
            ]);
        }
    }

    /**
     * Ta'til bekor qilinganda
     */
    protected function handleCancelled(LeaveRequest $request, $employee, $business): void
    {
        Log::info('LeaveRequestObserver: Leave cancelled', [
            'request_id' => $request->id,
            'user_id' => $employee->id,
        ]);
    }
}
