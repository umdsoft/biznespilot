<?php

namespace App\Http\Controllers\HR;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Traits\HasCurrentBusiness;
use App\Models\BusinessUser;
use App\Models\TurnoverRecord;
use Inertia\Inertia;

class TerminationWebController extends Controller
{
    use HasCurrentBusiness;

    public function index()
    {
        $business = $this->getCurrentBusiness();

        if (! $business) {
            return redirect()->route('login');
        }

        // Get active employees
        $activeEmployees = BusinessUser::where('business_id', $business->id)
            ->with('user:id,name,email')
            ->whereNotNull('accepted_at')
            ->whereNull('terminated_at')
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(fn ($member) => [
                'id' => $member->id,
                'user_id' => $member->user_id,
                'name' => $member->user->name ?? 'N/A',
                'email' => $member->user->email ?? null,
                'department' => $member->department_label,
                'position' => $member->position ?? 'Belgilanmagan',
                'joined_at' => $member->accepted_at?->format('d.m.Y'),
                'tenure_months' => $member->accepted_at ? now()->diffInMonths($member->accepted_at) : 0,
            ]);

        // Get terminated employees (turnover records)
        $terminatedEmployees = TurnoverRecord::where('business_id', $business->id)
            ->with('user:id,name,email')
            ->orderBy('termination_date', 'desc')
            ->get()
            ->map(fn ($record) => [
                'id' => $record->id,
                'user_id' => $record->user_id,
                'name' => $record->user->name ?? 'N/A',
                'email' => $record->user->email ?? null,
                'department' => $record->department,
                'position' => $record->position,
                'termination_date' => $record->termination_date?->format('d.m.Y'),
                'termination_type' => $record->termination_type,
                'termination_type_label' => $record->termination_type_label ?? ucfirst($record->termination_type ?? 'N/A'),
                'reason' => $record->reason,
                'tenure_months' => $record->tenure_months,
            ]);

        // Statistics
        $stats = [
            'active_employees' => $activeEmployees->count(),
            'terminated_this_year' => $terminatedEmployees->filter(
                fn ($r) => isset($r['termination_date']) && str_contains($r['termination_date'], now()->year)
            )->count(),
            'terminated_this_month' => $terminatedEmployees->filter(
                fn ($r) => isset($r['termination_date']) && str_contains($r['termination_date'], now()->format('m.Y'))
            )->count(),
            'voluntary' => $terminatedEmployees->where('termination_type', 'voluntary')->count(),
            'involuntary' => $terminatedEmployees->where('termination_type', 'involuntary')->count(),
        ];

        return Inertia::render('HR/Termination/Index', [
            'activeEmployees' => $activeEmployees,
            'terminatedEmployees' => $terminatedEmployees,
            'stats' => $stats,
            'currentBusiness' => [
                'id' => $business->id,
                'name' => $business->name,
            ],
        ]);
    }
}
