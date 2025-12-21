<?php

namespace App\Models;

use App\Traits\BelongsToBusiness;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ActivityLog extends Model
{
    use BelongsToBusiness;

    protected $fillable = [
        'business_id',
        'user_id',
        'action',
        'action_category',
        'action_type',
        'model_type',
        'model_id',
        'entity_type',
        'entity_id',
        'entity_name',
        'description',
        'changes',
        'old_values',
        'new_values',
        'metadata',
        'ip_address',
        'user_agent',
        'session_id',
        'device_type',
        'browser',
        'os',
        'country',
        'city',
        'is_important',
        'is_system',
    ];

    protected $casts = [
        'changes' => 'array',
        'old_values' => 'array',
        'new_values' => 'array',
        'metadata' => 'array',
        'is_important' => 'boolean',
        'is_system' => 'boolean',
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
        return $query->where('activity_type', $type);
    }

    public function scopeByEntity($query, $entityType, $entityId = null)
    {
        $query->where('entity_type', $entityType);

        if ($entityId) {
            $query->where('entity_id', $entityId);
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

    public static function log(
        $businessId,
        $activityType,
        $description,
        $entityType = null,
        $entityId = null,
        $metadata = null
    ) {
        return static::create([
            'business_id' => $businessId,
            'user_id' => auth()->id(),
            'activity_type' => $activityType,
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'description' => $description,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'metadata' => $metadata,
            'created_at' => now(),
        ]);
    }

    public function getActivityTypeIcon()
    {
        return match ($this->activity_type) {
            'login' => 'arrow-right-on-rectangle',
            'logout' => 'arrow-left-on-rectangle',
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

    public function getActivityTypeName()
    {
        return match ($this->activity_type) {
            'login' => 'Kirish',
            'logout' => 'Chiqish',
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
            default => $this->activity_type,
        };
    }
}
