<?php

namespace App\Models;

use App\Traits\BelongsToBusiness;
use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class OrgStructure extends Model
{
    use BelongsToBusiness, HasUuid;

    protected $fillable = [
        'business_id',
        'business_type_id',
        'name',
        'description',
        'is_active',
        'is_template_based',
        'created_from_template_id',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_template_based' => 'boolean',
    ];

    // ==================== Relationships ====================

    public function businessType(): BelongsTo
    {
        return $this->belongsTo(BusinessType::class);
    }

    public function departments(): HasMany
    {
        return $this->hasMany(OrgDepartment::class);
    }

    // ==================== Scopes ====================

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeFromTemplate($query)
    {
        return $query->where('is_template_based', true);
    }

    // ==================== Helper Methods ====================

    public function getTotalPositionsCount(): int
    {
        return $this->departments()
            ->withCount('positions')
            ->get()
            ->sum('positions_count');
    }

    public function getFilledPositionsCount(): int
    {
        $total = 0;
        foreach ($this->departments as $dept) {
            foreach ($dept->positions as $position) {
                $total += $position->current_count;
            }
        }
        return $total;
    }

    public function getFillRate(): float
    {
        $totalRequired = 0;
        $totalFilled = 0;

        foreach ($this->departments as $dept) {
            foreach ($dept->positions as $position) {
                $totalRequired += $position->required_count;
                $totalFilled += $position->current_count;
            }
        }

        return $totalRequired > 0 ? ($totalFilled / $totalRequired) * 100 : 0;
    }
}
