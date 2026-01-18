<?php

namespace App\Http\Controllers\SalesHead;

use App\Http\Controllers\Controller;
use App\Models\BusinessUser;
use Inertia\Inertia;

class TeamController extends Controller
{
    protected function getBusinessId()
    {
        return session('current_business_id');
    }

    public function index()
    {
        $businessId = $this->getBusinessId();

        $members = BusinessUser::where('business_id', $businessId)
            ->where('department', 'sales_operator')
            ->with('user:id,name,phone')
            ->get();

        return Inertia::render('SalesHead/Team/Index', [
            'members' => $members,
        ]);
    }

    public function show($member)
    {
        return Inertia::render('SalesHead/Team/Show');
    }

    public function performance($member)
    {
        return Inertia::render('SalesHead/Team/Performance');
    }
}
