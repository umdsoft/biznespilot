<?php

namespace App\Http\Controllers\Api\BusinessSystematization;

use App\Http\Controllers\Controller;
use App\Models\MotivationScheme;
use App\Models\MotivationComponent;
use App\Models\EmployeeMotivation;
use App\Models\MotivationCalculation;
use App\Models\KeyTaskMap;
use App\Models\KeyTask;
use App\Services\BusinessSystematization\MotivationCalculatorService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

/**
 * Motivation System API Controller
 * Implements Denis Shenukov's motivation methodology:
 * - Two-parameter: Fix + Bonus
 * - Three-parameter: Fix + Soft Salary + Bonus
 * - KPI calculation: (Fact - Base) / (Plan - Base)
 */
class MotivationController extends Controller
{
    protected MotivationCalculatorService $calculatorService;

    public function __construct(MotivationCalculatorService $calculatorService)
    {
        $this->calculatorService = $calculatorService;
    }

    // ==================== Motivation Schemes ====================

    /**
     * List motivation schemes
     */
    public function listSchemes(Request $request): JsonResponse
    {
        $businessId = $request->user()->current_business_id;

        $schemes = MotivationScheme::where('business_id', $businessId)
            ->with(['components', 'department', 'position'])
            ->when($request->department_id, fn($q) => $q->where('department_id', $request->department_id))
            ->when($request->position_id, fn($q) => $q->where('position_id', $request->position_id))
            ->when($request->active_only, fn($q) => $q->active())
            ->orderBy('name')
            ->paginate($request->per_page ?? 15);

        return response()->json([
            'success' => true,
            'data' => $schemes,
        ]);
    }

    /**
     * Get motivation scheme details
     */
    public function getScheme(MotivationScheme $scheme): JsonResponse
    {
        $this->authorize('view', $scheme);

        return response()->json([
            'success' => true,
            'data' => $scheme->load(['components', 'department', 'position', 'employeeMotivations.user']),
        ]);
    }

