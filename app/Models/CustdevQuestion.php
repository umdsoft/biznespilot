<?php

namespace App\Models;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CustdevQuestion extends Model
{
    use HasFactory, HasUuid;

    protected $fillable = [
        'survey_id',
        'type',
        'category',
        'question',
        'description',
        'placeholder',
        'options',
        'required',
        'is_default',
        'order',
        'icon',
        'settings',
    ];

    protected $casts = [
        'options' => 'array',
        'settings' => 'array',
        'required' => 'boolean',
        'is_default' => 'boolean',
    ];

    protected $appends = ['is_required'];

    /**
     * Get is_required attribute (alias for required)
     */
    public function getIsRequiredAttribute(): bool
    {
        return $this->required ?? true;
    }

    /**
     * Set is_required attribute (alias for required)
     */
    public function setIsRequiredAttribute($value): void
    {
        $this->attributes['required'] = $value;
    }

    /**
     * Get question type label
     */
    public function getTypeLabel(): string
    {
        return match($this->type) {
            'text' => 'Qisqa matn',
            'textarea' => 'Uzun matn',
            'select' => 'Bitta tanlash',
            'multiselect' => 'Ko\'p tanlash',
            'rating' => 'Reyting (yulduzlar)',
            'scale' => 'Shkala (1-10)',
            default => $this->type,
        };
    }

    /**
     * Get category label
     */
    public function getCategoryLabel(): string
    {
        return match($this->category) {
            'where_spend_time' => 'Vaqt va Joy',
            'info_sources' => 'Ma\'lumot Manbalari',
            'frustrations' => 'Muammolar',
            'dreams' => 'Orzular',
            'fears' => 'Qo\'rquvlar',
            'custom' => 'Boshqa',
            default => $this->category ?? 'Boshqa',
        };
    }

    // Relationships

    public function survey(): BelongsTo
    {
        return $this->belongsTo(CustdevSurvey::class, 'survey_id');
    }

    public function answers(): HasMany
    {
        return $this->hasMany(CustdevAnswer::class, 'question_id');
    }

    public function analytics(): HasMany
    {
        return $this->hasMany(CustdevAnalytics::class, 'question_id');
    }
}
