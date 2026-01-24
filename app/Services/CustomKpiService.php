<?php

namespace App\Services;

use App\Models\Business;
use App\Models\BusinessKpiConfiguration;
use App\Models\KpiDailyActual;
use App\Models\KpiTemplate;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class CustomKpiService
{
    /**
     * Available KPI data types.
     */
    public const TYPE_NUMBER = 'number';
    public const TYPE_CURRENCY = 'currency';
    public const TYPE_PERCENTAGE = 'percentage';
    public const TYPE_RATIO = 'ratio';
    public const TYPE_TIME = 'time';

    /**
     * Available aggregation methods.
     */
    public const AGG_SUM = 'sum';
    public const AGG_AVG = 'average';
    public const AGG_MAX = 'max';
    public const AGG_MIN = 'min';
    public const AGG_COUNT = 'count';
    public const AGG_LAST = 'last';

    /**
     * Create a custom KPI template for a business.
     */
    public function createCustomKpi(
        Business $business,
        string $name,
        string $description,
        string $dataType = self::TYPE_NUMBER,
        string $aggregation = self::AGG_SUM,
        array $options = []
    ): KpiTemplate {
        $kpiCode = 'custom_' . $business->id . '_' . Str::slug($name, '_');

        // Ensure unique code
        $counter = 1;
        $originalCode = $kpiCode;
        while (KpiTemplate::where('kpi_code', $kpiCode)->exists()) {
            $kpiCode = $originalCode . '_' . $counter;
            $counter++;
        }

        $template = KpiTemplate::create([
            'kpi_code' => $kpiCode,
            'name_uz' => $name,
            'name_en' => $options['name_en'] ?? $name,
            'description_uz' => $description,
            'description_en' => $options['description_en'] ?? $description,
            'category' => $options['category'] ?? 'custom',
            'subcategory' => $options['subcategory'] ?? 'user_defined',
            'data_type' => $dataType,
            'aggregation_method' => $aggregation,
            'unit' => $options['unit'] ?? null,
            'unit_position' => $options['unit_position'] ?? 'after',
            'decimal_places' => $options['decimal_places'] ?? 0,
            'is_higher_better' => $options['is_higher_better'] ?? true,
            'target_type' => $options['target_type'] ?? 'fixed',
            'default_target' => $options['default_target'] ?? null,
            'min_value' => $options['min_value'] ?? null,
            'max_value' => $options['max_value'] ?? null,
            'priority_level' => $options['priority'] ?? 'medium',
            'is_core' => false,
            'is_industry_specific' => false,
            'is_custom' => true,
            'business_id' => $business->id,
            'created_by' => auth()->id(),
            'formula' => $options['formula'] ?? null,
            'data_source' => $options['data_source'] ?? 'manual',
            'refresh_frequency' => $options['refresh_frequency'] ?? 'daily',
            'display_order' => $options['display_order'] ?? 999,
            'icon' => $options['icon'] ?? 'chart-bar',
            'color' => $options['color'] ?? '#6366f1',
            'is_active' => true,
        ]);

        // Add to business configuration
        $this->addKpiToBusinessConfig($business, $kpiCode, $options['priority'] ?? 'medium');

        Log::info('Custom KPI created', [
            'business_id' => $business->id,
            'kpi_code' => $kpiCode,
        ]);

        return $template;
    }

    /**
     * Update a custom KPI.
     */
    public function updateCustomKpi(
        KpiTemplate $kpi,
        array $data
    ): KpiTemplate {
        if (!$kpi->is_custom) {
            throw new \Exception('Faqat maxsus KPI larni o\'zgartirish mumkin');
        }

        $kpi->update([
            'name_uz' => $data['name'] ?? $kpi->name_uz,
            'name_en' => $data['name_en'] ?? $kpi->name_en,
            'description_uz' => $data['description'] ?? $kpi->description_uz,
            'description_en' => $data['description_en'] ?? $kpi->description_en,
            'data_type' => $data['data_type'] ?? $kpi->data_type,
            'aggregation_method' => $data['aggregation'] ?? $kpi->aggregation_method,
            'unit' => $data['unit'] ?? $kpi->unit,
            'decimal_places' => $data['decimal_places'] ?? $kpi->decimal_places,
            'is_higher_better' => $data['is_higher_better'] ?? $kpi->is_higher_better,
            'default_target' => $data['default_target'] ?? $kpi->default_target,
            'priority_level' => $data['priority'] ?? $kpi->priority_level,
            'icon' => $data['icon'] ?? $kpi->icon,
            'color' => $data['color'] ?? $kpi->color,
        ]);

        return $kpi->fresh();
    }

    /**
     * Delete a custom KPI.
     */
    public function deleteCustomKpi(KpiTemplate $kpi): bool
    {
        if (!$kpi->is_custom) {
            throw new \Exception('Faqat maxsus KPI larni o\'chirish mumkin');
        }

        $businessId = $kpi->business_id;
        $kpiCode = $kpi->kpi_code;

        DB::beginTransaction();
        try {
            // Remove from business configurations
            $configs = BusinessKpiConfiguration::where('business_id', $businessId)->get();
            foreach ($configs as $config) {
                $config->removeKpi($kpiCode);
                $config->save();
            }

            // Soft delete the template
            $kpi->delete();

            DB::commit();

            Log::info('Custom KPI deleted', [
                'business_id' => $businessId,
                'kpi_code' => $kpiCode,
            ]);

            return true;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Record a value for a custom KPI.
     */
    public function recordValue(
        Business $business,
        string $kpiCode,
        float $value,
        ?Carbon $date = null,
        ?string $notes = null
    ): KpiDailyActual {
        $date = $date ?? today();

        $kpi = KpiDailyActual::updateOrCreate(
            [
                'business_id' => $business->id,
                'kpi_code' => $kpiCode,
                'date' => $date->toDateString(),
            ],
            [
                'actual_value' => $value,
                'notes' => $notes,
                'recorded_by' => auth()->id(),
                'recorded_at' => now(),
            ]
        );

        return $kpi;
    }

    /**
     * Record multiple KPI values at once.
     */
    public function recordMultipleValues(
        Business $business,
        array $values,
        ?Carbon $date = null
    ): array {
        $date = $date ?? today();
        $results = [];

        foreach ($values as $kpiCode => $value) {
            $results[$kpiCode] = $this->recordValue($business, $kpiCode, $value, $date);
        }

        return $results;
    }

    /**
     * Get custom KPIs for a business.
     */
    public function getCustomKpis(Business $business): Collection
    {
        return KpiTemplate::where('business_id', $business->id)
            ->where('is_custom', true)
            ->where('is_active', true)
            ->orderBy('display_order')
            ->get();
    }

    /**
     * Get KPI history for a business.
     */
    public function getKpiHistory(
        Business $business,
        string $kpiCode,
        int $days = 30
    ): Collection {
        return KpiDailyActual::allBusinesses()
            ->where('business_id', $business->id)
            ->where('kpi_code', $kpiCode)
            ->where('date', '>=', now()->subDays($days))
            ->orderBy('date', 'desc')
            ->get();
    }

    /**
     * Calculate KPI trend.
     */
    public function calculateTrend(
        Business $business,
        string $kpiCode,
        int $days = 7
    ): array {
        $history = $this->getKpiHistory($business, $kpiCode, $days);

        if ($history->count() < 2) {
            return [
                'trend' => 'stable',
                'change_percent' => 0,
                'current_value' => $history->first()?->actual_value ?? 0,
                'previous_value' => 0,
            ];
        }

        $current = $history->first()->actual_value;
        $previous = $history->last()->actual_value;

        $changePercent = $previous > 0
            ? round((($current - $previous) / $previous) * 100, 1)
            : 0;

        $trend = 'stable';
        if ($changePercent > 5) {
            $trend = 'up';
        } elseif ($changePercent < -5) {
            $trend = 'down';
        }

        return [
            'trend' => $trend,
            'change_percent' => $changePercent,
            'current_value' => $current,
            'previous_value' => $previous,
        ];
    }

    /**
     * Get KPI summary for dashboard.
     */
    public function getKpiSummary(Business $business): array
    {
        $config = BusinessKpiConfiguration::where('business_id', $business->id)
            ->active()
            ->first();

        if (!$config) {
            return [
                'total_kpis' => 0,
                'custom_kpis' => 0,
                'kpis' => [],
            ];
        }

        $selectedKpis = $config->selected_kpis ?? [];
        $templates = KpiTemplate::whereIn('kpi_code', $selectedKpis)
            ->where('is_active', true)
            ->get();

        $todayActuals = KpiDailyActual::allBusinesses()
            ->where('business_id', $business->id)
            ->whereDate('date', today())
            ->get()
            ->keyBy('kpi_code');

        $kpis = $templates->map(function ($template) use ($todayActuals, $business) {
            $actual = $todayActuals->get($template->kpi_code);
            $trend = $this->calculateTrend($business, $template->kpi_code, 7);

            return [
                'code' => $template->kpi_code,
                'name' => $template->name_uz,
                'value' => $actual?->actual_value ?? 0,
                'target' => $template->default_target,
                'unit' => $template->unit,
                'trend' => $trend['trend'],
                'change_percent' => $trend['change_percent'],
                'is_custom' => $template->is_custom,
                'priority' => $template->priority_level,
                'color' => $template->color,
                'icon' => $template->icon,
            ];
        });

        return [
            'total_kpis' => $templates->count(),
            'custom_kpis' => $templates->where('is_custom', true)->count(),
            'kpis' => $kpis->toArray(),
        ];
    }

    /**
     * Create a calculated/formula KPI.
     */
    public function createCalculatedKpi(
        Business $business,
        string $name,
        string $formula,
        array $dependentKpis,
        array $options = []
    ): KpiTemplate {
        return $this->createCustomKpi($business, $name, $options['description'] ?? $name, self::TYPE_NUMBER, self::AGG_LAST, array_merge($options, [
            'formula' => $formula,
            'data_source' => 'calculated',
            'dependent_kpis' => $dependentKpis,
        ]));
    }

    /**
     * Calculate formula-based KPI value.
     */
    public function calculateFormulaKpi(
        Business $business,
        KpiTemplate $kpi,
        ?Carbon $date = null
    ): ?float {
        if (!$kpi->formula) {
            return null;
        }

        $date = $date ?? today();

        // Get dependent KPI values
        $values = [];
        $formula = $kpi->formula;

        // Find all KPI code references in formula (format: {kpi_code})
        preg_match_all('/\{([a-z_0-9]+)\}/', $formula, $matches);

        foreach ($matches[1] as $kpiCode) {
            $value = KpiDailyActual::allBusinesses()
                ->where('business_id', $business->id)
                ->where('kpi_code', $kpiCode)
                ->whereDate('date', $date)
                ->first()?->actual_value ?? 0;

            $values[$kpiCode] = $value;
            $formula = str_replace("{{$kpiCode}}", (string) $value, $formula);
        }

        // Safely evaluate the formula
        try {
            // Only allow basic math operations
            if (!preg_match('/^[\d\.\+\-\*\/\(\)\s]+$/', $formula)) {
                Log::warning('Invalid formula characters', ['formula' => $formula]);
                return null;
            }

            $result = eval("return {$formula};");

            // Record the calculated value
            $this->recordValue($business, $kpi->kpi_code, $result, $date);

            return $result;
        } catch (\Exception $e) {
            Log::error('Formula calculation error', [
                'kpi_code' => $kpi->kpi_code,
                'formula' => $kpi->formula,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Add KPI to business configuration.
     */
    protected function addKpiToBusinessConfig(
        Business $business,
        string $kpiCode,
        string $priority = 'medium'
    ): void {
        $config = BusinessKpiConfiguration::firstOrCreate(
            ['business_id' => $business->id],
            [
                'industry_code' => $business->category ?? 'general',
                'status' => 'active',
                'selected_kpis' => [],
                'kpi_priorities' => [],
            ]
        );

        $config->addKpi($kpiCode, $priority);
        $config->save();
    }

    /**
     * Get predefined KPI templates for selection.
     */
    public function getAvailableTemplates(string $category = null): Collection
    {
        $query = KpiTemplate::whereNull('business_id')
            ->where('is_custom', false)
            ->where('is_active', true);

        if ($category) {
            $query->where('category', $category);
        }

        return $query->orderBy('category')
            ->orderBy('display_order')
            ->get();
    }

    /**
     * Get KPI categories.
     */
    public function getCategories(): array
    {
        return [
            'sales' => 'Sotuv',
            'marketing' => 'Marketing',
            'finance' => 'Moliya',
            'operations' => 'Operatsiyalar',
            'hr' => 'HR',
            'customer' => 'Mijozlar',
            'custom' => 'Maxsus',
        ];
    }

    /**
     * Get data types for KPI creation.
     */
    public function getDataTypes(): array
    {
        return [
            self::TYPE_NUMBER => 'Son',
            self::TYPE_CURRENCY => 'Pul',
            self::TYPE_PERCENTAGE => 'Foiz',
            self::TYPE_RATIO => 'Nisbat',
            self::TYPE_TIME => 'Vaqt',
        ];
    }

    /**
     * Get aggregation methods.
     */
    public function getAggregationMethods(): array
    {
        return [
            self::AGG_SUM => 'Yig\'indi',
            self::AGG_AVG => 'O\'rtacha',
            self::AGG_MAX => 'Maksimum',
            self::AGG_MIN => 'Minimum',
            self::AGG_COUNT => 'Soni',
            self::AGG_LAST => 'Oxirgi qiymat',
        ];
    }
}