    /**
     * Create motivation scheme
     */
    public function createScheme(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'scheme_type' => 'required|in:two_parameter,three_parameter',
            'department_id' => 'nullable|uuid|exists:departments,id',
            'position_id' => 'nullable|uuid|exists:positions,id',
            'bonus_period' => 'required|in:monthly,quarterly,yearly',
            'base_salary_min' => 'nullable|numeric|min:0',
            'base_salary_max' => 'nullable|numeric|min:0',
            'bonus_fund_percent' => 'nullable|numeric|min:0|max:100',
            'is_active' => 'boolean',
        ]);

        $validated['business_id'] = $request->user()->current_business_id;
        $validated['created_by'] = $request->user()->id;

        $scheme = MotivationScheme::create($validated);

        return response()->json([
            'success' => true,
            'data' => $scheme,
            'message' => 'Motivatsiya sxemasi yaratildi',
        ], 201);
    }

    /**
     * Update motivation scheme
     */
    public function updateScheme(Request $request, MotivationScheme $scheme): JsonResponse
    {
        $this->authorize('update', $scheme);

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'scheme_type' => 'sometimes|in:two_parameter,three_parameter',
            'bonus_period' => 'sometimes|in:monthly,quarterly,yearly',
            'base_salary_min' => 'nullable|numeric|min:0',
            'base_salary_max' => 'nullable|numeric|min:0',
            'bonus_fund_percent' => 'nullable|numeric|min:0|max:100',
            'is_active' => 'boolean',
        ]);

        $scheme->update($validated);

        return response()->json([
            'success' => true,
            'data' => $scheme->fresh(['components']),
            'message' => 'Motivatsiya sxemasi yangilandi',
        ]);
    }

    // ==================== Motivation Components ====================

    /**
     * Add component to scheme
     */
    public function addComponent(Request $request, MotivationScheme $scheme): JsonResponse
    {
        $this->authorize('update', $scheme);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'component_type' => 'required|in:fixed_salary,soft_salary,bonus,penalty',
            'calculation_type' => 'required|in:fixed,percentage,scale,formula',
            'base_amount' => 'nullable|numeric|min:0',
            'percentage_value' => 'nullable|numeric',
            'max_amount' => 'nullable|numeric|min:0',
            'scale_table' => 'nullable|array',
            'formula' => 'nullable|string',
            'kpi_linkage' => 'nullable|array',
            'requirements' => 'nullable|array',
            'weight' => 'nullable|numeric|min:0|max:100',
            'order' => 'nullable|integer|min:0',
        ]);

        $validated['motivation_scheme_id'] = $scheme->id;
        $validated['business_id'] = $scheme->business_id;

        $component = MotivationComponent::create($validated);

        return response()->json([
            'success' => true,
            'data' => $component,
            'message' => 'Komponent qo\'shildi',
        ], 201);
    }

    /**
     * Update component
     */
    public function updateComponent(Request $request, MotivationComponent $component): JsonResponse
    {
        $this->authorize('update', $component->motivationScheme);

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'calculation_type' => 'sometimes|in:fixed,percentage,scale,formula',
            'base_amount' => 'nullable|numeric|min:0',
            'percentage_value' => 'nullable|numeric',
            'max_amount' => 'nullable|numeric|min:0',
            'scale_table' => 'nullable|array',
            'formula' => 'nullable|string',
            'kpi_linkage' => 'nullable|array',
            'requirements' => 'nullable|array',
            'weight' => 'nullable|numeric|min:0|max:100',
            'order' => 'nullable|integer|min:0',
            'is_active' => 'boolean',
        ]);

        $component->update($validated);

        return response()->json([
            'success' => true,
            'data' => $component->fresh(),
            'message' => 'Komponent yangilandi',
        ]);
    }

    /**
     * Delete component
     */
    public function deleteComponent(MotivationComponent $component): JsonResponse
    {
        $this->authorize('update', $component->motivationScheme);

        $component->delete();

        return response()->json([
            'success' => true,
            'message' => 'Komponent o\'chirildi',
        ]);
    }

    // ==================== Employee Motivation ====================

    /**
     * Assign motivation scheme to employee
     */
    public function assignToEmployee(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'user_id' => 'required|uuid|exists:users,id',
            'motivation_scheme_id' => 'required|uuid|exists:motivation_schemes,id',
            'fixed_salary' => 'required|numeric|min:0',
            'soft_salary_amount' => 'nullable|numeric|min:0',
            'bonus_percent' => 'nullable|numeric|min:0|max:100',
            'valid_from' => 'required|date',
            'valid_until' => 'nullable|date|after:valid_from',
            'custom_components' => 'nullable|array',
            'notes' => 'nullable|string',
        ]);

        $validated['business_id'] = $request->user()->current_business_id;
        $validated['created_by'] = $request->user()->id;
        $validated['is_active'] = true;

        // Deactivate previous assignments
        EmployeeMotivation::where('business_id', $validated['business_id'])
            ->where('user_id', $validated['user_id'])
            ->active()
            ->update(['is_active' => false]);

        $employeeMotivation = EmployeeMotivation::create($validated);

        return response()->json([
            'success' => true,
            'data' => $employeeMotivation->load(['user', 'motivationScheme']),
            'message' => 'Motivatsiya sxemasi xodimga tayinlandi',
        ], 201);
    }

    /**
     * Get employee's motivation assignment
     */
    public function getEmployeeMotivation(Request $request, string $userId): JsonResponse
    {
        $businessId = $request->user()->current_business_id;

        $employeeMotivation = EmployeeMotivation::where('business_id', $businessId)
            ->where('user_id', $userId)
            ->active()
            ->currentlyValid()
            ->with(['user', 'motivationScheme.components'])
            ->first();

        if (!$employeeMotivation) {
            return response()->json([
                'success' => false,
                'message' => 'Xodim uchun motivatsiya sxemasi topilmadi',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $employeeMotivation,
        ]);
    }

    // ==================== Calculations ====================

    /**
     * Calculate motivation for an employee
     */
    public function calculate(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'user_id' => 'required|uuid|exists:users,id',
            'period_start' => 'required|date',
            'period_end' => 'required|date|after:period_start',
            // Context data for calculation
            'plan_sales_plan' => 'nullable|numeric',
            'fact_sales_plan' => 'nullable|numeric',
            'base_sales_plan' => 'nullable|numeric',
            'revenue' => 'nullable|numeric',
            'profit' => 'nullable|numeric',
            'completed_requirements' => 'nullable|array',
            'receivables_collection_rate' => 'nullable|numeric|min:0|max:100',
        ]);

        $businessId = $request->user()->current_business_id;

        $employeeMotivation = EmployeeMotivation::where('business_id', $businessId)
            ->where('user_id', $validated['user_id'])
            ->active()
            ->currentlyValid()
            ->first();

        if (!$employeeMotivation) {
            return response()->json([
                'success' => false,
                'message' => 'Xodim uchun motivatsiya sxemasi topilmadi',
            ], 404);
        }

        $context = collect($validated)
            ->except(['user_id', 'period_start', 'period_end'])
            ->toArray();

        $calculation = $this->calculatorService->calculateForEmployee(
            $employeeMotivation,
            Carbon::parse($validated['period_start']),
            Carbon::parse($validated['period_end']),
            $context
        );

        return response()->json([
            'success' => true,
            'data' => $calculation->load(['user', 'employeeMotivation.motivationScheme']),
            'message' => 'Motivatsiya hisoblandi',
        ]);
    }

    /**
     * Get calculation history for an employee
     */
    public function getCalculationHistory(Request $request, string $userId): JsonResponse
    {
        $businessId = $request->user()->current_business_id;

        $calculations = MotivationCalculation::where('business_id', $businessId)
            ->where('user_id', $userId)
            ->with(['employeeMotivation.motivationScheme'])
            ->orderByDesc('period_start')
            ->paginate($request->per_page ?? 12);

        return response()->json([
            'success' => true,
            'data' => $calculations,
        ]);
    }

    /**
     * Approve calculation
     */
    public function approveCalculation(Request $request, MotivationCalculation $calculation): JsonResponse
    {
        $this->authorize('approve', $calculation);

        $calculation->update([
            'status' => 'approved',
            'approved_by' => $request->user()->id,
            'approved_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'data' => $calculation->fresh(),
            'message' => 'Hisob tasdiqlandi',
        ]);
    }

    /**
     * Calculate KPI score (helper endpoint)
     */
    public function calculateKpi(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'plan' => 'required|numeric',
            'fact' => 'required|numeric',
            'base' => 'required|numeric',
        ]);

        $kpiScore = $this->calculatorService->calculateKpiScore(
            $validated['plan'],
            $validated['fact'],
            $validated['base']
        );

        // Interpretation
        $interpretation = match(true) {
            $kpiScore < 0 => ['status' => 'critical', 'label' => 'Kritik', 'color' => 'red'],
            $kpiScore < 0.5 => ['status' => 'poor', 'label' => 'Yomon', 'color' => 'orange'],
            $kpiScore < 1 => ['status' => 'below_target', 'label' => 'Reja ostida', 'color' => 'yellow'],
            $kpiScore == 1 => ['status' => 'on_target', 'label' => 'Reja bajarildi', 'color' => 'green'],
            default => ['status' => 'above_target', 'label' => 'Reja oshirildi', 'color' => 'blue'],
        };

        return response()->json([
            'success' => true,
            'data' => [
                'kpi_score' => $kpiScore,
                'kpi_percent' => round($kpiScore * 100, 2),
                'interpretation' => $interpretation,
                'formula' => "(Fakt - Baza) / (Reja - Baza) = ({$validated['fact']} - {$validated['base']}) / ({$validated['plan']} - {$validated['base']})",
            ],
        ]);
    }

    /**
     * Generate scale table
     */
    public function generateScaleTable(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'base_percent' => 'nullable|numeric|min:0|max:100',
            'max_percent' => 'nullable|numeric|min:0|max:200',
            'step' => 'nullable|numeric|min:1|max:50',
            'progressive' => 'boolean',
        ]);

        $table = MotivationCalculatorService::generateScaleTable(
            $validated['base_percent'] ?? 80,
            $validated['max_percent'] ?? 120,
            $validated['step'] ?? 10,
            $validated['progressive'] ?? true
        );

        return response()->json([
            'success' => true,
            'data' => [
                'type' => ($validated['progressive'] ?? true) ? 'progressive' : 'regressive',
                'table' => $table,
            ],
        ]);
    }

    // ==================== Key Task Maps ====================

    /**
     * List key task maps
     */
    public function listKeyTaskMaps(Request $request): JsonResponse
    {
        $businessId = $request->user()->current_business_id;

        $maps = KeyTaskMap::where('business_id', $businessId)
            ->with(['user', 'tasks'])
            ->when($request->user_id, fn($q) => $q->where('user_id', $request->user_id))
            ->when($request->department_id, fn($q) => $q->where('department_id', $request->department_id))
            ->orderByDesc('period_start')
            ->paginate($request->per_page ?? 15);

        return response()->json([
            'success' => true,
            'data' => $maps,
        ]);
    }

    /**
     * Create key task map
     */
    public function createKeyTaskMap(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'user_id' => 'required|uuid|exists:users,id',
            'department_id' => 'nullable|uuid|exists:departments,id',
            'period_start' => 'required|date',
            'period_end' => 'required|date|after:period_start',
            'total_bonus_fund' => 'required|numeric|min:0',
            'min_completion_percent' => 'nullable|numeric|min:0|max:100',
            'full_bonus_percent' => 'nullable|numeric|min:0|max:100',
            'notes' => 'nullable|string',
            'tasks' => 'required|array|min:1',
            'tasks.*.name' => 'required|string|max:255',
            'tasks.*.description' => 'nullable|string',
            'tasks.*.weight' => 'required|numeric|min:0|max:100',
            'tasks.*.deadline' => 'nullable|date',
            'tasks.*.success_criteria' => 'nullable|string',
        ]);

        $validated['business_id'] = $request->user()->current_business_id;
        $validated['created_by'] = $request->user()->id;

        $tasks = $validated['tasks'];
        unset($validated['tasks']);

        $map = KeyTaskMap::create($validated);

        foreach ($tasks as $index => $taskData) {
            $taskData['key_task_map_id'] = $map->id;
            $taskData['business_id'] = $map->business_id;
            $taskData['order'] = $index;
            KeyTask::create($taskData);
        }

        return response()->json([
            'success' => true,
            'data' => $map->load('tasks'),
            'message' => 'Asosiy vazifalar kartasi yaratildi',
        ], 201);
    }

    /**
     * Update key task status
     */
    public function updateKeyTask(Request $request, KeyTask $task): JsonResponse
    {
        $this->authorize('update', $task->keyTaskMap);

        $validated = $request->validate([
            'status' => 'sometimes|in:pending,in_progress,completed,cancelled',
            'completion_percent' => 'sometimes|numeric|min:0|max:100',
            'completed_at' => 'nullable|date',
            'completion_notes' => 'nullable|string',
        ]);

        $task->update($validated);

        // Calculate and return updated bonus
        $map = $task->keyTaskMap;
        $bonusCalculation = $this->calculatorService->calculateKeyTaskMapBonus($map);

        return response()->json([
            'success' => true,
            'data' => [
                'task' => $task->fresh(),
                'bonus_calculation' => $bonusCalculation,
            ],
            'message' => 'Vazifa yangilandi',
        ]);
    }

    /**
     * Calculate key task map bonus
     */
    public function calculateKeyTaskMapBonus(KeyTaskMap $map): JsonResponse
    {
        $this->authorize('view', $map);

        $bonusCalculation = $this->calculatorService->calculateKeyTaskMapBonus($map);

        return response()->json([
            'success' => true,
            'data' => [
                'map' => $map->load('tasks'),
                'bonus_calculation' => $bonusCalculation,
            ],
        ]);
    }
}
