<?php

namespace App\Services\HR;

use App\Models\Business;
use App\Models\FlightRisk;
use App\Models\User;

/**
 * FlightRiskService - Flight Risk hisoblash uchun service wrapper
 *
 * RetentionService orqali flight risk funksiyalarini chaqiradi
 */
class FlightRiskService
{
    public function __construct(
        protected RetentionService $retentionService
    ) {}

    /**
     * Hodim uchun flight risk hisoblash
     */
    public function calculateForEmployee(Business $business, User $user): FlightRisk
    {
        return $this->retentionService->calculateFlightRisk($user, $business);
    }

    /**
     * Flight risk olish
     */
    public function getForEmployee(Business $business, User $user): ?FlightRisk
    {
        return $this->retentionService->getFlightRisk($user, $business);
    }

    /**
     * Yuqori riskli hodimlarni olish
     */
    public function getHighRiskEmployees(Business $business, int $limit = 10)
    {
        return $this->retentionService->getHighRiskEmployees($business, $limit);
    }
}
