<?php

namespace App\Policies;

use App\Models\GeneratedReport;
use App\Models\User;

class ReportPolicy
{
    /**
     * Determine whether the user can view any reports.
     */
    public function viewAny(User $user): bool
    {
        return $user->currentBusiness !== null;
    }

    /**
     * Determine whether the user can view the report.
     */
    public function view(User $user, GeneratedReport $report): bool
    {
        return $user->currentBusiness?->id === $report->business_id;
    }

    /**
     * Determine whether the user can generate reports.
     */
    public function generate(User $user): bool
    {
        return $user->currentBusiness !== null;
    }

    /**
     * Determine whether the user can export reports.
     */
    public function export(User $user, GeneratedReport $report): bool
    {
        return $user->currentBusiness?->id === $report->business_id;
    }

    /**
     * Determine whether the user can delete reports.
     */
    public function delete(User $user, GeneratedReport $report): bool
    {
        if ($user->currentBusiness?->id !== $report->business_id) {
            return false;
        }

        // Only admins can delete reports
        return $user->hasRole(['admin']);
    }

    /**
     * Determine whether the user can schedule reports.
     */
    public function schedule(User $user): bool
    {
        if (!$user->currentBusiness) {
            return false;
        }

        return $user->hasRole(['admin', 'sales_head', 'manager']);
    }

    /**
     * Determine whether the user can share reports.
     */
    public function share(User $user, GeneratedReport $report): bool
    {
        if ($user->currentBusiness?->id !== $report->business_id) {
            return false;
        }

        return $user->hasRole(['admin', 'sales_head', 'manager']);
    }

    /**
     * Determine whether the user can view financial reports.
     */
    public function viewFinancial(User $user): bool
    {
        if (!$user->currentBusiness) {
            return false;
        }

        return $user->hasRole(['admin', 'sales_head', 'finance']);
    }

    /**
     * Determine whether the user can view HR reports.
     */
    public function viewHR(User $user): bool
    {
        if (!$user->currentBusiness) {
            return false;
        }

        return $user->hasRole(['admin', 'hr_manager']);
    }

    /**
     * Determine whether the user can view sales reports.
     */
    public function viewSales(User $user): bool
    {
        return $user->currentBusiness !== null;
    }

    /**
     * Determine whether the user can view marketing reports.
     */
    public function viewMarketing(User $user): bool
    {
        return $user->currentBusiness !== null;
    }
}
