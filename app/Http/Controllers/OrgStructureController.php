<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Traits\HasCurrentBusiness;
use App\Models\BusinessType;
use App\Models\DepartmentTemplate;
use App\Models\OrgAssignment;
use App\Models\OrgDepartment;
use App\Models\OrgPosition;
use App\Models\OrgStructure;
use Illuminate\Http\Request;
use Inertia\Inertia;

class OrgStructureController extends Controller
{
    use HasCurrentBusiness;

    public function index()
    {
        $business = $this->getCurrentBusiness();

        if (! $business) {
            return redirect()->route('login');
        }

        $orgStructure = OrgStructure::with([
            'businessType',
            'departments.positions.assignments.user',
            'departments.children.positions.assignments.user',
        ])
            ->where('business_id', $business->id)
            ->first();

        return Inertia::render('HR/OrgStructure/Index', [
            'orgStructure' => $orgStructure,
            'business' => $business,
        ]);
    }

    public function create()
    {
        $business = $this->getCurrentBusiness();

        if (! $business) {
            return redirect()->route('login');
        }

        // Check if org structure already exists
        $existingStructure = OrgStructure::where('business_id', $business->id)->first();

        if ($existingStructure) {
            return redirect()->route('hr.org-structure.index')
                ->with('error', 'Tashkiliy tuzilma allaqachon mavjud');
        }

        $businessTypes = BusinessType::active()->get();

        return Inertia::render('HR/OrgStructure/Create', [
            'businessTypes' => $businessTypes,
            'business' => $business,
        ]);
    }

    public function store(Request $request)
    {
        $business = $this->getCurrentBusiness();

        if (! $business) {
            return redirect()->route('login');
        }

        $validated = $request->validate([
            'business_type_id' => 'required|exists:business_types,id',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'use_templates' => 'boolean',
        ]);

        // Create org structure
        $orgStructure = OrgStructure::create([
            'business_id' => $business->id,
            'business_type_id' => $validated['business_type_id'],
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'is_template_based' => $validated['use_templates'] ?? true,
            'is_active' => true,
        ]);

        // If using templates, create departments and positions from templates
        if ($validated['use_templates'] ?? true) {
            $this->createFromTemplates($orgStructure, $validated['business_type_id']);
        }

        return redirect()->route('hr.org-structure.index')
            ->with('success', 'Tashkiliy tuzilma muvaffaqiyatli yaratildi');
    }

    private function createFromTemplates(OrgStructure $orgStructure, int $businessTypeId)
    {
        // Get all relevant department templates (static + dynamic for this business type)
        $departmentTemplates = DepartmentTemplate::where(function ($query) use ($businessTypeId) {
            $query->where('type', 'static')
                ->orWhere(function ($q) use ($businessTypeId) {
                    $q->where('type', 'dynamic')
                        ->where('business_type_id', $businessTypeId);
                });
        })->with('positionTemplates')->get();

        foreach ($departmentTemplates as $template) {
            // Create department
            $department = OrgDepartment::create([
                'org_structure_id' => $orgStructure->id,
                'department_template_id' => $template->id,
                'name' => $template->name_uz,
                'code' => $template->code,
                'color' => $template->color,
                'icon' => $template->icon,
                'yqm_description' => $template->yqm_description,
                'order' => $template->order,
                'is_active' => true,
            ]);

            // Create positions from templates
            foreach ($template->positionTemplates as $positionTemplate) {
                OrgPosition::create([
                    'org_department_id' => $department->id,
                    'position_template_id' => $positionTemplate->id,
                    'title' => $positionTemplate->title_uz,
                    'level' => $positionTemplate->level,
                    'yqm_primary' => $positionTemplate->yqm_primary,
                    'yqm_description' => $positionTemplate->yqm_description,
                    'yqm_metrics' => $positionTemplate->yqm_metrics,
                    'required_count' => 1,
                    'current_count' => 0,
                    'order' => $positionTemplate->order,
                    'is_active' => true,
                ]);
            }
        }
    }

    public function show(OrgStructure $orgStructure)
    {
        $business = $this->getCurrentBusiness();

        if (! $business || $orgStructure->business_id !== $business->id) {
            return redirect()->route('login');
        }

        $orgStructure->load([
            'businessType',
            'departments' => function ($query) {
                $query->whereNull('parent_id')->with([
                    'positions.assignments.user',
                    'children.positions.assignments.user',
                ]);
            },
        ]);

        return Inertia::render('HR/OrgStructure/Show', [
            'orgStructure' => $orgStructure,
            'business' => $business,
        ]);
    }

    public function edit(OrgStructure $orgStructure)
    {
        $business = $this->getCurrentBusiness();

        if (! $business || $orgStructure->business_id !== $business->id) {
            return redirect()->route('login');
        }

        $orgStructure->load([
            'businessType',
            'departments.positions',
        ]);

        $businessTypes = BusinessType::active()->get();

        return Inertia::render('HR/OrgStructure/Edit', [
            'orgStructure' => $orgStructure,
            'businessTypes' => $businessTypes,
            'business' => $business,
        ]);
    }

