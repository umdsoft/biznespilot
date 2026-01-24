<?php

namespace App\Policies;

use App\Models\KpiDailyActual;
use App\Models\User;

class KpiPolicy
{
    /**
     * Determine whether the user can view any KPIs.
     */
    public function viewAny(User $user): bool
    {
        return $user->currentBusiness !== null;
    }

    /**
     * Determine whether the user can view the KPI.
     */
    public function view(User $user, KpiDailyActual $kpi): bool
    {
        return $user->currentBusiness?->id === $kpi->business_id;
    }

    /**
     * Determine whether the user can create/enter KPI data.
     */
    public function create(User $user): bool
    {
        return $user->currentBusiness !== null;
    }

    /**
     * Determine whether the user can update KPI data.
     */
    public function update(User $user, KpiDailyActual $kpi): bool
    {
        return $user->currentBusiness?->id === $kpi->business_id;
    }

    /**
     * Determine whether the user can delete KPI data.
     */
    public function delete(User $user, KpiDailyActual $kpi): bool
    {
        if ($user->currentBusiness?->id !== $kpi->business_id) {
            return false;
        }

        // Only admins can delete KPI data
        return $user->hasRole(['admin']);
    }

    /**
     * Determine whether the user can configure KPIs.
     */
    public function configure(User $user): bool
    {
        if (!$user->currentBusiness) {
            return false;
        }

        // Only admins and managers can configure KPIs
        return $user->hasRole(['admin', 'sales_head', 'manager']);
    }

    /**
     * Determine whether the user can set KPI targets/plans.
     */
    public function setTargets(User $user): bool
    {
        if (!$user->currentBusiness) {
            return false;
        }

        return $user->hasRole(['admin', 'sales_head', 'manager']);
    }

    /**
     * Determine whether the user can export KPI data.
     */
    public function export(User $user): bool
    {
        return $user->currentBusiness !== null;
    }

    /**
     * Determine whether the user can view KPI dashboard.
     */
    public function viewDashboard(User $user): bool
    {
        return $user->currentBusiness !== null;
    }

    /**
     * Determine whether the user can configure KPI alerts.
     */
    public function configureAlerts(User $user): bool
    {
        if (!$user->currentBusiness) {
            return false;
        }

        return $user->hasRole(['admin', 'sales_head', 'manager']);
    }

    /**
     * Determine whether the user can create custom KPIs.
     */
    public function createCustom(User $user): bool
    {
        if (!$user->currentBusiness) {
            return false;
        }

        return $user->hasRole(['admin', 'sales_head']);
    }
}
