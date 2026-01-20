<?php

namespace App\Models;

use App\Traits\BelongsToBusiness;
use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LeadScoreHistory extends Model
{
    use BelongsToBusiness, HasUuid;

    /**
     * Jadval nomi
     */
    protected $table = 'lead_score_history';

    /**
     * Faqat created_at timestampi
     */
    public const UPDATED_AT = null;

    /**
     * O'zgarish sabablari
     */
    public const REASON_INITIAL = 'initial_score';

    public const REASON_DATA_UPDATED = 'data_updated';

    public const REASON_ACTIVITY_CREATED = 'activity_created';

    public const REASON_CALL_MADE = 'call_made';

    public const REASON_TASK_COMPLETED = 'task_completed';

    public const REASON_STAGE_CHANGED = 'stage_changed';

    public const REASON_DECAY = 'score_decay';

    public const REASON_MANUAL = 'manual_adjustment';

    public const REASON_RECALCULATED = 'recalculated';

    public const REASONS = [
        self::REASON_INITIAL => 'Dastlabki ball',
        self::REASON_DATA_UPDATED => 'Ma\'lumot yangilandi',
        self::REASON_ACTIVITY_CREATED => 'Faollik yaratildi',
        self::REASON_CALL_MADE => 'Qo\'ng\'iroq qilindi',
        self::REASON_TASK_COMPLETED => 'Vazifa bajarildi',
        self::REASON_STAGE_CHANGED => 'Bosqich o\'zgardi',
        self::REASON_DECAY => 'Nofaollik tushishi',
        self::REASON_MANUAL => 'Qo\'lda o\'zgartirish',
        self::REASON_RECALCULATED => 'Qayta hisoblangan',
    ];

    /**
     * Trigger turlari
     */
    public const TRIGGERED_BY_SYSTEM = 'system';

    public const TRIGGERED_BY_USER = 'user';

    public const TRIGGERED_BY_SCHEDULED = 'scheduled';

    public const TRIGGERED_BY_WEBHOOK = 'webhook';

    /**
     * Fillable attributes
     */
    protected $fillable = [
        'uuid',
        'lead_id',
        'business_id',
        'old_score',
        'new_score',
        'change_amount',
        'old_category',
        'new_category',
        'reason',
        'details',
        'triggered_by',
        'user_id',
    ];

    /**
     * Casts
     */
    protected $casts = [
        'old_score' => 'integer',
        'new_score' => 'integer',
        'change_amount' => 'integer',
        'details' => 'array',
        'created_at' => 'datetime',
    ];

    /**
     * Lead relationship
     */
    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class);
    }

    /**
     * User relationship
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Business relationship
     */
    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    /**
     * Sabab nomini olish
     */
    public function getReasonNameAttribute(): string
    {
        return self::REASONS[$this->reason] ?? $this->reason;
    }

    /**
     * O'zgarish yo'nalishi (up/down/same)
     */
    public function getDirectionAttribute(): string
    {
        if ($this->change_amount > 0) {
            return 'up';
        }

        if ($this->change_amount < 0) {
            return 'down';
        }

        return 'same';
    }

    /**
     * Kategoriya o'zgarganmi
     */
    public function getCategoryChangedAttribute(): bool
    {
        return $this->old_category !== $this->new_category;
    }

    /**
     * Scope: Lead bo'yicha
     */
    public function scopeForLead($query, string $leadId)
    {
        return $query->where('lead_id', $leadId);
    }

    /**
     * Scope: Sana oralig'i bo'yicha
     */
    public function scopeDateRange($query, $from, $to)
    {
        return $query->whereBetween('created_at', [$from, $to]);
    }

    /**
     * Scope: Sabab bo'yicha
     */
    public function scopeReason($query, string $reason)
    {
        return $query->where('reason', $reason);
    }

    /**
     * Yangi yozuv yaratish
     */
    public static function log(
        Lead $lead,
        int $oldScore,
        int $newScore,
        ?string $oldCategory,
        ?string $newCategory,
        string $reason,
        ?array $details = null,
        string $triggeredBy = self::TRIGGERED_BY_SYSTEM,
        ?string $userId = null
    ): self {
        return self::create([
            'lead_id' => $lead->id,
            'business_id' => $lead->business_id,
            'old_score' => $oldScore,
            'new_score' => $newScore,
            'change_amount' => $newScore - $oldScore,
            'old_category' => $oldCategory,
            'new_category' => $newCategory,
            'reason' => $reason,
            'details' => $details,
            'triggered_by' => $triggeredBy,
            'user_id' => $userId ?? auth()->id(),
        ]);
    }
}
