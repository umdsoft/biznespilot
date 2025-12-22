<?php

namespace App\Services;

use App\Models\Business;
use App\Models\BusinessMaturityAssessment;
use App\Models\BusinessProblem;
use App\Models\Competitor;
use App\Models\DreamBuyer;
use App\Models\Integration;
use App\Models\MarketingHypothesis;
use App\Models\MarketResearch;
use App\Models\MarketingMetrics;
use App\Models\OnboardingProgress;
use App\Models\OnboardingStep;
use App\Models\SalesMetrics;
use App\Models\StepDefinition;
use Illuminate\Support\Collection;

class OnboardingService
{
    /**
     * Initialize onboarding for a business
     */
    public function initializeOnboarding(Business $business): OnboardingProgress
    {
        // Create onboarding progress if not exists
        $progress = $business->onboardingProgress ?? OnboardingProgress::create([
            'business_id' => $business->id,
            'current_phase' => 1,
            'phase_1_status' => 'in_progress',
        ]);

        // Create maturity assessment if not exists
        if (!$business->maturityAssessment) {
            BusinessMaturityAssessment::create([
                'business_id' => $business->id,
            ]);
        }

        // Create onboarding steps for all step definitions
        $stepDefinitions = StepDefinition::active()->ordered()->get();

        foreach ($stepDefinitions as $stepDef) {
            OnboardingStep::firstOrCreate(
                [
                    'business_id' => $business->id,
                    'step_definition_id' => $stepDef->id,
                ],
                [
                    'is_completed' => false,
                    'completion_percent' => 0,
                ]
            );
        }

        return $progress->fresh();
    }

    /**
     * Ensure onboarding steps exist for a business
     */
    public function ensureStepsExist(Business $business): void
    {
        $stepDefinitions = StepDefinition::active()->ordered()->get();

        foreach ($stepDefinitions as $stepDef) {
            OnboardingStep::firstOrCreate(
                [
                    'business_id' => $business->id,
                    'step_definition_id' => $stepDef->id,
                ],
                [
                    'is_completed' => false,
                    'completion_percent' => 0,
                ]
            );
        }
    }

    /**
     * Calculate overall progress for a business
     */
    public function calculateProgress(Business $business): array
    {
        $progress = $business->onboardingProgress;

        if (!$progress) {
            $progress = $this->initializeOnboarding($business);
        } else {
            // Ensure onboarding steps exist
            $this->ensureStepsExist($business);
        }

        // Calculate category progress
        $categories = ['profile', 'kpi', 'framework'];
        $categoryProgress = [];
        $totalPercent = 0;

        foreach ($categories as $category) {
            $catProgress = $this->calculateCategoryProgress($business, $category);
            $categoryProgress[$category] = $catProgress;
            $totalPercent += $catProgress['percent'];
        }

        $overallPercent = (int) round($totalPercent / count($categories));

        // Update progress
        $progress->update([
            'phase_1_completion_percent' => $overallPercent,
            'phase_1_status' => $overallPercent >= 100 ? 'completed' : 'in_progress',
            'phase_1_completed_at' => $overallPercent >= 100 ? now() : null,
            'overall_completion_percent' => $this->calculateOverallPercent($progress),
        ]);

        // Get steps with details
        $steps = $this->getStepsWithStatus($business);

        return [
            'current_phase' => $progress->current_phase,
            'overall_percent' => $overallPercent,
            'phase_1' => [
                'status' => $progress->phase_1_status,
                'percent' => $progress->phase_1_completion_percent,
                'completed_at' => $progress->phase_1_completed_at,
            ],
            'phase_2' => [
                'status' => $progress->phase_2_status,
                'is_unlocked' => $progress->isPhase2Unlocked(),
            ],
            'phase_3' => [
                'status' => $progress->phase_3_status,
                'is_unlocked' => $progress->isPhase3Unlocked(),
            ],
            'phase_4' => [
                'status' => $progress->phase_4_status,
                'is_unlocked' => $progress->isPhase4Unlocked(),
                'launched_at' => $progress->launched_at,
            ],
            'categories' => $categoryProgress,
            'steps' => $steps,
            'can_start_phase_2' => $progress->canStartPhase2(),
            'is_launched' => $progress->isLaunched(),
        ];
    }

