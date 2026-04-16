<?php

namespace App\Models;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Operator uchun avtomatik yaratilgan coaching vazifasi.
 * Ball past bo'lsa — avtomatik yaratiladi.
 */
class OperatorCoachingTask extends Model
{
    use HasUuid;

    protected $fillable = [
        'business_id', 'operator_id', 'call_analysis_id',
        'title', 'description', 'weak_area',
        'score_at_creation', 'priority', 'status',
        'due_date', 'completed_at', 'completion_notes',
    ];

    protected $casts = [
        'score_at_creation' => 'decimal:2',
        'due_date' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public const WEAK_AREA_LABELS = [
        'greeting' => 'Salomlashish',
        'discovery' => 'Ehtiyoj aniqlash',
        'presentation' => 'Taqdimot',
        'objection_handling' => 'E\'tirozlarni hal qilish',
        'closing' => 'Yopish',
        'rapport' => 'Munosabat qurish',
        'cta' => 'Keyingi qadam',
        'script_compliance' => 'Skript bajarish',
        'talk_ratio' => 'Gaplashish balansi',
        'sentiment' => 'Mijoz kayfiyati',
    ];

    public const PRIORITY_LABELS = [
        'low' => 'Past',
        'medium' => 'O\'rta',
        'high' => 'Yuqori',
        'urgent' => 'Shoshilinch',
    ];

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    public function operator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'operator_id');
    }

    public function callAnalysis(): BelongsTo
    {
        return $this->belongsTo(CallAnalysis::class);
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeForOperator($query, string $operatorId)
    {
        return $query->where('operator_id', $operatorId);
    }

    public function markCompleted(?string $notes = null): void
    {
        $this->update([
            'status' => 'completed',
            'completed_at' => now(),
            'completion_notes' => $notes,
        ]);
    }

    public function getWeakAreaLabelAttribute(): string
    {
        return self::WEAK_AREA_LABELS[$this->weak_area] ?? $this->weak_area;
    }
}
