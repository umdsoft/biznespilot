<?php

namespace App\Models;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class OrgDepartment extends Model
{
    use HasUuid;

    protected $fillable = [
        'org_structure_id',
        'department_template_id',
        'name',
        'code',
        'color',
        'icon',
        'yqm_description',
        'parent_id',
        'order',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    // ==================== Relationships ====================

    public function orgStructure(): BelongsTo
    {
        return $this->belongsTo(OrgStructure::class);
    }

    public function departmentTemplate(): BelongsTo
    {
        return $this->belongsTo(DepartmentTemplate::class);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(OrgDepartment::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(OrgDepartment::class, 'parent_id');
    }

    public function positions(): HasMany
    {
        return $this->hasMany(OrgPosition::class);
    }

    // ==================== Scopes ====================

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeRootOnly($query)
    {
        return $query->whereNull('parent_id');
    }

    // ==================== Helper Methods ====================

    public function getTotalEmployees(): int
    {
        return $this->positions()->sum('current_count');
    }

    public function getRequiredEmployees(): int
    {
        return $this->positions()->sum('required_count');
    }

    public function getYqmDescriptionAttribute($value): ?string
    {
        return $value ?? $this->departmentTemplate?->yqm_description;
    }
}
