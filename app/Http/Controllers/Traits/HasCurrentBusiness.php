<?php

namespace App\Http\Controllers\Traits;

use App\Models\Business;
use App\Models\BusinessUser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

trait HasCurrentBusiness
{
    /**
     * Get current business securely from database
     * Only returns businesses the user has access to (owner or team member)
     *
     * Priority:
     * 1. Session (if user has access)
     * 2. User's owned business
     * 3. User's team membership
     *
     * @param Request|null $request Optional request (business_id param validated for access)
     * @return Business|null
     */
    protected function getCurrentBusiness(?Request $request = null): ?Business
    {
        $user = Auth::user();
        if (!$user) {
            return null;
        }

        // Get all businesses user has access to (owned + team member)
        $accessibleBusinessIds = $this->getAccessibleBusinessIds($user);

        if ($accessibleBusinessIds->isEmpty()) {
            return null;
        }

        // Check session - validate user still has access
        $sessionBusinessId = session('current_business_id');
        if ($sessionBusinessId && $accessibleBusinessIds->contains($sessionBusinessId)) {
            $business = Business::find($sessionBusinessId);
            if ($business) {
                return $business;
            }
        }

        // Default: return user's owned business
        $ownedBusiness = Business::where('user_id', $user->id)->first();
        if ($ownedBusiness) {
            session(['current_business_id' => $ownedBusiness->id]);
            return $ownedBusiness;
        }

        // Fallback to team membership
        $business = Business::whereIn('id', $accessibleBusinessIds)->first();
        if ($business) {
            session(['current_business_id' => $business->id]);
            return $business;
        }

        return null;
    }

    /**
     * Get all business IDs user has access to
     */
    private function getAccessibleBusinessIds($user): \Illuminate\Support\Collection
    {
        // Owned businesses
        $ownedIds = Business::where('user_id', $user->id)->pluck('id');

        // Team memberships
        $memberIds = BusinessUser::where('user_id', $user->id)
            ->whereNotNull('accepted_at')
            ->pluck('business_id');

        return $ownedIds->merge($memberIds)->unique();
    }
}
