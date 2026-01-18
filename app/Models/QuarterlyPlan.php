<?php

namespace App\Models;

use App\Traits\BelongsToBusiness;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class QuarterlyPlan extends Model
{
    use BelongsToBusiness;

    protected $fillable = [
        'uuid',
        'business_id',
        'annual_strategy_id',
        'year',
        'quarter',
        'title',
        'status',
        'theme',
        'executive_summary',
        'quarterly_objectives',
        'goals',
        'key_results',
        'revenue_target',
        'budget',
        'lead_target',
        'customer_target',
        'initiatives',
        'campaigns',
        'experiments',
        'channel_priorities',
        'channel_budget',
        'resource_requirements',
        'team_assignments',
        'ai_recommendations',
        'ai_summary',
        'confidence_score',
        'completion_percent',
        'monthly_breakdown',
        'actual_results',
        'approved_at',
        'completed_at',
    ];

    protected $casts = [
        'quarterly_objectives' => 'array',
        'goals' => 'array',
        'key_results' => 'array',
        'initiatives' => 'array',
        'campaigns' => 'array',
        'experiments' => 'array',
        'channel_priorities' => 'array',
        'channel_budget' => 'array',
        'resource_requirements' => 'array',
        'team_assignments' => 'array',
        'ai_recommendations' => 'array',
        'monthly_breakdown' => 'array',
        'actual_results' => 'array',
        'revenue_target' => 'decimal:2',
        'budget' => 'decimal:2',
        'approved_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public const QUARTERS = [
        1 => 'Q1 (Yanvar-Mart)',
        2 => 'Q2 (Aprel-Iyun)',
        3 => 'Q3 (Iyul-Sentabr)',
        4 => 'Q4 (Oktabr-Dekabr)',
    ];

    public const STATUSES = [
        'draft' => 'Qoralama',
        'active' => 'Faol',
        'completed' => 'Tugallangan',
        'archived' => 'Arxivlangan',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->uuid)) {
                $model->uuid = (string) Str::uuid();
            }
        });
    }

    // Relationships
    public function annualStrategy(): BelongsTo
    {
        return $this->belongsTo(AnnualStrategy::class);
    }

    public function monthlyPlans(): HasMany
    {
        return $this->hasMany(MonthlyPlan::class);
    }

    public function kpiTargets(): HasMany
    {
        return $this->hasMany(KpiTarget::class);
    }

    public function budgetAllocations(): HasMany
    {
        return $this->hasMany(BudgetAllocation::class);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeForYear($query, int $year)
    {
        return $query->where('year', $year);
    }

    public function scopeForQuarter($query, int $quarter)
    {
        return $query->where('quarter', $quarter);
    }

    public function scopeCurrent($query)
    {
        return $query->where('year', now()->year)
            ->where('quarter', ceil(now()->month / 3));
    }

    // Helpers
    public function getQuarterLabel(): string
    {
        return self::QUARTERS[$this->quarter] ?? "Q{$this->quarter}";
    }

    public function getStatusLabel(): string
    {
        return self::STATUSES[$this->status] ?? $this->status;
    }

    public function getMonthsInQuarter(): array
    {
        $startMonth = (($this->quarter - 1) * 3) + 1;

        return [$startMonth, $startMonth + 1, $startMonth + 2];
    }

    public function getStartDate(): \Carbon\Carbon
    {
        $startMonth = (($this->quarter - 1) * 3) + 1;

        return \Carbon\Carbon::create($this->year, $startMonth, 1);
    }

    public function getEndDate(): \Carbon\Carbon
    {
        $endMonth = $this->quarter * 3;

        return \Carbon\Carbon::create($this->year, $endMonth, 1)->endOfMonth();
    }

    public function approve(): void
    {
        $this->update([
            'status' => 'active',
            'approved_at' => now(),
        ]);
    }

    public function complete(array $results = []): void
    {
        $this->update([
            'status' => 'completed',
            'completed_at' => now(),
            'actual_results' => $results,
        ]);

        // Update parent progress
        $this->annualStrategy?->updateProgress();
    }

    public function updateProgress(): void
    {
        $months = $this->monthlyPlans()->count();
        if ($months === 0) {
            $this->update(['completion_percent' => 0]);

            return;
        }

        $completed = $this->monthlyPlans()->where('status', 'completed')->count();
        $percent = round(($completed / $months) * 100);
        $this->update(['completion_percent' => $percent]);
    }
}
