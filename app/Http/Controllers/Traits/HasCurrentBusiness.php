<?php

namespace App\Http\Controllers\Traits;

use App\Models\Business;
use App\Models\BusinessUser;
use Illuminate\Support\Facades\Auth;

trait HasCurrentBusiness
{
    protected function getCurrentBusiness(): ?Business
    {
        $businessId = session('current_business_id');

        if ($businessId) {
            return Business::find($businessId);
        }

        // If no business in session, try to find one for the current user
        $user = Auth::user();
        if (!$user) {
            return null;
        }

        // First, check if user owns any businesses
        $ownedBusiness = Business::where('user_id', $user->id)->first();
        if ($ownedBusiness) {
            session(['current_business_id' => $ownedBusiness->id]);
            return $ownedBusiness;
        }

        // If not owner, check if user is a team member
        $membership = BusinessUser::where('user_id', $user->id)
            ->whereNotNull('accepted_at')
            ->first();

        if ($membership) {
            $business = Business::find($membership->business_id);
            if ($business) {
                session(['current_business_id' => $business->id]);
                return $business;
            }
        }

        return null;
    }
}
