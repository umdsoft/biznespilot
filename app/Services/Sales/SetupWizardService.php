<?php

namespace App\Services\Sales;

use App\Models\SalesAchievementDefinition;
use App\Models\SalesBonusSetting;
use App\Models\SalesKpiSetting;
use App\Models\SalesKpiTemplateSet;
use App\Models\SalesPenaltyRule;
use App\Models\SalesSetupProgress;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SetupWizardService
{
    public function __construct(
        protected KpiSettingsService $kpiSettingsService,
        protected AchievementService $achievementService
    ) {}

    /**
     * Mavjud shablonlarni olish
     */
    public function getAvailableTemplates(?string $industry = null): Collection
    {
        $query = SalesKpiTemplateSet::active()->ordered();

        if ($industry) {
            $query->forIndustry($industry);
        }

        return $query->get()->map(fn ($template) => [
            'id' => $template->id,
            'code' => $template->code,
            'name' => $template->name,
            'description' => $template->description,
            'industry' => $template->industry,
            'industry_label' => $template->industry_label,
            'icon' => $template->icon,
            'is_featured' => $template->is_featured,
            'kpi_count' => $template->kpi_count,
            'bonus_count' => $template->bonus_count,
            'penalty_rules_count' => $template->penalty_rules_count,
            'usage_count' => $template->usage_count,
        ]);
    }

    /**
     * Setup boshlash
     */
    public function startSetup(string $businessId, string $userId, ?string $templateId = null): SalesSetupProgress
    {
        $progress = SalesSetupProgress::getOrCreate($businessId, $userId);

        if ($templateId) {
            $progress->update(['template_set_id' => $templateId]);
        }

        Log::info('Sales setup started', [
            'business_id' => $businessId,
            'user_id' => $userId,
            'template_id' => $templateId,
        ]);

        return $progress;
    }

    /**
     * Shablon tanlash bosqichini tugatish
     */
    public function completeSelectTemplate(
        string $businessId,
        string $templateId
    ): SalesSetupProgress {
        $progress = SalesSetupProgress::where('business_id', $businessId)->firstOrFail();
        $template = SalesKpiTemplateSet::findOrFail($templateId);

        $progress->update(['template_set_id' => $templateId]);
        $progress->completeStep('select_template', [
            'template_id' => $templateId,
            'template_name' => $template->name,
        ]);

        // Shablon ishlatilganini belgilash
        $template->incrementUsage();

        return $progress->fresh();
    }

    /**
     * KPI sozlash bosqichini tugatish
     */
    public function completeCustomizeKpi(
        string $businessId,
        array $kpiSettings
    ): SalesSetupProgress {
        $progress = SalesSetupProgress::where('business_id', $businessId)->firstOrFail();

        // KPI sozlamalarini saqlash
        DB::transaction(function () use ($businessId, $kpiSettings) {
            foreach ($kpiSettings as $kpiData) {
                SalesKpiSetting::updateOrCreate(
                    [
                        'business_id' => $businessId,
                        'kpi_type' => $kpiData['kpi_type'],
                    ],
                    [
                        'name' => $kpiData['name'] ?? SalesKpiSetting::KPI_TYPES[$kpiData['kpi_type']] ?? $kpiData['kpi_type'],
                        'description' => $kpiData['description'] ?? null,
                        'measurement_unit' => $kpiData['measurement_unit'] ?? 'count',
                        'calculation_method' => $kpiData['calculation_method'] ?? 'sum',
                        'data_source' => $kpiData['data_source'] ?? 'auto',
                        'period_type' => $kpiData['period_type'] ?? 'monthly',
                        'weight' => $kpiData['weight'] ?? 10,
                        'target_min' => $kpiData['target_min'] ?? 0,
                        'target_good' => $kpiData['target_good'] ?? null,
                        'target_excellent' => $kpiData['target_excellent'] ?? null,
                        'is_active' => $kpiData['is_active'] ?? true,
                        'sort_order' => $kpiData['sort_order'] ?? 0,
                    ]
                );
            }
        });

        $progress->completeStep('customize_kpi', [
            'kpi_count' => count($kpiSettings),
        ]);

        return $progress->fresh();
    }

    /**
     * Maqsadlar bosqichini tugatish
     */
    public function completeSetTargets(
        string $businessId,
        array $targets
    ): SalesSetupProgress {
        $progress = SalesSetupProgress::where('business_id', $businessId)->firstOrFail();

        // Maqsadlarni saqlash - bu KpiSettingsService orqali amalga oshiriladi
        // Targets format: [kpi_setting_id => target_value, ...]
        $progress->completeStep('set_targets', [
            'targets_count' => count($targets),
            'targets' => $targets,
        ]);

        return $progress->fresh();
    }

    /**
     * Bonus sozlash bosqichini tugatish
     */
    public function completeConfigureBonus(
        string $businessId,
        array $bonusSettings
    ): SalesSetupProgress {
        $progress = SalesSetupProgress::where('business_id', $businessId)->firstOrFail();

        // Bonus sozlamalarini saqlash
        DB::transaction(function () use ($businessId, $bonusSettings) {
            foreach ($bonusSettings as $bonusData) {
                SalesBonusSetting::updateOrCreate(
                    [
                        'business_id' => $businessId,
                        'bonus_type' => $bonusData['bonus_type'],
                    ],
                    [
                        'name' => $bonusData['name'] ?? SalesBonusSetting::BONUS_TYPES[$bonusData['bonus_type']] ?? $bonusData['bonus_type'],
                        'description' => $bonusData['description'] ?? null,
                        'calculation_type' => $bonusData['calculation_type'] ?? 'percentage',
                        'base_amount' => $bonusData['base_amount'] ?? 0,
                        'percentage' => $bonusData['percentage'] ?? null,
                        'tiers' => $bonusData['tiers'] ?? SalesBonusSetting::DEFAULT_TIERS,
                        'applies_to_roles' => $bonusData['applies_to_roles'] ?? null,
                        'min_kpi_score' => $bonusData['min_kpi_score'] ?? 80,
                        'requires_approval' => $bonusData['requires_approval'] ?? true,
                        'is_active' => $bonusData['is_active'] ?? true,
                    ]
                );
            }
        });

        $progress->completeStep('configure_bonus', [
            'bonus_count' => count($bonusSettings),
        ]);

        return $progress->fresh();
    }

    /**
     * Jarima sozlash bosqichini tugatish
     */
    public function completeConfigurePenalty(
        string $businessId,
        array $penaltyRules
    ): SalesSetupProgress {
        $progress = SalesSetupProgress::where('business_id', $businessId)->firstOrFail();

        // Jarima qoidalarini saqlash
        DB::transaction(function () use ($businessId, $penaltyRules) {
            foreach ($penaltyRules as $ruleData) {
                SalesPenaltyRule::updateOrCreate(
                    [
                        'business_id' => $businessId,
                        'trigger_event' => $ruleData['trigger_event'],
                    ],
                    [
                        'name' => $ruleData['name'] ?? SalesPenaltyRule::TRIGGER_EVENTS[$ruleData['trigger_event']] ?? $ruleData['trigger_event'],
                        'description' => $ruleData['description'] ?? null,
                        'category' => $ruleData['category'] ?? 'activity',
                        'severity' => $ruleData['severity'] ?? 'medium',
                        'trigger_type' => $ruleData['trigger_type'] ?? 'auto',
                        'trigger_conditions' => $ruleData['trigger_conditions'] ?? [],
                        'penalty_type' => $ruleData['penalty_type'] ?? 'fixed',
                        'penalty_amount' => $ruleData['penalty_amount'] ?? 0,
                        'penalty_percentage' => $ruleData['penalty_percentage'] ?? null,
                        'warning_threshold' => $ruleData['warning_threshold'] ?? 2,
                        'warning_validity_days' => $ruleData['warning_validity_days'] ?? 30,
                        'daily_limit' => $ruleData['daily_limit'] ?? null,
                        'monthly_limit' => $ruleData['monthly_limit'] ?? null,
                        'is_active' => $ruleData['is_active'] ?? true,
                    ]
                );
            }
        });

        $progress->completeStep('configure_penalty', [
            'rules_count' => count($penaltyRules),
        ]);

        return $progress->fresh();
    }

    /**
     * Tasdiqlash bosqichini tugatish va setup yakunlash
     */
    public function completeReviewConfirm(
        string $businessId,
        string $userId
    ): SalesSetupProgress {
        $progress = SalesSetupProgress::where('business_id', $businessId)->firstOrFail();

        // Tizim yutuqlarini yaratish
        $this->achievementService->setupSystemAchievements($businessId);

        $progress->completeStep('review_confirm');
        $progress->update(['completed_by' => $userId]);

        Log::info('Sales setup completed', [
            'business_id' => $businessId,
            'user_id' => $userId,
        ]);

        return $progress->fresh();
    }

    /**
     * Shablondan to'liq sozlash
     */
    public function applyTemplate(string $businessId, string $templateId, string $userId): SalesSetupProgress
    {
        $template = SalesKpiTemplateSet::findOrFail($templateId);
        $progress = $this->startSetup($businessId, $userId, $templateId);

        DB::transaction(function () use ($businessId, $template, $userId) {
            // KPI sozlamalarini qo'llash
            if (! empty($template->kpi_settings)) {
                foreach ($template->kpi_settings as $kpiData) {
                    SalesKpiSetting::updateOrCreate(
                        [
                            'business_id' => $businessId,
                            'kpi_type' => $kpiData['kpi_type'],
                        ],
                        array_merge($kpiData, ['business_id' => $businessId])
                    );
                }
            }

            // Bonus sozlamalarini qo'llash
            if (! empty($template->bonus_settings)) {
                foreach ($template->bonus_settings as $bonusData) {
                    SalesBonusSetting::updateOrCreate(
                        [
                            'business_id' => $businessId,
                            'bonus_type' => $bonusData['bonus_type'],
                        ],
                        array_merge($bonusData, ['business_id' => $businessId])
                    );
                }
            }

            // Jarima qoidalarini qo'llash
            if (! empty($template->penalty_rules)) {
                foreach ($template->penalty_rules as $ruleData) {
                    SalesPenaltyRule::updateOrCreate(
                        [
                            'business_id' => $businessId,
                            'trigger_event' => $ruleData['trigger_event'],
                        ],
                        array_merge($ruleData, ['business_id' => $businessId])
                    );
                }
            }

            // Tizim yutuqlarini yaratish
            SalesAchievementDefinition::createSystemAchievements($businessId);
        });

        // Barcha bosqichlarni tugallangan deb belgilash
        $steps = array_keys(SalesSetupProgress::STEPS);
        foreach ($steps as $step) {
            $progress->completeStep($step);
        }

        $progress->update(['completed_by' => $userId]);
        $template->incrementUsage();

        Log::info('Template applied to business', [
            'business_id' => $businessId,
            'template_id' => $templateId,
        ]);

        return $progress->fresh();
    }

    /**
     * Setup progressni olish
     */
    public function getProgress(string $businessId): ?SalesSetupProgress
    {
        return SalesSetupProgress::where('business_id', $businessId)
            ->with('templateSet')
            ->first();
    }

    /**
     * Setup tugallanganmi?
     */
    public function isSetupCompleted(string $businessId): bool
    {
        $progress = $this->getProgress($businessId);

        return $progress && $progress->status === 'completed';
    }

    /**
     * Setup kerakmi? (hech qanday KPI sozlanmaganmi)
     */
    public function needsSetup(string $businessId): bool
    {
        // Agar KPI sozlamalari mavjud bo'lsa, setup kerak emas
        $kpiCount = SalesKpiSetting::forBusiness($businessId)->count();

        if ($kpiCount > 0) {
            return false;
        }

        // Agar setup boshlanmagan yoki tugallanmagan bo'lsa
        $progress = $this->getProgress($businessId);

        return ! $progress || $progress->status === 'in_progress';
    }

    /**
     * Setup statistikasi
     */
    public function getSetupStats(string $businessId): array
    {
        return [
            'kpi_settings' => SalesKpiSetting::forBusiness($businessId)->count(),
            'active_kpi' => SalesKpiSetting::forBusiness($businessId)->active()->count(),
            'bonus_settings' => SalesBonusSetting::forBusiness($businessId)->count(),
            'penalty_rules' => SalesPenaltyRule::forBusiness($businessId)->count(),
            'achievements' => SalesAchievementDefinition::forBusiness($businessId)->count(),
        ];
    }

    /**
     * Shablon preview
     */
    public function getTemplatePreview(string $templateId): array
    {
        $template = SalesKpiTemplateSet::findOrFail($templateId);

        return [
            'template' => [
                'id' => $template->id,
                'name' => $template->name,
                'description' => $template->description,
                'industry' => $template->industry_label,
            ],
            'kpi_settings' => collect($template->kpi_settings)->map(fn ($kpi) => [
                'type' => $kpi['kpi_type'],
                'name' => $kpi['name'] ?? SalesKpiSetting::KPI_TYPES[$kpi['kpi_type']] ?? $kpi['kpi_type'],
                'weight' => $kpi['weight'] ?? 10,
                'target_min' => $kpi['target_min'] ?? 0,
            ]),
            'bonus_settings' => collect($template->bonus_settings)->map(fn ($bonus) => [
                'type' => $bonus['bonus_type'],
                'name' => $bonus['name'] ?? SalesBonusSetting::BONUS_TYPES[$bonus['bonus_type']] ?? $bonus['bonus_type'],
                'min_kpi_score' => $bonus['min_kpi_score'] ?? 80,
            ]),
            'penalty_rules' => collect($template->penalty_rules)->map(fn ($rule) => [
                'trigger' => $rule['trigger_event'],
                'name' => $rule['name'] ?? SalesPenaltyRule::TRIGGER_EVENTS[$rule['trigger_event']] ?? $rule['trigger_event'],
                'severity' => $rule['severity'] ?? 'medium',
            ]),
            'onboarding_tips' => $template->onboarding_tips,
        ];
    }
}
