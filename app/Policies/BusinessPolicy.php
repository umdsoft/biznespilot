<?php

namespace App\Policies;

use App\Models\Business;
use App\Models\User;

class BusinessPolicy
{
    /**
     * Determine whether the user can view any businesses.
     */
    public function viewAny(User $user): bool
    {
        return true; // Users can view their own businesses
    }

    /**
     * Determine whether the user can view the business.
     */
    public function view(User $user, Business $business): bool
    {
        return $user->businesses()->where('businesses.id', $business->id)->exists();
    }

    /**
     * Determine whether the user can create businesses.
     */
    public function create(User $user): bool
    {
        // Check subscription limits if needed
        return true;
    }

    /**
     * Determine whether the user can update the business.
     */
    public function update(User $user, Business $business): bool
    {
        // Only business owner or admin can update
        return $this->isOwnerOrAdmin($user, $business);
    }

    /**
     * Determine whether the user can delete the business.
     */
    public function delete(User $user, Business $business): bool
    {
        // Only business owner can delete
        return $business->user_id === $user->id;
    }

    /**
     * Determine whether the user can invite members to the business.
     */
    public function invite(User $user, Business $business): bool
    {
        return $this->isOwnerOrAdmin($user, $business);
    }

    /**
     * Determine whether the user can remove members from the business.
     */
    public function removeUser(User $user, Business $business): bool
    {
        return $this->isOwnerOrAdmin($user, $business);
    }

    /**
     * Determine whether the user can update business settings.
     */
    public function updateSettings(User $user, Business $business): bool
    {
        return $this->isOwnerOrAdmin($user, $business);
    }

    /**
     * Determine whether the user can manage integrations.
     */
    public function manageIntegrations(User $user, Business $business): bool
    {
        return $this->isOwnerOrAdmin($user, $business);
    }

    /**
     * Determine whether the user can manage subscription.
     */
    public function manageSubscription(User $user, Business $business): bool
    {
        // Only owner can manage subscription
        return $business->user_id === $user->id;
    }

    /**
     * Check if user is owner or admin of the business
     */
    private function isOwnerOrAdmin(User $user, Business $business): bool
    {
        if ($business->user_id === $user->id) {
            return true;
        }

        $pivot = $user->businesses()
            ->where('businesses.id', $business->id)
            ->first()
            ?->pivot;

        return $pivot && in_array($pivot->role, ['admin', 'owner']);
    }
}
