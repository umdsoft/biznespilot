<?php

namespace App\Models;

use App\Traits\BelongsToBusiness;
use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * TurnoverRecord - Hodim ketish yozuvlari
 *
 * Turnover analytics va retention tahlili uchun
 */
class TurnoverRecord extends Model
{
    use BelongsToBusiness, HasUuid;

    protected $table = 'turnover_records';

    protected $fillable = [
        'business_id',
        'user_id',
        // Asosiy ma'lumotlar
        'termination_type',
        'termination_reason',
        'termination_reason_details',
        'hire_date',
        'termination_date',
        'tenure_months',
        // Lavozim
        'department',
        'position',
        'manager_id',
        // Exit interview
        'exit_interview_completed',
        'exit_interview_data',
        'exit_satisfaction_score',
        'would_recommend_employer',
        'would_return',
        // Tahlil
        'was_high_performer',
        'was_flight_risk',
        'last_engagement_score',
        'last_flight_risk_level',
        'is_regrettable',
        // Almashtirish
        'replacement_needed',
        'replacement_hired',
        'replacement_user_id',
        'days_to_fill',
    ];

    protected $casts = [
        'hire_date' => 'date',
        'termination_date' => 'date',
        'tenure_months' => 'integer',
        'exit_interview_completed' => 'boolean',
        'exit_interview_data' => 'array',
        'exit_satisfaction_score' => 'float',
        'would_recommend_employer' => 'boolean',
        'would_return' => 'boolean',
        'was_high_performer' => 'boolean',
        'was_flight_risk' => 'boolean',
        'last_engagement_score' => 'float',
        'is_regrettable' => 'boolean',
        'replacement_needed' => 'boolean',
        'replacement_hired' => 'boolean',
        'days_to_fill' => 'integer',
    ];

    // Ketish turlari
    public const TYPE_VOLUNTARY = 'voluntary';
    public const TYPE_INVOLUNTARY = 'involuntary';
    public const TYPE_RETIREMENT = 'retirement';
    public const TYPE_CONTRACT_END = 'contract_end';

    // Relationships
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function manager(): BelongsTo
    {
        return $this->belongsTo(User::class, 'manager_id');
    }

    public function replacement(): BelongsTo
    {
        return $this->belongsTo(User::class, 'replacement_user_id');
    }

    // Scopes
    public function scopeVoluntary($query)
    {
        return $query->where('termination_type', self::TYPE_VOLUNTARY);
    }

    public function scopeInvoluntary($query)
    {
        return $query->where('termination_type', self::TYPE_INVOLUNTARY);
    }

    public function scopeInPeriod($query, $startDate, $endDate)
    {
        return $query->whereBetween('termination_date', [$startDate, $endDate]);
    }

    public function scopeByDepartment($query, string $department)
    {
        return $query->where('department', $department);
    }

    public function scopeWithoutExitInterview($query)
    {
        return $query->where('exit_interview_completed', false);
    }

    // Accessors
    public function getTypeLabelAttribute(): string
    {
        return match($this->termination_type) {
            self::TYPE_VOLUNTARY => "O'z xohishi bilan",
            self::TYPE_INVOLUNTARY => 'Majburiy',
            self::TYPE_RETIREMENT => 'Pensiyaga chiqish',
            self::TYPE_CONTRACT_END => 'Shartnoma tugashi',
            default => "Boshqa",
        };
    }

    public function getTenureLabelAttribute(): string
    {
        $months = $this->tenure_months;

        if ($months < 3) {
            return '3 oydan kam';
        } elseif ($months < 6) {
            return '3-6 oy';
        } elseif ($months < 12) {
            return '6-12 oy';
        } elseif ($months < 24) {
            return '1-2 yil';
        } elseif ($months < 36) {
            return '2-3 yil';
        } elseif ($months < 60) {
            return '3-5 yil';
        } else {
            return '5 yildan ko\'p';
        }
    }

    // Methods
    public function isVoluntary(): bool
    {
        return $this->termination_type === self::TYPE_VOLUNTARY;
    }

    public function completeExitInterview(array $responses): bool
    {
        return $this->update([
            'exit_interview_completed' => true,
            'exit_survey_responses' => $responses,
            'exit_survey_date' => now(),
        ]);
    }

    public static function calculateTurnoverRate(Business $business, int $months = 12): float
    {
        $startDate = now()->subMonths($months);
        $terminations = self::where('business_id', $business->id)
            ->where('termination_date', '>=', $startDate)
            ->count();

        $averageEmployees = $business->users()->count();

        return $averageEmployees > 0
            ? round(($terminations / $averageEmployees) * 100, 2)
            : 0;
    }
}
