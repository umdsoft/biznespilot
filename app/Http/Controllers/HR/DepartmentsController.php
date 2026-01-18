<?php

namespace App\Http\Controllers\HR;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Traits\HasCurrentBusiness;
use App\Models\BusinessUser;
use Inertia\Inertia;

class DepartmentsController extends Controller
{
    use HasCurrentBusiness;

    public function index()
    {
        $business = $this->getCurrentBusiness();

        if (! $business) {
            return redirect()->route('login');
        }

        // Count employees by department
        $departmentStats = [];
        foreach (BusinessUser::DEPARTMENTS as $key => $label) {
            $count = BusinessUser::where('business_id', $business->id)
                ->where('department', $key)
                ->whereNotNull('accepted_at')
                ->count();

            $departmentStats[$key] = [
                'code' => $key,
                'label' => $label,
                'count' => $count,
            ];
        }

        return Inertia::render('HR/Departments/Index', [
            'departments' => $departmentStats,
        ]);
    }
}