    /**
     * Calculate progress for a specific category
     * Uses actual completion_percent of each step for accurate progress calculation
     */
    public function calculateCategoryProgress(Business $business, string $category): array
    {
        $stepDefinitions = StepDefinition::active()
            ->forCategory($category)
            ->ordered()
            ->get();

        $totalSteps = $stepDefinitions->count();
        $completedSteps = 0;
        $requiredSteps = 0;
        $requiredCompleted = 0;
        $totalPercent = 0;
        $requiredTotalPercent = 0;

        foreach ($stepDefinitions as $stepDef) {
            $step = $business->onboardingSteps()
                ->where('step_definition_id', $stepDef->id)
                ->first();

            // Get actual completion percent from step or calculate via validation
            $stepPercent = 0;
            if ($step) {
                $stepPercent = $step->completion_percent ?? 0;
            }

            // If step has no percent, try to calculate from validation
            if ($stepPercent === 0) {
                $validation = $this->validateStep($business, $stepDef->code);
                $stepPercent = $validation['percent'] ?? 0;
            }

            $totalPercent += $stepPercent;

            if ($step && $step->is_completed) {
                $completedSteps++;
            }

            if ($stepDef->is_required) {
                $requiredSteps++;
                $requiredTotalPercent += $stepPercent;

                if ($step && $step->is_completed) {
                    $requiredCompleted++;
                }
            }
        }

        // Calculate average percent based on actual completion percentages
        $percent = $totalSteps > 0 ? (int) round($totalPercent / $totalSteps) : 0;
        $requiredPercent = $requiredSteps > 0 ? (int) round($requiredTotalPercent / $requiredSteps) : 100;

        return [
            'category' => $category,
            'total_steps' => $totalSteps,
            'completed_steps' => $completedSteps,
            'required_steps' => $requiredSteps,
            'required_completed' => $requiredCompleted,
            'percent' => $percent,
            'required_percent' => $requiredPercent,
            'is_complete' => $requiredPercent >= 100,
        ];
    }

    /**
     * Get steps with their status
     */
    public function getStepsWithStatus(Business $business): Collection
    {
        $stepDefinitions = StepDefinition::active()->ordered()->get();

        return $stepDefinitions->map(function ($stepDef) use ($business) {
            $step = $business->onboardingSteps()
                ->where('step_definition_id', $stepDef->id)
                ->first();

            $validation = $this->validateStep($business, $stepDef->code);

            return [
                'code' => $stepDef->code,
                'phase' => $stepDef->phase,
                'category' => $stepDef->category,
                'name' => $stepDef->name_uz,
                'description' => $stepDef->description_uz,
                'icon' => $stepDef->icon,
                'is_required' => $stepDef->is_required,
                'estimated_time' => $stepDef->estimated_time_minutes,
                'is_completed' => $step?->is_completed ?? false,
                'completion_percent' => $step?->completion_percent ?? 0,
                'started_at' => $step?->started_at,
                'completed_at' => $step?->completed_at,
                'is_locked' => $this->isStepLocked($business, $stepDef),
                'validation' => $validation,
            ];
        });
    }

    /**
     * Check if a step is locked (dependencies not met)
     * UPDATED: Hech qachon lock qilinmaydi - barcha qadamlar ixtiyoriy
     */
    public function isStepLocked(Business $business, StepDefinition $stepDef): bool
    {
        // Barcha qadamlar doim ochiq
        return false;
    }

