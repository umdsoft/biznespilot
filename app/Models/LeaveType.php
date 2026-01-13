<?php

namespace App\Models;

use App\Traits\BelongsToBusiness;
use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LeaveType extends Model
{
    use BelongsToBusiness, HasUuid;

    protected $fillable = [
        'business_id',
        'name',
        'code',
        'description',
        'default_days_per_year',
        'requires_approval',
        'is_paid',
        'carry_forward',
        'max_carry_forward_days',
        'is_active',
        'notice_days',
        'max_consecutive_days',
        'allowed_for_departments',
    ];

    protected $casts = [
        'requires_approval' => 'boolean',
        'is_paid' => 'boolean',
        'carry_forward' => 'boolean',
        'is_active' => 'boolean',
        'allowed_for_departments' => 'array',
    ];

    // ==================== Relationships ====================

    public function leaveBalances(): HasMany
    {
        return $this->hasMany(LeaveBalance::class);
    }

    public function leaveRequests(): HasMany
    {
        return $this->hasMany(LeaveRequest::class);
    }

    // ==================== Scopes ====================

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeForDepartment($query, $department)
    {
        return $query->where(function ($q) use ($department) {
            $q->whereNull('allowed_for_departments')
                ->orWhereJsonContains('allowed_for_departments', $department);
        });
    }

    // ==================== Methods ====================

    /**
     * Get predefined leave types
     */
    public static function getPredefinedTypes(): array
    {
        return [
            [
                'name' => 'Yillik Ta\'til',
                'code' => 'annual',
                'description' => 'Yillik rejalashtirilgan ta\'til',
                'default_days_per_year' => 20,
                'requires_approval' => true,
                'is_paid' => true,
                'carry_forward' => true,
                'max_carry_forward_days' => 5,
                'notice_days' => 7,
            ],
            [
                'name' => 'Kasallik Ta\'tili',
                'code' => 'sick',
                'description' => 'Kasallik tufayli ta\'til',
                'default_days_per_year' => 10,
                'requires_approval' => true,
                'is_paid' => true,
                'carry_forward' => false,
                'notice_days' => 0,
            ],
            [
                'name' => 'Oilaviy Ta\'til',
                'code' => 'family',
                'description' => 'Oilaviy vaziyat tufayli ta\'til',
                'default_days_per_year' => 5,
                'requires_approval' => true,
                'is_paid' => true,
                'carry_forward' => false,
                'notice_days' => 3,
            ],
            [
                'name' => 'To\'lovdan Tashqari',
                'code' => 'unpaid',
                'description' => 'To\'lovsiz ta\'til',
                'default_days_per_year' => 0,
                'requires_approval' => true,
                'is_paid' => false,
                'carry_forward' => false,
                'notice_days' => 14,
            ],
        ];
    }

    /**
     * Create default leave types for business
     */
    public static function createDefaultTypes($businessId): void
    {
        foreach (self::getPredefinedTypes() as $type) {
            $type['business_id'] = $businessId;
            $type['is_active'] = true;
            self::create($type);
        }
    }

    /**
     * Check if user's department is allowed
     */
    public function isAllowedForDepartment(?string $department): bool
    {
        if (empty($this->allowed_for_departments)) {
            return true; // All departments allowed
        }

        return in_array($department, $this->allowed_for_departments);
    }
}
