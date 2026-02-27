<?php

namespace App\Services\Bot\Service;

use App\Models\Bot\Service\ServiceMaster;
use App\Models\Bot\Service\ServiceRequest;
use Illuminate\Database\Eloquent\Collection;

class ServiceMasterService
{
    public function findNearestAvailable(string $categoryId, ?float $lat = null, ?float $lng = null): ?ServiceMaster
    {
        $query = ServiceMaster::available()
            ->whereHas('categories', function ($q) use ($categoryId) {
                $q->where('service_categories.id', $categoryId);
            });

        if ($lat !== null && $lng !== null) {
            $query->whereNotNull('location_lat')
                ->whereNotNull('location_lng')
                ->orderByRaw(
                    'SQRT(POW(location_lat - ?, 2) + POW(location_lng - ?, 2))',
                    [$lat, $lng]
                );
        } else {
            $query->orderBy('completed_jobs', 'asc');
        }

        return $query->first();
    }

    public function updateLocation(ServiceMaster $master, float $lat, float $lng): void
    {
        $master->updateLocation($lat, $lng);
    }

    public function getSchedule(ServiceMaster $master, string $dateFrom, string $dateTo): Collection
    {
        return ServiceRequest::byMaster($master->id)
            ->whereDate('preferred_date', '>=', $dateFrom)
            ->whereDate('preferred_date', '<=', $dateTo)
            ->whereNotIn('status', ServiceRequest::TERMINAL_STATUSES)
            ->with(['category', 'serviceType'])
            ->orderBy('preferred_date')
            ->orderBy('preferred_time_slot')
            ->get();
    }
}