    /**
     * Validate a step and return validation details
     */
    public function validateStep(Business $business, string $stepCode): array
    {
        $stepDef = StepDefinition::where('code', $stepCode)->first();

        if (!$stepDef) {
            return ['is_valid' => false, 'errors' => ['Step not found']];
        }

        $errors = [];
        $requiredFields = $stepDef->required_fields ?? [];

        switch ($stepCode) {
            case 'business_basic':
                if (empty($business->name)) $errors[] = 'Biznes nomi kiritilmagan';
                if (empty($business->category)) $errors[] = 'Kategoriya tanlanmagan';
                if (empty($business->business_type)) $errors[] = 'Biznes turi tanlanmagan';
                if (empty($business->business_model)) $errors[] = 'Biznes modeli tanlanmagan';
                break;

            case 'business_details':
                if (empty($business->team_size)) $errors[] = 'Jamoa hajmi kiritilmagan';
                if (empty($business->city)) $errors[] = 'Shahar kiritilmagan';
                if (empty($business->business_stage)) $errors[] = 'Biznes bosqichi tanlanmagan';
                break;

            case 'business_maturity':
                $assessment = $business->maturityAssessment;
                if (!$assessment) {
                    $errors[] = 'Baholash to\'ldirilmagan';
                } else {
                    if ($assessment->monthly_revenue_range === 'none' || empty($assessment->monthly_revenue_range)) {
                        $errors[] = 'Oylik daromad ko\'rsatilmagan';
                    }
                    if (empty($assessment->main_challenges)) {
                        $errors[] = 'Asosiy qiyinchiliklar tanlanmagan';
                    }
                }
                break;

            case 'integration_instagram':
                $hasInstagram = Integration::where('business_id', $business->id)
                    ->where('type', 'instagram')
                    ->where('status', 'connected')
                    ->exists();
                if (!$hasInstagram) $errors[] = 'Instagram ulanmagan';
                break;

            case 'integration_telegram':
                $hasTelegram = Integration::where('business_id', $business->id)
                    ->whereIn('type', ['telegram', 'telegram_channel', 'telegram_bot'])
                    ->where('status', 'connected')
                    ->exists();
                if (!$hasTelegram) $errors[] = 'Telegram ulanmagan';
                break;

            case 'framework_problem':
                $hasProblem = BusinessProblem::where('business_id', $business->id)
                    ->where('status', 'active')
                    ->exists();
                if (!$hasProblem) $errors[] = 'Kamida 1 ta muammo kiritilmagan';
                break;

            case 'framework_dream_buyer':
                $dreamBuyer = DreamBuyer::where('business_id', $business->id)
                    ->where('is_primary', true)
                    ->first();
                if (!$dreamBuyer) {
                    $errors[] = 'Dream Buyer yaratilmagan';
                } else {
                    // Check 9 questions
                    $questions = [
                        'where_spend_time', 'info_sources', 'frustrations',
                        'dreams', 'fears', 'communication_preferences',
                        'language_style', 'daily_routine', 'happiness_triggers'
                    ];
                    $answeredCount = 0;
                    foreach ($questions as $q) {
                        if (!empty($dreamBuyer->$q)) $answeredCount++;
                    }
                    if ($answeredCount < 9) {
                        $errors[] = "9 ta savoldan faqat {$answeredCount} tasi javoblangan";
                    }
                }
                break;

            case 'framework_competitors':
                $competitorCount = Competitor::where('business_id', $business->id)
                    ->where('is_active', true)
                    ->count();
                if ($competitorCount < 2) {
                    $errors[] = "Kamida 2 ta raqobatchi kerak (hozirda: {$competitorCount})";
                }
                break;

            case 'framework_hypotheses':
                $hypothesisCount = MarketingHypothesis::where('business_id', $business->id)->count();
                if ($hypothesisCount < 1) {
                    $errors[] = 'Kamida 1 ta gipoteza yaratilmagan';
                }
                break;

            // KPI Steps - optional but track completion based on data
            case 'kpi_sales':
                $salesMetrics = $business->salesMetrics;
                if ($salesMetrics && $salesMetrics->hasData()) {
                    // Has data - calculate completion percent from model
                    $percent = $salesMetrics->completion_percent ?? 100;
                    return [
                        'is_valid' => true,
                        'errors' => [],
                        'percent' => $percent,
                    ];
                }
                return [
                    'is_valid' => true,
                    'errors' => [],
                    'percent' => 0,
                ];

            case 'kpi_marketing':
                $marketingMetrics = $business->marketingMetrics;
                if ($marketingMetrics && $marketingMetrics->hasData()) {
                    // Has data - calculate completion percent from model
                    $percent = $marketingMetrics->completion_percent ?? 100;
                    return [
                        'is_valid' => true,
                        'errors' => [],
                        'percent' => $percent,
                    ];
                }
                return [
                    'is_valid' => true,
                    'errors' => [],
                    'percent' => 0,
                ];

            // Optional integration steps - check if connected
            case 'integration_amocrm':
            case 'integration_google_ads':
                // Integration steps - check if actually connected
                $integration = Integration::where('business_id', $business->id)
                    ->where('type', str_replace('integration_', '', $stepCode))
                    ->where('status', 'connected')
                    ->exists();
                return [
                    'is_valid' => true,
                    'errors' => [],
                    'percent' => $integration ? 100 : 0,
                ];

            case 'framework_research':
                // Research step - check if research data exists
                $hasResearch = MarketResearch::where('business_id', $business->id)->exists();
                return [
                    'is_valid' => true,
                    'errors' => [],
                    'percent' => $hasResearch ? 100 : 0,
                ];
        }

        $percent = empty($errors) ? 100 : $this->calculateStepPercent($stepCode, $business, $errors);

        return [
            'is_valid' => empty($errors),
            'errors' => $errors,
            'percent' => $percent,
        ];
    }

