<?php

namespace App\Http\Controllers\HR;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Traits\HasCurrentBusiness;
use App\Models\BusinessUser;
use Inertia\Inertia;

class ContractsWebController extends Controller
{
    use HasCurrentBusiness;

    public function index()
    {
        $business = $this->getCurrentBusiness();

        if (! $business) {
            return redirect()->route('login');
        }

        // Get employees with contract info
        $employees = BusinessUser::where('business_id', $business->id)
            ->with('user:id,name,email,phone')
            ->whereNotNull('accepted_at')
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(fn ($member) => [
                'id' => $member->id,
                'user_id' => $member->user_id,
                'name' => $member->user->name ?? 'N/A',
                'email' => $member->user->email ?? null,
                'phone' => $member->user->phone ?? null,
                'department' => $member->department,
                'department_label' => $member->department_label,
                'position' => $member->position ?? 'Belgilanmagan',
                'contract_type' => $member->contract_type ?? 'unlimited',
                'contract_start' => $member->contract_start_date ?? $member->accepted_at?->format('Y-m-d'),
                'contract_end' => $member->contract_end_date ?? null,
                'salary' => $member->salary ?? null,
                'work_schedule' => $member->work_schedule ?? 'full_time',
                'joined_at' => $member->accepted_at?->format('d.m.Y'),
            ]);

        return Inertia::render('HR/Contracts/Index', [
            'employees' => $employees,
            'currentBusiness' => [
                'id' => $business->id,
                'name' => $business->name,
            ],
        ]);
    }
}
