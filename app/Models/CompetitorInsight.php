<?php

namespace App\Models;

use App\Traits\BelongsToBusiness;
use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * CompetitorInsight - Raqobatchilar tahlili asosida tavsiyalar
 *
 * Bu model biznes egasi uchun amaliy tavsiyalarni saqlaydi.
 * Har bir tavsiya aniq, tushunarli va bajarish mumkin bo'lgan amal.
 */
class CompetitorInsight extends Model
{
    use BelongsToBusiness, HasUuid;

    protected $fillable = [
        'business_id',
        'type',
        'priority',
        'title',
        'competitor_name',
        'description',
        'recommendation',
        'action_data',
        'raw_data',
        'status',
        'is_read',
        'read_at',
        'is_completed',
        'completed_at',
        'completion_notes',
    ];

    protected $casts = [
        'action_data' => 'array',
        'raw_data' => 'array',
        'is_read' => 'boolean',
        'is_completed' => 'boolean',
        'read_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    /**
     * Tavsiya turlari
     */
    public const TYPES = [
        'price' => 'Narx strategiyasi',
        'marketing' => 'Marketing tavsiyasi',
        'product' => 'Mahsulot tavsiyasi',
        'opportunity' => 'Imkoniyat',
        'threat' => 'Ogohlantirish',
        'sales_script' => 'Sotuv skripti',
        'positioning' => 'Pozitsiyalash',
        'content' => 'Kontent strategiyasi',
    ];

    /**
     * Muhimlik darajalari
     */
    public const PRIORITIES = [
        'high' => 'Yuqori',
        'medium' => 'O\'rta',
        'low' => 'Past',
    ];

    /**
     * Statuslar
     */
    public const STATUSES = [
        'active' => 'Faol',
        'completed' => 'Bajarildi',
        'dismissed' => 'Rad etildi',
        'archived' => 'Arxivlangan',
    ];

    /**
     * Bog'liq raqobatchi (agar mavjud bo'lsa)
     */
    public function competitor(): BelongsTo
    {
        return $this->belongsTo(Competitor::class, 'competitor_name', 'name')
            ->where('business_id', $this->business_id);
    }

    /**
     * O'qilgan deb belgilash
     */
    public function markAsRead(): void
    {
        if (! $this->is_read) {
            $this->update([
                'is_read' => true,
                'read_at' => now(),
            ]);
        }
    }

    /**
     * Bajarildi deb belgilash
     */
    public function markAsCompleted(?string $notes = null): void
    {
        $this->update([
            'is_completed' => true,
            'completed_at' => now(),
            'status' => 'completed',
            'completion_notes' => $notes,
        ]);
    }

    /**
     * Rad etish
     */
    public function dismiss(): void
    {
        $this->update([
            'status' => 'dismissed',
        ]);
    }

    /**
     * Scope: Faol tavsiyalar
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Scope: Yuqori muhimlikdagi
     */
    public function scopeHighPriority($query)
    {
        return $query->where('priority', 'high');
    }

    /**
     * Scope: O'qilmagan
     */
    public function scopeUnread($query)
    {
        return $query->where('is_read', false);
    }

    /**
     * Scope: Bajarilmagan
     */
    public function scopePending($query)
    {
        return $query->where('is_completed', false)->where('status', 'active');
    }

    /**
     * Scope: Tur bo'yicha
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Tur nomini olish
     */
    public function getTypeNameAttribute(): string
    {
        return self::TYPES[$this->type] ?? $this->type;
    }

    /**
     * Muhimlik nomini olish
     */
    public function getPriorityNameAttribute(): string
    {
        return self::PRIORITIES[$this->priority] ?? $this->priority;
    }

    /**
     * Status nomini olish
     */
    public function getStatusNameAttribute(): string
    {
        return self::STATUSES[$this->status] ?? $this->status;
    }

    /**
     * Amal matni (action_data dan)
     */
    public function getActionTextAttribute(): ?string
    {
        return $this->action_data['text'] ?? null;
    }

    /**
     * Icon turi (frontend uchun)
     */
    public function getIconTypeAttribute(): string
    {
        return match ($this->type) {
            'price' => 'currency',
            'marketing' => 'megaphone',
            'product' => 'cube',
            'opportunity' => 'light-bulb',
            'threat' => 'exclamation-triangle',
            'sales_script' => 'chat-bubble',
            'positioning' => 'map-pin',
            'content' => 'document-text',
            default => 'information-circle',
        };
    }

    /**
     * Rang turi (frontend uchun)
     */
    public function getColorTypeAttribute(): string
    {
        return match ($this->priority) {
            'high' => 'red',
            'medium' => 'yellow',
            'low' => 'green',
            default => 'gray',
        };
    }
}
