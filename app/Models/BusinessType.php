<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BusinessType extends Model
{
    protected $fillable = [
        'code',
        'name_uz',
        'name_ru',
        'name_en',
        'icon',
        'color',
        'description_uz',
        'description_ru',
        'has_templates',
        'is_active',
        'order',
    ];

    protected $casts = [
        'has_templates' => 'boolean',
        'is_active' => 'boolean',
    ];

    // ==================== Relationships ====================

    public function departmentTemplates(): HasMany
    {
        return $this->hasMany(DepartmentTemplate::class);
    }

    public function orgStructures(): HasMany
    {
        return $this->hasMany(OrgStructure::class);
    }

    // ==================== Scopes ====================

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeWithTemplates($query)
    {
        return $query->where('has_templates', true);
    }

    // ==================== Accessors ====================

    public function getNameAttribute(): string
    {
        $locale = app()->getLocale();

        return $this->{"name_{$locale}"} ?? $this->name_uz;
    }

    public function getDescriptionAttribute(): ?string
    {
        $locale = app()->getLocale();

        return $this->{"description_{$locale}"} ?? $this->description_uz;
    }
}
