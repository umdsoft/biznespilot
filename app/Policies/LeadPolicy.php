<?php

namespace App\Policies;

use App\Models\Lead;
use App\Models\User;

class LeadPolicy
{
    /**
     * Determine whether the user can view any leads.
     */
    public function viewAny(User $user): bool
    {
        return $user->currentBusiness !== null;
    }

    /**
     * Determine whether the user can view the lead.
     */
    public function view(User $user, Lead $lead): bool
    {
        return $user->currentBusiness?->id === $lead->business_id;
    }

    /**
     * Determine whether the user can create leads.
     */
    public function create(User $user): bool
    {
        return $user->currentBusiness !== null;
    }

    /**
     * Determine whether the user can update the lead.
     */
    public function update(User $user, Lead $lead): bool
    {
        return $user->currentBusiness?->id === $lead->business_id;
    }

    /**
     * Determine whether the user can delete the lead.
     */
    public function delete(User $user, Lead $lead): bool
    {
        return $user->currentBusiness?->id === $lead->business_id;
    }

    /**
     * Determine whether the user can assign the lead to another user.
     */
    public function assign(User $user, Lead $lead): bool
    {
        if ($user->currentBusiness?->id !== $lead->business_id) {
            return false;
        }

        // Only managers and admins can assign leads
        return $user->hasRole(['admin', 'sales_head', 'manager']);
    }

    /**
     * Determine whether the user can bulk update leads.
     */
    public function bulkUpdate(User $user): bool
    {
        return $user->currentBusiness !== null
            && $user->hasRole(['admin', 'sales_head', 'manager']);
    }

    /**
     * Determine whether the user can export leads.
     */
    public function export(User $user): bool
    {
        return $user->currentBusiness !== null
            && $user->hasRole(['admin', 'sales_head', 'manager']);
    }

    /**
     * Determine whether the user can import leads.
     */
    public function import(User $user): bool
    {
        return $user->currentBusiness !== null
            && $user->hasRole(['admin', 'sales_head', 'manager']);
    }
}
