<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class StrategyTemplate extends Model
{
    protected $fillable = [
        'uuid',
        'name',
        'slug',
        'description',
        'type',
        'industry',
        'business_size',
        'goals_template',
        'kpis_template',
        'budget_template',
        'content_template',
        'channels_template',
        'activities_template',
        'ai_system_prompt',
        'ai_generation_prompt',
        'is_default',
        'is_active',
        'is_premium',
        'usage_count',
        'avg_success_rate',
        'icon',
        'color',
        'thumbnail_url',
    ];

    protected $casts = [
        'goals_template' => 'array',
        'kpis_template' => 'array',
        'budget_template' => 'array',
        'content_template' => 'array',
        'channels_template' => 'array',
        'activities_template' => 'array',
        'is_default' => 'boolean',
        'is_active' => 'boolean',
        'is_premium' => 'boolean',
        'avg_success_rate' => 'decimal:2',
    ];

    public const TYPES = [
        'annual' => 'Yillik strategiya',
        'quarterly' => 'Choraklik reja',
        'monthly' => 'Oylik reja',
        'weekly' => 'Haftalik reja',
        'content' => 'Kontent kalendar',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->uuid)) {
                $model->uuid = (string) Str::uuid();
            }
            if (empty($model->slug)) {
                $model->slug = Str::slug($model->name);
            }
        });
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeForType($query, string $type)
    {
        return $query->where('type', $type);
    }

    public function scopeDefault($query)
    {
        return $query->where('is_default', true);
    }

    public function scopeForIndustry($query, ?string $industry)
    {
        return $query->where(function ($q) use ($industry) {
            $q->whereNull('industry')
                ->orWhere('industry', $industry);
        });
    }

    public function scopeFree($query)
    {
        return $query->where('is_premium', false);
    }

    // Helpers
    public function getTypeLabel(): string
    {
        return self::TYPES[$this->type] ?? $this->type;
    }

    public function incrementUsage(): void
    {
        $this->increment('usage_count');
    }

    public function updateSuccessRate(float $rate): void
    {
        $totalUsage = $this->usage_count;
        if ($totalUsage === 0) {
            $this->update(['avg_success_rate' => $rate]);
            return;
        }

        // Moving average
        $current = $this->avg_success_rate ?? 0;
        $newAvg = (($current * ($totalUsage - 1)) + $rate) / $totalUsage;
        $this->update(['avg_success_rate' => round($newAvg, 2)]);
    }

    public function getGoalsForPlan(): array
    {
        return $this->goals_template ?? [];
    }

    public function getKpisForPlan(): array
    {
        return $this->kpis_template ?? [];
    }

    public function getBudgetBreakdown(): array
    {
        return $this->budget_template ?? [];
    }

    public function getContentSchedule(): array
    {
        return $this->content_template ?? [];
    }

    public function getChannelDistribution(): array
    {
        return $this->channels_template ?? [];
    }

    public function getActivitiesSchedule(): array
    {
        return $this->activities_template ?? [];
    }

    public function getColorClass(): string
    {
        $color = $this->color ?? 'gray';
        return "bg-{$color}-100 text-{$color}-700";
    }

    public function getIconClass(): string
    {
        return $this->icon ?? 'document-text';
    }
}