    /**
     * Calculate step completion percent
     */
    private function calculateStepPercent(string $stepCode, Business $business, array $errors): int
    {
        // Base calculation - if there are errors, calculate based on filled fields
        $stepDef = StepDefinition::where('code', $stepCode)->first();
        $requiredFields = $stepDef?->required_fields ?? [];
        $totalFields = count($requiredFields);

        if ($totalFields === 0) {
            return empty($errors) ? 100 : 0;
        }

        $errorCount = count($errors);
        $completedFields = $totalFields - $errorCount;

        return (int) round(($completedFields / $totalFields) * 100);
    }

    /**
     * Mark a step as complete
     */
    public function markStepComplete(Business $business, string $stepCode): void
    {
        $stepDef = StepDefinition::where('code', $stepCode)->first();

        if (!$stepDef) {
            return;
        }

        $step = $business->onboardingSteps()
            ->where('step_definition_id', $stepDef->id)
            ->first();

        if ($step) {
            $step->markCompleted();
        }

        // Recalculate progress
        $this->calculateProgress($business);
    }

    /**
     * Update step progress
     * Creates step if it doesn't exist, then updates its progress
     */
    public function updateStepProgress(Business $business, string $stepCode): void
    {
        $stepDef = StepDefinition::where('code', $stepCode)->first();

        if (!$stepDef) {
            return;
        }

        $validation = $this->validateStep($business, $stepCode);

        // Get or create the step
        $step = OnboardingStep::firstOrCreate(
            [
                'business_id' => $business->id,
                'step_definition_id' => $stepDef->id,
            ],
            [
                'is_completed' => false,
                'completion_percent' => 0,
            ]
        );

        // Update progress
        $step->updateProgress(
            $validation['percent'],
            $validation['is_valid'] ? null : $validation['errors']
        );

        // Recalculate overall progress
        $this->calculateProgress($business);
    }

    /**
     * Calculate overall completion percent (all phases)
     */
    public function calculateOverallPercent(OnboardingProgress $progress): int
    {
        $phaseWeights = [
            1 => 40, // Phase 1: Data Input = 40%
            2 => 20, // Phase 2: AI Diagnostic = 20%
            3 => 30, // Phase 3: Strategy = 30%
            4 => 10, // Phase 4: Launch = 10%
        ];

        $total = 0;

        // Phase 1
        $total += ($progress->phase_1_completion_percent / 100) * $phaseWeights[1];

        // Phase 2
        if ($progress->phase_2_status === 'completed') {
            $total += $phaseWeights[2];
        } elseif ($progress->phase_2_status === 'processing') {
            $total += $phaseWeights[2] * 0.5;
        }

        // Phase 3
        if ($progress->phase_3_status === 'completed') {
            $total += $phaseWeights[3];
        } elseif ($progress->phase_3_status === 'in_progress') {
            $total += $phaseWeights[3] * 0.5;
        }

        // Phase 4
        if ($progress->phase_4_status === 'launched') {
            $total += $phaseWeights[4];
        } elseif ($progress->phase_4_status === 'ready') {
            $total += $phaseWeights[4] * 0.5;
        }

        return (int) round($total);
    }

    /**
     * Check if Phase 2 can be started
     */
    public function canStartPhase2(Business $business): bool
    {
        $progress = $business->onboardingProgress;

        if (!$progress) {
            return false;
        }

        return $progress->canStartPhase2();
    }

    /**
     * Start Phase 2 (AI Diagnostic)
     */
    public function startPhase2(Business $business): bool
    {
        if (!$this->canStartPhase2($business)) {
            return false;
        }

        $progress = $business->onboardingProgress;
        $progress->unlockPhase2();
        $progress->startPhase2();

        return true;
    }

    /**
     * Get step details by step code
     */
    public function getStepDetails(Business $business, string $stepCode): ?array
    {
        $stepDef = StepDefinition::where('code', $stepCode)->first();

        if (!$stepDef) {
            return null;
        }

        $step = $business->onboardingSteps()
            ->where('step_definition_id', $stepDef->id)
            ->first();

        $validation = $this->validateStep($business, $stepCode);

        return [
            'code' => $stepDef->code,
            'phase' => $stepDef->phase,
            'category' => $stepDef->category,
            'name' => $stepDef->name_uz,
            'description' => $stepDef->description_uz,
            'icon' => $stepDef->icon,
            'is_required' => $stepDef->is_required,
            'estimated_time' => $stepDef->estimated_time_minutes,
            'is_completed' => $step?->is_completed ?? false,
            'completion_percent' => $step?->completion_percent ?? 0,
            'started_at' => $step?->started_at,
            'completed_at' => $step?->completed_at,
            'is_locked' => $this->isStepLocked($business, $stepDef),
            'validation' => $validation,
            'required_fields' => $stepDef->required_fields ?? [],
            'config' => $stepDef->config ?? [],
        ];
    }
}
