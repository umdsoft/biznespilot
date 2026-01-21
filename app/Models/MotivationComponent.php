<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Motivation Component - individual parts of a motivation scheme:
 * - Fixed Salary (Qattiq oklad)
 * - Soft Salary (Yumshoq oklad) - function-based
 * - Bonus - result-based
 * - Penalty - deduction coefficient
 */
class MotivationComponent extends Model
{
    use HasUuids;

    protected $fillable = [
        'motivation_scheme_id',
        'component_type', // fixed_salary, soft_salary, bonus, penalty
        'name',
        'description',
        'base_amount',
        'max_amount',
        'calculation_type', // fixed, percentage, formula, scale
        'percentage_of', // revenue, profit, plan_completion
        'percentage_value',
        'function_requirements', // For soft salary
        'kpi_linkage', // For bonus
        'scale_table', // For scale-based calculation
        'weight',
        'order',
    ];

    protected $casts = [
        'base_amount' => 'decimal:2',
        'max_amount' => 'decimal:2',
        'percentage_value' => 'decimal:2',
        'function_requirements' => 'array',
        'kpi_linkage' => 'array',
        'scale_table' => 'array',
    ];

    // Relationships
    public function motivationScheme(): BelongsTo
    {
        return $this->belongsTo(MotivationScheme::class);
    }

    // Calculate amount based on settings
    public function calculateAmount(array $context = []): float
    {
        return match($this->calculation_type) {
            'fixed' => $this->base_amount,
            'percentage' => $this->calculatePercentage($context),
            'scale' => $this->calculateFromScale($context),
            'formula' => $this->calculateFormula($context),
            default => $this->base_amount,
        };
    }

    // Calculate percentage-based amount
    protected function calculatePercentage(array $context): float
    {
        $baseValue = $context[$this->percentage_of] ?? 0;
        return $baseValue * ($this->percentage_value / 100);
    }

    // Calculate from scale table
    protected function calculateFromScale(array $context): float
    {
        $kpiScore = $context['kpi_score'] ?? 0;
        $kpiPercent = $kpiScore * 100;

        if (!$this->scale_table) {
            return $this->base_amount;
        }

        foreach ($this->scale_table as $range) {
            if ($kpiPercent >= ($range['min'] ?? 0) && $kpiPercent < ($range['max'] ?? 100)) {
                return $this->base_amount * ($range['coefficient'] ?? 1);
            }
        }

        // If KPI >= 100%, use the last coefficient
        $lastRange = end($this->scale_table);
        if ($kpiPercent >= ($lastRange['max'] ?? 100)) {
            return $this->base_amount * ($lastRange['coefficient'] ?? 1);
        }

        return 0;
    }

    // Calculate formula-based (for complex calculations)
    protected function calculateFormula(array $context): float
    {
        // This can be extended for custom formulas
        return $this->base_amount;
    }

    // Calculate soft salary completion
    public function calculateSoftSalaryCompletion(array $completedRequirements = []): array
    {
        if ($this->component_type !== 'soft_salary' || !$this->function_requirements) {
            return ['earned' => $this->base_amount, 'percent' => 100];
        }

        $totalWeight = 0;
        $earnedWeight = 0;

        foreach ($this->function_requirements as $requirement) {
            $weight = $requirement['weight'] ?? 0;
            $totalWeight += $weight;

            $requirementId = $requirement['id'] ?? $requirement['name'];
            if (in_array($requirementId, $completedRequirements)) {
                $earnedWeight += $weight;
            }
        }

        $completionPercent = $totalWeight > 0 ? ($earnedWeight / $totalWeight) * 100 : 0;
        $earnedAmount = $this->base_amount * ($completionPercent / 100);

        return [
            'earned' => $earnedAmount,
            'percent' => $completionPercent,
            'total_weight' => $totalWeight,
            'earned_weight' => $earnedWeight,
        ];
    }

    // Get type label
    public function getTypeLabelAttribute(): string
    {
        return match($this->component_type) {
            'fixed_salary' => 'Qattiq oklad',
            'soft_salary' => 'Yumshoq oklad',
            'bonus' => 'Bonus',
            'penalty' => 'Jarima',
            default => $this->component_type,
        };
    }
}
