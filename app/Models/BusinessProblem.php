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
        'category',
        'title',
        'description',
        'severity',
        'status',
        'potential_solutions',
    ];

    protected $casts = [
        'potential_solutions' => 'array',
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

    public const SEVERITY_LEVELS = [
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
        return $query->where('severity', 'critical');
    }

    public function scopeByCategory($query, string $category)
    {
        return $query->where('category', $category);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('created_at', 'desc');
    }

    // Helpers
    public function getCategoryLabel(): string
    {
        return self::CATEGORIES[$this->category] ?? $this->category;
    }

    public function getSeverityLabel(): string
    {
        return self::SEVERITY_LEVELS[$this->severity] ?? $this->severity;
    }

    public function resolve(): void
    {
        $this->update([
            'status' => 'resolved',
        ]);
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function isCritical(): bool
    {
        return $this->severity === 'critical';
    }
}
