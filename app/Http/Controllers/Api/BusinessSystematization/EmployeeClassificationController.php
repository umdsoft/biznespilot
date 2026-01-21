<?php

namespace App\Http\Controllers\Api\BusinessSystematization;

use App\Http\Controllers\Controller;
use App\Models\EmployeeClassification;
use App\Models\FunctionKnowledgeRegistry;
use App\Models\VacancyCard;
use App\Models\InterviewProtocol;
use App\Models\BusinessDiagnostics;
use App\Services\BusinessSystematization\EmployeeClassificationService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

/**
 * Employee Classification API Controller
 * Implements Denis Shenukov's HR methodology:
 * - Thinker (Думатель) vs Doer (Делатель) classification
 * - Star employee risk detection
 * - Knowledge risk management
 * - Vacancy cards and interview protocols
 */
class EmployeeClassificationController extends Controller
{
    protected EmployeeClassificationService $classificationService;

    public function __construct(EmployeeClassificationService $classificationService)
    {
        $this->classificationService = $classificationService;
    }

    /**
     * Get HR analytics dashboard
     */
    public function dashboard(Request $request): JsonResponse
    {
        $businessId = $request->user()->current_business_id;

        $data = $this->classificationService->getDashboardData($businessId);

        return response()->json([
            'success' => true,
            'data' => $data,
        ]);
    }

    /**
     * Get classification summary
     */
    public function classificationSummary(Request $request): JsonResponse
    {
        $businessId = $request->user()->current_business_id;

        $data = $this->classificationService->getClassificationSummary($businessId);

        return response()->json([
            'success' => true,
            'data' => $data,
        ]);
    }

    /**
     * Get star employee risks
     */
    public function starEmployeeRisks(Request $request): JsonResponse
    {
        $businessId = $request->user()->current_business_id;

        $data = $this->classificationService->getStarEmployeeRisks($businessId);

        return response()->json([
            'success' => true,
            'data' => $data,
        ]);
    }

    /**
     * Get position mismatches
     */
    public function positionMismatches(Request $request): JsonResponse
    {
        $businessId = $request->user()->current_business_id;

        $data = $this->classificationService->getPositionMismatches($businessId);

        return response()->json([
            'success' => true,
            'data' => $data,
        ]);
    }

    /**
     * Get knowledge risks
     */
    public function knowledgeRisks(Request $request): JsonResponse
    {
        $businessId = $request->user()->current_business_id;

        $data = $this->classificationService->getKnowledgeRisks($businessId);

        return response()->json([
            'success' => true,
            'data' => $data,
        ]);
    }

    // ==================== Employee Classifications ====================

    /**
     * List employee classifications
     */
    public function listClassifications(Request $request): JsonResponse
    {
        $businessId = $request->user()->current_business_id;

        $classifications = EmployeeClassification::where('business_id', $businessId)
            ->with(['user', 'assessedByUser'])
            ->when($request->employee_type, fn($q) => $q->where('employee_type', $request->employee_type))
            ->when($request->is_star, fn($q) => $q->starEmployees())
            ->when($request->position_mismatch, fn($q) => $q->positionMismatches())
            ->orderBy('assessed_at', 'desc')
            ->paginate($request->per_page ?? 15);

        return response()->json([
            'success' => true,
            'data' => $classifications,
        ]);
    }

