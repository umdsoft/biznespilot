<?php

namespace App\Services\BusinessSystematization;

use App\Models\EmployeeClassification;
use App\Models\FunctionKnowledgeRegistry;
use App\Models\User;
use Illuminate\Support\Collection;

/**
 * Employee Classification Service
 * Implements book methodology:
 * - Thinker (Думатель) vs Doer (Делатель) classification
 * - Star employee risk detection
 * - Position fit analysis
 */
class EmployeeClassificationService
{
    /**
     * Get HR analytics dashboard data
     */
    public function getDashboardData(string $businessId): array
    {
        return [
            'classification_summary' => $this->getClassificationSummary($businessId),
            'star_employees' => $this->getStarEmployeeRisks($businessId),
            'position_mismatches' => $this->getPositionMismatches($businessId),
            'knowledge_risks' => $this->getKnowledgeRisks($businessId),
            'recommendations' => $this->getRecommendations($businessId),
        ];
    }

    /**
     * Get employee classification summary
     */
    public function getClassificationSummary(string $businessId): array
    {
        $classifications = EmployeeClassification::where('business_id', $businessId)->get();

        $thinkers = $classifications->where('employee_type', 'thinker');
        $doers = $classifications->where('employee_type', 'doer');
        $mixed = $classifications->where('employee_type', 'mixed');

        return [
            'total' => $classifications->count(),
            'thinkers' => [
                'count' => $thinkers->count(),
                'percent' => $classifications->count() > 0
                    ? round(($thinkers->count() / $classifications->count()) * 100, 1)
                    : 0,
                'description' => 'Mustaqil qaror qiluvchilar, TOP lavozimlar uchun',
            ],
            'doers' => [
                'count' => $doers->count(),
                'percent' => $classifications->count() > 0
                    ? round(($doers->count() / $classifications->count()) * 100, 1)
                    : 0,
                'description' => 'Ijrochilar, aniq ko\'rsatmalar bo\'yicha ishlaydi',
            ],
            'mixed' => [
                'count' => $mixed->count(),
                'percent' => $classifications->count() > 0
                    ? round(($mixed->count() / $classifications->count()) * 100, 1)
                    : 0,
                'description' => 'Aralash tip, ikki rolda ham ishlashi mumkin',
            ],
            'unclassified' => $this->getUnclassifiedCount($businessId),
        ];
    }

    /**
     * Get count of employees not yet classified
     */
    protected function getUnclassifiedCount(string $businessId): int
    {
        // Get all business users
        $totalEmployees = \DB::table('business_user')
            ->where('business_id', $businessId)
            ->where('status', 'active')
            ->count();

        $classifiedCount = EmployeeClassification::where('business_id', $businessId)->count();

        return max(0, $totalEmployees - $classifiedCount);
    }

    /**
     * Get star employee risks (from book's methodology)
     */
    public function getStarEmployeeRisks(string $businessId): array
    {
        $starEmployees = EmployeeClassification::where('business_id', $businessId)
            ->where('is_star_employee', true)
            ->with('user')
            ->get();

        $risks = $starEmployees->map(function ($employee) {
            $warnings = [];

            if ($employee->has_unique_knowledge) {
                $warnings[] = [
                    'type' => 'unique_knowledge',
                    'severity' => 'high',
                    'message' => 'Faqat bu xodim biladi - bilimni dokumentlash kerak',
                ];
            }

            if ($employee->has_client_dependencies) {
                $warnings[] = [
                    'type' => 'client_dependency',
                    'severity' => 'high',
                    'message' => 'Mijozlar bu xodimga bog\'liq - munosabatlarni tizimlashtirish kerak',
                ];
            }

            if ($employee->blocks_new_employees) {
                $warnings[] = [
                    'type' => 'blocks_newcomers',
                    'severity' => 'medium',
                    'message' => 'Yangi xodimlarning rivojlanishiga to\'sqinlik qiladi',
                ];
            }

            return [
                'user_id' => $employee->user_id,
                'user_name' => $employee->user?->name ?? 'Noma\'lum',
                'employee_type' => $employee->employee_type,
                'departure_risk' => $employee->departure_risk,
                'replacement_difficulty' => $employee->replacement_difficulty,
                'warnings' => $warnings,
                'risk_score' => count($warnings),
            ];
        })->sortByDesc('risk_score')->values();

        return [
            'count' => $starEmployees->count(),
            'high_risk_count' => $risks->where('risk_score', '>=', 2)->count(),
            'employees' => $risks,
            'total_risk_score' => $risks->sum('risk_score'),
        ];
    }

    /**
     * Get position mismatches (wrong type in wrong position)
     */
    public function getPositionMismatches(string $businessId): array
    {
        $mismatches = EmployeeClassification::where('business_id', $businessId)
            ->where('position_fit', false)
            ->with('user')
            ->get();

        return [
            'count' => $mismatches->count(),
            'employees' => $mismatches->map(function ($employee) {
                return [
                    'user_id' => $employee->user_id,
                    'user_name' => $employee->user?->name ?? 'Noma\'lum',
                    'employee_type' => $employee->employee_type,
                    'employee_type_label' => $employee->employee_type_label,
                    'notes' => $employee->position_fit_notes,
                    'recommendation' => $this->getMismatchRecommendation($employee),
                ];
            }),
        ];
    }

