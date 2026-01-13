<?php

namespace App\Models;

use App\Traits\BelongsToBusiness;
use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SalaryHistory extends Model
{
    use BelongsToBusiness, HasUuid;

    protected $table = 'salary_history';

    protected $fillable = [
        'business_id',
        'user_id',
        'old_salary',
        'new_salary',
        'change_amount',
        'change_percentage',
        'reason',
        'notes',
        'effective_date',
        'changed_by',
    ];

    protected $casts = [
        'old_salary' => 'decimal:2',
        'new_salary' => 'decimal:2',
        'change_amount' => 'decimal:2',
        'change_percentage' => 'decimal:2',
        'effective_date' => 'date',
    ];

    // ==================== Relationships ====================

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function changedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'changed_by');
    }

    // ==================== Accessors ====================

    public function getIsIncreaseAttribute(): bool
    {
        return $this->change_amount > 0;
    }

    public function getIsDecreaseAttribute(): bool
    {
        return $this->change_amount < 0;
    }

    // ==================== Scopes ====================

    public function scopeForUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeRecent($query, $days = 30)
    {
        return $query->where('effective_date', '>=', now()->subDays($days));
    }
}
