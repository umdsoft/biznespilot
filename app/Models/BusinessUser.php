<?php

namespace App\Models;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\Pivot;

class BusinessUser extends Pivot
{
    use HasUuid;

    protected $table = 'business_user';

    public $incrementing = false;

    protected $keyType = 'string';

    const DEPARTMENTS = [
        'sales_head' => 'Sotuv bo\'limi rahbari',
        'marketing' => 'Marketing bo\'limi',
        'sales_operator' => 'Sotuv operatorlari',
        'hr' => 'HR bo\'limi',
        'finance' => 'Moliya bo\'limi',
    ];

    const ROLES = [
        'admin' => 'Administrator',
        'manager' => 'Menejer',
        'member' => 'Xodim',
        'viewer' => 'Ko\'ruvchi',
    ];

    const EMPLOYMENT_TYPES = [
        'full_time' => 'To\'liq ish kuni',
        'part_time' => 'Yarim stavka',
        'contract' => 'Shartnoma asosida',
        'internship' => 'Amaliyot',
        'temporary' => 'Vaqtinchalik',
    ];

    const CONTRACT_TYPES = [
        'unlimited' => 'Muddatsiz',
        'fixed_term' => 'Muddatli',
        'probation' => 'Sinov muddati',
    ];

    protected $fillable = [
        'business_id',
        'user_id',
        'role',
        'employee_code',
        'badge_id',
        'face_id',
        'department',
        'job_description_id',
        'salary',
        'employment_type',
        'contract_type',
        'contract_start_date',
        'contract_end_date',
        'permissions',
        'invited_at',
        'accepted_at',
        'joined_at',
        'invited_by',
        'invitation_token',
        'invitation_expires_at',
    ];

    protected $casts = [
        'permissions' => 'array',
        'salary' => 'decimal:2',
        'invited_at' => 'datetime',
        'accepted_at' => 'datetime',
        'joined_at' => 'datetime',
        'invitation_expires_at' => 'datetime',
        'contract_start_date' => 'date',
        'contract_end_date' => 'date',
    ];

    /**
     * Get department label
     */
    public function getDepartmentLabelAttribute(): ?string
    {
        return self::DEPARTMENTS[$this->department] ?? null;
    }

    /**
     * Get role label
     */
    public function getRoleLabelAttribute(): ?string
    {
        return self::ROLES[$this->role] ?? $this->role;
    }

    /**
     * Get employment type label
     */
    public function getEmploymentTypeLabelAttribute(): ?string
    {
        return self::EMPLOYMENT_TYPES[$this->employment_type] ?? $this->employment_type;
    }

    /**
     * Get contract type label
     */
    public function getContractTypeLabelAttribute(): ?string
    {
        return self::CONTRACT_TYPES[$this->contract_type] ?? $this->contract_type;
    }

    /**
     * Check if invitation is pending
     */
    public function isPending(): bool
    {
        return $this->invited_at && ! $this->accepted_at;
    }

    /**
     * Check if invitation is expired
     */
    public function isExpired(): bool
    {
        return $this->invitation_expires_at && $this->invitation_expires_at->isPast();
    }

    /**
     * Get the user
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the business
     */
    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    /**
     * Get the inviter
     */
    public function inviter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'invited_by');
    }

    /**
     * Get the job description (position)
     */
    public function jobDescription(): BelongsTo
    {
        return $this->belongsTo(JobDescription::class);
    }
}
