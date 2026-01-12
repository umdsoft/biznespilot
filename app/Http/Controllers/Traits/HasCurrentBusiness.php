<?php

namespace App\Http\Controllers\Traits;

use App\Models\Business;

trait HasCurrentBusiness
{
    protected function getCurrentBusiness(): ?Business
    {
        $businessId = session('current_business_id');
        return $businessId ? Business::find($businessId) : null;
    }
}
