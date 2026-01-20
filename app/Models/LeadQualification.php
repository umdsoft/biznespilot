<?php

namespace App\Models;

use App\Traits\BelongsToBusiness;
use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * LeadQualification - Lead qualification tarixi
 * MQL/SQL o'tishlarini kuzatish uchun
 */
class LeadQualification extends Model
{
    use BelongsToBusiness, HasUuid;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'business_id',
        'lead_id',
        'from_status',
        'to_status',
        'qualified_by',
        'reason',
        'criteria_snapshot',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'criteria_snapshot' => 'array',
    ];

    // ==========================================
    // RELATIONSHIPS
    // ==========================================

    /**
     * Get the lead.
     */
    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class);
    }

    /**
     * Get the user who qualified the lead.
     */
    public function qualifiedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'qualified_by');
    }

    // ==========================================
    // SCOPES
    // ==========================================

    /**
     * Scope: Qualifications to MQL.
     */
    public function scopeToMql(Builder $query): Builder
    {
        return $query->where('to_status', 'mql');
    }

    /**
     * Scope: Qualifications to SQL.
     */
    public function scopeToSql(Builder $query): Builder
    {
        return $query->where('to_status', 'sql');
    }

    /**
     * Scope: Disqualifications.
     */
    public function scopeDisqualified(Builder $query): Builder
    {
        return $query->where('to_status', 'disqualified');
    }

    /**
     * Scope: Filter by date range.
     */
    public function scopeCreatedBetween(Builder $query, $from, $to): Builder
    {
        return $query->whereBetween('created_at', [$from, $to]);
    }

    // ==========================================
    // HELPER METHODS
    // ==========================================

    /**
     * Check if this was a promotion (new -> mql, mql -> sql).
     */
    public function isPromotion(): bool
    {
        $order = ['new' => 1, 'mql' => 2, 'sql' => 3, 'disqualified' => 0];

        return ($order[$this->to_status] ?? 0) > ($order[$this->from_status] ?? 0);
    }

    /**
     * Check if this was a demotion.
     */
    public function isDemotion(): bool
    {
        return $this->to_status === 'disqualified' && $this->from_status !== 'disqualified';
    }

    /**
     * Get status change description.
     */
    public function getChangeDescription(): string
    {
        $labels = [
            'new' => 'Yangi',
            'mql' => 'MQL',
            'sql' => 'SQL',
            'disqualified' => 'Rad etildi',
        ];

        $from = $labels[$this->from_status] ?? $this->from_status;
        $to = $labels[$this->to_status] ?? $this->to_status;

        return "{$from} → {$to}";
    }
}