    /**
     * Classify an employee
     */
    public function classifyEmployee(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'user_id' => 'required|uuid|exists:users,id',
            'employee_type' => 'required|in:thinker,doer,mixed',
            'position_fit' => 'required|boolean',
            'position_fit_notes' => 'nullable|string',
            'competency_scores' => 'nullable|array',
            'competency_scores.leadership' => 'nullable|numeric|min:0|max:10',
            'competency_scores.execution' => 'nullable|numeric|min:0|max:10',
            'competency_scores.communication' => 'nullable|numeric|min:0|max:10',
            'competency_scores.problem_solving' => 'nullable|numeric|min:0|max:10',
            'competency_scores.teamwork' => 'nullable|numeric|min:0|max:10',
        ]);

        $businessId = $request->user()->current_business_id;

        $classification = $this->classificationService->classifyEmployee(
            $businessId,
            $validated['user_id'],
            $validated['employee_type'],
            $validated['position_fit'],
            $validated['position_fit_notes'] ?? null,
            $validated['competency_scores'] ?? [],
            $request->user()->id
        );

        return response()->json([
            'success' => true,
            'data' => $classification->load('user'),
            'message' => 'Xodim klassifikatsiya qilindi',
        ]);
    }

    /**
     * Mark employee as star (with risk factors)
     */
    public function markAsStarEmployee(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'user_id' => 'required|uuid|exists:users,id',
            'has_unique_knowledge' => 'boolean',
            'has_client_dependencies' => 'boolean',
            'blocks_new_employees' => 'boolean',
            'departure_risk' => 'in:low,medium,high,critical',
            'replacement_difficulty' => 'numeric|min:1|max:5',
        ]);

        $businessId = $request->user()->current_business_id;

        $classification = $this->classificationService->markAsStarEmployee(
            $businessId,
            $validated['user_id'],
            $validated['has_unique_knowledge'] ?? false,
            $validated['has_client_dependencies'] ?? false,
            $validated['blocks_new_employees'] ?? false,
            $validated['departure_risk'] ?? 'medium',
            $validated['replacement_difficulty'] ?? 2.0
        );

        return response()->json([
            'success' => true,
            'data' => $classification->load('user'),
            'message' => "Xodim 'yulduz' sifatida belgilandi",
        ]);
    }

    // ==================== Function Knowledge Registry ====================

    /**
     * List function knowledge registry
     */
    public function listFunctionKnowledge(Request $request): JsonResponse
    {
        $businessId = $request->user()->current_business_id;

        $functions = FunctionKnowledgeRegistry::where('business_id', $businessId)
            ->when($request->at_risk_only, fn($q) => $q->atRisk())
            ->when($request->criticality, fn($q) => $q->where('criticality', $request->criticality))
            ->orderByRaw("FIELD(criticality, 'critical', 'high', 'medium', 'low')")
            ->paginate($request->per_page ?? 15);

        return response()->json([
            'success' => true,
            'data' => $functions,
        ]);
    }

    /**
     * Register function knowledge
     */
    public function registerFunctionKnowledge(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'function_name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'criticality' => 'required|in:low,medium,high,critical',
            'knowledgeable_users' => 'nullable|array',
            'knowledgeable_users.*' => 'uuid|exists:users,id',
        ]);

        $businessId = $request->user()->current_business_id;

        $function = $this->classificationService->registerFunctionKnowledge(
            $businessId,
            $validated['function_name'],
            $validated['criticality'],
            $validated['knowledgeable_users'] ?? [],
            $validated['description'] ?? null
        );

        return response()->json([
            'success' => true,
            'data' => $function,
            'message' => 'Funksiya bilimi ro\'yxatga olindi',
        ]);
    }

    /**
     * Update function knowledge holders
     */
    public function updateKnowledgeHolders(Request $request, FunctionKnowledgeRegistry $function): JsonResponse
    {
        $this->authorize('update', $function);

        $validated = $request->validate([
            'knowledgeable_users' => 'required|array',
            'knowledgeable_users.*' => 'uuid|exists:users,id',
            'is_documented' => 'boolean',
            'documentation_url' => 'nullable|url',
        ]);

        $function->update([
            'knowledgeable_users' => $validated['knowledgeable_users'],
            'is_documented' => $validated['is_documented'] ?? $function->is_documented,
            'documentation_url' => $validated['documentation_url'] ?? $function->documentation_url,
        ]);

        return response()->json([
            'success' => true,
            'data' => $function->fresh(),
            'message' => 'Bilim egalari yangilandi',
        ]);
    }

    // ==================== Vacancy Cards ====================

    /**
     * List vacancy cards
     */
    public function listVacancies(Request $request): JsonResponse
    {
        $businessId = $request->user()->current_business_id;

        $vacancies = VacancyCard::where('business_id', $businessId)
            ->with(['department', 'position', 'createdBy'])
            ->when($request->status, fn($q) => $q->where('status', $request->status))
            ->when($request->department_id, fn($q) => $q->where('department_id', $request->department_id))
            ->when($request->employee_type, fn($q) => $q->where('required_employee_type', $request->employee_type))
            ->orderByDesc('created_at')
            ->paginate($request->per_page ?? 15);

        return response()->json([
            'success' => true,
            'data' => $vacancies,
        ]);
    }

    /**
     * Create vacancy card
     */
    public function createVacancy(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'position_title' => 'required|string|max:255',
            'department_id' => 'required|uuid|exists:departments,id',
            'position_id' => 'nullable|uuid|exists:positions,id',
            'required_employee_type' => 'required|in:thinker,doer,mixed',
            'reports_to_user_id' => 'nullable|uuid|exists:users,id',
            // Job description
            'purpose' => 'required|string',
            'key_responsibilities' => 'required|array|min:1',
            'key_responsibilities.*' => 'string',
            'authority_boundaries' => 'nullable|array',
            // Requirements
            'hard_skills' => 'nullable|array',
            'soft_skills' => 'nullable|array',
            'experience_requirements' => 'nullable|string',
            'education_requirements' => 'nullable|string',
            // KPIs
            'key_kpis' => 'nullable|array',
            'trial_period_tasks' => 'nullable|array',
            'trial_period_kpis' => 'nullable|array',
            // Compensation
            'salary_range_min' => 'nullable|numeric|min:0',
            'salary_range_max' => 'nullable|numeric|min:0',
            'bonus_structure' => 'nullable|string',
            // Deadlines
            'urgency' => 'required|in:low,medium,high,critical',
            'target_hire_date' => 'nullable|date',
        ]);

        $validated['business_id'] = $request->user()->current_business_id;
        $validated['created_by'] = $request->user()->id;
        $validated['status'] = 'draft';

        $vacancy = VacancyCard::create($validated);

        return response()->json([
            'success' => true,
            'data' => $vacancy->load(['department', 'position']),
            'message' => 'Vakansiya kartasi yaratildi',
        ], 201);
    }

    /**
     * Update vacancy card
     */
    public function updateVacancy(Request $request, VacancyCard $vacancy): JsonResponse
    {
        $this->authorize('update', $vacancy);

        $validated = $request->validate([
            'status' => 'sometimes|in:draft,open,interviewing,offer_sent,filled,cancelled',
            'position_title' => 'sometimes|string|max:255',
            'required_employee_type' => 'sometimes|in:thinker,doer,mixed',
            'purpose' => 'sometimes|string',
            'key_responsibilities' => 'sometimes|array|min:1',
            'hard_skills' => 'nullable|array',
            'soft_skills' => 'nullable|array',
            'key_kpis' => 'nullable|array',
            'trial_period_tasks' => 'nullable|array',
            'trial_period_kpis' => 'nullable|array',
            'salary_range_min' => 'nullable|numeric|min:0',
            'salary_range_max' => 'nullable|numeric|min:0',
            'urgency' => 'sometimes|in:low,medium,high,critical',
            'target_hire_date' => 'nullable|date',
            'filled_by_user_id' => 'nullable|uuid|exists:users,id',
            'filled_at' => 'nullable|date',
        ]);

        $vacancy->update($validated);

        return response()->json([
            'success' => true,
            'data' => $vacancy->fresh(['department', 'position']),
            'message' => 'Vakansiya yangilandi',
        ]);
    }

    // ==================== Interview Protocols ====================

    /**
     * List interview protocols for a vacancy
     */
    public function listInterviews(Request $request, VacancyCard $vacancy): JsonResponse
    {
        $this->authorize('view', $vacancy);

        $interviews = InterviewProtocol::where('vacancy_id', $vacancy->id)
            ->with(['interviewer', 'candidate'])
            ->orderByDesc('interview_date')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $interviews,
        ]);
    }

    /**
     * Create interview protocol
     */
    public function createInterview(Request $request, VacancyCard $vacancy): JsonResponse
    {
        $this->authorize('update', $vacancy);

        $validated = $request->validate([
            'candidate_id' => 'nullable|uuid',
            'candidate_name' => 'required|string|max:255',
            'candidate_email' => 'nullable|email',
            'candidate_phone' => 'nullable|string|max:50',
            'interview_date' => 'required|date',
            'interview_type' => 'required|in:phone,video,in_person,assessment',
            // "Biladi" section - knowledge assessment
            'knowledge_assessment' => 'nullable|array',
            'knowledge_assessment.*.skill' => 'required|string',
            'knowledge_assessment.*.score' => 'required|numeric|min:0|max:10',
            'knowledge_assessment.*.notes' => 'nullable|string',
            // "Uddalaydi" section - capability assessment
            'capability_assessment' => 'nullable|array',
            'capability_assessment.*.task' => 'required|string',
            'capability_assessment.*.score' => 'required|numeric|min:0|max:10',
            'capability_assessment.*.notes' => 'nullable|string',
            // Employee type assessment
            'assessed_employee_type' => 'nullable|in:thinker,doer,mixed',
            'employee_type_indicators' => 'nullable|array',
            // Overall
            'strengths' => 'nullable|array',
            'weaknesses' => 'nullable|array',
            'red_flags' => 'nullable|array',
            'overall_score' => 'nullable|numeric|min:0|max:10',
            'recommendation' => 'nullable|in:strong_hire,hire,maybe,no_hire,strong_no_hire',
            'notes' => 'nullable|string',
        ]);

        $validated['vacancy_id'] = $vacancy->id;
        $validated['business_id'] = $vacancy->business_id;
        $validated['interviewer_id'] = $request->user()->id;

        $interview = InterviewProtocol::create($validated);

        return response()->json([
            'success' => true,
            'data' => $interview->load('interviewer'),
            'message' => 'Intervyu protokoli yaratildi',
        ], 201);
    }

    /**
     * Update interview protocol
     */
    public function updateInterview(Request $request, InterviewProtocol $interview): JsonResponse
    {
        $this->authorize('update', $interview->vacancy);

        $validated = $request->validate([
            'knowledge_assessment' => 'nullable|array',
            'capability_assessment' => 'nullable|array',
            'assessed_employee_type' => 'nullable|in:thinker,doer,mixed',
            'employee_type_indicators' => 'nullable|array',
            'strengths' => 'nullable|array',
            'weaknesses' => 'nullable|array',
            'red_flags' => 'nullable|array',
            'overall_score' => 'nullable|numeric|min:0|max:10',
            'recommendation' => 'nullable|in:strong_hire,hire,maybe,no_hire,strong_no_hire',
            'notes' => 'nullable|string',
        ]);

        $interview->update($validated);

        return response()->json([
            'success' => true,
            'data' => $interview->fresh(),
            'message' => 'Intervyu protokoli yangilandi',
        ]);
    }

    // ==================== Business Diagnostics ====================

    /**
     * Get latest business diagnostics
     */
    public function getLatestDiagnostics(Request $request): JsonResponse
    {
        $businessId = $request->user()->current_business_id;

        $diagnostics = BusinessDiagnostics::where('business_id', $businessId)
            ->latest()
            ->first();

        return response()->json([
            'success' => true,
            'data' => $diagnostics,
            'questions' => BusinessDiagnostics::getQuestionLabels(),
        ]);
    }

    /**
     * Create business diagnostics assessment
     */
    public function createDiagnostics(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'q1_owner_role' => 'required|in:tired_employee,leader',
            'q2_main_competency' => 'required|in:product,sales,management',
            'q3_company_type' => 'required|in:family_business,ptu,goal_instrument',
            'q4_management_style' => 'required|in:plan_control,problem_solving',
            'q5_tops_role' => 'required|in:leaders,secretaries',
            'q6_motivation_exists' => 'required|in:salary_only,salary_plus_bonus',
            'q7_motivation_type' => 'required|in:individual,team',
        ]);

        $validated['business_id'] = $request->user()->current_business_id;
        $validated['assessed_by'] = $request->user()->id;
        $validated['assessment_date'] = now();

        $diagnostics = BusinessDiagnostics::create($validated);

        // Calculate evolution level and recommendations
        $diagnostics->evolution_level = $diagnostics->calculateEvolutionLevel();
        $diagnostics->recommendations = $diagnostics->generateRecommendations();
        $diagnostics->save();

        return response()->json([
            'success' => true,
            'data' => [
                'diagnostics' => $diagnostics,
                'evolution_level_label' => $diagnostics->evolution_level_label,
            ],
            'message' => 'Diagnostika bajarildi',
        ], 201);
    }

    /**
     * Get diagnostics history
     */
    public function getDiagnosticsHistory(Request $request): JsonResponse
    {
        $businessId = $request->user()->current_business_id;

        $history = BusinessDiagnostics::where('business_id', $businessId)
            ->with('assessedByUser')
            ->latest()
            ->paginate($request->per_page ?? 10);

        return response()->json([
            'success' => true,
            'data' => $history,
        ]);
    }
}
