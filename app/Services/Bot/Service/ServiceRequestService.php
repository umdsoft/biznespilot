<?php

namespace App\Services\Bot\Service;

use App\Models\Bot\Service\ServiceCategory;
use App\Models\Bot\Service\ServiceMaster;
use App\Models\Bot\Service\ServiceRequest;
use App\Models\Bot\Service\ServiceSetting;
use Illuminate\Support\Facades\DB;

class ServiceRequestService
{
    public function createRequest(string $businessId, array $data): ServiceRequest
    {
        return DB::transaction(function () use ($businessId, $data) {
            $settings = ServiceSetting::getForBusiness($businessId);

            $request = ServiceRequest::create([
                'business_id' => $businessId,
                'request_number' => ServiceRequest::generateRequestNumber(),
                'telegram_user_id' => $data['telegram_user_id'],
                'customer_name' => $data['customer_name'],
                'customer_phone' => $data['customer_phone'],
                'category_id' => $data['category_id'],
                'service_type_id' => $data['service_type_id'],
                'master_id' => $data['master_id'] ?? null,
                'status' => ServiceRequest::STATUS_PENDING,
                'description' => $data['description'] ?? null,
                'images' => $data['images'] ?? null,
                'address' => $data['address'],
                'landmark' => $data['landmark'] ?? null,
                'lat' => $data['lat'] ?? null,
                'lng' => $data['lng'] ?? null,
                'preferred_date' => $data['preferred_date'] ?? null,
                'preferred_time_slot' => $data['preferred_time_slot'] ?? null,
                'payment_method' => $data['payment_method'] ?? 'cash',
            ]);

            if ($settings->auto_assign_master && ! $request->master_id) {
                $this->findAndAssignMaster($request);
            }

            return $request->load(['category', 'serviceType', 'master']);
        });
    }

    public function assignMaster(ServiceRequest $request, string $masterId): bool
    {
        $request->master_id = $masterId;
        $request->assigned_at = now();

        return $request->transitionTo(ServiceRequest::STATUS_ASSIGNED);
    }

    public function updateStatus(ServiceRequest $request, string $newStatus): bool
    {
        return $request->transitionTo($newStatus);
    }

    public function setCost(ServiceRequest $request, float $laborCost, array $partsUsed = []): bool
    {
        $partsCost = collect($partsUsed)->sum('price');

        $request->labor_cost = $laborCost;
        $request->parts_used = $partsUsed;
        $request->parts_cost = $partsCost;
        $request->total_cost = $laborCost + $partsCost;

        return $request->save();
    }

    public function approveCost(ServiceRequest $request): bool
    {
        $request->cost_approved = true;

        if ($request->status === ServiceRequest::STATUS_DIAGNOSING) {
            return $request->transitionTo(ServiceRequest::STATUS_IN_PROGRESS);
        }

        return $request->save();
    }

    public function complete(ServiceRequest $request): bool
    {
        if (! $request->transitionTo(ServiceRequest::STATUS_COMPLETED)) {
            return false;
        }

        // Calculate warranty if master has warranty_months set
        if ($request->master) {
            $master = $request->master;

            if ($master->warranty_months) {
                $request->warranty_until = now()->addMonths($master->warranty_months);
                $request->save();
            }

            $master->increment('completed_jobs');
        }

        return true;
    }

    public function cancelRequest(ServiceRequest $request, ?string $reason = null): bool
    {
        if (! $request->canTransitionTo(ServiceRequest::STATUS_CANCELLED)) {
            return false;
        }

        $request->cancel_reason = $reason;

        return $request->transitionTo(ServiceRequest::STATUS_CANCELLED);
    }

    public function rateRequest(ServiceRequest $request, int $rating, ?string $review = null): bool
    {
        if ($request->status !== ServiceRequest::STATUS_COMPLETED) {
            return false;
        }

        $request->rating = $rating;
        $request->review = $review;
        $request->save();

        // Update master's average rating
        if ($request->master) {
            $master = $request->master;
            $totalRating = ($master->rating_avg * $master->rating_count) + $rating;
            $master->rating_count += 1;
            $master->rating_avg = round($totalRating / $master->rating_count, 2);
            $master->save();
        }

        return true;
    }

    private function findAndAssignMaster(ServiceRequest $request): void
    {
        $query = ServiceMaster::available()
            ->whereHas('categories', function ($q) use ($request) {
                $q->where('service_categories.id', $request->category_id);
            });

        // If location provided, sort by distance (simplified Haversine)
        if ($request->lat && $request->lng) {
            $lat = $request->lat;
            $lng = $request->lng;

            $query->whereNotNull('location_lat')
                ->whereNotNull('location_lng')
                ->orderByRaw(
                    'SQRT(POW(location_lat - ?, 2) + POW(location_lng - ?, 2))',
                    [$lat, $lng]
                );
        } else {
            // Load balance: pick master with fewest completed jobs
            $query->orderBy('completed_jobs', 'asc');
        }

        $master = $query->first();

        if ($master) {
            $request->master_id = $master->id;
            $request->assigned_at = now();
            $request->status = ServiceRequest::STATUS_ASSIGNED;
            $request->save();
        }
    }
}
