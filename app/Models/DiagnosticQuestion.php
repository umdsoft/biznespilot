<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class DiagnosticQuestion extends Model
{
    protected $fillable = [
        'uuid',
        'diagnostic_id',
        'question_category',
        'question_text_uz',
        'question_text_en',
        'data_point_referenced',
        'why_asking',
        'answer_options',
        'answer_text',
        'answered_at',
        'impact_on_diagnosis',
        'sort_order',
        'is_required',
        'is_answered',
    ];

    protected $casts = [
        'data_point_referenced' => 'array',
        'answer_options' => 'array',
        'answered_at' => 'datetime',
        'is_required' => 'boolean',
        'is_answered' => 'boolean',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->uuid)) {
                $model->uuid = (string) Str::uuid();
            }
        });
    }

    // Constants
    public const CATEGORIES = [
        'revenue' => 'Daromad',
        'marketing' => 'Marketing',
        'sales' => 'Sotuvlar',
        'content' => 'Kontent',
        'operations' => 'Operatsiyalar',
        'general' => 'Umumiy',
    ];

    // Relationships
    public function diagnostic(): BelongsTo
    {
        return $this->belongsTo(AIDiagnostic::class, 'diagnostic_id');
    }

    // Scopes
    public function scopeUnanswered($query)
    {
        return $query->where('is_answered', false);
    }

    public function scopeAnswered($query)
    {
        return $query->where('is_answered', true);
    }

    public function scopeRequired($query)
    {
        return $query->where('is_required', true);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order');
    }

    // Helpers
    public function answer(string $answerText): void
    {
        $this->update([
            'answer_text' => $answerText,
            'answered_at' => now(),
            'is_answered' => true,
        ]);
    }

    public function getCategoryLabel(): string
    {
        return self::CATEGORIES[$this->question_category] ?? $this->question_category;
    }

    public function getQuestionText(string $locale = 'uz'): string
    {
        return $locale === 'en' && $this->question_text_en
            ? $this->question_text_en
            : $this->question_text_uz;
    }

    public function hasMultipleChoice(): bool
    {
        return !empty($this->answer_options);
    }
}
