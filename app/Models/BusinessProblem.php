<?php

namespace App\Models;

use App\Traits\BelongsToBusiness;
use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;

class BusinessProblem extends Model
{
    use BelongsToBusiness, HasUuid;

    protected $fillable = [
        'business_id',
        'problem_category',
        'problem_description',
        'impact_level',
        'when_started',
        'attempts_to_solve',
        'desired_outcome',
        'success_metrics',
        'priority',
        'status',
        'resolved_at',
    ];

    protected $casts = [
        'when_started' => 'date',
        'attempts_to_solve' => 'array',
        'resolved_at' => 'datetime',
    ];

    // Constants
    public const CATEGORIES = [
        'revenue' => 'Daromad muammosi',
        'leads' => 'Lidlar yetarli emas',
        'conversion' => 'Konversiya past',
        'retention' => 'Mijozlar ketib qolmoqda',
        'awareness' => 'Brend tanilmagan',
        'other' => 'Boshqa',
    ];

    public const IMPACT_LEVELS = [
        'critical' => 'Juda jiddiy',
        'high' => 'Yuqori',
        'medium' => 'O\'rtacha',
        'low' => 'Past',
    ];

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeResolved($query)
    {
        return $query->where('status', 'resolved');
    }

    public function scopeCritical($query)
    {
        return $query->where('impact_level', 'critical');
    }

    public function scopeByCategory($query, string $category)
    {
        return $query->where('problem_category', $category);
    }

    public function scopeOrderByPriority($query)
    {
        return $query->orderBy('priority');
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('created_at', 'desc');
    }

    // Helpers
    public function getCategoryLabel(): string
    {
        return self::CATEGORIES[$this->problem_category] ?? $this->problem_category;
    }

    public function getImpactLabel(): string
    {
        return self::IMPACT_LEVELS[$this->impact_level] ?? $this->impact_level;
    }

    public function resolve(): void
    {
        $this->update([
            'status' => 'resolved',
            'resolved_at' => now(),
        ]);
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function isCritical(): bool
    {
        return $this->impact_level === 'critical';
    }
}
