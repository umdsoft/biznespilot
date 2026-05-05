<?php

namespace App\Models;

use App\Traits\BelongsToBusiness;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ActivityLog extends Model
{
    use BelongsToBusiness;

    /**
     * MUHIM: Bu fillable DB schema'siga mos. activity_logs jadvali Spatie
     * formatda dizayn qilingan: type, action, subject_*, properties (JSON),
     * changes (JSON). Eski extra columnlar (action_type, entity_type, metadata)
     * jadvalda yo'q — agar fillable'ga qo'shilsa Eloquent xato qaytaradi.
     */
    protected $fillable = [
        'business_id',
        'user_id',
        'type',          // 'auth' | 'crud' | 'integration' | ...
        'action',        // 'login' | 'logout' | 'create' | 'update' | ...
        'subject_type',  // Eloquent morph type (Lead, Order, va h.k.)
        'subject_id',
        'description',
        'properties',    // JSON — IP/UA tashqaridagi har qanday qo'shimcha
        'changes',       // JSON — old/new values diff
        'ip_address',
        'user_agent',
    ];

    protected $casts = [
        'properties' => 'array',
        'changes' => 'array',
    ];

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scopeByType($query, $type)
    {
        return $query->where('type', $type);
    }

    public function scopeByAction($query, $action)
    {
        return $query->where('action', $action);
    }

    public function scopeBySubject($query, $subjectType, $subjectId = null)
    {
        $query->where('subject_type', $subjectType);

        if ($subjectId) {
            $query->where('subject_id', $subjectId);
        }

        return $query;
    }

    public function scopeRecent($query, $hours = 24)
    {
        return $query->where('created_at', '>=', now()->subHours($hours));
    }

    public function scopeForUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Convenience: yozuv yaratish.
     * action — 'login', 'create', 'update' va h.k.
     * type   — kategoriya: 'auth', 'crud', 'integration', va h.k.
     */
    public static function log(
        ?string $businessId,
        string $action,
        string $description,
        ?string $type = null,
        ?string $subjectType = null,
        $subjectId = null,
        array $properties = []
    ): self {
        return static::create([
            'business_id' => $businessId,
            'user_id' => auth()->id(),
            'type' => $type,
            'action' => $action,
            'subject_type' => $subjectType,
            'subject_id' => $subjectId,
            'description' => $description,
            'properties' => $properties,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
    }

    public function getActionIcon(): string
    {
        return match ($this->action) {
            'login' => 'arrow-right-on-rectangle',
            'logout' => 'arrow-left-on-rectangle',
            'login_failed' => 'shield-exclamation',
            'create' => 'plus-circle',
            'update' => 'pencil',
            'delete' => 'trash',
            'view' => 'eye',
            'export' => 'arrow-down-tray',
            'import' => 'arrow-up-tray',
            'sync' => 'arrow-path',
            'alert' => 'bell-alert',
            'report' => 'document-chart-bar',
            'ai_generate' => 'sparkles',
            default => 'information-circle',
        };
    }

    public function getActionName(): string
    {
        return match ($this->action) {
            'login' => 'Kirish',
            'logout' => 'Chiqish',
            'login_failed' => 'Muvaffaqiyatsiz kirish',
            'create' => 'Yaratish',
            'update' => 'Yangilash',
            'delete' => 'O\'chirish',
            'view' => 'Ko\'rish',
            'export' => 'Eksport',
            'import' => 'Import',
            'sync' => 'Sinxronlash',
            'alert' => 'Ogohlantirish',
            'report' => 'Hisobot',
            'ai_generate' => 'AI generatsiya',
            default => (string) $this->action,
        };
    }
}