    public function update(Request $request, OrgStructure $orgStructure)
    {
        $business = $this->getCurrentBusiness();

        if (! $business || $orgStructure->business_id !== $business->id) {
            return redirect()->route('login');
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        $orgStructure->update($validated);

        return redirect()->route('hr.org-structure.index')
            ->with('success', 'Tashkiliy tuzilma yangilandi');
    }

    public function destroy(OrgStructure $orgStructure)
    {
        $business = $this->getCurrentBusiness();

        if (! $business || $orgStructure->business_id !== $business->id) {
            return redirect()->route('login');
        }

        $orgStructure->delete();

        return redirect()->route('hr.org-structure.index')
            ->with('success', 'Tashkiliy tuzilma o\'chirildi');
    }

    // Department management
    public function storeDepartment(Request $request, OrgStructure $orgStructure)
    {
        $business = $this->getCurrentBusiness();

        if (! $business || $orgStructure->business_id !== $business->id) {
            return redirect()->route('login');
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:50',
            'color' => 'nullable|string',
            'icon' => 'nullable|string',
            'yqm_description' => 'nullable|string',
            'parent_id' => 'nullable|exists:org_departments,id',
            'order' => 'integer',
        ]);

        $validated['org_structure_id'] = $orgStructure->id;
        $validated['is_active'] = true;

        $department = OrgDepartment::create($validated);

        return back()->with('success', 'Bo\'lim qo\'shildi');
    }

    public function updateDepartment(Request $request, OrgDepartment $department)
    {
        $business = $this->getCurrentBusiness();

        if (! $business || $department->orgStructure->business_id !== $business->id) {
            return redirect()->route('login');
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:50',
            'color' => 'nullable|string',
            'icon' => 'nullable|string',
            'yqm_description' => 'nullable|string',
            'order' => 'integer',
            'is_active' => 'boolean',
        ]);

        $department->update($validated);

        return back()->with('success', 'Bo\'lim yangilandi');
    }

    public function destroyDepartment(OrgDepartment $department)
    {
        $business = $this->getCurrentBusiness();

        if (! $business || $department->orgStructure->business_id !== $business->id) {
            return redirect()->route('login');
        }

        $department->delete();

        return back()->with('success', 'Bo\'lim o\'chirildi');
    }

    // Position management
    public function storePosition(Request $request, OrgDepartment $department)
    {
        $business = $this->getCurrentBusiness();

        if (! $business || $department->orgStructure->business_id !== $business->id) {
            return redirect()->route('login');
        }

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'level' => 'required|integer|min:0|max:4',
            'yqm_primary' => 'required|string',
            'yqm_description' => 'nullable|string',
            'yqm_metrics' => 'nullable|array',
            'required_count' => 'required|integer|min:1',
            'salary_min' => 'nullable|numeric',
            'salary_max' => 'nullable|numeric',
            'order' => 'integer',
        ]);

        $validated['org_department_id'] = $department->id;
        $validated['current_count'] = 0;
        $validated['is_active'] = true;

        $position = OrgPosition::create($validated);

        return back()->with('success', 'Lavozim qo\'shildi');
    }

    public function updatePosition(Request $request, OrgPosition $position)
    {
        $business = $this->getCurrentBusiness();

        if (! $business || $position->orgDepartment->orgStructure->business_id !== $business->id) {
            return redirect()->route('login');
        }

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'level' => 'required|integer|min:0|max:4',
            'yqm_primary' => 'required|string',
            'yqm_description' => 'nullable|string',
            'yqm_metrics' => 'nullable|array',
            'required_count' => 'required|integer|min:1',
            'salary_min' => 'nullable|numeric',
            'salary_max' => 'nullable|numeric',
            'is_active' => 'boolean',
        ]);

        $position->update($validated);

        return back()->with('success', 'Lavozim yangilandi');
    }

    public function destroyPosition(OrgPosition $position)
    {
        $business = $this->getCurrentBusiness();

        if (! $business || $position->orgDepartment->orgStructure->business_id !== $business->id) {
            return redirect()->route('login');
        }

        $position->delete();

        return back()->with('success', 'Lavozim o\'chirildi');
    }

    // Assignment management
    public function assignUser(Request $request, OrgPosition $position)
    {
        $business = $this->getCurrentBusiness();

        if (! $business || $position->orgDepartment->orgStructure->business_id !== $business->id) {
            return redirect()->route('login');
        }

        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'assigned_date' => 'required|date',
            'is_primary' => 'boolean',
            'notes' => 'nullable|string',
        ]);

        $validated['org_position_id'] = $position->id;
        $validated['business_id'] = $business->id;
        $validated['is_active'] = true;

        $assignment = OrgAssignment::create($validated);

        // Update position current_count
        $position->increment('current_count');

        return back()->with('success', 'Xodim tayinlandi');
    }

    public function updateAssignment(Request $request, OrgAssignment $assignment)
    {
        $business = $this->getCurrentBusiness();

        if (! $business || $assignment->business_id !== $business->id) {
            return redirect()->route('login');
        }

        $validated = $request->validate([
            'end_date' => 'nullable|date',
            'is_active' => 'boolean',
            'performance_summary' => 'nullable|array',
            'notes' => 'nullable|string',
        ]);

        $wasActive = $assignment->is_active;
        $assignment->update($validated);

        // Update position current_count if status changed
        if ($wasActive && ! $assignment->is_active) {
            $assignment->orgPosition->decrement('current_count');
        } elseif (! $wasActive && $assignment->is_active) {
            $assignment->orgPosition->increment('current_count');
        }

        return back()->with('success', 'Tayinlash yangilandi');
    }

    public function destroyAssignment(OrgAssignment $assignment)
    {
        $business = $this->getCurrentBusiness();

        if (! $business || $assignment->business_id !== $business->id) {
            return redirect()->route('login');
        }

        if ($assignment->is_active) {
            $assignment->orgPosition->decrement('current_count');
        }

        $assignment->delete();

        return back()->with('success', 'Tayinlash bekor qilindi');
    }
}
