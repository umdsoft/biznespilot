<?php

namespace App\Policies;

use App\Models\LeadForm;
use App\Models\User;

class LeadFormPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->currentBusiness !== null;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, LeadForm $leadForm): bool
    {
        return $user->currentBusiness?->id === $leadForm->business_id;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->currentBusiness !== null;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, LeadForm $leadForm): bool
    {
        return $user->currentBusiness?->id === $leadForm->business_id;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, LeadForm $leadForm): bool
    {
        return $user->currentBusiness?->id === $leadForm->business_id;
    }
}
