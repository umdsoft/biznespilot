<?php

namespace App\Http\Controllers\HR;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Traits\HasCurrentBusiness;
use App\Models\Bonus;
use App\Models\PayrollCycle;
use App\Models\SalaryHistory;
use App\Models\SalaryStructure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class PayrollController extends Controller
{
    use HasCurrentBusiness;

    /**
     * Display payroll dashboard
     */
    public function index(Request $request)
    {
        $business = $this->getCurrentBusiness();

        if (! $business) {
            return redirect()->route('login');
        }

        // Get payroll cycles
        $cycles = PayrollCycle::where('business_id', $business->id)
            ->with(['approver:id,name', 'processor:id,name'])
            ->withCount('payslips')
            ->orderBy('start_date', 'desc')
            ->get()
            ->map(fn ($cycle) => [
                'id' => $cycle->id,
                'period' => $cycle->period,
                'start_date' => $cycle->start_date->format('d.m.Y'),
                'end_date' => $cycle->end_date->format('d.m.Y'),
                'payment_date' => $cycle->payment_date->format('d.m.Y'),
                'status' => $cycle->status,
                'status_label' => $cycle->status_label,
                'status_color' => $cycle->status_color,
                'total_gross' => $cycle->total_gross,
                'total_deductions' => $cycle->total_deductions,
                'total_net' => $cycle->total_net,
                'employee_count' => $cycle->payslips_count,
                'approver_name' => $cycle->approver->name ?? null,
            ]);

        // Stats
        $stats = [
            'total_employees' => $business->users()->count(),
            'active_salaries' => SalaryStructure::where('business_id', $business->id)
                ->active()
                ->count(),
            'pending_bonuses' => Bonus::where('business_id', $business->id)
                ->pending()
                ->sum('amount'),
            'this_month_payroll' => PayrollCycle::where('business_id', $business->id)
                ->where('period', now()->format('Y-m'))
                ->sum('total_net'),
        ];

        return Inertia::render('HR/Payroll/Index', [
            'cycles' => $cycles,
            'stats' => $stats,
        ]);
    }

    /**
     * Salary structures management
     */
    public function salaryStructures()
    {
        $business = $this->getCurrentBusiness();

        if (! $business) {
            return redirect()->route('login');
        }

        $structures = SalaryStructure::where('business_id', $business->id)
            ->with('user:id,name')
            ->orderBy('is_active', 'desc')
            ->orderBy('effective_from', 'desc')
            ->get()
            ->map(fn ($structure) => [
                'id' => $structure->id,
                'user_name' => $structure->user->name,
                'base_salary' => $structure->base_salary,
                'payment_frequency' => $structure->payment_frequency,
                'payment_frequency_label' => $structure->payment_frequency_label,
                'effective_from' => $structure->effective_from->format('d.m.Y'),
                'effective_until' => $structure->effective_until?->format('d.m.Y'),
                'is_active' => $structure->is_active,
                'total_allowances' => $structure->total_allowances,
                'total_deductions' => $structure->total_deductions,
                'gross_salary' => $structure->gross_salary,
                'net_salary' => $structure->net_salary,
                'allowances' => $structure->allowances,
                'deductions' => $structure->deductions,
            ]);

        // Get employees for assignment
        $employees = $business->users()
            ->select('users.id', 'users.name')
            ->get();

        return Inertia::render('HR/Payroll/SalaryStructures', [
            'structures' => $structures,
            'employees' => $employees,
        ]);
    }

    /**
     * Store salary structure
     */
    public function storeSalaryStructure(Request $request)
    {
        $business = $this->getCurrentBusiness();

        if (! $business) {
            return back()->with('error', 'Biznes topilmadi');
        }

        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'base_salary' => 'required|numeric|min:0',
            'payment_frequency' => 'required|in:monthly,bi-weekly,weekly',
            'effective_from' => 'required|date',
            'allowances' => 'nullable|array',
            'allowances.*.name' => 'required|string',
            'allowances.*.amount' => 'required|numeric|min:0',
            'deductions' => 'nullable|array',
            'deductions.*.name' => 'required|string',
            'deductions.*.amount' => 'required|numeric|min:0',
        ]);

        // Deactivate previous salary structures
        SalaryStructure::where('business_id', $business->id)
            ->where('user_id', $validated['user_id'])
            ->update(['is_active' => false, 'effective_until' => now()]);

        // Create new salary structure
        $structure = SalaryStructure::create([
            'business_id' => $business->id,
            'user_id' => $validated['user_id'],
            'base_salary' => $validated['base_salary'],
            'payment_frequency' => $validated['payment_frequency'],
            'effective_from' => $validated['effective_from'],
            'allowances' => $validated['allowances'] ?? [],
            'deductions' => $validated['deductions'] ?? [],
            'is_active' => true,
        ]);

        // Record salary history if there was a previous structure
        $previousStructure = SalaryStructure::where('business_id', $business->id)
            ->where('user_id', $validated['user_id'])
            ->where('id', '!=', $structure->id)
            ->orderBy('effective_from', 'desc')
            ->first();

        if ($previousStructure) {
            $changeAmount = $validated['base_salary'] - $previousStructure->base_salary;
            $changePercentage = $previousStructure->base_salary > 0
                ? ($changeAmount / $previousStructure->base_salary) * 100
                : 0;

            SalaryHistory::create([
                'business_id' => $business->id,
                'user_id' => $validated['user_id'],
                'old_salary' => $previousStructure->base_salary,
                'new_salary' => $validated['base_salary'],
                'change_amount' => $changeAmount,
                'change_percentage' => $changePercentage,
                'reason' => 'Maosh tuzilmasi yangilandi',
                'effective_date' => $validated['effective_from'],
                'changed_by' => Auth::id(),
            ]);
        }

        return back()->with('success', 'Maosh tuzilmasi saqlandi');
    }

    /**
     * Bonuses management
     */
    public function bonuses()
    {
        $business = $this->getCurrentBusiness();

        if (! $business) {
            return redirect()->route('login');
        }

        $bonuses = Bonus::where('business_id', $business->id)
            ->with(['user:id,name', 'approver:id,name'])
            ->orderBy('granted_date', 'desc')
            ->get()
            ->map(fn ($bonus) => [
                'id' => $bonus->id,
                'user_name' => $bonus->user->name,
                'type' => $bonus->type,
                'type_label' => $bonus->type_label,
                'title' => $bonus->title,
                'description' => $bonus->description,
                'amount' => $bonus->amount,
                'granted_date' => $bonus->granted_date->format('d.m.Y'),
                'is_paid' => $bonus->is_paid,
                'paid_at' => $bonus->paid_at?->format('d.m.Y'),
                'approver_name' => $bonus->approver->name ?? null,
            ]);

        $employees = $business->users()
            ->select('users.id', 'users.name')
            ->get();

        return Inertia::render('HR/Payroll/Bonuses', [
            'bonuses' => $bonuses,
            'employees' => $employees,
            'bonusTypes' => Bonus::TYPES,
        ]);
    }

    /**
     * Store bonus
     */
    public function storeBonus(Request $request)
    {
        $business = $this->getCurrentBusiness();

        if (! $business) {
            return back()->with('error', 'Biznes topilmadi');
        }

        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'type' => 'required|in:performance,annual,spot,referral',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'amount' => 'required|numeric|min:0',
            'granted_date' => 'required|date',
        ]);

        Bonus::create([
            'business_id' => $business->id,
            'user_id' => $validated['user_id'],
            'type' => $validated['type'],
            'title' => $validated['title'],
            'description' => $validated['description'] ?? null,
            'amount' => $validated['amount'],
            'granted_date' => $validated['granted_date'],
            'approved_by' => Auth::id(),
            'is_paid' => false,
        ]);

        return back()->with('success', 'Bonus qo\'shildi');
    }
}
