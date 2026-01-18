<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class ReportTemplate extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'business_id',
        'name',
        'code',
        'description',
        'type',
        'category',
        'sections',
        'metrics',
        'kpis',
        'charts',
        'tables',
        'language',
        'styles',
        'logo_path',
        'header_text',
        'footer_text',
        'auto_insights',
        'insight_rules',
        'max_insights',
        'include_previous_period',
        'include_benchmarks',
        'include_targets',
        'is_active',
        'is_default',
        'usage_count',
    ];

    protected $casts = [
        'sections' => 'array',
        'metrics' => 'array',
        'kpis' => 'array',
        'charts' => 'array',
        'tables' => 'array',
        'styles' => 'array',
        'insight_rules' => 'array',
        'auto_insights' => 'boolean',
        'include_previous_period' => 'boolean',
        'include_benchmarks' => 'boolean',
        'include_targets' => 'boolean',
        'is_active' => 'boolean',
        'is_default' => 'boolean',
    ];

    // Type constants
    public const TYPE_SYSTEM = 'system';

    public const TYPE_CUSTOM = 'custom';

    // Category constants
    public const CATEGORY_HEALTH = 'health';

    public const CATEGORY_MARKETING = 'marketing';

    public const CATEGORY_SALES = 'sales';

    public const CATEGORY_FINANCIAL = 'financial';

    public const CATEGORY_COMPREHENSIVE = 'comprehensive';

    // Default section configurations
    public const DEFAULT_SECTIONS = [
        'summary' => [
            'title_uz' => 'Umumiy ko\'rsatkichlar',
            'title_en' => 'Summary',
            'enabled' => true,
            'order' => 1,
        ],
        'health_score' => [
            'title_uz' => 'Salomatlik balli',
            'title_en' => 'Health Score',
            'enabled' => true,
            'order' => 2,
        ],
        'key_metrics' => [
            'title_uz' => 'Asosiy metrikalar',
            'title_en' => 'Key Metrics',
            'enabled' => true,
            'order' => 3,
        ],
        'trends' => [
            'title_uz' => 'Tendentsiyalar',
            'title_en' => 'Trends',
            'enabled' => true,
            'order' => 4,
        ],
        'insights' => [
            'title_uz' => 'Tushunchalar',
            'title_en' => 'Insights',
            'enabled' => true,
            'order' => 5,
        ],
        'recommendations' => [
            'title_uz' => 'Tavsiyalar',
            'title_en' => 'Recommendations',
            'enabled' => true,
            'order' => 6,
        ],
        'comparison' => [
            'title_uz' => 'Taqqoslash',
            'title_en' => 'Comparison',
            'enabled' => true,
            'order' => 7,
        ],
    ];

    // Relationships
    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    public function generatedReports(): HasMany
    {
        return $this->hasMany(GeneratedReport::class);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeSystem($query)
    {
        return $query->where('type', self::TYPE_SYSTEM);
    }

    public function scopeCustom($query)
    {
        return $query->where('type', self::TYPE_CUSTOM);
    }

    public function scopeForCategory($query, string $category)
    {
        return $query->where('category', $category);
    }

    public function scopeDefault($query)
    {
        return $query->where('is_default', true);
    }

    // Helpers
    public function getCategoryLabelAttribute(): string
    {
        return match ($this->category) {
            self::CATEGORY_HEALTH => 'Salomatlik',
            self::CATEGORY_MARKETING => 'Marketing',
            self::CATEGORY_SALES => 'Sotuvlar',
            self::CATEGORY_FINANCIAL => 'Moliyaviy',
            self::CATEGORY_COMPREHENSIVE => 'Keng qamrovli',
            default => $this->category,
        };
    }

    public function getTypeLabelAttribute(): string
    {
        return match ($this->type) {
            self::TYPE_SYSTEM => 'Tizim',
            self::TYPE_CUSTOM => 'Maxsus',
            default => $this->type,
        };
    }

    /**
     * Get sections with defaults merged
     */
    public function getSectionsWithDefaults(): array
    {
        $sections = $this->sections ?? [];

        return array_merge(self::DEFAULT_SECTIONS, $sections);
    }

    /**
     * Get enabled sections in order
     */
    public function getEnabledSections(): array
    {
        $sections = $this->getSectionsWithDefaults();

        $enabled = array_filter($sections, fn ($s) => $s['enabled'] ?? true);

        uasort($enabled, fn ($a, $b) => ($a['order'] ?? 999) <=> ($b['order'] ?? 999));

        return $enabled;
    }

    /**
     * Increment usage count
     */
    public function incrementUsage(): void
    {
        $this->increment('usage_count');
    }

    /**
     * Create system templates
     */
    public static function createSystemTemplates(): void
    {
        $templates = [
            [
                'name' => 'Haftalik Umumiy Hisobot',
                'code' => 'weekly_summary',
                'description' => 'Haftalik asosiy ko\'rsatkichlar va tushunchalar',
                'type' => self::TYPE_SYSTEM,
                'category' => self::CATEGORY_COMPREHENSIVE,
                'sections' => self::DEFAULT_SECTIONS,
                'metrics' => ['revenue', 'leads', 'conversion', 'cac', 'ltv'],
                'is_default' => true,
            ],
            [
                'name' => 'Marketing Hisoboti',
                'code' => 'marketing_report',
                'description' => 'Marketing kanallar va kampaniyalar samaradorligi',
                'type' => self::TYPE_SYSTEM,
                'category' => self::CATEGORY_MARKETING,
                'sections' => self::DEFAULT_SECTIONS,
                'metrics' => ['leads', 'ctr', 'cpc', 'impressions', 'reach'],
            ],
            [
                'name' => 'Sotuvlar Hisoboti',
                'code' => 'sales_report',
                'description' => 'Sotuvlar dinamikasi va mijozlar tahlili',
                'type' => self::TYPE_SYSTEM,
                'category' => self::CATEGORY_SALES,
                'sections' => self::DEFAULT_SECTIONS,
                'metrics' => ['sales', 'revenue', 'avg_check', 'conversion', 'repeat_rate'],
            ],
            [
                'name' => 'Moliyaviy Hisobot',
                'code' => 'financial_report',
                'description' => 'Daromad, xarajatlar va ROI tahlili',
                'type' => self::TYPE_SYSTEM,
                'category' => self::CATEGORY_FINANCIAL,
                'sections' => self::DEFAULT_SECTIONS,
                'metrics' => ['revenue', 'costs', 'profit', 'roi', 'roas'],
            ],
            [
                'name' => 'Salomatlik Hisoboti',
                'code' => 'health_report',
                'description' => 'Biznes salomatligi va asosiy KPIlar',
                'type' => self::TYPE_SYSTEM,
                'category' => self::CATEGORY_HEALTH,
                'sections' => self::DEFAULT_SECTIONS,
                'metrics' => ['health_score', 'growth_rate', 'retention', 'efficiency'],
            ],
        ];

        foreach ($templates as $template) {
            self::updateOrCreate(
                ['code' => $template['code']],
                array_merge($template, ['is_active' => true])
            );
        }
    }
}
