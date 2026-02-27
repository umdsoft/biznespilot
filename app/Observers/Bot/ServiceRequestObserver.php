<?php

namespace App\Observers\Bot;

use App\Models\Bot\Service\ServiceRequest;
use App\Services\Bot\Service\ServiceStatsService;
use Illuminate\Support\Facades\Log;

class ServiceRequestObserver
{
    public function creating(ServiceRequest $request): void
    {
        if (! $request->request_number) {
            $request->request_number = ServiceRequest::generateRequestNumber();
        }
    }

    public function created(ServiceRequest $request): void
    {
        Log::info("Service request created: {$request->request_number}", [
            'business_id' => $request->business_id,
            'category_id' => $request->category_id,
        ]);
    }

    public function updated(ServiceRequest $request): void
    {
        if (! $request->wasChanged('status')) {
            return;
        }

        $newStatus = $request->status;

        Log::info("Service request status changed: {$request->request_number} → {$newStatus}");

        if ($newStatus === ServiceRequest::STATUS_COMPLETED) {
            try {
                app(ServiceStatsService::class)
                    ->calculateDailyStats($request->business_id, now()->toDateString());
            } catch (\Throwable $e) {
                Log::error("Failed to update service stats: {$e->getMessage()}");
            }
        }
    }
}
