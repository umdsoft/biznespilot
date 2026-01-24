<?php

namespace App\Models;

use App\Traits\BelongsToBusiness;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class BusinessKpiConfiguration extends Model
{
    use HasFactory, SoftDeletes, BelongsToBusiness;

    protected $fillable = [
        'business_id',
        'industry_code',
        'sub_category',
        'business_size',
        'business_maturity',
        'primary_goal',
        'secondary_goals',
        'selected_kpis',
        'kpi_priorities',
        'kpi_weights',
        'is_auto_generated',
        'customized_by_user',
        'generation_params',
        'status',
        'activated_at',
        'last_reviewed_at',
        'total_kpis_count',
        'critical_kpis_count',
        'overall_achievement_rate',
        'notification_settings',
        'user_notes',
        'next_review_due',
        'review_frequency_days',
        'created_by_user_id',
        'last_modified_by_user_id',
    ];

    protected $casts = [
        'secondary_goals' => 'array',
        'selected_kpis' => 'array',
        'kpi_priorities' => 'array',
        'kpi_weights' => 'array',
        'generation_params' => 'array',
        'notification_settings' => 'array',
        'is_auto_generated' => 'boolean',
        'customized_by_user' => 'boolean',
        'activated_at' => 'datetime',
        'last_reviewed_at' => 'datetime',
        'next_review_due' => 'datetime',
        'total_kpis_count' => 'integer',
        'critical_kpis_count' => 'integer',
        'overall_achievement_rate' => 'decimal:2',
        'review_frequency_days' => 'integer',
    ];

    /**
     * Relationships
     */
    public function createdByUser()
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    public function lastModifiedByUser()
    {
        return $this->belongsTo(User::class, 'last_modified_by_user_id');
    }

    /**
     * Scopes
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeForIndustry($query, string $industryCode)
    {
        return $query->where('industry_code', $industryCode);
    }

    public function scopeForGoal($query, string $goal)
    {
        return $query->where('primary_goal', $goal);
    }

    public function scopeDueForReview($query)
    {
        return $query->where('next_review_due', '<=', now())
            ->where('status', 'active');
    }

    /**
     * Get all KPI templates for selected KPIs
     */
    public function getKpiTemplates()
    {
        if (empty($this->selected_kpis)) {
            return collect();
        }

        return KpiTemplate::whereIn('kpi_code', $this->selected_kpis)
            ->active()
            ->get();
    }

    /**
     * Get KPIs grouped by priority
     */
    public function getKpisByPriority(): array
    {
        $templates = $this->getKpiTemplates();
        $priorities = $this->kpi_priorities ?? [];

        $grouped = [
            'critical' => [],
            'high' => [],
            'medium' => [],
            'low' => [],
        ];

        foreach ($templates as $template) {
            $priority = $priorities[$template->kpi_code] ?? $template->priority_level;
            if (isset($grouped[$priority])) {
                $grouped[$priority][] = $template;
            }
        }

        return $grouped;
    }

    /**
     * Get critical KPIs
     */
    public function getCriticalKpis()
    {
        $byPriority = $this->getKpisByPriority();

        return collect($byPriority['critical'] ?? []);
    }

    /**
     * Check if KPI is selected
     */
    public function hasKpi(string $kpiCode): bool
    {
        return in_array($kpiCode, $this->selected_kpis ?? []);
    }

    /**
     * Add KPI to configuration
     */
    public function addKpi(string $kpiCode, ?string $priority = null, ?float $weight = null): void
    {
        $selectedKpis = $this->selected_kpis ?? [];

        if (! in_array($kpiCode, $selectedKpis)) {
            $selectedKpis[] = $kpiCode;
            $this->selected_kpis = $selectedKpis;

            // Update priority if provided
            if ($priority) {
                $priorities = $this->kpi_priorities ?? [];
                $priorities[$kpiCode] = $priority;
                $this->kpi_priorities = $priorities;
            }

            // Update weight if provided
            if ($weight) {
                $weights = $this->kpi_weights ?? [];
                $weights[$kpiCode] = $weight;
                $this->kpi_weights = $weights;
            }

            $this->updateKpiCounts();
            $this->customized_by_user = true;
        }
    }

    /**
     * Remove KPI from configuration
     */
    public function removeKpi(string $kpiCode): void
    {
        $selectedKpis = $this->selected_kpis ?? [];
        $selectedKpis = array_diff($selectedKpis, [$kpiCode]);
        $this->selected_kpis = array_values($selectedKpis);

        // Remove from priorities and weights
        $priorities = $this->kpi_priorities ?? [];
        unset($priorities[$kpiCode]);
        $this->kpi_priorities = $priorities;

        $weights = $this->kpi_weights ?? [];
        unset($weights[$kpiCode]);
        $this->kpi_weights = $weights;

        $this->updateKpiCounts();
        $this->customized_by_user = true;
    }

    /**
     * Update KPI priority
     */
    public function updateKpiPriority(string $kpiCode, string $priority): void
    {
        if ($this->hasKpi($kpiCode)) {
            $priorities = $this->kpi_priorities ?? [];
            $priorities[$kpiCode] = $priority;
            $this->kpi_priorities = $priorities;

            $this->updateKpiCounts();
            $this->customized_by_user = true;
        }
    }

    /**
     * Update KPI weight
     */
    public function updateKpiWeight(string $kpiCode, float $weight): void
    {
        if ($this->hasKpi($kpiCode)) {
            $weights = $this->kpi_weights ?? [];
            $weights[$kpiCode] = $weight;
            $this->kpi_weights = $weights;

            $this->customized_by_user = true;
        }
    }

    /**
     * Update KPI counts
     */
    protected function updateKpiCounts(): void
    {
        $this->total_kpis_count = count($this->selected_kpis ?? []);

        $priorities = $this->kpi_priorities ?? [];
        $this->critical_kpis_count = count(array_filter($priorities, fn ($p) => $p === 'critical'));
    }

    /**
     * Activate configuration
     */
    public function activate(): void
    {
        $this->status = 'active';
        $this->activated_at = now();
        $this->scheduleNextReview();
        $this->save();
    }

    /**
     * Pause configuration
     */
    public function pause(): void
    {
        $this->status = 'paused';
        $this->save();
    }

    /**
     * Archive configuration
     */
    public function archive(): void
    {
        $this->status = 'archived';
        $this->save();
    }

    /**
     * Schedule next review
     */
    public function scheduleNextReview(): void
    {
        $this->next_review_due = now()->addDays($this->review_frequency_days);
    }

    /**
     * Mark as reviewed
     */
    public function markAsReviewed(): void
    {
        $this->last_reviewed_at = now();
        $this->scheduleNextReview();
        $this->save();
    }

    /**
     * Check if review is due
     */
    public function isReviewDue(): bool
    {
        if (! $this->next_review_due) {
            return false;
        }

        return $this->next_review_due <= now();
    }

    /**
     * Get notification channels
     */
    public function getNotificationChannels(): array
    {
        $settings = $this->notification_settings ?? [];

        return $settings['channels'] ?? ['email'];
    }

    /**
     * Check if notification type is enabled
     */
    public function isNotificationEnabled(string $type): bool
    {
        $settings = $this->notification_settings ?? [];

        return $settings[$type] ?? false;
    }

    /**
     * Get digest time
     */
    public function getDigestTime(): string
    {
        $settings = $this->notification_settings ?? [];

        return $settings['digest_time'] ?? '07:00';
    }

    /**
     * Calculate overall achievement rate from current performance
     */
    public function calculateOverallAchievement(): float
    {
        // This will be calculated by aggregating daily/weekly actuals
        // with weighted average based on kpi_weights

        // Placeholder for now - will be implemented with actual data
        return 0.0;
    }

    /**
     * Get configuration summary
     */
    public function getSummary(): array
    {
        return [
            'business_id' => $this->business_id,
            'industry' => $this->industry_code,
            'subcategory' => $this->sub_category,
            'size' => $this->business_size,
            'maturity' => $this->business_maturity,
            'primary_goal' => $this->primary_goal,
            'total_kpis' => $this->total_kpis_count,
            'critical_kpis' => $this->critical_kpis_count,
            'status' => $this->status,
            'achievement_rate' => $this->overall_achievement_rate,
            'is_auto_generated' => $this->is_auto_generated,
            'is_customized' => $this->customized_by_user,
            'last_reviewed' => $this->last_reviewed_at?->format('Y-m-d'),
            'next_review' => $this->next_review_due?->format('Y-m-d'),
            'review_due' => $this->isReviewDue(),
        ];
    }
}
