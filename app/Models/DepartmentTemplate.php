<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DepartmentTemplate extends Model
{
    protected $fillable = [
        'code',
        'name_uz',
        'name_ru',
        'name_en',
        'icon',
        'color',
        'type',
        'business_type_id',
        'yqm_description',
        'responsibilities',
        'order',
        'is_active',
    ];

    protected $casts = [
        'responsibilities' => 'array',
        'is_active' => 'boolean',
    ];

    // ==================== Relationships ====================

    public function businessType(): BelongsTo
    {
        return $this->belongsTo(BusinessType::class);
    }

    public function positionTemplates(): HasMany
    {
        return $this->hasMany(PositionTemplate::class);
    }

    public function orgDepartments(): HasMany
    {
        return $this->hasMany(OrgDepartment::class);
    }

    // ==================== Scopes ====================

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeStatic($query)
    {
        return $query->where('type', 'static');
    }

    public function scopeDynamic($query)
    {
        return $query->where('type', 'dynamic');
    }

    public function scopeForBusinessType($query, $businessTypeId)
    {
        return $query->where(function ($q) use ($businessTypeId) {
            $q->where('type', 'static')
                ->orWhere(function ($q2) use ($businessTypeId) {
                    $q2->where('type', 'dynamic')
                        ->where('business_type_id', $businessTypeId);
                });
        });
    }

    // ==================== Accessors ====================

    public function getNameAttribute(): string
    {
        $locale = app()->getLocale();

        return $this->{"name_{$locale}"} ?? $this->name_uz;
    }

    public function getIsStaticAttribute(): bool
    {
        return $this->type === 'static';
    }

    public function getIsDynamicAttribute(): bool
    {
        return $this->type === 'dynamic';
    }
}
