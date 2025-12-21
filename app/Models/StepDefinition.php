<?php

namespace App\Models;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StepDefinition extends Model
{
    use HasUuid;
    public $timestamps = false;

    protected $fillable = [
        'code',
        'phase',
        'category',
        'name_uz',
        'name_en',
        'description_uz',
        'description_en',
        'is_required',
        'depends_on',
        'required_fields',
        'completion_rules',
        'icon',
        'estimated_time_minutes',
        'help_url',
        'sort_order',
        'is_active',
    ];

    protected $casts = [
        'is_required' => 'boolean',
        'is_active' => 'boolean',
        'depends_on' => 'array',
        'required_fields' => 'array',
        'completion_rules' => 'array',
    ];

    // Relationships
    public function onboardingSteps(): HasMany
    {
        return $this->hasMany(OnboardingStep::class);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeRequired($query)
    {
        return $query->where('is_required', true);
    }

    public function scopeForPhase($query, int $phase)
    {
        return $query->where('phase', $phase);
    }

    public function scopeForCategory($query, string $category)
    {
        return $query->where('category', $category);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('phase')->orderBy('sort_order');
    }

    // Helpers
    public function getName(string $locale = 'uz'): string
    {
        return $locale === 'en' ? $this->name_en : $this->name_uz;
    }

    public function getDescription(string $locale = 'uz'): ?string
    {
        return $locale === 'en' ? $this->description_en : $this->description_uz;
    }

    public function getDependencies(): array
    {
        return $this->depends_on ?? [];
    }

    public function hasDependencies(): bool
    {
        return !empty($this->depends_on);
    }
}
