<?php

namespace App\Models;

use App\Traits\BelongsToBusiness;
use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;

class BusinessSuccessStory extends Model
{
    use BelongsToBusiness, HasUuid;

    protected $fillable = [
        'business_id',
        'industry',
        'sub_industry',
        'initial_score',
        'final_score',
        'growth_percent',
        'duration_months',
        'actions_taken',
        'metrics_before',
        'metrics_after',
        'display_name',
        'is_anonymous',
        'is_featured',
    ];

    protected $casts = [
        'actions_taken' => 'array',
        'metrics_before' => 'array',
        'metrics_after' => 'array',
        'is_anonymous' => 'boolean',
        'is_featured' => 'boolean',
        'growth_percent' => 'decimal:2',
    ];

    // Scopes
    public function scopeByIndustry($query, string $industry)
    {
        return $query->where('industry', $industry);
    }

    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true);
    }

    public function scopeHighGrowth($query, int $minPercent = 50)
    {
        return $query->where('growth_percent', '>', $minPercent);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('growth_percent', 'desc');
    }

    // Helpers
    public function getDisplayName(): string
    {
        if ($this->is_anonymous) {
            return "O'xshash biznes";
        }

        return $this->display_name ?? $this->business?->name ?? "O'xshash biznes";
    }

    public function getScoreImprovement(): int
    {
        return ($this->final_score ?? 0) - ($this->initial_score ?? 0);
    }

    public function getActionsList(): array
    {
        if (! $this->actions_taken) {
            return [];
        }

        return collect($this->actions_taken)->pluck('action')->toArray();
    }

    public function toApiArray(): array
    {
        return [
            'name' => $this->getDisplayName(),
            'industry' => $this->industry,
            'before_score' => $this->initial_score,
            'after_score' => $this->final_score,
            'growth_percent' => $this->growth_percent,
            'duration_months' => $this->duration_months,
            'actions' => $this->getActionsList(),
        ];
    }
}
