<?php

namespace App\Policies;

use App\Models\Offer;
use App\Models\User;

class OfferPolicy
{
    /**
     * Determine whether the user can view any offers.
     */
    public function viewAny(User $user): bool
    {
        return $user->currentBusiness !== null;
    }

    /**
     * Determine whether the user can view the offer.
     */
    public function view(User $user, Offer $offer): bool
    {
        return $user->currentBusiness?->id === $offer->business_id;
    }

    /**
     * Determine whether the user can create offers.
     */
    public function create(User $user): bool
    {
        return $user->currentBusiness !== null;
    }

    /**
     * Determine whether the user can update the offer.
     */
    public function update(User $user, Offer $offer): bool
    {
        return $user->currentBusiness?->id === $offer->business_id;
    }

    /**
     * Determine whether the user can delete the offer.
     */
    public function delete(User $user, Offer $offer): bool
    {
        return $user->currentBusiness?->id === $offer->business_id;
    }

    /**
     * Determine whether the user can publish the offer.
     */
    public function publish(User $user, Offer $offer): bool
    {
        if ($user->currentBusiness?->id !== $offer->business_id) {
            return false;
        }

        // Only managers and admins can publish offers
        return $user->hasRole(['admin', 'sales_head', 'manager', 'marketer']);
    }

    /**
     * Determine whether the user can unpublish the offer.
     */
    public function unpublish(User $user, Offer $offer): bool
    {
        return $this->publish($user, $offer);
    }

    /**
     * Determine whether the user can duplicate the offer.
     */
    public function duplicate(User $user, Offer $offer): bool
    {
        return $user->currentBusiness?->id === $offer->business_id;
    }

    /**
     * Determine whether the user can manage offer automation.
     */
    public function manageAutomation(User $user, Offer $offer): bool
    {
        if ($user->currentBusiness?->id !== $offer->business_id) {
            return false;
        }

        return $user->hasRole(['admin', 'sales_head', 'manager', 'marketer']);
    }

    /**
     * Determine whether the user can view offer analytics.
     */
    public function viewAnalytics(User $user, Offer $offer): bool
    {
        return $user->currentBusiness?->id === $offer->business_id;
    }
}
