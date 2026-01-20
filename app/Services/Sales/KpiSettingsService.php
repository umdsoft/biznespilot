<?php

namespace App\Services\Sales;

use App\Models\Business;
use App\Models\SalesKpiSetting;
use App\Models\SalesKpiUserTarget;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class KpiSettingsService
{
    /**
     * Cache TTL (5 daqiqa)
     */
    protected int $cacheTTL = 300;

    /**
     * KPI shablonlari
     */
    public const TEMPLATES = [
        'b2b_sales' => [
            'name' => 'B2B Sotuvlar',
            'description' => 'B2B xizmatlar va mahsulotlar uchun',
            'kpis' => [
                ['type' => 'leads_converted', 'weight' => 25, 'target_min' => 10, 'target_good' => 15, 'target_excellent' => 20],
                ['type' => 'revenue', 'weight' => 25, 'target_min' => 50000000, 'target_good' => 75000000, 'target_excellent' => 100000000],
                ['type' => 'calls_made', 'weight' => 15, 'target_min' => 100, 'target_good' => 130, 'target_excellent' => 150],
                ['type' => 'meetings_held', 'weight' => 15, 'target_min' => 15, 'target_good' => 20, 'target_excellent' => 25],
                ['type' => 'conversion_rate', 'weight' => 10, 'target_min' => 20, 'target_good' => 25, 'target_excellent' => 30],
                ['type' => 'crm_compliance', 'weight' => 10, 'target_min' => 90, 'target_good' => 95, 'target_excellent' => 100],
            ],
        ],
        'b2c_retail' => [
            'name' => 'B2C Chakana savdo',
            'description' => 'Iste\'molchilarga to\'g\'ridan-to\'g\'ri sotuv',
            'kpis' => [
                ['type' => 'deals_count', 'weight' => 30, 'target_min' => 50, 'target_good' => 70, 'target_excellent' => 100],
                ['type' => 'revenue', 'weight' => 25, 'target_min' => 30000000, 'target_good' => 45000000, 'target_excellent' => 60000000],
                ['type' => 'calls_made', 'weight' => 20, 'target_min' => 150, 'target_good' => 200, 'target_excellent' => 250],
                ['type' => 'conversion_rate', 'weight' => 15, 'target_min' => 15, 'target_good' => 20, 'target_excellent' => 25],
                ['type' => 'response_time', 'weight' => 10, 'target_min' => 2, 'target_good' => 1, 'target_excellent' => 0.5],
            ],
        ],
        'edtech' => [
            'name' => 'Ta\'lim (EdTech)',
            'description' => 'Kurslar va ta\'lim xizmatlari uchun',
            'kpis' => [
                ['type' => 'leads_converted', 'weight' => 25, 'target_min' => 30, 'target_good' => 45, 'target_excellent' => 60],
                ['type' => 'revenue', 'weight' => 20, 'target_min' => 40000000, 'target_good' => 60000000, 'target_excellent' => 80000000],
                ['type' => 'calls_made', 'weight' => 20, 'target_min' => 120, 'target_good' => 160, 'target_excellent' => 200],
                ['type' => 'tasks_completed', 'weight' => 15, 'target_min' => 50, 'target_good' => 70, 'target_excellent' => 90],
                ['type' => 'conversion_rate', 'weight' => 10, 'target_min' => 18, 'target_good' => 22, 'target_excellent' => 28],
                ['type' => 'crm_compliance', 'weight' => 10, 'target_min' => 85, 'target_good' => 92, 'target_excellent' => 98],
            ],
        ],
        'services' => [
            'name' => 'Xizmatlar',
            'description' => 'Professional xizmatlar (konsalting, IT va h.k.)',
            'kpis' => [
                ['type' => 'leads_converted', 'weight' => 20, 'target_min' => 8, 'target_good' => 12, 'target_excellent' => 15],
                ['type' => 'revenue', 'weight' => 30, 'target_min' => 80000000, 'target_good' => 120000000, 'target_excellent' => 160000000],
                ['type' => 'avg_deal_size', 'weight' => 15, 'target_min' => 10000000, 'target_good' => 15000000, 'target_excellent' => 20000000],
                ['type' => 'meetings_held', 'weight' => 15, 'target_min' => 20, 'target_good' => 30, 'target_excellent' => 40],
                ['type' => 'proposals_sent', 'weight' => 10, 'target_min' => 15, 'target_good' => 22, 'target_excellent' => 30],
                ['type' => 'conversion_rate', 'weight' => 10, 'target_min' => 25, 'target_good' => 32, 'target_excellent' => 40],
            ],
        ],
    ];

    /**
     * Biznes uchun barcha faol KPIlarni olish
     */
    public function getActiveKpisForBusiness(string $businessId): Collection
    {
        $cacheKey = "sales_kpi_settings_{$businessId}";

        return Cache::remember($cacheKey, $this->cacheTTL, function () use ($businessId) {
            return SalesKpiSetting::forBusiness($businessId)
                ->active()
                ->ordered()
                ->get();
        });
    }

    /**
     * Shablon asosida KPIlar yaratish
     */
    public function createFromTemplate(string $businessId, string $templateCode): Collection
    {
        $template = self::TEMPLATES[$templateCode] ?? null;

        if (! $template) {
            throw new \InvalidArgumentException("Shablon topilmadi: {$templateCode}");
        }

        $createdKpis = collect();

        DB::transaction(function () use ($businessId, $template, &$createdKpis) {
            foreach ($template['kpis'] as $index => $kpiData) {
                $kpi = SalesKpiSetting::create([
                    'business_id' => $businessId,
                    'kpi_type' => $kpiData['type'],
                    'name' => SalesKpiSetting::KPI_TYPES[$kpiData['type']] ?? $kpiData['type'],
                    'weight' => $kpiData['weight'],
                    'target_min' => $kpiData['target_min'],
                    'target_good' => $kpiData['target_good'],
                    'target_excellent' => $kpiData['target_excellent'],
                    'measurement_unit' => $this->getDefaultMeasurementUnit($kpiData['type']),
                    'calculation_method' => $this->getDefaultCalculationMethod($kpiData['type']),
                    'data_source' => $this->getDefaultDataSource($kpiData['type']),
                    'period_type' => 'monthly',
                    'is_active' => true,
                    'is_system' => false,
                    'sort_order' => $index,
                    'applies_to_roles' => ['sales_operator'],
                ]);

                $createdKpis->push($kpi);
            }
        });

        // Cache ni tozalash
        $this->clearCache($businessId);

        return $createdKpis;
    }

    /**
     * Foydalanuvchi uchun maqsadlar belgilash
     */
    public function setUserTargets(
        string $businessId,
        string $userId,
        array $targets,
        string $periodType = 'monthly',
        ?Carbon $periodStart = null,
        ?string $setBy = null
    ): Collection {
        $periodDates = $this->getPeriodDates($periodType, $periodStart);
        $createdTargets = collect();

        DB::transaction(function () use ($businessId, $userId, $targets, $periodType, $periodDates, $setBy, &$createdTargets) {
            foreach ($targets as $kpiSettingId => $targetValue) {
                // Avvalgi maqsadni topish yoki yangi yaratish
                $target = SalesKpiUserTarget::updateOrCreate(
                    [
                        'business_id' => $businessId,
                        'kpi_setting_id' => $kpiSettingId,
                        'user_id' => $userId,
                        'period_type' => $periodType,
                        'period_start' => $periodDates['start'],
                    ],
                    [
                        'period_end' => $periodDates['end'],
                        'target_value' => $targetValue,
                        'set_by' => $setBy,
                        'status' => 'active',
                    ]
                );

                $createdTargets->push($target);
            }
        });

        return $createdTargets;
    }

    /**
     * Foydalanuvchining joriy davr maqsadlarini olish
     */
    public function getUserTargets(
        string $businessId,
        string $userId,
        string $periodType = 'monthly',
        ?Carbon $date = null
    ): Collection {
        $date = $date ?? now();

        return SalesKpiUserTarget::forBusiness($businessId)
            ->forUser($userId)
            ->forPeriod($periodType, $date)
            ->active()
            ->with('kpiSetting')
            ->get();
    }

    /**
     * Jamoa uchun KPI summary olish
     */
    public function getTeamKpiSummary(string $businessId, string $periodType = 'monthly', ?Carbon $date = null): array
    {
        $date = $date ?? now();
        $cacheKey = "team_kpi_summary_{$businessId}_{$periodType}_{$date->format('Y-m')}";

        return Cache::remember($cacheKey, $this->cacheTTL, function () use ($businessId, $periodType, $date) {
            $targets = SalesKpiUserTarget::forBusiness($businessId)
                ->forPeriod($periodType, $date)
                ->active()
                ->with(['user:id,name', 'kpiSetting:id,kpi_type,name,weight'])
                ->get();

            // Foydalanuvchilar bo'yicha guruhlash
            $byUser = $targets->groupBy('user_id');

            $teamSummary = [];

            foreach ($byUser as $userId => $userTargets) {
                $user = $userTargets->first()->user;
                $totalWeight = 0;
                $weightedScore = 0;

                foreach ($userTargets as $target) {
                    $weight = $target->kpiSetting->weight ?? 0;
                    $totalWeight += $weight;
                    $weightedScore += $target->score * ($weight / 100);
                }

                $overallScore = $totalWeight > 0 ? round($weightedScore / ($totalWeight / 100), 1) : 0;

                $teamSummary[] = [
                    'user_id' => $userId,
                    'user_name' => $user->name ?? 'Noma\'lum',
                    'targets_count' => $userTargets->count(),
                    'overall_score' => $overallScore,
                    'performance_level' => $this->getPerformanceLevelByScore($overallScore),
                    'targets' => $userTargets->map(fn ($t) => [
                        'kpi_type' => $t->kpiSetting->kpi_type,
                        'kpi_name' => $t->kpiSetting->name,
                        'target' => $t->effective_target,
                        'achieved' => $t->achieved_value,
                        'percent' => $t->achievement_percent,
                        'score' => $t->score,
                    ]),
                ];
            }

            // Ballar bo'yicha tartiblash
            usort($teamSummary, fn ($a, $b) => $b['overall_score'] <=> $a['overall_score']);

            // Rank qo'shish
            foreach ($teamSummary as $index => &$member) {
                $member['rank'] = $index + 1;
            }

            return $teamSummary;
        });
    }

    /**
     * Vaznlarni tekshirish (100% bo'lishi kerak)
     */
    public function validateWeights(string $businessId): array
    {
        $kpis = $this->getActiveKpisForBusiness($businessId);
        $totalWeight = $kpis->sum('weight');

        return [
            'valid' => abs($totalWeight - 100) < 0.01,
            'total_weight' => $totalWeight,
            'difference' => 100 - $totalWeight,
            'kpis_count' => $kpis->count(),
        ];
    }

    /**
     * Vaznlarni avtomatik taqsimlash
     */
    public function distributeWeights(string $businessId): void
    {
        $kpis = SalesKpiSetting::forBusiness($businessId)->active()->get();

        if ($kpis->isEmpty()) {
            return;
        }

        $weightPerKpi = round(100 / $kpis->count(), 2);
        $remainder = 100 - ($weightPerKpi * $kpis->count());

        DB::transaction(function () use ($kpis, $weightPerKpi, $remainder) {
            foreach ($kpis as $index => $kpi) {
                // Oxirgi KPIga qoldiqni qo'shish
                $weight = $index === $kpis->count() - 1
                    ? $weightPerKpi + $remainder
                    : $weightPerKpi;

                $kpi->update(['weight' => $weight]);
            }
        });

        $this->clearCache($businessId);
    }

    /**
     * Cache tozalash
     */
    public function clearCache(string $businessId): void
    {
        Cache::forget("sales_kpi_settings_{$businessId}");
        Cache::forget("team_kpi_summary_{$businessId}_monthly_".now()->format('Y-m'));
        Cache::forget("team_kpi_summary_{$businessId}_weekly_".now()->format('Y-W'));
    }

    /**
     * Mavjud shablonlar ro'yxatini olish
     */
    public function getAvailableTemplates(): array
    {
        return collect(self::TEMPLATES)->map(fn ($template, $code) => [
            'code' => $code,
            'name' => $template['name'],
            'description' => $template['description'],
            'kpis_count' => count($template['kpis']),
            'kpis' => collect($template['kpis'])->map(fn ($kpi) => [
                'type' => $kpi['type'],
                'name' => SalesKpiSetting::KPI_TYPES[$kpi['type']] ?? $kpi['type'],
                'weight' => $kpi['weight'],
            ]),
        ])->values()->toArray();
    }

    /**
     * Davr sanalarini hisoblash
     */
    protected function getPeriodDates(string $periodType, ?Carbon $date = null): array
    {
        $date = $date ?? now();

        return match ($periodType) {
            'daily' => [
                'start' => $date->copy()->startOfDay(),
                'end' => $date->copy()->endOfDay(),
            ],
            'weekly' => [
                'start' => $date->copy()->startOfWeek(),
                'end' => $date->copy()->endOfWeek(),
            ],
            'monthly' => [
                'start' => $date->copy()->startOfMonth(),
                'end' => $date->copy()->endOfMonth(),
            ],
            default => [
                'start' => $date->copy()->startOfMonth(),
                'end' => $date->copy()->endOfMonth(),
            ],
        };
    }

    /**
     * Ball bo'yicha performance darajasini aniqlash
     */
    protected function getPerformanceLevelByScore(float $score): string
    {
        return match (true) {
            $score >= 90 => 'exceptional',
            $score >= 75 => 'excellent',
            $score >= 60 => 'good',
            $score >= 45 => 'meets',
            $score >= 30 => 'developing',
            default => 'needs_improvement',
        };
    }

    /**
     * KPI turi uchun standart o'lchov birligini aniqlash
     */
    protected function getDefaultMeasurementUnit(string $kpiType): string
    {
        return match ($kpiType) {
            'revenue', 'avg_deal_size' => 'currency',
            'conversion_rate', 'crm_compliance', 'lost_rate', 'lead_touch_rate' => 'percentage',
            'response_time' => 'hours',
            'call_duration' => 'minutes',
            default => 'count',
        };
    }

    /**
     * KPI turi uchun standart hisoblash usulini aniqlash
     */
    protected function getDefaultCalculationMethod(string $kpiType): string
    {
        return match ($kpiType) {
            'revenue', 'call_duration' => 'sum',
            'avg_deal_size', 'response_time' => 'average',
            'conversion_rate', 'crm_compliance', 'lost_rate', 'lead_touch_rate' => 'rate',
            default => 'count',
        };
    }

    /**
     * KPI turi uchun standart ma'lumot manbasini aniqlash
     */
    protected function getDefaultDataSource(string $kpiType): string
    {
        return match ($kpiType) {
            'leads_converted', 'conversion_rate', 'lost_rate', 'lead_touch_rate' => 'leads',
            'tasks_completed', 'meetings_held', 'proposals_sent' => 'tasks',
            'calls_made', 'calls_answered', 'call_duration' => 'calls',
            'revenue', 'deals_count', 'avg_deal_size' => 'orders',
            'crm_compliance', 'response_time' => 'auto',
            default => 'auto',
        };
    }
}
