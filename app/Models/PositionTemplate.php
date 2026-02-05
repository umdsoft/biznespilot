<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PositionTemplate extends Model
{
    protected $fillable = [
        'department_template_id',
        'code',
        'title_uz',
        'title_ru',
        'title_en',
        'level',
        'reports_to',
        'yqm_primary',
        'yqm_description',
        'yqm_metrics',
        'responsibilities',
        'success_criteria',
        'requirements',
        'default_count',
        'salary_min',
        'salary_max',
        'order',
        'is_active',
    ];

    protected $casts = [
        'yqm_metrics' => 'array',
        'responsibilities' => 'array',
        'success_criteria' => 'array',
        'requirements' => 'array',
        'is_active' => 'boolean',
    ];

    public function departmentTemplate(): BelongsTo
    {
        return $this->belongsTo(DepartmentTemplate::class);
    }
}
