<?php

namespace App\Http\Controllers\Api\Bot\Service;

use App\Http\Controllers\Controller;
use App\Models\Bot\Service\ServiceRequest;
use App\Models\Bot\Service\ServiceSetting;
use Illuminate\Http\JsonResponse;

class ServiceTrackingController extends Controller
{
    public function status(ServiceRequest $serviceRequest): JsonResponse
    {
        $serviceRequest->load('master');
        $settings = ServiceSetting::getForBusiness($serviceRequest->business_id);

        $steps = [
            [
                'status' => ServiceRequest::STATUS_PENDING,
                'label' => 'So\'rov qabul qilindi',
                'completed' => true,
                'time' => $serviceRequest->created_at?->format('H:i'),
            ],
            [
                'status' => ServiceRequest::STATUS_ASSIGNED,
                'label' => 'Usta tayinlandi',
                'completed' => $serviceRequest->assigned_at !== null,
                'time' => $serviceRequest->assigned_at?->format('H:i'),
            ],
            [
                'status' => ServiceRequest::STATUS_EN_ROUTE,
                'label' => 'Usta yo\'lda',
                'completed' => $serviceRequest->en_route_at !== null,
                'time' => $serviceRequest->en_route_at?->format('H:i'),
            ],
            [
                'status' => ServiceRequest::STATUS_ARRIVED,
                'label' => 'Usta yetib keldi',
                'completed' => $serviceRequest->arrived_at !== null,
                'time' => $serviceRequest->arrived_at?->format('H:i'),
            ],
            [
                'status' => ServiceRequest::STATUS_DIAGNOSING,
                'label' => 'Diagnostika',
                'completed' => $serviceRequest->diagnosing_at !== null,
                'time' => $serviceRequest->diagnosing_at?->format('H:i'),
            ],
            [
                'status' => ServiceRequest::STATUS_IN_PROGRESS,
                'label' => 'Ish jarayonida',
                'completed' => $serviceRequest->in_progress_at !== null,
                'time' => $serviceRequest->in_progress_at?->format('H:i'),
            ],
            [
                'status' => ServiceRequest::STATUS_COMPLETED,
                'label' => 'Bajarildi',
                'completed' => $serviceRequest->completed_at !== null,
                'time' => $serviceRequest->completed_at?->format('H:i'),
            ],
        ];

        $masterLocation = null;
        if ($settings->show_master_location && $serviceRequest->master) {
            $master = $serviceRequest->master;
            if ($master->location_lat && $master->location_lng) {
                $masterLocation = [
                    'lat' => (float) $master->location_lat,
                    'lng' => (float) $master->location_lng,
                ];
            }
        }

        return response()->json([
            'current_status' => $serviceRequest->status,
            'master' => $serviceRequest->master ? [
                'name' => $serviceRequest->master->name,
                'phone' => $serviceRequest->master->phone,
                'avatar_url' => $serviceRequest->master->avatar_url,
                'rating_avg' => (float) $serviceRequest->master->rating_avg,
            ] : null,
            'master_location' => $masterLocation,
            'cost' => $serviceRequest->total_cost ? [
                'labor_cost' => (float) $serviceRequest->labor_cost,
                'parts_cost' => (float) $serviceRequest->parts_cost,
                'total_cost' => (float) $serviceRequest->total_cost,
                'cost_approved' => $serviceRequest->cost_approved,
            ] : null,
            'steps' => $steps,
        ]);
    }
}