    /**
     * Get recommendation for position mismatch
     */
    protected function getMismatchRecommendation(EmployeeClassification $employee): string
    {
        if ($employee->employee_type === 'thinker') {
            return "Bu xodim 'Думатель' (mustaqil) - ijrochi lavozimda emas, boshqaruv lavozimiga ko'taring.";
        }

        if ($employee->employee_type === 'doer') {
            return "Bu xodim 'Делатель' (ijrochi) - boshqaruv lavozimida samarasiz, ijrochi lavozimga o'tkazing.";
        }

        return "Xodimning kuchli tomonlarini aniqlang va mos lavozimga joylashtiring.";
    }

    /**
     * Get knowledge risks (functions known by only one person)
     */
    public function getKnowledgeRisks(string $businessId): array
    {
        $atRisk = FunctionKnowledgeRegistry::where('business_id', $businessId)
            ->where('is_at_risk', true)
            ->get();

        $notDocumented = FunctionKnowledgeRegistry::where('business_id', $businessId)
            ->whereIn('criticality', ['high', 'critical'])
            ->where('is_documented', false)
            ->get();

        return [
            'at_risk_count' => $atRisk->count(),
            'not_documented_count' => $notDocumented->count(),
            'critical_risks' => $atRisk->where('criticality', 'critical')->count(),
            'functions_at_risk' => $atRisk->map(function ($func) {
                return [
                    'id' => $func->id,
                    'name' => $func->function_name,
                    'criticality' => $func->criticality,
                    'criticality_label' => $func->criticality_label,
                    'holders_count' => $func->knowledge_holders_count,
                    'is_documented' => $func->is_documented,
                    'action_needed' => $func->knowledge_holders_count === 0
                        ? 'KRITIK: Hech kim bilmaydi!'
                        : 'Bilimni boshqa xodimlarga o\'rgatish kerak',
                ];
            }),
        ];
    }

    /**
     * Get overall recommendations
     */
    public function getRecommendations(string $businessId): array
    {
        $recommendations = [];

        // Check unclassified employees
        $unclassified = $this->getUnclassifiedCount($businessId);
        if ($unclassified > 0) {
            $recommendations[] = [
                'priority' => 'high',
                'area' => 'classification',
                'message' => "{$unclassified} ta xodim hali klassifikatsiya qilinmagan. Ularni baholang.",
            ];
        }

        // Check star employee risks
        $starRisks = $this->getStarEmployeeRisks($businessId);
        if ($starRisks['high_risk_count'] > 0) {
            $recommendations[] = [
                'priority' => 'critical',
                'area' => 'star_employees',
                'message' => "{$starRisks['high_risk_count']} ta 'yulduz' xodim yuqori xavf darajasida. Ularning bilimini dokumentlang.",
            ];
        }

        // Check position mismatches
        $mismatches = $this->getPositionMismatches($businessId);
        if ($mismatches['count'] > 0) {
            $recommendations[] = [
                'priority' => 'medium',
                'area' => 'position_fit',
                'message' => "{$mismatches['count']} ta xodim noto'g'ri lavozimda. Ularni mos lavozimga joylashtiring.",
            ];
        }

        // Check knowledge risks
        $knowledgeRisks = $this->getKnowledgeRisks($businessId);
        if ($knowledgeRisks['critical_risks'] > 0) {
            $recommendations[] = [
                'priority' => 'critical',
                'area' => 'knowledge',
                'message' => "{$knowledgeRisks['critical_risks']} ta kritik funksiya faqat 1 kishi tomonidan bilinar. Zudlik bilan o'rgatish kerak.",
            ];
        }

        return $recommendations;
    }

    /**
     * Classify an employee
     */
    public function classifyEmployee(
        string $businessId,
        string $userId,
        string $employeeType,
        bool $positionFit,
        ?string $positionFitNotes = null,
        array $competencyScores = [],
        ?string $assessedBy = null
    ): EmployeeClassification {
        $classification = EmployeeClassification::updateOrCreate(
            [
                'business_id' => $businessId,
                'user_id' => $userId,
            ],
            [
                'employee_type' => $employeeType,
                'position_fit' => $positionFit,
                'position_fit_notes' => $positionFitNotes,
                'competency_scores' => $competencyScores,
                'assessed_by' => $assessedBy,
                'assessed_at' => now(),
            ]
        );

        return $classification;
    }

    /**
     * Mark employee as star (with risk factors)
     */
    public function markAsStarEmployee(
        string $businessId,
        string $userId,
        bool $hasUniqueKnowledge = false,
        bool $hasClientDependencies = false,
        bool $blocksNewEmployees = false,
        string $departureRisk = 'medium',
        float $replacementDifficulty = 2.0
    ): EmployeeClassification {
        $classification = EmployeeClassification::where('business_id', $businessId)
            ->where('user_id', $userId)
            ->firstOrFail();

        $classification->update([
            'is_star_employee' => true,
            'has_unique_knowledge' => $hasUniqueKnowledge,
            'has_client_dependencies' => $hasClientDependencies,
            'blocks_new_employees' => $blocksNewEmployees,
            'departure_risk' => $departureRisk,
            'replacement_difficulty' => $replacementDifficulty,
        ]);

        return $classification;
    }

    /**
     * Register function knowledge
     */
    public function registerFunctionKnowledge(
        string $businessId,
        string $functionName,
        string $criticality,
        array $knowledgeableUserIds = [],
        ?string $description = null
    ): FunctionKnowledgeRegistry {
        return FunctionKnowledgeRegistry::updateOrCreate(
            [
                'business_id' => $businessId,
                'function_name' => $functionName,
            ],
            [
                'description' => $description,
                'criticality' => $criticality,
                'knowledgeable_users' => $knowledgeableUserIds,
            ]
        );
    }
}
